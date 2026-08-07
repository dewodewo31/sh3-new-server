# 02 — Participant Module

Pengelolaan data peserta lari SH3 beserta membership.

## Tabel `participants`

```sql
CREATE TABLE participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    gender ENUM('male', 'female'),
    date_of_birth DATE,
    address TEXT,
    emergency_contact VARCHAR(255),
    emergency_phone VARCHAR(20),
    medical_conditions TEXT,
    blood_type VARCHAR(5),
    jersey_size ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL'),
    membership_type VARCHAR(50) DEFAULT 'none',       -- key dari membership_plans
    membership_start_date DATE,
    membership_end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    total_events_participated INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

> **Catatan:** `membership_type` adalah VARCHAR(50), bukan ENUM. Nilainya adalah `key` dari tabel `membership_plans`. Migration yang mengubah ENUM → string: `2026_07_31_100001_change_membership_type_to_string.php`.

## Relasi

- `user()` — belongsTo User
- `membershipHistories()` — hasMany MembershipHistory
- `membershipPlan()` — belongsTo MembershipPlan via `membership_type` => `key`
- `eventParticipants()` — hasMany EventParticipant
- `payments()` — hasMany Payment
- `merchandiseOrders()` — hasMany MerchandiseOrder
- `organizationMembers()` — hasMany OrganizationMember
- `isMembershipActive()` — helper: true jika `membership_type != none` dan `membership_end_date >= hari ini`
- `membershipTypeLabel()` — label dari plan name (fallback: title case)

## hash_id

`ParticipantResource` menyertakan `hash_id`:
- Member (membership aktif): `%04d` (contoh `0022`)
- Non-member: `NM-%04d` (contoh `NM-0044`)
- Dihasilkan oleh accessor `hashId()` pada model Participant

## Registrasi Peserta

- **Via API**: `POST /api/v1/auth/register` — membuat `User` (role `participant`) + `Participant` dalam satu transaksi.
  - Field `username` bersifat **opsional**; jika tidak disertakan, sistem akan meng-generate
    otomatis berdasarkan `name` (format: `slug_name`, unik dengan suffix angka jika duplikat).
  - Aturan username: 3–30 karakter, hanya huruf/karis/underscore.
  - `email` tetap **wajib** (dipakai untuk notifikasi, verifikasi, reset password, komunikasi).
- **Via admin**: CRUD `/admin/participants`, role `admin_full_access` atau `admin_member`.

## Login Peserta (API)

Peserta login melalui API menggunakan **username**, bukan email.

```
POST /api/v1/auth/login
Content-Type: application/json

{
    "username": "johndoe",
    "password": "secret123"
}
```

- **Request class:** `LoginRequest` — memvalidasi `username` (required, string) + `password` (required, string).
- **Service:** `AuthService::login()` — lookup user via `UserRepository::findByUsername()`,
  lalu memastikan `role = 'participant'` dan `is_active = true`.
- **Response:** Sanctum token + user data (termasuk `username`).
- **Error scenarios:**
  - Username tidak ditemukan atau password salah → 422 `"Username atau password salah."`
  - Akun non-aktif (`is_active = false`) → 422 `"Akun Anda telah dinonaktifkan."`
  - Admin/user non-participant mencoba login via API → 422 `"Akun ini bukan peserta."`
- **Email** tidak dipakai untuk login peserta. Email tetap untuk notifikasi, verifikasi, dan reset password.

## Membership

Detail lengkap ada di `docs/12 — Membership Module.md`. Ringkasan:

- 3 tipe membership: `tahunan`, `setengah_tahun`, `mingguan`.
- Kolom `membership_type/start/end` pada peserta = status aktif saat ini.
- Riwayat lengkap disimpan di `membership_histories`.
- Admin bisa memberi langsung (grant) atau peserta membeli via API lalu dikonfirmasi bendahara.

## API

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/participants` | Daftar peserta (pagination) |
| GET | `/api/v1/participants/{id}` | Detail peserta + membership histories |
| PUT | `/api/v1/participants/{id}` | Update peserta |
| GET | `/api/v1/participants/{id}/events` | Event yang diikuti peserta |
| GET | `/api/v1/participants/{id}/attendance` | Absensi peserta |

## File Terkait

- `app/Http/Controllers/Admin/ParticipantController.php`
- `app/Http/Controllers/API/ParticipantController.php`
- `app/Repositories/ParticipantRepository.php`
- `app/Models/Participant.php`
- `app/Http/Resources/ParticipantResource.php`
- `app/Http/Requests/ParticipantRequest.php`
- `database/seeders/ParticipantSeeder.php`
