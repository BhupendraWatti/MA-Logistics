import requests
import re

def test_gettrackinghistorybookingid():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to get CSRF token
    resp = session.get(f"{base_url}/", timeout=timeout)
    assert resp.status_code == 200, "Failed to load login page for CSRF token"
    csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
    assert csrf_token_match, "CSRF token not found in login page"
    csrf_token_name = csrf_token_match.group(1)

    # Step 2: POST to '/auth/attemptLogin' to login
    login_payload = {
        'csrf_token_name': csrf_token_name,
        'username': 'admin',
        'password': 'admin'
    }
    login_headers = {'Referer': f"{base_url}/"}
    resp = session.post(f"{base_url}/auth/attemptLogin", data=login_payload, headers=login_headers, timeout=timeout)
    assert resp.status_code == 200, "Login POST failed"
    # Ensure login success, assume JSON response has success key or redirect
    # Checking cookies for 'ci_session'
    assert 'ci_session' in session.cookies.get_dict(), "Session cookie not found after login"

    # Step 3: POST to '/logistics/setCompany' with company_id=1
    # First get CSRF token again from response or reuse previous token
    resp2 = session.get(f"{base_url}/", timeout=timeout)
    assert resp2.status_code == 200, "Failed to refresh CSRF token for setCompany"
    csrf_token_match2 = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp2.text)
    assert csrf_token_match2, "CSRF token not found for setCompany"
    csrf_token_name_2 = csrf_token_match2.group(1)

    set_company_payload = {
        'csrf_token_name': csrf_token_name_2,
        'company_id': '1'
    }
    resp = session.post(f"{base_url}/logistics/setCompany", data=set_company_payload, headers=login_headers, timeout=timeout)
    assert resp.status_code == 200, "Setting company failed"

    # Step 4: POST /logistics/ajax-datatable to get booking_id and awb_no
    datatable_payload = {
        'draw': '1',
        'start': '0',
        'length': '1'
    }
    resp = session.post(f"{base_url}/logistics/ajax-datatable", data=datatable_payload, timeout=timeout)
    assert resp.status_code == 200, "Fetching booking data failed"
    json_resp = resp.json()
    assert 'data' in json_resp and isinstance(json_resp['data'], list) and len(json_resp['data']) > 0, "No booking data returned"
    first_booking = json_resp['data'][0]
    assert 'id' in first_booking and 'awb_no' in first_booking, "Booking data missing 'id' or 'awb_no'"
    booking_id = str(first_booking['id'])

    # Step 5: GET /tracking/history/{booking_id}
    resp = session.get(f"{base_url}/tracking/history/{booking_id}", timeout=timeout)
    assert resp.status_code == 200, f"Tracking history GET failed with status {resp.status_code}"
    history = resp.json()
    assert isinstance(history, list) or isinstance(history, dict), "Tracking history response is not JSON object or array"

    # Check chronological order by event_date and event_time if present, else just accept array
    if isinstance(history, list) and len(history) > 1:
        def to_datetime(entry):
            date = entry.get('event_date', '') if isinstance(entry, dict) else ''
            time = entry.get('event_time', '') if isinstance(entry, dict) else ''
            return date + ' ' + time
        dates = [to_datetime(h) for h in history]
        assert dates == sorted(dates), "Tracking history not in chronological order"

test_gettrackinghistorybookingid()