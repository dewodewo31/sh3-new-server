# 14 — Changelog & Perbaikan

Kumpulan perbaikan dan penambahan terbaru pada sistem SH3 (backend Laravel + frontend Next.js).

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
