# API Response Standard

Standar respons JSON untuk seluruh endpoint API (`/api/v1/*`) agar konsisten. Standar ini wajib dipakai pada semua implementasi Revision 1.

## Success Format

Struktur dasar:

```json
{
  "message": "Deskripsi singkat aksi",
  "data": { "...": "payload" }
}
```

Pola yang berlaku saat ini:

- Endpoint list/detail → `{ "data": ... }`.
- Aksi mutasi → `{ "message": "..." }` atau `{ "data": ..., "message": "..." }`.
- Daftar resource → `data` berisi array; gunakan Laravel Resource (`JsonResource::collection`) bila memungkinkan.

## Error Format

Struktur error umum:

```json
{
  "message": "Deskripsi error",
  "errors": {
    "field": ["Pesan validasi per field"]
  }
}
```

Aturan:

- Jangan pernah menampilkan stack trace pada production (`APP_DEBUG=false`).
- Untuk error yang bukan validasi, gunakan `message` saja.

## Validation Error

Validasi menggunakan `FormRequest` atau `ValidationException` akan menghasilkan status `422` dengan format bawaan Laravel:

```json
{
  "message": "Email atau password salah.",
  "errors": { "email": ["Email atau password salah."] }
}
```

Pola `ValidationException::withMessages()` dipakai di service (mis. kuota penuh, sudah terdaftar, stok tidak cukup) — pesan per field dengan status `422`.

## Authentication Error

- Tanpa token / token tidak valid → `401 Unauthorized`:

```json
{ "message": "Unauthenticated." }
```

- Token tidak punya akses (role) → `403 Forbidden` (untuk admin/web) atau `404` bila menyembunyikan resource milik user lain.

## Pagination

- `ParticipantController::index()` menggunakan `paginate(15)` dari `BaseRepository::paginate()` → respons berisi `data`, `links`, `current_page`, `first_page_url`, `from`, `last_page`, `per_page`, `to`, `total` (struktur bawaan Laravel).

```json
{
  "current_page": 1,
  "data": [],
  "per_page": 15,
  "total": 0
}
```

- Endpoint lain saat ini mengembalikan koleksi tanpa pagination.

## Meta

Kontrak agar konsisten antar endpoint — **sudah diterapkan** melalui middleware `EnsureApiMeta` (didaftarkan pada grup `api` di `bootstrap/app.php`):

```json
{
  "data": {},
  "meta": {
    "timestamp": "2026-08-01T00:00:00Z",
    "request_id": "uuid"
  }
}
```

Aturan penerapan:

- Semua respons JSON di bawah `/api/*` otomatis mendapat `meta` berisi `timestamp` (ISO 8601) dan `request_id` (UUID).
- `request_id` diambil dari header `X-Request-Id` bila dikirim klien; jika tidak, di-generate UUID baru.
- `request_id` yang sama di-echo ke header respons `X-Request-Id` untuk keperluan tracing.
- Berlaku juga untuk error responses (422, 401, 404, dst.) karena middleware menangkap exception dan menyuntikkan `meta` sebelum respons dikirim.

## HTTP Status Code

| Kode | Situasi |
|---|---|
| 200 | Sukses (list, detail, update, aksi) |
| 201 | Resource dibuat (register, store, subscribe, order) |
| 401 | Belum autentikasi / token tidak valid |
| 403 | Tidak punya akses (role) |
| 404 | Resource tidak ditemukan / resource milik user lain |
| 422 | Validasi gagal / business rule gagal |
| 429 | Rate limit / throttling |

## Naming Convention

- Key response dalam **camelCase**: `createdAt`, `membershipType`, `totalEvents`.
- Nama field mengikuti nama kolom DB / resource saat ini (mis. `membership_end_date` di `ParticipantResource` — perlu dibakukan ke camelCase).
- Waktu response pakai format yang konsisten (lihat Date Format).
- Pesan `message` dalam Bahasa Indonesia (konsisten dengan controller yang ada).

## Date Format

- Kolom bertipe `date` → `Y-m-d` (contoh `"2026-07-31"`).
- Kolom bertipe `datetime` / `timestamp` → ISO 8601 (contoh `"2026-08-01T07:30:00.000000Z"`).
- `NotificationController::format` memakai `created_at_raw` ISO 8601 + `created_at` `diffForHumans`.

## File Upload Response

Usulan untuk upload (avatar, payment proof, banner):

```json
{
  "data": { "path": "uploads/payments/xxx.png", "url": "https://host/storage/uploads/payments/xxx.png" },
  "message": "Upload berhasil"
}
```

- Gunakan `ImageHelper::upload` (disk `public`) dan `ImageHelper::getUrl` untuk URL.
- Validasi ukuran sesuai kebutuhan (avatar max 2MB, bukti bayar max 5MB).

## Checklist

- [x] Semua endpoint baru memakai `{ data, message }` / `{ message }` yang konsisten.
- [x] Error validasi selalu `422` dengan `errors`.
- [x] Unauthorized selalu `401`, forbidden `403`, not found `404`.
- [ ] Gunakan Resource untuk semua payload resource.
- [ ] Bakukan nama field ke camelCase.
- [x] Tambahkan `meta` (timestamp, request_id) — via middleware `EnsureApiMeta` pada grup `api`.
- [x] Dokumentasikan setiap perubahan format di file ini.
