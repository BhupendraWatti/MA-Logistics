import requests
import re

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30

def test_getlogisticsmanageallbookingslistview():
    session = requests.Session()
    try:
        # Step 1: GET '/' to retrieve CSRF token
        r = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        r.raise_for_status()
        csrf_token_name_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', r.text)
        assert csrf_token_name_search, "CSRF token name not found on home page"
        csrf_token_name = csrf_token_name_search.group(1)

        # Step 2: POST to '/auth/attemptLogin' with credentials and CSRF token
        login_data = {
            "csrf_token_name": csrf_token_name,
            "username": "admin",
            "password": "admin"
        }
        r = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_data, timeout=TIMEOUT)
        r.raise_for_status()

        # After login, get new CSRF token from landing page (could redirect)
        r = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        r.raise_for_status()
        csrf_token_name_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', r.text)
        assert csrf_token_name_search, "CSRF token name not found after login"
        csrf_token_name = csrf_token_name_search.group(1)

        # Step 3: POST to '/logistics/setCompany' with company_id=1 and CSRF token
        company_data = {
            "csrf_token_name": csrf_token_name,
            "company_id": "1"
        }
        r = session.post(f"{BASE_URL}/logistics/setCompany", data=company_data, timeout=TIMEOUT)
        r.raise_for_status()

        # Step 4: GET /logistics/manage with authenticated session and proper cookies
        r = session.get(f"{BASE_URL}/logistics/manage", timeout=TIMEOUT)
        r.raise_for_status()

        # Assert response content contains evidence of bookings management view
        text_lower = r.text.lower()
        assert ("bookings" in text_lower) or ("manage" in text_lower), \
            "Response does not contain expected booking management view content"

    finally:
        session.close()

test_getlogisticsmanageallbookingslistview()