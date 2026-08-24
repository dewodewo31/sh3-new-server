# server-sh3.cloud — Security + Performance Assessment (PPT-Ready)

> Date: 24 August 2026 · Mode: READ-ONLY / AUDIT / NON-DESTRUCTIVE
> All secrets redacted (REDACTED). No production changes were made.

---

## SLIDE 1 — Title

- **Server:** `server-sh3.cloud` (Production VPS)
- **Report:** Final Comprehensive Security + Performance Assessment
- **Date:** 24 August 2026
- **Scope:** Stress-test history, anti-DDoS audit, safe security tests, optimization roadmap

---

## SLIDE 2 — Executive Summary

- VPS: **1 vCPU (AMD EPYC 9354P), 3.8 GB RAM, 0 swap, 48 GB SSD**
- Stack: Nginx 1.24 + PHP-FPM 8.3 (Laravel 13) + MySQL 8.0 (Docker) + Redis 7.4 (Docker) + 4 Queue workers + Reverb + Next.js
- **Original weakness:** `pm.max_children=5`, no Laravel cache, no Nginx DDoS limits → RPS ceiling ~17
- **Hardening applied:** `pm.max_children=10` + caches + Nginx rate/conn limits + micro-cache + UFW + Fail2ban + localhost-only DB/Redis/Reverb
- **Current RPS ceiling:** ~22–25 req/s (1 vCPU bound) — VERIFIED
- **Safe for 500+ users?** NO — verified abort at 250 VU
- **Overall score:** 7.0 / 10 (Security strong ~8; Performance capacity 5)

---

## SLIDE 3 — Server Specification

| Component | Value |
|---|---|
| CPU | 1 vCPU (AMD EPYC 9354P) |
| RAM | 3.8 GB total, 2.3 GB used, 1.5 GB avail |
| Swap | 0 B (none) |
| Disk | 48 GB SSD, 24% used |
| OS | Ubuntu, uptime 54 days |
| Web | Nginx 1.24 + PHP-FPM 8.3 |
| App | Laravel 13 |
| DB | MySQL 8.0 (Docker, localhost) |
| Cache/Queue | Redis 7.4 (Docker, localhost) |
| Realtime | Reverb (supervisor, 127.0.0.1:8080) |
| Frontend | Next.js (proxy :3000, localhost) |

---

## SLIDE 4 — BEFORE Architecture (Original Weaknesses)

- PHP-FPM `pm.max_children = 5` → max 5 concurrent PHP requests
- **No** Laravel `config/route/view` cache → full bootstrap per request
- **No** Nginx rate/connection limits (no anti-DDoS edge)
- `server_tokens` on; no micro-cache
- RPS ceiling **~17–19**, p95 > 8s at 100 VU
- Unsafe for > ~22 concurrent users (verified abort at 250 VU)
- Reverb, MySQL, Redis: localhost (already private)

---

## SLIDE 5 — AFTER Architecture (Current Hardened)

- PHP-FPM `pm.max_children = 10` + `max_requests=500` + `request_terminate_timeout=30s` + slowlog
- Laravel `config/route/view` cache enabled
- Nginx: `limit_req` API 10r/s burst 40, Auth 2r/s burst 10
- Nginx: `limit_conn` 20 (Laravel) / 30 (Frontend) / 50 (Reverb)
- Micro-cache 30s for public GET (bypass when authenticated) — VERIFIED MISS→HIT
- UFW: allow 22/80/443, deny 8080, 3000 localhost-only
- Fail2ban: 4 jails (sshd, nginx-http-auth, nginx-botsearch, recidive)
- MySQL/Redis/Reverb bound to 127.0.0.1 only

---

## SLIDE 6 — Stress Test Timeline (VERIFIED)

| Date | Env | Conc | RPS | P95 | Result |
|---|---|---:|---:|---:|---|
| 2026-08-23 | Local Docker | 1–3 | 46.9 | 54ms | PASS |
| 2026-08-23 | Local Docker | 100 | 47.5 | 2212ms | PASS |
| 2026-08-23 | Local Docker | ≤200 | 47.5 | 4269ms | graceful |
| 2026-08-23 | Prod (max_children=5) | 1–5 | 19.2 | 343ms | PASS |
| 2026-08-23 | Prod (max_children=5) | 100 | 17.7 | 8130ms | p95>2s |
| 2026-08-23 | Prod (max_children=5) | 250 | 15.7 | 15094ms | **ABORT** |
| 2026-08-23 | Prod (max_children=10) | 100 | 24.7 | 5574ms | improved |

Raw logs: `loadtest/results/*.json`, `loadtest/REPORT.md`, `loadtest/REPORT.prod.md`.

---

## SLIDE 7 — Performance Before vs After

| Test | RPS before | p95 before | RPS after | p95 after |
|---|---:|---:|---:|---:|
| baseline (5 VU) | 19.2 | 343ms | 25.1 | 365ms |
| load10 | 15.4 | 1026ms | 23.5 | 776ms |
| load100 | 17.7 | 8130ms | 24.7 | 5574ms |

- Concurrency PHP-FPM: 5 → 10 (2×)
- RPS ceiling: +40–53%
- p95: −24–31% at same VU
- **Remaining ceiling = 1 vCPU** (~22–25 RPS regardless of tuning)

---

## SLIDE 8 — Anti-DDoS Protection Layers

| Layer | Config |
|---|---|
| API rate limit | `limit_req 10r/s burst=40 nodelay` |
| Auth rate limit | `limit_req 2r/s burst=10 nodelay` |
| Laravel conn | `limit_conn 20` per IP |
| Frontend conn | `limit_conn 30` per IP |
| Reverb conn | `limit_conn 50` per IP |
| Micro-cache | 30s public GET, bypass auth |
| Timeouts | client 12s, send 15s, keepalive 20s |
| PHP-FPM kill | `request_terminate_timeout=30s` |
| Firewall | UFW deny-all, allow 22/80/443 |
| Fail2ban | 4 jails, bantime 3600s (recidive 86400s) |

---

## SLIDE 9 — Security Findings

**PASS (VERIFIED):**
- HTTPS redirect, TLS 1.2/1.3 only (1.1 rejected), AES256-GCM
- `.env` / `.git` / logs / backups → 404
- OPTIONS/TRACE/PUT/DELETE/PATCH → 405
- Login rate-limit → 429 at 6th request
- MySQL/Redis/Reverb localhost-only; UFW correct

**GAPS:**
- ❌ Missing HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- ⚠️ `Access-Control-Allow-Origin: *`
- ⚠️ `participant/auth/verify-reset` has no app-layer throttle
- ⚠️ `docker-compose.prod.yml` (git-tracked) has hardcoded weak DB password
- ⚠️ Redis `maxmemory=0` / `noeviction`
- ⚠️ TLS cert expires 2026-09-28; Next.js binds `*:3000`

---

## SLIDE 10 — Authentication / API Protection

| Endpoint group | Throttle | Verified |
|---|---|---|
| `/auth/login` | 5/min per (user\|IP) | ✅ 429 confirmed |
| `/auth/register`, `/forgot-password`, `/reset-password` | 5/min per IP | ✅ config |
| `/participant/auth/reset-password` | 5 / 15 min per IP | ✅ config |
| `/participant/auth/verify-reset` | **none (app)** | ⚠️ nginx 2r/s only |
| Most GET endpoints | none (app); nginx 10r/s | ⚠️ no per-user quota |

- Sanctum auth + role gates (admin/bendahara/organizer) present
- Session cookie: `secure; httponly; samesite=lax` ✅

---

## SLIDE 11 — Reverb / WebSocket Security

- Binding: `127.0.0.1:8080` only; UFW `DENY 8080` → not externally reachable ✅
- Proxy: Nginx `location /app` → `127.0.0.1:8080` with WSS upgrade headers ✅
- Handshake `wss://server-sh3.cloud/app/{key}` → **HTTP 101** (verified) ✅
- Private channel `App.Models.User.{id}` enforces `$user->id === $id` ✅
- Connection limit `limit_conn 50` per IP ✅
- **WebSocket load test NOT performed** → capacity NOT VERIFIED

---

## SLIDE 12 — Firewall / Network Security

| Port | Public? | Note |
|---|---|---|
| 22 (SSH) | ✅ allowed | UFW |
| 80 / 443 | ✅ allowed | HTTP→HTTPS / WSS |
| 3000 (Next.js) | ⚠️ firewalled localhost | should bind 127.0.0.1 |
| 3306 (MySQL) | ❌ private | Docker 127.0.0.1 |
| 6379 (Redis) | ❌ private | Docker 127.0.0.1 |
| 8080 (Reverb) | ❌ denied | UFW + localhost |

No unexpected public port. ✅

---

## SLIDE 13 — Database / Redis Security

**MySQL (VERIFIED):**
- localhost-only, healthy, 2 threads, **0 slow queries**, QPS 0.43
- `max_connections=151` (default), 1–3/151 used
- Risk: weak hardcoded root password committed to git (`docker-compose.prod.yml`)

**Redis (VERIFIED):**
- localhost-only, v7.4.11, 10 clients, idle
- `maxclients=10000`
- Risk: `maxmemory=0` (unlimited) + `maxmemory-policy=noeviction` → RAM exhaustion risk on no-swap box

---

## SLIDE 14 — Safe Security Test Results (2026-08-24)

| Test | Result |
|---|---|
| HTTP → HTTPS | 301 ✅ |
| TLS 1.1 | rejected ✅ |
| TLS 1.2/1.3 | AES256-GCM ✅ |
| `/.env`, `/.git/HEAD`, `/storage/logs/laravel.log` | 404 ✅ |
| OPTIONS/TRACE/PUT/DELETE/PATCH `/` | 405 ✅ |
| Login ×6 (invalid) | 422×5 → 429 ✅ |
| WSS handshake (valid key) | 101 ✅ |
| Micro-cache repeat | MISS → HIT ✅ |
| Minimal throughput (50 req / 10 conc, cached) | 200 all, p95 923ms |

---

## SLIDE 15 — Remaining Risks (Ranked)

| Rank | Risk | Evidence |
|---|---|---|
| P0 | 1 vCPU ceiling (~22–25 RPS) | RPS flat across all loads |
| P1 | `pm.max_children=10` too low for spikes | max 10 concurrent PHP |
| P2 | Missing HTTP security headers; CORS `*` | curl headers |
| P2 | No app-layer throttle on most GET; `verify-reset` unthrottled | routes/api.php |
| P3 | Redis `maxmemory=0/noeviction`; cert 2026-09-28; Next.js `*:3000`; DB password in git | configs |

**NOT bottleneck:** MySQL (2/151, 0 slow), Redis (idle), RAM (1.5 GB avail).

---

## SLIDE 16 — Optimization Recommendations

| Tier | Action | Expected Impact |
|---|---|---|
| FREE | Add HSTS/CSP/XFO/Referrer-Policy; restrict CORS; bind Next.js 127.0.0.1; app-throttle all GET; throttle `verify-reset`; rotate git DB password | +security, no cost |
| FREE | Redis `maxmemory` cap + `allkeys-lru` | resilience |
| LOW COST | `pm.max_children` → 20–30; Cloudflare CDN/WAF (free) | +concurrency, offload |
| MEDIUM | vCPU 1→2; Redis-cache expensive endpoints; DB index review | +2–3× RPS |
| MEDIUM | Laravel Octane (Swoole) | removes bootstrap cost |
| HIGH | VPS upgrade + monitoring (Prometheus) + centralized logs | capacity + observability |

---

## SLIDE 17 — Security Score

| Domain | Score |
|---|---:|
| Infrastructure Security | 8/10 |
| Network Security | 8/10 |
| Web Server Security | 7/10 |
| Laravel Security | 7/10 |
| Database Security | 8/10 |
| Redis Security | 8/10 |
| Realtime Security | 8/10 |
| DDoS Resilience | 7/10 |
| Performance Capacity | 5/10 |
| **OVERALL** | **7.0/10** |

Note: Edge security hardened & verified; the single hard limit is 1 vCPU.

---

## SLIDE 18 — Final Conclusion

- **Edge security is strong and VERIFIED:** firewall, TLS, rate/connection limits, Fail2ban, localhost services.
- **The hard capacity limit is 1 vCPU** → throughput capped at ~22–25 RPS and ~22 stable concurrent users.
- **The "50–70% DDoS impact reduction" claim is ESTIMATED, not empirically proven** — defenses are real but unmeasured.
- **Recommended next step:** upgrade to 2 vCPU + Laravel Octane + Cloudflare CDN/WAF + add missing HTTP security headers. This moves the server from "safe for small events" to "safe for 500+ concurrent users."
- No production changes were made during this audit.
