# How to Run — SH3 Event Management System

## Prasyarat

Pastikan sudah terinstall:

- PHP ^8.3 + extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `gd`, `redis`
- Composer ^2
- MySQL / MariaDB
- Redis (session, cache, queue)
- Node.js >= 18 & npm

## 1. Cek Redis

```bash
redis-cli ping
# harus response: PONG
```

## 2. Setup Database

Buat database MySQL:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS db_server_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 3. Konfigurasi Environment

```bash
cp .env.example .env
# edit .env — sesuaikan DB_USERNAME, DB_PASSWORD, dan konfigurasi lain
```

## 4. Generate Key

```bash
php artisan key:generate
```

## 5. Install Dependencies

```bash
composer install
npm install
```

## 6. Migrasi & Seeder

```bash
php artisan migrate --seed
php artisan storage:link
```

## 7. Build Frontend Assets

```bash
npm run build
```

## 8. Jalankan Queue Worker

```bash
php artisan queue:work --queue=default --sleep=2 --tries=3
```

## 9. Jalankan Dev Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Akses: **http://127.0.0.1:8000**

## 10. Login

| Role | Email | Password |
|------|-------|----------|
| Admin Full Access | `admin.full@sh3.com` | `password` |
| Admin Laman | `admin.laman@sh3.com` | `password` |
| Admin Member | `admin.member@sh3.com` | `password` |
| Admin BNH | `admin.bnh@sh3.com` | `password` |
| Organizer | `organizer@sh3.com` | `password` |
| Bendahara | `bendahara@sh3.com` | `password` |

Buka `http://127.0.0.1:8000/login` untuk masuk panel admin.

## API Endpoints

Basis URL: `http://127.0.0.1:8000/api/v1`

Contoh login participant:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "budi_santoso", "password": "password"}'
```

## Catatan

- Queue worker harus berjalan agar notifikasi masuk ke database
- Scheduler (`php artisan schedule:work`) diperlukan untuk transisi status event otomatis
- Pastikan `APP_URL` di `.env` sesuai dengan host yang digunakan (agar URL storage dan notifikasi benar)
