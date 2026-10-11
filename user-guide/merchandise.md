# Merchandise

Mengelola produk merchandise (kaos, jaket, dll.) beserta order peserta.

**Pengguna:** `admin_full_access`, `admin_laman`, `merchandise`.

---

## 1. Melihat Daftar Merchandise

**Menu:** Sidebar → *Merchandise* (`/admin/merchandise`).

Tabel menampilkan nama, harga, stok, dan status produk.

---

## 2. Menambah Produk

**Cara penggunaan:**

1. Buka menu **Merchandise** → klik **Tambah Merchandise**.
2. Isi:
   - **Name** — nama produk.
   - **Price (Rp)** — harga.
   - **Stock** — jumlah stok tersedia.
   - **Status** — aktif/nonaktif.
   - **Size Options (JSON)** — pilihan ukuran, mis. `["S","M","L","XL"]`
     (boleh dikosongkan bila produk tidak berukuran).
   - **Image** — gambar produk.
   - **Description** — deskripsi.
3. Klik **Simpan**.

> **Size Options** ditulis sebagai JSON array. Validasi ukuran hanya berjalan bila
> field ini terisi.

---

## 3. Mengubah & Menghapus Produk

- **Ubah:** tombol **Edit** → ubah → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

---

## 4. Order Merchandise (Peserta)

Peserta memesan dari frontend (`/merchandise/order`) atau API:

```http
POST /api/v1/merchandise/order
{
  "merchandise_id": 1,
  "quantity": 2,
  "size": "L"
}
```

**Yang terjadi:**

1. Order dibuat dan **stok otomatis berkurang**.
2. Dibuat **Payment** berstatus pending.
3. Peserta mengunggah bukti bayar.
4. Bendahara **Confirm** di menu Payments → order diproses.

**Pembatalan order:** `POST /api/v1/merchandise/orders/{id}/cancel` → **stok dikembalikan**.

---

## 5. Endpoint API Merchandise

### Publik

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/merchandise` | Daftar produk tersedia |
| GET | `/api/v1/merchandise/{id}` | Detail produk |

### Terautentikasi

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/v1/merchandise/order` | Buat order |
| GET | `/api/v1/merchandise/orders` | Daftar order milik user |
| GET | `/api/v1/merchandise/orders/{id}` | Detail order |
| POST | `/api/v1/merchandise/orders/{id}/cancel` | Batalkan order |
| POST | `/api/v1/merchandise/orders/{id}/payment` | Upload bukti bayar |

---

## Tips

- Perbarui **stok** secara berkala agar tidak terjadi order melebihi persediaan.
- Pastikan **gambar produk** ter-upload dengan benar (`storage:link` aktif).
