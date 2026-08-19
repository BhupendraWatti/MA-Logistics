# 🎬 3-Video Unified Master Specification & Business Explanations

**Project**: M.A. Logistics ERP (MARL EXPRESS ERP)  
**Date**: August 13, 2026  
**Persona Standard**: `@persona-project-manager` & `Video-to-Action via Gemini Passthrough`  
**Source Video Record**:
1. **Screen Recording 1**: [Awesomescreenshot Video 55478782](https://www.awesomescreenshot.com/video/55478782?key=a65511f2b64e72820124b667cd38e5fc) *(53m 30s — Sir's Core Logistics Business Model, AWB/Docket Lifecycle, Surcharges, Item Drawer)*
2. **Screen Recording 2**: [Awesomescreenshot Video 55482897](https://www.awesomescreenshot.com/video/55482897?key=39aefc6eee473795dacd4806d9cdeef9) *(51m 13s — Point-by-Point Review of `CHANGES REQUIRED.docx`, 38 explicit client fix requests)*
3. **Video 3**: `C:\Users\bhupe\Downloads\Meet - Quick Meeting for Phase1 software.mp4` *(24m 45s — Deep Dive into Operational Functionality, Master Series Rules, Multi-Page Invoices, and Data Loss Prevention)*

---

## 1. Executive Business Workflow & Logistics Domain Model (Merged 3 Videos)

### 📦 A. Consignment Manifesting & Routing
- **Air Waybill (AWB) vs. Surface Docket/LR**:
  - Every consignment booking represents an **AWB Number** (for Air freight) or **Docket / LR Number** (for Surface freight).
  - Primary parties linked per booking: **Customer (Shipper)**, **Bill To Party**, **Consignee (Recipient)**, **Origin Airport/Hub**, **Destination Airport/Hub**, **Carrier (Airline/Transporter)**, and **Flight No**.
- **Item Drawer Session & Smart Carry-Forward**:
  - Package line items are entered via an offcanvas Item Drawer modal.
  - To minimize repetitive data entry during multi-package manifest creation, fields such as **Customer Name, Bill To, Consignee, Docket No, Part No, and Invoice Date** automatically copy forward into subsequent drawer sessions.

### ⚖️ B. Volumetric Weight Engine & Calculation Rules
- **Volumetric Weight Standard Formula**:
  $$\text{Volumetric Weight (kg)} = \frac{L \text{ (cm)} \times W \text{ (cm)} \times H \text{ (cm)}}{6000}$$
- **Chargeable Weight Rule**:
  $$\text{Chargeable Weight per Item} = \max(\text{Actual Weight}, \text{Volumetric Weight})$$
- **Audit Trap for Overrides**: Staff may manually override calculated chargeable weight under negotiated terms; every manual override must write an immutable log entry to `audit_logs`.

### 💰 C. Customer Rate Engine & Date-Range Validity
- **Historical Bill Preservation**: Updating customer master rates must **NEVER** retroactively alter previously generated invoices.
- **Date-Wise & Category-Wise Tariff Table**:
  - Rates are maintained in `customer_rates` mapped to `customer_id`, `material_category_id`, `effective_from`, and `effective_to`.
  - When creating a booking on `booking_date`, the system auto-fetches the active rate for that date range and snapshots it into `sales_charges`.

### 📄 D. Invoicing, FY Series & Multi-Page PDF Formatting
- **Financial Year Invoice Series**:
  - Invoices auto-generate sequential numbers formatted by Financial Year (e.g. `MA-26-27/001`, `MARL-26-27-0001`).
  - System supports multiple series (e.g. GST vs. Non-GST, Main Branch vs. Airport Branch).
- **Multi-Page Layout Architecture**:
  - High-density invoices with 10+ shipment rows automatically spill onto **Page 2**, maintaining clean table formatting and preventing cell blowout.
  - Dynamic Terms & Conditions ($60\%$ width) and digital signature ($40\%$ width) remain positioned at the footer of the final page using TCPDF side-by-side sub-tables.

### 🛡️ E. Form Persistence & Zero Data Loss Trap
- **Draft Recovery (`isDirty`)**:
  - To prevent catastrophic loss of typed manifest items during accidental tab closure or network drops, forms maintain auto-backup in local storage (`isDirty` flag).
  - Draft bookings are saved with status `'Draft'` until final submission.

---

## 2. Unified Master Task Matrix (15 Master Task Groups / 38 Requirements)

Below is the consolidated **Phase 1 Task Specification Matrix**, merging all discussions from Video 1, Video 2, and Video 3.

| Task ID | Doc Ref | Requirement Title | Problem Statement (Video 1 & 2) | Refined Functionality (Video 3) | Proposed Technical Fix | Target File(s) in `MAlogistic` | Priority | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TSK-01** | P1, P3, P26 | Bill To, Customer & Consignee Dropdowns | Plain text inputs required typing full customer names with no suggestions. | Must auto-populate exact Customer Master names to prevent spelling mismatches. | Convert text inputs to Select2 type-ahead search dropdowns loaded from Customer Master (`ASHIM FLOWERS`, `ASHISH FLOWERS`). | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[MasterController.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/MasterController.php) | High | Ready |
| **TSK-02** | P2, P8 | Payment Type & Category in Drawer | Payment Type and Material Category missing in package line item drawer. | Each item in multi-category consignments requires explicit Payment Type & Category. | Add Payment Type & Material Category selects to item drawer modal; persist in `shipment_items`. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[ShipmentItemModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/ShipmentItemModel.php) | High | Ready |
| **TSK-03** | P6, P7, P9 | Date-Wise Customer Rates Engine | Customer master rate updates retroactively altered historical invoice calculations. | Master tariff tables must be date-bounded (`effective_from` to `effective_to`) per material category. | Create `customer_rates` table. Auto-fetch active rate on booking date and snapshot into `sales_charges`. | [CustomerModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/CustomerModel.php)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php)<br>[erp-utils.js](file:///d:/Company%20Work/Company%20projects/MAlogistic/public/js/erp-utils.js) | High | Ready |
| **TSK-04** | P10 | Shift Flight Details to Page 2 Section | Airline select & Flight No cluttered Page 1 master booking header. | Shift flight routing fields to flight details section on Page 2 / item grid. | Relocate Airline select & Flight No input to flight details section on Tab 2 / item section. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php) | Medium | Ready |
| **TSK-05** | P11 | Invoice Grid Date = Booking Date | Invoice PDF grid displayed PDF generation date instead of actual booking date. | Invoice row date must strictly match actual consignment `booking_date`. | Map grid date column and PDF invoice row date to `booking_date` from `bookings` table. | [invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php)<br>[InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php) | High | Ready |
| **TSK-06** | P14, P17, P24 | Auto-Generated FY Invoice Numbers | Manual invoice numbers caused duplicate bill numbers across branches. | System must support multiple master series (`MA`, `MARL`) auto-incrementing by FY. | Implement auto FY invoice number generator (`MA-26-27/001`, `MARL-26-27-0001`) on final invoice save. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-07** | P16 | Suppress Zero-Value Surcharges on PDF | Zero-value surcharges (₹0) appeared as clutter on PDF invoices. | Hide any surcharge row with ₹0 or empty value on customer PDF invoices. | Filter out zero/null charges during PDF table row aggregation in `InvoiceService`. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | Medium | Ready |
| **TSK-08** | P18 | Centralized "All Invoices" Manager | No single view existed to search, view, and download generated bills. | Add central bill management page with quick PDF download & email action links. | Create "All Invoices" grid view under Logistics menu with filters by date, customer, and invoice number. | [manage_bookings.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/manage_bookings.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-09** | P19, P20 | Customer Address, SAC Code & GSTIN | Invoice header omitted customer GSTIN, SAC Code (996531), and full address. | Invoice PDF header must render customer GSTIN, State Code, SAC Code (996531), and Master address. | Auto-populate customer GSTIN, State Code, SAC Code (996531), and billing address into PDF invoice header. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | High | Ready |
| **TSK-10** | P25 | Billing Mode Choice (AWB / Docket) | Billing was locked strictly per AWB; clients require billing per Docket No. | Allow staff to generate bills either per AWB Number or per Docket Number. | Add radio toggle `Billing Mode: [AWB Mode / Docket Mode]` in invoice filter & generator. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[manage_bookings.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/manage_bookings.php) | High | Ready |
| **TSK-11** | P27, P33 | Unique AWB Validation & Non-Destructive Edit | Duplicate AWBs could be saved; editing AWB cleared typed drawer items. | Validate AWB uniqueness via AJAX; preserve typed item drawer rows during AWB edits. | Add AJAX uniqueness check on `awb_no` input. Retain item grid contents during edit mode. | [BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-12** | P35, P45 | Form Persistence & Data Loss Protection | Browser errors lost all entered booking data without saving draft. | Automatic local storage form recovery (`isDirty`) & explicit Draft state. | Add JS local storage backup trap and support explicit `'Draft'` status in `bookings` table. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php) | High | Ready |
| **TSK-13** | P46 | Item Weight vs. AWB Weight Sanity Check | Item weight could be entered as less than master AWB minimum weight. | Total item weight must be equal to or greater than master AWB weight. | Enforce `Total Item Weight >= Minimum AWB Weight` JS toast error & backend validation. | [erp-utils.js](file:///d:/Company%20Work/Company%20projects/MAlogistic/public/js/erp-utils.js)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php) | Medium | Ready |
| **TSK-14** | P58 | Custom Remarks / PO Note Input | No field on booking form or invoice for custom notes/remarks. | Remarks field needed on booking form, booking grid, and PDF invoice. | Add `remarks` text input to booking form, persist in `bookings.narration`, display on grid & PDF invoice. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[BookingModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/BookingModel.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | Medium | Ready |
| **TSK-15** | P59 | Consolidated Billing by Same LR / Docket | Multiple items sharing the same LR No printed as separate fragmented lines. | Provide option to club consignments sharing the same LR No into a single invoice line. | Add "Club by LR No" toggle in Invoice Generator to group items sharing the same LR No. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php) | Medium | Ready |
