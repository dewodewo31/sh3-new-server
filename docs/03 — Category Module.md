# 03 — Category Module

Event categories — Long Run, Short Run, Major Events, Super Long.

## Database

### Tabel `categories`

```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    distance_km DECIMAL(5,2),
    slug VARCHAR(100) UNIQUE NOT NULL,
    banner VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Schema dan foreign key resmi ada pada migration `2024_01_01_000004_create_categories_table.php`.

## Seed Data

- Long Run
- Short Run
- Major Events
- Super Long

> **Catatan:** Data seeder berbeda dari versi PRD (5K, 10K, 21K, 42K, Trail). Seed data aktual adalah 4 kategori di atas.

## Routes

- API publik: `GET /api/v1/categories`, mengembalikan kategori aktif dan data agregat yang dibentuk resource/controller.
- Admin CRUD: `/admin/categories`, role `admin_full_access` atau `admin_laman`.

Detail model, repository, request, resource, migration, dan controller tercantum pada `docs/17 — Implementation Sync.md`.
