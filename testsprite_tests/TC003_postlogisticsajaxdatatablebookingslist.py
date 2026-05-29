import requests
import re

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30

def test_postlogisticsajaxdatatablebookingslist():
    session = requests.Session()

    # Step 1: GET '/' to get CSRF token name and token value
    resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    assert resp.status_code == 200, "Failed to GET / for CSRF token"
    html_text = resp.text

    csrf_token_name_match = re.search(r'name="([a-zA-Z0-9_-]+)"\s+value="([^"]+)"', html_text)
    assert csrf_token_name_match, "CSRF token name and value not found"
    csrf_token_name = csrf_token_name_match.group(1)
    csrf_token_value = csrf_token_name_match.group(2)

    # Step 2: POST to '/auth/attemptLogin' with csrf token name as key and token value
    login_payload = {
        csrf_token_name: csrf_token_value,
        'username': 'admin',
        'password': 'admin'
    }
    login_headers = {
        'Referer': f"{BASE_URL}/"
    }
    login_resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_payload, headers=login_headers, timeout=TIMEOUT)
    assert login_resp.status_code == 200, "Login POST /auth/attemptLogin failed"
    assert 'ci_session' in session.cookies, "Login failed - ci_session cookie missing"

    # Step 3: POST to '/logistics/setCompany' with same CSRF token handling
    set_company_payload = {
        csrf_token_name: csrf_token_value,
        'company_id': '1'
    }
    set_company_resp = session.post(f"{BASE_URL}/logistics/setCompany", data=set_company_payload, timeout=TIMEOUT)
    assert set_company_resp.status_code == 200, "Failed to set company context"

    # Step 4: POST to '/logistics/ajax-datatable' with specified payload to get paginated bookings dataset
    datatable_payload = {
        'draw': '1',
        'start': '0',
        'length': '10'  # Usually length param defines page size; use 10 by default here
    }
    datatable_resp = session.post(f"{BASE_URL}/logistics/ajax-datatable", data=datatable_payload, timeout=TIMEOUT)
    assert datatable_resp.status_code == 200, "POST /logistics/ajax-datatable did not return 200"
    try:
        datatable_json = datatable_resp.json()
    except Exception:
        raise AssertionError("Response to /logistics/ajax-datatable is not valid JSON")

    # Validate presence of paging and data keys according to DataTables standard
    assert 'draw' in datatable_json, "'draw' field missing in response"
    assert 'recordsTotal' in datatable_json, "'recordsTotal' field missing in response"
    assert 'recordsFiltered' in datatable_json, "'recordsFiltered' field missing in response"
    assert 'data' in datatable_json, "'data' field missing in response"
    assert isinstance(datatable_json['data'], list), "'data' field is not a list"

    # Optional: Check that searching keys are present (e.g., draw matches request)
    assert str(datatable_json['draw']) == datatable_payload['draw'], "'draw' value does not match request"

    # If data is present, check first entry for expected keys (like id and awb_no presence is not required here but is a good sanity check)
    if len(datatable_json['data']) > 0:
        first_item = datatable_json['data'][0]
        assert isinstance(first_item, dict), "First item in data is not a dict"
        # The booking dataset should have at least an 'id' and 'awb_no' field or similar; no schema given so only basic check
        assert 'id' in first_item, "Booking entry missing 'id'"
        assert 'awb_no' in first_item, "Booking entry missing 'awb_no'"

test_postlogisticsajaxdatatablebookingslist()
