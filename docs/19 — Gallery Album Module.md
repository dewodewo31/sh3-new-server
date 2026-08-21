# 19 — Gallery Album Module

Modul admin untuk mengelola **album galeri** — pengelompokan foto/video gallery per event.
Album bertindak sebagai wadah (`gallery_album_id`) bagi record `galleries`.

Ditambahkan pada **2026-08-15** (CRUD admin lengkap). Tabel `gallery_albums` sudah ada sejak
awal (migration `2024_01_01_000012_create_galleries_table.php`).

---

## Overview

- **Gallery** = foto/video individual (dengan `type`, `file_path`/`google_drive_url`, `is_featured`).
- **GalleryAlbum** = wadah album: punya `title`, `description`, `cover_image`, opsional terikat ke `event`.

Fitur yang diimplementasikan:
1. CRUD lengkap (index, create, store, edit, update, destroy).
2. Upload cover image ke `storage/app/public/albums/` via `ImageHelper`.
3. Sorting kolom `title` & `created_at` pada halaman index (`Sort::apply`).
4. Statistik jumlah gallery per album (`withCount('galleries')`).
5. Activity logging (`create_album`, `update_album`, `delete_album`).
6. **Link folder Google Drive** (`gdrive_folder_url`) pada album — ditampilkan sebagai
   tombol/badge "Drive" di index, disimpan & divalidasi (hanya domain `drive.google.com`).
7. **Endpoint API publik** `GET /api/v1/gallery-albums` (2026-08-19).
8. **Sync isi folder Google Drive** (2026-08-20): command `gallery:sync-gdrive` (scheduler
   hourly + `withoutOverlapping`) dan tombol **Sync Drive** (`POST /admin/gallery-albums/sync`)
   menarik seluruh gambar+video folder publik menjadi record `galleries` — idempotent by
   `google_drive_file_id`, stale delete hanya saat sukses penuh, error sanitized di
   `gdrive_sync_error`, race guard `Cache::lock` per album.
9. **Endpoint API publik** `GET /api/v1/gallery-albums/{id}` (2026-08-20) — detail album
   berisi SEMUA media (image → thumbnail URL, video → `uc?export=download&id=...&confirm=t`,
   plus `external_url`).

---

## Database

### Tabel `gallery_albums`

```sql
CREATE TABLE gallery_albums (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    gdrive_folder_url TEXT NULL,
    last_synced_at TIMESTAMP NULL,
    gdrive_sync_error TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE SET NULL
);
```

> Kolom `gdrive_folder_url` ditambahkan oleh migration `2026_08_19_000001_add_gdrive_folder_url_to_gallery_albums.php`
> — link folder Google Drive opsional per album (dibuka sebagai link eksternal, tanpa API key / OAuth).
> Kolom `last_synced_at` + `gdrive_sync_error` ditambahkan oleh migration
> `2026_08_20_000001_add_gdrive_sync_columns_to_gallery_albums.php` (sekaligus composite index
> `(gallery_album_id, google_drive_file_id)` pada tabel `galleries` untuk upsert/stale-delete).

### Relasi

| Model | Relasi | Keterangan |
|-------|--------|------------|
| `GalleryAlbum` | `belongsTo Event` | Album milik sebuah event (opsional) |
| `GalleryAlbum` | `hasMany Gallery` | Foto/video dalam album |
| `Gallery` | `belongsTo GalleryAlbum` | Setiap gallery bisa masuk ke album |

---

## Files

- `app/Models/GalleryAlbum.php` — model (sudah ada sejak awal).
- `app/Http/Controllers/Admin/GalleryAlbumController.php` — controller CRUD (baru, 2026-08-15).
- `app/Repositories/GalleryAlbumRepository.php` — query (baru).
- `app/Http/Requests/GalleryAlbumRequest.php` — validasi (baru).
- `app/Http/Resources/GalleryAlbumResource.php` — resource API publik (baru, 2026-08-19).
- `app/Http/Controllers/API/GalleryAlbumController.php` — controller API publik (baru, 2026-08-19).
- `resources/views/gallery-albums/index.blade.php` — daftar album (baru).
- `resources/views/gallery-albums/create.blade.php` — form tambah (baru).
- `resources/views/gallery-albums/edit.blade.php` — form edit (baru).

---

## Controller Flow

### `index()`

```php
$albums = $this->galleryAlbumRepository->paginateWithRelations(15);
return view('gallery-albums.index', compact('albums'));
```

- `paginateWithRelations()` memuat relasi `event`, `withCount('galleries')`,
  dan `Sort::apply($query, ['title', 'created_at'], 'created_at', 'desc')`.
- Kolom sortable: `title`, `created_at`.
- View menampilkan: nomor, cover (thumbnail), title, event, jumlah gallery (badge), aksi.

### `store()` / `update()`

```php
if ($request->hasFile('cover_image')) {
    $data['cover_image'] = ImageHelper::upload($request->file('cover_image'), 'albums');
}
```

- Pada `update()`: cover lama dihapus (`ImageHelper::delete`) sebelum upload yang baru.
- Menulis log aktivitas: `create_album` / `update_album` dengan detail `{album_id, title}`.

### `destroy()`

- Menghapus `cover_image` dari storage lalu menghapus record album.
- Menulis log `delete_album`.

> **Catatan:** menghapus album **tidak** menghapus gallery di dalamnya. Kolom
> `galleries.gallery_album_id` akan menjadi `NULL` (ON DELETE SET NULL di migration).

---

## Validasi (`GalleryAlbumRequest`)

| Field | Rules |
|-------|-------|
| `event_id` | `nullable`, `exists:events,id` |
| `title` | `required`, `string`, `max:255` |
| `description` | `nullable`, `string` |
| `cover_image` | `nullable`, `image`, `max:4096` (4 MB) |
| `gdrive_folder_url` | `nullable`, `string`, `max:2048`, `url`, `regex:/drive\.google\.com/` |

---

## Routes (Admin Web)

| Method | URI | Name | Role |
|--------|-----|------|------|
| GET | `/admin/gallery-albums` | `admin.gallery-albums.index` | admin_full_access, admin_laman, gallery |
| GET | `/admin/gallery-albums/create` | `admin.gallery-albums.create` | admin_full_access, admin_laman, gallery |
| POST | `/admin/gallery-albums` | `admin.gallery-albums.store` | admin_full_access, admin_laman, gallery |
| GET | `/admin/gallery-albums/{gallery_album}/edit` | `admin.gallery-albums.edit` | admin_full_access, admin_laman, gallery |
| PUT/PATCH | `/admin/gallery-albums/{gallery_album}` | `admin.gallery-albums.update` | admin_full_access, admin_laman, gallery |
| DELETE | `/admin/gallery-albums/{gallery_album}` | `admin.gallery-albums.destroy` | admin_full_access, admin_laman, gallery |

Route didaftarkan via `Route::resource('gallery-albums', GalleryAlbumController::class)`
dalam grup `RoleMiddleware:admin_full_access,admin_laman,gallery` di `routes/web.php`.

---

## Sidebar

Menu **Albums** ditambahkan di `config/sidebar.php` di bawah menu Gallery:

```php
[
    'label' => 'Albums',
    'route' => 'admin.gallery-albums.index',
    'icon' => '...',
    'roles' => ['admin_full_access', 'admin_laman', 'gallery'],
    'active' => ['admin.gallery-albums.*'],
],
```

---

## Sortable Column (Index)

Halaman index memakai `x-th-sort` (lihat `docs/13 - Responsive Layout & Table Rules.md` untuk
aturan tabel, dan `app/Support/Sort.php` untuk helper sorting):

```blade
<x-th-sort column="title">Title</x-th-sort>
```

Query string yang dihasilkan: `?sort=title&direction=asc|desc`. URL mempertahankan query
string lain yang sedang aktif.

---

## Activity Log

| Aksi | `action` | Detail |
|------|----------|--------|
| Tambah album | `create_album` | `{album_id, title}` |
| Update album | `update_album` | `{album_id, title}` |
| Hapus album | `delete_album` | `{album_id, title}` |

Tercatat di tabel `user_activity_logs` via `UserService::logActivity()`.

---

## Integrasi dengan Gallery

Saat membuat/update gallery (modul `docs/06 — Gallery Module.md`), admin dapat memilih album
melalui field `gallery_album_id`. Album yang sudah dibuat di halaman ini akan muncul pada
dropdown album di form gallery.

---

## Public API

### GET `/api/v1/gallery-albums`

Endpoint publik (tanpa auth) — daftar album untuk halaman galeri frontend.

- `GalleryAlbumRepository::allPublic()`: `with('event')->withCount('galleries')->orderBy('title')`.
- Response via `GalleryAlbumResource::collection()`.

Contoh response:

```json
{
    "data": [
        {
            "id": 1,
            "event_id": null,
            "title": "SH3 Anniversary",
            "description": null,
            "cover_image": null,
            "gdrive_folder_url": "https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv",
            "galleries_count": 12,
            "event": null
        }
    ]
}
```

Frontend menampilkan link `gdrive_folder_url` sebagai tombol/link eksternal menuju folder
Google Drive (tanpa iframe, tanpa Google Drive API/OAuth).

### GET `/api/v1/gallery-albums/{id}`

Endpoint publik (tanpa auth) — detail album berisi **SEMUA media** di dalamnya
(`GalleryAlbumRepository::findPublicDetail()`, galleries diurutkan `sort_order, id`).
Kolom sync internal (`last_synced_at`, `gdrive_sync_error`) **tidak pernah** diekspos.

Contoh response:

```json
{
    "data": {
        "id": 1,
        "event_id": null,
        "title": "SH3 Anniversary",
        "description": null,
        "cover_image": null,
        "gdrive_folder_url": "https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv",
        "galleries_count": 2,
        "galleries": [
            {
                "id": 10,
                "album_id": 1,
                "title": "foto.jpg",
                "source": "gdrive",
                "url": "https://drive.google.com/thumbnail?id=img1&sz=w800",
                "thumb": "https://drive.google.com/thumbnail?id=img1&sz=w800",
                "external_url": "https://drive.google.com/file/d/img1/view",
                "type": "image",
                "is_featured": false
            },
            {
                "id": 11,
                "album_id": 1,
                "title": "klip.mp4",
                "source": "gdrive",
                "url": "https://drive.google.com/uc?export=download&id=vid1&confirm=t",
                "thumb": "https://drive.google.com/thumbnail?id=vid1&sz=w800",
                "external_url": "https://drive.google.com/file/d/vid1/view",
                "type": "video",
                "is_featured": false
            }
        ],
        "event": null
    }
}
```

> `GET /api/v1/galleries` dan index `/api/v1/gallery-albums` **tidak berubah** — index tetap
> tanpa array `galleries` (hanya `galleries_count`), galeri publik tetap featured-only.