# M.A. Logistics ERP — Implementation & Change Log

This file tracks every technical change, feature implementation, refactoring, and pending scope addition performed on the M.A. Logistics ERP project.

---

## 1. Completed Phase 1 Implementations

### [CHG-012] Dynamic Custom Charges — Shipment Item + Global Surcharges
* **Status**: Completed
* **Priority**: High (Client-Requested)
* **Requirement**: Allow staff to add unlimited dynamic label+value charge fields per shipment item AND per booking (global surcharges). Labels are user-defined (e.g. "Super Charge", "Ticket Cost"). Values flow through to booking totals, sales charges, PDF invoice, and MIS Excel export.
* **Implementation**:
  - **DB Migration**: `2026-07-22-000001_AddCustomChargesToShipmentItemsAndSalesCharges.php` — added `custom_charges TEXT NULL` to both `shipment_items` and `sales_charges` tables.
  - **Models**: Added `custom_charges` to `$allowedFields` in `ShipmentItemModel` and `SalesChargeModel`.
  - **Frontend (booking_form.php)**: Added `+ Add Charge` button in item drawer (offcanvas) that appends label+value rows via `addCustomItemChargeRow()`. Added `+ Add Surcharge` button in global surcharges section via `addCustomGlobalSurchargeRow()`. Custom global surcharge inputs carry class `calc-surcharge` so they're included in `calcTotals()` live total preview. Custom charges serialize into `items_json` via `renderGrid()`. Edit page correctly restores both item-level and global custom charges from DB.
  - **Backend (BookingService.php)**: `processShipments()` and `updateBooking()` parse `custom_charges` from `items_json` — stores as JSON string. `extractCustomGlobalSurcharges()` reads `custom_global_surcharge_labels[]`/`values[]` POST arrays and encodes to JSON. `calculateTotalAmount()` decodes and sums custom_charges in the sales total.
  - **InvoiceService.php**: `aggregateCharges()` decodes per-item custom_charges, groups by label → `customTotals`. `resolveActiveCharges()` accepts `$customTotals` parameter, creates `custom_*` keys for dynamic PDF column resolution. `buildShipmentRows()` decodes custom_charges per row, sums into `$customChargesSum` added to `$taxable`, and stores per-label values in `$itemCustomMap`.
  - **invoice.php**: `OTHER CHG` column now includes `array_sum($row['itemCustomMap'])` so column totals correctly sum to `TOTAL Amt.`
  - **View Booking (view_booking.php)**: Updated `view_booking.php` to decode item-level `custom_charges` and display charge badges (`Charge 1: ₹...`) under item details, include them in item "Total Chgs", and dynamically include global custom surcharges in the Financial Summary list, Subtotal, and Charges Breakdown.
* **Files Modified**: `app/Database/Migrations/2026-07-22-000001_AddCustomChargesToShipmentItemsAndSalesCharges.php`, `app/Models/ShipmentItemModel.php`, `app/Models/SalesChargeModel.php`, `app/Views/logistics/booking_form.php`, `app/Views/logistics/view_booking.php`, `app/Services/BookingService.php`, `app/Services/InvoiceService.php`, `app/Controllers/Logistics.php`, `app/Views/pdfs/invoice.php`
* **QA**: Subagent Verification Loop run — 1 major issue found (invoice OTHER CHG column missing custom charges) and fixed. View Booking UI updated to display custom charge badges and breakdown. All existing functionality verified unaffected.

---

### [CHG-014] Comprehensive Multi-Agent QA Audit & Automated Verification
* **Status**: Completed
* **Priority**: Critical (Pre-Release Assurance)
* **Requirement**: Conduct a comprehensive software QA audit across all modules, forms, button columns, masters, invoices, role permissions (`admin`, `staff`, `tracking`), database CRUD integrity, TCPDF layout stability, and exception handling safeguards.
* **Implementation**:
  - Executed CLI route validation (`php spark routes`) and database migration check (`php spark migrate:status`).
  - Audited RBAC permissions in `AuthFilter.php`, `AuthController.php`, and `AdminController.php` (login feedback, branch-level row isolation, permission toggles).
  - Verified tenant-scoped CRUD operations across all Master models (`CustomerModel`, `TransporterModel`, `DriverModel`, `AirlineModel`, `BankAccountModel`, `LookupValueModel`).
  - Audited DataTables SSP fast counts, button column actions (View, Edit, Delete, PDF Invoice, Tracking Drawer), and manual chargeable weight override logging to `audit_logs`.
  - Verified `InvoiceService.php` charge aggregation, GST mutual exclusion, amount-in-words Indian numbering system, and `invoice.php` Option C layout stability.
  - Verified `TrackingController` POD file upload handling, tracking history rollback on event deletion, and public API (`GET /api/track/{awb_no}`).
  - Fixed Playwright test script locator ambiguity in `testsprite_tests/TC001_...py` (`input[name="username"]`, `input[name="password"]`).
* **Files Modified**: `testsprite_tests/TC001_Sign_in_and_reach_the_logistics_workspace.py`, `docs/known-issues.md`, `docs/changes.md`, `docs/testing.md`

---

### [CHG-013] Production CRUD, Database Operations, AuthFilter Exemption & CSRF Token Fixes
* **Status**: Completed
* **Priority**: Critical
* **Requirement**: Resolve company creation and database non-updating issue on production, fix AuthFilter company requirement block, fix CSRF modal form submission failures, auto-heal admin privileges, and make company CRUD operations resilient across database column schema variations (`name` vs `company_name`, `gstin` vs `gst_no`).
* **Implementation**:
  - **Routes Method Matching (`Routes.php`)**: Updated company management routes (`logistics/setCompany`, `logistics/createCompany`, `logistics/deleteCompany`) to `$routes->match(['get', 'post'], ...)` instead of strict POST-only, preventing form submissions from failing when web servers (e.g. Apache/Hostinger URL rewrites) convert POST requests to GET redirects.
  - **AuthFilter Route Exemption (`AuthFilter.php`)**: Added `logistics/createCompany` and `logistics/deleteCompany` to the `$companyExempt` array in `AuthFilter.php`. Previously, when no company was selected (`selected_company_id` is null), submitting the "+ Add Company" modal triggered `AuthFilter`'s company requirement check (`strpos($cleanUri, 'logistics') === 0`), causing an immediate redirect back to `/company-selection` BEFORE `createCompany()` was ever executed!
  - **CSRF Token Fix (`.env`)**: Updated `security.tokenRandomize = false` in `.env` to ensure CSRF token names remain consistent across modal form submissions, preventing 403 silent rejects.
  - **Admin Auto-Healing (`UserModel.php`)**: Enhanced `attemptLogin()` with `ensureDefaultAdmin()` to automatically seed or repair admin account credentials (`password`, `role`, `is_active`, `can_create`, `can_edit`, `can_delete`, `branch_id`).
  - **Database Schema Resiliency (`Logistics.php`, `CompanyController.php`, `CompanyModel.php`)**: Dynamically detect database table fields (`$db->getFieldNames('companies')`) in `createCompany()`, `setCompany()`, `deleteCompany()`, and `updateSettings()`. Added support for field aliases (`name`/`company_name`, `gstin`/`gst_no`, `pan`/`pan_no`, `signature_path`/`signature_image`).
  - **Error Handling**: Wrapped company creation and deletion inside `try/catch (\Throwable $e)` blocks to surface explicit error feedback via SweetAlert alerts.
* **Files Modified**: `.env`, `app/Config/Routes.php`, `app/Filters/AuthFilter.php`, `app/Models/UserModel.php`, `app/Models/CompanyModel.php`, `app/Controllers/Logistics.php`, `app/Controllers/CompanyController.php`, `docs/known-issues.md`, `docs/changes.md`

---

### [CHG-001] Select2 Reversion to Standard Dropdowns
* **Status**: Completed
* **Priority**: High
* **Requirement**: Revert heavy searchable combo boxes to simple standard HTML `<select>` tags to match legacy software speed.
* **Implementation**: Stripped Select2 CSS/JS from `booking_form.php` and `layout.php`. Applied `form-select form-select-sm`.
* **Files Modified**: `app/Views/logistics/booking_form.php`, `app/Views/layout.php`

---

### [CHG-002] Sidebar Accordion Menu Reorganization
* **Status**: Completed
* **Priority**: Medium
* **Requirement**: Prevent dropdown menu overlays from covering navigation links. Move master registries to a dedicated menu.
* **Implementation**: Replaced Bootstrap Dropdown with Collapse (Accordion) components. Created dedicated **Masters** accordion bucket containing Customer, Transporter, Driver, Airline, and Lookup master links.
* **Files Modified**: `app/Views/layout.php`

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

---

## 2. Pending Phase 2 Out-of-Scope Additions (Client Change Requests)

The following 6 change requests have been formally cataloged into the official Phase 2 change tracking matrix ([Google Spreadsheet](https://docs.google.com/spreadsheets/d/1W9Zi4OHg0hqVbSTgccItXIKHrBRXCDeXwyOteGqNaXk/edit?gid=0#gid=0)):

### [CHG-P2-001] Customizable Email Send Feature
* **Module**: Booking List & Invoices Page
* **Status**: Phase 2 Requirement
* **How It Works**: Adds a 'Send Email' button in the table grid. Clicking it opens a window where staff can select which details (like AWB No, Charges, Status, or Customer info) to include in the email and send it directly to the client.
* **Why It Helps**: Saves time by removing manual email drafting, automates client updates, and lets staff customize what info is shared.

### [CHG-P2-002] Auto-Fill City & State using Pincode (Google Maps API)
* **Module**: Booking Entry & Customer Master
* **Status**: Phase 2 Requirement
* **How It Works**: When staff enters a 6-digit postal pincode in any address form, the system uses Google Maps API to automatically detect and fill in the correct City and State without manual typing.
* **Why It Helps**: Prevents spelling errors in location names, standardizes address data, and speeds up booking entry.

### [CHG-P2-003] Admin Grid Column Selector
* **Module**: Admin Settings / Table Views
* **Status**: Phase 2 Requirement
* **How It Works**: Adds a setting in the Admin Panel (similar to WordPress) where admins can choose which table columns to show or hide, change column order, and save customized view presets for different staff roles.
* **Why It Helps**: Gives admins full control to customize data views for different team members without needing a web developer.

### [CHG-P2-004] Smart Customer Name vs AWB Display on PDF Invoices *(From 1.jpeg)*
* **Module**: PDF Invoice & Booking View
* **Status**: Phase 2 Requirement
* **How It Works**: Header: If all packages in a booking belong to one customer, shows Customer Name in the 'TO:' box; if packages belong to different customers, shows AWB Number instead. Item Table: Adds a 'Customer Name' column next to Date if packages belong to different customers; stays hidden if all packages belong to the same customer.
* **Why It Helps**: Ensures invoices are accurate, legally clear, and easy to read for both single-customer and multi-customer bookings.

### [CHG-P2-005] Save Consolidated Invoices & Export 28-Column MIS Excel *(From 2.jpeg)*
* **Module**: All Invoices (Consolidated Billing)
* **Status**: Phase 2 Requirement
* **How It Works**: 1. Saved Invoices: Whenever a consolidated bill is generated, it is saved in the system under that company. Staff can view past bills in the 'All Invoices' tab and re-download the PDF anytime. 2. Export MIS Button: Adds an 'Export MIS' button that downloads an Excel file containing 28 exact columns requested by the client (Date, LR No, Rate, Freight, Fuel Surcharge, Ticket Costs, Consignor, Consignee, etc.).
* **Why It Helps**: Maintains a complete audit history of past consolidated bills and generates detailed MIS reports for finance and management in one click.

### [CHG-P2-006] 10 Dynamic Custom Charge Fields & MIS Excel Integration *(From 3.jpeg)*
* **Module**: Shipment Item Form & MIS Export
* **Status**: ✅ **Implemented** (Phase 2 requirement delivered in Phase 1)
* **How It Works**: Replaced the original "10 fixed fields" spec with a `+ Add Field` approach. Each added field has an editable label (heading) and numeric value. Values flow through to booking totals, PDF invoice (OTHER CHG column), and sales charges. Global booking-level surcharges also support dynamic label+value pairs via `+ Add Surcharge` button.
* **Implementation Reference**: See [CHG-012] above.
