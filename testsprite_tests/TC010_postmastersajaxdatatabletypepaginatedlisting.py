import requests
import re

BASE_URL = "https://granthinfotech.online"
USERNAME = "admin"
PASSWORD = "admin"
COMPANY_ID = "1"
TIMEOUT = 30

def test_postmastersajaxdatatable_customers_paginated_listing():
    session = requests.Session()
    try:
        # Step 1: GET / to retrieve CSRF token name and value
        r = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        r.raise_for_status()
        csrf_token_name_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', r.text)
        assert csrf_token_name_match, "CSRF token name not found on GET /"
        csrf_token_name = csrf_token_name_match.group(1)

        # The actual CSRF token value is in the field named by csrf_token_name
        csrf_token_value_match = re.search(r'name="' + re.escape(csrf_token_name) + r'"\s+value="([^"]+)"', r.text)
        assert csrf_token_value_match, "CSRF token value not found on GET /"
        csrf_token_value = csrf_token_value_match.group(1)

        # Step 2: POST /auth/attemptLogin with correct CSRF token and credentials
        login_payload = {
            csrf_token_name: csrf_token_value,
            "username": USERNAME,
            "password": PASSWORD
        }
        login_headers = {
            "Referer": f"{BASE_URL}/"
        }
        r = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_payload, headers=login_headers, timeout=TIMEOUT)
        r.raise_for_status()
        login_json = r.json()
        assert "status" in login_json, "Login response missing status key"
        assert login_json["status"] == "success", "Login failed"

        # Step 3: GET / again to get new CSRF for setCompany
        r = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
        r.raise_for_status()
        csrf_token_name_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', r.text)
        assert csrf_token_name_match, "CSRF token name not found on GET / before setCompany"
        csrf_token_name2 = csrf_token_name_match.group(1)

        csrf_token_value_match = re.search(r'name="' + re.escape(csrf_token_name2) + r'"\s+value="([^"]+)"', r.text)
        assert csrf_token_value_match, "CSRF token value not found on GET / before setCompany"
        csrf_token_value2 = csrf_token_value_match.group(1)

        set_company_payload = {
            csrf_token_name2: csrf_token_value2,
            "company_id": COMPANY_ID
        }
        r = session.post(f"{BASE_URL}/logistics/setCompany", data=set_company_payload, timeout=TIMEOUT)
        r.raise_for_status()

        # Step 4: POST /masters/ajax-datatable/customers with valid pagination & search form data
        datatable_payload = {
            "draw": "1",
            "start": "0",
            "length": "10",
            "search[value]": "",
            "order[0][column]": "0",
            "order[0][dir]": "asc"
        }
        r = session.post(f"{BASE_URL}/masters/ajax-datatable/customers", data=datatable_payload, timeout=TIMEOUT)
        r.raise_for_status()

        json_response = r.json()
        assert isinstance(json_response, dict), "Response is not a JSON object"
        assert "draw" in json_response, "Missing 'draw' in response"
        assert str(json_response["draw"]) == datatable_payload["draw"], "Draw value mismatch"
        assert "recordsTotal" in json_response and isinstance(json_response["recordsTotal"], int), "Invalid or missing 'recordsTotal'"
        assert "recordsFiltered" in json_response and isinstance(json_response["recordsFiltered"], int), "Invalid or missing 'recordsFiltered'"
        assert "data" in json_response and isinstance(json_response["data"], list), "Missing or invalid 'data' array"

    finally:
        session.close()

test_postmastersajaxdatatable_customers_paginated_listing()
