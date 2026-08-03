# Authentication Revision

## Tujuan

Menyamakan fitur API Authentication dengan API terdahulu (spesifikasi `docs/readme.md` → *Authentication API*).

Spesifikasi terdahulu menyebutkan 6 endpoint:

```
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

## Kondisi Saat Ini

6 endpoint terdahulu sudah tersedia: `register`, `login`, `logout`, `refresh`, `forgot-password`, `reset-password` (plus bonus `me`).

## Perbandingan Route Lama vs Route Baru

| # | Endpoint Terdahulu | Endpoint Saat Ini | Status |
|---|---|---|---|
| 1 | `POST /api/v1/auth/register` | `POST /api/v1/auth/register` | ✅ Ada |
| 2 | `POST /api/v1/auth/login` | `POST /api/v1/auth/login` | ✅ Ada |
| 3 | `POST /api/v1/auth/logout` | `POST /api/v1/auth/logout` | ✅ Ada (auth:sanctum) |
| 4 | `POST /api/v1/auth/refresh` | `POST /api/v1/auth/refresh` | ✅ Ada (auth:sanctum) |
| 5 | `POST /api/v1/auth/forgot-password` | `POST /api/v1/auth/forgot-password` | ✅ Ada |
| 6 | `POST /api/v1/auth/reset-password` | `POST /api/v1/auth/reset-password` | ✅ Ada |
| 7 | — | `GET /api/v1/auth/me` | ➕ Bonus |

## Route yang Sudah Ada

`routes/api.php` (prefix `v1`):

```php
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
```

## Controller yang Diubah

- `app/Http/Controllers/API/AuthController.php` — method `refresh()`, `forgotPassword()`, `resetPassword()` sudah ditambahkan.

## Method Baru

| Method | Fungsi |
|---|---|
| `refresh()` | Membuat token Sanctum baru untuk user yang sedang login, mencabut token lama |
| `forgotPassword(Request $request)` | Validasi email, generate token reset, kirim email (Laravel `Password::sendResetLink`) |
| `resetPassword(Request $request)` | Validasi token + password baru, reset password user |

## Request Validation

Sudah ada:

- `app/Http/Requests/LoginRequest.php`
  - `email` → required, email
  - `password` → required, string
- `app/Http/Requests/RegisterRequest.php`
  - `name` → required, string, max:255
  - `email` → required, email, unique di `users` dan `participants`
  - `phone`, `gender`, `date_of_birth`, `address`, `emergency_contact`, `emergency_phone`, `medical_conditions`, `blood_type`, `jersey_size` → nullable (validasi sesuai kolom peserta)

Ditambahkan:

- `ForgotPasswordRequest` → `email` required, email, exists:users,email
- `ResetPasswordRequest` → `token` required, `email` required email, `password` required confirmed min:8

## Business Logic

Terdahulu / sekarang (`app/Services/AuthService.php`):

| Proses | Lokasi |
|---|---|
| Login (cek password, cek `is_active`, update `last_login`) | `AuthService::login()` |
| Generate token Sanctum | `AuthService::generateToken()` |
| Cabut token saat logout | `AuthService::revokeToken()` |
| Cabut semua token | `AuthService::revokeAllTokens()` |
| Rotasi token saat refresh | `AuthService::refreshToken()` |
| Kirim link reset password | `AuthService::sendResetLink()` (via `Password::sendResetLink`) |
| Reset password | `AuthService::resetPassword()` (via `Password::reset`) |

Alur register saat ini (di `AuthController::register`):

1. Buat `User` (role `participant`, password random 60 karakter) dalam satu `DB::transaction`.
2. Buat `Participant` terkait dari data registrasi.
3. Generate token → response `201`.

## Response Success

`POST /api/v1/auth/login`:

```json
{
  "message": "Login berhasil",
  "user": { "id": 1, "name": "...", "email": "...", "role": "participant" },
  "token": "<plain-text-token>"
}
```

`POST /api/v1/auth/register` → `201`:

```json
{
  "message": "Registrasi berhasil",
  "user": { "...": "...", "participants": [] },
  "token": "<plain-text-token>"
}
```

`POST /api/v1/auth/logout` / `refresh` → `200`:

```json
{ "message": "Logout berhasil" }
```

```json
{ "message": "Token berhasil diperbarui.", "token": "<plain-text-token>" }
```

`POST /api/v1/auth/forgot-password` → `200`:

```json
{ "message": "Link reset password telah dikirim ke email Anda." }
```

`POST /api/v1/auth/reset-password` → `200`:

```json
{ "message": "Password berhasil direset." }
```

`GET /api/v1/auth/me`:

```json
{ "user": { "...": "...", "participants": [] } }
```

## Response Error

- Kredensial salah / akun nonaktif → `422` (ValidationException):

```json
{
  "message": "Email atau password salah.",
  "errors": { "email": ["Email atau password salah."] }
}
```

- Belum login → `401 Unauthenticated` (default Sanctum).
- User tanpa profil peserta → `404`.

## Database yang Digunakan

- Tabel `users` — `0001_01_01_000000_create_users_table.php` + `2026_07_30_122526_add_participant_role_to_users.php` (role `participant` ditambahkan).
- Tabel `participants` — dibuat di transaksi register.
- Tabel `password_reset_tokens` — sudah ada (untuk fitur forgot/reset password).
- Tabel `user_activity_logs` — mencatat aksi `login` / `logout` (via `UserService::logActivity`).

## Middleware

- `auth:sanctum` untuk `logout`, `me`, `refresh`.
- Endpoint `register`, `login`, `forgot-password`, `reset-password` publik.
- `app/Http/Middleware/RoleMiddleware.php` & `AdminMiddleware.php` hanya dipakai di route web (admin), tidak dipakai di route auth API.

## Testing Scenario

- Register sukses → `201`, user + participant terbuat.
- Register dengan email duplikat → `422`.
- Login sukses → `200` + token.
- Login salah password / email tidak terdaftar → `422`.
- Login akun nonaktif (`is_active=false`) → `422`.
- Logout → `200`, token dicabut.
- `/auth/me` tanpa token → `401`.
- Refresh → token baru, token lama dicabut.
- Forgot password → kirim link reset.
- Reset password dengan token valid → password berubah.

## Acceptance Criteria

- [x] 6 endpoint terdahulu tersedia dan berfungsi.
- [x] Register membuat `User` + `Participant` dalam satu transaksi.
- [x] Login hanya untuk user aktif, `last_login` ter-update.
- [x] Semua endpoint berautentikasi menolak tanpa token (`401`).
- [x] Response mengikuti format sukses/error di dokumen `09-api-response-standard.md`.

## Checklist

- [x] AuthController: tambah `refresh()`, `forgotPassword()`, `resetPassword()`.
- [x] Tambah route di `routes/api.php`.
- [x] Tambah `ForgotPasswordRequest` & `ResetPasswordRequest`.
- [x] Integrasi `Password::sendResetLink` / `Password::reset` dari Laravel.
- [x] Catat aktivitas ke `user_activity_logs` untuk semua aksi auth.
- [x] Tambah test feature di `tests/Feature/AuthApiTest.php`.
