# 18 — Participant Password Reset (Tanpa Email / Pihak Ketiga)

Fitur reset password khusus **Participant** (peserta event lari). Tidak menggunakan
email, OTP, SMS, WhatsApp, Google Authenticator, atau layanan pihak ketiga apa pun.
Verifikasi hanya menggabungkan **Username** + **Hash ID** (kode peserta).

Fitur ini **terpisah sepenuhnya** dari reset password Admin (yang tetap pakai email
via `/api/v1/auth/forgot-password` & `/api/v1/auth/reset-password`).

## Endpoint

| Method | Path                                        | Auth    | Rate Limit        |
|--------|---------------------------------------------|---------|-------------------|
| POST   | `/api/v1/participant/auth/verify-reset`     | publik  | –                 |
| POST   | `/api/v1/participant/auth/reset-password`   | publik  | 5 / 15 mnt / IP   |

## Alur (Frontend)

1. Halaman **Forgot Password**: form **Username** + **Hash ID** → tombol **Verifikasi**.
2. `verify-reset` → jika valid, frontend menampilkan form **Password Baru** +
   **Konfirmasi Password** → tombol **Reset Password**.
3. `reset-password` → update password participant.

Tidak ada email/link reset. Semua lewat API karena frontend peserta terpisah.

## 1. Verify Participant — `POST /api/v1/participant/auth/verify-reset`

Request:
```json
{
  "username": "BUDI123",
  "hash_id": "SH3A7B92XK"
}
```
Validation: `username` wajib, `hash_id` wajib.

Sukses — `200 OK`:
```json
{
  "success": true,
  "can_reset": true,
  "meta": { "timestamp": "...", "request_id": "..." }
}
```

Gagal (username/hash_id tidak cocok, atau bukan participant) — `200 OK`, pesan
generik, **tidak mengungkap field mana yang salah**:
```json
{
  "success": false,
  "message": "Data participant tidak valid."
}
```

## 2. Reset Password — `POST /api/v1/participant/auth/reset-password`

Request:
```json
{
  "username": "BUDI123",
  "hash_id": "SH3A7B92XK",
  "password": "passwordbaru",
  "password_confirmation": "passwordbaru"
}
```
Validation: `username` & `hash_id` wajib; `password` wajib, min 8 karakter,
`confirmed` (harus ada `password_confirmation` yang sama).

Sukses — `200 OK`:
```json
{
  "success": true,
  "message": "Password berhasil diperbarui."
}
```

Gagal verifikasi — `200 OK`, pesan generik (sama persis dengan di atas, agar
tidak bisa ditebak field mana yang salah):
```json
{ "success": false, "message": "Data participant tidak valid." }
```

Validation error — `422 Unprocessable Entity`:
```json
{
  "message": "The password field must be at least 8 characters.",
  "errors": { "password": ["The password field must be at least 8 characters."] }
}
```

Rate limit terlampaui — `429 Too Many Attempts`:
```json
{ "message": "Too Many Attempts." }
```

## Security

- Password disimpan via `Hash::make()` — tidak ada plaintext.
- Setelah reset, semua token peserta (`users.tokens`) di-revoke.
- Peserta hanya bisa reset akun **sendiri**: kombinasi `username` + `hash_id`
  harus milik participant yang sama. Peserta lain maupun **Admin** ditolak dengan
  pesan generik yang sama.
- `hash_id` peserta lain tidak pernah dibocorkan di response.
- Rate limit **5 percobaan / 15 menit / IP** pada `reset-password`.

## Audit Log

Setiap reset berhasil mencatat ke `user_activity_logs`:

| Field         | Isi                                                  |
|---------------|------------------------------------------------------|
| `user_id`     | id user participant                                  |
| `action`      | `Participant Password Reset`                         |
| `details`     | `{ "participant_id": <id>, "username": <username> }` |
| `ip_address`  | IP request                                           |
| `user_agent`  | User-Agent request                                   |

## Implementasi

- `app/Http/Controllers/API/ParticipantAuthController.php` — `verifyReset()`, `resetPassword()`.
- `app/Services/ParticipantPasswordResetService.php` — validasi + reset + audit log.
- `app/Http/Requests/ParticipantVerifyResetRequest.php`, `ParticipantResetPasswordRequest.php`.
- `routes/api.php` — 2 route publik (`reset-password` + `throttle:5,15`).
- `app/Models/Participant.php` — `hash_id` kini **kolom unik** berisi kode peserta
  (`SH3` + 7 alfanumerik), auto-generate saat participant dibuat.
- `database/migrations/2026_08_14_000100_add_hash_id_to_participants_table.php` —
  menambah kolom + backfill semua participant existing.
- `tests/Feature/ParticipantPasswordResetTest.php` — 10 kasus (semua PASS).

> Catatan: `hash_id` sebelumnya hanya format tampilan (`%04d` / `NM-%04d`). Kini
> menjadi kode peserta unik sungguhan (mis. `SH3A7B92XK`) yang dipakai sebagai
> faktor verifikasi reset password. Perubahan ini berlaku juga pada response
> `ParticipantResource.hash_id`.
