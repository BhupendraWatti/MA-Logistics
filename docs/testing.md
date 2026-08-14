# M.A. Logistics ERP — Testing & QA Framework

This document outlines the testing strategy, manual smoke test procedures, performance load-testing suite, and regression testing criteria for M.A. Logistics ERP.

## Current Backend Regression Additions

| Test Case | Scenario | Expected Result | Status |
| :--- | :--- | :--- | :---: |
| **TC016** | Customer Rate Snapshot Fallback | Save an item with blank/zero rate where `customer_rates` contains a matching company/customer/category/date row; `shipment_items.rate` stores the matched rate. | PASS |
| **TC017** | Zero Actual Weight Allowed | Save a shipment item with `actual_weight = 0` and no positive booking-level master weight. | PASS |
| **TC018** | Master AWB Weight Guard | Save a booking with `total_weight` greater than summed item actual weights; backend rejects the save with a clear validation message. | PASS |
| **TC019** | Customer Master Invoice Fallback | Generate PDF for a customer with master address/GST/PAN populated; invoice header uses master data before shipment free-text fallback. | PASS |
| **TC020** | Optional LR/Docket Clubbing | Generate invoice with `club_by_lr=1` or `billing_mode=docket` for repeated docket numbers; matching LR rows consolidate while default invoices stay per item. | PASS |
| **TC021** | FY Invoice Auto-Number Finalization | Generate a consolidated PDF for blank invoice rows; backend allocates the next company/prefix/FY number, persists it to selected shipment rows, and reuses it on reprint. | PASS |
| **TC022** | Hide Zero-Value Invoice Charges | Generate PDF invoices where docket/fuel/other/pickup/delivery charges are zero; zero-value surcharge columns are omitted and non-zero charge columns still total correctly. | PASS |
| **TC023** | Item-Level Payment Type UI | Add/edit shipment items with Payment Type in the item drawer; values persist in `items_json`, display in the grid, and keep booking-level compatibility through the hidden field. | PASS |
| **TC024** | Edit-Mode Save Item Autosave | Open an existing booking, edit item charges, click Save Item, then reload the AWB without pressing Update Booking. | PASS |
| **TC025** | Invoice First-Four Charge Columns | Generate a default invoice with more than four active item charges; first four charges render as columns and remaining charges sum into Other Charges. | PASS |
| **TC026** | Runtime AWB/Docket Uniqueness | Type a duplicate AWB or docket in the booking form/item drawer; duplicate feedback appears before final booking save and Save Item blocks duplicate docket rows. | PASS |
| **TC027** | Default Bank Preselection | Mark one bank as default in Bank Accounts, open All Invoices, and verify that bank is selected automatically. | PASS |

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
| **TC011** | Item Custom Charge Add & Save | Click `+ Add Charge` in item drawer → enter "Super Charge" + ₹500 → save item → submit booking → verify `shipment_items.custom_charges = [{"label":"Super Charge","value":500}]` in DB | PASS |
| **TC012** | Custom Charges in Invoice PDF | Open booking with custom item charges → Export PDF → Verify "OTHER CHG" column value includes custom charge amounts, and "TOTAL Amt." correctly equals Freight + Docket + Fuel + OTHER CHG | PASS |
| **TC013** | Global Surcharge Add & Persist | Click `+ Add Surcharge` in booking form → enter label + amount → save → reopen booking → verify global surcharge rows restore correctly from DB | PASS |
| **TC014** | Edit Booking — Custom Charges Round-Trip | Open existing booking with custom charges → edit item → verify charges pre-populated in drawer → change label → save → verify DB updated | PASS |
| **TC015** | Existing Booking Without Custom Charges | Open pre-CHG-012 booking → verify no JS errors, invoice renders correctly, totals unchanged | PASS |
