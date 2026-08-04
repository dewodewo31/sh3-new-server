# 09 — Sponsor Module

Sponsor management dengan tier dan status aktif.

## Database

Schema resmi, enum, foreign key, dan timestamps ada pada migration `2024_01_01_000011_create_sponsors_table.php`. Contoh SQL ringkas lama di dokumen ini bukan schema executable.

## Routes

- Admin CRUD: `/admin/sponsors` — `admin_full_access`, `admin_laman`, `sponsor`.
- API publik: `GET /api/v1/sponsors`.

Detail model, repository, service, request, resource, controller, migration, API response, dan permission dirangkum di `docs/17 — Implementation Sync.md`.
