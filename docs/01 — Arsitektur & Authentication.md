# 01 — Arsitektur & Authentication

# Layered Architecture

```
PRESENTATION LAYER
  Laravel Blade (Admin Panel) | API (Participant)
APPLICATION LAYER
  Controllers → Services → Requests → Resources
DOMAIN LAYER
  Models → Enums → DTOs → Domain Events/Jobs
INFRASTRUCTURE LAYER
  Database (MySQL) → Queue → Mail → Notification
```

# Authentication

- Admin Login: Web session-based via /auth
- Participant Login: API token-based (POST /api/participant/login)

# 8 Admin Roles

- ADMIN FULL ACCESS — Full akses semua menu
- ADMIN LAMAN — Semua menu website
- ADMIN MEMBER — Hanya member/participant
- ADMIN BNH — Gallery & konten BNH
- ORGANIZER — Event & orders
- BENDAHARA — Dashboard, orders, payments
- SPONSOR — Hanya sponsor
- MERCHANDISE — Hanya merchandise