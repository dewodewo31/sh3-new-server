# Pembayaran

Sistem pembayaran **polymorphic** — satu modul melayani registrasi **event**,
order **merchandise**, dan pembelian **membership**.

**Pengguna:** `admin_full_access`, `bendahara`.

---

## 1. Melihat Daftar Pembayaran

**Menu:** Sidebar → *Payments* (`/admin/payments`).

- Tabel menampilkan pemesan, jenis pembayaran, nominal, metode, status, tanggal.
- Gunakan filter status (`pending`, `confirmed`, `rejected`, `refunded`) dan jenis.

---

## 2. Status Pembayaran

```text
pending → confirmed
        → rejected
        → refunded
```

| Status | Arti |
|--------|------|
| `pending` | Menunggu konfirmasi bendahara |
| `confirmed` | Pembayaran diterima (aktivasi otomatis terkait) |
| `rejected` | Pembayaran ditolak |
| `refunded` | Dana dikembalikan |

---

## 3. Metode Pembayaran

`transfer`, `cash`, `qris`.

---

## 4. Mengonfirmasi Pembayaran

**Cara penggunaan:**

1. Buka menu **Payments**.
2. Klik pembayaran berstatus **pending** untuk membuka detail
   (`/admin/payments/{id}`).
3. Periksa bukti bayar (bila ada).
4. Klik **Confirm** → konfirmasi dialog.

**Efek konfirmasi:**

- **Registrasi event** → peserta ditandai sudah membayar (siap absen).
- **Merchandise** → order diproses; stok sudah terpotong saat order dibuat.
- **Membership** → membership peserta langsung **aktif**.

---

## 5. Menolak / Refund

- **Reject** — tolak pembayaran tidak valid (`PUT /admin/payments/{id}/reject`).
- **Refund** — tandai dana dikembalikan (`PUT /admin/payments/{id}/refund`).

---

## 6. Bukti Bayar & Pembuatan Pembayaran

Pembayaran dibuat otomatis saat:

- Peserta mendaftar event berbayar.
- Peserta order merchandise (`POST /api/v1/merchandise/order`).
- Peserta mengajukan membership (`POST /api/v1/membership/subscribe`).

Peserta dapat mengunggah bukti bayar:

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/v1/payments/create` | Buat pembayaran |
| GET | `/api/v1/payments/{id}` | Detail pembayaran |
| GET | `/api/v1/payments/history` | Riwayat pembayaran |
| POST | `/api/v1/merchandise/orders/{id}/payment` | Upload bukti bayar order |

---

## Tips

- Selalu cocokkan **nominal** dengan bukti sebelum **Confirm**.
- Pastikan **queue worker** berjalan agar notifikasi konfirmasi terkirim ke peserta.
- Untuk konfirmasi massal, proses satu per satu agar aktivasi terkait berjalan benar.
