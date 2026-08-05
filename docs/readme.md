# **Sistem Manajemen Event Lari - SH3 Running Club**

## **Daftar Isi**

1. Arsitektur Sistem
2. Modul User Management
3. Modul Participant & Membership
4. Modul Event
5. Modul Kategori Event
6. Modul Merchandise
7. Modul Gallery
8. Modul Payments
9. Modul Sponsor
10. Modul Struktur Organisasi
11. Modul Attendance & QR Code
12. Modul Membership
13. API Endpoints
14. Tech Stack
15. Modul Notification
16. Changelog & Perbaikan (lihat `14 — Changelog & Fixes.md`)

---

## **Arsitektur Sistem**

### **Layered Architecture**

```
┌─────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                 │
│  - Blade Views (Admin Dashboard)                    │
│  - API Responses (Mobile/Web Client)                │
│  - Middleware & Authentication                       │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│                   BUSINESS LAYER                    │
│  - Controllers (Request Handler)                    │
│  - Services (Business Logic)                        │
│  - DTOs (Data Transfer Objects)                     │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│                 DATA ACCESS LAYER                   │
│  - Models (Eloquent ORM)                            │
│  - Repositories (Database Queries)                  │
│  - Migrations & Seeders                             │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│                DATABASE LAYER                       │
│  - MySQL Database                                   │
│  - Redis (Cache/Queue/Session)                      │
└─────────────────────────────────────────────────────┘
```

### **Struktur Folder**

```
app/
├── DTOs/
│   ├── EventDTO.php
│   ├── ParticipantDTO.php
│   ├── PaymentDTO.php
│   └── UserDTO.php
├── Helpers/
│   ├── QRCodeHelper.php
│   └── ImageHelper.php
├── Http/
│   ├── Controllers/
│   │   ├── API/
│   │   │   ├── AttendanceController.php
│   │   │   ├── AuthController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── EventController.php
│   │   │   ├── GalleryController.php
│   │   │   ├── MembershipController.php
│   │   │   ├── MerchandiseController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── OrganizationController.php
│   │   │   ├── ParticipantController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ProfileController.php
│   │   │   └── SponsorController.php
│   │   └── Admin/
│   │       ├── AttendanceController.php
│   │       ├── CategoryController.php
│   │       ├── DashboardController.php
│   │       ├── EventController.php
│   │       ├── GalleryController.php
│   │       ├── MembershipController.php
│   │       ├── MembershipPlanController.php
│   │       ├── MerchandiseController.php
│   │       ├── NotificationController.php
│   │       ├── OrganizationController.php
│   │       ├── ParticipantController.php
│   │       ├── PaymentController.php
│   │       ├── SponsorController.php
│   │       └── UserController.php
│   ├── Middleware/
│   │   ├── AdminMiddleware.php
│   │   ├── EnsureApiMeta.php
│   │   └── RoleMiddleware.php
│   ├── Requests/               (24 Form Requests)
│   └── Resources/
│       ├── CategoryResource.php
│       ├── EventResource.php
│       ├── EventScheduleResource.php
│       ├── MembershipHistoryResource.php
│       ├── MerchandiseResource.php
│       ├── OrganizationMemberResource.php
│       ├── ParticipantResource.php
│       ├── SponsorResource.php
│       └── UserResource.php
├── Models/
│   ├── Attendance.php
│   ├── AttendanceLog.php
│   ├── Category.php
│   ├── Event.php
│   ├── EventParticipant.php
│   ├── EventSchedule.php
│   ├── Gallery.php
│   ├── GalleryAlbum.php
├── Notifications/
│   └── AdminNotification.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── RepositoryServiceProvider.php
├── Repositories/               (15 Repositories)
├── Services/
│   ├── AttendanceService.php
│   ├── AuthService.php
│   ├── EventService.php
│   ├── MembershipService.php
│   ├── MerchandiseService.php
│   ├── NotificationService.php
│   ├── PaymentService.php
│   ├── QRCodeService.php
│   ├── SidebarService.php
│   └── UserService.php
```

Models lanjutan: `MembershipHistory`, `MembershipPlan`, `Merchandise`, `MerchandiseOrder`, `OrganizationMember`, `Participant`, `Payment`, `Sponsor`, `User`, `UserActivityLog`.

---

## **Modul User Management**

### **Deskripsi**

Modul untuk mengelola semua pengguna sistem dengan pembagian role dan akses.

### **Tabel: `users`**

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM(
        'admin_full_access', 'admin_laman', 'admin_member',
        'admin_bnh', 'organizer', 'bendahara', 'sponsor',
        'merchandise', 'participant'
    ) NOT NULL DEFAULT 'participant',
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Sumber otoritatif: migration `0001_01_01_000000_create_users_table.php` dan `2026_07_30_122526_add_participant_role_to_users.php`.

### **Role & Permission Matrix**

| Role | Events | Merchandise | Gallery | Payments | Participants | Sponsors | Dashboard |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Admin Full Access | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin Laman | ✅ | ✅ | ✅ | ✅ | ⚠️ | ⚠️ | ✅ |
| Admin Member | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ⚠️ |
| Admin BNH | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ⚠️ |
| Organizer | ✅ | ❌ | ❌ | ⚠️ | ⚠️ | ❌ | ✅ |
| Bendahara | ⚠️ | ❌ | ❌ | ✅ | ⚠️ | ❌ | ✅ |
| Sponsor | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ⚠️ |
| Merchandise | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ⚠️ |

**Keterangan:**
- ✅ = Full Access (CRUD)
- ⚠️ = Read Only
- ❌ = No Access

> **Penting:** matrix di atas adalah panduan umum. Otorisasi aktual mengikuti grup middleware role di `routes/web.php` dan `routes/api.php`. Lihat `docs/17 — Implementation Sync.md` untuk detail route per role.

### **Seeder Default Users**

```php
// Database/Seeders/UserSeeder.php
public function run()
{
    User::create(['name' => 'Admin Full Access', 'email' => 'admin.full@sh3.com',  'password' => Hash::make('password'), 'role' => 'admin_full_access']);
    User::create(['name' => 'Admin Laman',      'email' => 'admin.laman@sh3.com',  'password' => Hash::make('password'), 'role' => 'admin_laman']);
    User::create(['name' => 'Admin Member',     'email' => 'admin.member@sh3.com', 'password' => Hash::make('password'), 'role' => 'admin_member']);
    User::create(['name' => 'Admin BNH',        'email' => 'admin.bnh@sh3.com',    'password' => Hash::make('password'), 'role' => 'admin_bnh']);
    User::create(['name' => 'Organizer',        'email' => 'organizer@sh3.com',    'password' => Hash::make('password'), 'role' => 'organizer']);
    User::create(['name' => 'Bendahara',        'email' => 'bendahara@sh3.com',    'password' => Hash::make('password'), 'role' => 'bendahara']);
    User::create(['name' => 'Sponsor',          'email' => 'sponsor@sh3.com',      'password' => Hash::make('password'), 'role' => 'sponsor']);
    User::create(['name' => 'Merchandise',      'email' => 'merchandise@sh3.com',  'password' => Hash::make('password'), 'role' => 'merchandise']);
}
```

### **Authentication Flow**

1. User Login (Email/Password)
2. Middleware Check Role
3. API: Token via Sanctum; Admin: Session-based
4. Token Generated / Session Created
5. Session/Token Management

### **Tabel: `user_activity_logs`**

```sql
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

Log aktivitas via `UserService::logActivity()`; dipanggil saat login, logout, refresh, dan operasi CRUD user.

---

## **Modul Participant & Membership**

### **Deskripsi**

Modul untuk mengelola data peserta lari dengan sistem membership yang fleksibel.

### **Tabel: `participants`**

```sql
CREATE TABLE participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
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
    membership_type VARCHAR(50) DEFAULT 'none',
    membership_start_date DATE,
    membership_end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    total_events_participated INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

> **Catatan:** `membership_type` adalah VARCHAR(50), bukan ENUM (diubah oleh migration `2026_07_31_100001`). Nilai disimpan sebagai `key` dari `membership_plans` (mis. `tahunan`, `setengah_tahun`, `mingguan`).

### **Relasi Participant**

- `user()` — belongsTo User
- `membershipHistories()` — hasMany MembershipHistory
- `membershipPlan()` — belongsTo MembershipPlan via `membership_type` => `key`
- `eventParticipants()` — hasMany EventParticipant
- `payments()` — hasMany Payment
- `merchandiseOrders()` — hasMany MerchandiseOrder
- `organizationMembers()` — hasMany OrganizationMember
- `isMembershipActive(): bool` — true jika `membership_type != none` dan `membership_end_date >= hari ini`
- `membershipTypeLabel(): string` — label dari plan name (fallback: title case)
- `hash_id` accessor: member aktif → `%04d` (contoh `0022`); non-member → `NM-%04d` (contoh `NM-0044`)

### **Participant Features**

1. **Pendaftaran** — Register via API (membuat User role participant + Participant)
2. **Membership** — 3 tipe default (tahunan, setengah_tahun, mingguan); dinamis via membership_plans
3. **History Event** — Riwayat partisipasi
4. **QR Code** — Generate untuk event
5. **Dashboard** — Informasi personal

---

## **Modul Event**

### **Deskripsi**

Modul untuk mengelola event lari yang diselenggarakan.

### **Tabel: `events`**

```sql
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    location VARCHAR(255),
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    image VARCHAR(255),
    banner VARCHAR(255),
    key_points JSON,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    registration_start_date DATETIME NOT NULL,
    registration_end_date DATETIME NOT NULL,
    quota INT,
    price DECIMAL(15,2),
    is_free_for_members BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'publish', 'ongoing', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

Kolom `title` ≠ `name`. Kolom `quota` ≠ `max_participants`. Sumber otoritatif: migration `2024_01_01_000007_create_events_table.php`.

### **Tabel: `event_schedules`**

```sql
CREATE TABLE event_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);
```

### **Tabel: `event_participants`**

```sql
CREATE TABLE event_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    participant_id INT NOT NULL,
    registration_type ENUM('free', 'paid', 'membership') DEFAULT 'free',
    amount DECIMAL(15,2),
    payment_status ENUM('pending', 'confirmed', 'rejected', 'refunded') DEFAULT 'pending',
    is_attended BOOLEAN DEFAULT FALSE,
    check_in_at DATETIME,
    check_out_at DATETIME,
    qr_code VARCHAR(255),
    is_membership_free BOOLEAN DEFAULT FALSE,
    payment_id INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (event_id, participant_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

### **Event Management Features**

1. **Create Event** — Buat event baru
2. **Update Event** — Update informasi event
3. **Publish Event** — Publikasi event (draft → publish)
4. **Cancel Event** — Batalkan event
5. **View Participants** — Lihat peserta
6. **Export Data** — Export peserta ke Excel

### **Status Transitions**

- `draft` → `publish` (via publish action)
- `publish` → `ongoing` (otomatis saat start_date ≤ now ≤ end_date)
- `publish`/`ongoing` → `completed` (otomatis saat end_date < now)
- Any → `cancelled` (manual)

> **Catatan:** Transisi status otomatis via `EventService::updateEventStatus()` sudah diimplementasikan tetapi **belum dijadwalkan** di Laravel Scheduler. Scheduler saat ini kosong (tidak ada scheduled task).

---

## **Modul Kategori Event**

### **Tabel: `categories`**

```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    distance_km DECIMAL(5,2),
    slug VARCHAR(100) UNIQUE NOT NULL,
    banner VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### **Data Kategori**

```
Long Run, Short Run, Major Events, Super Long
```

Seeder menggunakan nama di atas (bukan 5K/10K/21K/42K/Trail seperti versi PRD).

---

## **Modul Merchandise**

### **Tabel: `merchandise`**

```sql
CREATE TABLE merchandise (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(15,2) NOT NULL,
    size_options JSON,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    status ENUM('available', 'sold_out', 'discontinued') DEFAULT 'available',
    created_by INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

> Tidak ada kolom `category`. Status menggunakan `status` (ENUM available/sold_out/discontinued), bukan `is_active`.

### **Tabel: `merchandise_orders`**

```sql
CREATE TABLE merchandise_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    merchandise_id INT NOT NULL,
    participant_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_contact VARCHAR(255) NOT NULL,
    size VARCHAR(255),
    quantity INT NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_id INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (merchandise_id) REFERENCES merchandise(id) ON DELETE RESTRICT,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE RESTRICT
);
```

---

## **Modul Gallery**

### **Tabel: `galleries`**

```sql
CREATE TABLE galleries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    gallery_album_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    type ENUM('image', 'video') DEFAULT 'image',
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (gallery_album_id) REFERENCES gallery_albums(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `gallery_albums`**

```sql
CREATE TABLE gallery_albums (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
);
```

---

## **Modul Payments**

### **Tabel: `payments`**

```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    invoice_number VARCHAR(255) UNIQUE,
    payment_type ENUM('event_registration', 'merchandise', 'membership'),
    paymentable_type VARCHAR(255),
    paymentable_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('transfer', 'cash', 'qris') DEFAULT 'transfer',
    payment_proof VARCHAR(255),
    status ENUM('pending', 'confirmed', 'rejected', 'refunded') DEFAULT 'pending',
    confirmed_by INT,
    paid_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE RESTRICT,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

> **Polymorphic:** `paymentable_type` + `paymentable_id` (morph). Target: `EventParticipant`, `MerchandiseOrder`, `MembershipHistory`.
> Tidak ada kolom `event_id`, `merchandise_id`, atau `transaction_id` individual — ini adalah migration versi awal yang telah digantikan oleh polymorphic design.

### **Payment Flow**

1. Pembayaran dibuat otomatis saat event registration / merchandise order / membership subscribe
2. Status `pending`, menunggu konfirmasi bendahara
3. Bendahara konfirmasi (accept/reject) via `/admin/payments/{id}/confirm` atau `/admin/payments/{id}/reject`
4. Konfirmasi memanggil `PaymentService::confirmPayment()` → `paymentable()->markAsPaid()`

---

## **Modul Sponsor**

### **Tabel: `sponsors`**

```sql
CREATE TABLE sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    website VARCHAR(255),
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    tier ENUM('platinum', 'gold', 'silver', 'bronze', 'media_partner') NOT NULL DEFAULT 'bronze',
    year YEAR,
    sponsorship_value DECIMAL(15,2),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `event_sponsors`**

```sql
CREATE TABLE event_sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    sponsor_id INT NOT NULL,
    package VARCHAR(255),
    value DECIMAL(15,2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (event_id, sponsor_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
);
```

> Sponsor menggunakan `tier` (bukan `sponsor_level`). Migration juga menyertakan kolom `year` dan `sort_order`.

---

## **Modul Struktur Organisasi**

### **Tabel: `organization_members`**

Lihat migration `2024_01_01_000013_create_organization_members_table.php` dan `2026_08_01_000001_add_hierarchy_to_organization_members_table.php` untuk schema aktual.

Fitur:
- Hierarchy parent-child via `parent_id` dan `level`
- Periode aktif: `period_start`, `period_end`
- API publik: tree, stats, years, detail

---

## **Modul Attendance & QR Code**

### **Tabel: `attendances`**

```sql
CREATE TABLE attendances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_participant_id INT NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    status ENUM('present', 'absent', 'late', 'left_early') DEFAULT 'absent',
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    check_in_method ENUM('qr_code', 'manual', 'self_scan') DEFAULT 'qr_code',
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (event_participant_id) REFERENCES event_participants(id) ON DELETE CASCADE
);
```

### **Tabel: `attendance_logs`**

```sql
CREATE TABLE attendance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    participant_id INT NOT NULL,
    type ENUM('check_in', 'check_out') NOT NULL,
    scan_time DATETIME NOT NULL,
    scanned_by INT,
    qr_code VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    ip_address VARCHAR(45),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (event_id, participant_id, type),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **QR Code Format**

`QRCodeService::generate()` menghasilkan string: `SH3-{event_id}-{participant_id}-{8 random chars}`.
Disimpan di `event_participants.qr_code`.

### **Attendance Features**

1. **Generate QR Code** — Generate QR untuk setiap peserta
2. **Scan QR Code** — Scan dengan mobile atau webcam
3. **Check-in/out** — Catat waktu masuk/keluar
4. **Sync Up/Down** — Sinkronisasi offline (device tanpa koneksi)
5. **Attendance Report** — Laporan kehadiran

---

## **Modul Membership**

Dokumentasi lengkap di `docs/12 — Membership Module.md`.

### **Tabel: `membership_plans`**

```sql
CREATE TABLE membership_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    price INT UNSIGNED DEFAULT 0,
    duration INT UNSIGNED DEFAULT 0,
    duration_unit ENUM('days', 'months') DEFAULT 'months',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### **Tabel: `membership_histories`**

```sql
CREATE TABLE membership_histories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    membership_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

> `membership_type` adalah VARCHAR (string), bukan ENUM. Status mencakup `pending` (default). Constants: `STATUS_PENDING`, `STATUS_ACTIVE`, `STATUS_EXPIRED`, `STATUS_CANCELLED`, `MEMBERSHIP_TAHUNAN`, `MEMBERSHIP_SETENGAH_TAHUN`, `MEMBERSHIP_MINGGUAN`.

### **Membership Rules**

- Harga/durasi dari `membership_plans` (dinamis), bukan hardcoded
- `grant()` — admin memberi langsung (status active)
- `requestSubscription()` — peserta membeli via API (status pending + payment)
- `activate()` — aktivasi setelah konfirmasi bendahara
- `cancelMembership()` / `cancelHistory()` — pembatalan (status cancelled)
- `markExpiredHistories()` — tandai expired (end_date < hari ini)
- `autoRenewal()` — perpanjang otomatis jika sisa ≤ 7 hari
- `stats()` — total, active, pending, expired, expiring_soon, revenue

### **Member's API Response Fields**

- `membership_plan_name` — nama plan dari relasi `membershipPlan()->name`
- `hash_id` — format `%04d` (member aktif) atau `NM-%04d` (non-member)
- `is_membership_active` — hasil `isMembershipActive()`

---

## **Modul Notification**

### **Komponen**

- `AdminNotification` — queueable (ShouldQueue), channel database + broadcast
- `NotificationService` — helper: `notifyAdmins()`, `notifyRoles()`, `notifyUser()`, `notifyParticipant()`

### **API Endpoints**

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/notifications` | 20 notifikasi terbaru + unread_count |
| GET | `/api/v1/notifications/unread-count` | Jumlah belum dibaca |
| POST | `/api/v1/notifications/{id}/read` | Tandai satu dibaca |
| POST | `/api/v1/notifications/read-all` | Tandai semua dibaca |

---

## **API Endpoints**

Semua API memakai prefix `/api/v1`.

### **Authentication API**

```
POST /api/v1/auth/register              # password opsional (min 6)
POST /api/v1/auth/login
POST /api/v1/auth/logout                # auth:sanctum
POST /api/v1/auth/refresh               # auth:sanctum
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

### **Profile API**

```
GET  /api/v1/profile                    # auth:sanctum
PUT  /api/v1/profile                    # auth:sanctum
POST /api/v1/profile/photo              # auth:sanctum (multipart, field "avatar")
```

### **Event API**

Public:
```
GET /api/v1/events                      # publish+ongoing+completed
GET /api/v1/events/upcoming
GET /api/v1/events/{id}
GET /api/v1/events/{id}/participants
```

Authenticated:
```
POST /api/v1/events/{eventId}/register  # daftar event
GET  /api/v1/my-events                  # event diikuti user
POST /api/v1/events                     # role: admin_full_access, organizer
PUT  /api/v1/events/{id}                # role: admin_full_access, organizer
DELETE /api/v1/events/{id}              # role: admin_full_access, organizer
GET  /api/v1/events/{id}/qr             # role: admin_full_access, organizer
```

### **Participant API**

```
GET  /api/v1/participants               # auth:sanctum
GET  /api/v1/participants/{id}          # auth:sanctum
PUT  /api/v1/participants/{id}          # auth:sanctum
GET  /api/v1/participants/{id}/events   # auth:sanctum
GET  /api/v1/participants/{id}/attendance # auth:sanctum
```

Response includes `hash_id` (member: %04d, non-member: NM-%04d) dan `membership_plan_name`.

### **Membership API**

```
GET  /api/v1/membership                 # auth:sanctum — status membership user
GET  /api/v1/membership/plans           # auth:sanctum — daftar plan aktif
GET  /api/v1/membership/history         # auth:sanctum — riwayat membership
POST /api/v1/membership/subscribe       # auth:sanctum — ajukan pembelian
POST /api/v1/membership/cancel          # auth:sanctum — batalkan membership
```

### **Payment API**

```
POST /api/v1/payments/create            # auth:sanctum
GET  /api/v1/payments/{id}              # auth:sanctum
GET  /api/v1/payments/history           # auth:sanctum
POST /api/v1/payments/confirm/{id}      # role: admin_full_access, bendahara
```

### **Attendance API**

```
POST /api/v1/attendance/check-in        # auth:sanctum
POST /api/v1/attendance/check-out       # auth:sanctum
POST /api/v1/attendance/scan            # auth:sanctum
POST /api/v1/attendance/sync-up         # auth:sanctum — offline sync
GET  /api/v1/attendance/sync-down       # auth:sanctum — download offline data
GET  /api/v1/attendance/report          # auth:sanctum
GET  /api/v1/attendance/{eventId}       # auth:sanctum
```

### **Merchandise API**

```
GET  /api/v1/merchandise                # publik
GET  /api/v1/merchandise/{id}           # publik
POST /api/v1/merchandise/order          # auth:sanctum
GET  /api/v1/merchandise/orders         # auth:sanctum
GET  /api/v1/merchandise/orders/{id}    # auth:sanctum
POST /api/v1/merchandise/orders/{id}/cancel  # auth:sanctum
POST /api/v1/merchandise/orders/{id}/payment # auth:sanctum (upload bukti bayar)
```

### **Gallery API**

```
GET /api/v1/galleries                   # publik — semua foto (type=image)
```

### **Category API**

```
GET /api/v1/categories                  # publik — kategori aktif
```

### **Organization API**

```
GET /api/v1/organization                # publik
GET /api/v1/organization/{id}           # publik
GET /api/v1/organization/stats          # publik
GET /api/v1/organization/tree           # publik
GET /api/v1/organization/years          # publik
```

### **Sponsor API**

```
GET /api/v1/sponsors                    # publik
```

### **Notification API**

```
GET    /api/v1/notifications                # auth:sanctum
GET    /api/v1/notifications/unread-count   # auth:sanctum
POST   /api/v1/notifications/{id}/read      # auth:sanctum
POST   /api/v1/notifications/read-all       # auth:sanctum
```

---

## **Tech Stack**

### **Backend**

- **Framework**: Laravel 13
- **Language**: PHP 8.3
- **Database**: MySQL / MariaDB
- **Cache/Queue/Session**: Redis
- **Authentication**: Laravel Sanctum (API), Session (Admin)
- **Realtime**: Laravel Reverb (WebSocket) untuk notifikasi
- **QR Code**: simplesoftwareio/simple-qrcode

### **Frontend Admin**

- **Template**: AdminLTE 3
- **CSS**: Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery
- **Chart**: Chart.js
- **DataTable**: DataTables

### **Frontend Public/API**

- **Framework**: Next.js 16 + React 19 + Tailwind CSS v4
- **State Management**: React Context (AuthContext)
- **HTTP Client**: Axios

### **Additional Libraries**

- **Report/Export**: Maatwebsite/Laravel-Excel
- **Payment**: Konfirmasi manual oleh bendahara (belum gateway realtime)
- **Image Processing**: GD/Imagick via ImageHelper

---

## **Database ERD**

```
users ───┬─── participants
         │          ├─── membership_histories
         │          └─── membership_plans (via key)
         ├─── events ──┬── event_participants ── attendances ── attendance_logs
         │              │        │
         │              │        └── payments (morphTo)
         │              ├── galleries ── gallery_albums
         │              ├── event_sponsors ── sponsors
         │              ├── merchandise_orders ── merchandise
         │              └── event_schedules
         ├─── user_activity_logs
         └─── notifications
```

---

## **Security**

- **Authentication**: Laravel Sanctum (API), Session (Admin)
- **Authorization**: Role-based middleware (`RoleMiddleware`)
- **Validation**: 24 Form Request classes
- **CSRF Protection**: All forms
- **XSS Protection**: Blade escaping
- **SQL Injection**: Eloquent ORM
- **Rate Limiting**: API throttling (60 req/min)
- **CORS**: Configured for API

---

## **Notes**

1. **Scheduler**: `php artisan schedule:list` melaporkan tidak ada scheduled task. `EventService::updateEventStatus()` dan `MembershipService::markExpiredHistories()` tidak berjalan otomatis.
2. **Queue**: Notification memakai `ShouldQueue`; worker harus berjalan (`php artisan queue:work`).
3. **File Upload**: Max 2MB gambar, 5MB banner/bukti bayar.
4. **QR Code**: Format `SH3-{event_id}-{participant_id}-{8 chars}`.
5. **Membership**: Harga/durasi dinamis dari tabel `membership_plans`.
6. **Payment**: Polymorphic (morphs) ke EventParticipant, MerchandiseOrder, MembershipHistory.

---

**Versi**: 1.0.0 **Tanggal**: 2026-08-04

Lihat `docs/17 — Implementation Sync.md` untuk inventaris lengkap kode vs dokumentasi.
