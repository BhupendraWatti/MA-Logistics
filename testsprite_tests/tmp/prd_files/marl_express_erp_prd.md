# Product Requirement Document (PRD): MARL EXPRESS ERP

**Document Classification:** Product Requirement Document (PRD)  
**Target Audience:** Engineering Team, QA Engineers, DevOps, Product Owners, Automated Test Generators  

---

## ## Problem Statement

Logistics booking operators, branch managers, and administrators at MARL Express need a highly performant, high-volume, and reliable freight management system to generate airway bills (AWBs), manage master data, and track cargo operations. 

The existing legacy operational workflows suffered from severe stability and performance limitations:
1. **Silent Data Loss & Variable Truncation:** Large shipment bookings with numerous cargo items generated thousands of hidden HTML input fields, silently exceeding the PHP server's `max_input_vars` security limits and causing accidental data loss during saves.
2. **Brittle Session Caching & Shutdown Crashes:** Hostinger staging servers using LiteSpeed cache and MySQL strict mode crashed during standard database session writes on PHP shutdown. This silently converted successful `200 OK` AJAX saves into `500 Internal Server Error` statuses, breaking frontend visual updates.
3. **Weak Operational Tracking:** Tracking was limited to a single static string, lacking detailed location history log trails, chronological status updates, and digital Proof of Delivery (POD) attachments.
4. **HTML Quoting Crashes:** Storing raw JSON strings containing single quotes (e.g., in remarks or locations like `"driver's copy"`) inside inline HTML attributes caused rendering crashes that broke the user interface.

---

## ## Solution

The **MARL EXPRESS ERP** is a robust, highly dense, operational-first enterprise resource planning system designed to streamline dockets, freight, and cargo tracking with absolute ACID transaction safety. 

Key architectural components of the solution include:
1. **Atomic Master-Detail Transaction Pattern:** Bundles AWB master data and shipment items into a single serialized JSON payload, bypassing `max_input_vars` limitations entirely, eliminating DOM thrashing, and ensuring transactions either commit completely or roll back atomically.
2. **Early Session Release (`session_write_close`):** Explicitly saves and closes session write locks inside AJAX controllers right before returning JSON responses, eliminating database write blockages during the PHP shutdown sequence and ensuring clean `200 OK` responses.
3. **Chronological Tracking & POD offcanvas Drawer:** Provides a side-drawer UI that queries the live database (using client-side cache-busting to bypass aggressive LiteSpeed caches), lists cargo event logs, allows uploading POD signatures/files, and supports hard-deletes.
4. **Quoting-Safe Global Event Indexing:** Stores loaded tracking arrays in a global JavaScript window state and maps click events by index, permanently preventing special characters or quotes from crashing UI rendering.

---

## ## User Stories

1. As a booking operator, I want to create a new shipment booking with consignor details, origin, destination, transport mode, and payment type, so that I can generate a unique airway bill (AWB).
2. As a booking operator, I want to add multiple freight items (pieces, dimensions, and actual weight) dynamically in a spreadsheet-like grid, so that I can enter volumetric cargos rapidly.
3. As a booking operator, I want the system to calculate the volumetric weight automatically using `(Length * Width * Height) / 6000` and set the chargeable weight as the maximum of actual and volumetric weight, so that billing is computed accurately.
4. As an administrator, I want to manage a centralized Customer Master (with states, GST, and billing options), so that operators do not have to manually type customer details during booking.
5. As a booking operator, I want the system to auto-populate customer details, transporters, and drivers from master dropdowns, so that I can minimize keystrokes and speed up data entry.
6. As a logistics operator, I want to click a tracking icon next to any booking on the dashboard, so that I can view its chronological transit history in an offcanvas drawer.
7. As a logistics operator, I want to add a manual tracking log event (specifying current location, transit status, event date, time, and remarks), so that customer support can see where the cargo is.
8. As a logistics operator, I want to upload a Proof of Delivery (POD) signature or image file when changing a shipment's status to "Delivered", so that we have digital confirmation of receipt.
9. As a branch manager, I want to edit a previously recorded tracking update, so that I can correct manual typographical errors in location names or timestamps.
10. As a branch manager, I want to delete a manual tracking record permanently, so that accidental duplicates are removed from the cargo history immediately.
11. As a logistics administrator, I want to delete a booking record along with all its shipment items and sales charges atomically, so that aborted shipments do not leave orphaned financial records.
12. As a branch manager, I want the main bookings grid on the Manage Bookings page to refresh automatically when I add or delete a tracking status inside the drawer, so that the screen is always synchronized.
13. As an operator, I want the system to trap navigation attempts (using Back buttons or clicking sidebar links) when a form is partially filled and display a styled SweetAlert confirmation, so that I do not accidentally lose unsaved changes.
14. As an administrator, I want to switch my active company profile from a dropdown menu, so that I can manage logistics data isolated to different operational branches or corporate entities.
15. As a system administrator, I want to run a database inspector utility, so that I can verify active session states, bookings records, and tracking history structures on staging without logging into database consoles.

---

## ## Implementation Decisions

### Modules & Services Architecture
* **Booking Entry Module:** Employs the Monolithic mon-detail transaction handler. Frontend data is serialized into a single HTML field `items_json` and processed on the backend via a dedicated, transaction-safe `BookingService`.
* **Manual Courier Tracking Module:** Consists of a responsive side-drawer (`pod_tracking_drawer.php`) interacting with a dedicated JSON API (`TrackingController.php`).
* **Master Data Module (`MasterController`):** Manages server-side pagination, searching, and filtering for Customers, Transporters, Drivers, Airlines, and Lookup values via AJAX.
* **Authentication & tenant Context (`AuthFilter`):** Ensures that all logistics dockets and master listings are strictly partitioned by the operator's session-stored `selected_company_id`.

### Technical Clarifications & Workarounds
* **Native Session Write Lock Release:** Calls `session_write_close()` inside JSON API endpoints (`ajaxDatatable`, `getHistory`, `saveUpdate`, `deleteUpdate`, `delete`) right before sending responses, bypassing framework-level shutdown deadlocks on Hostinger MySQL.
* **Cache-Busting on GET APIs:** Re-loads AJAX GET requests for tracking histories by enforcing `cache: false` in jQuery and appending `_={timestamp}` to the URL, guaranteeing fresh database queries on Hostinger LiteSpeed.
* **Deletion Strategy:** Explicitly disables soft-deletes in `TrackingHistoryModel.php` (`$useSoftDeletes = false`) and uses `$this->trackingModel->delete($id, true)` in the controller. This forces direct physical hard-deletes, bypassing MySQL default timestamp configuration quirks on the staging database.
* **Quoting-Safe Click Handler:** The HTML action column uses an index reference (`onclick="editTrackingByIndex(${index})"`) bound to a global `window.trackingHistoryData` array. This eliminates quoting errors when remarks contain character values like apostrophes.

### Database Schema Decisions
* **`bookings` table:** Stores core AWB headers, status, consignor/consignee, origin, and destination.
* **`shipment_items` table:** Child table storing pieces, dimensions, volumetric calculations, and foreign key relations back to bookings.
* **`sales_charges` table:** Child table storing financial dockets, handling fees, and freight rates.
* **`tracking_history` table:** Chronological logs containing:
  - `id` (INT unsigned primary key, auto-increment)
  - `booking_id` (INT unsigned foreign key with cascade on delete)
  - `awb_no` (VARCHAR)
  - `current_location` (VARCHAR)
  - `status` (VARCHAR)
  - `event_date` (DATE)
  - `event_time` (TIME)
  - `remarks` (TEXT, nullable)
  - `proof_image` (VARCHAR, nullable)
  - `created_at` / `updated_at` (DATETIME, nullable)
* **`ci_sessions` table:** Recreated with a proper `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` datatype to ensure compatibility with strict MySQL configurations.

---

## ## Testing Decisions

### Test Characteristics
A robust test suite must **only test external operational behavior** rather than internal helper methods or private states. This ensures that refactoring controllers or optimization changes do not break test assertions.

### Modules Targeted for Testing
1. **Transaction Service (`BookingService`):**
   - Assert that throwing a validation exception on a shipment item rolls back the entire transaction, leaving no partial booking records.
   - Assert that an AWB with 100+ shipment items successfully saves, proving the JSON payload bypasses the PHP variable truncation limits.
2. **Manual Tracking API (`TrackingController`):**
   - Assert that adding a tracking history log returns a status `200 OK` JSON and updates the booking status in the `bookings` table.
   - Assert that `session_write_close()` is successfully evaluated without corrupting active user sessions.
3. **Database Constraints:**
   - Assert that physical hard-deletes successfully cascade to remove child tracking history rows when a booking is deleted.

---

## ## Out of Scope
* Automatic third-party API tracking integrations (e.g., DHL, FedEx, or Bluedart APIs).
* Live GPS location tracking or real-time map integrations for drivers.
* SMS or Email automated tracking notifications to consignors or consignees.
* Multi-currency calculations (standardized strictly on Indian Rupee `₹`).

---

## ## Further Notes
* **Data Density:** Layouts must always maintain Bootstrap `.form-control-sm` and `.form-select-sm` sizing and compact borders to maximize grid readability for keyboard-heavy data entry operators.
* **Security:** All deletions require blocking confirmation boxes powered by SweetAlert2, and all backend queries are bound via CodeIgniter Query Builder to prevent SQL injection vulnerabilities.
