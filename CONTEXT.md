# MA Logistics ERP - Session Context & Architecture Log

## Overview
This document serves as the continuing architectural context for the MA Logistics ERP application. It contains a detailed log of all frontend design changes, logic tweaks, bug fixes, and database alterations made during the current development session. Use this file to inform future agents of the exact state of the UI and backend logic without needing to analyze the entire codebase from scratch.

---

## 1. Frontend Refactoring & Design Fixes

### Standardized Form Dropdowns
* **Files Affected**: `app/Views/logistics/booking_form.php`, `app/Views/layout.php`
* **Changes Made**:
  * Completely stripped out the Select2 JavaScript and CSS library integration.
  * Reverted all data-heavy dropdowns (Origin, Destination, Transporters, Drivers, Airlines, Payment Type, Mode of Transport, Consignor) to use clean, standard Bootstrap `<select>` tags (`form-select form-select-sm`).
  * **Rationale**: The user explicitly requested standard HTML dropdowns to mirror the simple, clean UX of their legacy software, rather than searchable "combo boxes".

### Sidebar Reorganization & Accordion UI
* **Files Affected**: `app/Views/layout.php`
* **Changes Made**:
  * Fixed an issue where absolute-positioned dropdown menus in the sidebar were floating *over* and hiding other navigation links (like Tracking and Reports).
  * Replaced the Bootstrap "Dropdown" component with the Bootstrap "Collapse" (Accordion) component. Now, opening a sidebar menu gracefully pushes the content below it downwards.
  * Reorganized the sidebar architecture to include a dedicated **"Masters"** accordion.
  * Moved all master data links (Customer Master, Transporters, Drivers, Airlines, Lookup Values) out of the administrative "Settings" dropdown and into the new "Masters" bucket, mimicking the legacy software's mega-menu structure for future scalability.

### Fluid Responsive Layout (Ultrawide Desktop Support)
* **Files Affected**: `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/search_results.php`, `app/Views/logistics/view_booking.php`, `app/Views/company/settings.php`
* **Changes Made**:
  * Audited the layout wrappers and replaced hardcoded, fixed-width `<div class="container">` wrappers with `<div class="container-fluid">`.
  * **Rationale**: The fixed container locked the UI to a maximum width (e.g., 1320px), causing massive empty white margins on 2K/4K/Ultrawide monitors. The ERP now seamlessly utilizes 100% of the horizontal screen space on all devices.

---

## 2. Advanced UX: Unsaved Changes & Navigation Interception

### SweetAlert2 Navigation Trap
* **Files Affected**: `app/Views/logistics/booking_form.php`
* **Changes Made**:
  * Created a global `isDirty` listener (`$('input, select, textarea').on('change input')`) to track if the user has modified the logistics form.
  * **Internal Links**: Intercepted all `<a>` clicks to suppress the ugly native browser `beforeunload` dialog. Instead, attempting to click the sidebar or leave the page triggers a highly styled SweetAlert2 confirmation box.
  * **Browser Back Button Trap**: Implemented an advanced `history.pushState` mechanism to trap the browser's native Back arrow button (`popstate` event). If the form is dirty, clicking Back triggers the SweetAlert2 popup instead of immediately changing the page.
  * **Fallback**: Preserved the native `beforeunload` strictly as an un-bypassable fallback for full tab closures or hard page refreshes (which browsers strictly forbid custom popups for).

---

## 3. Database & Backend Data Flow Fixes

### The "Hidden Data" Bug (is_active Filter)
* **Files Affected**: `app/Models/CustomerModel.php`, `app/Models/TransporterModel.php`, `app/Models/DriverModel.php`, `app/Models/AirlineModel.php`, `app/Models/LookupValueModel.php`
* **Changes Made**:
  * **Diagnosis**: User reported that master data created in the dashboard was not appearing in the logistics booking dropdowns. Discovered that database records were defaulting to `is_active = 0` or `NULL`, causing the strict backend filters to hide the data.
  * **Fix**: Since the UI does not contain toggle switches to activate/deactivate records, the `->where('is_active', 1)` filter was systematically removed from all Master models. All created data now immediately populates in the logistics dropdowns.

### Airlines Dropdown Variable Typo
* **Files Affected**: `app/Views/logistics/booking_form.php`
* **Changes Made**:
  * Fixed a syntax typo in the frontend template where the Airlines loop was erroneously searching for data inside `$lookups['airlines']` instead of the root `$airlines` variable passed by the controller.

### Removal of Unused "Sort Order" Feature
* **Files Affected**: `app/Views/masters/lookups.php`, `app/Models/LookupValueModel.php`, `app/Controllers/MasterController.php`, Database Schema
* **Changes Made**:
  * Stripped the "Sort Order" input field and table column from the Lookup Values UI.
  * Removed backend insertion logic and removed it from the model's `$allowedFields`.
  * Executed a direct SQL script (`ALTER TABLE lookup_values DROP COLUMN sort_order;`) to permanently drop the column from the database architecture. Lookup values now default to sorting alphabetically by value.

---

## 4. Bug Fixes

### The Logout Crash (Undefined variable $success)
* **Files Affected**: `app/Views/layout.php`
* **Changes Made**:
  * **Diagnosis**: Attempting to log out threw an `ErrorException: Undefined variable $success`.
  * **Fix**: The flashdata variables (`$success`, `$error`, `$info`) used by the SweetAlert logic were incorrectly scoped inside the `if (session()->get('user_id'))` block. When logging out, the session was destroyed, bypassing the block and leaving the variables undefined for the guest login layout. Extracted these variables to the top of the `<body>` so they execute safely regardless of authentication state.

---

## 5. Recent Feature Additions & System Expansions

### Database Architecture Updates
* **Files Affected**: Migrations, `app/Models/SystemSettingsModel.php`, `app/Models/CustomerModel.php`
* **Changes Made**:
  * Created `system_settings` table to manage global ERP configurations.
  * Created `location_master` table to handle location-based data.
  * Expanded `customer_master` extensively by adding missing fields such as GST State and comprehensive billing details.
  * Added location-specific fields to the generic lookup values.
  * Cleaned up redundant or unused migration files related to shipment item modifications and sales charges.

### Frontend Modularization & JavaScript Utilities
* **Files Affected**: `public/js/erp-utils.js`, `app/Views/masters/_customer_form_fields.php`
* **Changes Made**:
  * Extracted common client-side logic into a dedicated `erp-utils.js` file for global reusability.
  * Modularized the Customer form UI by pulling the massive form structure out of `customers.php` and `customer_form.php` into a reusable partial: `_customer_form_fields.php`.

### Invoicing & Company Settings Enhancements
* **Files Affected**: `app/Controllers/CompanyController.php`, `app/Controllers/MasterController.php`, `app/Views/masters/company_settings.php`, `app/Views/pdfs/invoice.php`
* **Changes Made**:
  * Completely revamped the Company Settings UI and backend controllers to support extended corporate data management.
  * Upgraded the PDF invoice generator (`invoice.php`) to accommodate precise layout requirements and new company/customer fields.
  * Implemented support for uploading and storing digital signatures (`public/uploads/signatures/`) for invoice authorization.

### Logistics Views Refinement
* **Files Affected**: `app/Views/logistics/booking_form.php`, `app/Views/logistics/view_booking.php`
* **Changes Made**:
  * Applied major functional and visual updates to both the booking entry form and the booking viewer to incorporate the newly expanded customer data and system settings.
