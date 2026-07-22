# M.A. Logistics ERP — Project Development Rules & Constraints

This document defines the permanent coding standards, architectural rules, business logic constraints, and UI/UX conventions for the M.A. Logistics ERP application. All AI agents and developers must strictly adhere to these non-negotiable rules.

---

## 1. Documentation Maintenance Rule (Synchronized Documentation)
* **Documentation as Code**: The documentation inside `docs/` is an integral part of the project codebase.
* **Immediate Update Rule**: Whenever code is modified, added, removed, refactored, or system behavior changes, the affected documentation files must be reviewed and updated immediately.
* **Affected File Threshold**: At minimum, update whichever of `rules.md`, `changes.md`, `functionality.md`, `architecture.md`, `database.md`, `api.md`, `known-issues.md`, or `testing.md` are impacted by code changes.
* **Synchronization**: The documentation must always accurately reflect the current state of the codebase. Code and documentation must remain synchronized at all times.

---

## 2. Coding & Architectural Rules
* **Framework Standard**: Built strictly on CodeIgniter 4 (PHP 7.4+ / 8.x) following MVC architecture with a dedicated Service Layer (`BookingService.php`).
* **Fat Controller Prevention**: Controllers (such as `Logistics.php`) must delegate heavy calculations, transaction handling, array transformations, and export logic to dedicated service classes.
* **Standard HTML Controls**: Do NOT use Select2 or heavy third-party search dropdown libraries. Standard Bootstrap dropdowns (`<select class="form-select form-select-sm">`) must be used for all master data inputs to match legacy UX speed.
* **Responsive Layout Standard**: All main application pages must use `<div class="container-fluid">` instead of fixed `.container` wrappers to utilize 100% of horizontal screen space on ultrawide desktop monitors.
* **Error Response Format**: All internal AJAX and API endpoints must return structured JSON error responses with appropriate HTTP status codes (e.g., 400 Bad Request, 404 Not Found) instead of unformatted HTML error pages.

---

## 3. Business & Financial Rules
* **Volumetric Weight Calculation**: Default volumetric weight divisor is `6000` (`(L * W * H) / 6000`). The divisor must be configurable per booking session.
* **Chargeable Weight Resolution**: Chargeable weight per item must strictly resolve as $\max(\text{Actual Weight}, \text{Volumetric Weight})$. Manual overrides are allowed but must be logged to `audit_logs`.
* **Base Freight Calculation**: Base Freight Charge $= \text{Sales Rate} \times \text{Total Chargeable Weight}$.
* **Taxable Total Computation**: Total Taxable Amount $=$ Base Freight Charge $+$ sum of all `.calc-surcharge` input values.
* **GST Application & Rounding**:
  * CGST, SGST, and IGST rates are retrieved dynamically from the active **Company Master**.
  * Taxes apply conditionally based on the `#gst_applied` checkbox state.
  * All computed tax amounts must be rounded to the nearest integer using standard rounding (`Math.round` / PHP `round()`).
* **Multi-Company Isolation**: All database queries for tenant data must enforce multi-company context filtering using session `company_id`.

---

## 4. UI/UX & Form Rules
* **Unsaved Changes Trap (`isDirty`)**: Any modification to form inputs in `booking_form.php` must set an `isDirty` flag. Internal link clicks or browser back-button actions (`popstate`) must trigger a styled SweetAlert2 confirmation dialog.
* **Smart Field Carry-Forward**: When adding multiple shipment items in the manifest drawer, details including **Customer**, **Bill To**, **Consignee**, **Docket No**, **Part No**, and **Invoice Date** must automatically copy forward to the next row.
* **Dropdown Data Visibility**: Master data models must NOT filter out records using rigid `is_active = 1` filters unless user-facing toggle controls exist in the admin UI. All active master entries must populate immediately in booking dropdowns.

---

## 5. PDF Generation & Layout Rules
* **Engine Compatibility**: PDF invoice generation uses **TCPDF**.
* **Layout Stability (Option C Standard)**: Footer layout containing dynamic Terms & Conditions and Digital Signatures must be structured using independent side-by-side HTML sub-tables ($60\%$ left / $40\%$ right) inside outer cells to prevent TCPDF cell height blowouts and text clipping.
* **Signature Authorization**: Authorized digital signature images (`public/uploads/signatures/`) must print centered within the right-aligned signature box.
