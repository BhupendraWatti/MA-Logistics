# CURRENT SOFTWARE ANALYSIS: MA Logistics

## 1. Project Overview
- **Framework Version**: CodeIgniter 4 (PHP)
- **PHP Version**: PHP 7.4+ or 8.x (Standard for CI4)
- **Frontend Stack**: HTML, CSS, JavaScript (Vanilla/jQuery), Bootstrap (assumed based on `.form-control` classes)
- **Architectural Pattern**: MVC (Model-View-Controller) with an emerging Service Layer (`BookingService.php`).
- **Major Dependencies**: TCPDF for PDF Generation, standard CI4 packages.
- **Folder Structure**: Standard CI4 (`app/Controllers`, `app/Models`, `app/Views`, `app/Services`, `app/Database/Migrations`).

## 2. Existing Modules
### Logistics/Booking Module
- **Purpose**: Core application logic for creating and managing logistics shipments.
- **Completion Status**: Functional MVP but lacking advanced tracking, master data relations, and dynamic financial calculations.
- **Implemented Features**: AWB creation, Shipment item dynamic rows, Sales charges capture, basic Company-based data isolation, PDF generation, CSV Export.
- **Missing Features**: Master data lookups (Customers, Airlines), GST calculations (only hardcoded in PDF), POD workflow, Digital signatures.
- **Technical Concerns**: `Logistics.php` controller is becoming a "fat controller" (700+ lines). Heavy reliance on array manipulation instead of entity objects.

### Company Module
- **Purpose**: Multi-tenant isolation context.
- **Completion Status**: Basic implementation.
- **Implemented Features**: Session-based isolation (`company_id`), creation and deletion by Admin.
- **Technical Concerns**: No configuration capabilities (e.g., Company GST, Logo, specific settings).

### Authentication/User Module
- **Purpose**: Login and role-based access.
- **Completion Status**: Basic implementation.
- **Implemented Features**: Authentication, Role checking (Admin vs. User), basic permissions (`can_create`, `can_edit`, `can_delete`).
- **Technical Concerns**: Permissions logic is scattered (Session checks duplicated across methods).

## 3. Database Analysis
### Existing Tables
- `users`: Basic auth and role storage.
- `companies`: Simple multi-tenant structure.
- `bookings`: Core AWB entity.
- `shipment_items`: Child records of bookings (boxes, dimensions, weights).
- `sales_charges`: Child records of bookings (financial fees).
- `audit_logs`: Tracks `chargeable_weight` overrides.

### Normalization & Gaps
- **Missing Relationships**: Lack of Master tables. `customer_name`, `driver_name`, `airlines`, `origin`, `destination` are free-text fields. This will cause data inconsistency and prevents auto-fill functionality.
- **Missing Tables**: `customers`, `airlines`, `transporters`, `payment_types`, `tracking_status_logs`.
- **Nullable Issues**: Heavy reliance on nullable or default string fields instead of foreign keys.
- **Financial Tracking**: `sales_charges` lacks GST breakdown columns (`cgst_amount`, `sgst_amount`, `igst_amount`).

## 4. Booking/AWB Workflow Analysis
- **Current Workflow**: Draft -> Create -> Add Shipments/Sales -> PDF/Export.
- **Missing Workflow Parts**: Dynamic tracking steps, Proof of Delivery (POD) attachments, driver dispatch states, invoicing lifecycle.
- **Validation Issues**: Basic backend validation exists for `ShipmentItemModel`, but lacks strict financial validations and master data integrity checks.

## 5. Shipment Grid Analysis
- **Dynamic Rows**: Handled via JavaScript on the frontend and processed as an array in `BookingService::processShipments()`.
- **Calculations**: Volumetric weight `(L*W*H)/6000` is hardcoded. 
- **Chargeable Weight**: Calculates `max(actual_weight, volumetric_weight)`, with a floor of 45KG. This logic is rigid.
- **Scalability Concerns**: Deleting grid items relies on `array_diff` against submitted IDs. Can be prone to race conditions or UI desync issues. No pagination/lazy loading on large grid exports.

## 6. Financial Logic Analysis
- **Rate Calculation**: Highly static. `BookingService::calculateTotalAmount()` blindly sums up 20+ columns.
- **GST Handling**: Non-existent in the database or service calculation. Only visually hardcoded as `9% CGST / 9% SGST` in the `exportPdf` method.
- **Chargeable Weight Logic**: Handled implicitly, but financial decoupling from weight changes is risky.

## 7. Tracking System Analysis
- **AWB / Docket Tracking**: Completely missing structured tracking. Relies on a single `status` string column in the `bookings` table.
- **POD Workflow**: Not implemented. No file upload logic or digital signature capture exists.

## 8. Master Data Analysis
- **Current State**: Non-existent.
- **Impact**: Without Master Modules (Customer, Transporter, Airline, Payment Type), auto-filling dropdowns (as requested in the Change Requests) is impossible. The system currently accepts free text for all these entities.

## 9. Security & Multi-Company Analysis
- **Authentication**: Native CI4 session-based auth.
- **Company Isolation**: Implemented via session `selected_company_id`. Applied in `Logistics.php` exports and views.
- **Risks**: `BookingService` does not actively verify if the `company_id` passed in `postData` matches the logged-in user's authorized company. A malicious POST could potentially assign bookings to other companies.
- **Data Leaks**: `AdminController` allows company deletion which triggers a cascade delete, potentially destroying another tenant's data if ID validation isn't strict.

## 10. UI/UX Analysis
- **Current State**: Likely relying on standard Bootstrap grids and forms.
- **Concerns**: A form capturing Bookings + dynamic shipment rows + 20 financial sales charge inputs on one page will cause severe UI clutter and cognitive load. Totals are not dynamically aggregating from the shipment grid to the main view smoothly.

## 11. Technical Debt & Risks
- **Fat Controllers**: `Logistics.php` handles too much logic (PDF generation, CSV export formatting, number-to-words conversion).
- **Hardcoded Logic**: Volumetric divisor (6000), minimum weight (45), and GST percentages are hardcoded.
- **Duplicated Code**: PDF generation and CSV export loop through the same calculation logic instead of relying on a shared reporting service.
- **Transaction Safety**: `BookingService` wraps updates in transactions, which is good, but exception handling passes raw system messages to the user UI (`$e->getMessage() . ' in ' . $e->getFile()`).

## 12. Change Request Mapping
- **Manual Chargeable Weight**: Partially implemented (Audit logs track overrides, but UI/logic needs refinement).
- **Editable Volumetric Logic**: Missing (Currently hardcoded /6000).
- **GST Handling**: Missing natively.
- **Master Modules**: Missing entirely.
- **Customer/Shipment Auto-fill**: Blocked due to missing Master modules.
- **Payment Types**: Missing.
- **Digital Signature**: Missing.
- **Dynamic Tracking/POD**: Missing.
- **Company GST Configuration**: Missing.

## 13. Recommended Refactoring Plan
1. **Database Foundation**: 
   - Create migrations for Master Data (`customers`, `airlines`, `transporters`, `settings`).
   - Alter `sales_charges` to include explicit GST columns.
   - Alter `bookings` and `shipment_items` to use Foreign Keys to Master Data.
2. **Service Layer Extraction**:
   - Move PDF generation (`exportPdf`) out of the Controller into a `PdfReportService`.
   - Move Export logic out of the Controller into an `ExportService`.
3. **Configuration Extraction**:
   - Move volumetric divisors, minimum weights, and GST rates into a `settings` table or configurable config file, editable per Company.
4. **API / Auto-fill Implementation**:
   - Build RESTful endpoints to serve Master Data to the frontend for AJAX-based dropdowns and auto-fill.

## 14. Clarification Questions
### Business Questions
1. Do different companies have different volumetric divisors (e.g., 5000 vs 6000) and minimum weights (45 vs 100), or is it standardized across the system?
2. Does GST apply differently based on the Origin/Destination states (IGST vs CGST/SGST)? 
3. Should tracking updates be automatic (based on carrier integrations) or purely manual entry?
4. What are the specific statuses required for the dynamic tracking workflow?

### Technical Questions
1. Should digital signatures be captured as drawn images on a canvas (frontend) or uploaded as images?
2. Will Master Data be imported from an external system initially, or entered manually from scratch?
3. How should existing historical data be migrated when we introduce Master Data foreign keys?

---

### Major Risks
- Implementing GST and Rate calculations directly affects invoicing. Testing these financial algorithms against edge cases is critical.
- Modifying the dynamic shipment grid to support auto-fill and manual overrides will require significant frontend JavaScript refactoring.

### Recommended Next Steps
1. Client reviews and answers the Clarification Questions.
2. Draft Database Migrations for Master Data and Financial enhancements.
3. Review and approve the refactoring of the `Logistics` controller to stabilize the foundation.
