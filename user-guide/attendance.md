# Absensi & Scan QR

Mencatat kehadiran peserta event melalui scan QR (check-in / check-out), input manual,
serta laporan absensi. Mendukung mode offline (sinkronisasi).

**Pengguna:** `admin_full_access`, `admin_laman`.

---

## 1. Melihat Laporan Absensi

**Menu:** Sidebar → *Attendance* (`/admin/attendance/report`).

- Pilih event untuk melihat rekap kehadiran (hadir, check-in/out, waktu).
- Filter dan ekspor data absensi sesuai kebutuhan.

**Absensi per event:** `/admin/attendance/event/{eventId}`.

---

## 2. Scan QR (Check-in / Check-out)

**Menu:** Sidebar → *Scan QR* (`/admin/attendance/scan`).

**Cara penggunaan:**

1. Buka halaman **Scan QR**.
2. Pada panel **Input Manual**, pilih **Event** yang sedang berlangsung.
3. Pilih **Aksi Scan**:
   - **Check-in** — mencatat waktu hadir.
   - **Check-out** — mencatat waktu keluar.
4. **Aktifkan kamera**: klik **Mulai Scan**, lalu arahkan kamera ke QR peserta.
   - Kamera memindai otomatis (20 fps, jeda antar-scan 1,5 detik).
   - Bila berhasil, muncul nama peserta, event, dan waktu dalam panel **Hasil Scan**.
5. **Kamera tidak tersedia?** Ketik **Kode QR** peserta (mis. `3950` atau `NM0001`)
   pada kolom **Kode QR**, lalu klik **Proses Check-in / Check-out**.
6. Setelah hasil muncul, klik **Scan Berikutnya** untuk memindai peserta lain.

**Catatan penting:**

- Akses kamera butuh **HTTPS** atau `localhost`, dan izin kamera browser.
- Pastikan **mode aksi** (Check-in/Check-out) sudah benar **sebelum** scan.
- QR peserta hanya berlaku untuk 1 peserta pada 1 event.

---

## 3. Generate QR Peserta per Event

Bila peserta belum punya QR:

1. Buka **Attendance per event** atau detail event.
2. Klik **Generate QR** pada peserta yang diinginkan
   (`POST /admin/attendance/event-participant/{id}/generate-qr`).
3. QR dapat dicetak/ditunjukkan ke peserta.

---

## 4. Membatalkan Absensi (Invalidate)

Bila terjadi kesalahan scan, gunakan aksi **Invalidate** pada baris absensi
(`POST /admin/attendance/{id}/invalidate`) untuk membatalkan catatan kehadiran.

---

## 5. Mode Offline (Sinkronisasi)

Untuk lokasi tanpa jaringan:

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/v1/attendance/sync-up` | Unggah data absensi offline ke server |
| GET | `/api/v1/attendance/sync-down` | Unduh data peserta untuk dipakai offline |

---

## 6. Endpoint API Absensi

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/v1/attendance/check-in` | Check-in |
| POST | `/api/v1/attendance/check-out` | Check-out |
| POST | `/api/v1/attendance/scan` | Scan QR |
| GET | `/api/v1/attendance/report` | Laporan absensi |
| GET | `/api/v1/attendance/{eventId}` | Absensi per event |

QR berisi **kode peserta murni**: member `3950`, non-member `NM0001`.

---

## Tips

- Gunakan **input manual** sebagai cadangan saat QR sulit terbaca atau kamera bermasalah.
- Tandai mode dengan jelas (Check-in vs Check-out) agar waktu masuk/keluar tercatat benar.
- Cek laporan absensi secara berkala untuk memastikan tidak ada data ganda.
