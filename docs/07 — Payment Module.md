# 07 — Payment Module

Payment tracking for event registration, merchandise, and membership.

```sql
CREATE TABLE payments (
  id, participant_id, invoice_number UNIQUE,
  payment_type (event_registration|merchandise|membership),
  paymentable_type, paymentable_id, amount,
  payment_method (transfer|cash|qris),
  payment_proof, status (pending|confirmed|rejected|refunded),
  confirmed_by (User ID), paid_at
);
```

# Flow

- Participant upload bukti transfer/qris →
- Bendahara confirm/reject →
- Confirm → aktivasi membership / konfirmasi order