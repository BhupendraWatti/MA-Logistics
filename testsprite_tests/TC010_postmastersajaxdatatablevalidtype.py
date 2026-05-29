import requests
import re

def test_postmastersajaxdatatablevalidtype():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to get CSRF token
    resp = session.get(base_url + "/", timeout=timeout)
    assert resp.status_code == 200
    html_text = resp.text

    csrf_token_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_search, "CSRF token not found in login page"
    csrf_token_name = csrf_token_search.group(1)

    # Step 2: POST to /auth/attemptLogin with csrf_token_name, username=admin, password=admin
    login_data = {
        'csrf_token_name': csrf_token_name,
        'username': 'admin',
        'password': 'admin'
    }
    resp = session.post(base_url + "/auth/attemptLogin", data=login_data, timeout=timeout)
    assert resp.status_code == 200

    # After login, get new CSRF token from landing/Home page (likely /logistics or just '/')
    resp = session.get(base_url + "/", timeout=timeout)
    assert resp.status_code == 200
    html_text = resp.text
    csrf_token_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_search, "CSRF token not found after login"
    csrf_token_name = csrf_token_search.group(1)

    # Step 3: POST to /logistics/setCompany with csrf_token_name and company_id=1
    setcompany_data = {
        'csrf_token_name': csrf_token_name,
        'company_id': '1'
    }
    resp = session.post(base_url + "/logistics/setCompany", data=setcompany_data, timeout=timeout)
    assert resp.status_code == 200

    # After setting company, get new csrf_token_name from refreshed page
    resp = session.get(base_url + "/", timeout=timeout)
    assert resp.status_code == 200
    html_text = resp.text
    csrf_token_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html_text)
    assert csrf_token_search, "CSRF token not found after setting company"
    csrf_token_name = csrf_token_search.group(1)

    # Step 4: POST to /masters/ajax-datatable/customers with search/pagination parameters
    master_type = "customers"  # plural required
    datatable_url = f"{base_url}/masters/ajax-datatable/{master_type}"

    form_data = {
        'csrf_token_name': csrf_token_name,
        'draw': '1',
        'start': '0',
        'length': '10',
        'search[value]': '',
        'search[regex]': 'false'
    }

    headers = {
        # Don't set Content-Type as application/json; use default form encoding
        # Must pass ci_session cookie automatically via session
    }

    resp = session.post(datatable_url, data=form_data, timeout=timeout, headers=headers)
    assert resp.status_code == 200, f"Expected 200 OK, got {resp.status_code}"

    try:
        rjson = resp.json()
    except Exception:
        rjson = None
    assert rjson is not None, "Response is not valid JSON"

    # Validate required keys in response for a datatable
    # Expected keys usually: draw, recordsTotal, recordsFiltered, data
    assert 'draw' in rjson
    assert 'recordsTotal' in rjson
    assert 'recordsFiltered' in rjson
    assert 'data' in rjson
    assert isinstance(rjson['data'], list)

    # Optionally check pagination values
    assert int(rjson['draw']) == 1
    assert rjson['recordsTotal'] >= 0
    assert rjson['recordsFiltered'] >= 0

test_postmastersajaxdatatablevalidtype()