# M.A. Logistics ERP — Known Issues & Limitations

This document tracks known issues, technical limitations, and accepted workarounds within the M.A. Logistics ERP application.

---

## Active & Accepted Issues

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

## Resolved Issues Log

* **[RESOLVED] Master Entries Missing from Booking Dropdowns**: Resolved by removing hardcoded `->where('is_active', 1)` filters across master models (`CustomerModel`, `TransporterModel`, etc.).
* **[RESOLVED] Logout Crash (`Undefined variable $success`)**: Resolved by extracting flashdata checks outside the auth session check wrapper at top of `layout.php`.
* **[RESOLVED] Slow DataTables Response on 100k+ Row Database**: Resolved by optimizing batch count queries in `Logistics::ajaxDatatable()` and executing migration `2026-06-03-000002_AddBookingsCompanyListIndex`.
* **[RESOLVED] Production Form Submissions & Company Creation Blocked**: Resolved by adding `logistics/createCompany` and `logistics/deleteCompany` to `$companyExempt` routes in `AuthFilter.php` (preventing early redirect when `selected_company_id` is null), updating `Routes.php` to `$routes->match(['get', 'post'], ...)` for company routes (preventing Apache POST-to-GET rewrite drops), setting `security.tokenRandomize = false` in `.env` to prevent CSRF token mismatch on modal submissions, adding field name alias detection (`name`/`company_name`, `gstin`/`gst_no`), and wrapping operations in try/catch blocks with SweetAlert alerts.
