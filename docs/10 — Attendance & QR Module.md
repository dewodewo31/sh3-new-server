# 10 — Attendance & QR Module

QR Code absen check-in & check-out untuk participant event.

```sql
CREATE TABLE attendance_logs (
  id, event_id, participant_id,
  type (check_in|check_out),
  scan_time, scanned_by, qr_code,
  latitude, longitude, ip_address
);
```

# QR Code Format

- Format: SH3-{event_id}-{participant_id}-{random_hash}

# Scan Flow

- Scan → Cek registrasi valid → Cek event berlangsung
- Belum check-in → Check-in (attendance=TRUE)
- Sudah check-in → Check-out

# API & Export

- POST /api/events/{id}/attendance/scan
- GET /events/{id}/attendance/export/pdf