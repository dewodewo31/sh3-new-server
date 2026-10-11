# Galeri & Album

Mengelola foto/video event serta album galeri (termasuk sumber Google Drive).

**Pengguna:** `admin_full_access`, `admin_laman`, `gallery`.

---

## 1. Menambah Foto/Video (Gallery)

**Menu:** Sidebar → **Galleries** (`/admin/galleries`).

**Cara penggunaan:**

1. Buka menu **Galleries** → klik **Tambah Galeri**.
2. Isi:
   - **Title** — judul media.
   - **Event** — event terkait.
   - **Album** — album (opsional).
   - **Type** — `image` atau `video`.
   - **Source** — sumber berkas (`upload` atau Google Drive).
   - **File** — berkas (gambar maks 10MB / video maks 50MB).
   - **Google Drive URL** — mis. `https://drive.google.com/file/d/FILE_ID/view`.
   - **Sort Order** — urutan tampil.
   - **Is Featured** — tandai sebagai gambar unggulan (tampil di publik).
   - **Description** — deskripsi.
3. Klik **Simpan**.

> Hanya media bertanda **is_featured = true** yang ditampilkan di galeri publik.

---

## 2. Album Galeri

**Menu:** Sidebar → **Albums** (`/admin/gallery-albums`).

**Cara penggunaan:**

1. Buka menu **Albums** → klik **Tambah Album**.
2. Isi:
   - **Title** — nama album.
   - **Event** — event terkait.
   - **Cover Image** — gambar sampul.
   - **Google Drive Folder URL** — mis.
     `https://drive.google.com/drive/folders/FOLDER_ID` (untuk tautan folder eksternal).
   - **Description** — deskripsi.
3. Klik **Simpan**.

Album yang punya `gdrive_folder_url` menampilkan **tautan folder Google Drive** di
frontend publik.

---

## 3. Mengubah & Menghapus

- **Ubah:** tombol **Edit** pada baris → ubah → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

---

## 4. Galeri di Publik

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/galleries` | Semua foto unggulan (URL penuh + thumb) |
| GET | `/api/v1/gallery-albums` | Daftar album publik |
| GET | `/api/v1/gallery-albums/{id}` | Detail album (semua media, termasuk video) |

- Frontend menampilkan **Masonry gallery + lightbox**.
- Detail event juga menampilkan seksi galeri (foto event).

---

## Tips

- Tandai beberapa foto terbaik sebagai **Featured** agar tampil di halaman publik.
- Untuk video besar, gunakan **Google Drive URL** daripada upload langsung.
- Pastikan `APP_URL` dan `storage:link` benar, jika tidak gambar akan 403/404.
