# Testing

Rencana pengujian untuk seluruh fitur API Revision 1. Gunakan `php artisan test` / PHPUnit dengan `RefreshDatabase` pada tiap test.

## Unit Test

Target unit test untuk layer Service & Repository:

- `AuthService`: login sukses, password salah, akun nonaktif, generate/revoke token.
- `MembershipService`: `checkEligibility`, `grant`, `requestSubscription`, `activate`, `cancelMembership`, `calculateEndDate`, `calculatePrice`.
- `PaymentService`: `createPayment` (invoice number unik), `confirmPayment` (update status + markAsPaid paymentable).
- `EventService`: `registerParticipant` (kuota, duplikat, membership free, free event, paid), `cancelRegistration`, `publishEvent`.
- `QRCodeService`: `generate` & `decode` format `SH3-{event}-{participant}-{hash}`.
- `MerchandiseService`: `createOrder` (stok cukup/tidak), `confirmPayment`.
- `AttendanceService`: check-in/out valid & invalid.
- Repository: query `findPublished`, `findUpcoming`, `findActive`, dll.

## Feature Test

Test fitur lintas komponen (route + controller + service + database):

- Registrasi user + participant (transaksi).
- Alur membership dari subscribe → payment → confirm → active.
- Alur pendaftaran event → QR code → check-in/check-out.
- Alur order merchandise → stok berkurang → konfirmasi bayar.

## API Test

Test endpoint API (`/api/v1/*`) dengan `Sanctum::actingAs`:

- Setiap endpoint berautentikasi menolak tanpa token → `401`.
- Setiap endpoint merespons `422` untuk payload tidak valid.
- Response shape sesuai `09-api-response-standard.md`.

## Authentication Test

`tests/Feature/AuthApiTest.php` (baru):

- register sukses `201` + token.
- register email duplikat → `422`.
- login sukses `200` + token.
- login salah password → `422`.
- login akun nonaktif → `422`.
- logout → `200`, token dicabut.
- me tanpa token → `401`.
- refresh / forgot-password / reset-password (setelah diimplementasikan).

## Event Test

`tests/Feature/EventApiTest.php` (baru):

- list & detail event publik.
- upcoming.
- register event (free / paid / membership).
- register kuota penuh → `422`.
- register duplikat → `422`.
- CRUD event via API (setelah route ditambahkan).
- participants & QR event.

## Merchandise Test

`tests/Feature/MerchandiseApiTest.php` (baru):

- list merchandise (status `available`).
- detail merchandise.
- order sukses → stok berkurang, order `pending`.
- order stok tidak cukup → `422`.
- my orders / order detail (hanya milik sendiri).
- cancel order & upload bukti bayar (setelah diimplementasikan).

## Organization Test

`tests/Feature/OrganizationApiTest.php` (baru):

- list publik `200`.
- hanya anggota aktif, urutan `sort_order`.
- search / filter year / level (setelah diimplementasikan).

## Attendance Test

`tests/Feature/AttendanceApiTest.php` (baru):

- check-in sukses.
- check-in peserta tidak terdaftar → `422`.
- check-in ganda → `422`.
- check-out tanpa check-in → `422`.
- scan QR valid/invalid.
- report per event (setelah diimplementasikan).

## Profile Test

`tests/Feature/ProfileApiTest.php` (baru):

- get profile tanpa token → `401`.
- get profile → data user login.
- update profile → tersimpan.
- email duplikat → `422`.
- upload avatar valid/invalid.

## Performance Test

- Pastikan tidak ada N+1 query pada list (gunakan eager loading di repository).
- Pagination pada list besar (`GET /participants`, `GET /events` bila dipaginasi).
- Jumlah query per request dikendalikan (mis. via `DB::enableQueryLog` pada test tertentu).

## Security Test

- Token milik user A tidak bisa mengakses resource user B (mis. `GET /merchandise/orders/{id}` milik orang lain → `404`).
- Input injection dicegah oleh Eloquent / validation.
- Upload file divalidasi tipe & ukuran.
- `payment_proof`, `avatar`, dll. disimpan dengan nama aman (framework default).
- IDOR pada `PUT /participants/{id}`, `GET /payments/{id}` — pastikan otorisasi.

## Postman Collection

- Buat/update collection `docs/postman/sh3-api.postman_collection.json`.
- Environment: base URL `http://localhost:8000/api/v1`, token bearer.
- Kelompokkan folder sesuai modul (Auth, Events, Merchandise, Organization, Attendance, Profile, Membership, Payments, Notifications).
- Sertakan contoh body & response untuk setiap endpoint.

## Manual Testing

Skenario manual yang disarankan:

1. Register peserta baru → cek user + participant terbentuk.
2. Subscribe membership → upload bukti → konfirmasi bendahara → status aktif.
3. Buat event (admin) → daftar peserta → QR muncul.
4. Scan QR check-in/check-out → attendance tercatat.
5. Order merchandise → stok berkurang → konfirmasi bayar.
6. Ganti role pengguna → akses menu sesuai matrix role.
7. Login sebagai tiap role dan pastikan halaman admin sesuai izin.

## Acceptance Criteria

- [x] Seluruh test feature & unit berwarna hijau (`php artisan test` → 125 passed).
- [x] Seluruh route terdahulu berfungsi dengan response sesuai standar.
- [x] Tidak ada endpoint yang melempar 500 untuk input yang salah (semua tervalidasi).
- [x] IDOR & otorisasi teruji.
- [x] Postman collection siap untuk integrasi dengan aplikasi mobile (`docs/postman/sh3-api.postman_collection.json`).

## Catatan Implementasi

- `tests/TestCase.php` menonaktifkan middleware CSRF (`PreventRequestForgery`) dan memaksa `queue.default=sync` sehingga notifikasi database tersimpan sinkron saat test.
- Test berjalan di environment yang memakai MySQL (config cache); bila `php artisan config:clear` dijalankan, pastikan driver sqlite terpasang (`pdo_sqlite`) atau sesuaikan `phpunit.xml`.
