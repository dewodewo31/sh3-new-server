# 17 — Implementation Sync

Dokumen ini adalah indeks hasil sinkronisasi dokumentasi dengan implementasi Laravel yang terdeteksi pada **4 Agustus 2026**. Sumber kebenaran teknisnya adalah `app/`, `routes/`, `database/migrations/`, `database/seeders/`, `config/`, `bootstrap/`, `composer.json`, dan hasil `php artisan`.

## Overview

SH3 Event Management adalah aplikasi Laravel 13 untuk panel admin berbasis Blade/AdminLTE dan REST API `/api/v1` untuk peserta. Fitur yang terimplementasi mencakup autentikasi, user/role, peserta, membership, event, kategori, pembayaran polymorphic, attendance/QR, galeri, sponsor, merchandise, organisasi, notifikasi database/broadcast, dan **guest sponsor** (sejak 2026-08-18).

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
| Controllers | 33 | 33 | tercakup melalui dokumen modul dan route index ini |
| Services | 15 | 15 | tercakup |
| Repositories | 18 | 18 | tercakup |
| Models | 22 | 22 | tercakup |
| Routes terdaftar | 68 (API) + 108 (Web/console) = 176 | 176 | tercakup |
| API routes | 68 | 68 | tercakup |
| Migrations | 36 | 36 | tercakup dalam schema/migration notes |
| Middleware aplikasi | 3 | 3 | tercakup |
| Form Requests | 32 | 32 | tercakup sebagai validation layer |
| API Resources | 10 | 10 | tercakup sebagai response layer |
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
- Role user: `admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, `merchandise`, `gallery`, **`guest_sponsor`** (sejak 2026-08-18), `participant`.
- Gate yang didefinisikan di `AppServiceProvider`: `admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, dan `merchandise`.
- `participant` tidak memiliki Gate admin; aksesnya berjalan melalui API authenticated atau endpoint publik.
- `sponsor` dan `guest_sponsor` **tidak dapat login ke web admin** (login web ditolak; keduanya memakai API). Role `sponsor` sejak 2026-08-18 **tidak lagi memiliki akses Admin Panel** (breaking).

## Route and API Index

Semua API memakai prefix `/api/v1`.

### Public API

`POST /auth/register`, `POST /auth/login`, `POST /auth/forgot-password`, `POST /auth/reset-password`, `GET /events/upcoming`, `GET /events`, `GET /events/{id}`, `GET /events/{id}/participants`, `GET /galleries`, `GET /sponsors`, `GET /categories`, `GET /organization`, `GET /organization/{id}`, `GET /organization/stats`, `GET /organization/tree`, `GET /organization/years`, `GET /merchandise`, `GET /merchandise/{id}`, **`POST /attendance/sync-up`** (sejak 2026-08-15), dan **`GET /attendance/sync-down`** (sejak 2026-08-15).

### Authenticated API

Auth, profile, participant, event registration/management, payment, membership, attendance, merchandise orders, dan notification mengikuti route aktual di `routes/api.php`. Endpoint konfirmasi payment adalah `POST /payments/confirm/{id}`, bukan `PUT`. Sejak 2026-08-18 tersedia endpoint **guest sponsor**: `POST /guest-sponsor/auth/login` (publik), serta `GET /guest-sponsor/auth/me`, `POST /guest-sponsor/attendance/scan`, `POST /guest-sponsor/attendance/check-in`, `POST /guest-sponsor/attendance/check-out`, `GET /guest-sponsor/attendance/my` (auth).

### Admin Web

Semua route admin memakai `/admin` dan session `auth`. Resource routes tersedia untuk users, participants, events, categories, galleries, gallery-albums, organization, sponsors, dan merchandise. Route khusus meliputi dashboard, notification actions, membership plans, membership grant/cancel, event publish, payment confirm/reject, serta attendance scan/report/generate QR. Sejak 2026-08-18: **guest-sponsors** (resource `admin_full_access`) dan role `sponsor` **dihilangkan** dari grup route sponsors. Detail role per route adalah sumber otoritatif `routes/web.php`, bukan tabel lama di README.

## Controllers and Services

Controller API tersedia untuk Auth, Event, Participant, Profile, Payment, Membership, Attendance, Merchandise, Gallery, Category, Organization, Sponsor, dan Notification. Controller admin tersedia untuk Dashboard, User, Participant, Event, Category, Gallery, GalleryAlbum, Organization, Sponsor, Merchandise, Membership, MembershipPlan, Payment, Attendance, Notification, dan Bookkeeping. Sejak 2026-08-18: controller **GuestSponsor** (Admin) serta **GuestSponsorAuth** dan **GuestSponsorAttendance** (API). Sejak 2026-08-19: `AttendanceController` (Admin) menangani scan QR **guest sponsor** lewat `processGuestSponsorScan()`.

Service yang terimplementasi:

- `AuthService`: login, token, refresh, password reset.
- `UserService`: CRUD user, active toggle, activity logging.
- `EventService`: event registration (termasuk re-registration update flow), status/business rules, QR-related event operations.
- `MembershipService`: plan lookup, grant, subscription, activation, cancellation, expiry/statistics.
- `PaymentService`: create, confirm, reject (kini juga memanggil `markAsRejected()` pada paymentable), dan aktivasi paymentable polymorphic.
- `MerchandiseService`: product/order, stock, cancellation, payment proof.
- `AttendanceService`: check-in/out, scan, report, sync up/down.
- `QRCodeService`: generate/decode QR berisi `participant_code` murni (member `\d{4}`, non-member `NM\d{4}`) serta `isGuestSponsorCode()` (pola `GS-\d+-\d+-\d{4}`) untuk deteksi QR guest sponsor.
- `NotificationService`: notify role, admin, user, dan participant.
- `SidebarService`: data menu/sidebar admin.
- **`GuestSponsorService`** (sejak 2026-08-18): createAccount (kuota + auto user/QR), generateUsername/QrCode, quota/setQuota, toggleActive, authenticate, isUsableForEvent, checkIn/checkOut, scan, history.

## Support & Components (baru 2026-08-15)

- `App\Support\Sort` — helper statis untuk sorting tabel aman (whitelist kolom). Dipakai
  oleh `BaseRepository::allSorted()/paginateSorted()` dan banyak repository modul.
- `resources/views/components/th-sort.blade.php` — Blade component `<x-th-sort column="...">`
  untuk header tabel yang bisa diurutkan.
- `tests/Feature/Admin/` — 12 file test admin (auth, access control, dashboard, participant,
  membership, membership plan, user management, bookkeeping, guest sponsor admin/api).
- `tests/Feature/Sh3ParticipantImportTest.php` — test seeder import peserta.
- `database/seeders/Sh3ParticipantImportSeeder.php` — import peserta dari data spreadsheet
  (idempotent, keyed on `participant_code`).

## Models and Relationships

Model terdeteksi: `User`, `UserActivityLog`, `Participant`, `Category`, `Event`, `EventSchedule`, `EventParticipant`, `MembershipPlan`, `MembershipHistory`, `Payment`, `Attendance`, `AttendanceLog`, `Gallery`, `GalleryAlbum`, `Sponsor`, `OrganizationMember`, `Merchandise`, `MerchandiseOrder`, `Bookkeeping`, dan sejak 2026-08-18 **`GuestSponsor`**, **`GuestSponsorAttendance`**, **`GuestSponsorAttendanceLog`**.

Relasi utama: user-participant/activity logs; participant-membership histories/event participants/payments/orders/organization members; event-category/schedules/participants/galleries/sponsors; payment morph ke event participant, merchandise order, dan membership history; gallery-event/album; organization hierarchy parent-child; merchandise-orders; attendance-event participant dan attendance logs; guest sponsor-user/sponsor/event/attendances/attendance logs.

## Validation, Responses, and Errors

Validasi berada pada Form Request di `app/Http/Requests` (termasuk `LoginRequest` untuk participant API login via `username`, dan `EmailLoginRequest` untuk web admin login via `email`). Response sukses API menggunakan `message`, `user`, dan `token`. Validasi Laravel memakai HTTP 422 dengan `message` dan `errors` (key field sesuai request). Auth gagal adalah HTTP 401, forbidden role HTTP 403, resource tidak ditemukan HTTP 404.

## Database Schema and Migration Changes

Schema aktif terdiri dari users/cache/jobs, participants, categories, user activity logs, membership histories, events, event participants, merchandise, payments, organization members, galleries, sponsors, attendance logs, serta migration tambahan untuk attendance, Sanctum tokens, participant role, membership pending status, notifications, membership plans, membership type string, organization hierarchy, dan sejak 2026-08-18 **guest sponsor** (role enum, kuota pivot `event_sponsors.max_guest_accounts`, `guest_sponsors`, `guest_sponsor_attendances` + logs). Gunakan migration aktual sebagai sumber kolom, foreign key, cascade, dan enum; SQL contoh lama di `docs/readme.md` bukan schema executable.

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

{"username":"johndoe","password":"secret123"}
```

```json
{"message":"Login berhasil","user":{"id":1,"name":"...","username":"johndoe","email":"...","role":"participant"},"token":"..."}
```

```json
{"message":"The given data was invalid.","errors":{"username":["The username field is required."]}}
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
- `users.role` menyertakan 'participant' (ditambahkan migration `2026_07_30_122526`) dan 'gallery' (migration `2026_08_16_000001`) serta **'guest_sponsor'** (migration `2026_08_18_000003`).
- Pivot `event_sponsors` menyertakan kolom **`max_guest_accounts`** (migration `2026_08_18_000004`) untuk kuota akun guest sponsor.

## Cross-check Checklist

- Route ↔ Controller: diverifikasi dari route files dan `php artisan route:list`.
- Controller ↔ Service/Repository: inventory dan source class diverifikasi.
- Model ↔ Migration: inventory 18 model dan 22 migration dicatat.
- Resource ↔ API response: 9 Resource dicatat; payload detail mengikuti Resource aktual.
- Middleware ↔ Route: `auth`, `auth:sanctum`, dan role groups dicatat dari route files.
- Notification ↔ Roles: `NotificationService` dan `AdminNotification` dicatat.
- Scheduler ↔ Console: 4 command aplikasi telah didaftarkan ke scheduler (`events:update-status` everyMinute, `membership:expire` dailyAt 00:00, `membership:auto-renew` dailyAt 01:00, `notifications:cleanup --days=30` dailyAt 02:00).
- Policy/Enum/Job/Event/Listener: tidak ada class aplikasi yang terdeteksi.

---

## Sinkronisasi 2026-08-19 — Gallery Featured-Only & GDrive Folder Link

Perubahan perilaku & penambahan sejak sinkronisasi sebelumnya:

- **Galeri publik hanya menampilkan gambar featured**: `GalleryService::getAllPublic()` dan
  `getByEvent()` kini menambahkan `->where('is_featured', true)`; `EventResource` (seksi
  `galleries`) menerapkan filter yang sama. Keputusan produk: gambar yang tidak dipilih
  admin (featured) tidak tampil di API publik.
- **URL gambar Google Drive**: `ImageHelper::gdriveThumbUrl()` baru — URL gambar gdrive
  dirender sebagai `https://drive.google.com/thumbnail?id=FILE_ID&sz=w800` (format `uc?id=`
  sudah HTTP 403 sejak Januari 2024). Fallback ke URL mentah bila `google_drive_file_id`
  kosong. Dipakai di `GalleryResource` (url/thumb) dan `EventResource` (galleries).
- **Album galeri — link folder Google Drive**: kolom baru `gallery_albums.gdrive_folder_url`
  (migration `2026_08_19_000001`) + validasi `GalleryAlbumRequest` (wajib URL domain
  `drive.google.com`) + field pada form create/edit + badge "Drive" (link eksternal) pada
  index. Tanpa Google Drive API / OAuth / iframe.
- **Endpoint API publik baru**: `GET /api/v1/gallery-albums` → `GalleryAlbumRepository::allPublic()`
  + `GalleryAlbumResource` (id, event, title, description, cover_image, gdrive_folder_url,
  galleries_count).
- **Tests baru**: `tests/Feature/GalleryApiTest.php` (9 test) dan
  `tests/Feature/Admin/GalleryAlbumAdminTest.php` (7 test) — total 16 test tambahan terkait
  perubahan ini, semuanya PASS di dalam container.

## Sinkronisasi 2026-08-20 — Gallery GDrive Folder Sync

Perubahan perilaku & penambahan sejak sinkronisasi sebelumnya (fitur *gallery gdrive folder sync*):

- **Sync isi folder Google Drive per album**: `GoogleDriveService` baru (integration layer,
  `files.list` via API key `GOOGLE_DRIVE_API_KEY`, pagination merged, timeout 15s + retry,
  error 403/429/5xx/network/malformed dipetakan ke `GoogleDriveApiException` sanitized,
  base URL hardcoded anti-SSRF) + `App\DTOs\DriveFileDTO` (type dari mimeType, guard entry
  tanpa id). Hanya dipanggil dari `GalleryService::syncAlbumFromDrive()` /
  `syncAllDriveAlbums()`.
- **Snapshot idempotent**: upsert by `(gallery_album_id, google_drive_file_id)`
  (`GalleryRepository::updateOrCreateByDriveFile`); stale delete (`deleteStaleDriveFiles`)
  hanya setelah fetch sukses penuh; write phase dibungkus `DB::transaction`; kurasi admin
  (`is_featured`) tidak pernah ditimpa.
- **Race guard**: `Cache::lock('gallery:sync:{albumId}', 300)` per album dengan try/finally
  release — sync manual + scheduler bersamaan → status `skipped`.
- **Kolom sync**: migration `2026_08_20_000001_add_gdrive_sync_columns_to_gallery_albums.php`
  menambah `gallery_albums.last_synced_at` + `gdrive_sync_error` dan composite index
  `(gallery_album_id, google_drive_file_id)` di `galleries`. Error disimpan sanitized
  (tanpa API key/URL), tidak pernah tampil di API publik.
- **Command + scheduler**: `gallery:sync-gdrive` (auto-discover) + schedule hourly
  `withoutOverlapping()` di `bootstrap/app.php`; tombol **Sync Drive** admin
  (`POST /admin/gallery-albums/sync`, roles `admin_full_access,admin_laman,gallery` via
  RoleMiddleware existing).
- **Validasi diperketat (backward-compatible)**: `GalleryAlbumRequest::gdrive_folder_url`
  kini wajib format folder `drive.google.com/drive/folders/...` KECUALI nilai sama dengan
  record existing (album legacy tetap bisa diedit tanpa gagal validasi).
- **Endpoint API publik baru**: `GET /api/v1/gallery-albums/{id}` — detail album berisi SEMUA
  media: image → `thumbnail?id=...&sz=w800`, video → `uc?export=download&id=...&confirm=t`
  (`ImageHelper::gdriveContentUrl()`; `confirm=t` menghindari halaman virus-scan >25MB),
  plus `external_url`. `GET /galleries` & index album tetap featured-only / tanpa array galleries.
- **Tests baru**: `GallerySyncTest` (18), `GoogleDriveServiceTest` (9),
  `GalleryAlbumAdminTest` (+8 = 15), `GalleryApiTest` (+7 = 16) — semuanya PASS di container.
