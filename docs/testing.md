# M.A. Logistics ERP — Testing & QA Framework

This document outlines the testing strategy, manual smoke test procedures, performance load-testing suite, and regression testing criteria for M.A. Logistics ERP.

---

## 1. Manual Smoke Test Plan (Production / Staging)

Execute this 10-minute smoke test before signing off on any production deployment:

1. **Authentication & Session**:
   - Log in with valid credentials $\rightarrow$ Select active company $\rightarrow$ Verify dashboard loads recent bookings.
2. **DataTables Grid Performance**:
   - Open **Manage Bookings** $\rightarrow$ Verify booking grid loads in $< 3.0$ seconds.
3. **Consignment Entry & Copy-Forward Verification**:
   - Click **New Booking** $\rightarrow$ Add Item row in drawer $\rightarrow$ Save $\rightarrow$ Add second Item row $\rightarrow$ Verify **Customer**, **Docket No**, **Part No**, and **Invoice Date** automatically copy forward.
4. **GST Applied Dynamic Math**:
   - Fill in sales rate and surcharges $\rightarrow$ Toggle **GST Applied** checkbox ON/OFF $\rightarrow$ Verify Taxable Total, CGST, SGST, IGST, and Net Payable recalculate instantly.
5. **PDF Invoice Export**:
   - Open generated booking $\rightarrow$ Click **PDF Invoice** $\rightarrow$ Verify horizontal layout, dynamic Terms & Conditions alignment, and digital signature rendering.
6. **Tracking & POD Lifecycle**:
   - Open tracking drawer on booking entry $\rightarrow$ Add status event *Out for Delivery* $\rightarrow$ Save $\rightarrow$ Verify tracking history updates asynchronously.
7. **JSON Error Response Integrity**:
   - Issue invalid delete request via browser devtools $\rightarrow$ Verify backend returns formatted JSON error (`400/404`) instead of HTML stack traces.

---

## 2. Automated Performance & Load Testing

### Environment Preparation
```bash
# Seed 10,000 synthetic booking records
php spark migrate
php spark loadtest:seed --count 10000 --company 1
```

### Concurrency Load Execution
Run Python concurrency load test target against active environment:
```powershell
$env:TEST_BASE_URL="http://localhost:8080"
python testsprite_tests/TC_perf_load_and_concurrency.py
```

### Test Benchmarks & Criteria
* **Target Load**: 50 concurrent active user sessions.
* **Grid Response Time**: $< 500\text{ ms}$ average response time on DataTables AJAX endpoint.
* **Error Rate**: $0\%$ 5xx HTTP server errors under target concurrency.

### Load Test Purge Clean-up
```bash
# Purge synthetic load test data
php spark loadtest:purge --company 1
```

---

## 3. Regression Test Coverage Matrix
| Test Case | Scenario | Expected Result | Status |
| :--- | :--- | :--- | :---: |
| **TC001** | Standard AWB Creation | Booking saved with shipment rows & sales charges | PASS |
| **TC002** | Manual Chargeable Weight Override | Audit log record created in `audit_logs` | PASS |
| **TC003** | Parallel Concurrent Login Storm | Session handles 50 concurrent logins without 500 | PASS |
| **TC004** | Master Entry Creation & Dropdown Sync | New customer appears instantly in booking form dropdown | PASS |
| **TC005** | Invalid Booking Delete Request | Returns `{ "status": "error" }` with 400 HTTP status | PASS |
| **TC010** | Form Submission CSRF Verification | Submission succeeds with unified `csrf_token_name` | PASS |
