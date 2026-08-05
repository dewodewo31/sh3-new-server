# 14 — Changelog & Perbaikan

Kumpulan perbaikan dan penambahan terbaru pada sistem SH3 (backend Laravel + frontend Next.js).

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
