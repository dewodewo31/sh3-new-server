# 10 — Attendance & QR Module

QR Code absen check-in & check-out untuk participant event, plus sinkronisasi offline (offline-capable).

Ada **dua tabel utama**:

- `attendances` — catatan kehadiran per `event_participant` (status & waktu).
- `attendance_logs` — riwayat audit per scan (check-in/check-out).

## Database

### Tabel `attendances`

```sql
CREATE TABLE attendances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_participant_id INT NOT NULL,
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    status ENUM('present','absent','late','left_early') DEFAULT 'absent',
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    check_in_method ENUM('qr_code','manual','self_scan') DEFAULT 'qr_code',
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_participant_id) REFERENCES event_participants(id) ON DELETE CASCADE
);
```

### Tabel `attendance_logs` (audit log)

```sql
CREATE TABLE attendance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    participant_id INT NOT NULL,
    type ENUM('check_in','check_out') NOT NULL,
    scan_time DATETIME NOT NULL,
    scanned_by INT NULL,               -- user_id atau null
    qr_code VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (event_id, participant_id, type),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

### Kolom denormalisasi di `event_participants`

- `is_attended` (bool)
- `check_in_at`, `check_out_at`
- `qr_code`
- `registration_type` (free|paid|membership)
- `payment_status` (pending|confirmed|rejected|refunded)

## QR Code Format

Generator: `app/Services/QRCodeService.php`.

- Format QR tersimpan: `SH3-{event_id}-{participant_id}-{8 karakter acak}`.
- `QRCodeService::generate()` menghasilkan string tersebut, menyimpannya ke `event_participants.qr_code`.
- `QRCodeService::decode()` memecah string menjadi 4 bagian; valid hanya jika bagian pertama adalah `SH3`.

## Flow Scan

```
Scan QR → decode(event_id, participant_id) → cari registrasi
  → Belum check-in → Check-in (status=present, is_attended=true)
  → Sudah check-in → Check-out
```

- `checkIn()`: memvalidasi peserta terdaftar, menolak double check-in, membuat/meperbarui `attendances` + update `event_participants`, menulis `attendance_logs`, dan notifikasi admin.
- `checkOut()`: mensyaratkan sudah check-in, lalu set `check_out_time`.
- `scanQRCode()`: validasi QR melalui `QRCodeService::decode()`.

## Offline Sinkronisasi

`AttendanceService` mendukung device offline (mode tanpa koneksi):

| Endpoint | Deskripsi |
|----------|-----------|
| `POST /api/v1/attendance/sync-up` | Kirim data catatan → diproses check-in/out + OTS (payment) di server |
| `GET /api/v1/attendance/sync-down` | Unduh data attendance untuk digunakan offline |

> **Route publik (2026-08-15):** `sync-up` & `sync-down` dipindahkan dari grup `auth:sanctum`
> ke grup **publik** di `routes/api.php` (device OTS offline tidak memiliki token).

### `sync-up` (alur baru 2026-08-15)

`POST /api/v1/attendance/sync-up` menerima **dua array**:

| Field | Isi |
|-------|-----|
| `attendances` | attendance reguler: `{event_id, qr_code, hash_id, check_in_time, check_out_time}` |
| `ots_registrations` | pendaftaran OTS (On-The-Spot): `{event_id, hash_id, member_name, check_in_time, check_out_time}` |

Proses di dalam `DB::transaction`:

1. **Attendance reguler** — lookup `EventParticipant` via `qr_code+event_id`, fallback via
   `hash_id`; update `check_in_at/check_out_at/is_attended`; update/create `attendances`
   (tanpa duplikasi — attendance yang sudah punya `check_out_time` hanya di-update
   `check_in_time` + `status=present`).
2. **OTS** — participant aggregator `MANUAL_OTS_AGGREGATOR` dibuat otomatis untuk OTS manual;
   OTS member dicari/dibuat via `hash_id`. Satu `EventParticipant` per participant per event
   (`firstOrCreate`, `qr_code = OTS-{hash_id}EV{event_id}`), `total_events_participated`
   increment hanya sekali per event, **Payment dibuat otomatis** (`INV-OTS-{random8}`,
   `payment_type=event_registration`, `payment_method=cash`, `status=confirmed`), dan
   attendance di-update (dedup) atau dibuat.

Response:

```json
{
  "success": true,
  "message": "Event attendance synced successfully",
  "synced_attendance_count": 0,
  "synced_ots_count": 5
}
```

### `syncDown`

`syncDown` mengembalikan daftar `{ event_id, participant_id, status, check_in_time, check_out_time, check_in_method, latitude, longitude, notes, updated_at }`.

> `AttendanceService::syncUp(array $records)` (versi per-record: `processed`, `skipped`,
> `details[]`) masih tersedia namun endpoint API kini memakai logika OTS di
> `AttendanceController::syncUp()` yang menangani array `attendances` + `ots_registrations`.

> **Catatan sinkronisasi (fix 2026-08-14):** parameter `since` (format ISO, umumnya UTC dari
> client) di-parse dan dikonversi ke timezone aplikasi (`Asia/Jakarta`) sebelum dibandingkan
> dengan `updated_at` yang tersimpan dalam timezone lokal. Tanpa konversi, selisih zona waktu
> (mis. +7 jam) menyebabkan `sync-down` mengirim ulang data yang seharusnya sudah ter-sync.

## API Endpoints

Semua endpoint absensi butuh auth (`auth:sanctum`).

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/attendance/check-in` | Check-in (event_id, participant_id/qr_code, latitude, longitude, method) |
| POST | `/api/v1/attendance/check-out` | Check-out |
| POST | `/api/v1/attendance/scan` | Scan QR code |
| POST | `/api/v1/attendance/sync-up` | Sinkronisasi offline ke server |
| GET | `/api/v1/attendance/sync-down` | Unduh data untuk offline |
| GET | `/api/v1/attendance/report` | Laporan absensi (statistik) |
| GET | `/api/v1/attendance/{eventId}` | Daftar absensi per event |

## Route Admin (Web)

| Method | Route | Role |
|--------|-------|------|
| GET | `/admin/attendance/event/{eventId}` | Full Access, Laman |
| GET | `/admin/attendance/report` | Full Access, Laman |
| GET | `/admin/attendance/scan` | Full Access, Laman |
| POST | `/admin/attendance/scan` | Full Access, Laman |
| POST | `/admin/attendance/event-participant/{id}/generate-qr` | Full Access, Laman |

## File Terkait

- `app/Services/AttendanceService.php` — check-in/out, scan, report, syncUp/syncDown
- `app/Services/QRCodeService.php` — generate & decode QR
- `app/Repositories/AttendanceRepository.php` — query attendance & sync
- `app/Models/Attendance.php`, `app/Models/AttendanceLog.php`, `app/Models/EventParticipant.php`
- `app/Http/Controllers/Admin/AttendanceController.php`, `app/Http/Controllers/API/AttendanceController.php`

## Catatan

- Scanner admin responsif: fps 20, qrbox dinamis 80% viewfinder, dukungan `BarcodeDetector` native, cooldown antar-scan 1,5 detik.