import requests
import re

BASE_URL = "https://granthinfotech.online"
USERNAME = "admin"
PASSWORD = "admin"


def test_postlogisticsdeletebookinginvalidid():
    session = requests.Session()
    try:
        # Step 1: GET '/' to retrieve CSRF token from login page
        resp = session.get(BASE_URL + "/", timeout=30)
        resp.raise_for_status()
        csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_match, "CSRF token not found in login page"
        csrf_token_name = csrf_token_match.group(1)

        # Step 2: POST to '/auth/attemptLogin' with csrf_token_name, username, password
        login_data = {
            'csrf_token_name': csrf_token_name,
            'username': USERNAME,
            'password': PASSWORD
        }
        headers = {'Referer': BASE_URL + '/'}
        resp = session.post(BASE_URL + "/auth/attemptLogin", data=login_data, headers=headers, timeout=30)
        resp.raise_for_status()
        # After login, update csrf_token_name from response HTML or cookies if needed
        # To check login success, we can check if session cookie or redirect or response text do not indicate failure

        # Step 3: GET '/' again or wherever to get updated CSRF token for next request
        resp = session.get(BASE_URL + "/", timeout=30)
        resp.raise_for_status()
        csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp.text)
        assert csrf_token_match, "CSRF token not found after login"
        csrf_token_name = csrf_token_match.group(1)

        # Step 4: POST to '/logistics/setCompany' with csrf_token_name and company_id=1
        set_company_data = {
            'csrf_token_name': csrf_token_name,
            'company_id': '1'
        }
        headers['Referer'] = BASE_URL + '/'
        resp = session.post(BASE_URL + "/logistics/setCompany", data=set_company_data, headers=headers, timeout=30)
        resp.raise_for_status()

        # Step 5: POST to '/logistics/delete/{id}' with invalid booking id
        # Use 999999 as invalid id (assumed not existing)
        invalid_id = "999999"
        # Need latest CSRF token again before POST delete
        resp2 = session.get(BASE_URL + "/", timeout=30)
        resp2.raise_for_status()
        csrf_token_match = re.search(r'name="csrf_token_name"\s+value="([^"]+)"', resp2.text)
        assert csrf_token_match, "CSRF token not found before delete"
        csrf_token_name = csrf_token_match.group(1)

        delete_data = {
            'csrf_token_name': csrf_token_name
        }
        headers['Referer'] = BASE_URL + '/'
        url_delete = f"{BASE_URL}/logistics/delete/{invalid_id}"
        resp = session.post(url_delete, data=delete_data, headers=headers, timeout=30)

        # As per PRD, deleting with invalid id returns error response and no booking is deleted.
        # We expect non-200 or 200 with error in response JSON.
        # Check status code not 200 success with booking deleted
        if resp.status_code == 200:
            try:
                json_resp = resp.json()
            except Exception:
                json_resp = None
            # Expect an error message presence or field indicating failure
            assert json_resp is not None, "Response JSON expected on invalid delete"
            # There is no schema given for error, so check some error indication keys commonly present
            error_detected = False
            for key in ["error", "status", "message"]:
                if key in json_resp:
                    val = json_resp.get(key)
                    # Accept if status is error or message indicates failure
                    if key == "status" and val.lower() == "error":
                        error_detected = True
                    if key == "error" and val:
                        error_detected = True
                    if key == "message" and ("error" in val.lower() or "not found" in val.lower() or "invalid" in val.lower()):
                        error_detected = True
            assert error_detected, f"Expected error response, got: {json_resp}"
        else:
            # If server returns error status code such as 4xx or 5xx, acceptable for invalid delete
            assert resp.status_code >= 400, f"Unexpected status code {resp.status_code} for invalid delete"

    finally:
        session.close()


test_postlogisticsdeletebookinginvalidid()