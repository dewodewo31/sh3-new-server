# Migration Checklist

Checklist bertahap untuk memigrasikan fitur API agar sesuai dengan API terdahulu. Setiap fase harus lolos test sebelum lanjut ke fase berikutnya.

## Phase 1

Authentication

- [x] Tambahkan route `POST /auth/refresh`, `POST /auth/forgot-password`, `POST /auth/reset-password`.
- [x] Implementasikan `AuthController::refresh()`, `forgotPassword()`, `resetPassword()`.
- [x] Buat `ForgotPasswordRequest` & `ResetPasswordRequest`.
- [x] Integrasi `Password` facade Laravel (reset link / reset).
- [x] Test: register, login, logout, me, refresh, forgot/reset.

## Phase 2

Profile

- [x] Buat `ProfileController` (`show`, `update`, `uploadPhoto`).
- [x] Tambahkan route `GET/PUT /profile`, `POST /profile/photo`.
- [x] Upload avatar via `ImageHelper`.
- [x] Test: lihat profil, update, upload foto, otorisasi pemilik data.

## Phase 3

Event

- [x] Daftarkan route CRUD API: `POST /events`, `PUT /events/{id}`, `DELETE /events/{id}`.
- [x] Daftarkan route `GET /events/{id}/participants`.
- [x] Implementasikan `GET /events/{id}/qr` (daftar QR peserta).
- [x] Terapkan role admin/organizer pada endpoint CRUD.
- [x] Test: register flow (paid/free/membership), kuota penuh, duplikat.

## Phase 4

Merchandise

- [x] Implementasikan method `order()` di `MerchandiseController` (memanggil `MerchandiseService::createOrder`).
- [x] Tambahkan route `GET /merchandise/orders`, `GET /merchandise/orders/{id}`.
- [x] Tambahkan endpoint cancel order & upload bukti bayar.
- [x] Integrasikan order dengan tabel `payments` (payment_type `merchandise`).
- [x] Test: order, stok, my orders, cancel.
- [x] Tambahkan route `POST /payments/confirm` (bendahara/admin) untuk konfirmasi pembayaran.

## Phase 5

Organization

- [x] Implementasikan `show()`, `stats()`, search, filter `year`/`level` pada API.
- [x] (Opsional) Tambahkan migration `parent_id`/`level` untuk struktur tree.
- [x] Test: list, search, filter.

## Phase 6

Attendance

- [x] Tambahkan endpoint `GET /attendance/report` di API.
- [x] Implementasikan `report()` di `AttendanceController`.
- [x] Isi `attendance_logs` pada setiap check-in/check-out.
- [x] (Opsional) Siapkan `sync-up` / `sync-down` untuk offline mode.
- [x] Test: check-in/out, QR scan, duplikat, report.

## Phase 7

Testing

- [x] Semua test feature API lolos (`php artisan test` → 125 passed).
- [x] Postman collection diperbarui sesuai route final (`docs/postman/sh3-api.postman_collection.json`).
- [x] Response mengikuti standar `09-api-response-standard.md`.
- [x] Jalankan `php artisan test` tanpa failure.

## Final Checklist

- [x] Semua route terdahulu (`docs/readme.md` API Endpoints) tersedia.
- [x] Tidak ada route yang menunjuk ke method controller yang belum ada.
- [x] Format response konsisten (success, error, validation, 401/404/422).
- [x] Dokumentasi `docs/revision-1/*` sinkron dengan kode.
- [x] README progress dicentang.
