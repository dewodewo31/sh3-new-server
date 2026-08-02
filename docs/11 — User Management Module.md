# 11 — User Management Module

Pengelolaan user admin/web dan permission matrix berbasis role.

## Database

### Tabel `users`

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin_full_access','admin_laman','admin_member',
              'admin_bnh','organizer','bendahara','sponsor','merchandise','participant'),
    avatar VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabel `user_activity_logs` (audit aktivitas)

```sql
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255),            -- login, logout, refresh, create_*, update_*, dll.
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Role Permission Matrix

| Role | Akses |
|------|-------|
| Admin Full Access | Semua menu + user management |
| Admin Laman | Semua menu (read-only sebagian) |
| Admin Member | Participants & Memberships |
| Admin BNH | Gallery |
| Organizer | Events |
| Bendahara | Payments (+ view memberships) |
| Sponsor | Sponsors |
| Merchandise | Merchandise (+ view payments) |
| Participant | API publik (mobile/web) |

## Aktivitas Logging

Log aktivitas via `UserService::logActivity()`; dipanggil otomatis saat:

- `login`, `logout`, `refresh` (AuthController API)
- operasi create/update user (UserService)

Disimpan di `user_activity_logs` (action + details JSON + ip + user_agent).

## Route Admin (Web)

| Method | Route | Role |
|--------|-------|------|
| resource | `/admin/users` | Full Access only |
| PUT | `/admin/users/{id}/toggle-active` | Full Access only |

## API (Auth)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/auth/register` | Register peserta (role=participant), password opsional |
| POST | `/api/v1/auth/login` | Login → token + user |
| POST | `/api/v1/auth/logout` | Logout (revoke token) |
| GET | `/api/v1/auth/me` | Profil user + participants |
| POST | `/api/v1/auth/refresh` | Refresh token |
| POST | `/api/v1/auth/forgot-password` | Kirim link reset password |
| POST | `/api/v1/auth/reset-password` | Reset password |

Register membuat `User` (role `participant`) + `Participant` dalam satu transaksi.

## File Terkait

- `app/Services/UserService.php` — createUser, updateUser, toggleActive, logActivity
- `app/Services/AuthService.php` — login, token, refresh, reset password
- `app/Repositories/UserRepository.php`
- `app/Http/Controllers/Admin/UserController.php`, `app/Http/Controllers/API/AuthController.php`
- `app/Models/UserActivityLog.php`