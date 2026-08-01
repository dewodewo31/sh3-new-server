# Attendance Revision

## Tujuan

Menyamakan fitur API Attendance & QR Code dengan API terdahulu. Endpoint check-in/check-out sudah ada; laporan kehadiran (`GET /attendance/report`) dan perilaku sinkronisasi/offline belum tersedia di API.

## Existing API

`routes/api.php` (semua di bawah `auth:sanctum`):

```php
Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
Route::get('/attendance/{eventId}', [AttendanceController::class, 'byEvent']);
Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
```

## Missing API

| Endpoint | Fungsi | Status |
|---|---|---|
| `GET /api/v1/attendance/report` | Laporan kehadiran event | ✅ |
| `POST /api/v1/attendance/sync-up` | Sinkronisasi data absen offline → server | ✅ |
| `GET /api/v1/attendance/sync-down` | Sinkronisasi data absen dari server → device | ✅ |

Route `GET /attendance/report` sudah ada di web admin (`/admin/attendance/report`) tapi belum di API.

## QR Code

- Format: `SH3-{event_id}-{participant_id}-{random_hash}` (`app/Services/QRCodeService.php`).
- `generate(EventParticipant)` → simpan `qr_code` di `event_participants`.
- `decode(string)` → memvalidasi format (`SH3-` + 3 bagian), mengembalikan `{ event_id, participant_id, hash }` atau `null`.
- `POST /attendance/scan` menerima `qr_code`, mendecode via `AttendanceService::scanQRCode`, dan mengembalikan data hasil decode.

## Attendance Status

Status attendance (`2024_01_01_000014_create_attendance_logs_table.php` → tabel `attendances`):

- `present`, `absent`, `late`, `left_early` (default `absent`).
- Method check-in: `check_in_method` (`qr_code`, `manual`, `self_scan`), `latitude`, `longitude`, `notes`.
- `checkOut` hanya meng-update `check_out_time`.

Status pada `event_participants`: `is_attended`, `check_in_at`, `check_out_at`.

`attendance_logs` (tabel audit terpisah) menyimpan riwayat scan: `type` (`check_in`/`check_out`), `scan_time`, `scanned_by`, `qr_code`, `latitude`, `longitude`, `ip_address` — unique `(event_id, participant_id, type)`.

> Catatan: `AttendanceService` mengisi tabel `attendance_logs` (audit) pada setiap check-in/check-out via `updateOrCreate` (idempotent terhadap unique key `(event_id, participant_id, type)`).

## Attendance History

- `GET /api/v1/participants/{id}/attendance` → daftar `eventParticipants` dengan relasi `attendance` (dari `ParticipantController::attendance`).
- `GET /api/v1/attendance/{eventId}` → daftar `eventParticipants` dengan relasi `attendance` untuk satu event.

## Sync Down

Belum ada. Usulan: `GET /attendance/sync-down?event_id=X&since=...` mengembalikan data attendance yang berubah sejak waktu tertentu (untuk device offline).

## Sync Up

Belum ada. Usulan: `POST /attendance/sync-up` menerima batch scan check-in/check-out dari device offline lalu memprosesnya lewat `AttendanceService`.

## Offline Mode

Belum didukung. Persyaratan agar mendukung offline:

- Device menyimpan antrean scan lokal, lalu mengirimkan via `sync-up`.
- Server harus idempotent (menerima double-send tanpa error) — perlu cek duplikat check-in/check-out.
- Endpoint `sync-down` menyediakan data delta.

## Security

- Semua endpoint attendance memakai `auth:sanctum`.
- Validasi manual di controller (`checkIn`/`checkOut`): `event_id` dan `participant_id` wajib `exists`.
- `scan` memvalidasi `qr_code` required.
- Otorisasi: secara default user bisa check-in peserta sendiri; untuk admin, role `admin_full_access`/`admin_laman` (route web) — di API belum ada cek role.
- `scanned_by` (id user yang scan) sebaiknya dicatat ke `attendance_logs`.

## Controller

`app/Http/Controllers/API/AttendanceController.php`:

| Method | Deskripsi | Status |
|---|---|---|
| `checkIn(Request)` | Validasi + `AttendanceService::checkIn` | ✅ |
| `checkOut(Request)` | Validasi + `AttendanceService::checkOut` | ✅ |
| `byEvent(int $eventId)` | Peserta + attendance per event | ✅ |
| `scan(Request)` | Decode QR | ✅ |
| `report()` | Laporan kehadiran | ✅ |
| `syncUp(Request)` | Proses batch absen offline (idempotent) | ✅ |
| `syncDown(Request)` | Ambil delta attendance sejak waktu tertentu | ✅ |

## Validation

- `check-in`: `event_id` required exists:events,id; `participant_id` required exists:participants,id; `method` in `qr_code,manual,self_scan`; `latitude`/`longitude` numeric nullable.
- `check-out`: `event_id` & `participant_id` required exists.
- `scan`: `qr_code` required string.
- Usulan tambah: gunakan Form Request terpisah (`AttendanceCheckInRequest`, `AttendanceScanRequest`) agar konsisten dengan arsitektur.

## Response

`POST /attendance/check-in` → `200`:

```json
{ "message": "Check-in berhasil" }
```

`POST /attendance/check-out` → `200`:

```json
{ "message": "Check-out berhasil" }
```

`GET /attendance/{eventId}` → `200`:

```json
{
  "data": [
    {
      "...": "event_participant",
      "attendance": { "check_in_time": "...", "check_out_time": "...", "status": "present" }
    }
  ]
}
```

`POST /attendance/scan` → `200`:

```json
{ "data": { "event_id": 1, "participant_id": 2, "hash": "abc12345" } }
```

## Testing

- Check-in sukses → `present`, `check_in_time` terisi, `is_attended=true`.
- Check-in peserta tidak terdaftar → `422`.
- Check-in ganda → `422 "Peserta sudah melakukan check-in."`
- Check-out tanpa check-in → `422 "Peserta belum melakukan check-in."`
- Scan QR valid → `200`; QR tidak valid → `422`.
- Tanpa token → `401`.
- `GET /attendance/report` (setelah ditambahkan).

## Checklist

- [x] Tambahkan endpoint `GET /attendance/report` di API.
- [x] Implementasikan `report()` di `AttendanceController` + service/repository.
- [x] Isi tabel `attendance_logs` pada setiap check-in/check-out (audit).
- [x] Pertimbangkan endpoint `sync-up` / `sync-down` untuk offline mode.
- [x] Pindahkan validasi ke Form Request.
- [x] Tambahkan test feature.
