# 01 — Arsitektur & Authentication

## Layered Architecture

```
PRESENTATION LAYER
  Laravel Blade (Admin Panel) | API (Participant)
APPLICATION LAYER
  Controllers → Services → Requests → Resources
DOMAIN LAYER
  Models → DTOs
INFRASTRUCTURE LAYER
  Database (MySQL) → Queue → Mail → Notification
```

### Architecture Rules

- **Controllers** — thin routing layer. Delegates all business logic and data access to Services. No Eloquent queries, no DB::transaction, no business rules.
- **Services** — business logic, orchestration, and data access (via direct Eloquent queries or Repositories). Owns transaction boundaries.
- **Repositories** — reusable data-access helpers extending `BaseRepository`. Used for common CRUD patterns; Services may also query directly when repository abstraction adds no value.
- **Models** — Eloquent models with relationships, casts, scopes, lifecycle hooks. No business logic.

### Controllers That Delegate to Services

| Controller | Service | Method(s) Moved |
|---|---|---|
| `API\AttendanceController` | `AttendanceService` | `syncUp()` → `syncUpOffline()` |
| `API\AuthController` | `AuthService` | `register()`, `generateUsername()` |
| `API\ProfileController` | `ProfileService` | `update()`, `uploadPhoto()`, `getProfilePayload()` |
| `API\GalleryController` | `GalleryService` | `index()` → `getAllPublic()` |
| `API\NotificationController` | `NotificationService` | `index()`, `markAsRead()`, `markAllAsRead()`, `unreadCount()` |
| `Admin\NotificationController` | `NotificationService` | Same as above (web + JSON) |
| `Admin\DashboardController` | `DashboardService` | `buildRecentActivity()` |

## Authentication

### Two-Factor Auth Design

Sistem memiliki dua domain otentikasi yang sama sekali terpisah:

#### 1. Admin / Backoffice (Web Session)

- **Endpoint:** `GET/POST /login` (session-based), `POST /logout`
- **Credential:** `email` + `password`
- **Request class:** `EmailLoginRequest` (memvalidasi `email` + `password`)
- **Provider:** Laravel Eloquent default (menggunakan kolom `email` sebagai username field)
- **Middleware:** `auth` (session), `RoleMiddleware` untuk otorisasi berbasis role
- **User yang didukung:** semua role kecuali `participant` (admin_full_access, admin_laman, admin_member, admin_bnh, organizer, bendahara, sponsor, merchandise)

#### 2. Participant (API Token)

- **Endpoint:** `POST /api/v1/auth/login` (token-based Laravel Sanctum)
- **Credential:** `username` + `password` (email **tidak** dipakai untuk login)
- **Request class:** `LoginRequest` (memvalidasi `username` + `password`)
- **Service:** `AuthService::login()` — lookup via `UserRepository::findByUsername()`, hanya menerima user dengan `role = 'participant'`
- **Middleware:** `auth:sanctum` pada route yang membutuhkan otentikasi
- **User yang didukung:** hanya role `participant`

### Auth Flow

1. **Admin login (web):** form kirim `email` + `password` → `EmailLoginRequest` memvalidasi → `Auth::attempt(['email', 'password'])` → session dibuat → redirect `/admin/dashboard`.
2. **Participant login (API):** client kirim `username` + `password` → `LoginRequest` memvalidasi → `AuthService::login()` lookup user berdasarkan `username`, pastikan `role = 'participant'` dan `is_active = true` → `AuthService::generateToken()` (Sanctum) → kembalikan token.
3. **Admin yang mencoba login via API:** ditolak dengan error "Akun ini bukan peserta."
4. **Registrasi:** `POST /api/v1/auth/register` → `RegisterRequest` (username opsional) → buat `User` (role `participant`) + `Participant` dalam transaksi.
5. **Logout:** cabut token Sanctum (API) atau invalidate session (web).

### Middleware

- `RoleMiddleware` — memeriksa role user dan mengizinkan akses hanya untuk role yang tercantum. Redirect `/login` jika belum login, abort(403) jika role tidak cocok.
- `AdminMiddleware` — middleware tambahan untuk admin.
- `EnsureApiMeta` — middleware untuk API response (menambahkan meta seperti timestamp, request_id).

### Gate Definitions

Gate didefinisikan di `AppServiceProvider` untuk setiap role admin:
`admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, `merchandise`, `gallery`.

`participant` tidak memiliki Gate admin; aksesnya melalui API authenticated atau endpoint publik.

Otorisasi route memakai `RoleMiddleware`; Gate tambahan didefinisikan di `AppServiceProvider`. Route, Gate, service, model, migration, dan coverage aktual dirangkum di `docs/17 — Implementation Sync.md`.
