# Profile Revision

## Tujuan

Menyediakan endpoint profil yang lengkap untuk pengguna aplikasi (participant): melihat profil, memperbarui data pribadi, dan mengunggah foto/avatar — sesuai fitur yang dibutuhkan aplikasi mobile.

## Existing API

Endpoint yang saat ini mendekati profil:

```php
GET /api/v1/auth/me                     // AuthController::me — user + participants (auth:sanctum)
GET /api/v1/participants/{id}           // ParticipantController::show (auth:sanctum)
PUT /api/v1/participants/{id}           // ParticipantController::update (auth:sanctum)
```

- `GET /auth/me` mengembalikan user login beserta relasi `participants`.
- `GET/PUT /participants/{id}` bekerja pada data peserta; tidak ada pembatasan agar hanya pemilik data yang bisa mengakses.

## Missing API

| Endpoint | Fungsi | Status |
|---|---|---|
| `GET /api/v1/profile` | Profil lengkap user login (user + participant + membership) | ❌ |
| `PUT /api/v1/profile` | Update profil user login | ❌ |
| `POST /api/v1/profile/photo` | Upload foto profil / avatar | ❌ |

Belum ada `ProfileController` di `app/Http/Controllers/API/` (tidak ada file).

## Route

Usulan rute baru di `routes/api.php` (dalam grup `auth:sanctum`):

```php
Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);
Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
```

## Controller

Perlu dibuat baru: `app/Http/Controllers/API/ProfileController.php`.

Usulan struktur:

| Method | Fungsi |
|---|---|
| `show()` | Mengembalikan `UserResource`/`ParticipantResource` dari user login |
| `update(Request)` | Update user + participant (email, name, phone, gender, DOB, address, dll.) |
| `uploadPhoto(Request)` | Upload avatar (disimpan via `ImageHelper::upload`, update `users.avatar`) |

## Upload Photo

- Kolom: `users.avatar` (nullable string).
- Helper: `App\Helpers\ImageHelper::upload($file, 'avatars')` → menyimpan ke disk `public`.
- Validasi: `avatar` → required, image, max:2048 (mengikuti pola validasi image lain di proyek).

## Update Profile

- Update `name`, `email` pada `users`.
- Update data pribadi pada `participants` (phone, gender, date_of_birth, address, emergency_contact, emergency_phone, medical_conditions, blood_type, jersey_size).
- Email harus unik (`Rule::unique('users')->ignore(user->id)` dan `Rule::unique('participants')->ignore(participant->id)`), mengikuti pola `ParticipantRequest`.

## Storage

- Disk `public` (`storage/app/public`), path di bawah `uploads/` atau `avatars/`.
- URL diakses via `Storage::disk('public')->url($path)` (`ImageHelper::getUrl`).
- `php artisan storage:link` diperlukan untuk mengakses file via URL.

## Validation

Usulan `UpdateProfileRequest` (atau modifikasi `ParticipantRequest`):

- `name` → required, string, max:255
- `email` → required, email, unique (users & participants, ignore self)
- `phone` → nullable, string, max:20
- `gender` → nullable, in:male,female
- `date_of_birth` → nullable, date
- `address` → nullable, string
- `emergency_contact`, `emergency_phone`, `medical_conditions`, `blood_type`, `jersey_size` → nullable sesuai tipe

## Response

`GET /api/v1/profile` → `200`:

```json
{
  "data": {
    "user": { "id": 1, "name": "...", "email": "...", "avatar": "...", "role": "participant" },
    "participant": { "...": "participant + membership" }
  }
}
```

`PUT /api/v1/profile` → `200`:

```json
{ "data": { "...": "profil terbaru" }, "message": "Profil berhasil diupdate" }
```

`POST /api/v1/profile/photo` → `200`:

```json
{ "data": { "avatar": "uploads/avatars/xxx.png" }, "message": "Foto profil berhasil diupload" }
```

## Error

- Belum login → `401`.
- User tidak punya profil peserta → `404`.
- Validasi gagal (email duplikat, file bukan gambar, dll.) → `422`.

## Testing

- `GET /profile` tanpa token → `401`.
- `GET /profile` mengembalikan data user login.
- `PUT /profile` update data pribadi → tersimpan.
- Update email ke email yang sudah dipakai → `422`.
- Upload avatar → file tersimpan, `users.avatar` terisi.
- Upload file non-gambar / > 2MB → `422`.

## Checklist

- [ ] Buat `ProfileController` (show, update, uploadPhoto).
- [ ] Tambahkan 3 route profile di grup auth.
- [ ] Buat request validation (`UpdateProfileRequest`).
- [ ] Integrasikan `ImageHelper` untuk upload avatar.
- [ ] Gunakan Resource untuk response.
- [ ] Tambahkan test feature.
