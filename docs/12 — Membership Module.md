# 12 — Membership Module

Pengelolaan membership peserta SH3, mencakup pemberian membership oleh admin, pembelian oleh peserta via API, konfirmasi pembayaran, serta riwayat membership.

## Jenis & Harga Membership

| Tipe | Durasi | Harga |
|------|--------|-------|
| `tahunan` | 12 bulan | Rp 400.000 |
| `setengah_tahun` | 6 bulan | Rp 250.000 |
| `mingguan` | 7 hari | Rp 10.000 |

Harga dan durasi didefinisikan sebagai konstanta di `App\Services\MembershipService` (`PRICES` & `DURATIONS`).

## Database

### Tabel `membership_histories`

```sql
CREATE TABLE membership_histories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    membership_type ENUM('tahunan', 'setengah_tahun', 'mingguan') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

Field membership pada `participants` (kolom denormalisasi untuk status aktif saat ini):

- `membership_type` — tipe membership aktif (`none` jika bukan member)
- `membership_start_date` — tanggal mulai aktif
- `membership_end_date` — tanggal berakhir aktif

## Alur Pemberian Membership

### 1. Admin memberi membership langsung (Manual Grant)

```
Admin → Menu Memberships → "Beri Membership" → pilih peserta + tipe → submit
        ↓
MembershipService::grant()
        ↓
1. Membership lama yang aktif di-cancel (status → cancelled)
2. Membuat membership_histories baru (status active)
3. Update participants.membership_type / start / end
4. Notifikasi ke peserta
```

- Route: `POST /admin/memberships`
- Role: `admin_full_access`, `admin_member`, `bendahara`

### 2. Peserta membeli via API (Payment Flow)

```
Peserta (mobile) → POST /api/v1/membership/subscribe
        ↓
MembershipService::requestSubscription()
        ↓
1. Membuat membership_histories (status pending)
2. Membuat Payment (payment_type=membership, status pending, paymentable = membership_history)
3. Notifikasi ke bendahara / admin
        ↓
Bendahara konfirmasi → PaymentService::confirmPayment()
        ↓
MembershipHistory::markAsPaid() → MembershipService::activate()
        ↓
1. Membership lain yang aktif di-cancel
2. status history → active, tanggal dihitung ulang dari hari ini
3. Update participants.membership_type / start / end
```

### 3. Pembatalan

- `POST /admin/memberships/{id}/cancel` (web) — batalkan satu riwayat membership.
- `POST /api/v1/membership/cancel` (API) — peserta membatalkan membership aktifnya.
- Pembatalan mengubah status history menjadi `cancelled` dan reset kolom membership peserta ke `none`.

### 4. Kadaluarsa

- `MembershipService::markExpiredHistories()` otomatis dipanggil saat halaman index memberships dibuka; history `active` dengan `end_date < hari ini` menjadi `expired`.
- `Participant::isMembershipActive()` menghitung status aktif dari `membership_end_date`.

## Keanggotaan Gratis Event

Saat daftar event (`EventService::registerParticipant`), jika event `is_free_for_members = true` dan peserta punya membership aktif (`MembershipService::checkEligibility()`), biaya pendaftaran menjadi `0` dengan `registration_type = membership`.

## Route Web (Admin)

| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/admin/memberships` | Halaman membership (statistik + riwayat) |
| GET | `/admin/memberships/create` | Form beri membership |
| POST | `/admin/memberships` | Simpan pemberian membership |
| POST | `/admin/memberships/{id}/cancel` | Batalkan membership |

Role: `admin_full_access`, `admin_member`, `bendahara`

## Route API

Semua endpoint membership butuh autentikasi (`auth:sanctum`).

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/membership` | Status membership user yang sedang login |
| GET | `/api/v1/membership/plans` | Daftar paket & harga membership |
| GET | `/api/v1/membership/history` | Riwayat membership user |
| POST | `/api/v1/membership/subscribe` | Ajukan pembelian membership |
| POST | `/api/v1/membership/cancel` | Batalkan membership |

## API Spec

### `GET /api/v1/membership`

Response `200`:

```json
{
  "data": {
    "id": 1,
    "user_id": 3,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "membership_type": "tahunan",
    "membership_start_date": "2026-07-31",
    "membership_end_date": "2027-07-31",
    "is_membership_active": true,
    "membership_histories": [
      {
        "id": 1,
        "membership_type": "tahunan",
        "start_date": "2026-07-31",
        "end_date": "2027-07-31",
        "price": 400000.0,
        "status": "active"
      }
    ]
  }
}
```

Error `404` bila user tidak memiliki profil peserta.

### `GET /api/v1/membership/plans`

Response `200`:

```json
{
  "data": [
    { "type": "tahunan", "duration": "12 bulan", "price": 400000 },
    { "type": "setengah_tahun", "duration": "6 bulan", "price": 250000 },
    { "type": "mingguan", "duration": "7 hari", "price": 10000 }
  ]
}
```

### `GET /api/v1/membership/history`

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "membership_type": "tahunan",
      "start_date": "2026-07-31",
      "end_date": "2027-07-31",
      "price": 400000.0,
      "status": "active"
    }
  ]
}
```

### `POST /api/v1/membership/subscribe`

Request (multipart/form-data atau JSON):

```json
{
  "membership_type": "tahunan",
  "payment_method": "transfer",
  "payment_proof": "file-gambar-opsional",
  "duration_months": 12
}
```

Validasi:

- `membership_type` required, in `tahunan|setengah_tahun|mingguan`
- `payment_method` required, in `transfer|cash|qris`
- `payment_proof` nullable image, maks 5MB
- `duration_months` nullable integer 1–12 (default sesuai tipe)

Response `201`:

```json
{
  "data": {
    "id": 1,
    "membership_type": "tahunan",
    "start_date": "2026-07-31",
    "end_date": "2027-07-31",
    "price": 400000.0,
    "status": "pending"
  },
  "message": "Permintaan membership berhasil dibuat. Silakan lakukan pembayaran."
}
```

### `POST /api/v1/membership/cancel`

Response `200`:

```json
{
  "message": "Membership dibatalkan."
}
```

## File Terkait

- `app/Services/MembershipService.php` — business logic
- `app/Repositories/MembershipHistoryRepository.php` — query membership histories
- `app/Models/MembershipHistory.php` — model + `markAsPaid()`
- `app/Http/Controllers/Admin/MembershipController.php` — page admin
- `app/Http/Controllers/API/MembershipController.php` — endpoint API
- `app/Http/Requests/MembershipRequest.php` — validasi grant admin
- `app/Http/Requests/SubscribeMembershipRequest.php` — validasi subscribe API
- `app/Http/Resources/MembershipHistoryResource.php` — resource API
- `resources/views/memberships/index.blade.php` & `create.blade.php`
