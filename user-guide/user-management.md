# Manajemen User & Role

Mengelola akun pengguna panel admin beserta role/hak aksesnya.

**Pengguna:** hanya `admin_full_access`.

---

## 1. Melihat Daftar User

**Menu:** Sidebar → **Users** (`/admin/users`).

Tabel menampilkan nama, email, role, status aktif, dan avatar.

---

## 2. Menambah User

**Cara penggunaan:**

1. Buka menu **Users** → klik **Tambah User**.
2. Isi:
   - **Name** — nama user.
   - **Email** — email login.
   - **Password** — password login.
   - **Role** — pilih role (lihat tabel di bawah).
   - **Avatar** — foto profil (opsional).
3. Klik **Simpan**.

---

## 3. Daftar Role

| Role | Akses menu utama |
|------|------------------|
| `admin_full_access` | Semua menu |
| `admin_laman` | Categories, Merchandise, Sponsors, Galleries, Albums, Organization, Attendance |
| `admin_member` | Participants, Memberships |
| `admin_bnh` | (dashboard) |
| `organizer` | Events |
| `bendahara` | Memberships, Payments |
| `sponsor` | Sponsors saja |
| `merchandise` | Merchandise |
| `gallery` | Galleries, Albums |
| `participant` | Hanya API/frontend publik |

> Role `participant` **tidak dapat** login ke panel admin.

---

## 4. Mengaktifkan / Menonaktifkan User

1. Buka menu **Users**.
2. Klik tombol **Toggle Active** pada baris user
   (`PUT /admin/users/{id}/toggle-active`).
3. User non-aktif (`is_active = false`) tidak dapat login.

---

## 5. Mengubah & Menghapus User

- **Ubah:** tombol **Edit** → ubah data/role → **Simpan**.
- **Hapus:** tombol **Hapus** → konfirmasi.

---

## 6. Activity Logging

Sistem mencatat aktivitas user (login, logout, dan operasi CRUD) untuk keperluan
audit.

---

## Tips

- Terapkan **prinsip hak akses minimal** — berikan role sesuai kebutuhan.
- Segera nonaktifkan akun staf yang tidak lagi bertugas.
- Ganti seluruh password akun demo bawaan sebelum produksi.
