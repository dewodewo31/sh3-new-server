# 15 — Notification Module

Notifikasi real-time (WebSocket/Reverb) sekaligus tersimpan di database dengan status read/unread. Berbasis Laravel Notification + database & broadcast channels.

## Komponen

- `App\Notifications\AdminNotification` — notifikasi queueable (`ShouldQueue`) dengan channel `database` + `broadcast`; menyimpan `title`, `body`, `icon`, `url`; `broadcastType()` = `admin.notification`.
- `App\Services\NotificationService` — helper untuk mengirim notifikasi ke banyak target:
  - `notifyAdmins()` — ke semua role admin (`admin_full_access`, `admin_laman`, `admin_member`, `admin_bnh`, `organizer`, `bendahara`, `sponsor`, `merchandise`).
  - `notifyRoles(array $roles, ...)` — ke user dengan role tertentu & `is_active`.
  - `notifyUser(User $user, ...)` — ke satu user.
  - `notifyParticipant(Participant $participant, ...)` — ke user pemilik participant (skip bila user tidak aktif).

Notifikasi dikirim secara otomatis dari berbagai Service (mis. membership grant, order merchandise, payment confirm, check-in admin, dll).

## API Endpoints (auth `auth:sanctum`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/notifications` | 20 notifikasi terbaru + `unread_count` |
| GET | `/api/v1/notifications/unread-count` | Jumlah notifikasi belum dibaca |
| POST | `/api/v1/notifications/{id}/read` | Tandai satu notifikasi dibaca |
| POST | `/api/v1/notifications/read-all` | Tandai semua dibaca |

Response item:

```json
{
  "id": "...",
  "title": "...",
  "body": "...",
  "icon": "bell",
  "url": null,
  "read_at": null,
  "created_at": "3 menit lalu",
  "created_at_raw": "2026-08-01T..."
}
```

## Route Admin (Web)

| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/admin/notifications` | Halaman notifikasi |
| GET | `/admin/notifications/unread-count` | Badge jumlah belum dibaca |
| POST | `/admin/notifications/{id}/read` | Tandai dibaca |
| POST | `/admin/notifications/read-all` | Tandai semua dibaca |

## File Terkait

- `app/Notifications/AdminNotification.php`
- `app/Services/NotificationService.php`
- `app/Http/Controllers/API/NotificationController.php`
- `app/Http/Controllers/Admin/NotificationController.php`
- `database/migrations/...create_notifications_table.php`
- Broadcast: `routes/channels.php` + **Laravel Reverb** (WebSocket)