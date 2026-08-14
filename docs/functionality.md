# M.A. Logistics ERP — Functional Documentation

This document provides a comprehensive functional description of all modules, business workflows, mathematical calculations, form behaviors, and dropdown logic in the M.A. Logistics ERP system.

## Current Backend Behavior Additions

* **Date/category customer rates**: When a shipment item is saved with a blank or zero item rate, `BookingService` looks up `customer_rates` by active company, item customer, item/booking material category, and booking date. The matched rate is copied into `shipment_items.rate`, so later Customer Master or rate-table changes do not mutate old saved bills.
* **Item metadata persistence**: `shipment_items.payment_type` and `shipment_items.material_category` are persisted when provided in `items_json`; if absent, the booking-level payment type and material category are stored as fallbacks.
* **Zero weight allowance with AWB sanity guard**: Item actual weight may be zero. If a booking-level `total_weight` is declared, the backend rejects saves where summed item actual weight is below that master AWB weight.
* **Invoice master-data fallback**: PDF invoice generation prefers Customer Master address, GSTIN, and PAN details when available, with shipment `bill_to`/`consignee` as fallback.
* **Optional LR/docket clubbing**: Invoice row building supports grouping rows with the same LR/docket number when `club_by_lr=1` or consolidated billing posts `billing_mode=docket`; default invoice output remains per shipment item.
* **Remarks compatibility**: New `bookings.remarks` and legacy `bookings.narration` both print in the invoice remarks block.
* **Financial-year invoice numbering**: Consolidated PDF generation finalizes a company-scoped invoice number such as `MA-26-27/001`, persists it to selected shipment rows, and reuses that number on later reprints instead of allocating duplicates.
* **Edit-mode item autosave**: In booking edit mode, saving or deleting a shipment item immediately posts the updated booking form via AJAX to `Logistics::update()`. Staff no longer need to click Update Booking after Save Item for item-level changes to persist.
* **Invoice charge overflow rule**: The default invoice layout shows at most the first four active item-specific charge columns in the order Delivery, Docket, Pickup, Fuel, then later charges/custom charges. Any remaining active charges are summed into the Other Charges column so totals still match the taxable row amount.
* **Runtime uniqueness feedback**: AWB uniqueness is checked while the AWB field is edited. Docket uniqueness is checked while editing the item drawer and again before Save Item accepts the row, preventing late save-time validation from discarding filled form data.
* **Default invoice bank selection**: The All Invoices generator preselects the company default bank account when one is configured; backend invoice generation still falls back to the default bank, then first bank, then legacy company bank fields.

---

## 1. Logistics & Booking Module

### Purpose
Core operational engine for creating, viewing, editing, tracking, and invoicing air and surface freight consignment manifests.

### Key Workflows & Features
1. **Consignment Entry (`booking_form.php`)**:
   - **Header Details**: AWB/Docket number, booking date, branch, customer, consignor, consignee, origin, destination, payment type, transport mode, carrier, driver.
   - **Item Manifest Grid**: Dynamic drawer modal for adding individual package rows (pieces, dimensions L×W×H, actual weight, volumetric divisor).
   - **Smart Carry-Forward Mechanic**: When saving a row and opening the drawer for the next package, Customer, Bill To, Consignee, Docket No, Part No, and Invoice Date carry forward automatically to accelerate multi-package entry.
2. **Chargeable Weight Resolution**:
   - Computes Volumetric Weight $= \frac{L \times W \times H}{6000}$.
   - Sets item Chargeable Weight $= \max(\text{Actual Weight}, \text{Volumetric Weight})$.
   - Allows manual chargeable weight override with explicit audit trail logging to `audit_logs`.
3. **Dynamic Sales Charges & Surcharges**:
   - Computes Base Freight Charge $=$ Sales Rate $\times$ Total Chargeable Weight.
   - Captures auxiliary surcharges: Pickup, Inbound/Outbound TSP, TCP, Utility, X-Ray, ADO, Agent/Carrier AWB, Admin, Storage, Miscellaneous.
   - Sums total taxable amount dynamically.
4. **GST Applied Toggle Mechanics**:
   - Element `#gst_applied` triggers JavaScript calculation `calcTotals()`.
   - References active company tax rates (`cgst_rate`, `sgst_rate`, `igst_rate`).
   - Calculates rounded taxes:
     - $\text{CGST} = \text{round}\left(\text{Taxable} \times \frac{\text{CGST Rate}}{100}\right)$
     - $\text{SGST} = \text{round}\left(\text{Taxable} \times \frac{\text{SGST Rate}}{100}\right)$
     - $\text{IGST} = \text{round}\left(\text{Taxable} \times \frac{\text{IGST Rate}}{100}\right)$
   - Derives Net Payable $=$ Taxable Subtotal $+$ CGST $+$ SGST $+$ IGST.
5. **Dynamic Custom Charges (AWB-Protocol-Specific Fields)**:
   - **Item-Level**: Each shipment item has a `+ Add Charge` button in the item drawer. Clicking it appends a row with two inputs: **Label** (e.g. "Super Charge", "Ticket Cost", "Flyer Fees") and **Amount (₹)**. Multiple rows can be added. Each row can be removed independently.
   - **Global-Level**: The booking's surcharges section has a `+ Add Surcharge` button that similarly appends label+value rows at the booking level. These map to `sales_charges.custom_charges` JSON field.
   - **Serialization**: Item charges are serialized into the `items_json` hidden field as a JSON array `[{label, value}, ...]` per item. Global charges are submitted as `custom_global_surcharge_labels[]` + `custom_global_surcharge_values[]` form arrays.
   - **Totals**: Global custom surcharges include class `calc-surcharge` — automatically included in the live Net Payable footer calculation. `calculateTotalAmount()` in `BookingService` decodes and sums custom charges into the sales total.
   - **Invoice PDF**: `InvoiceService::buildShipmentRows()` decodes per-item custom charges, sums them into the taxable total per row, and populates `$itemCustomMap` (per-label lookup). `invoice.php` includes custom charges in the "OTHER CHG" column so all columns sum correctly to "TOTAL Amt."
   - **Backward Compatibility**: Existing bookings without custom charges work identically — `custom_charges` defaults to `NULL`, treated as zero throughout all calculation paths.
6. **PDF Invoice Generation (`app/Views/pdfs/invoice.php`)**:
   - Generates pixel-perfect horizontal A4 PDF invoices.
   - Performs row-level taxable calculation: $\text{Freight} + \text{Fuel Surcharge} + \text{Docket} + \text{Pickup} + \text{Delivery}$.
   - Prints company dynamic Terms & Conditions and Authorised Signatory digital signature.

### Responsible Files
* **Controller**: `app/Controllers/Logistics.php`
* **Service**: `app/Services/BookingService.php`
* **Models**: `app/Models/BookingModel.php`, `app/Models/ShipmentItemModel.php`, `app/Models/SalesChargeModel.php`, `app/Models/AuditLogModel.php`
* **Views**: `app/Views/logistics/booking_form.php`, `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/view_booking.php`, `app/Views/pdfs/invoice.php`
* **JS Utilities**: `public/js/erp-utils.js`

---

## 2. Master Data Management

### Purpose
Centralized administrative registry for operational assets, customer profiles, system configurations, and dynamic lookup choices.

### Sub-Modules & Data Flow
1. **Customer Master (`masters/customers.php`, `_customer_form_fields.php`)**:
   - Configures customer names, unique codes, billing addresses, GST State numbers (`27AAAAA1111A1Z1`), payment terms, and default currency.
2. **Airlines, Drivers & Transporters Masters**:
   - Registry of operational assets feeding booking form dropdowns.
3. **Lookup Values (`masters/lookups.php`)**:
   - Standardized lookup categories: `origin`, `destination`, `mode`, `material_type`, `material_category`, `payment_type`.
   - Dropdown options populate alphabetically.
4. **Company Settings (`company_settings.php`)**:
   - Manages active company profiles, SAC/PAN credentials, default tax rates (CGST/SGST/IGST), custom invoice print Terms & Conditions text, and digital signature uploads (`public/uploads/signatures/`).

### Responsible Files
* **Controllers**: `app/Controllers/MasterController.php`, `app/Controllers/CompanyController.php`
* **Models**: `app/Models/CustomerModel.php`, `app/Models/TransporterModel.php`, `app/Models/DriverModel.php`, `app/Models/AirlineModel.php`, `app/Models/LookupValueModel.php`, `app/Models/SystemSettingsModel.php`
* **Views**: `app/Views/masters/*`, `app/Views/company/settings.php`

---

## 3. Live Courier Tracking & POD System

### Purpose
Provides full tracking history visibility and proof-of-delivery (POD) document management.

### Workflow
1. **Transit Status Lifecycle**:
   - Stages: *Booked* $\rightarrow$ *Picked Up* $\rightarrow$ *In Transit* $\rightarrow$ *Arrived at Hub* $\rightarrow$ *Out for Delivery* $\rightarrow$ *Delivered*.
2. **Proof of Delivery (POD)**:
   - Accepts image uploads (JPEG/PNG) and digital signature signatures when marking shipment as *Delivered*.
3. **Asynchronous Tracking API**:
   - Public tracking endpoint (`/api/track/{awb_no}`) and internal tracking timeline drawer (`/tracking/history/{booking_id}`).

### Responsible Files
* **Controllers**: `app/Controllers/Logistics.php` (tracking actions), `app/Controllers/Api/TrackingApi.php`
* **Models**: `app/Models/TrackingStatusLogModel.php`, `app/Models/BookingModel.php`
* **Views**: `app/Views/logistics/tracking_drawer.php`, public tracking view
