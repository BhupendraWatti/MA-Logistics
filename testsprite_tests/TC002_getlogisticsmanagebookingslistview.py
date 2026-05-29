import requests
import re

def test_getlogisticsmanagebookingslistview():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    timeout = 30

    # Step 1: GET '/' to get CSRF token
    r_home = session.get(f"{base_url}/", timeout=timeout)
    r_home.raise_for_status()
    csrf_token_name_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', r_home.text)
    assert csrf_token_name_match, "CSRF token not found on home page"
    csrf_token_name = csrf_token_name_match.group(1)

    headers = {
        "Referer": f"{base_url}/"
    }

    # Step 2: POST '/auth/attemptLogin' with csrf_token_name, username=admin, password=admin
    login_data = {
        "csrf_token_name": csrf_token_name,
        "username": "admin",
        "password": "admin"
    }
    r_login = session.post(f"{base_url}/auth/attemptLogin", data=login_data, headers=headers, timeout=timeout)
    r_login.raise_for_status()
    # Usually login returns JSON or redirects, but must check success - assume 200 is success
    # Optionally check session cookie set
    assert "ci_session" in session.cookies.get_dict(), "Session cookie ci_session not set after login"

    # Step 3: POST '/logistics/setCompany' with csrf_token_name and company_id=1
    set_company_data = {
        "csrf_token_name": csrf_token_name,
        "company_id": "1"
    }
    r_set_company = session.post(f"{base_url}/logistics/setCompany", data=set_company_data, headers=headers, timeout=timeout)
    r_set_company.raise_for_status()
    # Assuming success if status code is 200

    # Step 4: GET '/logistics/manage' with authenticated session and ci_session cookie
    r_manage = session.get(f"{base_url}/logistics/manage", headers=headers, timeout=timeout)
    r_manage.raise_for_status()

    # Validate response content type
    content_type = r_manage.headers.get("Content-Type", "")
    assert "text/html" in content_type, "Expected HTML content from /logistics/manage"

    # Basic check that page contains indication of bookings management view
    # For example look for <title> or any unique identifier text in HTML
    assert re.search(r"<title>.*bookings.*</title>", r_manage.text, re.I) or \
           re.search(r"Manage.*Bookings", r_manage.text, re.I) or \
           re.search(r"bookings\s+management", r_manage.text, re.I), \
           "Bookings management listing not found in page content"

test_getlogisticsmanagebookingslistview()