# 17 — Implementation Sync

Dokumen ini adalah indeks hasil sinkronisasi dokumentasi dengan implementasi Laravel yang terdeteksi pada **4 Agustus 2026**. Sumber kebenaran teknisnya adalah `app/`, `routes/`, `database/migrations/`, `database/seeders/`, `config/`, `bootstrap/`, `composer.json`, dan hasil `php artisan`.

## Overview

SH3 Event Management adalah aplikasi Laravel 13 untuk panel admin berbasis Blade/AdminLTE dan REST API `/api/v1` untuk peserta. Fitur yang terimplementasi mencakup autentikasi, user/role, peserta, membership, event, kategori, pembayaran polymorphic, attendance/QR, galeri, sponsor, merchandise, organisasi, dan notifikasi database/broadcast.

## Architecture and Responsibilities

```text
Routes + Middleware + Form Requests + Resources
                    |
             Controllers
                    |
              Services + DTOs
                    |
             Repositories
                    |
             Eloquent Models
                    |
                Migrations
```

- Controllers mengorkestrasi request dan response.
- Services memuat aturan bisnis dan transaksi.
- Repositories memusatkan query dan mewarisi `BaseRepository`.
- Models mendefinisikan casts, accessors, dan relationships.
- Form Requests memvalidasi payload.
- API Resources membentuk payload API.
- `RepositoryServiceProvider` mengikat repository ke implementasi yang dipakai aplikasi.

## Inventory and Coverage

| Artefak | Implementasi | Dokumentasi modul | Status |
|---|---:|---:|---|
| Controllers | 29 | 29 | tercakup melalui dokumen modul dan route index ini |
| Services | 10 | 10 | tercakup |
| Repositories | 15 | 15 | tercakup |
| Models | 18 | 18 | tercakup |
| Routes terdaftar | 75 (API) + 74 (Web/console) = 149 | 149 | tercakup |
| API routes | 75 | 75 | tercakup |
| Migrations | 23 | 23 | tercakup dalam schema/migration notes |
| Middleware aplikasi | 3 | 3 | tercakup |
| Form Requests | 24 | 24 | tercakup sebagai validation layer |
| API Resources | 9 | 9 | tercakup sebagai response layer |
| Notifications | 1 | 1 | `AdminNotification` |
| Events aplikasi | 0 | 0 | tidak ada event class aplikasi |
| Listeners aplikasi | 0 | 0 | tidak ada listener class aplikasi |
| Jobs aplikasi | 0 | 0 | queue dipakai oleh notification, bukan job class aplikasi |
| Console commands aplikasi | 4 | 4 | `events:update-status`, `membership:expire`, `membership:auto-renew`, `notifications:cleanup` yang semuanya telah didaftarkan ke scheduler |
| Scheduler aplikasi | 4 | 4 | `events:update-status` everyMinute, `membership:expire` dailyAt 00:00, `membership:auto-renew` dailyAt 01:00, `notifications:cleanup --days=30` dailyAt 02:00 |
| Policies aplikasi | 0 | 0 | otorisasi memakai middleware role dan Gate |
| DTOs | 4 | 4 | `EventDTO`, `ParticipantDTO`, `PaymentDTO`, `UserDTO` |

## Authentication, Roles, and Permissions

- Web admin memakai session middleware `auth`; login `GET/POST /login`, logout `POST /logout`.
- API memakai `auth:sanctum` pada route privat.
- `RoleMiddleware` mengharuskan user login dan menolak role yang tidak diizinkan dengan HTTP 403.
- `AdminMiddleware` dan `EnsureApiMeta` adalah middleware aplikasi yang tersedia.
- Role user: `admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, `merchandise`, `participant`.
- Gate yang didefinisikan di `AppServiceProvider`: `admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, dan `merchandise`.
- `participant` tidak memiliki Gate admin; aksesnya berjalan melalui API authenticated atau endpoint publik.

## Route and API Index

Semua API memakai prefix `/api/v1`.

### Public API

`POST /auth/register`, `POST /auth/login`, `POST /auth/forgot-password`, `POST /auth/reset-password`, `GET /events/upcoming`, `GET /events`, `GET /events/{id}`, `GET /events/{id}/participants`, `GET /galleries`, `GET /sponsors`, `GET /categories`, `GET /organization`, `GET /organization/{id}`, `GET /organization/stats`, `GET /organization/tree`, `GET /organization/years`, `GET /merchandise`, dan `GET /merchandise/{id}`.

### Authenticated API

Auth, profile, participant, event registration/management, payment, membership, attendance, merchandise orders, dan notification mengikuti route aktual di `routes/api.php`. Endpoint konfirmasi payment adalah `POST /payments/confirm/{id}`, bukan `PUT`.

### Admin Web

Semua route admin memakai `/admin` dan session `auth`. Resource routes tersedia untuk users, participants, events, categories, galleries, organization, sponsors, dan merchandise. Route khusus meliputi dashboard, notification actions, membership plans, membership grant/cancel, event publish, payment confirm/reject, serta attendance scan/report/generate QR. Detail role per route adalah sumber otoritatif `routes/web.php`, bukan tabel lama di README.

## Controllers and Services

Controller API tersedia untuk Auth, Event, Participant, Profile, Payment, Membership, Attendance, Merchandise, Gallery, Category, Organization, Sponsor, dan Notification. Controller admin tersedia untuk Dashboard, User, Participant, Event, Category, Gallery, Organization, Sponsor, Merchandise, Membership, MembershipPlan, Payment, Attendance, dan Notification.

Service yang terimplementasi:

- `AuthService`: login, token, refresh, password reset.
- `UserService`: CRUD user, active toggle, activity logging.
- `EventService`: event registration, status/business rules, QR-related event operations.
- `MembershipService`: plan lookup, grant, subscription, activation, cancellation, expiry/statistics.
- `PaymentService`: create, confirm, reject, dan aktivasi paymentable polymorphic.
- `MerchandiseService`: product/order, stock, cancellation, payment proof.
- `AttendanceService`: check-in/out, scan, report, sync up/down.
- `QRCodeService`: generate/decode format QR SH3.
- `NotificationService`: notify role, admin, user, dan participant.
- `SidebarService`: data menu/sidebar admin.

## Models and Relationships

Model terdeteksi: `User`, `UserActivityLog`, `Participant`, `Category`, `Event`, `EventSchedule`, `EventParticipant`, `MembershipPlan`, `MembershipHistory`, `Payment`, `Attendance`, `AttendanceLog`, `Gallery`, `GalleryAlbum`, `Sponsor`, `OrganizationMember`, `Merchandise`, dan `MerchandiseOrder`.

Relasi utama: user-participant/activity logs; participant-membership histories/event participants/payments/orders/organization members; event-category/schedules/participants/galleries/sponsors; payment morph ke event participant, merchandise order, dan membership history; gallery-event/album; organization hierarchy parent-child; merchandise-orders; attendance-event participant dan attendance logs.

## Validation, Responses, and Errors

Validasi berada pada 24 Form Request di `app/Http/Requests`. Response sukses umumnya memakai `data` dan optional `message`, sedangkan validasi Laravel memakai HTTP 422 dengan `message` dan `errors`. Auth gagal adalah HTTP 401, forbidden role HTTP 403, resource tidak ditemukan HTTP 404. Field dan batas upload mengikuti Request class aktual.

## Database Schema and Migration Changes

Schema aktif terdiri dari users/cache/jobs, participants, categories, user activity logs, membership histories, events, event participants, merchandise, payments, organization members, galleries, sponsors, attendance logs, serta migration tambahan untuk attendance, Sanctum tokens, participant role, membership pending status, notifications, membership plans, membership type string, dan organization hierarchy. Ada 22 migration files. Gunakan migration aktual sebagai sumber kolom, foreign key, cascade, dan enum; SQL contoh lama di `docs/readme.md` bukan schema executable.

## Notifications, Events, Listeners, Jobs, Scheduler

- `AdminNotification` memakai database dan broadcast, queueable (`ShouldQueue`), serta menyimpan title/body/icon/url. Default notification channel `mail` sudah diganti dengan `database` dan `broadcast`.
- `routes/channels.php` mendaftarkan private channel user notification.
- Tidak ada event, listener, job, atau policy class aplikasi.
- `routes/console.php` mendaftarkan command closure `inspire` dan juga command aplikasi berikut:
  - `events:update-status` — transisi event draft→publish→ongoing→completed berdasarkan tanggal.
  - `membership:expire` — menandai membership histories yang expired.
  - `membership:auto-renew` — auto-renew participants dengan membership yang akan kedaluwarsa dalam 7 hari.
  - `notifications:cleanup` — menghapus notifikasi lebih dari 30 hari.
- Scheduler aktif di `bootstrap/app.php -> withSchedule()`:
  - `events:update-status` dijalankan setiap menit.
  - `membership:expire` dijalankan setiap hari pukul 00:00.
  - `membership:auto-renew` dijalankan setiap hari pukul 01:00.
  - `notifications:cleanup --days=30` dijalankan setiap hari pukul 02:00.

## Configuration and Dependencies

Dependensi utama: PHP `^8.3`, Laravel `^13.8`, Sanctum, Reverb, Tinker, dan Simple QR Code. Redis dipakai sesuai environment/config untuk session, cache, dan queue; Reverb dipakai untuk broadcast. Konfigurasi aplikasi berada di `config/*.php`, environment `.env`, dan bootstrap route/middleware registration.

## Sequence Flow

1. Request masuk melalui route dan middleware.
2. Form Request memvalidasi input bila endpoint mutation menggunakannya.
3. Controller memanggil Service/Repository.
4. Service menjalankan aturan bisnis dan transaksi.
5. Repository membaca/menulis model Eloquent.
6. Resource atau redirect menghasilkan response.
7. Notification dapat diantrikan ke database/broadcast.

## Request and Response Examples

```http
POST /api/v1/auth/login
Content-Type: application/json

{"email":"user@example.com","password":"secret123"}
```

```json
{"data":{"token":"...","user":{"id":1,"role":"participant"}},"message":"Login berhasil"}
```

```json
{"message":"The given data was invalid.","errors":{"field":["The field is required."]}}
```

Payload dan field response per modul harus dirujuk ke Request/Resource aktual karena tidak semua endpoint memakai bentuk yang sama.

## Known Limitations and Remaining Discrepancies

- Tidak ada application job/event/listener/policy/command khusus selain 4 Artisan command aplikasi yang sudah didaftarkan ke scheduler.
- `MembershipService::markExpiredHistories()` masih dipanggil manual di halaman index memberships, selain dijalankan via scheduler `membership:expire`.
- Beberapa dokumen lama masih membawa schema konseptual dan route tanpa prefix `/api/v1`; dokumen ini dan source code menjadi rujukan koreksi.
- `docs/readme.md` telah diperbarui pada 2026-08-04 untuk mencerminkan schema aktual, route aktual, dan arsitektur terkini.
- Tidak ada business logic yang diubah dalam sinkronisasi ini.
- Model Event menggunakan `title` bukan `name`, `quota` bukan `max_participants`, `is_free_for_members` bukan `is_membership_free`.
- `merchandise.status` adalah ENUM (available|sold_out|discontinued), bukan boolean `is_active`; tidak ada kolom `category`.
- `payments` sejak awal menggunakan polymorphic morphs (`paymentable_type` + `paymentable_id`), bukan FK individual.
- `galleries` menggunakan `file_path` dan `thumbnail_path` (bukan `image`).
- `participants.membership_type` adalah VARCHAR(50) (bukan ENUM) setelah migration `2026_07_31_100001`.
- `membership_histories.status` menyertakan 'pending' sebagai default (ditambahkan migration `2026_07_31_000001`).
- `sponsors` menggunakan `tier` (bukan `sponsor_level`) dengan kolom tambahan `year` dan `sort_order`.
- `users.role` menyertakan 'participant' (ditambahkan migration `2026_07_30_122526`).

## Cross-check Checklist

- Route ↔ Controller: diverifikasi dari route files dan `php artisan route:list`.
- Controller ↔ Service/Repository: inventory dan source class diverifikasi.
- Model ↔ Migration: inventory 18 model dan 22 migration dicatat.
- Resource ↔ API response: 9 Resource dicatat; payload detail mengikuti Resource aktual.
- Middleware ↔ Route: `auth`, `auth:sanctum`, dan role groups dicatat dari route files.
- Notification ↔ Roles: `NotificationService` dan `AdminNotification` dicatat.
- Scheduler ↔ Console: 4 command aplikasi telah didaftarkan ke scheduler (`events:update-status` everyMinute, `membership:expire` dailyAt 00:00, `membership:auto-renew` dailyAt 01:00, `notifications:cleanup --days=30` dailyAt 02:00).
- Policy/Enum/Job/Event/Listener: tidak ada class aplikasi yang terdeteksi.
