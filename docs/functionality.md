# M.A. Logistics ERP — Functional Documentation

This document provides a comprehensive functional description of all modules, business workflows, mathematical calculations, form behaviors, and dropdown logic in the M.A. Logistics ERP system.

## Current Backend Behavior Additions

* **All Invoice layout rules**: Consolidated invoices support both A4 landscape and portrait. The All Invoice header is deliberately text-only and does not use the uploaded logo. GSTIN/SAC/PAN identity rows, customer GST/PAN, GST table columns, and GST summary rows appear only for GST-applied invoices with configured rates. Non-GST output removes those tax-only fields. Portrait applies compact type and cell padding while preserving invoice data. Final pages show taxable amount, applicable tax components, gross amount, amount in words, a 60/40 terms-and-bank/signature footer, and repeated table headings on continuation pages.
* **Customer docket binding and layout**: Individual docket PDFs receive the raw shipment row plus resolved Company and Customer Master data after invoice aggregation. Company logo/contact/GST, booking route/date/mode, shipper and consignee names/addresses/phones, item quantities/weights/dimensions, payment type, contents, and calculated charges are dynamic. Unsupported Form No., Method of Pkg., and Declared Weight substitutions are omitted instead of printing unrelated values. Missing fields remain blank rather than falling back to sample company or shipment values. The Full Print includes amounts; Half Print suppresses them while retaining operational fields.
* **Docket contents persistence**: The shipment drawer exposes **Said to Contain** as a required item field. `BookingService` persists it to `shipment_items.contents`; Part No. remains independent and optional.
* **Date/category/location customer rates**: When a shipment item is saved with a blank or zero item rate, `BookingService` looks up `customer_rates` by active company, item customer, booking origin, booking destination, optional item/booking material category, and booking date. The matched rate is copied into `shipment_items.rate`, so later Customer Master or rate-table changes do not mutate old saved bills.
* **Item metadata persistence**: `shipment_items.payment_type` and `shipment_items.material_category` are persisted when provided in `items_json`; if absent, the booking-level payment type and material category are stored as fallbacks.
* **Zero weight allowance with AWB sanity guard**: Item actual weight may be zero. If a booking-level `total_weight` is declared, the backend rejects saves where summed item actual weight is below that master AWB weight.
* **Invoice master-data fallback**: PDF invoice generation prefers Customer Master address, GSTIN, and PAN details when available, with shipment `bill_to`/`consignee` as fallback.
* **Optional LR/docket clubbing**: Invoice row building supports grouping rows with the same LR/docket number when `club_by_lr=1` or consolidated billing posts `billing_mode=docket`; default invoice output remains per shipment item.
* **Remarks compatibility**: New `bookings.remarks` and legacy `bookings.narration` both print in the invoice remarks block.
* **Financial-year invoice numbering**: Consolidated PDF generation finalizes a company-scoped invoice number such as `MA-26-27/001`, persists it to selected shipment rows, and reuses that number on later reprints instead of allocating duplicates.
* **Edit-mode item autosave**: In booking edit mode, saving or deleting a shipment item immediately posts the updated booking form via AJAX to `Logistics::update()`. Staff no longer need to click Update Booking after Save Item for item-level changes to persist.
* **Invoice charge overflow rule**: The default invoice layout shows at most the first four active item-specific charge columns in the order Delivery, Docket, Pickup, Fuel, then later charges/custom charges. Any remaining active charges are summed into the Other Charges column so totals still match the taxable row amount.
* **Multi-page invoice continuation**: The company and invoice-detail header prints once on page 1. Overflow pages begin near the top with the repeated billing-table column headings, and shipment serial numbers continue from the preceding page without restarting.
* **Runtime uniqueness feedback**: AWB uniqueness is checked while the AWB field is edited. Docket uniqueness is checked while editing the item drawer and again before Save Item accepts the row, preventing late save-time validation from discarding filled form data.
* **Default invoice bank selection**: The All Invoices generator preselects the company default bank account when one is configured; backend invoice generation still falls back to the default bank, then first bank, then legacy company bank fields.
* **Month-wise invoice download history**: All Downloads filters saved consolidated PDFs by their invoice billing month (`invoice_date`, with `from_date` fallback), regardless of when the PDF was generated. It shows the month billing total from `invoice_downloads.total_amount`, shows who generated each bill, and allows permitted users to delete a saved PDF/history row.
* **Generated-invoice history focus**: After a consolidated PDF is generated, All Downloads automatically selects that invoice's billing month before refreshing, so a historical invoice generated in a later month is immediately visible without weakening company isolation.
* **Draft recovery hardening**: Draft saves keep the local browser recovery copy until a non-draft booking save succeeds, reducing data-loss risk when a draft submit is rejected by server validation.
* **Invoice Master prefixes**: Admins manage company-scoped invoice types with Name, GST/Non-GST type, and Prefix. Booking item entry exposes those prefixes beside Invoice No so staff assign the intended GST/Non-GST invoice series during shipment entry. All Invoices reads the saved prefix to warn about GST mismatches without blocking invoice generation.
* **Docket Master prefixes**: Admins manage company-scoped docket prefixes as Auto Increment or Manual. Booking item entry exposes the configured prefixes; auto prefixes generate with a locked sequence row, while manual prefixes leave the docket number editable.
* **Location-wise customer item rates**: Customer Master edits one active version per Customer + Origin + Destination + optional material category and shows closed versions read-only. Changes and removals close prior date ranges; they do not erase rate history. Supplied O&D must match exactly (case-insensitive), while category-specific rates retain precedence over blank-category rates.
* **Concurrent customer-rate saves**: Customer/rate writes use one transaction and a tenant-scoped customer-row lock. Repeated same-rate saves return the current version; stale differing saves return a reload-required conflict instead of silently overwriting another user.
* **Invoice PDF save picker**: All Invoices generates and records the PDF first, then shows an explicit “Choose save location” action. Supported Chromium secure contexts invoke `showSaveFilePicker()` directly from that click. Cancellation is not an error; unsupported/insecure contexts use normal download and show a fallback notice.
* **Customer integrity operations**: Customer names accept the database-supported 200 characters, address text round-trips unchanged, and customer deletion is tenant-scoped and transactional with related customer-rate rows.
* **Public tracking page**: `/track` and `/tracking` render the public AWB/Docket lookup without a login. The page calls the single-segment `/api/track/{awb_or_docket}` contract and does not advertise hardcoded records that may not exist. `/` remains the ERP login entry point, and internal tracking history/update routes remain authenticated.
* **Inclusive invoice shipment dates**: All Invoices includes every shipment through the selected To Date, including records whose `booking_date` contains a time later that day.
* **Booking party validation**: Shipment items require Customer, Bill To, and Consignee before entering the manifest grid, matching backend validation and preventing a late transaction rollback.

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
6. **Dual Invoice Output Engine (`invoice.php` & `docket_pdf.php`)**:
   - **AWB Invoice (All Invoices Summary)**: Generates text-only A4 landscape or portrait consolidated PDF invoices (`app/Views/pdfs/invoice.php`) matching sample `MAL_25-26_126.pdf`. It uses compact orientation-aware spacing, row-level taxable calculation ($\text{Freight} + \text{Fuel Surcharge} + \text{Docket} + \text{Pickup} + \text{Delivery}$), conditional GST fields, and dynamic multi-line Terms & Conditions. The uploaded company logo is intentionally excluded from this form.
   - **Docket Bill (Individual Shipper Copy Waybill)**: Generates a single-page portrait docket (`app/Views/pdfs/docket_pdf.php`) matching the tall, ruled proportions of sample `1.jpeg`. The controller passes the raw shipment and resolved Company/Customer Master values alongside calculated totals, so the document remains fully dynamic. Supports **Full Print** (financial breakdown) and **Half Print** (`print_mode=half` suppressing monetary values for delivery use).
   - **Visual Alignment & Encoding**: Uses `Rs.` for currency, explicit `PART NO.` (75%) and `QTY.` (25%) bordered cells, clean TCPDF square bracket checkboxes `[X]` / `[ ]`, `DELIVERY CHARGES` replacing Octroi, a dynamic `Said to Contain` field, and manual Insured/Signature boxes for physical stamping. Unsupported source fields are removed rather than guessed.

### Responsible Files
* **Controller**: `app/Controllers/Logistics.php`
* **Service**: `app/Services/BookingService.php`, `app/Services/InvoiceService.php`, `app/Services/PdfInvoiceGenerator.php`
* **Models**: `app/Models/BookingModel.php`, `app/Models/ShipmentItemModel.php`, `app/Models/SalesChargeModel.php`, `app/Models/AuditLogModel.php`
* **Views**: `app/Views/logistics/booking_form.php`, `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/view_booking.php`, `app/Views/pdfs/invoice.php`, `app/Views/pdfs/docket_pdf.php`
* **JS Utilities**: `public/js/erp-utils.js`

---

## Test Automation JSON API

The ERP exposes an isolated `/api/v1` surface for deterministic backend automation. It supports Basic Auth or API login sessions, explicit company context, tenant/branch-scoped resources, structured JSON errors, and producer IDs for bookings, tracking events, customers, and saved consolidated invoices. Existing browser workflows continue using their original session, redirect, and CSRF contracts.

Responsible files: `app/Controllers/Api/V1Controller.php`, `app/Filters/ApiBasicAuthFilter.php`, `app/Config/Routes.php`, and `app/Config/Filters.php`.

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
4. **Company Settings (`company/settings.php`)**:
   - Manages active company profiles, SAC/PAN credentials, default tax rates (CGST/SGST/IGST), custom invoice print Terms & Conditions text, digital signature uploads (`public/uploads/signatures/`), and **Company Logo Upload & Branding** (`public/uploads/logos/`).
   - Admins can upload company logos (PNG, JPG, WEBP, GIF up to 2MB) with live preview thumbnails and a Delete Logo action. Uploaded logos render dynamically in PDF invoice and waybill headers.
   - Admins can upload, preview, replace, and delete the invoice-footer signature (PNG, JPG, or GIF) from the same settings page.

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
4. **Public Tracking Page**:
   - `/track` and `/tracking` load the public lookup view; authentication is bypassed only for those exact aliases and the read-only tracking API.

### Responsible Files
* **Controllers**: `app/Controllers/Logistics.php` (tracking actions), `app/Controllers/Api/TrackingApi.php`
* **Models**: `app/Models/TrackingStatusLogModel.php`, `app/Models/BookingModel.php`
* **Views**: `app/Views/logistics/tracking_drawer.php`, public tracking view
