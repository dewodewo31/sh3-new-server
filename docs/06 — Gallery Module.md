# 06 — Gallery Module

Photo/video gallery and albums for SH3 events.

---

# Overview

Gallery digunakan untuk menyimpan foto maupun video dokumentasi event SH3.

Modul ini mendukung dua sumber media:

- **Local Storage** (upload file ke server)
- **Google Drive Link** (paste URL Google Drive tanpa upload ulang)

Pemilihan sumber media dilakukan saat admin membuat gallery.

---

# Database

## Tabel `galleries`

```sql
CREATE TABLE galleries (
    id INT PRIMARY KEY AUTO_INCREMENT,

    event_id INT,
    gallery_album_id INT,

    title VARCHAR(255) NOT NULL,
    description TEXT,

    source ENUM('local','gdrive') DEFAULT 'local',

    file_path VARCHAR(255),
    thumbnail_path VARCHAR(255),

    google_drive_url TEXT,
    google_drive_file_id VARCHAR(255),

    type ENUM('image','video') DEFAULT 'image',

    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,

    created_by INT,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE SET NULL,

    FOREIGN KEY (gallery_album_id)
        REFERENCES gallery_albums(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);
```

### Penjelasan Kolom

| Kolom | Keterangan |
|--------|------------|
| source | Sumber media (`local` atau `gdrive`) |
| file_path | Lokasi file pada Local Storage |
| thumbnail_path | Thumbnail untuk Local Storage |
| google_drive_url | URL Google Drive yang diinput admin |
| google_drive_file_id | File ID hasil ekstraksi dari URL Google Drive |

---

## Tabel `gallery_albums`

```sql
CREATE TABLE gallery_albums (
    id INT PRIMARY KEY AUTO_INCREMENT,

    event_id INT,

    title VARCHAR(255) NOT NULL,

    description TEXT,

    cover_image VARCHAR(255),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE SET NULL
);
```

---

# Relationships

- Event hasMany Gallery
- Gallery belongsTo Event
- Gallery belongsTo GalleryAlbum
- User hasMany Gallery

---

# Roles

Dapat mengelola Gallery:

- Admin Full Access
- Admin Laman
- Admin BNH
- Gallery (hanya Galleries & Albums)

---

# Upload Gallery

Gallery dapat dibuat menggunakan dua metode.

## 1. Local Upload

Admin memilih file dari komputer.

```
Source

(•) Local Upload

Choose File

photo.jpg
```

Sistem akan:

1. Upload file ke Local Storage.
2. Membuat thumbnail.
3. Menyimpan path file.
4. Menyimpan metadata ke database.

Lokasi penyimpanan:

```
storage/app/public/galleries/
```

---

## 2. Google Drive Link

Admin cukup menempelkan URL Google Drive.

```
Source

( ) Google Drive Link

https://drive.google.com/file/d/xxxxxxxxxxxxxxxx/view?usp=sharing
```

Sistem akan:

1. Memvalidasi URL Google Drive.
2. Mengekstrak Google Drive File ID.
3. Menyimpan metadata ke database.
4. Menampilkan gambar menggunakan **Google Drive Thumbnail URL** (`thumbnail?id=FILE_ID&sz=w800`).

Tidak ada proses upload ulang ke server.

---

# Supported Google Drive Links

Contoh URL yang didukung:

```
https://drive.google.com/file/d/FILE_ID/view?usp=sharing
```

```
https://drive.google.com/open?id=FILE_ID
```

```
https://drive.google.com/uc?id=FILE_ID
```

Semua otomatis dikonversi menjadi thumbnail URL:

```
https://drive.google.com/thumbnail?id=FILE_ID&sz=w800
```

> **Catatan**: format `uc?id=FILE_ID` **tidak lagi dipakai** — sejak Januari 2024 Google
> Drive mengembalikan HTTP 403 untuk `uc?id=` tanpa cookie download. Konversi kini memakai
> endpoint `thumbnail?id=FILE_ID&sz=w800` (via `ImageHelper::gdriveThumbUrl()`). Jika
> `google_drive_file_id` kosong, URL mentah yang diinput admin dikembalikan apa adanya
> (fallback).

File harus disetel menjadi:

> Anyone with the link can view.

---

## 3. Sync Google Drive Folder (per Album)

Album galeri dapat menarik **seluruh isi folder Google Drive** (gambar + video) secara otomatis
via `gallery:sync-gdrive` (command/scheduler) atau tombol **Sync Drive** di panel admin
(`POST /admin/gallery-albums/sync`, roles `admin_full_access,admin_laman,gallery`).

Cara kerja (`GalleryService::syncAlbumFromDrive()`):

1. API key dibaca dari `GOOGLE_DRIVE_API_KEY` (`.env`, backend-only) — memakai Google Drive API
   `files.list` **tanpa OAuth**; folder wajib dishare *Anyone with the link can view*.
2. Ekstraksi folder ID dari `gdrive_folder_url` (regex `drive.google.com/drive/folders/...`);
   URL lain ditolak (anti-SSRF, base URL hardcoded `www.googleapis.com`).
3. Snapshot idempotent: upsert per `(gallery_album_id, google_drive_file_id)` — re-sync tidak
   membuat duplikat; kurasi admin (`is_featured`, `sort_order`) dipertahankan.
4. File yang hilang dari folder dihapus **hanya saat fetch sukses penuh** (abort-before-write:
   gagal 403/429/5xx/timeout/halaman tengah → snapshot DB utuh).
5. MIME tak dikenal (bukan `image/*` / `video/*`) dan subfolder di-skip (tidak rekursif).
6. Error disimpan sanitized di `gallery_albums.gdrive_sync_error` (tanpa API key/URL) +
   `last_synced_at` terisi saat sukses. Race guard: `Cache::lock('gallery:sync:{albumId}')`
   mencegah sinkron ganda (manual + scheduler bersamaan → status `skipped`).

URL tampilan media hasil sync:

| Tipe | URL |
|------|-----|
| Gambar | `https://drive.google.com/thumbnail?id=FILE_ID&sz=w800` |
| Video | `https://drive.google.com/uc?export=download&id=FILE_ID&confirm=t` |

> **Catatan video**: `uc?export=download&id=` **wajib** dengan `&confirm=t` — tanpa itu file
> >25MB menampilkan halaman virus-scan interstitial sehingga `<video>` gagal dimuat.
> Catatan `uc?id=` HTTP 403 (di atas) tetap berlaku untuk gambar. Fallback publik:
> `external_url` (URL mentah Drive) ikut dikirim oleh `GalleryResource`.

---

# Validation

## Local Upload

### Image

- JPG
- JPEG
- PNG
- WEBP

### Video

- MP4
- MOV

Ukuran maksimum mengikuti konfigurasi aplikasi.

---

## Google Drive

Sistem akan memvalidasi:

- URL Google Drive valid
- File ID dapat diekstrak
- Link bersifat publik
- Format file didukung

Jika gagal:

```
422 Unprocessable Entity

{
    "message": "Google Drive link is invalid or inaccessible."
}
```

---

# Public API

## GET `/api/v1/galleries`

Mengembalikan **hanya gallery image yang ditandai featured** (`is_featured = true`).
Gambar yang tidak dipilih admin (featured) **tidak** muncul di API publik — sesuai keputusan
produk bahwa galeri publik hanya menampilkan gambar terpilih.

Contoh response:

```json
{
    "data":[
        {
            "id":1,
            "event_id":1,
            "album_id":1,
            "title":"SH3 Anniversary Run 2026",

            "source":"gdrive",

            "url":"https://drive.google.com/thumbnail?id=xxxxxxxx&sz=w800",

            "thumb":"https://drive.google.com/thumbnail?id=xxxxxxxx&sz=w800",

            "type":"image",

            "is_featured":true,

            "event":{
                "id":1,
                "title":"SH3 Anniversary Run 2026",
                "status":"publish"
            }
        }
    ]
}
```

Urutan:

1. is_featured DESC
2. sort_order ASC
3. id ASC

Filter `is_featured = true` diterapkan di `GalleryService::getAllPublic()` dan
`GalleryService::getByEvent()` (digunakan `EventResource` untuk seksi galleries
pada `GET /api/v1/events/{id}`).

---

# Upload API

## POST `/api/v1/admin/galleries`

### Local Upload

Multipart Form Data

```
event_id
gallery_album_id
title
description
source=local
file
```

---

### Google Drive

```json
{
    "event_id":1,
    "gallery_album_id":1,
    "title":"Finish Line",
    "description":"Photo at finish line",
    "source":"gdrive",
    "google_drive_url":"https://drive.google.com/file/d/FILE_ID/view"
}
```

---

# Response

```json
{
    "message":"Gallery uploaded successfully."
}
```

---

# Gallery on Event Detail

GET `/api/v1/events/{id}`

Response:

```json
{
    "data":{
        "id":4,
        "title":"Ultra Marathon Bromo",

        "galleries":[
            {
                "url":"https://drive.google.com/thumbnail?id=FILE_ID&sz=w800"
            },
            {
                "url":"https://example.com/storage/galleries/photo.jpg"
            }
        ]
    }
}
```

Frontend tidak perlu mengetahui apakah gambar berasal dari Local Storage maupun Google Drive.

> Seksi `galleries` pada event detail juga hanya berisi gambar **featured**.

---

# Frontend

Halaman:

- `/gallery`
- `/events/upcoming`
- `/events/finished`

Komponen:

- MasonryGallery
- EventGallery
- Lightbox
- Thumbnail Strip

Admin Gallery Form:

```
Title

Description

Event

Album

Source

(•) Local Upload

( ) Google Drive Link

------------------------

Jika Local

Choose File

------------------------

Jika Google Drive

Paste Google Drive URL

------------------------

Preview

------------------------

Save
```

Saat URL Google Drive ditempel:

- Validasi URL
- Ambil File ID
- Preview gambar
- Simpan metadata

---

# Business Rules

- Gallery dapat berasal dari Local Storage maupun Google Drive.
- Frontend selalu menggunakan field `url`.
- Google Drive File ID disimpan agar URL dapat dibangun ulang jika diperlukan.
- Link Google Drive wajib bersifat publik (**Anyone with the link**).
- **API publik hanya menampilkan gallery `is_featured = true`** (gambar terpilih oleh admin).
- Gallery tetap diurutkan berdasarkan:
  1. `is_featured DESC`
  2. `sort_order ASC`
  3. `id ASC`

---

# Notes

- Penyimpanan Google Drive tidak meng-upload ulang file ke server.
- Server hanya menyimpan metadata dan Google Drive File ID.
- Jika file Google Drive dihapus atau akses publik dicabut, gallery akan dianggap tidak tersedia hingga link diperbaiki.
- URL gambar Google Drive dirender via endpoint thumbnail (`thumbnail?id=FILE_ID&sz=w800`) karena `uc?id=` sudah tidak berfungsi (HTTP 403 sejak Januari 2024).
- Desain ini tetap kompatibel dengan Local Storage sehingga tidak mengubah struktur API maupun frontend.
```