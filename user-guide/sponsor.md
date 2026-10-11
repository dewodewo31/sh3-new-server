# Sponsor

Mengelola data sponsor event SH3, tier, kontak, dan logo.

**Pengguna:** `admin_full_access`, `admin_laman`, `sponsor`.

---

## 1. Melihat Daftar Sponsor

**Menu:** Sidebar → **Sponsors** (`/admin/sponsors`).

Tabel menampilkan nama, tier, kontak, tahun, dan status.

> Role `sponsor` hanya memiliki akses ke menu ini dan langsung diarahkan ke
> `/admin/sponsors` setelah login.

---

## 2. Menambah Sponsor

**Cara penggunaan:**

1. Buka menu **Sponsors** → klik **Tambah Sponsor**.
2. Isi:
   - **Name** — nama sponsor/perusahaan.
   - **Tier** — tingkat sponsorship: `platinum`, `gold`, `silver`, `bronze`.
   - **Contact Person / Contact Email / Contact Phone** — kontak.
   - **Website** — situs sponsor.
   - **Year** — tahun kerja sama.
   - **Sponsorship Value** — nilai sponsorship (opsional).
   - **Sort Order** — urutan tampil.
   - **Logo** — unggah logo.
   - **Description** — deskripsi.
3. Klik **Simpan**.

---

## 3. Mengubah & Menghapus

- **Ubah:** tombol **Edit** → ubah → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

---

## 4. Sponsor & Event

Sponsor dapat dikaitkan dengan **banyak event** (many-to-many). Detail event
menampilkan daftar sponsornya. Endpoint publik `GET /api/v1/sponsors` menampilkan
daftar sponsor untuk halaman `/sponsor` di frontend.

---

## Tips

- Gunakan **Tier** yang konsisten agar halaman sponsor publik tersusun rapi.
- Unggah **logo** berlatar transparan (PNG) untuk tampilan terbaik.
