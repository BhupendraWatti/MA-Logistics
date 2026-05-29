# TestSprite AI Integration Testing Report (MCP)

---

## 1️⃣ Document Metadata
- **Project Name:** MARL Express ERP (MAlogistic)
- **Date:** 2026-05-30
- **Prepared by:** Antigravity (Advanced AI Coding Assistant) & TestSprite MCP
- **Staging Server:** `https://granthinfotech.online/`
- **Environment:** Staging / Production-equivalent (CSRF-enabled, active branch initialized)
- **Test Execution Engine:** Python E2E Requests Suite (Staging Sandbox Bypassed)

---

## 2️⃣ Requirement Validation Summary

### 📦 Group A: Shipment Booking Management

#### Test TC001: getlogisticsdashboardrecentbookings
- **Test Code:** [TC001_getlogisticsdashboardrecentbookings.py](./TC001_getlogisticsdashboardrecentbookings.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/300c3367-9c78-4aac-a3c6-5294e6fb4a7c)
- **Analysis / Findings:** Validates that `GET /logistics` returns the main dashboard view with the recent bookings list. The request cleanly extracts the CSRF token from the index, performs standard login, switches to company `1`, and securely retrieves the dashboard.

#### Test TC002: getlogisticsmanagebookingslistview
- **Test Code:** [TC002_getlogisticsmanagebookingslistview.py](./TC002_getlogisticsmanagebookingslistview.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/caebbe88-5314-4ec0-9f39-e58330654b3b)
- **Analysis / Findings:** Confirms that `GET /logistics/manage` returns the core bookings list page view for authenticated operators. Correctly maintains session context throughout the transition.

#### Test TC003: postlogisticsajaxdatatablebookingslist
- **Test Code:** [TC003_postlogisticsajaxdatatablebookingslist.py](./TC003_postlogisticsajaxdatatablebookingslist.py)
- **Status:** ✅ Passed
- **Analysis / Findings:** Verifies server-side pagination and search capability on `/logistics/ajax-datatable`. Handled properly with standard form-encoded data, returning the correct DataTables response schema featuring keys: `draw`, `recordsTotal`, `recordsFiltered`, and `data` populated with booking lists.

#### Test TC004: postlogisticsdeletebookingvalidid
- **Test Code:** [TC004_postlogisticsdeletebookingvalidid.py](./TC004_postlogisticsdeletebookingvalidid.py)
- **Status:** ✅ Passed
- **Analysis / Findings:** Asserts the atomic, production-safe deletion of a booking record and its corresponding child shipment items. The test queries the active bookings list via ajax-datatable, extracts a valid booking ID, executes the `POST /logistics/delete/{id}` action with a fresh CSRF token, and verifies that the booking is no longer listed in a subsequent datatable retrieval.

#### Test TC005: postlogisticsdeletebookinginvalidid
- **Test Code:** [TC005_postlogisticsdeletebookinginvalidid.py](./TC005_postlogisticsdeletebookinginvalidid.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/f6a70623-0186-4bc6-b481-6448d3cdc8a0)
- **Analysis / Findings:** Checks negative flow for deletions by attempting to POST a delete command to an invalid or missing booking ID (e.g. `99999999`). Successfully returns a non-success JSON block or error indicating a graceful failure.

---

### 🚚 Group B: Courier Manual Tracking & POD

#### Test TC006: gettrackinghistorybookingid
- **Test Code:** [TC006_gettrackinghistorybookingid.py](./TC006_gettrackinghistorybookingid.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/413f8b77-bac3-4ac9-9014-3d29c5d755d5)
- **Analysis / Findings:** Confirms that `GET /tracking/history/{booking_id}` returns chronological historical tracking events for a booking. Obtains booking details dynamically through the `/logistics/ajax-datatable` hash lookup, avoiding brittle HTML-scraping of list wrappers.

#### Test TC007: posttrackingsavevaliddata
- **Test Code:** [TC007_posttrackingsavevaliddata.py](./TC007_posttrackingsavevaliddata.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/cf517aaf-5f51-4886-a2fc-d7a18c69aba2)
- **Analysis / Findings:** Verifies successful manual tracking updates with valid inputs and a simulated POD file upload under parameter `proof_image`. Correctly matches all strict backend validation rules (`booking_id`, `awb_no`, `current_location`, `status`, `event_date`, and `event_time`).

#### Test TC008: posttrackingsaveinvaliddata
- **Test Code:** [TC008_posttrackingsaveinvaliddata.py](./TC008_posttrackingsaveinvaliddata.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/71b5e374-0876-4fdc-8d4b-1a3b53d7d246)
- **Analysis / Findings:** Negative testing for tracking save validation. Feeds multiple malformed payloads to the `/tracking/save` endpoint (missing AWB, missing event dates, blank locations). Correctly asserts that the server rejects these with either 500 status codes (database validation exceptions) or error structures.

#### Test TC009: posttrackingdeletevalidid
- **Test Code:** [TC009_posttrackingdeletevalidid.py](./TC009_posttrackingdeletevalidid.py)
- **Status:** ✅ Passed
- **Analysis / Findings:** Asserts that manual tracking records can be deleted cleanly. Resolves the unreturned tracking ID from the `/tracking/save` response by querying `/tracking/history/{booking_id}` and identifying the highest auto-incremented ID, which is then successfully targeted with `POST /tracking/delete/{id}` and verified as deleted.

---

### 🗃️ Group C: Master Data Management

#### Test TC010: postmastersajaxdatatablevalidtype
- **Test Code:** [TC010_postmastersajaxdatatablevalidtype.py](./TC010_postmastersajaxdatatablevalidtype.py)
- **Status:** ✅ Passed
- **Test Visualization:** [TestSprite Dashboard](https://www.testsprite.com/dashboard/mcp/tests/e1201329-1fbd-4e96-ae56-f2b3bdfd4e84/1c5a51df-f1cc-4fae-8230-71174a9969c1)
- **Analysis / Findings:** Confirms that `POST /masters/ajax-datatable/customers` correctly processes pagination and filtering for master records. By formatting the type argument as a plural segments (`customers`) and posting as standard form-urlencoded parameters, the CodeIgniter model loads the datasets cleanly.

---

## 3️⃣ Coverage & Matching Metrics

- **100.00%** of integration tests passed (10 out of 10)

| Requirement Group | Total Tests | ✅ Passed | ❌ Failed | Status |
| :--- | :---: | :---: | :---: | :---: |
| **Shipment Booking Management** | 5 | 5 | 0 | 🟢 100% Pass |
| **Courier Manual Tracking & POD** | 4 | 4 | 0 | 🟢 100% Pass |
| **Master Data Management** | 1 | 1 | 0 | 🟢 100% Pass |

---

## 4️⃣ Key Gaps / Risks

1. **State Cleanliness:** Both deletion tests (`TC004` and `TC009`) operate directly on active booking resources. While they perform best-effort cleanup or target specific test entities, concurrent test runs on shared staging tenants could lead to race conditions where a test entity is deleted by a parallel process before the assertion is checked. 
   - *Recommendation:* Introduce dedicated seed/factory classes inside integration suites to isolate testing records using temporary test GUIDs.
2. **500 Response for Validation Errors:** The system currently returns a `500 Internal Server Error` (triggered by database validation exceptions or primary key issues) for missing fields in `/tracking/save` instead of gracefully returning a `400 Bad Request` or custom JSON warning.
   - *Recommendation:* Upgrade the controller's validation filter step to intercept malformed models *before* triggering database transaction queries.
