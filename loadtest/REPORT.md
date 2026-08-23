# Stress Test Report — SH3 Event Management System

**Tanggal**: 2026-08-23 · **Target**: `http://localhost:8000` (container `sh3-app`, local Docker) · **Tool**: k6 v1.4.0
**Environment**: LOCAL dev Docker (bukan production). Hanya endpoint GET publik read-only. Nol mutasi data.

## 1. Stack yang Diuji

| Komponen | Nilai |
|---|---|
| App | Laravel 13, PHP 8.3, **`php artisan serve`** (PHP built-in server, 1 proses — bukan nginx/FPM) |
| DB | MySQL 8.0 (`sh3-mysql`, max_connections default = 151) |
| Redis | redis:7-alpine — cache db1, session+queue db0 |
| Worker | 1× `queue:work`, 1× `schedule:work` (dalam container app) |
| Resource limits | Tidak ada di compose; host: 8 core, 9.6 GB RAM |

## 2. Endpoint Dites (15, semua publik GET tanpa auth)

Weighted mix per iterasi k6: event detail 15%, participants 10%, upcoming 10%, events list 10% (cached), galleries 8%, album detail 7%, albums list 5%, categories/merch/sponsors/org-index 5% each, merch-detail 3%, org-stats 4%, org-tree 3%.

**Dikecualikan** (destructive/excluded): semua POST/PUT/PATCH/DELETE, upload (`events/{id}/register`, `profile/photo`, `orders/{id}/payment`), payment & membership subscribe/cancel, attendance sync-up/down, admin gallery Drive sync (`POST /admin/gallery-albums/sync`, `gallery:sync-gdrive`), seluruh `/admin/*`.

## 3. Hasil

### 3.1 Baseline → Load → Stress → Spike

| Fase | VUs | RPS | p50 | p90 | p95 | p99 | max | Error |
|---|---|---|---|---|---|---|---|---|
| baseline | 1–3 | 46.9 | 26 ms | 48 ms | 54 ms | — | 81 ms | 0% |
| load10 | 10 | 48.0 | 208 ms | 227 ms | 233 ms | — | 263 ms | 0% |
| load25 | 25 | 47.4 | 528 ms | 556 ms | 567 ms | — | 616 ms | 0% |
| load50 | 50 | 47.1 | 1058 ms | 1113 ms | 1129 ms | — | 1201 ms | 0% |
| load100 | 100 | 47.5 | 2102 ms | 2196 ms | 2212 ms | 2232 ms | 2278 ms | 0% |
| stress (ramp→200) | ≤200 | 47.5 | 2574 ms | 4230 ms | 4269 ms | — | 4411 ms | 0% |
| spike (10→100→10) | ≤100 | 47.6 | 1996 ms | 2171 ms | 2203 ms | — | 2284 ms | 0% |

(p99 untuk fase awal tidak terekam karena `summaryTrendStats` ditambahkan belakangan; fase `load100` rerun memilikinya.)

### 3.2 Resource peaks saat test

| Metrik | Nilai | Interpretasi |
|---|---|---|
| app CPU | ~90–148% (≈1 core) | **Saturated sejak awal** — ceiling single-process |
| app RAM | 118–191 MB | Sehat, naik saat spike lalu normal |
| MySQL CPU | ~11–13% | Sangat idle |
| MySQL connections | 2 (max 151) | Jauh dari exhaustion |
| Slow queries | 0 | Tidak ada query lambat |
| Redis memory | 1.4 MB; clients 6–7 | Sangat idle |
| Host load1 | ≤2.4 (8 core) | Aman; stack `owndangan-*` tidak terganggu signifikan |

### 3.3 Service time per endpoint (1 VU, tanpa queueing)

| Endpoint | avg | Catatan |
|---|---|---|
| `/api/v1/events/{id}/participants` | 29.3 ms | Paling lambat (eager-load nested) |
| `/api/v1/events/{id}` | 26.4 ms | Eager-load category+schedules+creator+galleries |
| `/api/v1/organization` | 26.2 ms | Query search dinamis |
| `/api/v1/events/upcoming` | 23.7 ms | |
| `/api/v1/galleries` | 23.0 ms | Join ke event + URL building |
| `/api/v1/gallery-albums` | 20.7 ms | count per album |
| `/api/v1/gallery-albums/{id}` | 20.2 ms | |
| `/api/v1/organization/stats` | 19.8 ms | Aggregate |
| `/api/v1/merchandise` | 19.2 ms | |
| `/api/v1/sponsors` | 19.0 ms | |
| `/api/v1/events` (cached) | 16.5 ms | Redis hit |
| `/api/v1/organization/tree` (cached) | 15.9 ms | Redis hit |
| `/api/v1/categories` (cached) | 15.4 ms | Redis hit |
| `/up` (tanpa DB) | 14.6 ms | **Floor = framework bootstrap** |

## 4. Analisis Bottleneck

**Fakta utama**: throughput terkunci di **~47–48 req/s pada SEMUA level concurrency**, latensi naik linear terhadap VU (Little's law: wait ≈ VUs / 47.5), error tetap 0% sampai 200 VU.

| # | Bottleneck | Severity | Bukti |
|---|---|---|---|
| 1 | **Single-process HTTP server (`php artisan serve`)** | **Critical** | RPS flat 47.5 dari 3→200 VU; CPU app ≈100% (1 core); DB/Redis idle; latensi = antrian murni |
| 2 | **Framework bootstrap ~14 ms/request** | High | `/up` (zero-DB) butuh 14.6 ms ≈ 68 req/s teoretis per proses; cached vs uncached hanya beda 1–2 ms |
| 3 | Endpoint eager-load berat (`event_detail`, `event_participants`) | Medium | 26–29 ms vs 15–16 ms cached — jadi penyumbang terbesar setelah overhead framework |
| 4 | Cache kurang efektif utk payload besar | Low | `events_list` cached hanya hemat ~7 ms pada dataset seed kecil; akan lebih berdampak saat data besar |
| 5 | Bukan bottleneck: MySQL (conn 2/151, slowQ 0), Redis (idle), RAM, host | — | Semua metrik jauh dari limit |

**Catatan arsitektur tambahan** (bukan hasil load test, tapi relevan): queue worker cuma 1 proses; Reverb WebSocket tidak dinyalakan oleh entrypoint; `PHP_CLI_SERVER_WORKERS` tidak diset.

## 5. Kesimpulan Kapasitas

- **Max stable concurrency (SLA p95 < 500 ms)**: **± 20 VU** (~24 pengguna aktif berpikir 1–2 detik antar klik)
- **Max stable concurrency (SLA p95 < 2 s)**: ± 95 VU
- **Batas absolut**: tidak ada error sampai 200 VU — server "hanya" makin lambat (queue), tidak crash. Degradasi bersifat graceful.
- **Throughput maksimum sistem: ~47–48 req/s** (≈ 4.1 juta request/hari) terlepas dari jumlah user.

## 6. Rekomendasi (urut prioritas)

1. **[Critical] Ganti `php artisan serve` → nginx + php-fpm** (atau Octane/RoadRunner/Swoole) di Dockerfile & entrypoint. Ini satu-satunya perbaikan yang mengubah throughput ceiling. FPM dengan `pm.max_children=16–32` diperkirakan melipatgandakan kapasitas ~10–30× (DB masih sangat idle).
   - Alternatif cepat (masih dev-grade): set `PHP_CLI_SERVER_WORKERS=8` di entrypoint → multi-worker built-in server, ~8× ceiling.
2. **[High] Kurangi bootstrap cost**: `php artisan config:cache route:cache view:cache event:cache` saat container start (entrypoint belum melakukannya untuk serve mode). Hemat beberapa ms × semua request.
3. **[Medium] Optimasi endpoint eager-load teratas** (`event_participants`, `event_detail`, `org_index`): pastikan index pada FK (`event_participants.event_id`, `galleries.gallery_album_id` — sudah ada composite index menurut test) dan pertimbangkan cache 60s utk participants list.
4. **[Medium] Naikkan queue worker** menjadi ≥4 proses (supervisor `numprocs=4` sudah ada di README produksi; entrypoint Docker hanya menjalankan 1).
5. **[Low] Set `maxmemory` + eviction policy di Redis dan turunkan `max_connections` MySQL sesuai kebutuhan riil** — sekarang default, tidak masalah pada beban read-only tapi penting saat write-heavy.
6. **[Info] Reverb tidak jalan di deployment Docker** — jalankan `reverb:start` di entrypoint jika notifikasi real-time dipakai di produksi.

## 7. Reproduksi

```bash
cd loadtest
~/.local/bin/k6 run --summary-export results/x.json k6/perendpoint.js   # service time ranking
./run.sh baseline load10 load25 load50 load100 stress spike             # full suite (~15 min)
python3 summarize.py                                                    # tabel ringkas
```

File: `k6/stress.js` (skenario weighted, threshold abort `err>5%`/`p95>15s`), `k6/perendpoint.js`, `monitor.sh` (CSV metrik container/host/DB/Redis per 2–6 s), `summarize.py`. Hasil mentah di `results/*.json|log|csv`.
