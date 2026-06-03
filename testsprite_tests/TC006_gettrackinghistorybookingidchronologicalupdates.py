import requests
import re

def test_gettrackinghistorybookingidchronologicalupdates():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to obtain CSRF token
    resp = session.get(f"{base_url}/", timeout=timeout)
    assert resp.status_code == 200, "Failed to load login page"
    html = resp.text

    m = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html)
    assert m, "CSRF token not found on login page"
    csrf_token_name = m.group(1)

    # Step 2: POST to '/auth/attemptLogin' with credentials and CSRF token
    login_data = {
        'csrf_token_name': csrf_token_name,
        'username': 'admin',
        'password': 'admin',
    }
    login_headers = {'Content-Type': 'application/x-www-form-urlencoded'}
    resp = session.post(f"{base_url}/auth/attemptLogin", data=login_data, headers=login_headers, timeout=timeout)
    assert resp.status_code == 200, f"Login failed with status {resp.status_code}"

    # Step 3: POST to '/logistics/setCompany' with company_id=1 and CSRF token
    # Need to get fresh CSRF token from the page or from cookies/session? 
    # Usually CSRF token is regenerated/required fresh for POSTs, so GET '/' again to get new token.
    resp = session.get(f"{base_url}/", timeout=timeout)
    assert resp.status_code == 200, "Failed to reload page for CSRF token after login"
    html = resp.text
    m = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html)
    assert m, "CSRF token not found after login"
    csrf_token_name = m.group(1)

    setcompany_data = {
        'csrf_token_name': csrf_token_name,
        'company_id': '1',
    }
    resp = session.post(f"{base_url}/logistics/setCompany", data=setcompany_data, timeout=timeout)
    assert resp.status_code == 200, f"Failed to set company context, status {resp.status_code}"

    # Step 4: POST to '/logistics/ajax-datatable' with {'draw':'1','start':'0','length':'1'} to get booking id and awb_no
    datatable_payload = {'draw': '1', 'start': '0', 'length': '1'}
    resp = session.post(f"{base_url}/logistics/ajax-datatable", data=datatable_payload, timeout=timeout)
    assert resp.status_code == 200, f"Failed to get bookings ajax datatable, status {resp.status_code}"
    json_resp = resp.json()
    assert 'data' in json_resp and isinstance(json_resp['data'], list) and len(json_resp['data']) > 0, "No booking data returned"
    booking = json_resp['data'][0]
    assert 'id' in booking and 'awb_no' in booking, "Booking data missing 'id' or 'awb_no'"
    booking_id = str(booking['id'])

    # Step 5: GET '/tracking/history/{booking_id}' and verify 200 and that response has chronological tracking history
    resp = session.get(f"{base_url}/tracking/history/{booking_id}", timeout=timeout)
    assert resp.status_code == 200, f"Tracking history request failed with status {resp.status_code}"
    json_hist = resp.json()
    # Validate that returned history is chronological by checking timestamp ordering if fields exist
    # We expect a list or object containing an ordered list
    assert isinstance(json_hist, (dict, list)), "Tracking history response not object or list"

    # If dict and contains list of entries, check ordering by event_date and event_time fields
    records = None
    if isinstance(json_hist, dict):
        # We try to find a key that holds the list of tracking records; assume 'data' or 'history'
        if 'data' in json_hist and isinstance(json_hist['data'], list):
            records = json_hist['data']
        elif 'history' in json_hist and isinstance(json_hist['history'], list):
            records = json_hist['history']
        else:
            records = list(json_hist.values()) if all(isinstance(v, list) for v in json_hist.values()) else None
            if records:
                records = records[0] if len(records) > 0 else None
    elif isinstance(json_hist, list):
        records = json_hist

    if records:
        # Sort records by event_date and event_time ascending and check if they are already sorted
        def parse_date_time(r):
            # We will parse event_date and event_time strings, expecting something like 'YYYY-MM-DD' and 'HH:MM:SS'
            d = r.get('event_date', '')
            t = r.get('event_time', '')
            return d + ' ' + t
        sorted_records = sorted(records, key=parse_date_time)
        assert records == sorted_records, "Tracking history is not in chronological order"

    # else if no records list, we just confirm 200 and JSON response

test_gettrackinghistorybookingidchronologicalupdates()