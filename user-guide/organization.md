# Struktur Organisasi

Mengelola data kepengurusan SH3 (pengurus, jabatan, periode).

**Pengguna:** `admin_full_access`, `admin_laman`.

---

## 1. Melihat Struktur Organisasi

**Menu:** Sidebar → **Organization** (`/admin/organization`).

Tabel menampilkan nama, jabatan (position), periode, dan urutan.

---

## 2. Menambah Anggota Organisasi

**Cara penggunaan:**

1. Buka menu **Organization** → klik **Tambah**.
2. Isi:
   - **Name** — nama pengurus.
   - **Position** — jabatan (mis. "Ketua", "Sekretaris").
   - **Sort Order** — urutan tampil.
   - **Period Start / Period End** — periode jabatan.
   - **Avatar** — foto pengurus.
   - **Role Description** — deskripsi tugas.
3. Klik **Simpan**.

---

## 3. Mengubah & Menghapus

- **Ubah:** tombol **Edit** → ubah → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

---

## 4. Struktur Organisasi di Publik

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/organization` | Daftar pengurus |
| GET | `/api/v1/organization/stats` | Statistik organisasi |
| GET | `/api/v1/organization/tree` | Struktur pohon (hierarkis) |
| GET | `/api/v1/organization/years` | Daftar tahun periode |
| GET | `/api/v1/organization/{id}` | Detail anggota |

Halaman publik `/structure` menampilkan bagan struktur organisasi.

---

## Tips

- Isi **periode** dengan lengkap agar filter tahun (`/organization/years`) berfungsi.
- Gunakan **Sort Order** untuk mengatur posisi tampil sesuai jabatan.
