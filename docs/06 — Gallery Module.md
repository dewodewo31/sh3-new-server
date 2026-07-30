# 06 — Gallery Module

Photo/video gallery and albums for SH3 events.

```sql
CREATE TABLE galleries (
  id, event_id, title, description,
  type (image|video), file_path, thumbnail_path,
  is_featured, sort_order
);
CREATE TABLE gallery_albums (
  id, event_id, title, description, cover_image
);
```

# Roles

- Admin Full Access, Admin Laman, Admin BNH