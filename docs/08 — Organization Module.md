# 08 — Organization Module

Struktur organisasi / pengurus SH3.

```sql
CREATE TABLE organization_members (
  id, participant_id, name, position,
  role_description, sort_order, avatar,
  is_active, period_start, period_end
);
```

- Route: /organization — Full Access, Admin Laman
- API: GET /api/organization (publik)