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

## Instalasi Produksi (End-to-End)

Panduan lengkap men-deploy sistem ke **server produksi** agar seluruh fitur berjalan: backend (Laravel + API), panel admin (Blade), frontend publik (Next.js), **queue worker**, **scheduler**, **notifikasi real-time (Reverb)**, **storage gambar**, dan perubahan status event otomatis.

> **Catatan**: seluruh command dijalankan sebagai user deploy (`www-data` atau user non-root dengan permission baca-tulis pada direktori project). Sesuaikan nama domain (`sh3.example.com`) pada semua contoh di bawah.

### 0. Topologi

```
Browser
   ├── https://sh3.example.com    → Nginx → Laravel app (php-fpm)        [backend + admin panel]
   ├── https://app.sh3.example.com→ Nginx → Next.js (port 3000)         [frontend publik]
   └── wss://   :8080             → Reverb (WebSocket)                  [notif real-time]
   └── Redis :6379                →  session, cache, queue
   └── MySQL/MariaDB               →  database
```

### 1. Persyaratan Server

- **Ubuntu/Debian** (LTS)
- PHP **^8.3** + extensions: `bcmath ctype fileinfo json mbstring openssl pdo tokenizer xml gd redis pcntl`
- Composer **^2**
- MySQL 8 / MariaDB 10+
- **Redis** 7 (session, cache, queue)
- Node.js **>= 18** dan npm (untuk frontend + Reverb)

Install (Ubuntu) contoh:

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mysql php8.3-gd \
  php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-intl \
  unzip nginx redis-server mysql-server supervisor curl git
sudo apt install -y php8.3-redis
curl -fsSL https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Directory & Permission

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www

git clone <repo-url> sh3-server
cd sh3-server

mkdir -p storage/framework/{sessions,views,cache}
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Backend — Environment

```bash
cp .env.example .env
php artisan key:generate
```

Set minimal berikut di `.env` (sesuaikan nilai):

```ini
APP_NAME="SH3 Event Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sh3.example.com        # WAJIB domain HTTPS (dipakai untuk URL storage gambar)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_server_new
DB_USERNAME=sh3_user
DB_PASSWORD=STRONG-PASS

SESSION_DRIVER=redis
SESSION_LIFETIME=120

FILESYSTEM_DISK=public                 # simpan file ke disk 'public' agar bisa diakses URL

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=832654
REVERB_APP_KEY=<REVERB_APP_KEY>
REVERB_APP_SECRET=<REVERB_APP_SECRET>
REVERB_HOST=sh3.example.com            # host publik tempat WebSocket diakses
REVERB_PORT=8080
REVERB_SCHEME=http                     # https jika di belakang TLS proxy

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

MAIL_MAILER=smtp                       # atau gunakan 'log' untuk development
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<user>
MAIL_PASSWORD=<pass>
MAIL_FROM_ADDRESS="no-reply@sh3.example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **PENTING**: `APP_URL` menentukan basis URL file storage (`APP_URL/storage/...`). Jika salah, gambar akan 403/404 (lihat bug upload gambar di Changelog). HTTPS diperlukan agar browser bisa memuat konten.


### 4. Dependencies & Instalasi

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --seed                # buat database terlebih dahulu
php artisan storage:link                  # symlink public/storage -> storage/app/public
```

Buat user database dan database (jika belum):

```bash
mysql -uroot <<'SQL'
CREATE DATABASE IF NOT EXISTS db_server_new
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sh3_user'@'localhost' IDENTIFIED BY 'STRONG-PASS';
GRANT ALL PRIVILEGES ON db_server_new.* TO 'sh3_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 5. Cache Config & Storage

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### 6. Frontend Publik (Next.js)

```bash
cd frontend-sh3
npm ci --omit=dev
cp .env.example .env
# .env frontend:
#   NEXT_PUBLIC_API_URL=https://sh3.example.com/api/v1
#   NEXT_PUBLIC_BASE_ASSET_URL=https://sh3.example.com/storage
npm run build
```

Jalankan (untuk production):

```bash
npm start -- -H 127.0.0.1 -p 3000   # Jalankan lewat PM2/systemd agar persisten
```

### 7. Queue Worker & Scheduler (Supervisor)

Perlu diingat proses berjalan terus-menerus adalah **kunci** fitur notifikasi (tabel `notifications`), job, dan scheduler. Tanpa worker, notifikasi yang di-enqueue TIDAK akan masuk ke panel admin (lihat Changelog). Buat file supervisor:

`/etc/supervisor/conf.d/sh3-queue.conf`:

```ini
[program:sh3-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sh3-server/artisan queue:work --queue=default --sleep=2 --tries=3
autostart=true
autorestart=true
stopasgroup=true
numprocs=4
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sh3-server/storage/logs/queue.log
```

`/etc/supervisor/conf.d/sh3-reverb.conf`:

```ini
[program:sh3-reverb]
command=php /var/www/sh3-server/artisan reverb:start --no-interaction
directory=/var/www/sh3-server
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sh3-server/storage/logs/reverb.log
```

`/etc/supervisor/conf.d/sh3-scheduler.conf`:

```ini
[program:sh3-scheduler]
command=php /var/www/sh3-server/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sh3-server/storage/logs/scheduler.log
```

Aktifkan & mulai:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

> **Scheduler** perlu diaktifkan untuk **transisi status event otomatis** (publish → ongoing → completed) dan job terjadwal lainnya. Bila belum ada, tambahkan di `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(\App\Services\EventService::class)->updateEventStatus())
    ->everyMinute();
```

Jalankan pula cron (jika tidak memakai `schedule:work`, sebagai alternatif):

```bash
* * * * * cd /var/www/sh3-server && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Nginx — Backend Laravel

`/etc/nginx/sites-available/sh3-backend` (domain `sh3.example.com` = backend + panel + storage):

```nginx
server {
    listen 80;
    server_name sh3.example.com;

    root /var/www/sh3-server/public;
    index index.php;

    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Reverb (WebSocket) proxy (jika tidak expose port 8080 langsung)
    location /reverb/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/sh3-backend /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

> Tambahkan blok `listen 443 ssl` dan `certbot certonly --nginx -d sh3.example.com` untuk HTTPS (Lihat Langkah 10).

### 9. Nginx — Frontend Next.js

`/etc/nginx/sites-available/sh3-frontend` (domain `app.sh3.example.com` → Next.js port 3000):

```nginx
server {
    listen 80;
    server_name app.sh3.example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

> Jika Anda menjalankan Next.js dengan `next start` di port 3000 (satu proses), pastikan di-restart melalui manager proses (systemd) dan auto-start. Next.js **next start secara default tidak mendukung multi-instance**, gunakan satu instance atau `standalone` + PM2 cluster.

### 10. TLS/HTTPS (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot certonly --nginx -d sh3.example.com -d app.sh3.example.com
sudo certbot renew --dry-run
```

Setelah sertifikat ada, tambahkan `listen 443 ssl;` + sertifikat dan `server_name ...;` di masing-masing config, atau pakai `certbot --nginx` untuk auto-edit.

### 11. Verifikasi Instalasi

```bash
# 1) PHP-FPM + aplikasi
curl -I https://sh3.example.com/up           # 200 (GET health check)

# 2) Storage symlink          
ls -la sh3-server/public/storage            # symlink -> ../storage/app/public

# 3) Redis
redis-cli ping                              # PONG

# 4) Queue worker
sudo tail -f -n 20 /var/www/sh3-server/storage/logs/queue.log

# 5) Reverb WebSocket
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8080/   # 200

# 6) File storage dapat diakses
curl -s -o /dev/null -w "%{http_code}\n" \
  https://sh3.example.com/storage/galleries/events/city-run-sudirman/foto-1.jpg   # 200

# 7) Notifikasi Real-time
# Buka panel admin → buat data peserta di API → notif harus muncul tanpa refresh.

# 8) Test end-to-end: user beli tiket event di frontend → notif muncul di /admin/dashboard
```

### 12. Checklist Fitur

| Fitur | Dependensi yang wajib aktif |
|--------|-----------------------------|
| Login panel & role | PHP extension `redis` + session redis + FPM |
| Gambar upload | `storage:link` + `APP_URL` benar + disk `public` |
| Queue / job | Supervisor `sh3-queue` berjalan |
| Notifikasi DB | queue worker + koneksi Redis |
| Notif real-time | Reverb + nginx proxy `wss` |
| Transisi status event | Scheduler (`:updateEventStatus`) |
| Galeri / merchandise / sponsor | storage gambar + API URL benar |
| Pembayaran konfirmasi | queue worker (event `PaymentService`) |
| Frontend publik API | `NEXT_PUBLIC_API_URL` + CORS benar |

---

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
