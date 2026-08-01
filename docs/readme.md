# **🏃 Sistem Manajemen Event Lari - SH3 Running Club**

## **📋 Daftar Isi**

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
15. Changelog & Perbaikan (lihat `14 — Changelog & Fixes.md`)

---

## **🏗️ Arsitektur Sistem**

### **Layered Architecture**

text

```
┌─────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                 │
│  - Blade Views (Admin Dashboard)                    │
│  - API Responses (Mobile/Web Client)               │
│  - Middleware & Authentication                      │
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
│  - Redis (Cache)                                    │
└─────────────────────────────────────────────────────┘
```

### **Struktur Folder**

text

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── API/
│   │   │   ├── AuthController.php
│   │   │   ├── EventController.php
│   │   │   ├── ParticipantController.php
│   │   │   └── ...
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── EventController.php
│   │       └── ...
│   ├── Middleware/
│   │   ├── AdminMiddleware.php
│   │   └── RoleMiddleware.php
│   └── Requests/
│       ├── EventRequest.php
│       └── ParticipantRequest.php
├── Models/
│   ├── User.php
│   ├── Event.php
│   ├── Participant.php
│   ├── Category.php
│   ├── Order.php
│   └── ...
├── Services/
│   ├── EventService.php
│   ├── PaymentService.php
│   └── MembershipService.php
├── Repositories/
│   ├── EventRepository.php
│   └── ParticipantRepository.php
└── Helpers/
    ├── QRCodeHelper.php
    └── ImageHelper.php
```

---

## **👤 Modul User Management**

### **Deskripsi**

Modul untuk mengelola semua pengguna sistem dengan pembagian role dan akses.

### **Tabel: `users`**

sql

```
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM(
        'admin_full_access',
        'admin_laman',
        'admin_member',
        'admin_bnh',
        'organizer',
        'bendahara',
        'sponsor',
        'merchandise',
        'participant'
    ) NOT NULL,
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Role & Permission Matrix**

| **Role** | **Events** | **Merchandise** | **Gallery** | **Payments** | **Participants** | **Sponsors** | **Dashboard** |
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

### **Seeder Default Users**

php

```
// Database/Seeders/UserSeeder.php
public function run()
{
    // 1. ADMIN FULL ACCESS
    User::create([
        'name' => 'Admin Full Access',
        'email' => 'admin.full@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'admin_full_access',
    ]);

    // 2. ADMIN LAMAN
    User::create([
        'name' => 'Admin Laman',
        'email' => 'admin.laman@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'admin_laman',
    ]);

    // 3. ADMIN MEMBER
    User::create([
        'name' => 'Admin Member',
        'email' => 'admin.member@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'admin_member',
    ]);

    // 4. ADMIN BNH
    User::create([
        'name' => 'Admin BNH',
        'email' => 'admin.bnh@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'admin_bnh',
    ]);

    // 5. ORGANIZER
    User::create([
        'name' => 'Organizer',
        'email' => 'organizer@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'organizer',
    ]);

    // 6. BENDAHARA
    User::create([
        'name' => 'Bendahara',
        'email' => 'bendahara@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'bendahara',
    ]);

    // 7. SPONSOR
    User::create([
        'name' => 'Sponsor',
        'email' => 'sponsor@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'sponsor',
    ]);

    // 8. MERCHANDISE
    User::create([
        'name' => 'Merchandise',
        'email' => 'merchandise@sh3.com',
        'password' => Hash::make('password'),
        'role' => 'merchandise',
    ]);
}
```

### **Authentication Flow**

text

```
1. User Login (Email/Password)
2. Middleware Check Role
3. Redirect to Dashboard sesuai Role
4. Token Generated (Sanctum/JWT)
5. Session Management
```

### **Middleware Role Check**

php

```
// app/Http/Middleware/RoleMiddleware.php
public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        return redirect('/login');
    }

    if (!in_array(auth()->user()->role, $roles)) {
        abort(403, 'Unauthorized access');
    }

    return $next($request);
}
```

### **Routes dengan Middleware**

php

```
// routes/web.php
Route::middleware(['auth', 'role:admin_full_access'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'role:admin_full_access,admin_member'])->group(function () {
    Route::resource('participants', ParticipantController::class);
});

Route::middleware(['auth', 'role:admin_full_access,organizer'])->group(function () {
    Route::resource('events', EventController::class);
});
```

---

## **🏃 Modul Participant & Membership**

### **Deskripsi**

Modul untuk mengelola data peserta lari dengan sistem membership yang fleksibel.

### **Tabel: `participants`**

sql

```
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
    membership_type ENUM('tahunan', 'setengah_tahun', 'mingguan', 'none') DEFAULT 'none',
    membership_start_date DATE,
    membership_end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    total_events_participated INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `membership_histories`**

sql

```
CREATE TABLE membership_histories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    membership_type ENUM('tahunan', 'setengah_tahun', 'mingguan') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

### **Tabel: `event_participants`**

sql

```
CREATE TABLE event_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    participant_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'waiting', 'cancelled', 'attended') DEFAULT 'registered',
    qr_code VARCHAR(255),
    check_in_time TIMESTAMP NULL,
    check_out_time TIMESTAMP NULL,
    is_membership_free BOOLEAN DEFAULT FALSE,
    payment_id INT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
```

### **Membership Rules**

> Detail lengkap: lihat `docs/12 — Membership Module.md`.

php

```
// app/Services/MembershipService.php
class MembershipService
{
    // Harga per tipe membership
    public const PRICES = [
        'tahunan' => 400000,
        'setengah_tahun' => 250000,
        'mingguan' => 10000,
    ];

    public function checkEligibility($participant)
    {
        if ($participant->membership_type === 'none') {
            return 'paid';
        }

        if ($participant->membership_end_date && $participant->membership_end_date >= now()->toDateString()) {
            return 'free';
        }

        return 'paid';
    }

    public function grant($participant, $type)
    {
        // Admin memberi membership langsung (status active)
    }

    public function requestSubscription($participant, $type, $paymentMethod)
    {
        // Peserta membeli via API (history pending + payment pending)
    }

    public function activate($history)
    {
        // Dipanggil saat pembayaran dikonfirmasi bendahara
    }
}
```

### **Participant Features**

1. **Pendaftaran** - Register via API
2. **Membership** - 3 jenis membership
3. **History Event** - Riwayat partisipasi
4. **QR Code** - Generate untuk event
5. **Dashboard** - Informasi personal

---

## **🏁 Modul Event**

### **Deskripsi**

Modul untuk mengelola event lari yang diselenggarakan.

### **Tabel: `events`**

sql

```
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    banner VARCHAR(255),
    location VARCHAR(255),
    address TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    registration_start_date DATETIME NOT NULL,
    registration_end_date DATETIME NOT NULL,
    max_participants INT,
    price DECIMAL(15,2),
    is_membership_free BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'publish', 'ongoing', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `event_schedules`**

sql

```
CREATE TABLE event_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);
```

### **Event Management Features**

1. **Create Event** - Buat event baru
2. **Update Event** - Update informasi event
3. **Publish Event** - Publikasi event
4. **Cancel Event** - Batalkan event
5. **View Participants** - Lihat peserta
6. **Export Data** - Export peserta ke Excel

---

## **📂 Modul Kategori Event**

### **Deskripsi**

Modul untuk mengelola kategori event lari.

### **Tabel: `categories`**

sql

```
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    distance_km DECIMAL(5,2),
    slug VARCHAR(100) UNIQUE NOT NULL,
    banner VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Data Kategori**

php

```
// Database/Seeders/CategorySeeder.php
public function run()
{
    $categories = [
        ['name' => '5K Fun Run', 'description' => 'Lari santai 5km', 'distance_km' => 5.00],
        ['name' => '10K Challenge', 'description' => 'Tantangan 10km', 'distance_km' => 10.00],
        ['name' => '21K Half Marathon', 'description' => 'Setengah maraton', 'distance_km' => 21.10],
        ['name' => '42K Full Marathon', 'description' => 'Maraton penuh', 'distance_km' => 42.20],
        ['name' => 'Trail Run', 'description' => 'Lari trail', 'distance_km' => 0.00],
    ];

    foreach ($categories as $category) {
        Category::create($category);
    }
}
```

---

## **🛍️ Modul Merchandise**

### **Deskripsi**

Modul untuk mengelola merchandise event lari.

### **Tabel: `merchandise`**

sql

```
CREATE TABLE merchandise (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(15,2) NOT NULL,
    stock INT DEFAULT 0,
    category VARCHAR(50),
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `merchandise_orders`**

sql

```
CREATE TABLE merchandise_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    merchandise_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_id INT,
    FOREIGN KEY (participant_id) REFERENCES participants(id),
    FOREIGN KEY (merchandise_id) REFERENCES merchandise(id)
);
```

### **Merchandise Features**

1. **CRUD Merchandise** - Kelola produk
2. **Stock Management** - Manajemen stok
3. **Order Management** - Kelola pesanan
4. **Payment Integration** - Integrasi pembayaran

---

## **🖼️ Modul Gallery**

### **Deskripsi**

Modul untuk mengelola gallery foto event.

### **Tabel: `galleries`**

sql

```
CREATE TABLE galleries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    image_type ENUM('photo', 'video') DEFAULT 'photo',
    is_featured BOOLEAN DEFAULT FALSE,
    order_number INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Gallery Features**

1. **Upload Media** - Upload foto/video
2. **CRUD Gallery** - Kelola gallery
3. **Featured Images** - Gambar unggulan
4. **Bulk Upload** - Upload banyak file

---

## **💳 Modul Payments**

### **Deskripsi**

Modul untuk mengelola pembayaran event dan merchandise.

### **Tabel: `payments`**

sql

```
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,
    event_id INT,
    merchandise_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('bank_transfer', 'credit_card', 'e_wallet', 'cash') NOT NULL,
    payment_status ENUM('pending', 'success', 'failed', 'refund') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_proof VARCHAR(255),
    payment_date TIMESTAMP,
    order_id VARCHAR(255),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (merchandise_id) REFERENCES merchandise(id) ON DELETE SET NULL
);
```

### **Payment Features**

1. **Payment Gateway Integration** - Midtrans/Xendit
2. **Invoice Generation** - Generate invoice
3. **Payment Confirmation** - Konfirmasi pembayaran
4. **Report Management** - Laporan keuangan

---

## **🏢 Modul Sponsor**

### **Deskripsi**

Modul untuk mengelola sponsor event.

### **Tabel: `sponsors`**

sql

```
CREATE TABLE sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    website VARCHAR(255),
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    sponsor_level ENUM('platinum', 'gold', 'silver', 'bronze', 'media_partner') NOT NULL,
    sponsorship_value DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Tabel: `event_sponsors`**

sql

```
CREATE TABLE event_sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    sponsor_id INT NOT NULL,
    package VARCHAR(255),
    value DECIMAL(15,2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
);
```

---

## **🏛️ Modul Struktur Organisasi**

### **Deskripsi**

Modul untuk mengelola struktur organisasi SH3.

### **Tabel: `organizations`**

sql

```
CREATE TABLE organizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    user_id INT,
    order_number INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Struktur Organisasi SH3**

php

```
// Seeder struktur organisasi
$organizations = [
    ['name' => 'Ketua', 'position' => 'Ketua SH3'],
    ['name' => 'Wakil Ketua', 'position' => 'Wakil Ketua SH3'],
    ['name' => 'Sekretaris', 'position' => 'Sekretaris SH3'],
    ['name' => 'Bendahara', 'position' => 'Bendahara SH3'],
    ['name' => 'Koordinator Event', 'position' => 'Koordinator Event'],
    ['name' => 'Koordinator Member', 'position' => 'Koordinator Member'],
    ['name' => 'Koordinator Dokumentasi', 'position' => 'Koordinator Dokumentasi'],
];
```

---

## **✅ Modul Attendance & QR Code**

### **Deskripsi**

Modul untuk mengelola kehadiran peserta dengan QR Code scan.

### **Tabel: `attendances`**

sql

```
CREATE TABLE attendances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_participant_id INT NOT NULL,
    check_in_time TIMESTAMP,
    check_out_time TIMESTAMP,
    status ENUM('present', 'absent', 'late', 'left_early') DEFAULT 'absent',
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    check_in_method ENUM('qr_code', 'manual', 'self_scan') DEFAULT 'qr_code',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_participant_id) REFERENCES event_participants(id) ON DELETE CASCADE
);
```

### **QR Code System**

php

```
// app/Helpers/QRCodeHelper.php
class QRCodeHelper
{
    public function generateQRCode($eventParticipantId)
    {
        $data = [
            'id' => $eventParticipantId,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'timestamp' => now()->timestamp
        ];

        $qrcode = QrCode::size(300)
            ->format('png')
            ->generate(json_encode($data));

        return $qrcode;
    }

    public function scanQRCode($data)
    {
        // Validate QR Code
        // Check event validity
        // Record attendance
        // Update status
    }
}
```

### **Attendance Features**

1. **Generate QR Code** - Generate QR untuk setiap peserta
2. **Scan QR Code** - Scan dengan mobile atau webcam
3. **Check-in/out** - Catat waktu masuk/keluar
4. **Attendance Report** - Laporan kehadiran

### **QR Code Workflow**

text

```
1. Peserta daftar event
2. Generate QR Code unik
3. QR dikirim via email/WA
4. Saat event:
   - Scan QR untuk check-in
   - Scan QR untuk check-out
5. Data attendance tercatat
```

---

## **🔌 API Endpoints**

### **Authentication API**

http

```
POST /api/v1/auth/register     # password opsional (min 6); dipakai login bila dikirim
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

**`POST /auth/register`** — payload yang didukung:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "phone": "08123456789",
  "gender": "male",
  "date_of_birth": "2000-10-31",
  "blood_type": "O",
  "emergency_contact": "Dewo",
  "emergency_phone": "082131231233",
  "medical_conditions": "Debu",
  "jersey_size": "L"
}
```

Semua field selain `name` dan `email` bersifat opsional. Token & user dikembalikan di `res.data.token` / `res.data.user`.

### **Participant API**

http

```
GET /api/v1/participants
GET /api/v1/participants/{id}
PUT /api/v1/participants/{id}
GET /api/v1/participants/{id}/events
GET /api/v1/participants/{id}/attendance
```

Response peserta kini menyertakan `hash_id`:
- Member (membership aktif): `0022` (format `%04d`).
- Non-member: `NM-0044` (prefix `NM-`).

### **Profile API**

http

```
GET  /api/v1/profile
PUT  /api/v1/profile
POST /api/v1/profile/photo   # multipart, field "avatar"
```

`PUT /profile` menerima: `name`, `phone`, `gender`, `date_of_birth`, `blood_type`, `emergency_contact`, `emergency_phone`, `medical_conditions`.

### **Membership API**

http

```
GET /api/v1/membership
GET /api/v1/membership/plans
GET /api/v1/membership/history
POST /api/v1/membership/subscribe
POST /api/v1/membership/cancel
```

### **Event API**

http

```
GET /api/v1/events                      # publish + ongoing + completed (tanpa draft)
GET /api/v1/events/{id}                 # detail + galleries (array URL foto) + registered_count
GET /api/v1/events/{id}/participants    # daftar peserta (PUBLIK)
GET /api/v1/events/upcoming
GET /api/v1/my-events                   # auth: event diikuti user + status order
POST /api/v1/events/{id}/register       # auth: daftar event
POST /api/v1/events                     # auth: admin_full_access, organizer
PUT /api/v1/events/{id}                 # auth: admin_full_access, organizer
DELETE /api/v1/events/{id}              # auth: admin_full_access, organizer
GET /api/v1/events/{id}/qr              # auth: admin_full_access, organizer
```

### **Gallery API**

http

```
GET /api/v1/galleries        # semua foto event (type=image), URL penuh + thumb + info event
```

### **Category API**

http

```
GET /api/v1/categories       # kategori aktif + events_count
```

### **Organization API**

http

```
GET /api/v1/organization          # struktur organisasi
GET /api/v1/organization/{id}     # detail anggota
GET /api/v1/organization/stats    # statistik
GET /api/v1/organization/tree     # pohon organisasi (termasuk period_start/end)
GET /api/v1/organization/years    # daftar tahun periode
```

### **Payment API**

http

```
POST /api/v1/payments/create
GET /api/v1/payments/{id}
POST /api/v1/payments/confirm
GET /api/v1/payments/history
```

### **Attendance API**

http

```
POST /api/v1/attendance/check-in
POST /api/v1/attendance/check-out
GET /api/v1/attendance/{eventId}
GET /api/v1/attendance/report
```

### **Merchandise API**

http

```
GET /api/v1/merchandise
GET /api/v1/merchandise/{id}
POST /api/v1/merchandise/order
GET /api/v1/merchandise/orders
```

---

## **🛠️ Tech Stack**

### **Backend**

- **Framework**: Laravel 10/11
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Queue**: Redis/Beanstalkd
- **Authentication**: Laravel Sanctum/JWT

### **Frontend Admin**

- **Template**: AdminLTE 3
- **CSS**: Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery
- **Chart**: Chart.js
- **DataTable**: DataTables

### **Frontend Public/API**

- **Framework**: React.js/Vue.js (Optional)
- **State Management**: Redux/Pinia (Optional)
- **HTTP Client**: Axios

### **Additional Libraries**

- **QR Code**: simplesoftwareio/simple-qrcode
- **Excel**: Maatwebsite/Laravel-Excel
- **PDF**: Barryvdh/Laravel-DomPDF
- **Payment**: Midtrans/Xendit SDK
- **Image Processing**: Intervention Image
- **API Documentation**: Swagger/Postman

### **Development Tools**

- **Version Control**: Git/GitHub
- **CI/CD**: GitHub Actions
- **Code Quality**: PHPStan, Laravel Pint
- **Testing**: PHPUnit, Pest

### **Deployment**

- **Server**: Nginx/Apache
- **Process Manager**: Supervisor
- **Monitoring**: Laravel Telescope
- **Logging**: Laravel Log

---

## **📦 Package.json**

json

```
{
    "private": true,
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "axios": "^1.6.0",
        "bootstrap": "^5.3.0",
        "chart.js": "^4.4.0",
        "datatables.net": "^1.13.0",
        "jquery": "^3.7.0",
        "laravel-vite-plugin": "^0.8.0",
        "vite": "^4.0.0",
        "sass": "^1.69.0"
    }
}
```

---

## **🚀 Getting Started**

### **Installation**

bash

```
# Clone repository
git clone https://github.com/sh3-running/event-management.git

# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Copy .env file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database and update .env
# Run migrations and seeders
php artisan migrate --seed

# Run development server
php artisan serve
npm run dev
```

### **Environment Configuration**

env

```
APP_NAME=SH3 Running Club
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sh3_db
DB_USERNAME=root
DB_PASSWORD=

MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_IS_PRODUCTION=false

QR_CODE_SIZE=300
QR_CODE_FORMAT=png
```

---

## **📊 Database ERD**

text

```
users ───┬─── participants
         │        │
         ├─── events ──┬── event_participants ── attendances
         │              │        │
         └─── categories         ├── payments
                                 │
                                 ├── galleries
                                 │
                                 ├── event_sponsors ── sponsors
                                 │
                                 ├── merchandise_orders ── merchandise
                                 │
                                 └── event_schedules
```

---

## **🔒 Security**

- **Authentication**: Laravel Sanctum for API
- **Authorization**: Role-based middleware
- **Validation**: Form request validation
- **CSRF Protection**: All forms
- **XSS Protection**: Blade escaping
- **SQL Injection**: Eloquent ORM
- **Rate Limiting**: API throttling
- **CORS**: Configured for API

---

## **📝 Notes**

1. **API Rate Limit**: 60 requests per minute
2. **File Upload**: Max 5MB per file
3. **QR Code**: Unique per event participant
4. **Membership Auto**: Auto check for free event
5. **Payment**: Support multiple payment methods
6. **Logging**: All important actions logged

---

**Dibuat oleh SH3 Development Team**

**Versi**: 1.0.0

**Tanggal**: 2026