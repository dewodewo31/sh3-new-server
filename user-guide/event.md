# Manajemen Event

Mengelola event lari SH3: pembuatan, publikasi, kuota, harga, registrasi peserta, dan
transisi status otomatis.

**Pengguna:** `admin_full_access`, `organizer`.

---

## 1. Melihat Daftar Event

**Menu:** Sidebar → *Events* (`/admin/events`).

- Tabel menampilkan judul, kategori, tanggal, lokasi, kuota, status.
- Gunakan pencarian (`Cari event...`) dan filter status.

---

## 2. Membuat Event Baru

**Cara penggunaan:**

1. Buka menu **Events** → klik **Tambah Event**.
2. Isi formulir:
   - **Title** — nama event.
   - **Kategori** — pilih dari daftar kategori.
   - **Lokasi** — tempat event.
   - **Tanggal Mulai / Selesai** — waktu event.
   - **Periode Pendaftaran** (mulai/selesai).
   - **Kuota Peserta** — jumlah maksimal peserta.
   - **Harga Tiket (Rp)** — isi `0` untuk gratis.
   - **Gratis untuk Member** (centang bila member tidak dikenakan biaya).
   - **Gambar** & **Banner** — unggah berkas.
   - **Link Iframe Google Maps** — tempel embed maps (opsional).
   - **Deskripsi** — rute, benefit, informasi penting.
3. Klik **Simpan**. Event tersimpan dengan status **draft**.

---

## 3. Alur Status Event

```text
draft → publish → ongoing → completed
```

- **draft** — belum tampil di publik. Publikasi manual lewat tombol **Publish**.
- **publish** — tampil sebagai event mendatang; pendaftaran dibuka.
- **ongoing** — event berlangsung (transisi otomatis oleh scheduler).
- **completed** — event selesai (transisi otomatis oleh scheduler).

> Transisi otomatis membutuhkan **scheduler** aktif. Lihat README bagian instalasi.

**Publish event:** buka detail/edit event → klik **Publish**.
**Batalkan event:** tombol **Cancel** pada event.

---

## 4. Registrasi Peserta

Peserta mendaftar dari frontend (`/events/register`) atau API:

```http
POST /api/v1/events/{eventId}/register
```

Aturan biaya pendaftaran:

| Kondisi | Tipe registrasi | Biaya |
|---------|-----------------|-------|
| Event gratis | `free` | Rp 0 |
| Event berbayar | `paid` | sesuai harga |
| Gratis untuk member + membership aktif | `membership` | Rp 0 |

- Setiap registrasi membuat **QR Code** peserta untuk absensi.
- Jika berbayar, dibuat **Payment** berstatus pending (lihat [payment.md](payment.md)).

---

## 5. Melihat Detail Event & Peserta

- **Detail event** (`/admin/events/{id}`): info lengkap, galeri, sponsor, jumlah
  peserta terdaftar.
- **Daftar peserta event** publik: `GET /api/v1/events/{id}/participants`.
- **QR peserta per event** (admin/organizer): `GET /api/v1/events/{id}/qr`.

---

## 6. Endpoint API Event

### Publik

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/events/upcoming` | Event mendatang |
| GET | `/api/v1/events` | Semua event (publish/ongoing/completed) |
| GET | `/api/v1/events/{id}` | Detail event (galeri, sponsor, jumlah peserta) |
| GET | `/api/v1/events/{id}/participants` | Daftar peserta event |

### Terautentikasi (admin/organizer)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/v1/events/{eventId}/register` | Daftar event |
| POST | `/api/v1/events` | Buat event |
| PUT | `/api/v1/events/{id}` | Ubah event |
| DELETE | `/api/v1/events/{id}` | Hapus event |
| GET | `/api/v1/events/{id}/qr` | QR peserta event |

---

## Tips

- Tetapkan **kuota** dan **periode pendaftaran** agar registrasi terkontrol.
- Untuk event member-only, aktifkan **Gratis untuk Member** — biaya otomatis Rp 0
  bagi peserta dengan membership aktif.
- Pastikan scheduler berjalan agar status event berpindah otomatis.
