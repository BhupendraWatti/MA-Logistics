# M.A. Logistics ERP — Implementation & Change Log

This file tracks every technical change, feature implementation, and refactoring performed on the M.A. Logistics ERP project.

---

## Change Log Entries

### [CHG-001] Select2 Reversion to Standard Dropdowns
* **Status**: Completed
* **Priority**: High
* **Requirement**: Revert heavy searchable combo boxes to simple standard HTML `<select>` tags to match legacy software speed.
* **Implementation**: Stripped Select2 CSS/JS from `booking_form.php` and `layout.php`. Applied `form-select form-select-sm`.
* **Files Modified**: `app/Views/logistics/booking_form.php`, `app/Views/layout.php`
* **Dependencies**: Bootstrap 5
* **Risks**: None.

---

### [CHG-002] Sidebar Accordion Menu Reorganization
* **Status**: Completed
* **Priority**: Medium
* **Requirement**: Prevent dropdown menu overlays from covering navigation links. Move master registries to a dedicated menu.
* **Implementation**: Replaced Bootstrap Dropdown with Collapse (Accordion) components. Created dedicated **Masters** accordion bucket containing Customer, Transporter, Driver, Airline, and Lookup master links.
* **Files Modified**: `app/Views/layout.php`
* **Dependencies**: Bootstrap Collapse JS

---

### [CHG-003] Fluid Responsive Layout Conversion
* **Status**: Completed
* **Priority**: Medium
* **Requirement**: Utilize 100% horizontal screen space on ultrawide desktop monitors.
* **Implementation**: Replaced `.container` wrappers with `.container-fluid` across manage bookings, search results, booking viewer, and settings pages.
* **Files Modified**: `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/search_results.php`, `app/Views/logistics/view_booking.php`, `app/Views/company/settings.php`

---

### [CHG-004] SweetAlert2 Navigation & Back-Button Interception
* **Status**: Completed
* **Priority**: High
* **Requirement**: Prevent accidental loss of unsaved form data during navigation or browser back-button clicks.
* **Implementation**: Added global `isDirty` state listener. Intercepted `<a>` clicks and trapped `popstate` events using `history.pushState` and SweetAlert2 confirmation dialogs.
* **Files Modified**: `app/Views/logistics/booking_form.php`

---

### [CHG-005] Master Data `is_active` Filter Removal
* **Status**: Completed
* **Priority**: High
* **Requirement**: Fix bug where master entries created in the dashboard were missing from booking dropdowns.
* **Implementation**: Removed `->where('is_active', 1)` strict backend filter from Customer, Transporter, Driver, Airline, and Lookup models.
* **Files Modified**: `app/Models/CustomerModel.php`, `app/Models/TransporterModel.php`, `app/Models/DriverModel.php`, `app/Models/AirlineModel.php`, `app/Models/LookupValueModel.php`

---

### [CHG-006] Removal of Sort Order Column from Lookups
* **Status**: Completed
* **Priority**: Low
* **Requirement**: Simplify lookup value management by dropping unused sort order field.
* **Implementation**: Removed input field from `lookups.php`, updated model allowed fields, and executed SQL migration dropping `sort_order` column. Lookups now sort alphabetically.
* **Files Modified**: `app/Views/masters/lookups.php`, `app/Models/LookupValueModel.php`, `app/Controllers/MasterController.php`

---

### [CHG-007] Logout Exception Scoping Fix
* **Status**: Completed
* **Priority**: High
* **Requirement**: Resolve `ErrorException: Undefined variable $success` on session logout.
* **Implementation**: Extracted flashdata session checks (`$success`, `$error`, `$info`) to the top of `layout.php` body tag to execute safely regardless of auth state.
* **Files Modified**: `app/Views/layout.php`

---

### [CHG-008] Extended Master Data Architecture
* **Status**: Completed
* **Priority**: High
* **Requirement**: Support location data, company settings, and expanded customer billing/GST details.
* **Implementation**: Created `system_settings` and `location_master` tables; expanded `customer_master` columns. Extracted client utilities to `public/js/erp-utils.js` and modularized customer form fields to `_customer_form_fields.php`.
* **Files Modified**: Migrations, `app/Models/SystemSettingsModel.php`, `app/Models/CustomerModel.php`, `public/js/erp-utils.js`, `app/Views/masters/_customer_form_fields.php`

---

### [CHG-009] PDF Invoice Generator Upgrade & Digital Signatures
* **Status**: Completed
* **Priority**: High
* **Requirement**: Support legal horizontal PDF invoices with dynamic T&C and digital signatures.
* **Implementation**: Revamped `app/Views/pdfs/invoice.php`, updated `CompanyController`, added signature upload directory (`public/uploads/signatures/`).
* **Files Modified**: `app/Controllers/CompanyController.php`, `app/Views/pdfs/invoice.php`, `app/Views/masters/company_settings.php`

---

### [CHG-010] DataTables Query Optimization & Database Indexing
* **Status**: Completed
* **Priority**: Critical
* **Requirement**: Fix slow booking list loading under high concurrency and 100k+ row datasets.
* **Implementation**: Optimized `ajaxDatatable()` query in `Logistics.php` to eliminate N+1 joins. Created compound index `(company_id, id DESC)` via migration `2026-06-03-000002`.
* **Files Modified**: `app/Controllers/Logistics.php`, `app/Database/Migrations/2026-06-03-000002_AddBookingsCompanyListIndex.php`

---

### [CHG-011] Modular Knowledge Base Initialization
* **Status**: Completed
* **Priority**: High
* **Requirement**: Consolidate and structure project knowledge base into standardized, modular markdown files linked via `gemini.md`.
* **Implementation**: Created `rules.md`, `changes.md`, `functionality.md`, `architecture.md`, `database.md`, `api.md`, `known-issues.md`, `testing.md`, `README.md`, and updated `gemini.md`.
* **Files Modified**: `docs/*`
