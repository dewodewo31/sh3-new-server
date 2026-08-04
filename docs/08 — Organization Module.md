# 08 — Organization Module

Struktur organisasi / pengurus SH3.

## Database

### Tabel `organization_members`

```sql
CREATE TABLE organization_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT,                            -- hierarchy (self-referencing FK)
    participant_id INT,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    level INT DEFAULT 0,                      -- depth level (0 = root)
    role_description TEXT,
    avatar VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    period_start DATE,
    period_end DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES organization_members(id) ON DELETE SET NULL,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL
);
```

Kolom hierarchy (`parent_id`, `level`) ditambahkan oleh migration `2026_08_01_000001_add_hierarchy_to_organization_members_table.php`.

## Routes

- Admin CRUD: `/admin/organization` — `admin_full_access`, `admin_laman`.
- API publik memakai prefix `/api/v1`: `/organization`, `/organization/{id}`, `/organization/stats`, `/organization/tree`, dan `/organization/years`.

## Relationships

Model mendukung hubungan parent-child untuk hierarchy organisasi serta periode aktif/nonaktif.

Detail controller, service/repository, resource, request, migration, response, dan permission dirangkum di `docs/17 — Implementation Sync.md`.
