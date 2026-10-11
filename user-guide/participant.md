# Data Peserta

Mengelola data peserta lari SH3: profil, kontak darurat, identitas member, hingga
riwayat keikutsertaan event.

**Pengguna:** `admin_full_access`, `admin_member`.

---

## 1. Melihat Daftar Peserta

**Menu:** Sidebar → *Participants* (`/admin/participants`).

- Tabel menampilkan nama, **Kode Peserta**, email, kontak, dan status.
- Kolom **Kode** menampilkan Member ID (`3950`) atau Non-Member Code (`NM0001`)
  tergantung status membership peserta.
- Gunakan kolom pencarian untuk mencari nama/email/kode.

---

## 2. Menambah Peserta

**Cara penggunaan:**

1. Buka menu **Participants**.
2. Klik **Tambah Peserta**.
3. Isi data:
   - Nama lengkap (wajib), Email (wajib), Telepon.
   - Gender, Tanggal lahir.
   - Alamat.
   - Kontak darurat & telepon darurat.
   - Golongan darah, Ukuran jersey, Kondisi medis (opsional).
4. Klik **Simpan**.

> Saat peserta dibuat, sistem otomatis membuat **Permanent Member ID** (`hash_id`)
> dan **Non-Member Code** (`non_member_code`). Keduanya tidak dapat diisi manual.

---

## 3. Melihat Detail Peserta

Klik nama peserta atau tombol **Detail** pada tabel.

Halaman detail (`/admin/participants/{id}`) menampilkan:

- Data pribadi + Foto Profil.
- **Kode Peserta**.
- Kontak darurat & kondisi medis.
- Riwayat membership.
- Riwayat event yang diikuti.

---

## 4. Mengubah & Menghapus Peserta

- **Ubah:** tombol **Edit** → ubah data → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi. Data terkait (membership, registrasi
  event) dapat ikut terhapus sesuai relasi.

---

## 5. Permanent Member ID & Non-Member Code

Setiap peserta punya **dua identifier**:

| Identifier | Format | Sifat |
|------------|--------|-------|
| `hash_id` (Permanent Member ID) | `\d{4}`, mis. `4580` | Dibuat sekali, **tidak pernah berubah** |
| `non_member_code` | `NM\d{4}`, mis. `NM0012` | Kode tampilan saat membership tidak aktif |

**Aturan tampilan:**

- Membership aktif → tampilkan **Member ID** (`4580`).
- Membership tidak aktif/expired/cancel → tampilkan **Non-Member Code** (`NM0012`).

> Status membership boleh berubah berkali-kali, tetapi Permanent Member ID peserta
> tetap sama.

---

## 6. Registrasi Peserta (Publik)

Peserta dapat mendaftar sendiri dari frontend (`/members/register`) atau via API:

```http
POST /api/v1/auth/register
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "username": "budi_santoso",     // opsional, auto-generate bila kosong
  "password": "rahasia123",        // opsional, acak bila kosong
  "password_confirmation": "rahasia123"
}
```

- `email` wajib (untuk notifikasi & reset password).
- `username` 3–30 karakter, huruf/angka/underscore.

---

## 7. Endpoint API Peserta (Admin/Internal)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/participants` | Daftar peserta (paginasi) |
| GET | `/api/v1/participants/{id}` | Detail peserta + riwayat membership |
| PUT | `/api/v1/participants/{id}` | Update peserta |
| GET | `/api/v1/participants/{id}/events` | Event yang diikuti peserta |
| GET | `/api/v1/participants/{id}/attendance` | Absensi peserta |

---

## 8. Profil Peserta (Peserta Sendiri)

Peserta yang login dapat mengelola profilnya via API:

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/profile` | Lihat profil |
| PUT | `/api/v1/profile` | Ubah profil |
| POST | `/api/v1/profile/photo` | Upload foto profil |
| GET | `/api/v1/my-events` | Event yang diikuti + status order |

---

## Tips

- Jangan mengubah `hash_id`/`non_member_code` secara manual.
- Saat impor peserta massal, pastikan email unik agar tidak gagal validasi.
