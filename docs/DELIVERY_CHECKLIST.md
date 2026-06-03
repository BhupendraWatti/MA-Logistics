# Go-Live Checklist — Product Delivery

Use this **tonight** on the server that hosts `granthinfotech.online` (or production).

---

## A. Deploy these code changes (already in repo)

| Change | Why |
|--------|-----|
| `Security.php` → `csrf_token_name` | Matches forms/tests; fewer 403 login failures |
| `Session.php` → **FileHandler** | Cuts MySQL session lock contention under many users |
| `Filters.php` → removed **pagecache** + **toolbar** | Page cache broke dynamic ERP; toolbar slows production |
| `Logistics::ajaxDatatable` | Faster counts + one batch query per page (not N+1) |
| `Logistics::delete` | Always returns JSON for invalid/missing IDs |
| Migration `2026-06-03-000002` | Index `(company_id, id DESC)` for booking list |

---

## B. On the server (30 minutes)

### 1. Environment

```bash
# In project root .env
CI_ENVIRONMENT = production
```

### 2. Writable folders

```bash
chmod -R 775 writable/
# Must exist and be writable:
# writable/session
# writable/cache
# writable/logs
# writable/uploads
```

### 3. Database

```bash
php spark migrate
```

### 4. PHP / web server (copy to host or php.ini)

```ini
opcache.enable=1
opcache.memory_consumption=128
realpath_cache_size=4096K
```

**PHP-FPM** (if used):

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 8
pm.min_spare_servers = 5
pm.max_spare_servers = 15
```

**MySQL:**

```ini
max_connections = 150
```

Restart PHP-FPM + MySQL after changes.

### 5. Remove load-test data from production DB

```bash
php spark loadtest:purge --company 1
```

Only if you ran `loadtest:seed` on this database.

### 6. Smoke test (manual, 10 min)

1. Login → select company → dashboard shows **Recent Bookings**
2. **Manage bookings** — grid loads in &lt; 3 seconds
3. Open tracking on one AWB → save status → delete test row
4. **Masters → Customers** — list loads
5. Try delete on fake id (browser devtools) — should get JSON error, not HTML crash

---

## C. What failed in tests vs what you fixed

| Test issue | Root cause | Fix |
|------------|------------|-----|
| TC003 403 login | Staging overload + CSRF name mismatch | File sessions + `csrf_token_name` + no page cache |
| TC005 delete JSON error | Invalid id returned HTML or empty body | `delete()` returns 400/404 JSON |
| TC010 CSRF not found | Wrong token field name in HTML | Unified `csrf_token_name` |
| 50 users → HTTP 500 | DB sessions + weak PHP-FPM | File sessions + FPM `max_children` |
| Slow grid with many rows | Double COUNT + join + N+1 queries | Optimized `ajaxDatatable` |

---

## D. Realistic expectations for tomorrow

| Scenario | Ready? |
|----------|--------|
| **5–20 users**, normal booking volume | **Yes**, after deploy + smoke test |
| **50 users** all clicking at once | **Risky** until FPM/MySQL tuned; not required for day-1 demo |
| **100k+ bookings** | **OK** with new index; grid stays fast if purge seed data |

---

## E. If something breaks during demo

1. **Grid empty / “No company selected”** → user must pick company after login  
2. **403 on save** → refresh page (new CSRF), retry once  
3. **500 errors** → check `writable/logs/log-*.log`, restart PHP-FPM  
4. **Slow list** → confirm migration ran; run `php spark loadtest:purge` if test data left behind  

---

## F. After delivery (next week)

- Redis sessions (`RedisHandler`) if user count grows  
- Full TestSprite re-run on staging when server is idle  
- Optional: rate limit `/auth/attemptLogin` at firewall  

See also: [`docs/SCALE_REVIEW.md`](SCALE_REVIEW.md)
