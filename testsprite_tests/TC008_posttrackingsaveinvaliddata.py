import requests
import re

def test_posttrackingsaveinvaliddata():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to fetch CSRF token and cookies
    resp = session.get(base_url + "/", timeout=timeout)
    resp.raise_for_status()
    html_text = resp.text
    csrf_token_name_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_name_search, "CSRF token not found in login page"
    csrf_token_name = csrf_token_name_search.group(1)

    # Step 2: POST to '/auth/attemptLogin' with credentials and CSRF token
    login_data = {
        'csrf_token_name': csrf_token_name,
        'username': 'admin',
        'password': 'admin'
    }
    resp = session.post(base_url + "/auth/attemptLogin", data=login_data, timeout=timeout)
    resp.raise_for_status()

    # Step 3: POST to '/logistics/setCompany' with CSRF token and company_id=1
    resp = session.get(base_url + "/logistics", timeout=timeout)
    resp.raise_for_status()
    html_text = resp.text
    csrf_token_name_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_name_search, "CSRF token not found on /logistics page"
    csrf_token_name = csrf_token_name_search.group(1)

    set_company_data = {
        'csrf_token_name': csrf_token_name,
        'company_id': '1'
    }
    resp = session.post(base_url + "/logistics/setCompany", data=set_company_data, timeout=timeout)
    resp.raise_for_status()

    # Step 4: POST '/logistics/ajax-datatable' to get a valid booking_id and awb_no
    ajax_data = {'draw': '1', 'start': '0', 'length': '1'}
    resp = session.post(base_url + "/logistics/ajax-datatable", data=ajax_data, timeout=timeout)
    resp.raise_for_status()
    data = resp.json()
    assert 'data' in data and len(data['data']) > 0, "No booking data found"
    first_booking = data['data'][0]
    booking_id = str(first_booking['id'])
    awb_no = str(first_booking['awb_no'])

    # Prepare invalid payloads with corrected field name 'location' instead of 'current_location'
    invalid_payloads = [
        # missing booking_id
        {
            'csrf_token_name': csrf_token_name,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # missing awb_no
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # missing location
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # missing status
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # missing event_date
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_time': '12:00'
        },
        # missing event_time
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '2026-05-29',
        },
        # invalid booking_id (non-numeric)
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': 'invalid_id',
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # invalid date format
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '29-05-2026',
            'event_time': '12:00'
        },
        # invalid time format
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '25:61'
        },
        # empty status
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': 'Test Location',
            'status': '',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
        # empty location
        {
            'csrf_token_name': csrf_token_name,
            'booking_id': booking_id,
            'awb_no': awb_no,
            'location': '',
            'status': 'In Transit',
            'event_date': '2026-05-29',
            'event_time': '12:00'
        },
    ]

    url_tracking_save = base_url + "/tracking/save"

    for payload in invalid_payloads:
        try:
            response = session.post(url_tracking_save, data=payload, timeout=timeout)
        except requests.RequestException as e:
            assert False, f"Request failed with exception: {e}"

        if response.status_code == 500:
            continue
        elif response.status_code == 200:
            try:
                resp_json = response.json()
            except Exception:
                assert False, "Response is not JSON when 200 received on invalid data"

            assert isinstance(resp_json, dict), "Response JSON not an object"
            status_value = resp_json.get('status')
            assert status_value == 'error', f"Expected status 'error' but got: {status_value}"
        else:
            assert False, f"Unexpected status code {response.status_code} for payload {payload}"

test_posttrackingsaveinvaliddata()
