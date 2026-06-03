import requests


def test_postlogisticsajaxdatatablepaginatedbookingslist():
    base_url = "https://granthinfotech.online"
    timeout = 30

    session = requests.Session()

    # Step 1: Login without CSRF tokens since not reliably present
    login_payload = {
        "username": "admin",
        "password": "admin"
    }
    headers = {"Referer": f"{base_url}/"}
    try:
        r = session.post(f"{base_url}/auth/attemptLogin", data=login_payload, headers=headers, timeout=timeout)
        r.raise_for_status()
    except Exception as e:
        assert False, f"Failed to POST /auth/attemptLogin : {e}"

    # Login should set session cookie
    assert 'ci_session' in session.cookies.get_dict(), "ci_session cookie not found after login"

    # Step 2: POST to '/logistics/setCompany' with company_id=1, no CSRF token
    company_payload = {
        "company_id": "1"
    }
    try:
        r = session.post(f"{base_url}/logistics/setCompany", data=company_payload, headers={"Referer": f"{base_url}/"}, timeout=timeout)
        r.raise_for_status()
    except Exception as e:
        assert False, f"Failed to set company with /logistics/setCompany: {e}"

    # Step 3: POST to '/logistics/ajax-datatable' with pagination parameters
    ajax_datatable_payload = {
        "draw": "1",
        "start": "0",
        "length": "10"
    }
    try:
        r = session.post(f"{base_url}/logistics/ajax-datatable", data=ajax_datatable_payload, timeout=timeout)
    except Exception as e:
        assert False, f"Failed to POST /logistics/ajax-datatable : {e}"

    assert r.status_code == 200, f"Expected status code 200, got {r.status_code}"

    try:
        response_json = r.json()
    except Exception as e:
        assert False, f"Response is not valid JSON: {e}"

    # Validate response structure contains required keys for server-side DataTables
    required_keys = {"draw", "recordsTotal", "recordsFiltered", "data"}
    assert required_keys.issubset(response_json.keys()), f"Response JSON missing keys: {required_keys - set(response_json.keys())}"

    # Validate 'data' is a list (the dataset)
    assert isinstance(response_json["data"], list), "'data' field is not a list"

    # Optionally, check pagination fields are integers or string digits convertible to int
    try:
        assert int(response_json["draw"]) >= 1
        assert int(response_json["recordsTotal"]) >= 0
        assert int(response_json["recordsFiltered"]) >= 0
    except Exception:
        assert False, "Pagination counts are not valid integers or invalid values"


test_postlogisticsajaxdatatablepaginatedbookingslist()
