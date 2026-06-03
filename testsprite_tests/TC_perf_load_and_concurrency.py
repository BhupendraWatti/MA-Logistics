"""
Load, concurrency, and collision tests.

  set TEST_BASE_URL=http://localhost:8080   # after: php spark serve
  python testsprite_tests/TC_perf_load_and_concurrency.py
"""
import os
import re
import time
import statistics
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime

import requests

BASE_URL = os.environ.get("TEST_BASE_URL", "https://granthinfotech.online").rstrip("/")
USERNAME = os.environ.get("TEST_USERNAME", "admin")
PASSWORD = os.environ.get("TEST_PASSWORD", "admin")
TIMEOUT = int(os.environ.get("TEST_TIMEOUT", "30"))


def _extract_csrf(html: str):
    m = re.search(r'name="(csrf_[^"]+)"\s+value="([^"]+)"', html)
    if m:
        return m.group(1), m.group(2)
    return None, None


def bootstrap_session():
    session = requests.Session()
    csrf_field, csrf_val, referer = None, None, None
    for path in ("/login", "/"):
        r = session.get(f"{BASE_URL}{path}", timeout=TIMEOUT)
        if r.status_code == 200:
            csrf_field, csrf_val = _extract_csrf(r.text)
            if csrf_val:
                referer = f"{BASE_URL}{path}"
                break
    if not csrf_val:
        raise RuntimeError("CSRF token not found on /login or /")

    login = session.post(
        f"{BASE_URL}/auth/attemptLogin",
        data={csrf_field: csrf_val, "username": USERNAME, "password": PASSWORD},
        headers={"Referer": referer},
        timeout=TIMEOUT,
        allow_redirects=True,
    )
    if login.status_code == 403:
        raise RuntimeError("Login forbidden (CSRF); check credentials and Security config")
    if "ci_session" not in session.cookies.get_dict():
        raise RuntimeError(f"Login failed HTTP {login.status_code}")

    r2 = session.get(f"{BASE_URL}/login", timeout=TIMEOUT)
    f2, v2 = _extract_csrf(r2.text)
    if v2:
        csrf_field, csrf_val = f2, v2

    session.post(
        f"{BASE_URL}/logistics/setCompany",
        data={csrf_field: csrf_val, "company_id": "1"},
        timeout=TIMEOUT,
    ).raise_for_status()
    return session, csrf_val, csrf_field


def get_booking(session):
    r = session.post(
        f"{BASE_URL}/logistics/ajax-datatable",
        data={"draw": "1", "start": "0", "length": "1"},
        timeout=TIMEOUT,
    )
    r.raise_for_status()
    j = r.json()
    row = j["data"][0]
    return row["id"], row["awb_no"]


def test_large_offset_pagination():
    session, _, _ = bootstrap_session()
    results = []
    offsets = [(0, "page_0"), (99000, "offset_99k")]
    total = None
    for start, label in offsets:
        t0 = time.perf_counter()
        r = session.post(
            f"{BASE_URL}/logistics/ajax-datatable",
            data={"draw": "1", "start": str(start), "length": "100"},
            timeout=max(TIMEOUT, 60),
        )
        elapsed = time.perf_counter() - t0
        ok = r.status_code == 200
        meta = {}
        if ok:
            j = r.json()
            total = j.get("recordsTotal")
            meta = {
                "recordsTotal": total,
                "recordsFiltered": j.get("recordsFiltered"),
                "rows": len(j.get("data", [])),
            }
            ok = elapsed < 15
        results.append(
            {
                "label": label,
                "ok": ok,
                "status": r.status_code,
                "elapsed_s": round(elapsed, 3),
                **meta,
            }
        )
    return results, total


def _worker_datatable(worker_id: int):
    t0 = time.perf_counter()
    try:
        s, _, _ = bootstrap_session()
        r = s.post(
            f"{BASE_URL}/logistics/ajax-datatable",
            data={"draw": "1", "start": "0", "length": "25"},
            timeout=TIMEOUT,
        )
        elapsed = time.perf_counter() - t0
        ok = r.status_code == 200 and "data" in r.json()
        return {"worker": worker_id, "ok": ok, "status": r.status_code, "elapsed_s": elapsed}
    except Exception as e:
        return {
            "worker": worker_id,
            "ok": False,
            "status": 0,
            "elapsed_s": time.perf_counter() - t0,
            "error": str(e),
        }


def _datatable_only(session, worker_id: int):
    t0 = time.perf_counter()
    try:
        r = session.post(
            f"{BASE_URL}/logistics/ajax-datatable",
            data={"draw": "1", "start": "0", "length": "25"},
            timeout=TIMEOUT,
        )
        elapsed = time.perf_counter() - t0
        ok = r.status_code == 200 and "data" in r.json()
        return {"worker": worker_id, "ok": ok, "status": r.status_code, "elapsed_s": elapsed}
    except Exception as e:
        return {
            "worker": worker_id,
            "ok": False,
            "status": 0,
            "elapsed_s": time.perf_counter() - t0,
            "error": str(e),
        }


def test_concurrent_reads(workers=50, mode="datatable_only"):
    latencies = []
    if mode == "datatable_only":
        pool_size = min(workers, 10)
        sessions = [bootstrap_session()[0] for _ in range(pool_size)]
        with ThreadPoolExecutor(max_workers=workers) as ex:
            futures = [
                ex.submit(_datatable_only, sessions[i % pool_size], i)
                for i in range(workers)
            ]
            rows = [f.result() for f in as_completed(futures)]
    else:
        with ThreadPoolExecutor(max_workers=workers) as ex:
            futures = [ex.submit(_worker_datatable, i) for i in range(workers)]
            rows = [f.result() for f in as_completed(futures)]
    successes = sum(1 for row in rows if row.get("ok"))
    for row in rows:
        if row.get("ok"):
            latencies.append(row["elapsed_s"])
    p95 = statistics.quantiles(latencies, n=20)[18] if len(latencies) >= 2 else (latencies[0] if latencies else 0)
    return {
        "mode": mode,
        "workers": workers,
        "successes": successes,
        "failures": workers - successes,
        "pass": successes >= 45 if mode == "datatable_only" else successes >= 40,
        "p95_s": round(p95, 3),
        "max_s": round(max(latencies), 3) if latencies else None,
        "sample_errors": [r.get("error") or r.get("status") for r in rows if not r.get("ok")][:5],
    }


def test_concurrent_tracking_writes(threads=10):
    """TC018: parallel POST /tracking/save on one booking."""
    session, csrf_val, csrf_field = bootstrap_session()
    booking_id, awb_no = get_booking(session)

    now = datetime.now()
    event_date = now.strftime("%Y-%m-%d")
    event_time = now.strftime("%H:%M:%S")

    def save_one(idx):
        loc = f"CollisionTest-W{idx}"
        try:
            r = session.post(
                f"{BASE_URL}/tracking/save",
                data={
                    csrf_field: csrf_val,
                    "booking_id": str(booking_id),
                    "awb_no": awb_no,
                    "current_location": loc,
                    "status": "In Transit",
                    "event_date": event_date,
                    "event_time": event_time,
                },
                timeout=TIMEOUT,
            )
            body = {}
            try:
                body = r.json()
            except Exception:
                pass
            return {
                "idx": idx,
                "ok": r.status_code == 200 and body.get("status") != "error",
                "status": r.status_code,
                "location": loc,
                "body": body,
            }
        except Exception as e:
            return {"idx": idx, "ok": False, "error": str(e)}

    created_ids = []
    try:
        with ThreadPoolExecutor(max_workers=threads) as ex:
            results = list(ex.map(save_one, range(threads)))

        successes = sum(1 for r in results if r.get("ok"))
        server_errors = sum(1 for r in results if r.get("status", 0) >= 500)

        hist = session.get(f"{BASE_URL}/tracking/history/{booking_id}", timeout=TIMEOUT)
        hist.raise_for_status()
        data = hist.json()
        rows = data if isinstance(data, list) else data.get("data", [])
        locations = {r.get("current_location") for r in rows if isinstance(r, dict)}
        collision_locs = [f"CollisionTest-W{i}" for i in range(threads)]
        found = sum(1 for loc in collision_locs if loc in locations)

        for row in rows:
            if isinstance(row, dict) and str(row.get("current_location", "")).startswith("CollisionTest-W"):
                created_ids.append(int(row["id"]))

        return {
            "threads": threads,
            "booking_id": booking_id,
            "save_successes": successes,
            "server_5xx": server_errors,
            "locations_found_in_history": found,
            "pass": server_errors == 0 and successes >= 1,
            "results": results,
        }
    finally:
        for tid in created_ids:
            try:
                session.post(
                    f"{BASE_URL}/tracking/delete/{tid}",
                    data={csrf_field: csrf_val},
                    timeout=TIMEOUT,
                )
            except Exception:
                pass


def main():
    print(f"Target: {BASE_URL}\n")

    print("=== TC016 Large-data pagination stress ===")
    rows, total = test_large_offset_pagination()
    for row in rows:
        print(row)
    print(f"DB recordsTotal (company 1): {total}")

    print("\n=== TC017a Concurrent read — 50 parallel full logins ===")
    print(test_concurrent_reads(50, mode="full_bootstrap"))

    print("\n=== TC017b Concurrent read — 50 parallel datatable (10 sessions) ===")
    print(test_concurrent_reads(50, mode="datatable_only"))

    print("\n=== TC018 Concurrent tracking write collision ===")
    print(test_concurrent_tracking_writes(10))


if __name__ == "__main__":
    main()
