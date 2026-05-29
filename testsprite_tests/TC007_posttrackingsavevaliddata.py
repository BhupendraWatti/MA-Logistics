import requests
import re
from datetime import datetime
import io

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30
USERNAME = "admin"
PASSWORD = "admin"
COMPANY_ID = 1

def test_posttrackingsavevaliddata():
    session = requests.Session()
    try:
        # Step 1: GET '/' to get CSRF token and session cookie
        resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        resp.raise_for_status()
        csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_match, "CSRF token not found in login page"
        csrf_token = csrf_token_match.group(1)
        # print("CSRF token:", csrf_token)

        # Step 2: POST to '/auth/attemptLogin' with csrf_token_name, username=admin, password=admin
        login_data = {
            "csrf_token_name": csrf_token,
            "username": USERNAME,
            "password": PASSWORD
        }
        resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_data, timeout=TIMEOUT)
        resp.raise_for_status()
        # After login, session cookie 'ci_session' should be set and authenticated

        # Step 3: POST to '/logistics/setCompany' with csrf_token_name and company_id=1
        set_company_data = {
            "csrf_token_name": csrf_token,
            "company_id": COMPANY_ID
        }
        resp = session.post(f"{BASE_URL}/logistics/setCompany", data=set_company_data, timeout=TIMEOUT)
        resp.raise_for_status()

        # Step 4: POST to '/logistics/ajax-datatable' to get a booking_id and awb_no
        ajax_data = {
            "draw": "1",
            "start": "0",
            "length": "1"
        }
        resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data=ajax_data, timeout=TIMEOUT)
        resp.raise_for_status()
        json_data = resp.json()
        assert "data" in json_data and isinstance(json_data["data"], list) and len(json_data["data"]) > 0, "No booking data found"
        booking_record = json_data["data"][0]
        booking_id = str(booking_record["id"])
        awb_no = booking_record["awb_no"]

        # Prepare the payload for /tracking/save with valid data
        now = datetime.now()
        event_date = now.strftime("%Y-%m-%d")
        event_time = now.strftime("%H:%M:%S")
        status_text = "In Transit"
        current_location = "Warehouse A"

        # Step 5: GET /tracking/history/{booking_id} to attempt to find existing tracking (optional)
        # Not required but we won't delete an existing record in this test case as not specified.

        # Prepare a small dummy POD file for uploading (optional, included here)
        pod_file_content = b"dummy proof of delivery file content"
        pod_file = io.BytesIO(pod_file_content)
        pod_file.name = "pod_dummy.txt"

        tracking_save_data = {
            "csrf_token_name": csrf_token,
            "booking_id": booking_id,
            "awb_no": awb_no,
            "current_location": current_location,
            "status": status_text,
            "event_date": event_date,
            "event_time": event_time
        }
        files = {
            "proof_image": ("pod_dummy.txt", pod_file, "text/plain")
        }

        # Step 6: POST /tracking/save
        resp = session.post(f"{BASE_URL}/tracking/save", data=tracking_save_data, files=files, timeout=TIMEOUT)
        resp.raise_for_status()
        save_resp_json = resp.json()

        # Validate 200 response and success indication in JSON
        assert resp.status_code == 200, f"Expected status code 200 but got {resp.status_code}"
        assert isinstance(save_resp_json, dict), "Response JSON is not a dict"
        # According to doc, if save successful it returns 200 and presumably a success object
        # We check if 'status' or similar key exists and is success (not error)
        # If no schema keys specified, just verify no error key or status error
        if "status" in save_resp_json:
            assert save_resp_json["status"].lower() != "error", f"API returned error status: {save_resp_json['status']}"
        # No traceback or error key in response
        assert "error" not in save_resp_json, "Response contains error field"

    finally:
        # Clean up uploaded tracking record - we do not have ID returned here, so fetch history and delete
        try:
            # Fetch history
            session.headers.update({"Accept": "application/json"})
            resp = session.get(f"{BASE_URL}/tracking/history/{booking_id}", timeout=TIMEOUT)
            resp.raise_for_status()
            history_json = resp.json()
            assert isinstance(history_json, dict) and "data" in history_json, "Invalid history response"

            # Find max tracking record ID
            max_tracking_id = None
            for record in history_json.get("data", []):
                if "id" in record:
                    rid = record["id"]
                    if max_tracking_id is None or rid > max_tracking_id:
                        max_tracking_id = rid
            if max_tracking_id is not None:
                # Delete the tracking record
                delete_resp = session.post(f"{BASE_URL}/tracking/delete/{max_tracking_id}", data={"csrf_token_name": csrf_token}, timeout=TIMEOUT)
                delete_resp.raise_for_status()
                del_json = delete_resp.json()
                assert del_json.get("status", "").lower() != "error", "Error deleting the tracking record"
        except Exception:
            # If cleanup fails, do not raise to avoid masking original test errors
            pass

test_posttrackingsavevaliddata()