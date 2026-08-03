# 09 — Sponsor Module

Sponsor management dengan tier (Platinum, Gold, Silver, Bronze).

```sql
CREATE TABLE sponsors (
  id, name, description, logo, website,
  tier ENUM("platinum","gold","silver","bronze"),
  year YEAR, sort_order, is_active
);
```

- Route: /sponsors — Full Access, Admin Laman, Sponsor
- API: GET /api/sponsors (publik)