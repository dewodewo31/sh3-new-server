# Login & Akses

SH3 memiliki **dua jalur login** yang berbeda:

| Pengguna | Cara login | Halaman/Endpoint |
|----------|-----------|------------------|
| Admin & staf (semua role non-participant) | Email + password (sesi web) | `/login` |
| Peserta | Username + password (token API) | `POST /api/v1/auth/login` |

---

## 1. Login Admin (Panel Web)

**Cara penggunaan:**

1. Buka `https://<domain>/login`.
2. Isi **Email** dan **Password**, lalu klik **Login**.
3. Anda akan diarahkan ke Dashboard (`/admin/dashboard`).
4. Menu sidebar tampil sesuai **role** akun.

**Catatan:**

- Role `sponsor` hanya melihat menu **Sponsors**; setelah login langsung diarahkan
  ke `/admin/sponsors`.
- Akun dengan `is_active = false` tidak dapat login.
- Role `participant` **tidak bisa** login ke panel admin.

**Logout:** klik menu profil di kanan atas → **Logout**.

---

## 2. Login Peserta (API)

Peserta login memakai **username**, bukan email.

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "username": "budi_santoso",
  "password": "password"
}
```

Response `200` berisi `token` (Sanctum) yang dipakai sebagai
`Authorization: Bearer <token>` untuk endpoint terproteksi.

**Kemungkinan error:**

| Pesan | Penyebab |
|-------|----------|
| "Username atau password salah." | Username tidak ada / password keliru |
| "Akun Anda telah dinonaktifkan." | `is_active = false` |
| "Akun ini bukan peserta." | Akun admin/staf mencoba login via API peserta |

---

## 3. Registrasi Peserta Baru

Peserta mendaftar lewat frontend/API. Password bersifat **opsional** (bila kosong
sistem membuat password acak). Username juga opsional dan akan dibuat otomatis dari
nama bila kosong.

```http
POST /api/v1/auth/register
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "phone": "08123456789",
  "username": "budi_santoso",
  "password": "rahasia123",
  "password_confirmation": "rahasia123"
}
```

---

## 4. Lupa / Reset Password (Peserta)

1. **Lupa password** — `POST /api/v1/auth/forgot-password` dengan `email`.
   Sistem mengirim tautan reset ke email peserta.
2. **Reset password** — `POST /api/v1/auth/reset-password` dengan token dari email,
   `email`, `password`, dan `password_confirmation`.

> Peserta juga dapat login/reset memakai **Member ID** (`3950`) atau **Non-Member
> Code** (`NM0001`). Lihat [participant.md](participant.md).

---

## 5. Logout & Refresh Token (API)

| Endpoint | Fungsi |
|----------|--------|
| `POST /api/v1/auth/logout` | Cabut token aktif |
| `POST /api/v1/auth/refresh` | Terbitkan token baru |
| `GET /api/v1/auth/me` | Profil user + data peserta yang login |
