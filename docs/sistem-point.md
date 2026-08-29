# Sistem Point (Flat Point + Redemption) — SH3 Event Management

> Dokumentasi ini mendeskripsikan **implementasi aktual** yang berjalan di repository saat ini (`app/`, `database/migrations/`, `routes/`, `tests/`), bukan asumsi dan bukan business logic baru.
> Source of truth (berurutan): `.omo/plans/flat-point-redemption.md` → source code aktual → migrations → routes → tests.
> Setiap penyimpangan antara plan dan kode ditandai eksplisit di **§ 29. Discrepancy (Plan vs Implementasi)**.

---

## 1. Overview & Goals

Sistem Point adalah sub-sistem terpusat untuk memberi **reward berupa poin datar (flat point)** kepada peserta yang melakukan check-in event yang valid, dan mengizinkan penukaran (**redemption**) poin tersebut dengan merchandise.

Tujuan utama:
- Memberi insentif kehadiran (attendance) bagi peserta dengan membership aktif.
- Menjaga integritas saldo poin sebagai **append-only ledger** (`point_transactions`) yang dapat direkonstruksi ulang (reconcilable) dari `SUM(amount)`.
- Mencegah duplikasi poin (idempotency) dan saldo negatif (guarded via throw).
- Mengecualikan secara mutlak peserta OTS (on-the-spot) dan aggregator dari perolehan poin.
- Menangani reversal/refund poin secara otomatis saat attendance di-invalidate atau pembayaran event/merchandise ditolak/refund.

Modul terkait: **Attendance, Membership, Merchandise, Payment, Participant**.

---

## 2. Design Principles

1. **Ledger append-only (immutable).** Baris `point_transactions` hanya di-INSERT. Tidak ada UPDATE/DELETE pada baris ledger. Reversal dilakukan sebagai baris `REVERSAL` baru, bukan mengubah baris `EARN`/`REDEEM` asli. Historical ledger tidak boleh dimutasi atau dihapus untuk memperbaiki saldo.
2. **Server-authoritative.** Poin HANYA diberikan dari check-in attendance yang valid. Rate dihitung di server dari membership history peserta pada waktu check-in, lalu di-*snapshot* ke baris ledger. Client tidak pernah mengirim nilai poin/rate/harga.
3. **Flat rate, bukan konversi Rupiah.** Tidak ada konstanta global "1 poin = Rp X". Rate per check-in = `membership_plans.point_per_event_checkin` dari plan yang **aktif** saat check-in. Redemption dikonfigurasi **per merchandise** (Model B).
4. **Denormalized cache yang selalu recomputable.** `participants.point_balance` hanyalah cache; nilai sejati (source of truth) adalah `SUM(amount)` di `point_transactions`. `PointService::reconcileBalance()` menimpa cache dari ledger.
5. **Idempotency via UNIQUE index + service-level check.** Defense-in-depth: cek `findSource()` di service + `UNIQUE(source_type, source_id)` di DB.
6. **Non-negative invariant.** Saldo tidak boleh negatif; redemption/adjustment/reversal yang akan membuat negatif akan **throw**, bukan di-clamp diam-diam.

---

## 3. Terminology & Glossary

| Istilah | Arti |
|---|---|
| `point_balance` | Kolom denormalisasi di `participants` (cache saldo). |
| `ledger_balance` | `SUM(amount)` dari `point_transactions` untuk peserta (source of truth). |
| `EARN` | Baris ledger: poin masuk dari attendance valid (amount > 0). |
| `REDEEM` | Baris ledger: poin keluar untuk redemption merchandise (amount < 0). |
| `REVERSAL` | Baris ledger: pengembalian poin (amount > 0) saat EARN/REDEEM dibatalkan. |
| `ADJUSTMENT` | Baris ledger: koreksi manual oleh admin (amount bertanda). |
| `rate` / `point_rate` | Poin datar per check-in (di-snapshot ke ledger). |
| `source_type` / `source_id` | Pelabelan asal transaksi (`attendance` / `merchandise_order` + id). |
| OTS | On-The-Spot check-in. Dapat berupa aggregator (`hash_id` = `OTS_AGGREGATOR_CODE`) atau registrasi member bercode QR `OTS-*`. |
| Model B | Redemption di mana diskon per-unit dihitung dari `price - price_after_points` per merchandise. |
| Idempotency | Satu sumber (attendance/order/reversal) hanya menghasilkan satu ledger row bertipe terkait. |

---

## 4. Architecture Overview

Layered architecture (sesuai `rules.md`):

```
Presentation
  API:  PointController, AttendanceController, MerchandiseController
  Web:  Admin/ParticipantController (show + reconcile), Blade participants/show.blade.php
  Resource: ParticipantResource (point_balance, ledger_balance)
        ↓
Business (Services)
  PointService            — orchestrator ledger (earn/redeem/reverse/refund/reconcile/adjust)
  PointRateService        — resolveAt() resolver flat rate
  AttendanceService       — trigger earnForAttendance pada check-in / sync-up
  MerchandiseService      — trigger redeemForOrder pada order
  PaymentService          — trigger refundRedemption / invalidateAttendance pada reject/refund
        ↓
Data (Repositories)
  PointTransactionRepository — ledger reads/writes
        ↓
Models
  PointTransaction, Participant, MembershipHistory, MembershipPlan,
  Merchandise, MerchandiseOrder, Attendance, EventParticipant, Payment
        ↓
Database
  point_transactions, participants(point_balance),
  membership_plans(point_per_event_checkin),
  merchandise(points_required, price_after_points),
  merchandise_orders(*_snapshot, points_used, discount_amount),
  attendances(is_invalid, ...), payments(refunded_at, refunded_by)
```

Semua mutasi poin dijalankan **di dalam transaksi milik caller** (DB::transaction di `AttendanceService`/`MerchandiseService`/`PaymentService`) sehingga ledger dan cache `point_balance` atomik dengan event pemicunya.

---

## 5. Database Schema

### 5.1 `point_transactions` (migration `2026_08_28_195444_create_point_transactions_table.php`)
Ledger append-only.
- `id`, `participant_id` (FK cascade), `type` ENUM(`EARN`,`REDEEM`,`REVERSAL`,`ADJUSTMENT`), `amount` INTEGER (bertanda), `source_type` nullable, `source_id` nullable.
- Metadata: `membership_plan_id`, `point_rate`, `event_id`, `attendance_id`, `merchandise_order_id`, `ref`, `adjusted_by` (FK users), `note`, `timestamps`.
- Index: `idx_point_tx_participant_created (participant_id, created_at)`.
- **`UNIQUE(source_type, source_id)`** → jaminan idempotensi. (NULL source dikecualikan dari uniqueness, sehingga `ADJUSTMENT` tanpa source tetap unik.)

### 5.2 `participants.point_balance` (migration `2026_08_28_195441_...`)
Kolom `integer default 0` — cache saldo, recomputable via `reconcileBalance()`.

### 5.3 `membership_plans.point_per_event_checkin` (migration `2026_08_28_195442_...`)
`integer default 0` — flat poin per check-in event untuk plan yang aktif. Di-snapshot ke `point_transactions.point_rate` saat EARN.

### 5.4 `merchandise.points_required` + `merchandise.price_after_points` (migration `2026_08_28_195443_...`)
- `points_required` INTEGER nullable — poin per-unit untuk redeem. NULL = tidak redeemable.
- `price_after_points` DECIMAL(12,2) nullable — harga cash per-unit setelah diskon poin. `(price - price_after_points)` = diskon per-unit.
- Guard konfigurasi: `price_after_points` harus `>= 0` dan `<= price` (di-enforce di `MerchandiseRequest`/validasi).

### 5.5 `merchandise_orders` snapshot columns (sama migration 5.4)
`points_used`, `discount_amount`, `unit_price_snapshot`, `quantity_snapshot`, `points_per_unit_snapshot`, `discount_per_unit_snapshot`, `cash_amount_snapshot` — di-capture saat order dibuat agar perubahan harga/konfigurasi nanti tidak mengubah kewajiban order yang sudah jalan. `total_price` di-aim ke cash final (setelah diskon poin).

### 5.6 `attendances` invalidation columns (migration `2026_08_28_195445_...`)
`is_invalid`, `invalidated_by`, `invalidated_at`, `invalidation_reason` — menandai attendance yang dibatalkan validitasnya (memicu reversal EARN).

### 5.7 `payments` refund columns (migration `2026_08_28_195446_...`)
`refunded_at`, `refunded_by` — menandai pembayaran yang di-refund (idempotensi refund).

### 5.8 `participants.point_balance` check (migration `2026_08_28_195447_...`)
Constraint/check terkait konsistensi `point_balance`.

---

## 6. Module Breakdown

| Komponen | File | Tanggung jawab |
|---|---|---|
| `PointService` | `app/Services/PointService.php` | earn/redeem/reverse/refund/reconcile/adjust; konstanta `SOURCE_ATTENDANCE='attendance'`, `SOURCE_ORDER='merchandise_order'`. |
| `PointRateService` | `app/Services/PointRateService.php` | `resolveAt()` → rate flat dari membership history aktif. |
| `PointTransactionRepository` | `app/Repositories/PointTransactionRepository.php` | `findSource`, `historyForParticipant`, `balanceFromLedger`, `transaction`. |
| `PointController` | `app/Http/Controllers/API/PointController.php` | `balance`, `history` (API peserta). |
| `AttendanceService` | `app/Services/AttendanceService.php` | panggil `earnForAttendance` di `checkIn` (~baris 92) & `syncUpOffline` (~baris 554); `invalidateAttendance` (reverse EARN). |
| `MerchandiseService` | `app/Services/MerchandiseService.php` | `createOrder` (redeem), `cancelOrder` (refund). |
| `PaymentService` | `app/Services/PaymentService.php` | `rejectPayment`/`refundPayment` → refund redemption / invalidate attendance. |
| `ParticipantResource` | `app/Http/Resources/ParticipantResource.php` | ekspos `point_balance` + `ledger_balance`. |
| `Admin/ParticipantController` | `app/Http/Controllers/Admin/ParticipantController.php` | `show()` (ledgerBalance + pointHistory), `reconcilePoints()`. |
| `Merchandise` model | `app/Models/Merchandise.php` | `isPointRedeemable()`, `discountPerUnit()`. |
| `PointTransaction` model | `app/Models/PointTransaction.php` | konstanta `TYPE_EARN`/`TYPE_REDEEM`/`TYPE_REVERSAL`/`TYPE_ADJUSTMENT`. |

---

## 7. Point Lifecycle / User Journey

```
[Check-in valid]
   → AttendanceService.checkIn / syncUpOffline
   → PointService.earnForAttendance
        ├─ OTS/aggregator?            → return null (0 poin)
        ├─ sudah ada EARN untuk att?  → idempoten (kembalikan yg ada)
        ├─ rate = 0 (non-member)?     → return null (0 poin)
        └─ else → INSERT EARN (+rate), increment point_balance

[Order merchandise pakai poin]
   → MerchandiseService.createOrder
   → lock participant FOR UPDATE
   → hitung pointsUsed = points_required * qty
   → saldo cukup? → INSERT REDEEM (-pointsUsed), decrement point_balance
   → buat payment (total_price = cash setelah diskon)

[Pembatalan / Penolakan / Refund]
   → cancelOrder / rejectPayment / refundPayment
   → PointService.refundRedemption  → INSERT REVERSAL (+pointsUsed), increment
   → (event) invalidateAttendance   → INSERT REVERSAL (+rate EARN), increment

[Admin perbaiki drift]
   → ParticipantController.reconcilePoints
   → PointService.reconcileBalance → point_balance = SUM(ledger)

[Admin koreksi manual]
   → PointService.adminAdjust (TYPE_ADJUSTMENT, signed, adjusted_by, note)
```

---

## 8. Point Earning Rules

Flow wajib (sesuai business rule):

```
Valid Attendance
      ↓
Eligibility
      ↓
Membership Rate
      ↓
Server-side Calculation
      ↓
EARN
      ↓
Point Balance
```

Implementasi di `PointService::earnForAttendance(Attendance, Event, Participant)`:

1. **Hard invariant (H1): Manual-OTS aggregator tidak pernah earn.** `if ($participant->isOtsAggregator()) return null;`
2. **Hard invariant: Member-OTS registration (QR `OTS-*`) tidak earn.** `if ($this->isOtsRegistration($attendance->eventParticipant)) return null;` — deteksi via prefix `OTS-` pada `qr_code`.
3. **Idempotency:** `findSource(SOURCE_ATTENDANCE, attendance_id)` → jika ada, kembalikan yang ada (tidak dobel).
4. **Eligibility + Membership Rate:** `PointRateService::resolveAt($participant, now())`. Jika `rate <= 0` → `return null` (0 poin, mis. non-member).
5. **Server-side Calculation + EARN:** INSERT `EARN` dengan `amount = rate`, snapshot `membership_plan_id`, `point_rate`, `event_id`, `attendance_id`; lalu `participant->increment('point_balance', rate)`.

OTS (hard exclusion):

```
OTS Aggregator      → 0 point
OTS Registration    → 0 point
```

Poin diberikan **hanya sekali per attendance** berkat UNIQUE index + guard idempotensi ganda (service-level + DB-level).

---

## 9. Point Rate Resolution

Rate berasal dari:

```
membership_plans.point_per_event_checkin
```

berdasarkan **membership history yang aktif pada waktu check-in**.

`PointRateService::resolveAt(Participant $participant, Carbon $date)` — **mencerminkan (mirror) `MembershipService::checkEligibility()`**:
- Cari `MembershipHistory` peserta yang `status = active` dan `start_date <= $date <= end_date` (aktif pada waktu check-in).
- Jika ditemukan: `rate = plan->point_per_event_checkin`, `plan = plan aktif tersebut`. Rate di-snapshot ke ledger → edit plan di masa depan tidak retroaktif.
- Jika tidak ada membership aktif (non-member): `rate = config('points.non_member_rate', 0)` (default `0`). → Non-member earn 0.

Konfigurasi: `config/points.php` → `'non_member_rate' => env('POINTS_NON_MEMBER_RATE', 0)`.

> Rate dibaca dari **membership history** (bukan cache `participants.membership_type`), sesuai prinsip server-authoritative.

---

## 10. Point Redemption

Gunakan **Model B**:

```
points_required × quantity
```

dan:

```
discountPerUnit
=
price - price_after_points
```

Implementasi di `MerchandiseService::createOrder()`:
1. Lock `merchandise` & `participant` **`FOR UPDATE`** (serialisasi stock + saldo).
2. Validasi stock & size.
3. Jika `use_points` && `merchandise->isPointRedeemable()`:
   - `pointsPerUnit = (int) merchandise->points_required`
   - `discountPerUnit = merchandise->discountPerUnit()` = `round(price - price_after_points)`
   - `pointsUsed = pointsPerUnit * quantity`, `discountAmount = discountPerUnit * quantity`
   - **Safety cap:** `discountAmount` tidak boleh melebihi subtotal (`price * quantity`).
   - Jika `participant->point_balance < pointsUsed` → `ValidationException` 422 (poin tidak cukup).
   - Simpan snapshot: `points_per_unit_snapshot`, `discount_per_unit_snapshot`, `cash_amount_snapshot`, `unit_price_snapshot`, `quantity_snapshot`, `points_used`, `discount_amount`.
4. `cashAmount = max(0, price*qty - discountAmount)`; `total_price` order = cash final.
5. `PointService::redeemForOrder($participant, $order, $pointsUsed)` → INSERT `REDEEM` (-pointsUsed), decrement saldo. Idempoten per order.
6. Buat `Payment` (amount = `total_price`).

Perhitungan dilakukan **server-side**; client hanya mengirim `use_points: true/false` + `quantity`. `Participant` di-lock `FOR UPDATE` **sebelum** validasi saldo/redemption.

`Merchandise::isPointRedeemable()` = `points_required !== null && points_required > 0`.
`Merchandise::discountPerUnit()` = `round((price ?? 0) - (price_after_points ?? 0))`.

---

## 11. Point Reversal / Refund

Semua reversal adalah baris `REVERSAL` baru (EARN/REDEEM asli tidak diubah — **original ledger transaction tetap dipertahankan**).

Attendance invalid:

```
EARN +100
   ↓
invalidate attendance
   ↓
REVERSAL +100
```

Implementasi: `AttendanceService::invalidateAttendance()` → set `is_invalid`, `invalidated_by`, `invalidated_at`, `invalidation_reason`; panggil `PointService::reverseEarn()` → INSERT `REVERSAL (+rate)`, increment. Idempoten (attendance sudah invalid → no-op). Jika reversal akan membuat saldo negatif → **di-BLOCK (throw)**, bukan clamp; admin harus pakai `adminAdjust`.

Merchandise cancellation/refund:

```
REDEEM -100
   ↓
cancel/refund
   ↓
REVERSAL +100
```

Implementasi:
- `PointService::refundRedemption(Participant, MerchandiseOrder)` — untuk order dibatalkan (`MerchandiseService::cancelOrder`) / payment ditolak. Idempoten via `source_type='merchandise_order'`, `source_id = reversal_order`.
- `PaymentService::rejectPayment()` — untuk `EventParticipant` payment: invalidate attendance → reverseEarn. Untuk `MerchandiseOrder` payment: `refundRedemption`. Idempoten.
- `PaymentService::refundPayment()` — sama; `refunded_at`/`refunded_by` dipakai sebagai guard idempotensi (sudah `refunded` → return no-op).

---

## 12. Point Adjustment

`PointService::adminAdjust(Participant $participant, int $amount, int $adminUserId, string $reason)`:
- `amount` tidak boleh 0.
- Menjaga invariant non-negative: jika `point_balance + amount < 0` → throw.
- INSERT `TYPE_ADJUSTMENT` dengan `adjusted_by`, `note=reason`; increment/decrement `point_balance`.
- Jalur koreksi manual yang diaudit; digunakan ketika reversal ter-block karena saldo negatif, atau untuk koreksi administratif lainnya.

---

## 13. Idempotency

```text
1 Attendance → maximum 1 EARN
1 Order      → maximum 1 REDEEM
1 Reversal   → maximum 1 REVERSAL
```

Defense-in-depth (dua lapis):
- **Service-level check:** `PointService` memanggil `repository->findSource(sourceType, sourceId)` sebelum INSERT → panggilan berulang mengembalikan transaksi yang sudah ada (tidak membuat duplikat).
- **Database UNIQUE constraint:** `UNIQUE(source_type, source_id)` pada `point_transactions` (migration `195444`) → duplikasi INSERT dilempar sebagai `UniqueConstraintViolationException`. Di `AttendanceService::checkIn`/`syncUpOffline`, exception ini ditangkap dan dianggap sukses idempoten.

Sumber idempotensi:
- `EARN` → `source_id = attendance_id`
- `REDEEM` → `source_id = merchandise_order_id`
- `REVERSAL` → `source_id = referenced tx/order` (reversal tidak pernah dobel-apply)

---

## 14. Concurrency

- **Check-in race:** `UNIQUE(event_participant_id)` pada `attendances` (migration `195441`) mencegah dua attendance untuk satu registrasi. Dua check-in bersamaan tidak dapat membuat dua attendance (dan dua EARN).
- **Redemption race:** `participant` di-lock `FOR UPDATE` di `createOrder` → saldo & stock ter-serialisasi; cek `point_balance < pointsUsed` dilakukan setelah lock.
- **Reversal race:** guard idempotensi + UNIQUE index mencegah double-reversal meski `rejectPayment`/`refundPayment` dipanggil bersamaan.
- **Ledger SUM:** `balanceFromLedger` membaca `SUM(amount)` — konsisten meski banyak konkuren, karena ledger append-only dan tiap mutasi transactional.

> Catatan: test concurrency (`PointAuditFindingsTest`) di-skip di environment container karena `pcntl_fork` tidak tersedia; bukan kegagalan (lihat § 26).

---

## 15. Point Balance vs Ledger Consistency

`point_transactions` adalah **source of truth**. `participants.point_balance` adalah **denormalized cache**.

```
ledger SUM
    ↓
reconcileBalance()
    ↓
point_balance
```

- `PointController::balance` mengembalikan **both** `balance` (cache) dan `ledger_balance` (SUM) agar klien dapat mendeteksi drift.
- `PointService::reconcileBalance($id)` = `point_balance = balanceFromLedger($id)` (overwrite, tanpa menulis ledger).
- Admin dapat memicu reconcile via tombol di halaman peserta (lihat § 17).
- **Historical ledger tidak boleh dimutasi atau dihapus untuk memperbaiki saldo.** Satu-satunya cara memperbaiki cache yang drift adalah `reconcileBalance()` (ledger tetap otoritatif).
- Drift yang diketahui: mutasi manual di luar Service (jarang), atau bug — selalu dapat diperbaiki dengan reconcile.

---

## 16. Security

### Client tidak authoritative

Dokumentasi menegaskan bahwa **client tidak pernah authoritative** terhadap nilai-nilai berikut. Seluruh nilai dihitung/divalidasi di server:

- `point` (jumlah poin yang diberikan)
- `point balance` (saldo poin)
- `point rate` (rate per check-in)
- `total point` (akumulasi)
- `unit price` (harga satuan merchandise)
- `total price` (total harga cash)
- `discount` (diskon poin)
- `payment amount` (jumlah pembayaran)
- `membership rate` (rate dari plan membership)

Client hanya mengirimkan intent (`use_points: true/false`, `quantity`, `size`, dll); server yang menghitung `pointsUsed`, `discountAmount`, `cashAmount`, dan menulis ledger.

### H4 IDOR Fix

Endpoint attendance mensyaratkan ownership: identitas peserta **harus berasal dari authenticated user**, bukan dari parameter input client.

Implementasi: `AttendanceController` (API) mengambil participant dari `$request->user()->participants()->firstOrFail()` — bukan dari `participant_id` yang dikirim client. Ini menutup IDOR (attacker tidak dapat check-in atas nama peserta lain / mengklaim poin peserta lain).

Otorisasi lain:
- API point (`/points/*`) butuh Sanctum bearer; peserta hanya lihat miliknya sendiri via `auth()->user()->participants()->first()`.
- Endpoint reconcile admin (`POST /admin/participants/{id}/reconcile-points`) masuk grup `admin_full_access, admin_member`.
- Mass assignment: `PointTransaction` ditulis lewat Repository/Service (tidak ada `create()` langsung dari request).
- CSRF: form admin pakai `@csrf`.
- Idempotensi mencegah duplikasi poin via replay request.

---

## 17. Admin UI / Operations

- **Halaman peserta** (`resources/views/participants/show.blade.php`):
  - Card **"Poin Reward"**: menampilkan `point_balance` (cache) + badge konsistensi vs `ledger_balance`. Jika tidak konsisten → tampil peringatan + tombol **Reconcile**.
  - Tabel riwayat poin (`pointHistory`): EARN/REDEEM/REVERSAL/ADJUSTMENT dengan sumber & waktu.
  - Responsif: wrapper `overflow-x-auto`, `whitespace-nowrap` (sesuai `docs/13 — Responsive Layout & Table Rules`).
- **Tombol Reconcile:** form `POST` dengan `@csrf` ke `route('admin.participants.reconcile-points', $participant)`, hanya muncul saat inkonsisten.
- **Controller:** `Admin/ParticipantController::show()` mengirim `ledgerBalance` + `pointHistory`; `reconcilePoints()` memanggil `PointService::reconcileBalance()`.
- **Route:** `POST /admin/participants/{id}/reconcile-points` (name `admin.participants.reconcile-points`), grup `admin_full_access, admin_member`.

---

## 18. API Endpoints

### Authenticated peserta (Sanctum)
| Method | Endpoint | Deskripsi | Response |
|---|---|---|---|
| GET | `/api/v1/points/balance` | Saldo poin peserta | `data.participant_id`, `data.balance`, `data.ledger_balance` |
| GET | `/api/v1/points/history` | Ledger paginasi | `data` (items: id, type, amount, source_type, source_id, point_rate, event/merchandiseOrder, created_at, note), meta paginasi |

Route terdaftar di `routes/api.php` baris 117–118.

### Pemicu poin (implicit)
| Method | Endpoint | Efek poin |
|---|---|---|
| POST | `/api/v1/attendance/check-in` | EARN (jika member aktif & bukan OTS) |
| POST | `/api/v1/attendance/sync-up` | EARN (offline sync, non-OTS) |
| POST | `/api/v1/merchandise/order` | REDEEM (jika `use_points`) |
| POST | `/api/v1/merchandise/orders/{id}/cancel` | REVERSAL (refund redemption) |

> H4 (IDOR) telah diperbaiki: `AttendanceController` mengambil participant dari `$request->user()->participants()->firstOrFail()`.

---

## 19. Webhooks / External Integrations

Tidak ada webhook atau integrasi eksternal yang memicu/mengubah poin. Seluruh mutasi berasal dari aksi internal (check-in, order, payment). Notifikasi (Laravel Reverb) dipancarkan oleh `AttendanceService`/`MerchandiseService`/`PaymentService` terkait event bisnis, **bukan** sebagai trigger poin.

---

## 20. Configuration

`config/points.php`:
```php
'non_member_rate' => (int) env('POINTS_NON_MEMBER_RATE', 0),
```
- `POINTS_NON_MEMBER_RATE` — poin per check-in untuk peserta tanpa membership aktif. Default `0`.
- Rate member diambil dari DB (`membership_plans.point_per_event_checkin`), bukan env.
- Tidak ada config `EARNABLE_EVENT_TYPES` (lihat **§ 29 Discrepancy D2**).

---

## 21. Business Rules / Constraints

- B01: Poin hanya dari attendance valid (present).
- B02: OTS aggregator & OTS-registration (`OTS-*`) → 0 poin (hard exclusion).
- B03: Rate = `point_per_event_checkin` plan aktif saat check-in; di-snapshot ke ledger.
- B04: Non-member → `non_member_rate` (default 0).
- B05: Redemption per-unit (`points_required × quantity`), diskon = `price - price_after_points`, cap ≤ subtotal.
- B06: Saldo tidak boleh negatif (redeem/adjust/reversal di-block jika melanggar → throw).
- B07: 1 Attendance → 1 EARN; 1 Order → 1 REDEEM; 1 Reversal → 1 REVERSAL.
- B08: `point_balance` dapat direkonstruksi penuh dari ledger (`reconcileBalance`).
- B09: Historical ledger immutable (tidak di-UPDATE/DELETE untuk fix saldo).
- B10: Client tidak authoritative untuk poin/saldo/rate/harga/diskon/payment/membership rate.

---

## 22. Edge Cases / Failure Modes

| Skenario | Penanganan |
|---|---|
| Double check-in (race) | UNIQUE index + catch `UniqueConstraintViolationException` → idempoten |
| Earn dua kali via service | `findSource` → kembalikan EARN yg ada |
| Redeem saldo kurang | 422 `Poin tidak mencukupi…` |
| Cancel order sudah di-refund | `refundRedemption` idempoten → no-op |
| Reject payment event | attendance di-invalidate → reverseEarn |
| Reverse membuat saldo negatif | BLOCK (throw), admin pakai `adminAdjust` |
| Non-member check-in | rate 0 → 0 poin, tidak ada ledger row |
| OTS aggregator check-in | `isOtsAggregator()` → null, 0 poin |
| Perubahan harga/point config setelah order | Snapshot di `merchandise_orders` → order tetap valid |
| Drift `point_balance` vs ledger | `reconcileBalance` / tombol admin |

---

## 23. Data Flow Diagrams

### 23.1 Earn
```
AttendanceController/AttendanceService.checkIn
        │  (participant dari auth, bukan input)
        ▼
PointService.earnForAttendance(att, event, participant)
        ├─ isOtsAggregator? → null
        ├─ isOtsRegistration? → null
        ├─ findSource(EARN)? → kembali (idempoten)
        ├─ rate = PointRateService.resolveAt(participant, now())
        ├─ rate <= 0? → null
        └─ INSERT EARN (+rate) + increment point_balance
```

### 23.2 Redeem
```
MerchandiseController.order
        ▼
MerchandiseService.createOrder (DB::transaction, lock FOR UPDATE)
        ├─ validasi stock/size
        ├─ if use_points && isPointRedeemable:
        │     pointsUsed = points_required * qty
        │     if point_balance < pointsUsed → 422
        │     PointService.redeemForOrder → INSERT REDEEM (-pointsUsed)
        ├─ total_price = cash setelah diskon
        └─ PaymentService.createPayment
```

### 23.3 Reverse (event reject/refund)
```
PaymentService.rejectPayment / refundPayment
        ├─ EventParticipant? → AttendanceService.invalidateAttendance
        │                        → reverseEarn → INSERT REVERSAL (+rate)
        └─ MerchandiseOrder? → PointService.refundRedemption
                                 → INSERT REVERSAL (+pointsUsed)
```

### 23.4 Reconcile
```
ledger SUM (point_transactions)
        ▼
PointService.reconcileBalance(participantId)
        ▼
participants.point_balance = ledger_sum
```

---

## 24. State Machines

### 24.1 Attendance (terkait poin)
```
[created] ──check-in valid──▶ [present] ──invalidate──▶ [invalid]
                                   │                         │
                                   └── EARN dibuat ─────────┘── reverseEarn → REVERSAL
```
- `is_invalid=false` → EARN ada.
- `invalidateAttendance` set `is_invalid=true` + buat REVERSAL (idempoten).

### 24.2 MerchandiseOrder (terkait poin)
```
[pending] ──createOrder(use_points)──▶ [REDEEM dibuat, payment pending]
    │                                      │
    ├─ cancel ────────────────────────────▶ [cancelled] → refundRedemption → REVERSAL
    └─ payment rejected ─────────────────▶ [rejected]  → refundRedemption → REVERSAL
[pending] ──confirm──▶ [paid] (REDEEM tetap)
```

### 24.3 Payment (terkait poin)
```
[pending] ──confirm──▶ [confirmed]
[pending] ──reject──▶ [rejected]  ──▶ (event) reverseEarn / (merch) refundRedemption
[pending] ──refund──▶ [refunded]  ──▶ (event) reverseEarn / (merch) refundRedemption
(refunded sudah) ──refund lagi──▶ no-op (idempoten via refunded_at)
```

### 24.4 Participant point_balance
```
ledger SUM  ──reconcileBalance──▶  point_balance (cache)
   ▲                                   │
   └──── EARN / REDEEM / REVERSAL / ADJUSTMENT (semua via Service)
```

---

## 25. Migration / Rollout

Migrations (semua `2026_08_28_19544x`):
- `195441` — UNIQUE `event_participant_id` + `participants.point_balance` (dengan backfill hapus duplikat attendance).
- `195442` — `membership_plans.point_per_event_checkin`.
- `195443` — `merchandise.points_required`/`price_after_points` + `merchandise_orders` snapshot.
- `195444` — tabel `point_transactions` + UNIQUE index.
- `195445` — kolom invalidasi `attendances`.
- `195446` — kolom refund `payments`.
- `195447` — check `point_balance`.

Rollout: migrasi dijalankan otomatis saat container start (`docker-entrypoint.sh`). Tidak ada data seeding poin (saldo awal 0).

---

## 26. Testing Strategy

Unit utama di `tests/Feature/`:
- **`PointApiTest.php`** — 12 test: member earns rate, idempoten earn, non-member 0, OTS aggregator/registration 0, redemption deduct + snapshot, insufficient balance 422, cancel refund, cancel idempoten, ledger reconciles, balance/history endpoints.
- **`PointAuditFindingsTest.php`** — audit findings H1–H5/M1–M4.
- **`AttendanceApiTest`**, **`MerchandiseApiTest`**, **`PaymentApiTest`**, **`MembershipApiTest`** — menutupi jalur earn/redeem/reverse terintegrasi.

### Current verified test state

```text
102 passed
1 skipped
0 failed
```

Skip:

```text
PointAuditFindingsTest concurrency test
```

karena environment container tidak menyediakan:

```text
pcntl_fork
```

Test tidak diubah agar angka menjadi 0 skipped (sesuai instruksi). Hasil di atas diverifikasi dengan menjalankan suite di dalam container (`docker exec -e APP_ENV=testing sh3-app php artisan test`).

---

## 27. Monitoring / Observability

- Tidak ada metric khusus; observabilitas via:
  - `point_transactions` (audit trail lengkap, termasuk `ref`, `note`, `adjusted_by`).
  - `attendances.is_invalid` + `invalidated_by/at/reason`.
  - `payments.refunded_at/by`.
  - Log aplikasi (`storage/logs`) untuk exception (mis. reversal blocked).
- Admin dapat memantau drift via badge konsistensi di halaman peserta.

---

## 28. Known Limitations / Future Work

- L28.1: Tidak ada filter tipe event (lihat § 29 D1) — jika sistem menambah tipe event non-'event' yang tidak boleh memberi poin, filter harus ditambahkan.
- L28.2: `adminAdjust` ada di Service tapi belum ada UI admin dedicated (saat ini reversal-blocked ditangani manual via reconcile/direct). Bisa ditambah form adjustment di halaman peserta.
- L28.3: Tidak ada expiry poin (poin tidak kadaluarsa). Jika diperlukan, butuh mekanisme baru (bukan sekadar flag).
- L28.4: Concurrency test di-skip di container; sebaiknya dijalankan di CI dengan `pcntl_fork` untuk pembuktian race-condition.
- L28.5: `ledger_balance` di API dihitung via `SUM` tiap request — untuk skala besar bisa di-cache (namun cache harus invalidasi saat mutasi).

---

## 29. Discrepancy (Plan vs Implementasi)

Berikut penyimpangan **eksplisit** antara `.omo/plans/flat-point-redemption.md` dan kode aktual. **Tidak ada yang diklaim sudah fixed** — keduanya dicatat apa adanya.

### D1 — EVENT_TYPE_FILTER

Plan menginginkan:

```text
EARNABLE_EVENT_TYPES = ['event']
```

tetapi implementasi aktual `earnForAttendance()` saat ini **belum memfilter `event.type`**. Poin diberikan untuk attendance apa pun selama participant punya membership aktif dengan `rate > 0`.

### D2 — EARNABLE_EVENT_TYPES config

Plan menyebut konfigurasi allowlist event type, tetapi config tersebut **belum tersedia** pada implementasi aktual (`config/points.php` hanya punya `non_member_rate`; tidak ada `earnable_event_types`).

**Penjelasan:** Saat ini semua event yang ada bertipe `event`, sehingga discrepancy D1/D2 belum menghasilkan *behavioral difference* pada current dataset. Jika kelak ada tipe event lain yang tidak boleh memberi poin, filter tersebut harus ditambahkan di `earnForAttendance()` (atau `PointRateService::resolveAt`).

### Konsistensi (tidak discrepancy)

- Resolusi membership aktif (`PointRateService::resolveAt` mirror `MembershipService::checkEligibility`) — SESUAI plan.
- Non-member rate default 0 — SESUAI plan.
- OTS/aggregator exclusion — SESUAI plan (via `isOtsAggregator()` + prefix `OTS-`).
- Model B redemption per-unit + snapshot — SESUAI plan.
- Reversal/refund otomatis pada reject/refund payment (V12/H1) — SESUAI plan di `PaymentService`.
- `reconcileBalance` overwrite cache dari ledger — SESUAI prinsip plan.

Kesimpulan: satu-satunya deviation material adalah **D1/D2 (filter tipe event tidak ada)**; sisanya konsisten dengan rencana.

---

## Final Summary

Sistem Point SH3 adalah ledger poin **append-only, server-authoritative**, dengan:

- **Earn:** flat rate dari `membership_plans.point_per_event_checkin` (plan aktif saat check-in, di-snapshot), hanya untuk attendance valid, OTS/aggregator dikecualikan mutlak, idempoten per attendance (service-level + UNIQUE index).
- **Redemption:** Model B per-merchandise (`points_required × quantity`, `discountPerUnit = price - price_after_points`), perhitungan server-side, participant di-lock `FOR UPDATE` sebelum validasi saldo, cap diskon ≤ subtotal, idempoten per order.
- **Reverse/Refund:** baris `REVERSAL` baru; otomatis saat attendance di-invalidate atau payment event/merchandise ditolak/refund; saldo negatif di-block (throw), bukan clamp; original ledger tetap dipertahankan.
- **Adjust:** `adminAdjust` (ADJUSTMENT) untuk koreksi manual ter-audit.
- **Consistency:** `participants.point_balance` adalah cache recomputable via `reconcileBalance()`; API mengembalikan both cache & ledger; admin punya tombol reconcile + badge konsistensi. Historical ledger immutable.
- **Security:** client tidak authoritative untuk poin/saldo/rate/harga/diskon/payment/membership rate; H4 IDOR fix — attendance identity dari authenticated user.
- **Testing:** 102 passed / 1 skipped terverifikasi di container; mencakup earn/redeem/reverse/idempotensi/OTS.

**Discrepancy (tetap terbuka):** D1/D2 — filter tipe event (`EVENT_TYPE_FILTER='event'`) dari plan **belum** diimplementasikan; saat ini tidak berdampak karena semua event bertipe `event`.

---

```text
DOCUMENTATION: COMPLETE

File:
docs/sistem-point.md

Production code changed:
NO

Tests changed:
NO

Sections:
29 sections documented

Current verified test state:
102 passed / 1 skipped / 0 failed
```
