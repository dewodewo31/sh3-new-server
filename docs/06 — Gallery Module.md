# 06 — Gallery Module

Photo/video gallery and albums for SH3 events.

## Database

```sql
CREATE TABLE galleries (
  id, event_id, title, description,
  type (image|video), file_path, thumbnail_path,
  is_featured, sort_order, created_by
);
CREATE TABLE gallery_albums (
  id, event_id, title, description, cover_image
);
```

Relasi: `Event` has-many `Gallery` (via `event_id`), `Gallery` belongs-to `GalleryAlbum` (via `gallery_album_id`).

## Roles

- Admin Full Access, Admin Laman, Admin BNH

## Public API

### `GET /api/v1/galleries`

Mengembalikan semua foto (`type = image`) beserta URL lengkap, thumb, dan info event/album. Publik — tidak butuh token.

Contoh response:

```json
{
  "data": [
    {
      "id": 1,
      "event_id": 1,
      "album_id": 1,
      "title": "SH3 Anniversary Run 2026 Foto 1",
      "description": "...",
      "url": "http://localhost:8000/storage/galleries/events/sh3-anniversary-run-2026/foto-1.jpg",
      "thumb": "http://localhost:8000/storage/galleries/events/sh3-anniversary-run-2026/thumb-foto-1.jpg",
      "type": "image",
      "is_featured": true,
      "event": { "id": 1, "title": "SH3 Anniversary Run 2026", "category": "...", "status": "publish" }
    }
  ]
}
```

Urutan: `is_featured` DESC → `sort_order` ASC → `id` ASC.

## Galeri pada Detail Event

`GET /api/v1/events/{id}` memuat relasi `galleries` dan `EventResource` mengeksposnya sebagai array URL foto:

```json
{
  "data": {
    "id": 4,
    "title": "Ultra Marathon Bromo",
    "galleries": [
      "http://localhost:8000/storage/galleries/events/ultra-marathon-bromo/foto-1.jpg",
      "http://localhost:8000/storage/galleries/events/ultra-marathon-bromo/foto-2.jpg"
    ]
  }
}
```

Urutan galeri: `is_featured` DESC → `sort_order` ASC → `id` ASC. Hanya `type = image`.

## Frontend

- `/gallery` — halaman galeri publik, memakai `galleryService.js` (`GET /galleries`) → MasonryGallery.
- Halaman event detail (`/events/finished` & `/events/upcoming`) menampilkan seksi **Galeri** (komponen `EventGallery`) di bagian bawah berisi `event.galleries`; mendukung lightbox, navigasi keyboard, dan thumbnail strip.

## Catatan

- File yang terdaftar di DB tetapi hilang dari disk bisa di-generate ulang (mis. via PIL) agar URL tidak 404.
