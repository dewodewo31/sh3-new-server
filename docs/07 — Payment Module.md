# 07 — Payment Module

Pembayaran event, merchandise, dan membership — dengan konfirmasi (accept/reject) oleh Bendahara. Menggunakan data berikut.

## Database — Tabel `payments`

```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    invoice_number VARCHAR(255) UNIQUE,
    payment_type ENUM('event_registration','merchandise','membership'),
    paymentable_type VARCHAR(255),        -- morph: EventParticipant | MerchandiseOrder | MembershipHistory
    paymentable_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('transfer','cash','qris') DEFAULT 'transfer',
    payment_proof VARCHAR(255) NULL,
    status ENUM('pending','confirmed','rejected','refunded') DEFAULT 'pending',
    confirmed_by INT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

> **Polymorphic relation:** `morphTo(Payment::class, 'paymentable')` pada model Payment. `paymentable_type` + `paymentable_id` menyimpan referensi ke `EventParticipant`, `MerchandiseOrder`, atau `MembershipHistory`. Tidak ada kolom individual `event_id`/`merchandise_id` — tabel ini sudah polymorphic sejak awal.

## Flow

```
Participant (upload bukti transfer/qris)
   ↓
POST /api/v1/payments/create  (payment_type + paymentable) atau dibuat dari
                                   order/merchandise/membership subscribe
   ↓  status = pending
Bendahara: /admin/payments/{id} (lihat bukti)
   ↓
confirm → PaymentService::confirmPayment() → paid_at + confirmed_by
   ↓
  mengaktifkan entitas (EventParticipant.markAsPaid / MerchOrder.markAsPaid / MembershipHistory.markAsPaid)
   OR
reject → status = rejected
```

- `PaymentService::confirmPayment()` menandai pembayaran confirmed dan memicu aksi di entitas terkait (aktivasi, dll. via polymorphic morph map). Konfirmasi memanggil method `markAsPaid()` pada paymentable.
- `PaymentService::rejectPayment()` → status `rejected`.

## API Endpoints

| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| POST | `/payments/create` | Buat pembayaran | ✓ |
| GET | `/payments/{id}` | Detail pembayaran | ✓ |
| GET | `/payments/history` | Riwayat pembayaran user | ✓ |
| POST | `/payments/confirm/{id}` | Konfirmasi pembayaran (Bendahara/Admin) | ✓ role bendahara |

## Route Admin (Web)

| Method | Route | Role |
|--------|-------|------|
| GET | `/admin/payments` | Full Access, Bendahara |
| GET | `/admin/payments/{id}` | Full Access, Bendahara (detail + bukti) |
| PUT | `/admin/payments/{id}/confirm` | Full Access, Bendahara |
| PUT | `/admin/payments/{id}/reject` | Full Access, Bendahara |

## File Terkait

- `app/Services/PaymentService.php` — createPayment, confirmPayment, rejectPayment
- `app/Repositories/PaymentRepository.php` — findByInvoice, findPending, findByParticipant
- `app/Models/Payment.php` — morph
- `app/DTOs/PaymentDTO.php`, `app/Http/Requests/PaymentRequest.php`
- `app/Http/Controllers/API/PaymentController.php`, `app/Http/Controllers/Admin/PaymentController.php`

## Catatan

- Pembayaran event dibuat otomatis saat `EventService::registerParticipant` bila berbayar.
- Pembayaran merchandise dibuat otomatis saat `MerchandiseService::createOrder`.
- Pembayaran membership dibuat otomatis saat `MembershipService::requestSubscription`.