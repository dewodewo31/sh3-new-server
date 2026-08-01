# Revision 1

Tujuan:
Menyamakan fitur API Routes dengan API Routes terdahulu.

Urutan Implementasi

1. Authentication
2. Events
3. Merchandise
4. Organization
5. Attendance
6. Profile
7. Database
8. API Response
9. Testing

## Ringkasan Temuan

Dokumentasi analisis telah dibuat berdasarkan kode aktual. Endpoint yang hilang/tidak cocok dibandingkan dengan API terdahulu (`docs/readme.md` → *API Endpoints*):

| Modul | Endpoint Terdahulu | Status |
|---|---|---|
| Auth | `POST /auth/refresh`, `POST /auth/forgot-password`, `POST /auth/reset-password` | ✅ Implemented |
| Event | `POST /events`, `PUT /events/{id}`, `DELETE /events/{id}` | ❌ Method ada, route belum |
| Event | `GET /events/{id}/participants` | ❌ Method ada, route belum |
| Event | `GET /events/{id}/qr` | ❌ Belum ada |
| Merchandise | `POST /merchandise/order` | ❌ Route ada, method controller belum diimplementasikan |
| Merchandise | `GET /merchandise/orders` | ❌ Belum ada |
| Payment | `POST /payments/confirm` | ✅ Implemented |
| Attendance | `GET /attendance/report` | ✅ Implemented (+ `sync-up`/`sync-down`) |
| Profile | `GET/PUT /profile`, `POST /profile/photo` | ❌ Belum ada |
| Organization | tree, stats, search, filter year/level | ❌ Belum ada |

Progress

- [x] Authentication — dokumen `01-authentication.md`
- [x] Events — dokumen `02-events.md`
- [x] Merchandise — dokumen `03-merchandise.md`
- [x] Organization — dokumen `04-organization.md`
- [x] Attendance — dokumen `05-attendance.md`
- [x] Profile — dokumen `06-profile.md`
- [x] Database — dokumen `08-database-changes.md`
- [x] API Response — dokumen `09-api-response-standard.md`
- [x] Testing — dokumen `10-testing.md`

Checklist implementasi bertahap ada di `07-migration-checklist.md`.

## Daftar Dokumen

| File | Isi |
|---|---|
| `01-authentication.md` | Analisis & rencana endpoint auth |
| `02-events.md` | Analisis & rencana endpoint event |
| `03-merchandise.md` | Analisis & rencana endpoint merchandise |
| `04-organization.md` | Analisis & rencana endpoint organization |
| `05-attendance.md` | Analisis & rencana endpoint attendance & QR |
| `06-profile.md` | Rencana endpoint profil |
| `07-migration-checklist.md` | Checklist migrasi bertahap |
| `08-database-changes.md` | Perubahan database |
| `09-api-response-standard.md` | Standar respons API |
| `10-testing.md` | Rencana pengujian |
