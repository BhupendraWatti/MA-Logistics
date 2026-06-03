# MAlogistic — Scale Review for ~50 Concurrent Users

**Date:** 2026-06-03  
**Stack:** CodeIgniter 4, PHP 8+, MySQL, sessions via `DatabaseHandler` (`ci_sessions` table)

---

## 1. Current application profile

| Area | Current state | Risk at 50 users |
|------|----------------|------------------|
| Sessions | `DatabaseHandler` → `ci_sessions` | Every request reads/writes MySQL; login storms create hot rows |
| Bookings grid | `POST /logistics/ajax-datatable` with subquery joins on `shipment_items` + `sales_charges` | CPU + connection time per grid refresh |
| Auth | CSRF + `POST /auth/attemptLogin` per session | 50 parallel logins caused **HTTP 500** on staging (observed 2026-06-03) |
| Indexes | `idx_bookings_awb`, `idx_bookings_branch_date`, `idx_tracking_lookup` | Missing `(company_id, id DESC)` for tenant list (added in migration `2026-06-03-000002`) |

---

## 2. PHP-FPM (recommended)

Target: **≥ 50 concurrent active requests** with headroom for spikes.

```ini
; /etc/php/8.x/fpm/pool.d/malogistic.conf (example)
pm = dynamic
pm.max_children = 80          ; ≥ peak concurrent requests
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 20
pm.max_requests = 500         ; recycle workers to limit memory leaks

request_terminate_timeout = 60s
```

**Sizing formula:**  
`pm.max_children ≈ (RAM_for_PHP) / (avg_MB_per_worker)`  
Example: 4 GB for PHP ÷ ~50 MB/worker → ~80 children (leave margin for MySQL/OS).

**Also enable:**

- `opcache.enable=1`, `opcache.memory_consumption=128` (or higher)
- `realpath_cache_size=4096K`
- `upload_max_filesize` / `post_max_size` aligned with POD uploads

---

## 3. MySQL (recommended)

```ini
max_connections = 200          ; must exceed (php-fpm children + admin + replicas)
innodb_buffer_pool_size = 1G   ; scale with RAM (50–70% of dedicated DB RAM on dedicated host)
innodb_log_file_size = 256M
wait_timeout = 300
```

**Connection math:**

```
max_connections ≥ (app_servers × pm.max_children) + 20% overhead
```

Single app server @ 80 workers → plan for **~100–120** MySQL connections minimum.

**Query notes for this codebase:**

- `ajaxDatatable()` runs **two** `countAllResults()` plus a joined `SELECT` — 3 round-trips per grid load. At scale, consider caching `recordsTotal` per company or denormalized totals.
- Migration `idx_bookings_company_id (company_id, id DESC)` aligns with `WHERE company_id` + `ORDER BY id DESC`.

---

## 4. Sessions: Database → Redis (recommended before 50 users)

`app/Config/Session.php` currently uses:

```php
public string $driver = \CodeIgniter\Session\Handlers\DatabaseHandler::class;
public string $savePath = 'ci_sessions';
```

**Why change:** 50 users × frequent AJAX = heavy `ci_sessions` read/write contention.

**Steps:**

1. Install Redis on app tier (or managed ElastiCache).
2. Set in `.env`:
   ```
   session.driver = CodeIgniter\Session\Handlers\RedisHandler
   ```
3. Configure `app/Config/Cache.php` → `redis` host/port/password.
4. Point `Session::$savePath` to Redis DSN per [CI4 RedisHandler docs](https://codeigniter.com/user_guide/libraries/sessions.html#redishandler).

**Expected gain:** Lower MySQL load, faster session lock release, better parallel login behavior.

---

## 5. Deployment checklist (50 operators)

- [ ] `pm.max_children` ≥ 80 on each app node  
- [ ] MySQL `max_connections` ≥ 120  
- [ ] Run migration `2026-06-03-000002_AddBookingsCompanyListIndex`  
- [ ] Move sessions to **Redis** (or Memcached)  
- [ ] Enable OPcache in production  
- [ ] Rate-limit `/auth/attemptLogin` at reverse proxy (e.g. 10 req/s per IP) to prevent brute-force and login storms  
- [ ] Put static assets behind CDN / long cache headers  
- [ ] Monitor: PHP slow log, MySQL slow query log (`ajax-datatable` > 1s), 5xx rate  

---

## 6. Horizontal scaling (optional)

| Tier | Pattern |
|------|---------|
| App | 2+ nodes behind load balancer, sticky sessions **or** shared Redis sessions |
| DB | Read replica for reporting/export; primary for writes |
| Files | POD uploads → S3/shared NFS so all nodes see `proof_image` paths |

---

## 7. Load-test commands (this repo)

```bash
# Seed 10k synthetic bookings (local or staging DB per .env)
php spark migrate
php spark loadtest:seed --count 10000 --company 1

# Performance script (set target URL)
$env:TEST_BASE_URL="http://localhost:8080"
python testsprite_tests/TC_perf_load_and_concurrency.py

# Cleanup
php spark loadtest:purge --company 1
```

---

## 8. Verdict

| Scenario | Ready? |
|----------|--------|
| 10–15 concurrent users, small DB | Likely OK on current staging |
| 50 concurrent users (login + grid) | **Not ready** without PHP-FPM tuning, MySQL connections, and Redis sessions |
| 100k+ booking rows | Requires `idx_bookings_company_id` + join optimization / caching |
