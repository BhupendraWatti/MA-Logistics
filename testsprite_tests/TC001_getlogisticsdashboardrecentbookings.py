import requests
import re

def test_getlogisticsdashboardrecentbookings():
    base_url = "https://granthinfotech.online"
    timeout = 30

    session = requests.Session()

    # Step 1: GET '/' to get CSRF token
    try:
        resp = session.get(f"{base_url}/", timeout=timeout)
        resp.raise_for_status()
    except Exception as e:
        assert False, f"Failed to GET / : {e}"
    # Extract csrf_token_name using regex
    match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
    assert match, "CSRF token not found in GET / response"
    csrf_token_name = match.group(1)

    # Step 2: POST to '/auth/attemptLogin' with credentials and CSRF token
    login_payload = {
        'csrf_token_name': csrf_token_name,
        'username': 'admin',
        'password': 'admin'
    }
    headers = {
        'Referer': f"{base_url}/",
    }
    try:
        login_resp = session.post(f"{base_url}/auth/attemptLogin", data=login_payload, headers=headers, timeout=timeout)
        login_resp.raise_for_status()
    except Exception as e:
        assert False, f"Failed to POST /auth/attemptLogin: {e}"

    # Successful login should retain a ci_session cookie
    assert 'ci_session' in session.cookies.get_dict(), "ci_session cookie not found after login"

    # Step 3: POST to '/logistics/setCompany' with CSRF token and company_id=1
    set_company_payload = {
        'csrf_token_name': csrf_token_name,
        'company_id': '1'
    }
    try:
        setcompany_resp = session.post(f"{base_url}/logistics/setCompany", data=set_company_payload, headers=headers, timeout=timeout)
        setcompany_resp.raise_for_status()
    except Exception as e:
        assert False, f"Failed to POST /logistics/setCompany: {e}"

    # Step 4: GET /logistics to get the dashboard with recent bookings
    try:
        logistics_resp = session.get(f"{base_url}/logistics", timeout=timeout)
        logistics_resp.raise_for_status()
    except Exception as e:
        assert False, f"Failed to GET /logistics: {e}"

    # Validate that the response is HTML containing recent bookings dashboard
    # Since no JSON schema is given for this page, check HTTP 200, content-type and presence of expected keywords.
    content_type = logistics_resp.headers.get('Content-Type', '')
    assert 'text/html' in content_type.lower(), f"Expected HTML content-type but got {content_type}"
    content = logistics_resp.text.lower()
    # Validate presence of keywords that indicate recent bookings dashboard presence
    expected_keywords = ['recent bookings', 'dashboard', 'logistics']
    assert any(kw in content for kw in expected_keywords), "Dashboard page does not contain expected keywords indicating recent bookings"

test_getlogisticsdashboardrecentbookings()