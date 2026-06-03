import requests
import re
import io
from datetime import datetime

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30
USERNAME = "admin"
PASSWORD = "admin"
COMPANY_ID = "1"


def test_posttrackingsavecreatesupdatesrecordwithpod():
    session = requests.Session()
    try:
        # Step 1: GET '/' to retrieve CSRF token
        resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        resp.raise_for_status()
        csrf_token_name_search = re.search(
            r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_name_search, "CSRF token not found on GET /"
        csrf_token_name = csrf_token_name_search.group(1)

        # Step 2: POST to '/auth/attemptLogin' to login
        login_data = {
            "csrf_token_name": csrf_token_name,
            "username": USERNAME,
            "password": PASSWORD,
        }
        login_headers = {
            "Referer": f"{BASE_URL}/",
            "Content-Type": "application/x-www-form-urlencoded"
        }
        resp = session.post(f"{BASE_URL}/auth/attemptLogin",
                            data=login_data, headers=login_headers, timeout=TIMEOUT)
        resp.raise_for_status()
        # After login, update CSRF token from response text
        csrf_token_name_search = re.search(
            r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        if csrf_token_name_search:
            csrf_token_name = csrf_token_name_search.group(1)

        # Step 3: POST to '/logistics/setCompany' with company_id=1
        set_company_data = {
            "csrf_token_name": csrf_token_name,
            "company_id": COMPANY_ID
        }
        resp = session.post(f"{BASE_URL}/logistics/setCompany",
                            data=set_company_data, timeout=TIMEOUT)
        resp.raise_for_status()

        # Step 4: POST to '/logistics/ajax-datatable' to get booking_id and awb_no
        datatable_payload = {'draw': '1', 'start': '0', 'length': '1'}
        resp = session.post(f"{BASE_URL}/logistics/ajax-datatable",
                            data=datatable_payload, timeout=TIMEOUT)
        resp.raise_for_status()
        json_data = resp.json()
        assert "data" in json_data and isinstance(json_data["data"], list) and len(
            json_data["data"]) > 0, "No booking data found"
        booking_data = json_data["data"][0]
        booking_id = str(booking_data.get("id", ""))
        awb_no = str(booking_data.get("awb_no", ""))
        assert booking_id, "booking_id missing"
        assert awb_no, "awb_no missing"

        # Step 5: Prepare tracking save payload
        now = datetime.now()
        event_date = now.strftime("%Y-%m-%d")
        event_time = now.strftime("%H:%M:%S")
        status = "In Transit"
        current_location = "Warehouse"

        # Need fresh CSRF token again from GET / for form submission
        resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        resp.raise_for_status()
        csrf_token_name_search = re.search(
            r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_name_search, "CSRF token not found on refetch"
        csrf_token_name = csrf_token_name_search.group(1)

        tracking_payload = {
            "csrf_token_name": csrf_token_name,
            "booking_id": booking_id,
            "awb_no": awb_no,
            "current_location": current_location,
            "status": status,
            "event_date": event_date,
            "event_time": event_time,
        }

        # Step 6: Create an in-memory dummy POD file to upload
        pod_content = b"Dummy POD image content"
        pod_file = io.BytesIO(pod_content)
        pod_file.name = 'pod.jpg'

        files = {"proof_image": ("pod.jpg", pod_file, "image/jpeg")}

        # Step 7: POST to '/tracking/save' with payload and POD file
        resp = session.post(
            f"{BASE_URL}/tracking/save",
            data=tracking_payload,
            files=files,
            timeout=TIMEOUT
        )
        try:
            resp.raise_for_status()
        except requests.HTTPError as e:
            # If raising HTTPError, try to parse json for error message
            try:
                error_json = resp.json()
                assert error_json.get("status") != "error", f"Tracking save failed: {error_json}"
            except Exception:
                raise e

        # Step 8: Validate response JSON indicates success
        json_resp = resp.json()
        assert isinstance(json_resp, dict), "Response is not a JSON object"
        assert "status" in json_resp, "Missing status in response"
        assert json_resp["status"] == "success", f"Unexpected status: {json_resp['status']}"

    finally:
        # Cleanup tracking record if possible is skipped because response does not return record ID
        # No delete operation instructed in TC007; thus, just close session
        session.close()


test_posttrackingsavecreatesupdatesrecordwithpod()