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
3. Mengubah URL menjadi format public preview.
4. Menyimpan metadata ke database.
5. Menampilkan gambar menggunakan URL Google Drive.

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

Semua otomatis dikonversi menjadi:

```
https://drive.google.com/uc?id=FILE_ID
```

File harus disetel menjadi:

> Anyone with the link can view.

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

Mengembalikan seluruh gallery image.

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

            "url":"https://drive.google.com/uc?id=xxxxxxxx",

            "thumb":"https://drive.google.com/uc?id=xxxxxxxx",

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
                "url":"https://drive.google.com/uc?id=FILE_ID"
            },
            {
                "url":"https://example.com/storage/galleries/photo.jpg"
            }
        ]
    }
}
```

Frontend tidak perlu mengetahui apakah gambar berasal dari Local Storage maupun Google Drive.

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
- Gallery tetap diurutkan berdasarkan:
  1. `is_featured DESC`
  2. `sort_order ASC`
  3. `id ASC`

---

# Notes

- Penyimpanan Google Drive tidak meng-upload ulang file ke server.
- Server hanya menyimpan metadata dan Google Drive File ID.
- Jika file Google Drive dihapus atau akses publik dicabut, gallery akan dianggap tidak tersedia hingga link diperbaiki.
- Desain ini tetap kompatibel dengan Local Storage sehingga tidak mengubah struktur API maupun frontend.
```