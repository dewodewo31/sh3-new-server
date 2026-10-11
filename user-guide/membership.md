# Membership

Fitur membership mengelola keanggotaan peserta SH3: paket/harga, pemberian oleh admin,
pembelian oleh peserta, konfirmasi pembayaran, hingga status aktif/kadaluarsa.

**Pengguna:** `admin_full_access`, `admin_member` (kelola & beri), `bendahara` (konfirmasi).

---

## 1. Konsep Dasar

- Setiap peserta memiliki **Permanent Member ID** (`hash_id`, contoh `4580`) yang
  **dibuat sekali dan tidak pernah berubah**.
- Saat membership **tidak aktif**, peserta ditampilkan dengan **Non-Member Code**
  (`NM0012`). Saat aktif, ditampilkan dengan **Member ID** (`4580`).

```text
Belum member        → Display: NM0012
Beli membership     → Display: 4580
Expired / cancel    → Display: NM0012   (hash tetap 4580)
Perpanjang (renew)  → Display: 4580
```

- Membership menyimpan status: `pending`, `active`, `expired`, `cancelled`.
- Harga & durasi **dinamis** — diambil dari tabel **Plan Membership**, bukan hardcoded.

---

## 2. Mengelola Plan Membership (Paket & Harga)

**Menu:** Sidebar → *Plan Membership* (`/admin/membership-plans`) — hanya `admin_full_access`.

**Cara menambah plan:**

1. Buka menu **Plan Membership**.
2. Klik **Tambah Plan**.
3. Isi:
   - **Key** — identitas plan (mis. `tahunan`), tidak boleh sama.
   - **Name** — label tampil (mis. "Tahunan").
   - **Harga** — mis. `150000`.
   - **Durasi** — angka (mis. `12`) + **Unit** (`months` / `days`).
   - **Status** aktif/nonaktif dan urutan tampil.
4. Klik **Simpan**.

**Menambah/mengubah/menghapus:** gunakan tombol **Edit** / **Hapus** pada baris plan.

> Plan **tidak bisa dihapus** jika sudah dipakai peserta atau riwayat membership.
> Nonaktifkan saja (`is_active = false`) bila ingin menyembunyikannya.

Contoh plan bawaan (seeder):

| Key | Nama | Durasi | Harga |
|-----|------|--------|-------|
| `tahunan` | Tahunan | 12 bulan | Rp 400.000 |
| `setengah_tahun` | Setengah tahun | 6 bulan | Rp 250.000 |
| `mingguan` | Mingguan | 7 hari | Rp 10.000 |

---

## 3. Memberi Membership ke Peserta (oleh Admin)

**Menu:** Sidebar → *Memberships* (`/admin/memberships`) → tombol **Beri Membership**.

**Cara penggunaan:**

1. Buka menu **Memberships**.
2. Klik **Beri Membership** (membuka `/admin/memberships/create`).
3. Pilih **Peserta** dari daftar.
4. Pilih **Tipe Membership** (plan).
5. (Opsional) isi **Durasi (bulan)** — kosongkan untuk memakai default plan.
6. Klik **Berikan Membership**.

**Yang terjadi di sistem:**

- Membership aktif lama peserta otomatis di-`cancelled`.
- Dibuat riwayat membership baru berstatus **active**.
- Kolom membership peserta (tipe/tanggal mulai/tanggal berakhir) diperbarui.
- Peserta menerima **notifikasi**.

---

## 4. Melihat Status & Statistik

Halaman **Memberships** menampilkan kartu statistik:

| Statistik | Arti |
|-----------|------|
| Total | Semua riwayat membership |
| Active | Membership yang sedang aktif |
| Pending | Menunggu konfirmasi pembayaran |
| Expired | Sudah kadaluarsa |
| Expiring Soon | Akan berakhir dalam 7 hari |
| Revenue | Total harga membership aktif |

Gunakan kolom **cari**/filter status untuk menyaring riwayat.

---

## 5. Membatalkan Membership

**Menu:** Memberships → tombol **Cancel** pada riwayat yang aktif.

1. Klik **Cancel** pada baris membership.
2. Konfirmasi pada dialog.
3. Status riwayat menjadi `cancelled`; kolom membership peserta direset ke `none`.

> Peserta juga dapat membatalkan sendiri via API `POST /api/v1/membership/cancel`.

---

## 6. Pembelian Membership oleh Peserta (Alur Pembayaran)

Peserta membeli lewat frontend/API, lalu bendahara mengonfirmasi.

**Alur:**

```text
Peserta → POST /api/v1/membership/subscribe
   → dibuat riwayat (pending) + Payment (pending)
   → notifikasi ke bendahara/admin
Bendahara → Payments → Confirm
   → membership diaktifkan (active), tanggal dihitung dari hari ini
```

**Peserta (API):**

```http
POST /api/v1/membership/subscribe
{
  "membership_type": "tahunan",
  "payment_method": "transfer",
  "duration_months": 12
}
```

- `membership_type` wajib, harus **key** plan aktif.
- `payment_method` wajib: `transfer` | `cash` | `qris`.
- `payment_proof` opsional (gambar, maks 5MB).

**Bendahara:** buka menu **Payments**, buka detail, lalu **Confirm** (atau **Reject**).
Konfirmasi otomatis mengaktifkan membership peserta.

---

## 7. Kadaluarsa & Perpanjangan Otomatis

- Saat halaman Memberships dibuka, membership aktif yang `end_date` sudah lewat
  otomatis ditandai **expired**.
- **Auto-renewal**: bila masa aktif tersisa **≤ 7 hari**, sistem memperpanjang
  otomatis satu kali (sesuai plan) dan mengirim notifikasi.

---

## 8. Endpoint API Membership

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/membership` | Status membership user yang login |
| GET | `/api/v1/membership/plans` | Daftar paket & harga aktif |
| GET | `/api/v1/membership/history` | Riwayat membership user |
| POST | `/api/v1/membership/subscribe` | Ajukan pembelian membership |
| POST | `/api/v1/membership/cancel` | Batalkan membership |

---

## 9. Keanggotaan Gratis untuk Event

Apabila event bertanda **free for members** dan peserta memiliki membership aktif,
biaya pendaftaran otomatis **Rp 0** (tipe registrasi `membership`). Lihat
[event.md](event.md).

---

## Tips

- Buat plan dengan `key` yang jelas dan konsisten; `key` tersimpan pada peserta &
  riwayat, jadi hindari mengganti `key` plan yang sudah terpakai.
- Pastikan **queue worker** berjalan agar notifikasi membership terkirim.
