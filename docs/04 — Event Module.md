# 04 — Event Module

Event CRUD, registrations, quota management, coordinates via Nominatim.

# Tables

```sql
events: id, category_id, title, slug, description, location,
  latitude, longitude, start_date, end_date, quota, price,
  key_point (JSON), image, banner, status, is_free_for_members

event_registrations: id, event_id, participant_id,
  registration_type (free|paid|membership), amount,
  payment_status, is_attended, check_in_at, check_out_at, qr_code
```

# Image & Banner Handling

- `EventController::store/update` memanggil `ImageHelper::upload($file, 'events')` untuk field `image` & `banner`.
- Hasil upload menyimpan path ke public disk (`storage/app/public/events/...`), bukan temp path upload (`/tmp/php...`).
- Saat `update`: file lama dihapus via `ImageHelper::delete()` sebelum file baru di-upload.
- Saat `destroy`: file `image` & `banner` ikut dihapus.
- Public URL dibentuk lewat `ImageHelper::getUrl()` → `Storage::disk('public')->url()` → `${APP_URL}/storage/{path}`.
- Fetch URL hanya valid jika file benar-benar ada di disk; path yang salah akan menghasilkan HTTP `403`.

# Registration Flow

- Member aktif → gratis (registration_type=membership)
- Event free → gratis (registration_type=free)
- Event berbayar → bayar (registration_type=paid)