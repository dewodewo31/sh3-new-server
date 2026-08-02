# 📄 Product Requirements Document — SH3 Event Management System

| | |
|---|---|
| **Nama Produk** | SH3 Event Management System (SH3 EMS) |
| **Versi Dokumen** | 1.0.0 |
| **Status** | Draft |
| **Tanggal** | 2026-08-02 |
| **Penulis** | SH3 Development Team |
| **Teknologi** | Laravel 13 (Backend API + Admin), Next.js 16 / React 19 / Tailwind v4 (Frontend Publik), AdminLTE 3 (Admin Web) |

---

## 1. Ringkasan Eksekutif

**SH3 Event Management System** adalah platform digital untuk mengelola seluruh siklus kegiatan komunitas lari (running club) SH3 — mulai dari pendaftaran anggota/member, management serta pendaftaran event lari, membership, pembayaran, penjualan merchandise, galeri foto, hingga absensi berbasis QR Code.

Sistem terdiri dari **dua permukaan (front-end)**:

1. **Admin Web Panel** — dikelola oleh 8 role pengurus dengan kontrol akses berbeda.
2. **Frontend Publik** (Next.js) — landing page, daftar event, register member, galeri, dan merchandise untuk anggota/public.

Kedua permukaan terhubung ke **satu backend API** (`/api/v1`) berbasis Laravel yang melayani frontend publik serta calon konsumen (mobile/web).

---

## 2. Tujuan & Sasaran

### 2.1 Tujuan Bisnis
- Sentralisasi data anggota, event, keuangan, dan organisasi dalam satu sistem.
- Meningkatkan transparansi pembayaran dan status membership.
- Mempercepat proses pendaftaran event dan check-in (QR).
- Membangun ekosistem member (membership) yang berkelanjutan.

### 2.2 Objective & Key Results (OKR)
| Objective | Key Result |
|---|---|
| Adopsi digitalisasi anggota | 100% anggota baru terdaftar melalui sistem dalam 6 bulan |
| Efisiensi absensi | Waktu check-in per peserta < 10 detik via QR |
| Transparansi keuangan | 100% pembayaran tercatat & terekonfirmasi bendahara |
| Kesetiaan member | ≥ 60% member aktif memperpanjang membership |

### 2.3 Non-Goals (di luar lingkup saat ini)
- *Native mobile app* (difungsikan via REST API untuk dikembangkan nanti).
- Integrasi payment gateway otomatis/realtime (saat ini konfirmasi manual oleh bendahara; gateway direncanakan di iterasi berikutnya).
- E-commerce merchandise dengan pengiriman/ekspedisi realtime.

---

## 3. Persona & Pengguna

### 3.1 Role & Permintaan Akses

| Role | Deskripsi & Tugas Utama |
|---|---|
| **Admin Full Access** | Akses penuh semua modul; super-admin. |
| **Admin Laman** | Mengelola konten laman situs publik (event, galeri, organisasi). |
| **Admin Member** | Mengelola data peserta/member (CRUD peserta). |
| **Admin BNH** | Mengelola galeri (unggah foto/video event). |
| **Organizer** | Membuat & mengelola event, jadwal, melihat peserta. |
| **Bendahara** | Mengonfirmasi/menolak pembayaran, laporan keuangan. |
| **Sponsor** | Mengelola data sponsor & paket sponsorship. |
| **Merchandise** | Mengelola produk & penjualan merchandise. |
| **Participant (Peserta)** | Mendaftar event, mengikuti status membership & pembayaran, check-in/out via QR. |

### 3.2 User Stories (ringkasan)

- Sebagai **Admin Member**, saya ingin mendaftarkan/mengedit data anggota agar data selalu mutakhir.
- Sebagai **Organizer**, saya ingin membuat dan publish event dengan kuota agar peserta bisa mendaftar.
- Sebagai **Bendahara**, saya ingin mengonfirmasi atau menolak pembayaran agar status membership/event peserta valid.
- Sebagai **Peserta**, saya ingin mendaftar event dan melihat status pembayaran & membership saya.
- Sebagai **Admin BNH**, saya ingin mengunggah foto event agar tercantum di galeri publik.

---

## 4. Ruang Lingkup / Modul (Features)

### 4.1 Autentikasi & Profil
- Registrasi & login peserta via API (password opsional pada register, wajib untuk login).
- Login multi-role admin (web + middleware role).
- Profil peserta (update data diri, unggah foto profil).

### 4.2 Manajemen Event
- CRUD event, jadwal (`event_schedules`), kategori, kuota, status (draft → publish → ongoing → completed / cancelled).
- Publikasi event; endpoint publik menampilkan publish + ongoing + completed (draft disembunyikan).
- Detail event lengkap (banner, galeri, registered_count, creator, sponsor).

### 4.3 Peserta & Membership
- Data peserta lengkap (identitas, emergensi, medis, jersey).
- Membership 3 tipe: **tahunan / setengah tahun / mingguan**.
- Pemberian langsung oleh admin, pembelian via API dengan konfirmasi bendahara, pembatalan.
- Riwayat membership & status aktif/kedaluwarsa.

### 4.4 Pembayaran
- Tercatat untuk event, merchandise, dan membership.
- Metode: bank transfer / kartu / e-wallet / tunai.
- Konfirmasi penolakan manual oleh bendahara (status pending → success/failed/refund).

### 4.5 Absensi & QR Code
- QR unik per peserta per event.
- Check-in / check-out via QR scan (admin dengan scanner respon tinggi + self-scan peserta).
- Laporan kehadiran.

### 4.6 Galeri
- Unggah foto/video, thumbnail, featured image.
- Galeri publik tersusun (Masonry) + lightbox di frontend.

### 4.7 Sponsor & Merchandise
- Sponsor (tingkat platinum/gold/silver/bronze/media partner) & relasi ke event.
- Merchandise (stok, variasi size) + order peserta.

### 4.8 Organisasi
- Struktur kepengurusan, pohon data/tree, statistik, periode tahun.

### 4.9 Notifikasi
- Notifikasi real-time (Reverb/WebSocket) + tersimpan DB, status read/unread, unread-count.

---

## 5. Persyaratan Fungsional (Prioritas)

Daftar *skill* (fitur) dirinci di `README.md` dan `docs/`. Prioritas ringkas:

| # | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| F-01 | Auth & Profil | Registrasi/login/logout, profil | P0 |
| F-02 | Manajemen Event | CRUD + status + kuota + detail | P0 |
| F-03 | Peserta & Membership | Pendaftaran + 3 tipe membership + riwayat | P0 |
| F-04 | Pembayaran | Pencatatan & konfirmasi bendahara | P0 |
| F-05 | Absensi QR | Check-in/out + scan | P0 |
| F-06 | Galeri | Unggah & tampilan publik | P1 |
| F-07 | Merchandise | Produk & order | P1 |
| F-08 | Sponsor | Data & relasi event | P1 |
| F-09 | Organisasi | Struktur, tree, periode | P1 |
| F-10 | Notifikasi | Real-time + riwayat | P1 |
| F-11 | Laporan/Export | Export peserta ke Excel | P2 |

---

## 6. Persyaratan Non-Fungsional

- **Performa**: Session/cache/queue berbasis Redis; mengurangi beban MySQL.
- **Keamanan**: Sanctum untuk API, role middleware, Form Request validation, CSRF, XSS escaping, rate limiting per API, CORS dikonfigurasi.
- **Skalabilitas**: Arsitektur berlapis (Controller → Service → Repository → Model).
- **Ketersediaan**: Queue worker untuk job (notifikasi).
- **Kompatibilitas**: PHP 8.3+, MySQL/MariaDB, Node.js & NPM.
- **Module upload**: Maks 5MB/file; dukungan image GD/Imagick.

---

## 7. Arsitektur Sistem

```
┌──────────────────────────────────────────────────────┐
│           PRESENTATION LAYER                          │
│  - Blade Views (Admin Web / AdminLTE)                │
│  - Frontend Publik (Next.js)                         │
│  - API Response (JSON) / Mobile Client               │
└──────────────────────────────────────────────────────┘
                           ↓
┌──────────────────────────────────────────────────────┐
│               BUSINESS LAYER                          │
│  - Controllers (Request Handler)                     │
│  - Services (Business Logic)                         │
│  - DTOs / Resources / Form Requests                  │
└──────────────────────────────────────────────────────┘
                           ↓
┌──────────────────────────────────────────────────────┐
│              DATA ACCESS LAYER                        │
│  - Models (Eloquent ORM)                             │
│  - Repositories (Query)                              │
│  - Migrations & Seeders                             │
└──────────────────────────────────────────────────────┘
                           ↓
┌──────────────────────────────────────────────────────┐
│                 DATABASE LAYER                        │
│  - MySQL                                             │
│  - Redis (Cache / Queue / Session)                   │
└──────────────────────────────────────────────────────┘
```

### Teknologi
| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Auth | Laravel Sanctum |
| Realtime | Laravel Reverb (WebSocket) |
| QR | simplesoftwareio/simple-qrcode |
| Database | MySQL / MariaDB (SQLite dev) |
| Cache/Queue | Redis |
| Frontend Publik | Next.js 16, React 19, Tailwind v4 |
| Admin Template | AdminLTE 3, Bootstrap 5 |
| Laporan/PDF | Maatwebsite/Laravel-Excel (ekspor), dom PDF |

---

## 8. Entity Utama (Ringkas)

| Entity | Deskripsi Singkat |
|---|---|
| `users` | Pengguna sistem + role |
| `participants` | Data peserta lengkap |
| `membership_histories` | Riwayat membership |
| `membership_plans` | Paket membership dinamis (key, price, duration) |
| `events` + `event_schedules` | Event & jadwal |
| `categories` | Kategori event (5K/10K/21K/42K/trail) |
| `event_participants` | Relasi peserta-event + QR + status |
| `attendances` + `attendance_logs` | Check-in/out + audit log scan |
| `payments` | Transaksi keuangan (polymorphic) |
| `galleries` + `gallery_albums` | Foto/video event & album |
| `sponsors` + `event_sponsors` | Sponsor & relasi |
| `merchandise` + `merchandise_orders` | Produk & order |
| `organizations` / `organization_members` | Struktur kepengurusan |
| `notifications` | Notifikasi tersimpan |
| `user_activity_logs` | Audit aktivitas pengguna |

ERD detail & schema tabel per modul lihat: `docs/readme.md` & `docs/01`–`docs/14`.

---

## 9. Antarmuka Pengguna

### 9.1 Admin Web (Blade + AdminLTE)
- Dashboard dengan Chart.js.
- Tabel CRUD (DataTables).
- Form responsive untuk data peserta, event, merchandise, dll.
- Scanner QR untuk absensi dengan viewfinder & cooldown.

### 9.2 Frontend Publik (Next.js)
- Landing page, halaman event (upcoming/finished/detail).
- Register member, galeri (Masonry + lightbox).
- Merchandise.
- Responsive mobile-first.

---

## 10. API Ringkasan

Dokumentasi lengkap endpoint ada di `README.md`. Ringkasan per area:

- **Auth**: register / login / logout / me.
- **Event**: list (publik), detail, upcoming, registrasi, my-events.
- **Participant**: list/detail/update/events/attendance.
- **Membership**: status, plans, riwayat, subscribe, cancel.
- **Payment**: create, detail, history, confirm.
- **Attendance**: check-in, check-out, per-event, scan.
- **Merchandise**: list/detail/order.
- **Gallery / Category / Sponsor / Organization**: endpoint publik.
- **Notification**: list, unread-count, read, read-all.

Semua endpoint berprefix `/api/v1`. Autentikasi memakai `Bearer token` (Sanctum).

---

## 11. Metrik & Keberhasilan

- Waktu pendaftaran event rata-rata < 2 menit (UX).
- Akurasi scan QR ≥ 95% pada kondisi normal.
- Waktu konfirmasi pembayaran setelah upload bukti < 24 jam.
- Penguatan pengunaan active: `total_events_participated` per member experience positif.

---

## 12. Risiko & Asumsi

| Risiko | Mitigasi |
|---|---|
| Ketergantungan Redis untuk session/cache | Dokumentasi setup + check `redis-cli ping` |
| Konfirmasi pembayaran manual | Alur notifikasi real-time ke bendahara |
| Angka membership bisa turun | Program perpanjangan & reminder expirasi |
| Upload foto banyak | Membatasi ukuran (max 5MB) + kompresi image |

---

## 13. Roadmap / Iterasi

- **Iterasi Kandang 1 (P0)** : Auth, Profil, Event, Peserta, Membership Dinamis, Pembayaran (konfirmasi bendahara), Absensi QR + sinkronisasi offline.
- **Iterasi Kandang 2 (P1)** : Galeri publik + album, Merchandise (order/cancel/upload bukti), Sponsor, Organisasi, Notifikasi real-time, Membership Plans CRUD.
- **Iterasi Kandang 3 (P2)** : Gateway pembayaran otomatis (Midtrans/Xendit), Dashboard advanced analytics, Export laporan & invoice PDF, Aplikasi mobile.

---

## 14. Lampiran

- Dokumentasi lengkap per modul: `docs/` (01 – 14).
- Postman collection: `docs/postman/sh3-api.postman_collection.json`.
- Data default & seeder: di `README.md`.

---

**Tanggal pembaruan**: 2026-08-02 ·
**Dibuat oleh**: SH3 Development Team