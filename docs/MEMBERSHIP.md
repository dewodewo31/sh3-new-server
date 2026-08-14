# Membership System — Architecture & Debug Guide

## Database Schema

### `membership_plans` (config pricing)

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `key` | varchar(50) UNIQUE | `tahunan`, `setengah_tahun`, `mingguan` |
| `name` | varchar | Display name |
| `description` | varchar, nullable | |
| `base_event_price` | int | Harga per event (Rp 25.000) |
| `discount_percentage` | int | Diskon % (0–100) |
| `reference_event_count` | int | Jumlah event referensi (berapa kali datang) |
| **`price`** | **int** | **Derived** = `(base × (100-disc) / 100) × ref_count` |
| `duration` | int | Durasi membership |
| `duration_unit` | enum | `days`, `months`, `years` |
| `is_active` | bool | Aktif/tidak |
| `sort_order` | int | Urutan tampil |

> **`price` is DERIVED, never set manually.** The `saving` observer recalculates it every write:
> ```php
> // MembershipPlan::booted()
> static::saving(function (self $plan) {
>     $plan->price = $plan->fullPackagePrice();  // effectivePrice × referenceCount
> });
> ```

### `membership_histories` (transaksi per peserta)

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `participant_id` | FK → `participants` | |
| `membership_type` | varchar(50) | `tahunan`, `setengah_tahun`, `mingguan` |
| `start_date` | date | Mulai membership |
| `end_date` | date | Berakhir (actual, bisa dipotong year-end) |
| `normal_end_date` | date, nullable | Berakhir sebelum year-end dipotong |
| `eligible_event_count` | int, nullable | Jumlah Sunday event eligible |
| `base_event_price` | int, nullable | Snapshot dari plan |
| `discount_percentage` | int, nullable | Snapshot dari plan |
| `effective_event_price` | int, nullable | Snapshot: `base × (100-disc) / 100` |
| **`price`** | **decimal(15,2)** | **Harga yang ditagihkan ke peserta** |
| `status` | enum | `pending`, `active`, `expired`, `cancelled` |

> **`price` di sini adalah SNAPSHOT** — diisi saat membership dibuat, tidak berubah walau plan di-edit.

### `payments`

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `participant_id` | FK → `participants` | |
| `invoice_number` | varchar UNIQUE | Auto-generated |
| `payment_type` | enum | `event_registration`, `merchandise`, `membership` |
| `paymentable_type` | morph type | Class name |
| `paymentable_id` | morph id | ID record terkait |
| `amount` | decimal(15,2) | Jumlah yang harus dibayar |
| `payment_method` | enum | `transfer`, `cash`, `qris` |
| `payment_proof` | varchar, nullable | Path file bukti bayar |
| `status` | enum | `pending`, `confirmed`, `rejected`, `refunded` |

---

## Pricing Formula

```
effectiveEventPrice = baseEventPrice × (100 - discountPercentage) / 100
fullPackagePrice    = effectiveEventPrice × referenceEventCount
```

### Contoh per Plan

| Plan | base | disc | ref | effective | price |
|---|---|---|---|---|---|
| Tahunan | 25.000 | 10% | 53 | 22.500 | **1.192.500** |
| Setengah Tahun | 25.000 | 5% | 26 | 23.750 | **617.500** |
| Mingguan | 25.000 | 0% | 1 | 25.000 | **25.000** |

> Formula ini hanya untuk menentukan **harga package**. Jumlah event eligible (`eligible_event_count`) dihitung terpisah tapi **tidak dipakai untuk harga** (hanya untuk info/display).

---

## MembershipPeriod (Year-End Boundary)

```
normalEndDate  = startDate + duration
yearEndDate    = December 31 tahun yang sama
actualEndDate  = min(normalEndDate, yearEndDate)
```

Contoh:
- Tahunan mulai 14 Agustus 2026 → normal end = 14 Agustus 2027, year end = 31 Des 2026 → **actual = 31 Des 2026**
- Setengah Tahun mulai 14 Agustus 2026 → normal end = 14 Februari 2027, year end = 31 Des 2026 → **actual = 31 Des 2026**
- Mingguan mulai 14 Agustus 2026 → normal end = 21 Agustus 2026 → **actual = 21 Agustus 2026** (sebelum year-end)

---

## Status Flow

```
                    ┌─────────────────────────┐
                    │      membership_histories│
                    └─────────────────────────┘

[1] Admin Grant ──────────────────────────────────────
    MembershipService::grant()
    Status awal: ACTIVE
    Pembayaran: tidak ada (admin langsung grant)

[2] Client Subscribe (API) ──────────────────────────
    MembershipService::requestSubscription()
    Status awal: PENDING + Payment(pending)

[3] Admin Confirm Pembayaran ────────────────────────
    MembershipHistory::markAsPaid()
    Status: PENDING → ACTIVE

[4] Expired (otomatis) ──────────────────────────────
    MembershipService::markExpiredHistories()  (via scheduler)
    Status: ACTIVE → EXPIRED  (jika end_date < now)

[5] Cancel (manual) ─────────────────────────────────
    MembershipService::cancelHistory() atau cancelMembership()
    Status: ACTIVE/PENDING → CANCELLED
```

### Diagram Flow

```
┌─────────────┐     ┌──────────────┐     ┌────────────────┐
│ Admin Grant │────▶│   ACTIVE     │────▶│   EXPIRED      │
└─────────────┘     └──────────────┘     └────────────────┘
                          │
                          ▼
                    ┌──────────────┐
                    │  CANCELLED   │
                    └──────────────┘

┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│Client Subscr.│───▶│   PENDING    │───▶│   ACTIVE     │
└──────────────┘    └──────────────┘    └──────────────┘
                          │
                          ▼
                    ┌──────────────┐
                    │  CANCELLED   │
                    └──────────────┘
```

---

## Flow Detail: Admin Grant Membership

```
1. Admin buka /admin/memberships/create
2. Pilih participant + pilih plan (tahunan/setengah_tahun/mingguan)
3. Submit → POST /admin/memberships
   ↓
4. MembershipController::store()
   → MembershipRequest::validate()
   ↓
5. MembershipService::grant(participant, type)
   a. cancelActiveHistories(participant)  // batalkan semua ACTIVE lama
   b. findPlan(type)
   c. pricingService->calculatePrice(plan)  // hitung period + eligible events
   d. membershipHistories()->create([
        membership_type, start_date, end_date, normal_end_date,
        eligible_event_count, base_event_price, discount_percentage,
        effective_event_price,
        price => plan->price,       // ← SNAPSHOT dari plan
        status => ACTIVE
      ])
   e. participant->update(membership_type, start_date, end_date)
   f. notificationService->notifyParticipant()
   ↓
6. Redirect ke /admin/memberships dengan success message
```

### Checkpoint Debug (Admin Grant)

```
□ MembershipPlan ada di DB? → SELECT * FROM membership_plans WHERE key='tahunan'
□ Plan price sudah benar? → price harus = effectivePrice × refCount
□ eligible_event_count = 0? → Normal, tidak mempengaruhi harga
□ membership_histories.price = plan.price? → HARUS SAMA
□ participant.membership_type updated? → harus sama dengan type yang di-grant
```

---

## Flow Detail: Client Subscribe (API)

```
1. Client POST /api/v1/membership/subscribe
   Body: { membership_type, payment_method, payment_proof? }
   ↓
2. SubscribeMembershipRequest::validate()
   → membership_type harus ada di membership_plans & is_active=true
   ↓
3. API MembershipController::subscribe()
   → Upload payment_proof jika ada
   → MembershipService::requestSubscription(participant, type, method, proof)
     a. findPlan(type)
     b. pricingService->calculatePrice(plan)
     c. membershipHistories()->create([
          ...,
          price => plan->price,   // ← SNAPSHOT
          status => PENDING
        ])
     d. paymentService->createPayment([
          amount => plan->price,  // ← harus sama dengan history.price
          status => 'pending'
        ])
   ↓
4. Response 201: { data: MembershipHistoryResource, message: "..." }
   ↓
5. Admin konfirmasi pembayaran → markAsPaid() → PENDING → ACTIVE
```

### Checkpoint Debug (Client Subscribe)

```
□ Request tidak punya field 'price'? → BENAR, server yang hitung
□ history.price = plan.price? → HARUS SAMA
□ payment.amount = plan.price? → HARUS SAMA
□ history.status = PENDING? → Benar, menunggu konfirmasi admin
□ payment.status = PENDING? → Benar, menunggu konfirmasi admin
```

---

## Flow Detail: Activate (Admin Confirm Payment)

```
1. Admin POST /admin/payments/{id}/confirm
   ↓
2. PaymentService::confirm(payment)
   → payment.status = CONFIRMED
   → payment.confirmed_by = admin_user_id
   → payment.paid_at = now()
   ↓
3. MembershipHistory::markAsPaid()  (via Observer atau Service)
   → history.status = PENDING → ACTIVE
   → participant.membership_type = history.membership_type
   → participant.membership_start_date = history.start_date
   → participant.membership_end_date = history.end_date
```

### Checkpoint Debug (Activate)

```
□ payment.status = confirmed? → Benar
□ history.status = active? → Setelah markAsPaid
□ participant fields updated? → membership_type, start_date, end_date harus match
□ history.price masih sama? → Tidak boleh berubah saat activate
```

---

## Flow Detail: Cancel

```
1. Admin klik "Batalkan" di /admin/memberships → POST /admin/memberships/{id}/cancel
   ATAU Client POST /api/v1/membership/cancel
   ↓
2. MembershipService::cancelHistory(history)  atau cancelMembership(participant)
   a. history.update(status => CANCELLED)
   b. participant.update(membership_type => 'none', dates => null)  // jika cancelMembership
   ↓
3. Response redirect/JSON dengan success
```

### Checkpoint Debug (Cancel)

```
□ history.status = cancelled? → Benar
□ participant.membership_type = none? → Jika full cancel
□ history.price masih ada? → TIDAK BERUBAH, harga tetap tersimpan
```

---

## Flow Detail: Expired (Scheduler)

```
1. Cron/Task Scheduler → artisan membership:check-expired
   ↓
2. MembershipService::markExpiredHistories()
   → Query: status=ACTIVE AND end_date < now()
   → ForEach: update(status => EXPIRED)
   → Send notification: "Membership telah berakhir"
   ↓
3. Stats updated otomatis
```

---

## Price Flow — Titik-Titik Kritis

```
┌──────────────────────────────────────────────────────────────────┐
│                    PRICE FLOW DIAGRAM                            │
│                                                                  │
│  membership_plans.price (derived)                                │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────────────┐            │
│  │ MembershipService::grant()                       │            │
│  │   price => $plan->price  ←── SNAPSHOT           │            │
│  └─────────────────────────────────────────────────┘            │
│       │                                                          │
│       ▼                                                          │
│  membership_histories.price  ←── Sumber tampilan di admin        │
│       │                                                          │
│       ├──▶ Blade: Rp {{ number_format($h->price) }}             │
│       ├──▶ API: MembershipHistoryResource → 'price'             │
│       └──▶ stats: SUM(price) WHERE status IN (active, expired)  │
│                                                                  │
│  ┌─────────────────────────────────────────────────┐            │
│  │ MembershipPricingService::calculatePrice()       │            │
│  │   final_price = effectiveEventPrice × count      │            │
│  │   → TIDAK DIPAKAI UNTUK HARGA (hanya info)      │            │
│  └─────────────────────────────────────────────────┘            │
└──────────────────────────────────────────────────────────────────┘
```

### Bug yang Pernah Terjadi & Fix-nya

| Bug | Penyebab | Fix |
|---|---|---|
| **Harga Rp 0** | `grant()` & `requestSubscription()` pakai `$breakdown['final_price']` (event-based). Jika tidak ada Sunday event eligible → `final_price = 0` | Ganti ke `$plan?->price` (snapshot plan) |
| **Harga Rp 0 di existing records** | Record lama dibuat sebelum fix | Manual UPDATE: `UPDATE membership_histories SET price = (SELECT price FROM membership_plans WHERE key = membership_type) WHERE price = 0` |
| **Sisa hari float** | `diffInDays()` return float | Cast ke `(int)` |
| **Seeder harga salah** | `ParticipantSeeder` pakai hardcoded 400k/250k/10k | Lookup dari `MembershipPlan::where('key', $type)->first()->price` |
| **Mingguan ada diskon** | `MembershipPlanSeeder` set `discount_percentage=5` untuk mingguan | Ubah ke `discount_percentage=0` |

---

## Routes

### Admin (Web)

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/admin/memberships` | `Admin\MembershipController@index` | List + stats |
| GET | `/admin/memberships/create` | `Admin\MembershipController@create` | Form grant |
| POST | `/admin/memberships` | `Admin\MembershipController@store` | Proses grant |
| POST | `/admin/memberships/{id}/cancel` | `Admin\MembershipController@cancel` | Cancel 1 record |
| GET | `/admin/membership-plans` | `Admin\MembershipPlanController@index` | List plans |
| POST | `/admin/membership-plans` | `Admin\MembershipPlanController@store` | Create plan |
| PUT | `/admin/membership-plans/{id}` | `Admin\MembershipPlanController@update` | Update plan |
| DELETE | `/admin/membership-plans/{id}` | `Admin\MembershipPlanController@destroy` | Delete plan |

### API (Client)

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/api/v1/membership` | `API\MembershipController@show` | Profil + membership |
| GET | `/api/v1/membership/history` | `API\MembershipController@history` | Riwayat membership |
| GET | `/api/v1/membership/plans` | `API\MembershipController@plans` | Daftar plan |
| POST | `/api/v1/membership/subscribe` | `API\MembershipController@subscribe` | Subscribe baru |
| POST | `/api/v1/membership/cancel` | `API\MembershipController@cancel` | Cancel membership |

---

## Validation Rules

### MembershipRequest (Admin Grant)

```php
'participant_id' => 'required|exists:participants,id'
'membership_type' => 'required|exists:membership_plans,key'
'duration_months' => 'nullable|integer|min:1'
// NOTE: tidak ada field 'price' — server yang hitung
```

### SubscribeMembershipRequest (Client)

```php
'membership_type' => 'required|exists:membership_plans,key'
'payment_method' => 'required|in:transfer,cash,qris'
'payment_proof' => 'nullable|image|max:2048'
// NOTE: tidak ada field 'price' — server yang hitung
```

---

## Key Files Reference

```
app/
├── Services/
│   ├── MembershipService.php          ← Core logic: grant, subscribe, activate, cancel
│   └── MembershipPricingService.php   ← Pricing formula & period calculation
├── Models/
│   ├── MembershipPlan.php             ← Plan + derived price (observer)
│   └── MembershipHistory.php          ← Transaksi + snapshot pricing
├── Observers/
│   └── MembershipPlanObserver.php     ← Recalculate price on save
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── MembershipController.php       ← Admin CRUD memberships
│   │   │   └── MembershipPlanController.php   ← Admin CRUD plans
│   │   └── API/
│   │       └── MembershipController.php       ← Client subscribe/cancel
│   ├── Requests/
│   │   ├── MembershipRequest.php              ← Admin validation
│   │   └── SubscribeMembershipRequest.php     ← Client validation
│   └── Resources/
│       └── MembershipHistoryResource.php      ← API response shape
├── Repositories/
│   ├── MembershipHistoryRepository.php
│   └── MembershipPlanRepository.php
resources/views/
└── memberships/
    └── index.blade.php                ← Admin list view
database/
├── migrations/
│   ├── 2024_01_01_000006_create_membership_histories_table.php
│   ├── 2026_07_31_100000_create_membership_plans_table.php
│   ├── 2026_08_14_000001_add_pricing_config_to_membership_plans.php
│   └── 2024_01_01_000010_create_payments_table.php
└── seeders/
    ├── MembershipPlanSeeder.php       ← Plan config + pricing rules
    └── ParticipantSeeder.php          ← Sample memberships
```

---

## Debug Checklist

Ketika harga salah / membership bermasalah, cek ini:

```
1. □ Plan price benar?
   SELECT key, price, base_event_price, discount_percentage, reference_event_count
   FROM membership_plans;
   → price harus = base × (100 - disc) / 100 × ref

2. □ History price = plan price?
   SELECT h.id, h.membership_type, h.price, p.price AS plan_price
   FROM membership_histories h
   JOIN membership_plans p ON h.membership_type = p.key;
   → h.price harus = p.price

3. □ Payment amount = history price?
   SELECT pay.id, pay.amount, h.price AS history_price
   FROM payments pay
   JOIN membership_histories h ON pay.payable_id = h.id
   WHERE pay.payment_type = 'membership';
   → pay.amount harus = h.price

4. □ Participant fields updated?
   SELECT id, name, membership_type, membership_start_date, membership_end_date
   FROM participants WHERE id = {participant_id};
   → membership_type harus match dengan history terakhir

5. □ Status konsisten?
   SELECT h.id, h.status, h.start_date, h.end_date, p.id AS payment_id, p.status AS payment_status
   FROM membership_histories h
   LEFT JOIN payments p ON p.payable_id = h.id AND p.paymentable_type = 'App\\Models\\MembershipHistory'
   WHERE h.participant_id = {id};
   → active → payment confirmed
   → pending → payment pending
   → cancelled/expired → tidak ada payment aktif
```

---

## Quick SQL Debug

```sql
-- Semua membership dengan harga
SELECT h.id, p.name, h.membership_type, h.price, h.status,
       h.eligible_event_count, h.effective_event_price,
       h.start_date, h.end_date
FROM membership_histories h
JOIN participants p ON h.participant_id = p.id
ORDER BY h.id;

-- Cari yang harga = 0 (seharusnya tidak ada setelah fix)
SELECT * FROM membership_plans WHERE price = 0;
SELECT * FROM membership_histories WHERE price = 0;

-- Revenue
SELECT SUM(price) AS total_revenue
FROM membership_histories
WHERE status IN ('active', 'expired');

-- Active membership count
SELECT COUNT(*) AS active_count
FROM membership_histories
WHERE status = 'active' AND end_date >= CURDATE();
```
