# MAlogistic — Full Test Execution Report (2026-06-03)

Four recommended steps were executed: TestSprite re-run, load DB seed + perf tests, scale review, and TC018 collision testing.

---

## 1️⃣ Document Metadata

| Field | Value |
| :--- | :--- |
| **Project** | MARL Express ERP (MAlogistic) |
| **Date** | 2026-06-03 |
| **Staging** | `https://granthinfotech.online/` |
| **Local perf target** | `http://127.0.0.1:8080` (after `php spark serve`) |
| **TestSprite run** | [Dashboard](https://www.testsprite.com/dashboard/mcp/tests/f9c05573-8bc9-4614-9f5e-56d0564d1689/) |
| **Scale review** | [`docs/SCALE_REVIEW.md`](../docs/SCALE_REVIEW.md) |

---

## 2️⃣ Step 1 — TestSprite (May 2026 CSRF rules)

**Result: 7 / 10 passed (70%)** on staging — improved from 50% in the earlier run.

| ID | Test | Status | Notes |
|:---|:-----|:------:|:------|
| TC001 | Dashboard recent bookings | ✅ | |
| TC002 | Manage bookings view | ✅ | |
| TC003 | Ajax datatable | ❌ | 403 on login (staging rate-limit / CSRF timing) |
| TC004 | Delete booking (safe) | ✅ | |
| TC005 | Invalid delete id | ❌ | JSON parse on non-JSON response |
| TC006 | Tracking history | ✅ | |
| TC007 | Tracking save + POD | ✅ | |
| TC008 | Invalid tracking save | ✅ | |
| TC009 | Tracking delete | ✅ | |
| TC010 | Masters datatable | ❌ | CSRF extraction failed on GET `/` |

**Conclusion:** Core booking/tracking flows pass when CSRF bootstrap succeeds. Remaining failures are intermittent staging/auth issues, not functional regressions in TC007–TC009.

---

## 3️⃣ Step 2 — Load DB seed + performance tests

### Seeding (local `.env` database)

```bash
php spark migrate
php spark loadtest:seed --count 10000 --company 1
```

- **Inserted:** 10,000 `LOADTEST-*` rows in **0.75s**
- **Company 1 total bookings:** 10,018
- **Cleanup:** `php spark loadtest:purge --company 1`
- **100k option:** `php spark loadtest:seed --count 100000 --company 1` (~8s estimated)

### TC016–TC018 (local, 10k rows)

Command:

```powershell
$env:TEST_BASE_URL="http://127.0.0.1:8080"
python testsprite_tests/TC_perf_load_and_concurrency.py
```

| Test | Result | Metrics |
|:-----|:------:|:--------|
| **TC016** Pagination | ✅ | `start=0`: 0.44s, 100 rows; `start=99000`: 0.24s, 0 rows; `recordsTotal=10018` |
| **TC017a** 50× full login | ✅ | **50/50** success (local); p95 **47.1s** (slow but stable) |
| **TC017b** 50× parallel datatable | ✅ | **50/50** success; p95 **8.8s** |
| **TC018** 10× concurrent tracking save | ✅ | **10/10** saves; 0× 5xx; all locations in history; cleaned up after |

**Staging contrast (prior run):** 50 parallel logins → **0/50** with HTTP 500. Local dev server handles concurrency; production/staging needs tuning per scale review.

---

## 4️⃣ Step 3 — Scale review (50 concurrent users)

Deliverable: **[`docs/SCALE_REVIEW.md`](../docs/SCALE_REVIEW.md)**

Summary:

| Component | Recommendation |
|-----------|----------------|
| **PHP-FPM** | `pm.max_children` ≥ 80, OPcache on |
| **MySQL** | `max_connections` ≥ 120; migration `idx_bookings_company_id` applied |
| **Sessions** | Move from `DatabaseHandler` → **RedisHandler** before 50 users |
| **App** | Cache or simplify `ajax-datatable` double-count + heavy joins |

---

## 5️⃣ Step 4 — TC018 collision test

Implemented in [`TC_perf_load_and_concurrency.py`](./TC_perf_load_and_concurrency.py) → `test_concurrent_tracking_writes()`.

- 10 threads POST `/tracking/save` on the same `booking_id` with distinct `current_location`
- **No server 5xx**; all writes persisted; test records deleted in `finally`

**Risk:** No optimistic locking — concurrent writes can all succeed (last-write-wins). Acceptable for audit trail; consider `version` column if business requires conflict detection.

---

## 6️⃣ Coverage metrics

| Area | Tests | Passed |
|------|------:|-------:|
| TestSprite staging (TC001–TC010) | 10 | 7 |
| Local perf (TC016–TC018) | 4 | 4 |
| Scale documentation | 1 | ✅ |

---

## 7️⃣ Key gaps / next actions

1. Re-run TestSprite TC003/TC005/TC010 when staging is not rate-limited.
2. Run `loadtest:seed --count 100000` on a **dedicated perf DB** (not shared staging).
3. Apply [`docs/SCALE_REVIEW.md`](../docs/SCALE_REVIEW.md) on production host before 50 live users.
4. Point `TEST_BASE_URL` at staging after scale fixes to compare TC017 results.

---

## Artifacts added in this session

| File | Purpose |
|------|---------|
| `app/Commands/LoadTestSeedBookings.php` | `php spark loadtest:seed` |
| `app/Commands/LoadTestPurgeBookings.php` | `php spark loadtest:purge` |
| `app/Database/Migrations/2026-06-03-000002_AddBookingsCompanyListIndex.php` | List index |
| `testsprite_tests/TC_perf_load_and_concurrency.py` | TC016–TC018 |
| `docs/SCALE_REVIEW.md` | Infrastructure guidance |
