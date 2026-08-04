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

## Authentication

- Admin login: web session-based melalui `GET/POST /login`; logout `POST /logout`.
- Participant/API login: token-based Laravel Sanctum melalui `POST /api/v1/auth/login`.
- API private route memakai middleware `auth:sanctum`; route admin memakai middleware `auth`.

## Admin Roles

- `admin_full_access` — full access.
- `admin_laman` — akses menu website sesuai route role.
- `admin_member` — participant dan membership sesuai route role.
- `admin_bnh` — role user tersedia; akses aktual harus mengikuti route yang eksplisit.
- `organizer` — event sesuai route role.
- `bendahara` — membership dan payment sesuai route role.
- `sponsor` — sponsor sesuai route role.
- `merchandise` — merchandise sesuai route role.
- `participant` — role API; bukan role admin.

## Middleware

- `RoleMiddleware` — memeriksa role user dan mengizinkan akses hanya untuk role yang tercantum. Redirect `/login` jika belum login, abort(403) jika role tidak cocok.
- `AdminMiddleware` — middleware tambahan untuk admin.
- `EnsureApiMeta` — middleware untuk API response (menambahkan meta seperti timestamp, request_id).

## Gate Definitions

Gate didefinisikan di `AppServiceProvider` untuk setiap role admin:
`admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, `merchandise`.

`participant` tidak memiliki Gate admin; aksesnya melalui API authenticated atau endpoint publik.

Otorisasi route memakai `RoleMiddleware`; Gate tambahan didefinisikan di `AppServiceProvider`. Route, Gate, service, model, migration, dan coverage aktual dirangkum di `docs/17 — Implementation Sync.md`.
