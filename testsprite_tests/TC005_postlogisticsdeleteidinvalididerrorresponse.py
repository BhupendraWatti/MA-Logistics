import requests
import re

BASE_URL = "https://granthinfotech.online"
TIMEOUT = 30
USERNAME = "admin"
PASSWORD = "admin"
COMPANY_ID = "1"

def test_postlogisticsdeleteidinvalididerrorresponse():
    session = requests.Session()

    # Step 1: GET / to get CSRF token
    resp = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    assert resp.status_code == 200, "Failed to GET / for CSRF token"
    html = resp.text

    csrf_search = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html)
    assert csrf_search, "CSRF token not found in / response"
    csrf_token = csrf_search.group(1)

    # Step 2: POST /auth/attemptLogin with csrf_token_name, username=admin, password=admin
    login_data = {
        "csrf_token_name": csrf_token,
        "username": USERNAME,
        "password": PASSWORD,
    }
    login_resp = session.post(f"{BASE_URL}/auth/attemptLogin", data=login_data, timeout=TIMEOUT)
    assert login_resp.status_code == 200, "Login POST failed"
    login_json = login_resp.json()
    assert "success" in login_json and login_json["success"] is True, "Login unsuccessful"

    # Get fresh CSRF token after login by GET /
    resp2 = session.get(f"{BASE_URL}/", timeout=TIMEOUT)
    assert resp2.status_code == 200, "Failed to GET / after login for CSRF token"
    html2 = resp2.text
    csrf_search2 = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', html2)
    assert csrf_search2, "CSRF token not found in / response after login"
    csrf_token2 = csrf_search2.group(1)

    # Step 3: POST /logistics/setCompany with csrf_token_name and company_id=1
    comp_data = {
        "csrf_token_name": csrf_token2,
        "company_id": COMPANY_ID,
    }
    comp_resp = session.post(f"{BASE_URL}/logistics/setCompany", data=comp_data, timeout=TIMEOUT)
    assert comp_resp.status_code == 200, "SetCompany POST failed"
    comp_json = comp_resp.json()
    assert "success" in comp_json and comp_json["success"] is True, "SetCompany unsuccessful"

    # Use the valid session and cookie ci_session for subsequent requests:
    # Step 4: Test POST /logistics/delete/{id} with invalid numeric ids 0 and 99999999
    invalid_ids = [0, 99999999]
    headers = {}  # No specific headers beyond cookies needed
    for invalid_id in invalid_ids:
        url = f"{BASE_URL}/logistics/delete/{invalid_id}"

        post_data = {
            "csrf_token_name": csrf_token2
        }

        delete_resp = session.post(url, data=post_data, timeout=TIMEOUT)

        if delete_resp.status_code == 200:
            if delete_resp.content:
                try:
                    resp_json = delete_resp.json()
                except Exception:
                    resp_json = None
                assert resp_json is not None, f"Response is 200 but no JSON returned for invalid id {invalid_id}"
                # Check typical error keys or success false
                has_error = False
                if 'status' in resp_json and isinstance(resp_json['status'], str) and resp_json['status'].lower() == 'error':
                    has_error = True
                elif resp_json.get("success") is False:
                    has_error = True
                elif any(k in resp_json for k in ['error', 'message']):
                    has_error = True
                assert has_error, f"Invalid id {invalid_id} did not return error info in JSON response."
            else:
                # Empty response content with 200 status for invalid id: treat as error response
                assert False, f"Empty response body with 200 status for invalid id {invalid_id}"
        else:
            # Accept any error status code >=400 as success for this test
            assert delete_resp.status_code >= 400, (
                f"Unexpected status code {delete_resp.status_code} for invalid id {invalid_id}"
            )

test_postlogisticsdeleteidinvalididerrorresponse()