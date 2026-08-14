# M.A. Logistics ERP — Known Issues & Limitations

This document tracks known issues, technical limitations, accepted workarounds, and Phase 1 client-reported issues within the M.A. Logistics ERP application.

## Current Phase 1 Backend Status

* **Resolved in CHG-015**: Backend support for item-level payment type/material category, date/category customer rate snapshot lookup, zero actual-weight allowance, master AWB weight sanity validation, Customer Master invoice address/GST/PAN fallback, remarks aliasing, and optional LR/docket clubbing.
* **Resolved in CHG-016**: Consolidated invoice PDF generation now auto-generates company-scoped financial-year invoice numbers, persists them to selected shipment rows, and reuses finalized numbers for reprints.
* **Resolved in CHG-019**: Edit-mode Save Item now autosaves booking changes, default bank accounts are preselected in invoice generation, duplicate AWB/docket feedback appears during entry, default invoices place only the first four active charges into columns, and later charges flow into Other Charges.
* **Resolved in CHG-023**: Customer rates are immutable versions with one database-enforced active scope, exact O&D lookup no longer falls back to generic rows, and the blank session save-path override no longer redirects PHP sessions to `D:\xampp\tmp`.

---

## Active & Accepted Technical Issues

### [ISSUE-001] TCPDF Table Cell Height Blowout on Dynamic Terms & Conditions
* **Description**: In TCPDF HTML rendering, when dynamic Terms & Conditions text expands to 5+ lines, table cell height inheritance forces the right-aligned signature box to push downwards, creating an empty gap above the signature.
* **Cause**: TCPDF implements standard HTML table row height inheritance rules (`<tr>` inherits tallest `<td>` height) and ignores modern CSS flexbox.
* **Workaround**: Implement Option C Architecture (independent side-by-side sub-tables $60\% / 40\%$ inside outer cells). Enforce a visual character guidance limit on the Company Settings T&C input field.
* **Related Files**: `app/Views/pdfs/invoice.php`, `app/Controllers/CompanyController.php`

---

### [ISSUE-002] MySQL Session Table Lock Contention Under Concurrent Logins
* **Description**: When 50+ concurrent users log in simultaneously on staging using `DatabaseHandler` (`ci_sessions` table), MySQL row lock contention causes transient HTTP 500 errors.
* **Cause**: Synchronous read/write operations on the `ci_sessions` database table during burst authentication storms.
* **Workaround**: Switch session driver in `app/Config/Session.php` to `FileHandler` (`writable/session`) or Redis (`RedisHandler`).
* **Related Files**: `app/Config/Session.php`, `.env`

---

### [ISSUE-003] Multi-Tab CSRF Token Mismatch
* **Description**: Opening multiple browser tabs with booking forms occasionally caused 403 Forbidden errors on form submission.
* **Cause**: Dynamic CSRF token rotation generated mismatched token field names between tab sessions.
* **Workaround**: Unified `csrf_token_name` in `Security.php` and added automatic AJAX header token injection in `erp-utils.js`.
* **Related Files**: `app/Config/Security.php`, `public/js/erp-utils.js`

---

## Phase 1 Client-Reported Issues Registry (`CHANGES REQUIRED.docx`)

| Issue ID | Client Doc Ref | Issue Title | Root Cause / Behavior | Resolution Blueprint & Code File Location |
| :--- | :--- | :--- | :--- | :--- |
| **[CLT-001]** | P1, P3, P26 | Bill To & Consignee Dropdowns | Input fields were plain text. Users typed exact names manually without autocompletion. | Convert inputs to Select2 type-ahead search dropdowns linked to Customer Master (`app/Views/logistics/booking_form.php`). |
| **[CLT-002]** | P2, P8 | Missing Payment Type & Category | Item drawer modal did not capture Payment Type and Material Category. | Add select controls to item drawer modal & persist in `shipment_items` table (`app/Models/ShipmentItemModel.php`). |
| **[CLT-003]** | P6, P7, P9 | Customer Rates Overwrite History | Customer master rate updates retroactively altered saved invoice calculations. | Add `customer_rates` table with date range (`01.04.26` to `30.04.26`) & category. Store rate on booking date (`app/Models/CustomerModel.php`). |
| **[CLT-004]** | P10 | Flight Details Clutter Page 1 | Airline select & Flight No took space on Page 1 master booking form. | Relocate Airline select & Flight No input to Page 2 / item section (`app/Views/logistics/booking_form.php`). |
| **[CLT-005]** | P11 | Invoice Print Date Mismatch | Invoice PDF printed invoice creation date instead of booking date. | Map grid and PDF row date directly to `booking_date` (`app/Views/pdfs/invoice.php`). |
| **[CLT-006]** | P14, P17, P24 | Manual Invoice Numbers | Manual numbering caused duplicate or inconsistent invoice numbers. | Auto-generate FY invoice numbers (e.g. `MA-26-27/001`) sequentially on final save (`app/Services/InvoiceService.php`). |
| **[CLT-007]** | P16 | ₹0 Charges Shown on Invoice | Surcharges with zero values (₹0) rendered on PDF invoice printouts. | Exclude ₹0/null charges during PDF row aggregation (`app/Services/InvoiceService.php`). |
| **[CLT-008]** | P18 | Missing "All Invoices" Link | Staff could not view or search all generated invoices in a single grid. | Add "All Invoices" view & grid with filters by date, customer, and invoice number (`app/Views/logistics/manage_bookings.php`). |
| **[CLT-009]** | P19, P20 | Invoice Missing Master GSTIN/Address | Invoices omitted customer GSTIN, SAC Code (996531), and full billing address. | Populate customer master GSTIN, state code, SAC code, and address onto PDF invoice header (`app/Views/pdfs/invoice.php`). |
| **[CLT-010]** | P25 | Billing Mode Restricted to AWB Only | Billing was enforced per AWB only; some clients require billing per Docket No. | Add radio toggle `[AWB Mode / Docket Mode]` in billing filter and invoice generator (`app/Services/InvoiceService.php`). |
| **[CLT-011]** | P27, P33 | Duplicate AWB Saved & Edit Wipes Items | Duplicate AWBs could be saved; editing AWB cleared item drawer contents. | Add AJAX uniqueness check on `awb_no` & preserve items on edit (`app/Services/BookingService.php`). |
| **[CLT-012]** | P35, P45 | Unsaved Data Loss on Page Error | Browser crashes or navigation lost typed booking inputs. | Implement local storage trap (`isDirty`) & Draft booking state (`app/Views/logistics/booking_form.php`). |
| **[CLT-013]** | P46 | Item Weight Lower Than AWB Weight | Total item weight could be saved less than master AWB weight. | Enforce `Total Item Weight >= Minimum AWB Weight` JS & backend validation (`public/js/erp-utils.js`). |
| **[CLT-014]** | P58 | Missing Remark / Note Option | Booking form and invoice lacked custom note/PO number fields. | Add `remarks` text input to booking form, grid, and PDF invoice (`app/Views/logistics/booking_form.php`). |
| **[CLT-015]** | P59 | Fragmented LR No on Invoices | Multiple consignments with the same LR No printed as separate fragmented lines. | Add "Club by LR No" option in Invoice Generator (`app/Services/InvoiceService.php`). |

---

## Resolved Issues Log

* **[RESOLVED] Customer Rate History and Concurrent Save Collision**: `CustomerRateService` closes changed/removed versions under a tenant/customer transaction lock; a unique active-scope key provides the database guard and stale differing saves return HTTP 409.
* **[RESOLVED] Local Session Path 500**: Removed the blank `.env` `session.savePath` override. File sessions now resolve to `writable/session`, which must remain writable at deployment.
* **[ACCEPTED LIMITATION] Native PDF Save Picker Availability**: `showSaveFilePicker()` depends on a supported Chromium browser and secure context. Other environments receive a normal browser download and the generated invoice remains recoverable from All Downloads.
* **[RESOLVED] Master Entries Missing from Booking Dropdowns**: Resolved by removing hardcoded `->where('is_active', 1)` filters across master models (`CustomerModel`, `TransporterModel`, etc.).
* **[RESOLVED] Logout Crash (`Undefined variable $success`)**: Resolved by extracting flashdata checks outside the auth session check wrapper at top of `layout.php`.
* **[RESOLVED] Slow DataTables Response on 100k+ Row Database**: Resolved by optimizing batch count queries in `Logistics::ajaxDatatable()` and executing migration `2026-06-03-000002_AddBookingsCompanyListIndex`.
* **[RESOLVED] Production Form Submissions & Company Creation Blocked**: Resolved by adding `logistics/createCompany` and `logistics/deleteCompany` to `$companyExempt` routes in `AuthFilter.php`, updating `Routes.php` to `$routes->match(['get', 'post'], ...)`, setting `security.tokenRandomize = false` in `.env`, adding field name alias detection, and wrapping operations in try/catch blocks with SweetAlert alerts.
* **[RESOLVED] Playwright Strict Mode Ambiguity on Login Locators**: Updated brittle absolute XPath in `testsprite_tests/TC001_Sign_in_and_reach_the_logistics_workspace.py` to explicit `input[name="username"]` and `input[name="password"]` locators.
