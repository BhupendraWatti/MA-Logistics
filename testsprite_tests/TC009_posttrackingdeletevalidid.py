import requests
import re
from datetime import datetime

BASE_URL = "https://granthinfotech.online"

def test_posttrackingdeletevalidid():
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to get CSRF token
    resp_root = session.get(BASE_URL + "/", timeout=timeout)
    resp_root.raise_for_status()
    html_text = resp_root.text
    csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_match, "CSRF token not found on GET /"
    csrf_token = csrf_token_match.group(1)

    # Step 2: POST '/auth/attemptLogin' with username=admin and password=admin
    login_data = {
        "csrf_token_name": csrf_token,
        "username": "admin",
        "password": "admin"
    }
    resp_login = session.post(BASE_URL + "/auth/attemptLogin", data=login_data, timeout=timeout)
    resp_login.raise_for_status()
    # Expect some indication of success; typical CI returns JSON with success
    try:
        login_json = resp_login.json()
        assert login_json.get('success') or login_json.get('status') == 'success' or resp_login.status_code == 200
    except Exception:
        # If not JSON, fallback to basic status code check
        assert resp_login.status_code == 200

    # Step 3: POST '/logistics/setCompany' with company_id=1 and csrf_token_name
    # Need refreshed CSRF token since login page may have changed it or keep same from root?
    # Try to extract from login response or reuse root token? We'll reuse root token since no info says changed.
    setcompany_data = {
        "csrf_token_name": csrf_token,
        "company_id": "1"
    }
    resp_setcompany = session.post(BASE_URL + "/logistics/setCompany", data=setcompany_data, timeout=timeout)
    resp_setcompany.raise_for_status()
    # No explicit response schema, assume 200 means success
    assert resp_setcompany.status_code == 200

    # Step 4: POST '/logistics/ajax-datatable' to get booking_id and awb_no
    datatable_data = {
        "draw": "1",
        "start": "0",
        "length": "1"
    }
    resp_dt = session.post(BASE_URL + "/logistics/ajax-datatable", data=datatable_data, timeout=timeout)
    resp_dt.raise_for_status()
    dt_json = resp_dt.json()
    data_list = dt_json.get("data")
    assert data_list and isinstance(data_list, list) and len(data_list) > 0, "No bookings found from ajax-datatable"
    first_item = data_list[0]
    booking_id = str(first_item.get("id"))
    awb_no = first_item.get("awb_no")
    assert booking_id and awb_no, "Booking ID or AWB number missing"

    # Step 5: POST '/tracking/save' to add a new tracking record
    # Need a datetime for event_date and event_time
    now = datetime.now()
    event_date = now.strftime("%Y-%m-%d")
    event_time = now.strftime("%H:%M:%S")

    # Post requires exact keys: 'csrf_token_name', 'booking_id', 'awb_no', 'current_location', 'status', 'event_date', 'event_time'
    tracking_save_data = {
        "csrf_token_name": csrf_token,
        "booking_id": booking_id,
        "awb_no": awb_no,
        "current_location": "Test Location",
        "status": "In Transit",
        "event_date": event_date,
        "event_time": event_time
    }
    resp_save = session.post(BASE_URL + "/tracking/save", data=tracking_save_data, timeout=timeout)
    resp_save.raise_for_status()
    save_json = resp_save.json()
    # Expect success - no explicit ID returned, but 200 implies success
    assert resp_save.status_code == 200 and ("status" not in save_json or save_json.get("status") != "error")

    # Step 6: GET /tracking/history/{booking_id} to find the max tracking record id
    resp_history = session.get(BASE_URL + f"/tracking/history/{booking_id}", timeout=timeout)
    resp_history.raise_for_status()
    history_json = resp_history.json()
    # history_json expected to be a list or dict containing list of records with 'id'
    records = None
    if isinstance(history_json, dict):
        # Try to get list under a key; fallback if not found
        if 'data' in history_json and isinstance(history_json['data'], list):
            records = history_json['data']
        elif 'records' in history_json and isinstance(history_json['records'], list):
            records = history_json['records']
        else:
            # If history returns list in root keys
            records = [v for v in history_json.values() if isinstance(v, list)]
            if records:
                records = records[0]
            else:
                records = []
    elif isinstance(history_json, list):
        records = history_json
    else:
        records = []

    # Find max int id in records with integer id
    max_id = None
    for item in records:
        try:
            item_id = int(item.get("id", -1))
            if max_id is None or item_id > max_id:
                max_id = item_id
        except Exception:
            continue
    assert max_id is not None, "No tracking record ID found in history"

    # Step 7: POST /tracking/delete/{max_id} to delete newly created tracking record
    resp_delete = session.post(BASE_URL + f"/tracking/delete/{max_id}", timeout=timeout)
    resp_delete.raise_for_status()
    delete_json = resp_delete.json()
    # Expect 200 status code and no error in response
    assert resp_delete.status_code == 200
    assert "status" not in delete_json or delete_json.get("status") != "error"

    # Step 8: Confirm deletion - GET history again and verify max_id not present
    resp_history_after = session.get(BASE_URL + f"/tracking/history/{booking_id}", timeout=timeout)
    resp_history_after.raise_for_status()
    history_after_json = resp_history_after.json()
    records_after = None
    if isinstance(history_after_json, dict):
        if 'data' in history_after_json and isinstance(history_after_json['data'], list):
            records_after = history_after_json['data']
        elif 'records' in history_after_json and isinstance(history_after_json['records'], list):
            records_after = history_after_json['records']
        else:
            records_after = [v for v in history_after_json.values() if isinstance(v, list)]
            if records_after:
                records_after = records_after[0]
            else:
                records_after = []
    elif isinstance(history_after_json, list):
        records_after = history_after_json
    else:
        records_after = []

    ids_after = [int(r.get("id", -1)) for r in records_after if r.get("id") is not None]
    assert max_id not in ids_after, "Deleted tracking record ID still found in history"

test_posttrackingdeletevalidid()