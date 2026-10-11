# Notifikasi

Notifikasi real-time untuk admin (aktivitas peserta/keuangan) dan peserta (status
registrasi/pembayaran/membership). Dikirim lewat WebSocket (Laravel Reverb) dan
tersimpan di database.

**Pengguna:** semua pengguna yang login.

---

## 1. Melihat Notifikasi (Admin)

**Menu:** ikon lonceng di navbar, atau halaman *Notifications* (`/admin/notifications`).

- **Badge angka** menunjukkan jumlah notifikasi **belum dibaca**.
- Notifikasi terbaru muncul sebagai **toast** tanpa perlu refresh halaman.

---

## 2. Menandai Dibaca

| Aksi | Cara |
|------|------|
| Baca satu | Klik notifikasi (`POST /admin/notifications/{id}/read`) |
| Baca semua | Klik **Tandai semua dibaca** (`POST /admin/notifications/read-all`) |
| Cek unread | `GET /admin/notifications/unread-count` |

---

## 3. Jenis Notifikasi

**Untuk admin/bendahara:**

- Registrasi peserta baru.
- Pembayaran (event/merchandise/membership) menunggu konfirmasi.
- Order merchandise baru.
- Check-in peserta.
- Pengajuan membership.

**Untuk peserta:**

- Registrasi event berhasil.
- Pembayaran dikonfirmasi / ditolak.
- Membership diaktifkan.
- Perpanjangan membership otomatis.

---

## 4. Notifikasi Peserta (API)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/notifications` | 20 notifikasi terbaru |
| GET | `/api/v1/notifications/unread-count` | Jumlah belum dibaca |
| POST | `/api/v1/notifications/{id}/read` | Tandai satu dibaca |
| POST | `/api/v1/notifications/read-all` | Tandai semua dibaca |

---

## Tips

- Notifikasi bergantung pada **queue worker** — pastikan supervisor `sh3-queue` berjalan.
- Notifikasi **real-time** membutuhkan **Reverb** aktif dan proxy WebSocket (wss) benar.
- Bila notifikasi tidak muncul, periksa log `storage/logs/queue.log` dan `reverb.log`.
