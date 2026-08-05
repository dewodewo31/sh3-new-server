# 04 — Event Module

Event CRUD, registrasi, quota management, dan pengelolaan file event.

## Tables

### Tabel `events`

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

> Kolom aktual: `title` (bukan `name`), `quota` (bukan `max_participants`), `is_free_for_members` (bukan `is_membership_free`). Migration: `2024_01_01_000007_create_events_table.php`.

### Tabel `event_participants`

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

> Migration `2024_01_01_000008_create_event_participants_table.php`. Tidak ada kolom `status` ENUM lama (`registered|waiting|cancelled|attended`); diganti dengan `registration_type` dan `payment_status`.

Nama model dan tabel registrasi aktual adalah `events` dan `event_participants`; tidak ada tabel `event_registrations`.

## Image & Banner Handling

- Controller/service memakai helper image dan public storage sesuai implementasi.
- URL publik bergantung pada disk/config storage dan `APP_URL`.

## Registration Flow

- Member aktif dapat gratis jika event mengaktifkan `is_free_for_members`.
- Event gratis tidak membuat biaya.
- Event berbayar membuat payment sesuai flow service.
- Registrasi dan payment memakai relasi `EventParticipant`/polymorphic payment.

## Routes

- API: `/api/v1/events`, `/api/v1/events/upcoming`, `/api/v1/events/{id}`, `/api/v1/events/{id}/participants`.
- Authenticated API: register, my-events, CRUD event, dan QR route sesuai `routes/api.php`.
- Admin: `/admin/events` dan `/admin/events/{id}/publish`, role `admin_full_access` atau `organizer`.

## Scheduler Limitation

Status otomatis melalui scheduler belum aktif. `php artisan schedule:list` tidak memiliki scheduled task; jangan menganggap `updateEventStatus()` berjalan otomatis.

Detail model, service, repository, request, resource, controller, migration, dan response dirangkum di `docs/17 — Implementation Sync.md`.
