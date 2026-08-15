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
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE SET NULL
);
```

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

---

## Routes (Admin Web)

| Method | URI | Name | Role |
|--------|-----|------|------|
| GET | `/admin/gallery-albums` | `admin.gallery-albums.index` | admin_full_access, admin_laman |
| GET | `/admin/gallery-albums/create` | `admin.gallery-albums.create` | admin_full_access, admin_laman |
| POST | `/admin/gallery-albums` | `admin.gallery-albums.store` | admin_full_access, admin_laman |
| GET | `/admin/gallery-albums/{gallery_album}/edit` | `admin.gallery-albums.edit` | admin_full_access, admin_laman |
| PUT/PATCH | `/admin/gallery-albums/{gallery_album}` | `admin.gallery-albums.update` | admin_full_access, admin_laman |
| DELETE | `/admin/gallery-albums/{gallery_album}` | `admin.gallery-albums.destroy` | admin_full_access, admin_laman |

Route didaftarkan via `Route::resource('gallery-albums', GalleryAlbumController::class)`
dalam grup `RoleMiddleware:admin_full_access,admin_laman` di `routes/web.php`.

---

## Sidebar

Menu **Albums** ditambahkan di `config/sidebar.php` di bawah menu Gallery:

```php
[
    'label' => 'Albums',
    'route' => 'admin.gallery-albums.index',
    'icon' => '...',
    'roles' => ['admin_full_access', 'admin_laman'],
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