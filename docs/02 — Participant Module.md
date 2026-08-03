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
    membership_type ENUM('tahunan', 'setengah_tahun', 'mingguan', 'none') DEFAULT 'none',
    membership_start_date DATE,
    membership_end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    total_events_participated INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Registrasi Peserta

- **Via API**: `POST /api/v1/auth/register` — membuat `User` (role `participant`) + `Participant` dalam satu transaksi.
- **Via admin**: CRUD `/admin/participants`.

Setiap `User` bisa memiliki banyak `Participant` (hasMany), namun flow saat ini menggunakan peserta pertama (`user->participants()->first()`).

## Relasi

- `user()` — belongsTo User
- `membershipHistories()` — hasMany MembershipHistory
- `eventParticipants()` — hasMany EventParticipant
- `payments()` — hasMany Payment
- `merchandiseOrders()` — hasMany MerchandiseOrder
- `organizationMembers()` — hasMany OrganizationMember
- `isMembershipActive()` — helper: true jika `membership_type != none` dan `membership_end_date >= hari ini`

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
