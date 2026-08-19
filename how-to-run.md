# How to Run — SH3 Event Management System

Ada dua cara menjalankan sistem:

1. **Docker Compose** — disarankan, terutama untuk produksi. Satu perintah untuk seluruh stack.
2. **Manual** — PHP + Composer + Node langsung di host (untuk development lokal).

---

## Cara 1: Docker Compose (Disarankan — Produksi)

Seluruh stack (`app`, `mysql`, `redis`) berjalan di dalam container. Frontend assets di-build otomatis
oleh Vite di dalam image, migrasi + seeder dijalankan otomatis oleh `docker-entrypoint.sh`,
begitu juga `storage:link`, queue worker, dan scheduler.

### Prasyarat

- Docker Engine + Docker Compose v2

### 1. Build image aplikasi

```bash
docker compose build app
```

> Image berisi: vendor (composer), frontend assets hasil `npm run build` (Vite),
> extension PHP (`pdo_mysql`, `gd`, `bcmath`, `intl`, `redis`, dst.), dan `.env` yang
> otomatis dikonfigurasi menunjuk ke service `mysql` & `redis` di jaringan Docker.

### 2. Jalankan seluruh stack

```bash
docker compose up -d
```

Ini akan menjalankan:

| Container | Port | Keterangan |
|-----------|------|------------|
| `sh3-app` | `8000` | Laravel (php artisan serve) + queue worker + scheduler |
| `sh3-mysql` | internal | MySQL 8.0, database `db_server_new`, healthcheck |
| `sh3-redis` | internal | Redis 7 (session, cache, queue) |

Cek status:

```bash
docker compose ps
# semua harus "Up" / "healthy"
```

### 3. Migrasi & Seeder (otomatis)

`docker-entrypoint.sh` menunggu MySQL siap, lalu:

- `php artisan migrate --force`
- `php artisan db:seed --force` **hanya jika tabel `users` kosong** (idempotent — aman di-restart)
- `php artisan storage:link`
- menjalankan `queue:work` dan `schedule:work` di background
- menjalankan `php artisan serve --host=0.0.0.0 --port=8000` di foreground

Tidak perlu menjalankan migrasi manual — sudah otomatis saat container start.

### 4. Akses & Login

Buka **http://localhost:8000** (atau IP/domain server) → `/login`.

| Role | Email | Password |
|------|-------|----------|
| Admin Full Access | `admin.full@sh3.com` | `password` |
| Admin Laman | `admin.laman@sh3.com` | `password` |
| Admin Member | `admin.member@sh3.com` | `password` |
| Admin BNH | `admin.bnh@sh3.com` | `password` |
| Organizer | `organizer@sh3.com` | `password` |
| Bendahara | `bendahara@sh3.com` | `password` |
| Sponsor | `sponsor@sh3.com` | `password` |
| Merchandise | `merchandise@sh3.com` | `password` |
| Admin Gallery | `admin.gallery@sh3.com` | `password` |

### 5. Menjalankan Test

Database test dibuat manual sekali (dibutuhkan oleh `phpunit.xml`):

```bash
docker exec sh3-mysql mysql -uroot -pdatabase_pass -e "
  CREATE DATABASE IF NOT EXISTS db_server_new_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'sh3_user'@'%' IDENTIFIED BY 'adminsh3_dbpassword';
  GRANT ALL PRIVILEGES ON db_server_new_test.* TO 'sh3_user'@'%';
  GRANT ALL PRIVILEGES ON db_server_new.* TO 'sh3_user'@'%';
  FLUSH PRIVILEGES;"
```

Jalankan seluruh test suite di dalam container:

```bash
docker exec \
  -e DB_HOST=mysql -e DB_PORT=3306 \
  -e DB_DATABASE=db_server_new_test \
  -e DB_USERNAME=sh3_user -e DB_PASSWORD=adminsh3_dbpassword \
  sh3-app php artisan test
```

Test per modul:

```bash
docker exec \
  -e DB_HOST=mysql -e DB_PORT=3306 \
  -e DB_DATABASE=db_server_new_test \
  -e DB_USERNAME=sh3_user -e DB_PASSWORD=adminsh3_dbpassword \
  sh3-app php artisan test --filter='BookkeepingAdminTest|AdminAccessControlTest'
```

### 6. Maintenance

```bash
docker compose logs -f app            # log aplikasi
docker exec -it sh3-app bash          # masuk container
docker exec sh3-app php artisan <cmd> # artisan tanpa masuk container
docker compose restart app            # restart app
docker compose down                   # stop (data mysql/redis tetap tersimpan di volume)
docker compose down -v                # stop + hapus volume (HATI-HATI: data hilang)
```

### 7. Setelah Mengubah Kode / Frontend

Karena kode disalin ke image saat build (bukan bind-mount), perubahan kode wajib rebuild:

```bash
docker compose build app && docker compose up -d app
```

> **PENTING (asset frontend basi)**: folder `public/build` bersifat gitignored dan
> **harus tetap ada di `.dockerignore`**. Bila folder itu ikut ter-copy ke image, hasil
> `npm run build` segar di dalam image akan ketimpa `public/build` lokal yang basi,
> sehingga `manifest.json` menunjuk CSS/JS lama (gejala: ikon sortir besar & tidak rapi,
> styling baru tidak muncul). Perintah `docker compose build app` di atas selalu
> menghasilkan build assets terbaru.

---

## Cara 2: Manual — Development Lokal

### Prasyarat

- PHP ^8.3 + extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `gd`, `redis`
- Composer ^2
- MySQL / MariaDB
- Redis (session, cache, queue)
- Node.js >= 18 & npm

### 1. Cek Redis

```bash
redis-cli ping
# harus response: PONG
```

### 2. Setup Database

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS db_server_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
# edit .env — sesuaikan DB_USERNAME, DB_PASSWORD, dan konfigurasi lain
php artisan key:generate
```

### 4. Install Dependencies

```bash
composer install
npm install
```

### 5. Migrasi & Seeder

```bash
php artisan migrate --seed
php artisan storage:link
```

### 6. Build Frontend Assets

```bash
npm run build
```

### 7. Jalankan Queue Worker & Scheduler (background)

```bash
php artisan queue:work --queue=default --sleep=2 --tries=3 &
php artisan schedule:work &
```

### 8. Jalankan Dev Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Akses: **http://127.0.0.1:8000** (login lihat tabel di atas).

### 9. Menjalankan Test

Siapkan database test dengan kredensial sesuai `phpunit.xml`, lalu:

```bash
php artisan test
```

---

## API Endpoints

Basis URL: `http://localhost:8000/api/v1` (sesuaikan host)

Contoh login participant:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "budi_santoso", "password": "password"}'
```

---

## Catatan

- Queue worker harus berjalan agar notifikasi masuk ke database (otomatis di Docker, manual di Cara 2).
- Scheduler (`php artisan schedule:work`) diperlukan untuk transisi status event otomatis (otomatis di Docker).
- Pastikan `APP_URL` di `.env` sesuai dengan host yang digunakan (agar URL storage dan notifikasi benar).
- Di Docker, `APP_URL` default `http://localhost:8000` — sesuaikan di `Dockerfile` (langkah sed) atau override saat container start bila memakai domain.