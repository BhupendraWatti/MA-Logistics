import requests
import re
from datetime import datetime

BASE_URL = "https://granthinfotech.online"
USERNAME = "admin"
PASSWORD = "admin"
TIMEOUT = 30

def test_posttrackingdeleteidharddeletetrackingrecord():
    session = requests.Session()

    # Step 1: GET '/' to get CSRF token
    resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    resp.raise_for_status()
    csrf_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
    assert csrf_search, "CSRF token not found in landing page"
    csrf_token = csrf_search.group(1)

    headers = {
        "Referer": f"{BASE_URL}/"
    }

    # Step 2: POST to '/auth/attemptLogin' with csrf_token_name, username, password
    login_data = {
        "csrf_token_name": csrf_token,
        "username": USERNAME,
        "password": PASSWORD
    }
    resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_data, headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()
    # After login, extract updated CSRF token from response (if provided)
    csrf_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
    if csrf_search:
        csrf_token = csrf_search.group(1)

    # Step 3: POST to '/logistics/setCompany' with csrf_token_name and company_id=1
    company_data = {
        "csrf_token_name": csrf_token,
        "company_id": "1"
    }
    resp = session.post(f"{BASE_URL}/logistics/setCompany", data=company_data, headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()

    # Step 4: POST to '/logistics/ajax-datatable' to get a valid booking id and awb_no
    ajax_data = {
        "draw": "1",
        "start": "0",
        "length": "1"
    }
    resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data=ajax_data, headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()
    json_data = resp.json()
    assert "data" in json_data and len(json_data["data"]) > 0, "No booking data received"
    booking_record = json_data["data"][0]
    booking_id = str(booking_record.get("id", ""))
    awb_no = booking_record.get("awb_no", "")
    assert booking_id and awb_no, "Booking id or awb_no missing"

    # Step 5: POST to '/tracking/save' to add a new tracking record
    now = datetime.now()
    event_date = now.strftime("%Y-%m-%d")
    event_time = now.strftime("%H:%M:%S")

    tracking_save_payload = {
        "csrf_token_name": csrf_token,
        "booking_id": booking_id,
        "awb_no": awb_no,
        "current_location": "Test Location",
        "status": "In Transit",
        "event_date": event_date,
        "event_time": event_time
    }
    resp = session.post(f"{BASE_URL}/tracking/save", data=tracking_save_payload, headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()
    save_json = resp.json()
    assert isinstance(save_json, dict), "Tracking save response is not a JSON object"
    # We cannot get tracking record id from save response

    # Step 6: GET tracking history to find the max integer id (new tracking record)
    resp = session.get(f"{BASE_URL}/tracking/history/{booking_id}", headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()
    history_json = resp.json()
    assert isinstance(history_json, list) or isinstance(history_json, dict), "Tracking history response invalid"

    records = []
    if isinstance(history_json, dict) and "data" in history_json:
        records = history_json["data"]
    elif isinstance(history_json, list):
        records = history_json
    else:
        # Try to detect if list in any key
        for v in history_json.values():
            if isinstance(v, list):
                records = v
                break
    assert records, "No tracking history records found"

    max_id = -1
    for rec in records:
        rec_id = rec.get("id")
        try:
            rec_id_int = int(rec_id)
            if rec_id_int > max_id:
                max_id = rec_id_int
        except Exception:
            continue
    assert max_id > 0, "No valid tracking record id found in history"

    tracking_id_to_delete = max_id

    # Step 7: POST to '/tracking/delete/{id}' with the tracking record id to hard delete
    try:
        resp = session.post(f"{BASE_URL}/tracking/delete/{tracking_id_to_delete}", data={"csrf_token_name": csrf_token}, headers=headers, timeout=TIMEOUT)
        resp.raise_for_status()
        delete_json = resp.json()
        assert resp.status_code == 200, f"Expected HTTP 200, got {resp.status_code}"
    finally:
        # Cleanup: If the deletion failed, no explicit cleanup here, as the record was supposed to be hard deleted.
        pass

    # Step 8: Verify that the record is deleted by fetching history again and ensuring the id is gone
    resp = session.get(f"{BASE_URL}/tracking/history/{booking_id}", headers=headers, timeout=TIMEOUT)
    resp.raise_for_status()
    after_delete_json = resp.json()

    post_delete_records = []
    if isinstance(after_delete_json, dict) and "data" in after_delete_json:
        post_delete_records = after_delete_json["data"]
    elif isinstance(after_delete_json, list):
        post_delete_records = after_delete_json
    else:
        for v in after_delete_json.values():
            if isinstance(v, list):
                post_delete_records = v
                break

    ids_after_delete = set()
    for rec in post_delete_records:
        try:
            ids_after_delete.add(int(rec.get("id")))
        except Exception:
            continue

    assert tracking_id_to_delete not in ids_after_delete, "Tracking record was not deleted"

test_posttrackingdeleteidharddeletetrackingrecord()