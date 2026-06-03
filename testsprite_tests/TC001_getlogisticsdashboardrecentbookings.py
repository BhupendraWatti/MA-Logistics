import requests
import re

def test_TC001_getlogisticsdashboardrecentbookings():
    base_url = "https://granthinfotech.online"
    session = requests.Session()
    try:
        # Step 1: GET '/' to get CSRF token
        resp = session.get(f"{base_url}/", timeout=30)
        assert resp.status_code == 200, f"GET / failed with status {resp.status_code}"
        csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_match, "CSRF token not found on GET /"
        csrf_token = csrf_token_match.group(1)

        # Step 2: POST to '/auth/attemptLogin' with csrf_token_name, username=admin, password=admin
        login_payload = {
            "csrf_token_name": csrf_token,
            "username": "admin",
            "password": "admin"
        }
        login_resp = session.post(f"{base_url}/auth/attemptLogin", data=login_payload, timeout=30)
        assert login_resp.status_code == 200, f"Login POST failed with status {login_resp.status_code}"
        # Verify login success by checking for ci_session cookie existence
        assert "ci_session" in session.cookies, "ci_session cookie not found after login"

        # Extract new CSRF token from login response for next requests
        csrf_token_match_login = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', login_resp.text)
        if csrf_token_match_login:
            csrf_token = csrf_token_match_login.group(1)

        # Step 3: POST to '/logistics/setCompany' with csrf_token_name and company_id=1
        set_company_payload = {
            "csrf_token_name": csrf_token,
            "company_id": "1"
        }
        set_company_resp = session.post(f"{base_url}/logistics/setCompany", data=set_company_payload, timeout=30)
        assert set_company_resp.status_code == 200, f"Set company POST failed with status {set_company_resp.status_code}"

        # Step 4: GET /logistics with authenticated session and ci_session cookie
        logistics_resp = session.get(f"{base_url}/logistics", timeout=30)
        assert logistics_resp.status_code == 200, f"GET /logistics failed with status {logistics_resp.status_code}"

        # Step 5: Check presence of 'Recent Bookings' or 'recent bookings' or a table with id containing 'booking'
        page_text = logistics_resp.text.lower()
        has_recent_bookings = (
            ("recent bookings" in page_text) or
            (re.search(r'<table[^>]+id=["\'][^"\']*booking[^"\']*["\']', logistics_resp.text, re.IGNORECASE) is not None)
        )
        assert has_recent_bookings, "Page does not contain 'Recent Bookings' or a bookings table ID"

    finally:
        session.close()

test_TC001_getlogisticsdashboardrecentbookings()