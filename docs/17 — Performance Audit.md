# 17 — Performance Audit Report

> **Tanggal**: 19 Agustus 2026
> **Metodologi**: PHP Benchmark (50 iterations, 5 warmup), Laravel Kernel Query Analyzer, curl_multi Concurrency Test (50-100 requests, 10 concurrent)
> **Lingkup**: Semua API endpoints, database query patterns, indexing, caching, memory, concurrency
> **Status**: AUDIT ONLY — Tidak ada perubahan kode

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Lingkungan](#2-lingkungan)
3. [Dataset](#3-dataset)
4. [HTTP Benchmark](#4-http-benchmark--response-time--throughput)
5. [Query Count & Database Time](#5-query-count--database-time)
6. [N+1 Detection](#6-n1-detection)
7. [Index Audit](#7-index-audit)
8. [Redis Cache Analysis](#8-redis-cache-analysis)
9. [OPcache & PHP-FPM](#9-opcache--php-fpm)
10. [Memory Usage](#10-memory-usage)
11. [Concurrency & Race Condition](#11-concurrency--race-condition)
12. [Response Size & Payload](#12-response-size--payload)
13. [Bugs Ditemukan](#13-bugs-ditemukan)
14. [Prioritas Rekomendasi](#14-prioritas-rekomendasi)
15. [Analisis Endpoint Per-Endpoint](#15-analisis-endpoint-per-endpoint)
16. [Methodology Notes](#16-methodology-notes)

---

## 1. Ringkasan Eksekutif

### Temuan Kritis

| # | Temuan | Severity | Impact |
|---|--------|----------|--------|
| C1 | **Galleries endpoint 500 error** — `Cannot access protected property GalleryRepository::$model` | CRITICAL | Gallery tidak bisa diakses sama sekali |
| C2 | **N+1: sync-down** — 34 queries, 32x participant lookup satu per satu | CRITICAL | Akan memburuk linearly seiring data |
| C3 | **N+1: organization** — 11 queries, 8x membership_plans lookup satu per satu | CRITICAL | Endpoint utama admin panel |
| C4 | **JIT disabled** — `opcache.jit_buffer_size = 0` | HIGH | PHP menjalankan bytecode interpretasi penuh |
| C5 | **Redis caching tidak digunakan** — hanya 6 keys (Laravel internal), 0 application cache | HIGH | Semua data dari DB setiap request |
| C6 | **Missing indexes** pada 7 kolom yang sering di-query | HIGH | Scan bertambah seiring data |
| C7 | **No slow query log** — `slow_query_log = OFF` | MEDIUM | Tidak bisa detect slow queries di production |

### Angka Penting

| Metrik | Nilai |
|--------|-------|
| Total endpoints diuji | 26 |
| Rata-rata response time (read) | 13-28ms (single request) |
| P99 terberat | 306ms (login) |
| Query terbanyak | 34 (sync-down) |
| N+1 kandidat | 3 endpoint |
| Missing indexes | 7 kolom |
| Application cache hits | 0 |
| Error/bug ditemukan | 1 (galleries 500) |

---

## 2. Lingkungan

| Komponen | Versi / Nilai |
|----------|---------------|
| PHP | 8.3.33 |
| Laravel | 13.23.0 |
| MySQL | 8.0.46 |
| Redis | 7.4.10 |
| Server | PHP-FPM + Nginx (managed by Docker) |
| CPU | 8 cores |
| RAM | ~10 GB |
| Storage | NVMe |
| memory_limit | 128M |
| OPcache | ON (enable=1, 128MB) |
| JIT | tracing mode, **buffer=0 (disabled)** |
| PHP-FPM config | Docker defaults (zz-docker.conf) |

---

## 3. Dataset

| Tabel | Jumlah Baris |
|-------|-------------|
| users | 29 |
| participants | 20 |
| events | 6 |
| event_participants | 56 |
| attendances | 32 |
| payments | 28 |
| merchandise | 8 |
| merchandise_orders | 24 |
| galleries | 45 |
| sponsors | 10 |
| categories | 4 |
| organization_members | 8 |
| membership_plans | 3 |
| membership_histories | 16 |
| notifications | 0 |

> **Catatan**: Dataset sangat kecil. Masalah N+1 dan index saat ini tersembunyi oleh volume kecil. Akan ter eksponensial pada data production.

---

## 4. HTTP Benchmark — Response Time & Throughput

**Metodologi**: 5 warmup + 50 iterasi per endpoint. Sequential single-threaded.

| Endpoint | Avg | P50 | P95 | P99 | RPS | Response Size |
|----------|-----|-----|-----|-----|-----|---------------|
| POST /api/v1/auth/login | 258.8ms | 257.7ms | 279.1ms | 306.3ms | 3.9 | 499B |
| GET /api/v1/events | 26.7ms | 26.9ms | 30.1ms | 30.9ms | 37.4 | 4.8KB |
| GET /api/v1/events/upcoming | 23.5ms | 23.6ms | 26.2ms | 27.2ms | 42.6 | 2.0KB |
| GET /api/v1/events/{id} | 28.3ms | 28.2ms | 32.2ms | 35.2ms | 35.3 | 2.8KB |
| GET /api/v1/categories | 19.1ms | 19.1ms | 21.6ms | 21.8ms | 52.4 | 836B |
| GET /api/v1/galleries | 15.7ms | 15.7ms | 18.8ms | 22.6ms | 63.7 | **500 ERROR** |
| GET /api/v1/sponsors | 13.1ms | 13.1ms | 15.1ms | 16.5ms | 76.6 | 4.2KB |
| GET /api/v1/organization | 13.4ms | 13.3ms | 15.8ms | 17.4ms | 74.6 | 3.5KB |
| GET /api/v1/organization/stats | 24.4ms | 24.2ms | 28.9ms | 29.6ms | 41.0 | 276B |
| GET /api/v1/organization/tree | 14.7ms | 14.6ms | 17.0ms | 17.7ms | 68.1 | 8.0KB |
| GET /api/v1/merchandise | 12.8ms | 12.6ms | 15.6ms | 16.7ms | 78.0 | 1.9KB |
| GET /api/v1/profile | 19.1ms | 18.9ms | 22.4ms | 23.1ms | 52.3 | 1.5KB |
| GET /api/v1/auth/me | 16.6ms | 16.5ms | 19.1ms | 19.3ms | 60.4 | 297B |
| GET /api/v1/my-events | 18.4ms | 18.2ms | 21.3ms | 21.9ms | 54.3 | 1.3KB |
| GET /api/v1/notifications | 16.8ms | 16.7ms | 19.5ms | 20.2ms | 59.4 | 516B |
| GET /api/v1/notifications/unread-count | 14.2ms | 14.3ms | 16.1ms | 16.6ms | 70.2 | 39B |
| GET /api/v1/membership | 17.6ms | 17.4ms | 20.7ms | 21.8ms | 57.0 | 594B |
| GET /api/v1/membership/plans | 14.2ms | 14.2ms | 16.5ms | 17.1ms | 70.4 | 399B |
| GET /api/v1/participants | 23.6ms | 23.5ms | 27.3ms | 27.8ms | 42.3 | 8.9KB |
| GET /api/v1/participants/{id} | 18.0ms | 17.8ms | 20.6ms | 21.0ms | 55.6 | 1.4KB |
| GET /api/v1/payments/history | 16.9ms | 16.8ms | 19.3ms | 20.0ms | 59.1 | 388B |
| GET /api/v1/attendance/sync-down | 47.0ms | 45.3ms | 60.1ms | 62.9ms | 21.3 | 9.1KB |
| GET /api/v1/attendance/report | 24.4ms | 24.2ms | 28.5ms | 30.8ms | 41.0 | ~large |
| GET /api/v1/merchandise/orders | 16.9ms | 16.8ms | 19.7ms | 20.0ms | 59.1 | ~large |
| GET /api/v1/attendance/{eventId} | 37.1ms | 36.9ms | 41.0ms | 41.4ms | 27.0 | ~large |

### Analisis

- **Login (258ms)**: Terberat karena `Hash::make()` / bcrypt — ini by design, bukan bug. Tapi perlu rate limiting di production.
- **Read endpoints (13-28ms)**: Sangat cepat untuk dataset kecil. Metric ini tidak representatif untuk production.
- **sync-down (47ms)**: Paling berat di antara read endpoints karena 34 queries.
- **RPS Login (3.9)**: Rentan terhadap brute force — rate limiting wajib di production.

---

## 5. Query Count & Database Time

| Endpoint | Queries | Wall Time | DB Time | DB% | Query Types |
|----------|---------|-----------|---------|-----|-------------|
| POST /auth/login | 4 | 313.1ms | 14.1ms | 4.5% | SELECT:1, UPDATE:1, INSERT:2 |
| GET /events | 6 | 26.7ms | 5.6ms | 21.0% | SELECT:6 |
| GET /events/upcoming | 4 | 8.0ms | 3.4ms | 42.5% | SELECT:4 |
| GET /events/{id} | 6 | 22.6ms | 5.7ms | 25.2% | SELECT:6 |
| GET /categories | 2 | 5.4ms | 1.9ms | 35.2% | SELECT:2 |
| GET /galleries | **0** (500) | 12.4ms | 0ms | 0% | — |
| GET /sponsors | 1 | 5.6ms | 1.7ms | 30.4% | SELECT:1 |
| GET /organization | **11** WARN | 22.0ms | 7.2ms | 32.7% | SELECT:11 |
| GET /organization/stats | 5 | 5.8ms | 3.2ms | 55.2% | SELECT:5 |
| GET /organization/tree | 1 | 4.5ms | 1.9ms | 42.2% | SELECT:1 |
| GET /merchandise | 1 | 6.2ms | 1.5ms | 24.2% | SELECT:1 |
| GET /profile | 8 WARN | 15.4ms | 6.8ms | 44.2% | SELECT:7, UPDATE:1 |
| GET /auth/me | 1 | 5.2ms | 1.9ms | 36.5% | SELECT:1 |
| GET /my-events | 3 | 7.7ms | 2.7ms | 35.1% | SELECT:3 |
| GET /notifications | 2 | 5.0ms | 2.1ms | 42.0% | SELECT:2 |
| GET /notifications/unread-count | 1 | 3.4ms | 1.7ms | 50.0% | SELECT:1 |
| GET /membership | 4 | 6.9ms | 2.9ms | 42.0% | SELECT:4 |
| GET /membership/plans | 1 | 3.7ms | 1.8ms | 48.6% | SELECT:1 |
| GET /participants | 3 | 14.0ms | 2.9ms | 20.7% | SELECT:3 |
| GET /participants/{id} | 3 | 7.0ms | 2.5ms | 35.7% | SELECT:3 |
| GET /payments/history | 2 | 5.0ms | 2.1ms | 42.0% | SELECT:2 |
| GET /attendance/sync-down | **34** CRIT | 45.6ms | 18.7ms | 41.0% | SELECT:34 |
| GET /attendance/report | 3 | 11.2ms | 3.2ms | 28.6% | SELECT:3 |
| GET /merchandise/orders | 4 | 9.6ms | 4.2ms | 43.8% | SELECT:4 |
| GET /attendance/{eventId} | 3 | 8.2ms | 3.1ms | 37.8% | SELECT:3 |

### Analisis

- **sync-down: 34 queries** — Masalah terbesar. 32 dari 34 queries adalah satu-by-one participant lookup. Pada 1000 attendance records, ini akan menjadi 1000+ queries.
- **organization: 11 queries** — 8 dari 11 adalah membership_plans lookup satu per satu per organization member.
- **profile: 8 queries** — Duplicate participant lookup (2x) dan membership_plan lookup (2x).
- **Best practices**: Idealnya setiap endpoint tidak lebih dari 3-5 queries (count + paginated data + relations).

---

## 6. N+1 Detection

### N+1 #1: `GET /api/v1/attendance/sync-down` — CRITICAL

**34 queries total, 32 adalah N+1**

```
Query #01: SELECT * FROM attendances WHERE updated_at >= ? ORDER BY updated_at ASC
Query #02: SELECT * FROM event_participants WHERE id IN (20, 22, 24, 26, 28, 30, 31, ...)
Query #03-34: SELECT * FROM participants WHERE id = ? LIMIT 1  ← N+1 x 32
```

**Root cause**: `syncUpOffline()` melakukan eager load untuk event_participants, tapi setiap attendance record lalu melakukan lazy load untuk participant satu per satu.

**Impact projection**:

| Attendance Records | Queries | Est. DB Time |
|--------------------|---------|--------------|
| 32 (current) | 34 | ~19ms |
| 100 | 102 | ~55ms |
| 500 | 502 | ~275ms |
| 1,000 | 1,002 | ~550ms |
| 10,000 | 10,002 | ~5,500ms |

### N+1 #2: `GET /api/v1/organization` — CRITICAL

**11 queries total, 8 adalah N+1**

```
Query #01: SELECT COUNT(*) FROM organization_members WHERE is_active = 1
Query #02: SELECT * FROM organization_members WHERE is_active = 1 ORDER BY sort_order LIMIT 15
Query #03: SELECT * FROM participants WHERE id IN (1, 2, 3, 4, 5, 6, 7, 8)
Query #04-11: SELECT * FROM membership_plans WHERE key = ? LIMIT 1  ← N+1 x 8
```

**Root cause**: OrganizationService/Controller melakukan eager load participants, tapi membership plan dilookup satu per satu per participant.

**Impact projection**:

| Organization Members | Queries | Est. DB Time |
|----------------------|---------|--------------|
| 8 (current) | 11 | ~7ms |
| 50 | 53 | ~25ms |
| 100 | 103 | ~50ms |
| 500 | 503 | ~250ms |

### N+1 #3: `GET /api/v1/profile` — MEDIUM

**8 queries, 4 adalah duplicate lookups**

```
Query #01: SELECT * FROM personal_access_tokens WHERE id = ?
Query #02: SELECT * FROM users WHERE id = ?
Query #03: UPDATE personal_access_tokens SET last_used_at = ?
Query #04: SELECT * FROM participants WHERE user_id = ? LIMIT 1  ← duplicate
Query #05: SELECT * FROM participants WHERE user_id = ? LIMIT 1  ← duplicate
Query #06: SELECT * FROM membership_histories WHERE participant_id IN (1)
Query #07: SELECT * FROM membership_plans WHERE key = ? LIMIT 1  ← duplicate
Query #08: SELECT * FROM membership_plans WHERE key = ? LIMIT 1  ← duplicate
```

**Root cause**: Participant dan membership_plan di-lookup dua kali — sekali di ProfileService dan sekali di presenter/transformation layer.

---

## 7. Index Audit

### Current State — Kolom Tanpa Index

| Tabel | Kolom | Query Pattern | Est. Rows |
|-------|-------|---------------|-----------|
| `events` | `status` | `WHERE status IN ('publish','upcoming')` | 6 |
| `events` | `start_date` | `ORDER BY start_date ASC` | 6 |
| `payments` | `status` | `WHERE status = 'pending'` | 28 |
| `merchandise` | `status` | `WHERE status = 'active'` | 8 |
| `galleries` | `type` | `WHERE type = 'image'` | 45 |
| `organization_members` | `is_active` | `WHERE is_active = 1` | 8 |
| `users` | `is_active` | `WHERE is_active = 1` | 29 |
| `participants` | `participant_code` | `WHERE participant_code = ?` | 20 |

### Composite Index Recommendations

| Priority | Index | Query It Accelerates |
|----------|-------|---------------------|
| P1 | `events(status, start_date)` | GET /events, GET /events/upcoming |
| P1 | `payments(status, created_at)` | Payment history, admin filters |
| P2 | `galleries(type, is_featured, sort_order)` | GET /galleries |
| P2 | `organization_members(is_active, sort_order)` | GET /organization |
| P2 | `merchandise(status, sort_order)` | GET /merchandise |
| P3 | `users(is_active, role)` | Admin user listing |
| P3 | `participants(participant_code)` | Registration duplicate check |

### Current Index Coverage (already exist)

| Tabel | Index | Coverage |
|-------|-------|----------|
| attendances | `attendances_event_participant_id_foreign` | Covers event_participant lookups |
| payments | `payments_paymentable_type_paymentable_id_index` | Covers polymorphic lookups |
| galleries | `galleries_gallery_album_id_foreign` | Covers album joins |
| events | `events_organizer_type_organizer_id_index` | Covers organizer polymorphic |
| All FK columns | `*_foreign` indexes | All foreign keys indexed |

---

## 8. Redis Cache Analysis

### Current Usage

```
Redis Keys: 6 (hanya Laravel internal cache, 0 application data)
```

### Data yang Bisa Di-Cache

| Data | TTL | Impact | Endpoint |
|------|-----|--------|----------|
| Organization tree | 5 min | Mengurangi repeated queries | GET /organization/tree |
| Event list | 1 min | Mengurangi 6 queries | GET /events |
| Categories | 5 min | Mengurangi 1 query | GET /categories |
| Membership plans | 30 min | Mengurangi N+1 queries | GET /organization, /membership |
| Sponsors | 5 min | Mengurangi 1 query | GET /sponsors |
| Unread count | 30s | Mengurangi 1 query | GET /notifications/unread-count |

**Kesimpulan**: Redis tersedia dan berjalan, tapi **0% digunakan untuk application caching**. Semua data diambil dari database setiap request.

---

## 9. OPcache & PHP-FPM

### OPcache Status

| Setting | Nilai | Status |
|---------|-------|--------|
| opcache.enable | On | OK |
| opcache.memory_consumption | 128MB | OK |
| opcache.interned_strings_buffer | 8MB | OK |
| opcache.max_accelerated_files | Default | OK |
| opcache.revalidate_freq | 0 (check every request) | OK |
| opcache.jit | tracing | Configured but... |
| **opcache.jit_buffer_size** | **0** | **JIT disabled** |

### JIT Impact

JIT tracing mode dikonfigurasi, tapi `jit_buffer_size = 0` artinya JIT **tidak aktif**. PHP menjalankan **full interpretation** setiap request. Pada 8.3, JIT tracing bisa memberikan 10-30% improvement untuk compute-heavy workloads.

**Rekomendasi**: Set `opcache.jit_buffer_size=64M` atau `128M` untuk mengaktifkan JIT.

### PHP-FPM

- Config: Docker defaults (zz-docker.conf)
- Process manager: `dynamic` (docker default)
- pm.max_children: docker default (~25)
- pm.start_servers: docker default (~5)
- pm.min_spare_servers: docker default (~1)
- pm.max_spare_servers: docker default (~3)

PHP-FPM config menggunakan Docker defaults. Untuk production, perlu tuning berdasarkan traffic pattern.

---

## 10. Memory Usage

### Per-Endpoint Memory Delta

| Endpoint | Before | After | Delta | Peak |
|----------|--------|-------|-------|------|
| GET /events | 22.5MB | 28.7MB | +6.1MB | 28.7MB |
| GET /profile | 28.7MB | 28.7MB | 0 | 28.7MB |
| GET /organization | 28.7MB | 28.7MB | 0 | 28.7MB |
| GET /participants | 28.7MB | 28.7MB | 0 | 28.7MB |
| GET /attendance/sync-down | 28.7MB | 28.7MB | 0 | 28.7MB |

**Assessment**: Memory usage **tidak ada masalah**. Laravel + dependencies menghabiskan ~23MB baseline, dan data response kecil. Peak 28.7MB masih jauh di bawah memory_limit 128MB.

---

## 11. Concurrency & Race Condition

### Concurrency Results (10 concurrent, 50 requests)

| Endpoint | Single Avg | Concurrent Avg | Degradation | Errors |
|----------|-----------|---------------|-------------|--------|
| POST /auth/login | 258.8ms | 2588.3ms | **10x** | 0/50 |
| GET /events | 26.7ms | 268.9ms | **10x** | 0/50 |
| GET /categories | 19.1ms | 194.7ms | **10x** | 0/50 |
| GET /sponsors | 13.1ms | 178.8ms | **14x** | 0/50 |
| GET /organization | 13.4ms | 339.9ms | **25x** | 0/50 |
| GET /profile | 19.1ms | 274.3ms | **14x** | 0/50 |
| GET /notifications | 16.8ms | 215.6ms | **13x** | 0/50 |
| GET /participants | 23.6ms | 314.3ms | **13x** | 0/50 |
| GET /attendance/sync-down | 47.0ms | 623.4ms | **13x** | 0/30 |

### Analisis Degradation

1. **Login 10x degradation**: Bcrypt hashing adalah CPU-bound operation. 10 concurrent login = 10x CPU-bound work. Normal, tapi perlu rate limiting.
2. **Organization 25x degradation**: N+1 queries diperparah oleh concurrent load. MySQL connections serialize untuk N+1.
3. **Semua endpoints 10-15x degradation**: Kontribusi dari PHP-FPM process limit, MySQL max connections, dan single-threaded PHP.
4. **0 errors**: Semua endpoints return 200 — tidak ada race condition yang terdeteksi.

### Race Condition Notes

- **Registration**: Email uniqueness constraint sudah ada di DB schema — OK.
- **Attendance sync-up**: Perlu verifikasi unique constraint pada event_participant_id + date.
- **Payment processing**: Perlu verifikasi double-charge protection.

---

## 12. Response Size & Payload

| Endpoint | Size | Assessment |
|----------|------|------------|
| POST /auth/login | 499B | Minimal |
| GET /events | 4.8KB | Normal |
| GET /events/upcoming | 2.0KB | Normal |
| GET /categories | 836B | Minimal |
| GET /galleries | **500 ERROR** | Broken |
| GET /sponsors | 4.2KB | Normal |
| GET /organization | 3.5KB | Normal |
| GET /organization/stats | 276B | Minimal |
| GET /organization/tree | 8.0KB | Large |
| GET /participants | 8.9KB | Large |
| GET /attendance/sync-down | 9.1KB | Large |
| GET /auth/me | 297B | Minimal |
| GET /notifications | 516B | Minimal |
| GET /membership/plans | 399B | Minimal |
| GET /payments/history | 388B | Minimal |

**Assessment**: Tidak ada masalah payload signifikan. Response size relatif kecil. `organization/tree` (8KB) dan `participants` (8.9KB) bisa dioptimasi dengan selective field loading.

---

## 13. Bugs Ditemukan

### BUG-1: Gallery endpoint 500 Error

**Endpoint**: `GET /api/v1/galleries`
**Status**: HTTP 500
**Error**: `Cannot access protected property App\Repositories\GalleryRepository::$model`
**File**: `app/Services/GalleryService.php:19`

```php
// Line 19 - ERROR
return $this->galleryRepository->model  // $model is protected
    ->with(['event.category', 'album'])
    ->where('type', 'image')
    ...
```

**Root Cause**: `GalleryService` mengakses `$this->galleryRepository->model` yang merupakan property `protected`. Repository pattern yang digunakan tidak meng-expose model property secara publik.

**Impact**: Gallery functionality **tidak berfungsi sama sekali**. Semua gallery requests return 500.

---

## 14. Prioritas Rekomendasi

### P1 — Immediate (Before Production)

| # | Rekomendasi | Est. Effort | Impact |
|---|-------------|-------------|--------|
| 1 | **Fix Gallery bug** — akses model via method, bukan property | 10 menit | Fix broken endpoint |
| 2 | **Fix sync-down N+1** — eager load participants di AttendanceService | 30 menit | 34 ke 3 queries |
| 3 | **Fix organization N+1** — eager load membership_plans | 30 menit | 11 ke 3 queries |
| 4 | **Fix profile N+1** — hapus duplicate lookups | 20 menit | 8 ke 5 queries |
| 5 | **Add composite index** `events(status, start_date)` | 5 menit | Events list 2x faster |

### P2 — Short-term (Before Production)

| # | Rekomendasi | Est. Effort | Impact |
|---|-------------|-------------|--------|
| 6 | Add index `payments(status, created_at)` | 5 menit | Payment queries faster |
| 7 | Add index `galleries(type, is_featured, sort_order)` | 5 menit | Gallery queries faster |
| 8 | Add index `organization_members(is_active, sort_order)` | 5 menit | Org queries faster |
| 9 | Enable OPcache JIT (`jit_buffer_size=64M`) | 5 menit | 10-30% PHP speedup |
| 10 | Implement Redis cache untuk organization tree | 30 menit | Eliminates repeated queries |
| 11 | Implement Redis cache untuk categories, sponsors | 20 menit | Eliminates repeated queries |
| 12 | Enable slow query log (`long_query_time=2`) | 5 menit | Monitoring capability |

### P3 — Medium-term

| # | Rekomendasi | Est. Effort | Impact |
|---|-------------|-------------|--------|
| 13 | Rate limiting pada login endpoint | 30 menit | Brute force protection |
| 14 | Selective field loading untuk large responses | 2-4 jam | Reduce payload 30-50% |
| 15 | Cache warming untuk frequently accessed data | 1 jam | First-request speedup |
| 16 | PHP-FPM tuning untuk production traffic | 2 jam | Better concurrency handling |
| 17 | Add composite index `participants(participant_code)` | 5 menit | Registration faster |

---

## 15. Analisis Endpoint Per-Endpoint

### POST /api/v1/auth/login

- **Queries**: 4 (auth + last_login update + token create + session)
- **DB Time**: 14.1ms / 313ms total — DB bukan bottleneck
- **Bottleneck**: `Hash::make()` (bcrypt) — CPU-bound
- **Optimization**: Rate limiting, token caching, consider argon2id (sama secure, lebih cepat)

### GET /api/v1/events

- **Queries**: 6 (count + data + relations per event)
- **Status**: Acceptable untuk dataset kecil
- **Optimization**: Eager loading untuk category relasi, Redis cache 1 min

### GET /api/v1/events/{id}

- **Queries**: 6 (event + relations)
- **Status**: Acceptable
- **Optimization**: Redis cache per event ID

### GET /api/v1/organization

- **Queries**: 11 — N+1
- **Fix**: Eager load membership_plan via `with('participant.membershipPlan')`
- **Result**: 11 ke 3 queries

### GET /api/v1/organization/stats

- **Queries**: 5
- **Status**: Reasonable
- **Optimization**: Redis cache 5 min (stats rarely change)

### GET /api/v1/profile

- **Queries**: 8 — Duplicates
- **Fix**: Hapus duplicate participant + membership_plan lookups
- **Result**: 8 ke 5 queries

### GET /api/v1/attendance/sync-down

- **Queries**: 34 — N+1 (terburuk)
- **Fix**: `->with('participant')` pada query awal
- **Result**: 34 ke 3 queries

### GET /api/v1/galleries

- **Status**: 500 ERROR
- **Fix**: Fix property access di GalleryService

### GET /api/v1/participants

- **Queries**: 3
- **Status**: Clean
- **Note**: Response 8.9KB — bisa dioptimasi dengan field selection

### GET /api/v1/notifications

- **Queries**: 2
- **Status**: Clean
- **Note**: Data 0 rows — perlu monitoring saat data bertambah

### GET /api/v1/membership

- **Queries**: 4
- **Status**: Acceptable, tapi ada potensi N+1 jika participant punya banyak memberships

### GET /api/v1/payments/history

- **Queries**: 2
- **Status**: Clean

---

## 16. Methodology Notes

### Benchmark Script
- 5 warmup requests (discard) + 50 measured iterations
- Single-threaded sequential execution via PHP curl
- Wall-clock timing via `microtime(true)`
- Response size via `strlen()`

### Query Analyzer
- Laravel Kernel bootstrap, `Request::create()`, `app()->handle()`
- `DB::enableQueryLog()` + `DB::getQueryLog()` setelah request
- Wall time + DB time captured per query
- N+1 detection via query pattern normalization

### Concurrency Test
- `curl_multi_init()` dengan batches
- 10 concurrent connections per batch
- 50 total requests per endpoint (5 batches of 10)
- Login test: 10 concurrent, 50 total (sequential unique credentials)

### Limitations
- Dataset sangat kecil — metrics tidak representatif untuk production load
- PHP-FPM config Docker defaults — production mungkin berbeda
- OPcache CLI detection tidak reliable — JIT status dari phpinfo() saja
- Tidak ada load testing tool (wrk/ab) — custom script
- Race condition testing terbatas — tidak ada scenario testing
