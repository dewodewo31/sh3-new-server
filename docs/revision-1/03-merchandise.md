# Merchandise Revision

## Tujuan

Menyamakan fitur API Merchandise dengan API terdahulu (spesifikasi `docs/readme.md` → *Merchandise API*).

Spesifikasi terdahulu:

```
GET  /api/v1/merchandise
GET  /api/v1/merchandise/{id}
POST /api/v1/merchandise/order
GET  /api/v1/merchandise/orders
```

## Existing API

`routes/api.php`:

```php
Route::get('/merchandise', [MerchandiseController::class, 'index']);
Route::get('/merchandise/{id}', [MerchandiseController::class, 'show']);
// auth:sanctum
Route::post('/merchandise/order', [MerchandiseController::class, 'order']);
```

## Missing API

1. `GET /api/v1/merchandise/orders` — riwayat order milik user yang login.
2. Method `order()` di `MerchandiseController` **belum diimplementasikan** — route `POST /merchandise/order` menunjuk ke method yang tidak ada (akan error bila dipanggil).
3. Detail order, upload bukti pembayaran order, cancel order, dan status pembayaran order belum memiliki endpoint API.

## Route

Route yang harus dilengkapi / diperbaiki:

```php
// auth:sanctum
Route::post('/merchandise/order', [MerchandiseController::class, 'order']);   // fix: implementasikan method
Route::get('/merchandise/orders', [MerchandiseController::class, 'orders']); // baru
Route::get('/merchandise/orders/{id}', [MerchandiseController::class, 'orderDetail']); // baru
Route::post('/merchandise/orders/{id}/cancel', [MerchandiseController::class, 'cancelOrder']); // baru
Route::post('/merchandise/orders/{id}/payment', [MerchandiseController::class, 'uploadPayment']); // baru
```

## Controller

`app/Http/Controllers/API/MerchandiseController.php` — saat ini hanya:

- `index()` → `MerchandiseRepository::findAvailable()` (status `available`)
- `show(int $id)` → detail merchandise

Method yang perlu ditambahkan: `order()`, `orders()`, `orderDetail()`, `cancelOrder()`, `uploadPayment()`.

## Order Flow

`MerchandiseService::createOrder(Merchandise $merchandise, array $data)`:

1. Cek stok: `$merchandise->stock < $data['quantity']` → `422 "Stok tidak mencukupi."`
2. Buat `merchandise_orders`:
   - `participant_id`, `customer_name`, `customer_contact`, `size`, `quantity`
   - `total_price` = `price × quantity`
   - `payment_status` = `pending`
3. Decrement stok merchandise.
4. Notifikasi role `merchandise` & `admin_full_access`.

> `order()` di controller belum memanggil service ini (belum diimplementasikan).

## Payment Flow

- Saat ini konfirmasi pembayaran merchandise dilakukan via `MerchandiseService::confirmPayment(Merchandise, orderId)` (set `payment_status` → `paid`), tanpa payment record.
- Alur terdahulu (`docs/readme.md` *Payment API*): `POST /payments/create` → `POST /payments/confirm` (dikonfirmasi bendahara).
- Ideal: order merchandise membuat `Payment` (payment_type `merchandise`, morphTo `merchandise_order`), lalu `PaymentService::confirmPayment` mengonfirmasi order.

## Upload Payment

- Belum ada endpoint API upload bukti bayar untuk order merchandise.
- Pola yang tersedia: `ImageHelper::upload($file, 'payments')` dipakai di `MembershipController::subscribe` untuk upload `payment_proof` (image max 5MB) → bisa direplikasi untuk order merchandise.

## Cancel Order

- Belum ada endpoint. Aturan disarankan: hanya bisa cancel saat `payment_status = pending`; stok dikembalikan (increment).

## My Orders

- `GET /api/v1/merchandise/orders` → daftar `merchandise_orders` milik participant user yang login (`user->participants()->first()`).

## Order Detail

- `GET /api/v1/merchandise/orders/{id}` → detail order milik user yang login.

## Payment Status

- Status order saat ini (migration `2024_01_01_000009_create_merchandise_table.php`): `pending`, `paid`, `cancelled`.
- Kolom `payment_id` tersedia di `merchandise_orders` (belum dipakai di flow).

## Validation

Usulan untuk `MerchandiseOrderRequest` (belum ada):

- `merchandise_id` → required, exists:merchandise,id
- `customer_name` → required, string, max:255
- `customer_contact` → required, string
- `size` → required, string (harus ada di `size_options` merchandise)
- `quantity` → required, integer, min:1

## Response

`GET /merchandise` → `200`:

```json
{ "data": [ { "id": 1, "name": "...", "price": 100000, "stock": 5, "size_options": [...] } ] }
```

`POST /merchandise/order` → `201` (setelah method diimplementasikan):

```json
{ "data": { "...": "order" }, "message": "Order berhasil dibuat" }
```

## Error

- Merchandise tidak ditemukan → `404`.
- Stok tidak cukup → `422 { "message": "Stok tidak mencukupi.", "errors": { "quantity": [...] } }`.
- Belum login → `401`.
- Order milik user lain → `404`.

## Testing

- List merchandise (hanya `available`).
- Detail merchandise.
- Order sukses → stok berkurang, order `pending`.
- Order dengan stok tidak cukup → `422`.
- Order tanpa autentikasi → `401`.
- My orders & order detail hanya menampilkan milik sendiri.
- Cancel order pending → stok dikembalikan.

## Checklist

- [ ] Implementasikan method `order()` di `MerchandiseController` + panggil `MerchandiseService::createOrder`.
- [ ] Tambahkan route `GET /merchandise/orders`, `GET /merchandise/orders/{id}`.
- [ ] Tambahkan endpoint cancel order & upload bukti bayar.
- [ ] Integrasikan order merchandise dengan `Payment` (payment_type `merchandise`).
- [ ] Tambahkan `MerchandiseOrderRequest`.
- [ ] Tambahkan test feature.
