# SH3 Event Management System

Sistem manajemen event untuk komunitas lari SH3. Dibangun dengan Laravel 13 dan AdminLTE 3.

## Persyaratan

- PHP ^8.3
- Composer
- MySQL / MariaDB (atau SQLite untuk development)
- Redis (untuk session, cache, dan queue)
- Node.js & NPM (untuk frontend assets)
- Extension PHP: `BCMath`, `Ctype`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PDO`, `Tokenizer`, `XML`, `GD` atau `Imagick`, `redis`

## Instalasi

```bash
# 1. Clone repository
git clone <repo-url> sh3-server
cd sh3-server

# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Konfigurasi database di .env
#    Sesuaikan DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# DB_CONNECTION=mysql
# DB_DATABASE=db_server_new
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Buat database (jika belum ada)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_server_new"

# 8. Pastikan Redis sudah berjalan
redis-cli ping
# Harus response: PONG

# 9. Jalankan migrasi dan seeder
php artisan migrate --seed

# 10. Buat storage symlink
php artisan storage:link

# 11. Build frontend assets
npm run build

# 12. Jalankan development server
php artisan serve
```

## Default Credentials (Seeder)

| Role | Email | Password |
|------|-------|----------|
| Admin Full Access | admin.full@sh3.com | password |
| Admin Laman | admin.laman@sh3.com | password |
| Admin Member | admin.member@sh3.com | password |
| Admin BNH | admin.bnh@sh3.com | password |
| Organizer | organizer@sh3.com | password |
| Bendahara | bendahara@sh3.com | password |
| Sponsor | sponsor@sh3.com | password |
| Merchandise | merchandise@sh3.com | password |

Login di `/login`.

## Struktur

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/       # Web admin controllers
│   │   └── API/         # REST API controllers
│   └── Requests/        # Form request validation
├── Helpers/             # ImageHelper, QRCodeHelper
├── Models/
├── Repositories/        # Repository pattern
└── Services/            # Business logic layer
routes/
├── api.php              # API routes (prefix: /api/v1)
├── web.php              # Admin web routes (prefix: /admin)
└── auth.php             # Login/logout routes
frontend-sh3/            # Frontend publik (Next.js 16, React 19, Tailwind v4)
```

## Frontend Publik (Next.js)

Frontend situs publik (landing page, daftar event, register member, galeri, merchandise) berada di `frontend-sh3/`.

```bash
cd frontend-sh3
npm install
cp .env.example .env   # sesuaikan NEXT_PUBLIC_API_URL & NEXT_PUBLIC_BASE_ASSET_URL
npm run dev            # http://localhost:3000
```

Konfigurasi env frontend:

| Variable | Nilai | Keterangan |
|----------|-------|------------|
| `NEXT_PUBLIC_API_URL` | `http://localhost:8000/api/v1` | Base URL API backend |
| `NEXT_PUBLIC_BASE_ASSET_URL` | `http://localhost:8000/storage` | Base URL file storage (foto, galeri) |

## API Endpoints

Semua endpoint API berada di prefix `/api/v1`.

### Public (tanpa auth)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/register` | Registrasi peserta baru (password opsional, wajib jika lewat frontend register) |
| POST | `/auth/login` | Login (email + password) |
| GET | `/events/upcoming` | Event mendatang |
| GET | `/events` | Semua event (publish + ongoing + completed, tanpa draft) |
| GET | `/events/{id}` | Detail event (termasuk `galleries`, `sponsors`, `registered_count`, `creator`) |
| GET | `/events/{id}/participants` | Daftar peserta event (publik) |
| GET | `/categories` | Daftar kategori event + jumlah event |
| GET | `/galleries` | Semua foto galeri event (URL penuh + thumb) |
| GET | `/sponsors` | Daftar sponsor |
| GET | `/organization` | Struktur organisasi |
| GET | `/organization/stats` | Statistik organisasi |
| GET | `/organization/tree` | Struktur pohon organisasi |
| GET | `/organization/years` | Daftar tahun periode organisasi |
| GET | `/organization/{id}` | Detail anggota organisasi |
| GET | `/merchandise` | Daftar merchandise |
| GET | `/merchandise/{id}` | Detail merchandise |

### Authenticated (Bearer token)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Profil user + peserta |
| POST | `/events/{eventId}/register` | Daftar event |
| GET | `/my-events` | Event yang diikuti user + status order |
| GET | `/participants` | Data peserta |
| GET | `/participants/{id}` | Detail peserta |
| PUT | `/participants/{id}` | Update peserta |
| GET | `/participants/{id}/events` | Event peserta |
| GET | `/participants/{id}/attendance` | Absensi peserta |
| POST | `/payments/create` | Buat pembayaran |
| GET | `/payments/{id}` | Detail pembayaran |
| GET | `/payments/history` | Riwayat pembayaran |
| GET | `/membership` | Status membership user (aktif/expired + riwayat) |
| GET | `/membership/plans` | Daftar paket & harga membership |
| GET | `/membership/history` | Riwayat membership user |
| POST | `/membership/subscribe` | Ajukan pembelian membership (menghasilkan payment pending) |
| POST | `/membership/cancel` | Batalkan membership |
| POST | `/attendance/check-in` | Check-in |
| POST | `/attendance/check-out` | Check-out |
| GET | `/attendance/{eventId}` | Absensi per event |
| POST | `/attendance/scan` | Scan QR code |
| POST | `/merchandise/order` | Order merchandise |
| GET | `/notifications` | Notifikasi user (20 terbaru) |
| GET | `/notifications/unread-count` | Jumlah notifikasi belum dibaca |
| POST | `/notifications/{id}/read` | Tandai notifikasi dibaca |
| POST | `/notifications/read-all` | Tandai semua notifikasi dibaca |

## Redis

Aplikasi menggunakan **Redis** untuk menyimpan session, cache, dan antrian queue — mengurangi beban MySQL dan meningkatkan performa.

| Fungsi | Driver |
|--------|--------|
| Session | `redis` |
| Cache | `redis` |
| Queue | `redis` |

Pastikan Redis berjalan sebelum menjalankan aplikasi:

```bash
redis-cli ping
# PONG
```

Untuk queue worker (pemrosesan antrian):

```bash
php artisan queue:work
```

## Fitur

- **Manajemen Event** — CRUD event, jadwal, kategori, quota, publish/ongoing/completed
- **Manajemen Peserta** — Registrasi via API (tanpa username/password), membership
- **Membership** — 3 tipe membership (tahunan, setengah tahun, mingguan), pemberian langsung oleh admin, pembelian via API dengan konfirmasi bendahara, riwayat & statistik di halaman `/admin/memberships`
- **Pembayaran** — Konfirmasi/reject oleh bendahara
- **Absensi** — Check-in/check-out dengan QR code
- **Galeri** — Upload foto/video event
- **Sponsor & Merchandise** — Manajemen sponsor dan penjualan merchandise
- **Organisasi** — Struktur kepengurusan
- **Role-based Access** — 8 level role untuk admin panel
- **Notifikasi Real-time** — broadcast via Reverb (WebSocket) ke admin web dan peserta mobile; tersimpan di database dengan status read/unread

## Pengembangan

```bash
# Jalankan queue worker (untuk job)
php artisan queue:work

# Development dengan hot-reload
npm run dev

# Tests
php artisan test

# Code style
./vendor/bin/pint
```

## Changelog Terbaru

### 2026-08-01 — Perbaikan API, Galeri, Register Member & Scan QR

**API Public Baru**
- `GET /events/{id}/participants` — dipindah dari grup autentikasi menjadi **publik** (untuk menampilkan peserta event). QR codes tetap dilindungi (`/events/{id}/qr`).
- `GET /galleries` — `GalleryController` baru; mengembalikan semua foto (`type=image`) dengan URL penuh, thumb, info event & album.
- `GET /categories` — `CategoryController` baru; kategori aktif + `events_count`.
- `GET /organization/years` — daftar tahun periode organisasi.

**Event**
- `EventRepository::findPublic()` — `GET /events` kini menampilkan `publish` + `ongoing` + `completed` (draft disembunyikan).
- `EventResource` diperkaya: `image_url`, `banner_url`, `registered_count`, `creator`, dan `galleries` (array URL foto, diurutkan featured → sort_order).
- `EventController::show()` memuat relasi `galleries`.
- `EventService::updateEventStatus()` diperbaiki dari akses property protected → `Event::query()` (transisi status publish/ongoing/completed kini berfungsi).
- `EventParticipant::markAsPaid()` — ditambahkan sehingga konfirmasi pembayaran event tidak error rollback.

**Member / Participant**
- `Participant` mendapat accessor `hash_id` + `$appends` → member `%04d` (mis. `0022`), non-member `NM-%04d` (mis. `NM-0044`); di-expose di `ParticipantResource`.
- `RegisterRequest` menerima `password` & `password_confirmation` opsional (min. 6 karakter).
- `AuthController::register()` memakai password yang dikirim user (fallback random jika kosong).
- Form Registrasi Member (`members/register`) diselaraskan dengan form Data Diri (`members/detail`): label "Nama Lengkap", urutan field Gender sebelum Tanggal Lahir, dan field `gender`/`blood_type`/`emergency_*`/`medical_conditions` bersifat opsional.
- Halaman admin `participants/show` menampilkan Foto Profil, Emergency Contact, Emergency Phone, dan Medical Conditions (sesuai data input frontend).

**Merchandise & Sponsor**
- `MerchandiseResource` & `SponsorResource` baru — response index/show kini terstruktur.
- `MerchandiseService` — validasi size hanya dijalankan jika `size_options` terisi.

**Organisasi**
- `OrganizationMemberRepository::tree()` menambahkan `period_start`/`period_end`.
- `OrganizationMemberRepository::years()` baru untuk filter periode.

**Scan QR Attendance (admin)**
- Scanner lebih responsif: `fps` 10 → 20, `qrbox` dinamis 80% viewfinder, native `BarcodeDetector` (`useBarCodeDetectorIfSupported`), video constraint 1280x720, cooldown antar-scan 3 dtk → 1,5 dtk. Akurasi membaca QR dari foto/screen meningkat.

**Frontend (Next.js)**
- Halaman event detail (`/events/finished` & `/events/upcoming`) menampilkan seksi **Galeri** di bagian bawah berisi foto event (lightbox + navigasi).
- Halaman galeri publik (`/gallery`) terhubung ke `GET /galleries` dengan MasonryGallery.
- `galleryService.js` dibuat; komponen Footer SVG diperbaiki.

Lihat juga `docs/` untuk dokumentasi per modul yang lebih detail.
