# 05 — Merchandise Module

Merchandise/jersey management & order system. Peserta dapat memesan merchandise dengan pilihan size, mengunggah bukti bayar, dan admin merchandise mengonfirmasi pembayaran.

## Database

### Tabel `merchandise`

```sql
CREATE TABLE merchandise (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(15,2) NOT NULL,
    size_options JSON NULL,               -- array ukuran: ["S","M","L","XL"]
    stock INT DEFAULT 0,
    image VARCHAR(255) NULL,
    status ENUM('available','sold_out','discontinued') DEFAULT 'available',
    created_by INT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Tabel `merchandise_orders`

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
    payment_status ENUM('pending','paid','cancelled') DEFAULT 'pending',
    payment_id INT NULL,                  -- FK ke payments
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (merchandise_id) REFERENCES merchandise(id) ON DELETE RESTRICT,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE RESTRICT
);
```

## Order Flow (API)

1. **Buat order** `POST /merchandise/order`
   - Input: merchandise_id, quantity, size, customer_name, customer_contact, payment_method, payment_proof (opsional).
   - Validasi: stok cukup, `size` harus ada di `size_options` (bila terisi).
   - `total_price = price * quantity`, stok di-kurangi.
   - Payment dibuat (pending) dan dikaitkan (`payment_id`).
   - Notifikasi ke role `merchandise` & `admin_full_access`.

2. **Unggah/update bukti bayar** `POST /merchandise/orders/{id}/payment`
   - Hanya saat status `pending`.
   - Update `payment_proof` / `payment_method` pada payment terkait (atau buat payment bila belum ada).

3. **Batalkan order** `POST /merchandise/orders/{id}/cancel`
   - Hanya saat status `pending`.
   - Stok dikembalikan (`increment`).
   - Status → `cancelled`.

4. **Konfirmasi pembayaran** → pada 2.x pola matang di belah `merchandise/order` admin → `markAsPaid()` (status → `paid`).

## API Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/merchandise` | Daftar merchandise (publik, status available) |
| GET | `/merchandise/{id}` | Detail merchandise (publik) |
| POST | `/merchandise/order` | Buat order (auth) |
| GET | `/merchandise/orders` | Order milik user (auth) |
| GET | `/merchandise/orders/{id}` | Detail order user (auth) |
| POST | `/merchandise/orders/{id}/cancel` | Batalkan order (auth) |
| POST | `/merchandise/orders/{id}/payment` | Unggah/update bukti bayar (auth) |

Semua order butuh auth (`auth:sanctum`); `participant_id` diambil dari user login.

## Route Admin (Web)

| Role | Route |
|------|-------|
| Full Access, Laman, Merchandise | CRUD `/admin/merchandise` |

Pembayaran/order merchandise diconfirm oleh role **Bendahara** via `/admin/payments`.

## File Terkait

- `app/Services/MerchandiseService.php` — createOrder, cancelOrder, uploadPayment, confirmPayment
- `app/Repositories/MerchandiseRepository.php`, `app/Repositories/MerchandiseOrderRepository.php`
- `app/Models/Merchandise.php`, `app/Models/MerchandiseOrder.php`
- `app/Http/Controllers/API/MerchandiseController.php`, `app/Http/Controllers/Admin/MerchandiseController.php`
- `app/Http/Resources/MerchandiseResource.php`, `app/Http/Requests/MerchandiseOrderRequest.php`