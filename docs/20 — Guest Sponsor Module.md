# 20 — Guest Sponsor Module

Manajemen akun **guest sponsor** (perwakilan sponsor yang hadir di event) dengan kuota,
QR Code, dan attendance. Role `guest_sponsor` login melalui **API** (bukan web admin).

## Konsep

- Kuota ditentukan per pasangan **(sponsor, event)** pada pivot `event_sponsors.max_guest_accounts`.
- Admin membuat akun guest sponsor dalam kuota → otomatis membuat `users` (role `guest_sponsor`),
  username `gs_{slug(sponsor)}`, password acak `Str::random(8)` (ditampilkan sekali), dan QR unik
  `GS-{sponsor_id}-{event_id}-{seq:04d}`.
- Akun memiliki masa berlaku (`valid_from` / `valid_until`, default mengikuti `events.end_date`).
- Akun yang dinonaktifkan, kedaluwarsa, atau terkait event selesai/dibatalkan **tidak dapat**
  login maupun check-in; riwayat attendance tetap tersimpan.

## Database

Migration (semua `2026_08_18`):

- `000003_add_guest_sponsor_role_to_users` — menambahkan `guest_sponsor` ke ENUM `users.role`.
- `000004_add_max_guest_accounts_to_event_sponsors` — kolom kuota pada pivot.
- `000005_create_guest_sponsors_table` — metadata akun (user, sponsor, event, QR, masa berlaku, status).
- `000006_create_guest_sponsor_attendances_table` — attendance + log (unique: `gsa_guest_event_unique`,
  `gsal_guest_event_type_unique`).

## Routes

### Admin Web (`/admin` — `admin_full_access`)

- `GET /guest-sponsors` — daftar akun + kartu statistik + tabel kuota.
- `GET /guest-sponsors/create` — form tambah (dropdown hanya sponsor/event berkuota, event dependen).
- `POST /guest-sponsors`, `POST /guest-sponsors/quota`, `POST /guest-sponsors/{id}/toggle-active`.
- `GET /guest-sponsors/{id}` — detail + QR + edit.
- `PUT /guest-sponsors/{id}`, `DELETE /guest-sponsors/{id}`.

### API (`/api/v1`)

- `POST /guest-sponsor/auth/login` — login (username/password) → Sanctum token.
- `GET /guest-sponsor/auth/me` — profil + status usable (auth).
- `POST /guest-sponsor/attendance/check-in`, `check-out` — attendance (auth).
- `POST /guest-sponsor/attendance/scan` — scan QR (auth).
- `GET /guest-sponsor/attendance/my` — riwayat attendance (auth).

## Breaking Change

Role **`sponsor`** kehilangan seluruh akses Admin Panel:

- Route `admin.sponsors.*` kini hanya `admin_full_access, admin_laman`.
- Menu Sponsors & Dashboard tidak lagi menyertakan role `sponsor`.
- Login web ditolak untuk role `sponsor` dan `guest_sponsor` (keduanya memakai API).

Detail model, repository, service, request, controller, migration, API response, dan permission
dirangkum di `docs/17 — Implementation Sync.md`.