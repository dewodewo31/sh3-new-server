# Panduan Pengguna SH3 Event Management

Kumpulan panduan penggunaan fitur **SH3 Event Management System**. Setiap berkas
menjelaskan **apa fungsi fitur** dan **langkah-langkah cara memakainya**, baik dari
panel admin (web) maupun dari API/frontend publik.

> Panduan ini ditulis untuk pengguna (admin, bendahara, organizer, peserta). Untuk
> dokumentasi teknis/arsitektur, lihat folder [`docs/`](../docs/).

## Daftar Panduan

| Berkas | Fitur | Pengguna utama |
|--------|-------|----------------|
| [login.md](login.md) | Login & Akses (panel admin & peserta) | Semua |
| [user-management.md](user-management.md) | Manajemen User & Role | Admin Full Access |
| [participant.md](participant.md) | Data Peserta & Member ID | Admin Member |
| [membership.md](membership.md) | Membership & Plan | Admin Member, Bendahara |
| [event.md](event.md) | Manajemen Event | Admin, Organizer |
| [category.md](category.md) | Kategori Event | Admin Laman |
| [attendance.md](attendance.md) | Absensi & Scan QR | Admin Laman |
| [payment.md](payment.md) | Pembayaran | Bendahara |
| [merchandise.md](merchandise.md) | Merchandise & Order | Admin, Merchandise |
| [gallery.md](gallery.md) | Galeri & Album | Admin Laman, Gallery |
| [sponsor.md](sponsor.md) | Sponsor | Admin Laman, Sponsor |
| [organization.md](organization.md) | Struktur Organisasi | Admin Laman |
| [notification.md](notification.md) | Notifikasi | Semua |

## Peran (Role) Pengguna

| Role | Akses menu utama |
|------|------------------|
| `admin_full_access` | Semua menu |
| `admin_laman` | Categories, Merchandise, Sponsors, Galleries, Albums, Organization, Attendance |
| `admin_member` | Participants, Memberships |
| `admin_bnh` | (dashboard) |
| `organizer` | Events |
| `bendahara` | Memberships, Payments |
| `sponsor` | Sponsors (saja) |
| `merchandise` | Merchandise |
| `gallery` | Galleries, Albums |
| `participant` | Hanya API/frontend publik (tidak bisa masuk panel admin) |

## Cara Membuka Panel Admin

1. Buka `https://<domain-anda>/login`.
2. Masukkan **email** dan **password** akun admin.
3. Setelah login Anda diarahkan ke `/admin/dashboard`.
4. Menu di sidebar muncul sesuai role Anda.

## Akun Demo (hasil seeder)

| Role | Email | Password |
|------|-------|----------|
| Admin Full Access | admin.full@sh3.com | password |
| Admin Laman | admin.laman@sh3.com | password |
| Admin Member | admin.member@sh3.com | password |
| Organizer | organizer@sh3.com | password |
| Bendahara | bendahara@sh3.com | password |
| Sponsor | sponsor@sh3.com | password |
| Merchandise | merchandise@sh3.com | password |
| Admin Gallery | admin.gallery@sh3.com | password |

> Ganti seluruh password ini sebelum produksi.
