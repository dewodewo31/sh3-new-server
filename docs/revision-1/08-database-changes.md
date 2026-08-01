# Database Changes

## Tujuan

Mendokumentasikan struktur database saat ini, perubahan yang sudah dilakukan sejak commit pertama, serta perubahan yang dibutuhkan untuk menyelesaikan Revision 1 (menyamakan fitur API dengan API terdahulu).

## Existing Tables

Berdasarkan `database/migrations/`:

| Tabel | Migration |
|---|---|
| `users` | `0001_01_01_000000_create_users_table.php` |
| `password_reset_tokens` | `0001_01_01_000000_create_users_table.php` |
| `sessions` | `0001_01_01_000000_create_users_table.php` |
| `cache` / `cache_locks` | `0001_01_01_000001_create_cache_table.php` |
| `jobs` / `job_batches` / `failed_jobs` | `0001_01_01_000002_create_jobs_table.php` |
| `user_activity_logs` | `2024_01_01_000003_create_user_activity_logs_table.php` |
| `categories` | `2024_01_01_000004_create_categories_table.php` |
| `participants` | `2024_01_01_000005_create_participants_table.php` |
| `membership_histories` | `2024_01_01_000006_create_membership_histories_table.php` |
| `events` | `2024_01_01_000007_create_events_table.php` |
| `event_schedules` | `2024_01_01_000007_create_events_table.php` |
| `event_participants` | `2024_01_01_000008_create_event_participants_table.php` |
| `merchandise` | `2024_01_01_000009_create_merchandise_table.php` |
| `merchandise_orders` | `2024_01_01_000009_create_merchandise_table.php` |
| `payments` | `2024_01_01_000010_create_payments_table.php` |
| `sponsors` | `2024_01_01_000011_create_sponsors_table.php` |
| `event_sponsors` | `2024_01_01_000011_create_sponsors_table.php` |
| `galleries` | `2024_01_01_000012_create_galleries_table.php` |
| `gallery_albums` | `2024_01_01_000012_create_galleries_table.php` |
| `organization_members` | `2024_01_01_000013_create_organization_members_table.php` |
| `attendance_logs` | `2024_01_01_000014_create_attendance_logs_table.php` |
| `attendances` | `2024_01_01_000014_create_attendance_logs_table.php` |
| `personal_access_tokens` | `2026_07_30_122525_create_personal_access_tokens_table.php` |
| `notifications` | `2026_07_31_034236_create_notifications_table.php` |
| `membership_plans` | `2026_07_31_100000_create_membership_plans_table.php` |

## Missing Tables

Tabel yang dibutuhkan agar sesuai dengan API terdahulu / fitur lanjutan:

- `event_sponsors` — ✅ sudah ada (pivot event ↔ sponsor, dibuat di migration `2024_01_01_000011`).
- `gallery_albums` — ✅ sudah ada (dibuat di migration `2024_01_01_000012`).
- (Opsional) tabel tambahan untuk fitur lanjutan organization (`parent_id`/`level` cukup via kolom, tidak perlu tabel baru).

## New Columns

Sudah ditambahkan sejak commit pertama:

- `users.role` → nilai `participant` (migration `2026_07_30_122526_add_participant_role_to_users.php`).
- `notifications` (tabel baru).
- `membership_plans` (tabel baru).
- `organization_members.parent_id` & `organization_members.level` → hierarki pengurus (migration `2026_08_01_000001_add_hierarchy_to_organization_members_table.php`).

## Modified Columns

| Kolom | Perubahan | Migration |
|---|---|---|
| `participants.membership_type` | ENUM → `string(50)`, default `none` | `2026_07_31_100001_change_membership_type_to_string.php` |
| `membership_histories.membership_type` | ENUM → `string(50)` | `2026_07_31_100001_change_membership_type_to_string.php` |
| `membership_histories.status` | tambah `pending` (ENUM: pending, active, expired, cancelled), default `pending` | `2026_07_31_000001_add_pending_status_to_membership_histories_table.php` |

## Foreign Key

Aturan saat ini:

| Kolom | Tabel | Perilaku |
|---|---|---|
| `participants.user_id` | users | nullOnDelete |
| `events.category_id` | categories | restrictOnDelete |
| `events.created_by` / `updated_by` | users | nullOnDelete |
| `event_schedules.event_id` | events | cascadeOnDelete |
| `event_participants.event_id` | events | cascadeOnDelete |
| `event_participants.participant_id` | participants | cascadeOnDelete |
| `merchandise.created_by` | users | nullOnDelete |
| `merchandise_orders.merchandise_id` | merchandise | restrictOnDelete |
| `merchandise_orders.participant_id` | participants | restrictOnDelete |
| `payments.participant_id` | participants | restrictOnDelete |
| `payments.confirmed_by` | users | nullOnDelete |
| `membership_histories.participant_id` | participants | cascadeOnDelete |
| `organization_members.participant_id` | participants | nullOnDelete |
| `attendance_logs.event_id` | events | cascadeOnDelete |
| `attendance_logs.participant_id` | participants | cascadeOnDelete |
| `attendance_logs.scanned_by` | users | nullOnDelete |
| `attendances.event_participant_id` | event_participants | cascadeOnDelete |
| `organization_members.parent_id` | organization_members | nullOnDelete |

## Index

- `event_participants`: unique `(event_id, participant_id)`.
- `event_sponsors`: unique `(event_id, sponsor_id)`.
- `attendance_logs`: unique `(event_id, participant_id, type)`.
- `notifications`: index `(notifiable_id, notifiable_type, read_at)`.
- `users.email` unique; `merchandise` → belum ada index custom selain default.

## Seeder

- `UserSeeder` — user admin & role bawaan.
- `CategorySeeder` — kategori event (5K, 10K, 21K, 42K, Trail).
- `ParticipantSeeder` — data peserta.
- `MembershipPlanSeeder` — paket membership (tahunan 400.000 / setengah_tahun 250.000 / mingguan 10.000).
- `EventSeeder` & `EventParticipantSeeder` — event + pendaftaran.
- `MerchandiseSeeder` — produk merchandise.
- `SponsorSeeder` — data sponsor.
- `GallerySeeder` — galeri.
- `OrganizationMemberSeeder` — 8 jabatan pengurus SH3.

## Factory

Factory yang tersedia (di `database/factories/`):

- `UserFactory` (dipakai di test membership & notification).
- `ParticipantFactory` (dipakai di test).
- `CategoryFactory`, `EventFactory`, `EventScheduleFactory`, `EventParticipantFactory`.
- `MembershipPlanFactory`, `MembershipHistoryFactory`.
- `OrganizationMemberFactory` (termasuk state `childOf()` untuk hierarki).
- `MerchandiseFactory`, `MerchandiseOrderFactory`.
- `SponsorFactory`, `GalleryFactory`, `GalleryAlbumFactory`.
- `PaymentFactory` (state `forMembership()` / `forMerchandise()`).
- `AttendanceLogFactory`, `AttendanceFactory`.

Semua model terkait sudah menggunakan trait `HasFactory` sehingga bisa dipanggil via `Model::factory()`.

## Migration Order

Urutan migrasi (sesuai timestamp):

1. `0001_01_01_000000_create_users_table`
2. `0001_01_01_000001_create_cache_table`
3. `0001_01_01_000002_create_jobs_table`
4. `2024_01_01_000003_create_user_activity_logs_table`
5. `2024_01_01_000004_create_categories_table`
6. `2024_01_01_000005_create_participants_table`
7. `2024_01_01_000006_create_membership_histories_table`
8. `2024_01_01_000007_create_events_table`
9. `2024_01_01_000008_create_event_participants_table`
10. `2024_01_01_000009_create_merchandise_table`
11. `2024_01_01_000010_create_payments_table`
12. `2024_01_01_000011_create_sponsors_table`
13. `2024_01_01_000012_create_galleries_table`
14. `2024_01_01_000013_create_organization_members_table`
15. `2024_01_01_000014_create_attendance_logs_table`
16. `2026_07_30_122525_create_personal_access_tokens_table`
17. `2026_07_30_122526_add_participant_role_to_users`
18. `2026_07_31_000001_add_pending_status_to_membership_histories_table`
19. `2026_07_31_034236_create_notifications_table`
20. `2026_07_31_100000_create_membership_plans_table`
21. `2026_07_31_100001_change_membership_type_to_string`
22. `2026_08_01_000001_add_hierarchy_to_organization_members_table`

## Rollback

- Semua tabel dibuat dengan `dropIfExists` di `down()` → rollback aman secara berurutan.
- Perubahan kolom memiliki `down()` yang mengembalikan ENUM semula.
- `event_sponsors` dan `gallery_albums` sudah memiliki `down()` sendiri (dropIfExists).

## Checklist

- [x] Buat migration `event_sponsors` (pivot events ↔ sponsors) — sudah ada di `2024_01_01_000011_create_sponsors_table.php`.
- [x] Buat migration `gallery_albums` — sudah ada di `2024_01_01_000012_create_galleries_table.php`.
- [x] Tambahkan factory untuk Event, Merchandise, MembershipHistory, OrganizationMember, dll.
- [x] Pastikan `php artisan migrate:fresh --seed` berjalan tanpa error.
- [x] Dokumentasikan perubahan kolom baru pada file ini.
