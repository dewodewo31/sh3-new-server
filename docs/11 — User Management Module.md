# 11 — User Management Module

Admin user management & role permission matrix.

```sql
CREATE TABLE users (
  id, name, email UNIQUE, password,
  role ENUM("admin_full_access","admin_laman","admin_member",
          "admin_bnh","organizer","bendahara","sponsor","merchandise"),
  remember_token, created_at, updated_at
);
CREATE TABLE user_activity_logs (
  id, user_id, action, details (JSON),
  ip_address, user_agent, created_at
);
```

# Role Permission Matrix

Full Access: Semua menu ✅ | Laman: Semua menu ✅ | Member: Hanya Participants ✅ | BNH: Gallery ✅ | Organizer: Events ✅ | Bendahara: Payments ✅ | Sponsor: Sponsors ✅ | Merchandise: Merchandise ✅

- Route: /users — Admin Full Access only
- /users/{id}/activity — Log aktivitas