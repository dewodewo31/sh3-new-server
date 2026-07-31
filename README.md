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
```

## API Endpoints

Semua endpoint API berada di prefix `/api/v1`.

### Public (tanpa auth)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/register` | Registrasi peserta baru |
| POST | `/auth/login` | Login (email + password) |
| GET | `/events/upcoming` | Event mendatang |
| GET | `/events` | Semua event |
| GET | `/events/{id}` | Detail event |
| GET | `/sponsors` | Daftar sponsor |
| GET | `/organization` | Struktur organisasi |
| GET | `/merchandise` | Daftar merchandise |
| GET | `/merchandise/{id}` | Detail merchandise |

### Authenticated (Bearer token)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Profil user + peserta |
| POST | `/events/{eventId}/register` | Daftar event |
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
