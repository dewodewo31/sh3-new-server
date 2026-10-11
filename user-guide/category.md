# Kategori Event

Mengelola kategori/jenis event (mis. Long Run, Short Run) beserta jarak tempuh.

**Pengguna:** `admin_full_access`, `admin_laman`.

---

## 1. Melihat Daftar Kategori

**Menu:** Sidebar → **Categories** (`/admin/categories`).

Tabel menampilkan nama, jarak (km), jumlah event, dan status aktif.

---

## 2. Menambah Kategori

**Cara penggunaan:**

1. Buka menu **Categories** → klik **Tambah Kategori**.
2. Isi:
   - **Nama** kategori (mis. "Long Run").
   - **Deskripsi** (opsional).
   - **Icon** (opsional).
   - **Slug** (opsional, dibuat otomatis bila kosong).
   - **Distance (km)** — jarak tempuh.
   - **Sort Order** — urutan tampil.
   - **Aktif** — tampil/tidak di publik.
3. Klik **Simpan**.

---

## 3. Mengubah & Menghapus

- **Ubah:** tombol **Edit** pada baris → ubah → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

> Kategori yang masih dipakai event sebaiknya tidak dihapus; nonaktifkan saja.

---

## 4. Kategori di Publik

Endpoint publik `GET /api/v1/categories` mengembalikan kategori **aktif** beserta
`events_count` (jumlah event). Kategori dipakai saat:

- Membuat/mengedit event (pilihan kategori).
- Menampilkan daftar/filter event di frontend publik.

---

## Data Bawaan (Seeder)

| Nama | Keterangan |
|------|------------|
| Long Run | Jarak jauh |
| Short Run | Jarak pendek |
| Major Events | Event besar |
| Super Long | Jarak sangat jauh |
