# Event Revision

## Tujuan

Menyamakan fitur API Event dengan API terdahulu (spesifikasi `docs/readme.md` → *Event API*).

Spesifikasi terdahulu:

```
GET    /api/v1/events
GET    /api/v1/events/{id}
POST   /api/v1/events
PUT    /api/v1/events/{id}
DELETE /api/v1/events/{id}
POST   /api/v1/events/{id}/register
GET    /api/v1/events/{id}/participants
GET    /api/v1/events/{id}/qr
```

## Feature Comparison

| Fitur | Terdahulu | Saat Ini | Keterangan |
|---|---|---|---|
| List event publik | ✅ | ✅ | `GET /events` (status `publish`) |
| Detail event | ✅ | ✅ | `GET /events/{id}` |
| Event upcoming | — | ✅ | `GET /events/upcoming` |
| Register event | ✅ | ✅ | `POST /events/{id}/register` (auth) |
| Daftar peserta event | ✅ | ❌ | Method ada di controller, route belum ada |
| QR peserta event | ✅ | ❌ | Belum ada route/method |
| Buat event (API) | ✅ | ❌ | Method `store` ada, route belum ada |
| Update event (API) | ✅ | ❌ | Method `update` ada, route belum ada |
| Hapus event (API) | ✅ | ❌ | Method `destroy` ada, route belum ada |

## Route Existing

`routes/api.php`:

```php
Route::get('/events/upcoming', [EventController::class, 'upcoming']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
// auth:sanctum
Route::post('/events/{eventId}/register', [EventController::class, 'register']);
```

## Missing Route

```php
// auth:sanctum (admin: admin_full_access, organizer)
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);
Route::get('/events/{id}/participants', [EventController::class, 'participants']);
Route::get('/events/{id}/qr', [EventController::class, 'qrCodes']);
```

Catatan: `store`, `update`, `destroy`, dan `participants` sudah diimplementasikan di controller tapi belum terdaftar sebagai route API.

## Controller

`app/Http/Controllers/API/EventController.php` — sudah memiliki method:

- `index()` → semua event `publish` (eager load `category`)
- `show(int $id)` → detail event (`category`, `schedules`)
- `store(EventRequest)` → create event (`201`)
- `update(int $id, EventRequest)` → update event
- `destroy(int $id)` → hapus event
- `register(int $eventId)` → daftar peserta ke event
- `participants(int $eventId)` → daftar peserta (eager load `eventParticipants.participant`)
- `upcoming()` → event `publish`/`ongoing` dengan `start_date >= now()`

## Method

| Method | Terdapat | Route | Terpakai |
|---|---|---|---|
| `index` | ✅ | ✅ | ✅ |
| `show` | ✅ | ✅ | ✅ |
| `store` | ✅ | ❌ | ❌ |
| `update` | ✅ | ❌ | ❌ |
| `destroy` | ✅ | ❌ | ❌ |
| `register` | ✅ | ✅ | ✅ |
| `participants` | ✅ | ❌ | ❌ |
| `upcoming` | ✅ | ✅ | ✅ |
| `qrCodes` | ❌ | ❌ | ❌ |

## Database Relation

- `events` → `category_id` (restrict), `created_by`/`updated_by` (nullOnDelete).
- `event_schedules` → cascade delete dari `events`.
- `event_participants` → cascade delete; unique `(event_id, participant_id)`.
- `Event` model: relasi `category`, `schedules`, `eventParticipants`, `sponsors` (pivot `event_sponsors`), `galleries`, `galleryAlbums`, `attendanceLogs`, `createdBy`, `updatedBy`, `participants` (belongsToMany via `event_participants`).
- `remainingQuota()` → `quota - jumlah registrasi (pending + confirmed)`; `-1` bila tanpa kuota.

## Flow Register Event

`EventService::registerParticipant()` (dalam transaksi):

1. Cek kuota tersisa → jika habis, `422 "Kuota event sudah penuh."`
2. Cek duplikat registrasi (`event_participants` unique) → `422 "Anda sudah terdaftar di event ini."`
3. Tentukan `registration_type` & `amount`:
   - Event `is_free_for_members` + membership aktif → `membership`, amount `0`, `is_membership_free=true`.
   - Event tanpa harga (price 0/null) → `free`, amount `0`.
   - Selain itu → `paid`, amount = `event.price`.
4. `payment_status` = `confirmed` bila amount 0, selain itu `pending`.
5. Generate QR (`QRCodeService::generate`), simpan `qr_code` di `event_participants`.
6. Increment `total_events_participated`.
7. Kirim notifikasi ke admin (`notifyAdmins`) dan peserta (`notifyParticipant`).

## Flow Cancel

`EventService::cancelRegistration(Event, Participant)`:

1. Cari registrasi; jika tidak ada → `422 "Pendaftaran tidak ditemukan."`
2. Hapus registrasi.
3. Decrement `total_events_participated`.

> Route API cancel belum ada; saat ini hanya tersedia via service (belum diekspos ke route).

## Flow Participant

`GET /events/{id}/participants` → `EventController::participants` mengembalikan seluruh `eventParticipants` (dengan relasi `participant`). Route belum terdaftar.

## Flow Merchandise

Tidak terkait langsung dengan modul Event. Merchandise punya alur sendiri (lihat `03-merchandise.md`).

## Validation

`app/Http/Requests/EventRequest.php`:

- `category_id` → required, exists:categories,id
- `title` → required, string, max:255
- `description`, `location`, `address`, `latitude`, `longitude`, `image` (image max 2MB), `banner` (image max 2MB), `key_points` (json) → nullable
- `start_date` → required, date; `end_date` → required, date, after:start_date
- `registration_start_date` → required, date; `registration_end_date` → required, date, after:registration_start_date
- `quota` → nullable, integer, min:1
- `price` → nullable, numeric, min:0
- `is_free_for_members` → boolean
- `status` → nullable, in:draft,publish,ongoing,completed,cancelled

> Catatan: slug dibuat otomatis di model `Event` (booted `creating`).

## Response

`GET /events` / `GET /events/upcoming` → `200`:

```json
{ "data": [ { "...": "EventResource shape" } ] }
```

`GET /events/{id}` → `200` dengan `data` (termasuk `category`, `schedules`, `remaining_quota`).

`POST /events` → `201`:

```json
{ "data": { "...": "event" }, "message": "Event berhasil dibuat" }
```

`PUT /events/{id}` → `200`:

```json
{ "data": { "...": "event" }, "message": "Event berhasil diupdate" }
```

`DELETE /events/{id}` → `200`:

```json
{ "message": "Event berhasil dihapus" }
```

`POST /events/{id}/register` → `200`:

```json
{ "message": "Pendaftaran berhasil" }
```

## Error Response

- Event tidak ditemukan → `404` (findOrFail).
- Kuota penuh / sudah terdaftar → `422` (ValidationException dengan `errors.event`).
- User tanpa profil peserta → `404 { "message": "Data peserta tidak ditemukan" }`.
- Belum login → `401`.

## Edge Cases

- Kuota penuh saat mendaftar.
- Registrasi ganda (unique constraint + cek manual).
- Event gratis vs berbayar vs gratis untuk member.
- Event tanpa kuota (`quota` null → `remainingQuota() = -1`).
- Status event: draft tidak muncul di list publik (`findPublished` hanya `publish`).
- `publishEvent` hanya boleh dari status `draft`.

## Testing

- Test list & detail event publik.
- Test register sukses (paid / free / membership).
- Test register kuota penuh → 422.
- Test register duplikat → 422.
- Test CRUD event (store/update/destroy) setelah route ditambahkan.
- Test `GET /events/{id}/participants`.
- Test autentikasi: endpoint berbayar menolak tanpa token.

## Checklist

- [ ] Tambahkan route `POST /events`, `PUT /events/{id}`, `DELETE /events/{id}`.
- [ ] Tambahkan route `GET /events/{id}/participants`.
- [ ] Tambahkan method `qrCodes()` + route `GET /events/{id}/qr`.
- [ ] Gunakan `EventResource` untuk response API (saat ini masih raw model).
- [ ] Pastikan pembatasan role (admin/organizer) untuk endpoint CRUD.
- [ ] Tambahkan test feature.
