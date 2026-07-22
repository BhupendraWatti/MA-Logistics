# M.A. Logistics ERP — Functional Documentation

This document provides a comprehensive functional description of all modules, business workflows, mathematical calculations, form behaviors, and dropdown logic in the M.A. Logistics ERP system.

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
5. **PDF Invoice Generation (`app/Views/pdfs/invoice.php`)**:
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
