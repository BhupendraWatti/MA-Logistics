import requests
import re
from datetime import datetime

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30


def test_posttrackingsaveinvalidfieldserrorresponse():
    session = requests.Session()
    try:
        # Step 1: Get CSRF token from GET '/'
        res = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        res.raise_for_status()
        csrf_token_name_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', res.text)
        assert csrf_token_name_match, "CSRF token not found in GET / response"
        csrf_token_name = csrf_token_name_match.group(1)

        # Step 2: Login with POST '/auth/attemptLogin'
        login_payload = {
            "csrf_token_name": csrf_token_name,
            "username": "admin",
            "password": "admin",
        }
        res = session.post(
            f"{BASE_URL}/auth/attemptLogin",
            data=login_payload,
            timeout=TIMEOUT,
            allow_redirects=False,
        )
        # Allow either 200, 302 or 303 redirect after login
        assert res.status_code in (200, 302, 303), f"Login failed with status {res.status_code}"

        # Update csrf_token_name after login (typically might change)
        # Fetch fresh CSRF token from the response or next page
        res2 = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        res2.raise_for_status()
        csrf_token_name_match2 = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', res2.text)
        assert csrf_token_name_match2, "CSRF token not found in GET / after login"
        csrf_token_name = csrf_token_name_match2.group(1)

        # Step 3: Set company via POST '/logistics/setCompany'
        set_company_payload = {
            "csrf_token_name": csrf_token_name,
            "company_id": "1",
        }
        res = session.post(
            f"{BASE_URL}/logistics/setCompany",
            data=set_company_payload,
            timeout=TIMEOUT,
        )
        assert res.status_code == 200, f"Set company failed with status {res.status_code}"

        # Step 4: Get a valid booking_id and awb_no from POST '/logistics/ajax-datatable'
        ajax_payload = {
            "draw": "1",
            "start": "0",
            "length": "1",
        }
        res = session.post(
            f"{BASE_URL}/logistics/ajax-datatable",
            data=ajax_payload,
            timeout=TIMEOUT,
        )
        res.raise_for_status()
        ajax_json = res.json()
        data_list = ajax_json.get("data", [])
        assert data_list and isinstance(data_list, list), "No booking data returned"
        booking = data_list[0]
        booking_id = str(booking.get("id"))
        awb_no = str(booking.get("awb_no"))
        assert booking_id and awb_no, "Invalid booking_id or awb_no"

        # Prepare list of invalid payloads for mandatory tracking save fields
        # Required fields: 'csrf_token_name', 'booking_id', 'awb_no', 'location', 'status', 'event_date', 'event_time'.
        # We'll test missing fields or invalid empty values.
        current_date = datetime.now().strftime("%Y-%m-%d")
        current_time = datetime.now().strftime("%H:%M:%S")
        base_payload = {
            "csrf_token_name": csrf_token_name,
            "booking_id": booking_id,
            "awb_no": awb_no,
            "location": "Test Location",
            "status": "In Transit",
            "event_date": current_date,
            "event_time": current_time,
        }

        invalid_payloads = [
            # Missing 'booking_id'
            {k: v for k, v in base_payload.items() if k != "booking_id"},
            # Missing 'awb_no'
            {k: v for k, v in base_payload.items() if k != "awb_no"},
            # Missing 'location'
            {k: v for k, v in base_payload.items() if k != "location"},
            # Missing 'status'
            {k: v for k, v in base_payload.items() if k != "status"},
            # Missing 'event_date'
            {k: v for k, v in base_payload.items() if k != "event_date"},
            # Missing 'event_time'
            {k: v for k, v in base_payload.items() if k != "event_time"},
            # Empty string in 'booking_id'
            {**base_payload, "booking_id": ""},
            # Empty string in 'awb_no'
            {**base_payload, "awb_no": ""},
            # Empty string in 'location'
            {**base_payload, "location": ""},
            # Empty string in 'status'
            {**base_payload, "status": ""},
            # Invalid date format in 'event_date'
            {**base_payload, "event_date": "invalid-date"},
            # Invalid time format in 'event_time'
            {**base_payload, "event_time": "invalid-time"},
        ]

        for idx, payload in enumerate(invalid_payloads):
            res = session.post(
                f"{BASE_URL}/tracking/save",
                data=payload,
                timeout=TIMEOUT,
                allow_redirects=False,
            )
            # Accept either status 500 or status 200 with json status 'error'
            if res.status_code == 500:
                # Server rejected invalid data as expected
                continue
            if res.status_code == 200:
                try:
                    json_resp = res.json()
                except Exception:
                    assert False, f"Response not JSON on iteration {idx}"
                status = json_resp.get("status", "").lower()
                assert status == "error", f"Expected status 'error' on iteration {idx}, got {status}"
            else:
                assert False, f"Unexpected status code {res.status_code} on iteration {idx}"

    finally:
        session.close()


test_posttrackingsaveinvalidfieldserrorresponse()
