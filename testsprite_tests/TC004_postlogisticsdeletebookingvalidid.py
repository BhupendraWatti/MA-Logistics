import requests
import re

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30

def test_postlogisticsdeletebookingvalidid():
    session = requests.Session()

    # Step 1: GET '/' to get CSRF token
    resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    resp.raise_for_status()
    csrf_token = None
    csrf_token_name = "csrf_token_name"
    # Extract csrf_token_name and token value from HTML using re
    csrf_name_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
    if csrf_name_match:
        csrf_token = csrf_name_match.group(1)
    assert csrf_token_name and csrf_token, "CSRF token or name not found on login page"

    # Step 2: POST to '/auth/attemptLogin' with csrf_token_name, username=admin, password=admin
    login_payload = {
        csrf_token_name: csrf_token,
        "username": "admin",
        "password": "admin",
    }
    resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_payload, timeout=TIMEOUT)
    resp.raise_for_status()
    assert 'ci_session' in session.cookies, "Login failed"

    # Update CSRF from response if available for next requests
    # Sometimes CSRF changes after login, attempt to get it:
    home_resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    home_resp.raise_for_status()
    csrf_match = re.search(r'name="([^"]+csrf[^"]*)" value="([^"]+)"', home_resp.text)
    if csrf_match:
        csrf_token_name = csrf_match.group(1)
        csrf_token = csrf_match.group(2)

    # Step 3: POST to '/logistics/setCompany' with csrf_token_name and company_id=1
    set_company_payload = {
        csrf_token_name: csrf_token,
        "company_id": "1",
    }
    resp = session.post(f"{BASE_URL}/logistics/setCompany", data=set_company_payload, timeout=TIMEOUT)
    resp.raise_for_status()
    # Confirm set company success
    try:
        json_resp = resp.json()
    except Exception:
        json_resp = None
    assert (json_resp and (json_resp.get("success") or json_resp.get("status") == "success")) or resp.status_code == 200, "SetCompany failed"

    # Step 4: POST to '/logistics/ajax-datatable' with {'draw': '1', 'start': '0', 'length': '1'} to get booking id
    datatable_payload = {
        "draw": "1",
        "start": "0",
        "length": "1",
    }
    resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data=datatable_payload, timeout=TIMEOUT)
    resp.raise_for_status()
    data = resp.json()
    assert "data" in data and isinstance(data["data"], list) and len(data["data"]) > 0, "No booking found to delete"
    booking = data["data"][0]
    booking_id = booking.get("id")
    assert booking_id, "Booking ID not found in datatable response"

    # Step 5: POST to '/logistics/delete/{id}' with the valid booking id
    # get fresh CSRF token from a page before delete in case it's needed
    resp = session.get(f"{BASE_URL}/logistics", timeout=TIMEOUT)
    resp.raise_for_status()
    csrf_match = re.search(r'name="([^"]+csrf[^"]*)" value="([^"]+)"', resp.text)
    if csrf_match:
        csrf_token_name = csrf_match.group(1)
        csrf_token = csrf_match.group(2)

    delete_url = f"{BASE_URL}/logistics/delete/{booking_id}"
    delete_payload = {csrf_token_name: csrf_token}
    delete_resp = session.post(delete_url, data=delete_payload, timeout=TIMEOUT)
    delete_resp.raise_for_status()

    # The response should return 200 status code and presumably json object indicating success
    try:
        delete_json = delete_resp.json()
    except Exception:
        delete_json = None
    assert delete_resp.status_code == 200, "Delete booking did not return 200 OK"
    # We expect successful deletion, check for success indicator in json
    assert delete_json and (delete_json.get("success") or delete_json.get("status") == "success" or "deleted" in (delete_json.get("message", "")).lower()), "Booking not deleted successfully"

    # Step 6: Verify that the booking was deleted by trying to fetch booking again
    resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data={"draw": "1", "start": "0", "length": "10"}, timeout=TIMEOUT)
    resp.raise_for_status()
    data_after = resp.json()
    # Confirm that deleted booking id no longer appears
    bookings_ids_after = [item.get("id") for item in data_after.get("data", []) if "id" in item]
    assert booking_id not in bookings_ids_after, "Deleted booking id still present after delete"

test_postlogisticsdeletebookingvalidid()
