import requests
import re

BASE_URL = "https://granthinfotech.online"
USERNAME = "admin"
PASSWORD = "admin"
COMPANY_ID = "1"
TIMEOUT = 30

def get_csrf_token_and_session():
    session = requests.Session()
    resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    resp.raise_for_status()
    html = resp.text
    match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html)
    if not match:
        raise RuntimeError("CSRF token not found in main page")
    csrf_token = match.group(1)
    # session cookie automatically stored by requests.Session()
    return session, csrf_token

def login(session, csrf_token):
    login_data = {
        "csrf_token_name": csrf_token,
        "username": USERNAME,
        "password": PASSWORD
    }
    resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_data, timeout=TIMEOUT)
    resp.raise_for_status()
    # May check for successful login by presence of ci_session cookie or redirect?
    if "ci_session" not in session.cookies:
        raise RuntimeError("Login failed, ci_session cookie missing")

def set_company(session, csrf_token):
    data = {
        "csrf_token_name": csrf_token,
        "company_id": COMPANY_ID
    }
    resp = session.post(f"{BASE_URL}/logistics/setCompany", data=data, timeout=TIMEOUT)
    resp.raise_for_status()
    # No specific response validation given for setCompany

def get_booking_id_and_awb(session, csrf_token):
    payload = {
        "draw": "1",
        "start": "0",
        "length": "1",
        "csrf_token_name": csrf_token
    }
    resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data=payload, timeout=TIMEOUT)
    resp.raise_for_status()
    data = resp.json()
    if "data" not in data or not isinstance(data["data"], list) or len(data["data"]) == 0:
        raise RuntimeError("No booking data returned from ajax-datatable")
    booking_item = data["data"][0]
    booking_id = booking_item.get("id")
    awb_no = booking_item.get("awb_no")
    if booking_id is None or awb_no is None:
        raise RuntimeError("Booking id or awb_no missing in booking data")
    return booking_id, awb_no

def test_postlogisticsdeleteidatomicbookingdeletion():
    session, csrf_token = get_csrf_token_and_session()
    try:
        login(session, csrf_token)
        set_company(session, csrf_token)

        # Get booking id and awb_no for the test
        booking_id, awb_no = get_booking_id_and_awb(session, csrf_token)
        assert isinstance(booking_id, int) or (isinstance(booking_id, str) and booking_id.isdigit()), "Booking ID is not valid numeric"

        delete_url = f"{BASE_URL}/logistics/delete/{booking_id}"
        delete_payload = {
            "csrf_token_name": csrf_token
        }

        # POST to delete booking atomically
        resp = session.post(delete_url, data=delete_payload, timeout=TIMEOUT)
        try:
            resp.raise_for_status()
        except requests.HTTPError as e:
            # If production, prefer skip destructive delete
            # But here since we're on staging given instructions, allow test to fail in that case
            raise AssertionError(f"Delete booking failed with HTTP error: {e}")

        json_resp = None
        try:
            json_resp = resp.json()
        except Exception:
            raise AssertionError("Response is not a valid JSON")

        # Based on PRD and instructions: 200 response with object indicating success
        # We assert 200 was returned and response is JSON object
        assert isinstance(json_resp, dict), "Delete response JSON is not an object"

        # No specific success key defined so assume presence of 'success' key or just 200 means success atomic delete
        # According to PRD: atomic delete of booking and shipment items means no partial/failed delete
        # Since no more details, basic assertion:
        assert resp.status_code == 200, "Delete booking status code is not 200"

    except Exception as ex:
        raise ex

test_postlogisticsdeleteidatomicbookingdeletion()