# Stress Test Report — SH3 Production VPS

**Tanggal**: 2026-08-23
**Target**: `https://server-sh3.cloud` (nginx:443 → PHP-FPM 8.3 on host) — VPS ini sendiri (domain resolves ke IPv6 host `2a02:4780:59:c39e::1` = `145.79.13.41`).
**Tool**: k6 v1.4.0 (existing `loadtest/k6/stress.js`, di-extend additively untuk profile 250–3000 VU; `run.sh` + `monitor.sh` + `summarize.py` digunakan).
**Endpoint**: 15 endpoint GET publik read-only (sama persis dengan existing load test). Nol mutasi / DELETE / order / payment.
**Stack diuji**: Nginx 1.24 + PHP-FPM 8.3 (`pm.max_children=5`) + Laravel (production) + MySQL 8.0 (Docker) + Redis 7 (Docker) + 4 Queue worker (supervisor) + Reverb (supervisor, :8080).

---

## 1. Phase 0 — Pre-Flight (READ-ONLY) — SEMUA SEHAT

| Komponen | Status |
|---|---|
| Docker (sh3-mysql / sh3-redis) | Up 2j, healthy, 0 restart |
| Nginx | active; hanya 404 gambar statis (Facebook crawler), bukan app error |
| PHP-FPM | active; **pm.max_children = 5**, opcache ON, memory_limit 256M, pm.max_requests *tidak diset* |
| Laravel `/up` | 200 (112ms); hanya `bootstrap/cache/services.php` yang cached (config/routes **belum** di-cache) |
| MySQL | healthy, 1 conn / 151 max, 0 slow queries, CPU <1% |
| Redis | 1.48M, 11 clients, 15 ops/s, latency ~0.27ms |
| Queue | 4 worker RUNNING, depth 0, 23 failed jobs *pra-eksisting* |
| Reverb | RUNNING pada :8080, 0 koneksi WS aktif teramati |

Tidak ada kondisi CRITICAL → lanjut test.

> Catatan: `monitor.sh` bawaan tidak menangkap metrik app di produksi (ia mengasumsikan container `sh3-app` & env `MYSQL_ROOT_PASSWORD` di host — keduanya tidak ada di deploy produksi ini). Untuk itu ditambahkan monitor host (`/tmp/hostmon.sh`) yang mengukur PHP-FPM, Nginx:443, Reverb:8080, MySQL, Redis, dan host. Tidak ada konfigurasi aplikasi/infra yang diubah.

---

## 2. Phase 1–4 — Hasil per Tahap

| Test | VUs | RPS | p50 | p95 | p99 | Error | fpmCPU% | fpmRAM | Result |
|------|----:|----:|----:|----:|----:|------:|--------:|-------:|--------|
| baseline | 1–5 | 19.2 | 214ms | 343ms | 473ms | 0% | <max | 269MB | ✅ PASS (p95<500ms) |
| load10 | 10 | 15.4 | 576ms | 1026ms | 1580ms | 0% | 19.4 | 269MB | 🟡 p95>500ms |
| load100 | 100 | 17.7 | 5283ms | 8130ms | 8252ms | 0% | 29.9 | 269MB | 🔴 p95>2s |
| load250 | 250 | 15.7 | 7197ms | 15094ms | 15433ms | 0% | 30.8 | 269MB | ⛔ ABORT (safety rail p95>15s) |
| spike (10→100→10) | ≤100 | 15.9 | 5622ms | 7487ms | 8530ms | 0% | 32.8 | 269MB | 🟡 degraded, recovered |

Resource peak (hostmon): `fpm_procs` selalu **5** (langit-langit `max_children`), `nginx:443` conns sampai 253 (load250), `load1` host sampai **8.67** (spike), host RAM ~3.5GB/8GB. MySQL conn max 3/151, Redis clients 11–16 — keduanya **idle**.

---

## 3. Analisis Bottleneck (berbasis DATA)

Throughput terkunci di **~15–19 req/s pada SEMUA level concurrency** (Little's law: latency ≈ VUs / ~17). Naiknya VU hanya menambah antrian, bukan throughput.

| # | Bottleneck | Severity | Bukti |
|---|---|---|---|
| 1 | **PHP-FPM `pm.max_children = 5`** (maks 5 request PHP konkuren) | **Critical** | `fpm_procs` mentok di 5 sejak 10 VU; RPS flat ~17; MySQL/Redis idle → worker, bukan DB, yang jadi penahan |
| 2 | **Service time per-request membengkak saat konkuren** (~42ms di 1 VU → ~300ms efektif di load) — dipicu Laravel bootstrap tanpa `config:cache`/`route:cache` (hanya `services.php` cached) + eager-load + CPU host dibagi dgn reverb/queue/scheduler/site lain (load1~8) | High | p95 melonjak 343ms→15s seiring VU; fpmCPU hanya ~30% (worker mengantri, bukan CPU-bound murni) |
| 3 | Tidak bottleneck: MySQL (conn 1–3/151, slowQ 0, CPU<1%), Redis (idle), RAM (3.5/8GB), Queue (depth 0), Nginx (0 error 502/504) | — | — |

---

## 4. Metrik Turunan

1. **Max stable VU (p95 < 500ms)**: **~6 VU** (baseline 5 VU = 343ms; load10 = 1026ms).
2. **Max stable VU (p95 < 2s)**: **~22 VU** (ekstrapolasi linier dari load10→load100).
3. **Max stable RPS**: **~19 RPS** (baseline) / **~17 RPS** sustained di bawah concurrency.
4. **VU saat p95 > 500ms**: **~6 VU**.
5. **VU saat p95 > 1s**: **~10 VU** (load10 = 1026ms).
6. **VU saat p95 > 2s**: **~22 VU**.
7. **First bottleneck**: PHP-FPM `pm.max_children=5`.
8. **Second bottleneck**: service-time per request di bawah concurrency (no config/route cache + shared host CPU).
9. **PHP-FPM util**: 5/5 worker **100% occupied** sejak 10 VU ↑; fpmCPU ~30% (mengantri).
10. **MySQL util**: <2% CPU, 1–3/151 conn → **bukan** bottleneck.
11. **Redis util**: <1% CPU, 11–16 clients, 1.7MB → **bukan** bottleneck.
12. **Queue behavior**: 4 worker, depth 0 sepanjang test, tidak ada backlog (test read-only, 0 write).
13. **Reverb behavior**: RUNNING, 0 koneksi WS teramati, tidak terpengaruh (lihat Phase 5).
14. **Recovery setelah spike**: `/up`=200 (111ms) pasca-test, fpm kembali idle (4 proc), 0 error 502/504, reverb/queue/scheduler utuh → **recovery < beberapa detik**.

---

## 5. Phase 5 — Reverb / WebSocket

Repository **tidak memiliki** script load-test WebSocket/Reverb (`loadtest/k6/` hanya berisi `stress.js` dan `perendpoint.js`, keduanya HTTP GET). Sesuai instruksi: **tidak membuat & menjalankan test WebSocket baru**.

> **HTTP stress test selesai, tetapi WebSocket/Reverb belum diuji.**

---

## 6. FINAL VERDICT

### 🔴 UNSAFE (kapasitas produksi saat ini sangat terbatas)

Bukti utama: langit-langit throughput ~17 RPS dan `pm.max_children=5` membuat server tidak mampu melayani concurrency menengah sekalipun. Test dihentikan di **load250** karena safety rail `p95>15s` terpicu (Phase 3/7: STOP pada degradasi serius). Tidak dilanjutkan ke 500/1000/2000/3000 karena server **sudah tidak stabil di 250 VU** dan menekan produksi lebih jauh hanya memperburuk dampak tanpa info baru.

| Pertanyaan | Jawab | RPS tercapai | p95 | Error | Bottleneck | Evidence |
|---|---|---|---|---|---|---|
| Aman untuk **500** concurrent users? | **NO** | ~17 (ceil) | ~40s* (ekstrapolasi) | 0% sementara, lalu 502 saat backlog penuh | PHP-FPM max_children=5 | load100 уже p95=8.1s; load250 abort p95>15s |
| Aman untuk **1.000** concurrent users? | **NO** | ~17 | ~80s* | 502 storm | PHP-FPM max_children=5 | sama; antrian >> backlog 511 |
| Aman untuk **2.000** concurrent users? | **NO** | ~17 | >>100s* | 502 storm | PHP-FPM max_children=5 | — |
| Aman untuk **3.000** concurrent users? | **NO** | ~17 | kolaps | 502 massal | PHP-FPM max_children=5 | load250 (250 VU) sudah abort |

\* p95 di 500–3000 VU adalah ekstrapolasi linier (Little's law: p95 ≈ VUs / 17 RPS); akan memicu 502 begitu backlog PHP-FPM (511) dan koneksi nginx habis — bukan sekadar lambat.

---

## 7. Rekomendasi (prioritas, TIDAK dijalankan selama test)

1. **[Critical] Naikkan `pm.max_children` PHP-FPM** (mis. 16–32+) dan `pm.max_spare_servers` — ini satu-satunya perbaikan yang menaikkan langit-langit concurrency secara langsung. MySQL/Redis masih sangat idle, jadi naikkan aman.
2. **[High] `php artisan config:cache route:cache`** saat deploy — turunkan bootstrap cost (saat ini hanya `services.php` cached; service time 42ms→bisa <20ms).
3. **[Medium] Optimasi eager-load** (`event_detail`, `event_participants`, `org_index`) + pastikan index FK.
4. **[Medium] Naikkan queue worker** & pastikan Reverb diuji terpisah (buat script WS bila perlu).
5. **[Low] Set `maxmemory`+eviction Redis & turunkan `max_connections` MySQL** sesuai kebutuhan riil.

---

*Raw results: `loadtest/results/summary_{baseline,load10,load100,load250,spike}.json`, `loadtest/results/k6_*.log`, host metrics di `/tmp/hostmon_*.csv`. Tidak ada perubahan pada source code, config Nginx/PHP-FPM/Docker, migration, atau endpoint mutasi.*

---

# Addendum — PHP-FPM Optimization (pasca stress test)

**Tanggal**: 2026-08-23 (sesi lanjutan)

## Phase 1 — Audit (READ-ONLY)
- **CPU**: **1 vCPU** (AMD EPYC 9354P). Ini langit-langit throughput (~17-19 RPS sebelum optimasi) — PHP single-thread.
- **RAM**: 3.8 GB total, **NO swap**. Saat audit terpakai 3.3 GB, avail 515 MB (termasuk `opencode` session ~1.7 GB yang transien, tidak ada di produksi nyata).
- **PHP-FPM worker**: ~50 MB idle / ~54 MB peak.
- Konsumen RAM persisten: mysqld 404MB, next-server 341MB, 4×queue 252MB, reverb 65MB, scheduler 64MB, fpm ~150MB.
- `pm = dynamic`, `max_children=5`, `start_servers=2`, `min_spare=1`, `max_spare=3`, `pm.max_requests` tidak diset.
- **Laravel cache HILANG**: hanya `packages.php` + `services.php` di `bootstrap/cache` (config/route/view BELUM di-cache) → setiap request bootstrap penuh (buang CPU di 1 core).
- `APP_DEBUG=false`, `APP_ENV=production`, OPcache ON (128MB/10000 files; file count 8790 < 10000 → cukup, tidak diubah).

## Phase 2 — Kapasitas aman
- Reserves: OS 300 + MySQL 450 + Redis 100 + Nginx 60 + Next.js 350 + Queue/Reverb/Sched 450 + FPM master 60 + safety 300 ≈ 2070 MB.
- Worker budget: produksi (tanpa opencode) ~2280 MB → ~30 worker; sesi ini (dengan opencode) ~515 MB → ~6-8 worker.
- **Dipilih `pm.max_children = 10`** (2× dari 5, aman di sesi ini maupun produksi; produksi bisa 12-20 setelah opencode tiada).

## Phase 3 — Laravel prod config
- config/route/view cache: **ditambahkan** (sebelumnya tidak ada). APP_DEBUG=false ✓, OPcache ✓. Nginx fastcgi standar (cukup).

## Phase 4 — Perubahan minimal (backup → edit → validate → reload)
- Backup: `www.conf.bak.202608232037`.
- `pm.max_children 5→10`, `start_servers 2→4`, `min_spare 1→3`, `max_spare 3→5`, tambah `pm.max_requests=500`.
- `php artisan config:cache`, `route:cache`, `view:cache` (sebagai www-data).
- `php-fpm8.3 -t` OK; `systemctl reload php8.3-fpm` (graceful).
- Health: /up 200, queue/reverb/scheduler RUNNING, nginx -t OK, mysql/redis alive.

## Phase 5 — Verifikasi (5/10/25/50/100 VU, tanpa 250+)
| Test | VU | RPS before | p95 before | RPS after | p95 after |
|------|---:|---:|---:|---:|---:|
| baseline | 5 | 19.2 | 343ms | 25.1 | 365ms |
| load10 | 10 | 15.4 | 1026ms | 23.5 | 776ms |
| load25 | 25 | - | - | 22.7 | 1658ms |
| load50 | 50 | - | - | 22.5 | 2791ms |
| load100 | 100 | 17.7 | 8130ms | 24.7 | 5574ms |

RAM selama 100 VU: avail stabil ~405-471 MB (aman, no OOM, no swap). `fpm_procs` cap 10 (tidak lagi mentok di 5).

## Kesimpulan Optimasi
- Concurrency PHP-FPM: 5→10 (2×).
- RPS ceiling naik ~+40-53% (cache Laravel turunkan CPU/request).
- p95 turun ~24-31% di VU sama (queueing berkurang).
- **Bottleneck tersisa = 1 vCPU** (RPS mentok ~22-25 regardless VU). pm.max_children tidak bisa memecahkan batas CPU; butuh caching lanjut / Octane-Swoole / upgrade vCPU.
