# 14 — Changelog & Perbaikan

Kumpulan perbaikan dan penambahan terbaru pada sistem SH3 (backend Laravel + frontend Next.js).

## 2026-08-19 — Scan Attendance Admin Mendukung Guest Sponsor

`POST /admin/attendance/scan` kini dapat melakukan check-in **guest sponsor** dari panel admin,
tidak hanya peserta reguler.

- `QRCodeService::isGuestSponsorCode()` — deteksi QR guest sponsor (pola `GS-\d+-\d+-\d{4}`).
- `AttendanceController::processScan()` — meneruskan QR guest sponsor ke
  `processGuestSponsorScan()` (findByQr → cek event match → `GuestSponsorService::checkIn`).
- Response berisi `guest_sponsor=true`, `sponsor_name`, dan `event_title`.
- View `resources/views/attendance/scan.blade.php` — placeholder manual kode QR kini menerima
  format `3950 / NM0001 / GS-1-2-0001`, hasil scan menampilkan nama sponsor, dan badge
  "Check-in guest sponsor berhasil" bila `guest_sponsor=true`.
- **Tests** — `AdminAttendanceScanTest` (+4): check-in sukses, QR tak dikenal (422),
  event mismatch (422), dan duplicate check-in (422).
- `OrganizationMemberResource` — `holder` kini null-safe (hanya dirender bila relasi `participant` ada).
- `.dockerignore` — tambah `public/build`.
- `how-to-run.md` — dirombak: dua cara menjalankan (Docker Compose disarankan, atau manual),
  langkah build/test, dan catatan `APP_URL`.

---

## 2026-08-18 — Modul Guest Sponsor

**Commit:** `203c825` → `5c1ed43` → `c71aa4a` → `c87d117` → `73a92d2`.

### A. Modul Guest Sponsor (akun perwakilan sponsor per event)

Admin dapat membuat akun guest sponsor (username/password + QR unik) dalam kuota per pasangan
(sponsor, event), dengan masa berlaku dan status aktif, untuk attendance via API.

- **Database (4 migrasi)** — role `guest_sponsor` di ENUM `users.role`; kolom
  `event_sponsors.max_guest_accounts` (kuota); tabel `guest_sponsors`; tabel
  `guest_sponsor_attendances` + `guest_sponsor_attendance_logs` (unique index pendek
  `gsa_guest_event_unique`, `gsal_guest_event_type_unique` untuk menghindari batas 64 karakter MySQL).
- **Model** — `GuestSponsor`, `GuestSponsorAttendance`, `GuestSponsorAttendanceLog` +
  `GuestSponsorFactory`; relasi di `User`, `Sponsor`, `Event`.
- **`GuestSponsorRepository`** — paginateSorted, findByUser/Qr, countAll/Active/Expired,
  quota/setQuota (syncWithPivotValues), attendance CRUD, attendanceHistory, quotas.
- **`GuestSponsorService`** — createAccount (cek kuota, auto username `gs_{slug}`, password
  `Str::random(8)` ditampilkan sekali, QR `GS-{sponsor}-{event}-{seq}`), generateUsername/QrCode,
  quota/setQuota, toggleActive, authenticate (cek role + is_active + usable), isUsableForEvent
  (is_active + valid window + event status/end_date), checkIn/checkOut (duplicate & usability),
  scan (QR + event match + status), history.
- **Admin Web (`admin_full_access`)** — index (statistik + tabel kuota + daftar akun),
  create/store, show (detail + QR `QrCode::svg` + edit), update, destroy, toggle-active, quota.
- **API** — `POST /guest-sponsor/auth/login`, `GET /guest-sponsor/auth/me`,
  `POST /guest-sponsor/attendance/scan|check-in|check-out`, `GET /guest-sponsor/attendance/my`.
- **Form Requests** — `GuestSponsorRequest`, `GuestSponsorQuotaRequest`, `GuestSponsorCheckInRequest`.
- **Views** — `guest-sponsors/{index,create,show}` mengikuti aturan responsive (table-wrap,
  card padding, stat-card, pagination).
- **Tests** — `GuestSponsorAdminTest` (12) + `GuestSponsorApiTest` (14).

### B. Breaking — Role `sponsor` kehilangan akses Admin Panel

- Route `admin.sponsors.*` kini hanya `admin_full_access, admin_laman` (sebelumnya menyertakan `sponsor`).
- Menu Sponsors & Dashboard di `config/sidebar.php` tidak lagi menyertakan role `sponsor`.
- Login web (`AuthenticatedSessionController::store`) ditolak untuk role `sponsor` dan `guest_sponsor`
  (keduanya memakai API); dashboard admin kini di-guard `RoleMiddleware`.
- `AdminAccessControlTest` diperbarui: sponsor → 403 pada sponsors/dashboard/guest-sponsors.

### C. Perbaikan setelah QA

- `GuestSponsorRepository::quotas()` mengembalikan key `remaining` (error `Undefined array key`
  pada view index).
- Form tambah akun: dropdown Sponsor & Event hanya menampilkan pasangan yang **sudah punya kuota**,
  dan dropdown Event dependen terhadap Sponsor yang dipilih (`data-sponsor` + JS).
- Padding card pada form create/edit (`p-5 sm:p-6`); jarak banner alert terhadap konten (`mb-6`).

---

## 2026-08-16 — Hash ID Admin, Preview Gambar Event, Role Gallery

> **Riwayat (digantikan 2026-08-17):** kolom "Hash ID" pada panel peserta kini menampilkan
> `participant_code` (member `0001`, non-member `NM0001`). Kolom DB `hash_id` dihapus
> (migration `2026_08_17_000002`), tanpa backward compat.

### A. Hash ID Partisipan di Panel Admin

Tampilkan `hash_id` peserta di panel admin — murni perubahan view, backend sudah menyediakan via accessor `$appends = ['hash_id']` pada model `Participant`.

- **`resources/views/participants/index.blade.php`** — kolom "Hash ID" ditambahkan di tabel daftar peserta (setelah kolom Name). Nilai ditampilkan dalam format `<code>` monospace. Empty-state `colspan` diubah dari 8 → 9.
- **`resources/views/participants/show.blade.php`** — row "Hash ID" ditambahkan di kartu Data Peserta (setelah row Nama). Format `<code>` monospace.

### B. Preview Banner & Gambar Event di Panel Admin

- **`resources/views/events/index.blade.php`** — kolom "Gambar" (thumbnail) ditambahkan di tabel daftar event (antara Title dan Category). Menggunakan `ImageHelper::getUrl($event->image)`. Empty-state `colspan` diubah dari 8 → 9.
- **`resources/views/events/show.blade.php`** — blok media (banner full-width + gambar thumbnail) ditambahkan di kartu Detail Event, sebelum grid info. Banner: `max-height:240px`, gambar: `h-24 w-36`. Keduanya conditional (`@if`). Menggunakan `ImageHelper::getUrl()`.

### C. Role 'gallery' + Akun Admin Gallery

Role baru `gallery` ditambahkan untuk akun yang hanya bisa mengelola Galleries & Albums.

- **`database/migrations/2026_08_16_000001_add_gallery_role_to_users.php`** (baru) — menambahkan `'gallery'` ke ENUM role users via `ALTER TABLE`. Guard `mysql` driver. Rollback menghapus `'gallery'`.
- **`app/Http/Requests/UserRequest.php`** — `'gallery'` ditambahkan ke `Rule::in([...])`.
- **`resources/views/users/create.blade.php`** & **`edit.blade.php`** — `'gallery'` ditambahkan ke array dropdown role.
- **`routes/web.php`** — grup route galleries (`admin.galleries.*` + `admin.gallery-albums.*`) dipisah dari grup categories/organization. Middleware gallery: `admin_full_access,admin_laman,gallery`.
- **`config/sidebar.php`** — `'gallery'` ditambahkan ke roles menu Dashboard, Galleries, dan Albums.
- **`database/seeders/UserSeeder.php`** — entry `Admin Gallery` (`admin.gallery@sh3.com` / `password` / role `gallery`).
- **`README.md`** — baris kredensial Admin Gallery ditambahkan di tabel Default Credentials.

---

## 2026-08-15 — Sync-Up OTS (Payment + Attendance), Re-Registration Flow, Sortable Tables, Gallery Album & Seeder Import

### A. Sync-Up OTS: Sinkronisasi Offline Lengkap (Payment + Attendance + Dedup)

**Commit:** `a445031` → `b72e698` → `9be35b7` — `app/Http/Controllers/API/AttendanceController.php` dirombak besar-besaran.

#### Route Publik (bukan lagi auth-only)

- **`routes/api.php`** — `POST /api/v1/attendance/sync-up` dan `GET /api/v1/attendance/sync-down`
  dipindahkan dari grup `auth:sanctum` ke grup **publik** (sebelumnya hanya bisa dipanggil
  dengan token; device OTS offline tidak memiliki token). Kini bisa dipanggil tanpa autentikasi.

#### Payload `sync-up`

Request `POST /api/v1/attendance/sync-up` kini menerima **dua array**:

```json
{
  "attendances": [
    {
      "event_id": 1,
      "participant_code": "0001",
      "check_in_time": "2026-08-17 07:30:00",
      "check_out_time": null
    }
  ],
  "ots_registrations": [
    {
      "event_id": 1,
      "participant_code": "NM0001",
      "member_name": "Budi",
      "check_in_time": "2026-08-17 08:00:00",
      "check_out_time": null
    }
  ]
}
```

#### Alur proses `sync-up`

1. **Attendance reguler** (`attendances[]`):
   - `EventParticipant` dicari via `event_id` + peserta dengan `participant_code` yang sama
   (`whereHas('participant', participant_code)`). Payload tanpa `participant_code`
   (format lama) **di-abaikan diam-diam** (`synced_attendance_count` 0).
   - `event_participants` di-update: `check_in_at`, `check_out_at`, `is_attended = true`.
   - `attendances` di-*update* bila sudah ada (tanpa duplikasi), atau dibuat bila belum ada.
   - Jika attendance sudah punya `check_out_time`, hanya `check_in_time` + `status=present`
     yang di-update (mencegah menimpa waktu keluar yang sudah tercatat).
2. **OTS member** (`ots_registrations[]`):
   - Participant aggregator sentinel `NM0000` (`Participant::OTS_AGGREGATOR_CODE`, `firstOrCreate`,
     `name = 'Manual OTS NON MEMBER'`, `email = manual.ots@sh3.com`) dibuat otomatis untuk OTS manual.
   - OTS member: participant dicari/dibuat via `participant_code`.
   - **Satu `EventParticipant` per participant per event** (`firstOrCreate` dengan
     `qr_code = OTS-{participant_code}EV{event_id}`, `registration_type = paid`, `payment_status = confirmed`).
   - `total_events_participated` di-increment **hanya sekali per event** (tracking `$processedEvents`).
   - **Payment dibuat otomatis** untuk setiap scan OTS: `INV-OTS-{random8}`, tipe
     `event_registration`, method `cash`, status `confirmed`, terhubung polymorphic ke
     `EventParticipant`.
   - Attendance di-*update* bila sudah ada (dedup), dibuat bila belum, dengan
     `notes = 'Manual OTS: ...'` atau `'OTS Member: ...'`.
   - `event_participants.check_in_at/check_out_at` di-sinkronkan ulang.

#### Fix OTS dedup & re-registration (`9be35b7`)

- **`app/Services/EventService.php`** — re-registrasi sekarang **memperbarui** `EventParticipant`
  yang sudah ada (reset `is_attended=false`, `check_in_at/out_at=null`, `payment_id`,
  `registration_type`, `amount`, `payment_status`) dan **menghapus attendance lama**
  (`$registration->attendance()->delete()`) alih-alih menolak/duplikasi.
- `total_events_participated` hanya di-increment pada **registrasi pertama** (bila `$existing` null).
- Keanggotaan gratis kini memakai hasil terstruktur `membershipService->checkEligibility()`
  (`$eligibility['is_eligible']`), bukan hitungan manual.
- **`app/Models/EventParticipant.php`** — method baru `markAsRejected()`.
- **`app/Services/PaymentService.php`** — `rejectPayment()` dibungkus `DB::transaction` dan
  memanggil `$paymentable->markAsRejected()` bila method ada (status `event_participants`
  kini ikut berubah menjadi `rejected` saat pembayaran ditolak).
- **`app/Http/Controllers/API/EventController.php`** — pengecekan `payment_method`/`payment_proof`
  didasarkan pada `$registration->amount > 0` (bukan `$event->price`), sehingga re-registrasi
  membership-free tidak memaksa upload bukti bayar. `orderStatus()` memperbaiki status
  `rejected` (bukan lagi `cancelled`) dan `is_membership_free`.

#### Verifikasi

- `tests/Feature/AttendanceApiTest.php` (+116 baris) dan `tests/Feature/EventApiTest.php`
  (baru, +384 baris) menambah cakupan alur attendance dan re-registration.

### B. Sortable Tables (Kolom Tabel Bisa Diurutkan) — Admin

Semua tabel index admin kini mendukung **sorting kolom** lewat query string
`?sort={kolom}&direction={asc|desc}` tanpa reload halaman.

#### Komponen Baru

- **`app/Support/Sort.php`** (baru) — helper statis:
  - `resolve(array $allowed, string $default, string $defaultDirection)` — membaca `sort` &
    `direction` dari request, memvalidasi terhadap whitelist kolom, fallback ke default.
  - `apply(Builder $query, array $allowed, ...)` — menerapkan `orderBy` dengan aman.
  - `active($column)`, `nextDirection($column)`, `isAsc($column)`, `url($column)` —
    membangun URL sort yang mempertahankan query string lain (search/filter) sambil toggle arah.
  - `directionLabel($column)` — label aksesibilitas.
- **`resources/views/components/th-sort.blade.php`** (baru) — Blade component:
  ```blade
  <x-th-sort column="name">Name</x-th-sort>
  ```
  Merender link sort dengan ikon panah atas/bawah; class aktif `active asc/desc` diset
  otomatis. Tanpa `column` → render teks polos.
- **`resources/css/app.css`** — class `.th-sort`, `.th-sort-icon`, `.th-sort.active`,
  `.th-sort.asc/.desc` (Tailwind `@layer components`).

#### Repository (BaseRepository + per-modul)

- **`app/Repositories/BaseRepository.php`** — method baru:
  - `allSorted(array $allowed, string $default = 'id', string $defaultDirection = 'asc', array $relations = [])`
  - `paginateSorted(array $allowed, int $perPage = 15, array $relations = [], string $default = 'created_at', string $defaultDirection = 'desc')`
  (keduanya memakai `Sort::apply` + `->withQueryString()` pada paginate).
- Repository lain memakai `Sort::apply` langsung: `AttendanceRepository` (check_in_time),
  `EventRepository` (created_at), `MembershipHistoryRepository`, `MembershipPlanRepository`
  (sort_order), `MerchandiseRepository`, `ParticipantRepository`, `PaymentRepository`,
  `GalleryAlbumRepository`.

#### Controller & View yang Diperbarui

- `Admin\CategoryController` → `allSorted(['name','slug','distance_km','sort_order','is_active'], 'sort_order', 'asc')`
- `Admin\GalleryController` → `paginateSorted(['title','type','is_featured','created_at'], 15, ['event'])`
- `Admin\OrganizationController` → `allSorted([...], 'sort_order', 'asc')`
- `Admin\SponsorController` → `allSorted([...], 'name', 'asc')`
- `Admin\UserController` → `paginateSorted(['name','email','role','is_active','last_login','created_at'], 15)`
- View index: `attendance`, `categories`, `events`, `galleries`, `membership_plans`,
  `memberships`, `merchandise`, `organizations`, `participants`, `payments`, `sponsors`,
  `users`, `gallery-albums` — `<th>` diganti `<x-th-sort column="...">`.

### C. Gallery Album Module (CRUD Admin)

Modul baru untuk mengelola **album galeri** (mengelompokkan gallery per event).

- **`app/Http/Controllers/Admin/GalleryAlbumController.php`** (baru) — index, create, store,
  edit, update, destroy; upload cover via `ImageHelper::upload(..., 'albums')`, hapus cover lama,
  dan `logActivity()` (create_album/update_album/delete_album).
- **`app/Repositories/GalleryAlbumRepository.php`** (baru) — `paginateWithRelations(15)`
  dengan relasi `event`, `withCount('galleries')`, dan `Sort::apply`.
- **`app/Http/Requests/GalleryAlbumRequest.php`** (baru) — `event_id` (nullable, exists),
  `title` (required), `description` (nullable), `cover_image` (image, max 4096).
- **`resources/views/gallery-albums/`** (baru) — `index.blade.php`, `create.blade.php`, `edit.blade.php`.
- **`routes/web.php`** — `Route::resource('gallery-albums', ...)` dalam grup role
  `admin_full_access,admin_laman`.
- **`config/sidebar.php`** — item menu "Albums" (ikon album) di bawah Gallery,
  aktif untuk `admin.gallery-albums.*`.
- **`app/Models/GalleryAlbum.php`** — model sudah ada; relasi `event()` dan `galleries()`.

> Tabel `gallery_albums` sudah ada sejak awal (migration `2024_01_01_000012`). Modul ini
> menambahkan antarmuka admin CRUD-nya. Detail lengkap: `docs/19 — Gallery Album Module.md`.

### D. Fix Checkbox `is_free_for_members` (Hidden Value)

- **`resources/views/events/create.blade.php`** & **`edit.blade.php`** — ditambahkan
  `<input type="hidden" name="is_free_for_members" value="0">` sebelum checkbox.
  Sebelumnya, bila checkbox tidak dicentang, field tidak terkirim → Laravel menganggap
  `false`/default `true` (bila kolom `boolean` default true). Kini unchecked selalu mengirim `0`.

### E. Seeder Import Peserta SH3

> **Riwayat (digantikan 2026-08-17):** seeder kini keyed on `participant_code` (kolom `hash_id` dihapus).

- **`database/seeders/Sh3ParticipantImportSeeder.php`** (baru) — import peserta SH3 dari data
  spreadsheet ke tabel `participants` + `users` (role participant). **Idempotent** (keyed on
  `hash_id`), bisa dijalankan ulang tanpa duplikat:
  ```bash
  php artisan db:seed --class=Sh3ParticipantImportSeeder
  ```
  - Membuat `User` (username + password hashed) untuk login participant API.
  - Menangani email kosong → dummy (`dummy+{hash_id}@example.com`), email duplikat → dummy,
    username invalid → normalisasi, username bentrok → suffix `_1`, `_2`, dst.
  - Menghasilkan summary & laporan conflict/error.
- **`tests/Feature/Sh3ParticipantImportTest.php`** (baru) — 19 peserta terimport, password
  hashed & login bekerja, idempotent pada re-run, dummy email, normalisasi username.

### F. Test Suite Admin (baru)

- **`tests/Feature/Admin/`** (baru, 7 file, ±957 baris):
  - `AdminAuthTest.php` — login web admin (guest redirect, login valid/invalid, logout).
  - `AdminAccessControlTest.php` — matrix role per halaman (dashboard, users, membership
    plans, participants, memberships, events, categories, galleries, organization, sponsors,
    merchandise, payments, attendance).
  - `AdminDashboardTest.php`, `AdminParticipantTest.php`, `AdminMembershipTest.php`,
    `AdminMembershipPlanTest.php`, `AdminUserManagementTest.php` — CRUD & akses halaman admin.

---

## 2026-08-14 — Deploy Produksi: Fix Sync-Down, Payments History, Participants, Reverb TLS & PHP 8.3

### Perubahan Kode

- **`app/Repositories/AttendanceRepository.php`** — fix sinkronisasi `sync-down`:
  Parameter `since` (ISO UTC dari client) kini di-parse dan dikonversi ke timezone aplikasi
  (`Carbon::parse(...)->timezone(config('app.timezone'))`) sebelum dibandingkan dengan `updated_at`
  yang tersimpan dalam timezone lokal (Asia/Jakarta). Sebelumnya selisih 7 jam menyebabkan
  data yang sudah ter-sync tetap terkirim ulang.
- **`routes/api.php`** — fix route `/api/v1/payments/history` tertelan oleh `/payments/{id}`:
  Route `history` dipindah ke atas route `{id}` dan `{id}` diberi constrain `->whereNumber('id')`.
  Sebelumnya `GET /payments/history` memanggil `PaymentController::show()` dengan `id='history'`
  → `TypeError` (500).
- **`app/Http/Controllers/API/ParticipantController.php`** — fix `paginate(['user'])`:
  Argumen pertama `paginate()` adalah `int $perPage`, bukan array relasi.
  Diubah menjadi `paginate(15, ['user'])` → `GET /api/v1/participants` tidak lagi 500.
- **`app/Http/Requests/ParticipantRequest.php`** — fix validasi unique email saat update:
  Route param bernama `{id}` (bukan `{participant}`), sehingga `Route::unique('participants')->ignore()`
  gagal dan email sendiri dianggap "already taken". Kini `$participantId` diambil dari `route('participant')`
  atau `route('id')`.
- **`config/reverb.php`** — Reverb kini dapat berjalan **secure (WSS/HTTPS langsung di port 8080)**
  memakai sertifikat Let's Encrypt: TLS diisi dari `REVERB_TLS_CERT` / `REVERB_TLS_KEY`
  (default `/etc/reverb/fullchain.pem` & `/etc/reverb/privkey.pem`). Sesuai `.env`
  `REVERB_SCHEME=https`. Sebelumnya `'tls' => []` (plain HTTP) sehingga broadcast dari PHP
  ke `https://server-sh3.cloud:8080` gagal (`SSL connection timeout`).

### Infrastruktur / Konfigurasi Server

- Project di-deploy pada **PHP 8.3** (branch `prod`). Branch `main` memerlukan PHP 8.4.
- Sertifikat TLS untuk Reverb disalin ke `/etc/reverb/` (ownership `www-data`, `privkey.pem` mode `600`)
  karena `/etc/letsencrypt` tidak dapat dibaca oleh user `www-data` (Reverb dijalankan sebagai `www-data`).
- `phpunit.xml` disesuaikan ke database lokal (`127.0.0.1`, user `sh3_user`, DB `db_server_new_test`)
  menggantikan nilai Docker (`DB_HOST=mysql`, root) yang tidak tersedia di server.

### Verifikasi

- `php artisan test` → **164/164 PASS** (520 assertions).
- API publik & autentikasi (auth, events, membership, payments, participants, merchandise,
  attendance, notifications, organization, galleries) → 200 OK di `https://server-sh3.cloud`.
- Broadcast real-time berhasil dikirim melalui **WSS/HTTPS** ke Reverb (handshake WebSocket 101).
- Panel admin (`/admin/*`) login via email dan seluruh halaman 200.
- Upload foto profil & QR scan attendance berfungsi.

---

## 2026-08-14 — Participant Forgot/Reset Password (Tanpa Email / Pihak Ketiga)

> **Riwayat (digantikan 2026-08-17):** verifikasi kini memakai **Username + `participant_code`**
> (member `0001` / non-member `NM0001`). Lihat `docs/18 — Participant Password Reset.md`.

Fitur reset password khusus **Participant** tanpa email / OTP / SMS / WA / pihak ke-3.
Verifikasi hanya **Username + Hash ID** (kode peserta). Terpisah dari reset password Admin.

### Endpoint (publik, via API)
| Method | Path                                        | Keterangan                                      |
|--------|---------------------------------------------|-------------------------------------------------|
| POST   | `/api/v1/participant/auth/verify-reset`     | Verifikasi username + hash_id                   |
| POST   | `/api/v1/participant/auth/reset-password`   | Reset password (throttle 5/15 mnt per IP)       |

### File
- `app/Http/Controllers/API/ParticipantAuthController.php` (baru)
- `app/Services/ParticipantPasswordResetService.php` (baru)
- `app/Http/Requests/ParticipantVerifyResetRequest.php`, `ParticipantResetPasswordRequest.php` (baru)
- `routes/api.php` — 2 route publik (`reset-password` + `throttle:5,15`)
- `app/Models/Participant.php` — `hash_id` kini kolom unik (kode peserta `SH3XXXXXXX`), auto-generate + migrasi backfill
- `database/migrations/2026_08_14_000100_add_hash_id_to_participants_table.php` (baru)
- `tests/Feature/ParticipantPasswordResetTest.php` (baru, 10 kasus) — **PASS**

### Keamanan
- `Hash::make()`; token participant di-revoke setelah reset.
- Hanya pemilik kombinasi username + hash_id yang bisa reset; peserta lain / Admin ditolak dengan pesan generik.
- `hash_id` peserta lain tidak dibocorkan.
- Rate limit 5 / 15 mnt / IP pada `reset-password`.

### Audit Log
- `user_activity_logs`: `action = 'Participant Password Reset'`, `details = {participant_id, username}`, `ip_address`, `user_agent`.

### Dokumentasi
- `docs/18 — Participant Password Reset.md` (baru).

---

## 2026-08-06 — Dedicated Participant Authentication (Username-based)

### Latar Belakang

Sistem otentikasi sebelumnya menggunakan **shared `LoginRequest`** untuk kedua jalur login
(web admin via session dan API participant via Sanctum), sehingga:

- Form login web admin mengirimkan `email`, tetapi `LoginRequest` menvalidasi `username` → error "The username field is required."
- API login tersedia untuk **semua peran** termasuk admin, bukan hanya participant.

### Perubahan

#### Auth Request yang Dipisahkan

| Jalur | Request Class | Field | Tujuan |
|-------|--------------|-------|--------|
| **API participant** | `LoginRequest` | `username` + `password` | Login peserta via `POST /api/v1/auth/login` |
| **Web admin** | `EmailLoginRequest` (baru) | `email` + `password` | Login admin via `POST /login` |

- **`app/Http/Requests/LoginRequest.php`** — divalidasi `username` (required, string) + `password` (required, string).
  Digunakan eksklusif oleh `AuthController::login()` (API participant).
- **`app/Http/Requests/EmailLoginRequest.php`** — baru; divalidasi `email` (required, email) + `password`.
  Digunakan oleh `AuthenticatedSessionController` (web admin).

#### Controller & Service

- **`app/Http/Controllers/Auth/AuthenticatedSessionController.php`** — beralih ke `EmailLoginRequest`.
  `Auth::attempt()` menerima `['email', 'password']` yang kompatibel dengan provider Eloquent default.
- **`app/Services/AuthService.php::login()`** — lookup user **hanya** via `username`
  (`UserRepository::findByUsername()`), **melepahkan fallback ke `email`**.
  Menambahkan pengecekan peran: hanya user dengan `role = participant` yang boleh login via API.
  Admin yang mencoba login via API akan mendapat error "Akun ini bukan peserta."

#### Registrasi

- **`app/Http/Requests/RegisterRequest.php`** — menambahkan field opsional `username`
  (3–30 karakter, alfanumerik + underscore, unique).
- **`app/Http/Controllers/API/AuthController::register()`** — `username` dibuat otomatis
  via `generateUsername()` jika tidak disertakan. User yang terdaftar selalu memiliki
  `role = participant`.
- **`database/seeders/ParticipantSeeder.php`** — setiap peserta seed juga membuat
  `User` record (role `participant`, username otomatis, password `password`).

#### Factory & Seeder

- **`database/factories/UserFactory.php`** — menambahkan `username` (unique), `role` (default `participant`).
  Menambahkan factory state `admin()` untuk user admin.
- **`database/seeders/UserSeeder.php`** — menambahkan `username` pada semua admin (mis. `admin_full`, `organizer`).
- **`database/migrations/2026_08_05_000001_add_username_to_users_table.php`** — migrasi direkonstruksi
  (file hilang dari repositori tapi terdaftar di tabel `migrations`); menambahkan kolom
  `username VARCHAR(30) UNIQUE` pada tabel `users` untuk fresh install.

#### Resource

- **`app/Http/Resources/UserResource.php`** — menambahkan field `username`.

#### Repository

- **`app/Repositories/UserRepository.php`** — memastikan `findByUsername()` tersedia.

#### Frontend (Next.js)

- `frontend-sh3/test_api_correct.js`, `test_api_fixed.js`, `test_frontend_api.js` — diperbarui
  untuk menggunakan `username` pada request login.

### Verifikasi

- Participant login via API dengan `username`: **200 OK** + token.
- Admin login via API dengan `username`: **422** — "Akun ini bukan peserta."
- Admin login via web dengan `email`: **302 redirect ke /admin/dashboard**.
- Registrasi otomatis generate `username` jika tidak disertakan.
- Semua test di `AuthApiTest` (18 test) **pass**, termasuk test baru untuk
  penolakan login admin via API dan login dengan username.

### Dokumentasi

- `docs/01 — Arsitektur & Authentication.md` — memperbarui deskripsi alur login.
- `docs/02 — Participant Module.md` — menambahkan spesifikasi login participant.
- `docs/11 — User Management Module.md` — memperbarui tabel API auth.
- `docs/17 — Implementation Sync.md` — memperbarui contoh request/response login.
- `docs/readme.md` — memperbarui API Endpoints.
- `README.md` — memperbarui API Endpoints & default credentials.

---

## 2026-08-04 — Sinkronisasi Implementasi & Dokumentasi

### Scheduler & Console Commands

- **app/Console/Commands/** (4 file baru):
  - `UpdateEventStatus.php` — `events:update-status`, transisi event draft→publish→ongoing→completed berdasarkan tanggal.
  - `MembershipExpiration.php` — `membership:expire`, menandai membership histories yang expired (daily 00:00).
  - `MembershipAutoRenewal.php` — `membership:auto-renew`, auto-renew participants dengan membership akan kedaluwarsa dalam 7 hari (daily 01:00).
  - `NotificationCleanup.php` — `notifications:cleanup --days=30`, hapus notifikasi lebih dari 30 hari (daily 02:00).
- **bootstrap/app.php** — menambahkan `->withSchedule(...)` dengan 4 jadwal task.

### Notification

- **app/Services/NotificationService.php::notifyAdmins()** — memperbaiki daftar role admin dengan menambahkan `sponsor` dan `merchandise` (sebelumnya hanya sampai `bendahara`).

### Event — Cancel Flow

- **app/Models/Event.php** — menambahkan konstanta status: `STATUS_DRAFT`, `STATUS_PUBLISH`, `STATUS_ONGOING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`.
- **app/Services/EventService.php**:
  - Method baru `cancelEvent(Event $event)` — membatalkan event (validasi tidak cancelled/ongoing/completed), mengirim notifikasi ke admin.
  - `updateEventStatus()` — menggunakan konstanta Event, bukan magic string.
  - `publishEvent()` — menggunakan konstanta Event.
  - `registerEvent()` — validasi baru: cek `registration_start_date` (belum dibuka) dan `registration_end_date` (sudah ditutup) dengan pengecualian validation.
- **app/Http/Controllers/API/EventController.php** — method baru `cancel(int $id)`.
- **app/Http/Controllers/Admin/EventController.php**:
  - `publish()` — memakai `EventService::publishEvent()` (dengan error handling validation).
  - Method baru `cancel(int $id)`.
- **routes/api.php** — route baru `POST /api/v1/events/{id}/cancel`.
- **routes/web.php** — route baru `POST admin/events/{id}/cancel`.

### Activity Logging

- **app/Http/Controllers/Admin/UserController.php** — menambahkan `logActivity()` pada store, update, destroy, toggleActive.
- **app/Http/Controllers/Admin/EventController.php** — menambahkan `logActivity()` pada store, update, destroy, publish, cancel.
- **app/Http/Controllers/Admin/CategoryController.php** — menambahkan `logActivity()` pada store, update, destroy.
- **app/Http/Controllers/Admin/ParticipantController.php** — menambahkan `logActivity()` pada store, update, destroy.

### Dokumentasi

- **docs/17 — Implementation Sync.md** — memperbarui tabel inventory (Console Commands: 4, Scheduler: 4), bagian Notifications, dan Known Limitations.

---

## 2026-08-03 — Fix Klik Notifikasi Admin Terlempar ke Login (Host Mismatch)

### Masalah

- Saat admin mengklik notifikasi di `/admin/notifications`, browser selalu di-redirect ke `/login`.
- Bukan karena role/permission (RoleMiddleware hanya `abort(403)` bila role tidak punya akses),
  melainkan karena **mismatch host** antara cookie session dan URL tujuan notifikasi.
- Notifikasi menyimpan URL absolut yang digenerate via `route('admin.*')` saat pembuatan
  (mis. `app/Services/EventService.php`, `app/Services/PaymentService.php`), berdasar `APP_URL=http://localhost:8000`.
- Admin login di `http://127.0.0.1:8000` dengan `SESSION_DOMAIN=null` → cookie session terikat host `127.0.0.1`.
  Saat klik notifikasi, browser berpindah ke `http://localhost:8000/admin/...` (host berbeda),
  cookie `127.0.0.1` tidak ikut terkirim → sesi dianggap belum login → redirect ke `/login`.

### Perbaikan

- Pastikan **host yang dipakai konsisten** antara `APP_URL` dan host login di browser.
- **Opsi A (pakai `127.0.0.1`):** ubah `.env` → `APP_URL=http://127.0.0.1:8000`, jalankan
  `php artisan config:clear`, hapus cookie/login ulang, lalu login via `127.0.0.1:8000`.
- **Opsi B (pakai `localhost`):** selalu buka admin di `http://localhost:8000/admin`, bukan `127.0.0.1`;
  hapus cookie `127.0.0.1` di browser.

> Catatan: di produksi `APP_URL` harus domain HTTPS yang sama dengan domain login agar URL storage,
> route, dan sesi tetap konsisten (lihat juga bug upload gambar).

---

## 2026-08-03 — Fix Upload Gambar Tidak Tampil (Broken `storage:link` Symlink)

### Masalah

- Semua gambar yang di-upload (backend `payment_proof`, frontend foto profil user/avatar)
  tidak bisa ditampilkan — browser menerima HTTP 404 untuk semua URL `/storage/...`.
- Akar penyebab: symlink `public/storage` sebelumnya mengarah ke `storage/app\public`
  (backslash `\` alih-alih path separator), sehingga target symlink tidak ditemukan di Linux.
- Baik Blade views (`asset('storage/' . $path)`) maupun API frontend (`ImageHelper::getUrl()` /
  `Storage::disk('public')->url()`) menghasilkan URL yang melewati symlink `public/storage`
  yang broken — semua request gambar 404.
- Selain itu, route `/` secara unconditional redirect ke `/login`, menyebabkan loop redirect
  bagi authenticated user yang mengakses `/login` secara langsung.

### Perbaikan

- **`public/storage`** — symlink direcreate ulang via `php artisan storage:link`, sekarang
  mengarah ke `storage/app/public` dengan benar.
- **`routes/web.php`** — route `/` sekarang memeriksa status autentikasi:
  - authenticated → redirect ke `/admin/dashboard`
  - unauthenticated → redirect ke `/login`
  (sebelumnya unconditional redirect ke `/login` yang menyebabkan loop bagi user yang sudah login)

### Verifikasi

- `curl -I http://127.0.0.1:8000/storage/payments/<file>` → HTTP 200
- `curl -I http://127.0.0.1:8000/storage/avatars/<file>` → HTTP 200
- Frontend (`http://127.0.0.1:3000`) juga berhasil memuat gambar via API URL yang sama.

---

---

## 2026-08-02 — Fix Upload Gambar Event (403 Forbidden)

### Masalah

- Data event yang dibuat/brownfields menyimpan **temp path upload** (`/tmp/php...`) pada kolom `image` dan `banner`,
  bukan path public disk.
- Akibatnya URL `Storage::disk('public')->url()` menghasilkan `${APP_URL}/storage/tmp/php...` yang tidak ada di disk,
  sehingga browser & Next.js `/optimizer` (Image Optimization) menerima kode **HTTP 403 (Forbidden)**.

### Perbaikan (Backend — Admin Event)

- **`Admin\EventController::store()`** — kini memanggil `ImageHelper::upload($request->file($field), 'events')`
  untuk `image` & `banner` sebelum disimpan (sebelumnya dilewatkan mentah sebagai `UploadedFile`, menyimpan temp path).
- **`Admin\EventController::update()`** — menghapus file lama via `ImageHelper::delete()` jika field di-upload ulang,
  lalu menyimpan file baru ke public disk.
- **`Admin\EventController::destroy()`** — menghapus file `image` & `banner` saat event dihapus.
- Pola sinkron dengan `Admin\GalleryController`.

### Perbaikan Data
- Kolom `image` & `banner` record event `id=43` yang menunjuk ke file temp yang sudah tidak ada dikosongkan (`NULL`)
  untuk menghentikan HTTP 403 pada halaman depan.
- Untuk memulihkan gambar event ini, upload ulang `image`/`banner` lewat admin (kini tersimpan dengan benar).

---

## 2026-08-01 — Perbaikan API, Galeri, Register Member & Scan QR

### API — Public

| Endpoint | Perubahan |
|----------|-----------|
| `GET /events/{id}/participants` | Dipindah dari grup auth ke **publik**; daftar peserta event bisa dilihat tanpa token. QR codes tetap di-gate (`/events/{id}/qr`). |
| `GET /galleries` | **Baru** — `GalleryController` (publik). Semua foto `type=image` + URL penuh + thumb + info event/album. |
| `GET /categories` | **Baru** — `CategoryController` (publik). Kategori aktif + `events_count`. |
| `GET /organization/years` | **Baru** — daftar tahun periode organisasi (`OrganizationMemberRepository::years()`). |
| `GET /my-events` | **Baru** (auth) — event yang diikuti user + status order (`EventController::myEvents()`). |

### API — Response/Resource

> **Riwayat (digantikan 2026-08-17):** `ParticipantResource` kini meng-expose
> `participant_code`, bukan `hash_id`.

- **`EventResource`**: menambahkan `image_url`, `banner_url`, `registered_count` (hitung `eventParticipants` pending+confirmed), `creator` (id+nama), dan `galleries` (array URL foto).
- **`ParticipantResource`**: menambahkan `hash_id`.
- **`MerchandiseResource`** & **`SponsorResource`**: resource baru — response `index`/`show` merchandise dan sponsor kini terstruktur.
- **`Participant::hash_id`** (accessor + `$appends`): member → `%04d` (mis. `0022`), non-member → `NM-%04d` (mis. `NM-0044`). Logika: `isMembershipActive()`.

### Event

- `EventRepository::findPublic()` — `GET /events` kini menampilkan status `publish` + `ongoing` + `completed` (draft disembunyikan).
- `EventController::show()` memuat relasi `galleries`.
- `EventService::updateEventStatus()` — diperbaiki dari akses `$this->eventRepository->model` (protected) menjadi `Event::query()`; transisi `publish → ongoing → completed` kini berfungsi.
- `EventParticipant::markAsPaid()` — ditambahkan agar `PaymentService::confirmPayment()` (yang memanggil `$payment->paymentable->markAsPaid()`) tidak error rollback untuk pendaftaran event. Status `payment_status` → `confirmed`.

### Member / Register

- `RegisterRequest` menerima `password` (`nullable|string|min:6`) & `password_confirmation` (`same:password`).
- `AuthController::register()` — memakai `$data['password']` yang dikirim user (fallback `Str::random(60)` jika kosong).
- Form **Registrasi Member** (`members/register`) diselaraskan dengan form **Data Diri** (`members/detail`):
  - Label `Full Name` → `Nama Lengkap`.
  - Urutan field disesuaikan: ... Telepon → **Gender** → **Tanggal Lahir** → Golongan Darah.
  - `gender`, `blood_type`, `emergency_contact`, `emergency_phone`, `medical_conditions` → **opsional** (hapus `required` / `*`), konsisten dengan backend yang nullable.
  - Validasi client tetap mewajibkan `name`, `email`, `phone`, `date_of_birth`.

### Admin — Participant Detail

- `participants/show.blade.php` menambahkan tampilan: **Foto Profil** (dari `user.avatar`), **Emergency Contact**, **Emergency Phone**, **Medical Conditions** — sehingga data yang tampil sesuai dengan yang diinput via form frontend.

### Admin — Scan QR Attendance

`attendance/scan.blade.php` — scanner lebih responsif & akurat untuk QR dari foto/screen:

- `fps`: 10 → 20 (frame diproses 2x lebih cepat).
- `qrbox`: dinamis 80% dari ukuran viewfinder (tidak harus pas di tengah).
- `experimentalFeatures.useBarCodeDetectorIfSupported: true` — memakai native `BarcodeDetector` browser (lebih cepat & akurat) dengan fallback JS decoder.
- `videoConstraints`: `facingMode: environment`, 1280x720.
- Cooldown antar-scan: 3000 ms → 1500 ms.

### Merchandise & Organisasi

- `MerchandiseService::createOrder()` — validasi size hanya dijalankan jika `$merchandise->size_options` terisi (mencegah error saat kosong).
- `OrganizationMemberRepository::tree()` menambahkan `period_start` & `period_end` pada node pohon organisasi.

### Frontend (Next.js)

- Halaman **detail event** (`/events/finished` & `/events/upcoming`) menampilkan seksi **Galeri** di bagian bawah (komponen `EventGallery`) dengan lightbox & navigasi.
- Halaman **galeri publik** (`/gallery`) terhubung ke `GET /galleries` (`galleryService.js`).
- Fix SVG Footer (`height="inherit"` → `height="137"`).
