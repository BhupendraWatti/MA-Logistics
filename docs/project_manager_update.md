# 📊 Executive Project Manager Task Update & Sprint Specification

**Project**: M.A. Logistics ERP (MARL EXPRESS ERP)  
**Report Date**: August 13, 2026  
**Prepared By**: Technical Project Manager / Lead Solutions Architect  
**Project Health**: 🟢 **ON TRACK** (Requirements Baseline & Specification Complete)  
**Source Assets Processed**:
- **Screen Recording 1**: [Awesomescreenshot Video 55478782](https://www.awesomescreenshot.com/video/55478782?key=a65511f2b64e72820124b667cd38e5fc) *(Business Model & Core Workflow Walkthrough — 53m 30s)*
- **Screen Recording 2**: [Awesomescreenshot Video 55482897](https://www.awesomescreenshot.com/video/55482897?key=39aefc6eee473795dacd4806d9cdeef9) *(Fixes & Document Review — 51m 13s)*
- **Client Document**: `CHANGES REQUIRED.docx` *(38 specific client requirement points)*
- **Financial Artifacts**: `invoices.xlsx` *(BLUESTAR, TRW, NX Logistics monthly bills)*, `Airline details with charges.xlsx` *(Air India, IndiGo charge reports)*, and PDF manifests.

---

## 1. Executive Summary & Business Architecture

The **M.A. Logistics ERP** is a multi-tenant enterprise application managing air freight and surface cargo manifesting. Based on stakeholder discussions led by Sir (Ravi Varma) and technical analysis of the [MAlogistic](file:///d:/Company%20Work/Company%20projects/MAlogistic) codebase, the platform enforces the following core operational rules:

1. **Manifesting & Routing**: Consignments track AWBs (Air) or Dockets/LRs (Surface), linking Shipper, Bill To, Consignee, Origin, Destination, Carrier, and Flight details.
2. **Volumetric Weight Engine**: Automatically computes Volumetric Weight ($\frac{L \times W \times H}{6000}$) and sets Chargeable Weight to $\max(\text{Actual Weight}, \text{Volumetric Weight})$. Manual overrides are recorded in audit logs (`audit_logs`).
3. **Dynamic Surcharges**: Calculates Base Freight ($=\text{Rate} \times \text{Chargeable Weight}$) alongside standard surcharges (Pickup, Delivery, Docket, FSC, HC, TSP, TCP, X-Ray, Admin) and dynamic custom item charges (`+ Add Charge`).
4. **Financial Controls & Invoicing**: Supports billing per AWB or per Docket No, auto-generates Financial Year invoice numbers (`MA-26-27/001`), and renders horizontal A4 PDF invoices with dynamic Terms & Conditions and digital signatures.

---

## 2. Phase 1 Sprint Backlog & Technical Specification

The table below outlines the formal **Phase 1 Sprint Backlog**, detailing the problem statement, client requirement, proposed technical fix, and exact file locations in the codebase.

| Task ID | Doc Ref | Requirement Title | Problem & Root Cause | Technical Solution & Implementation Plan | Target File(s) in `MAlogistic` | Priority | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TSK-01** | P1, P3, P26 | Bill To, Customer & Consignee Dropdowns | Input fields were plain text. Users typed exact names manually without autocompletion. | Convert inputs to Select2 type-ahead search dropdowns linked to Customer Master (`ASHIM FLOWERS`, `ASHISH FLOWERS`). | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[MasterController.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/MasterController.php) | High | Ready |
| **TSK-02** | P2, P8 | Payment Type & Material Category in Drawer | Item drawer modal did not capture per-item Payment Type and Material Category. | Add Payment Type & Material Category select dropdowns to item drawer modal; persist in `shipment_items`. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[ShipmentItemModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/ShipmentItemModel.php) | High | Ready |
| **TSK-03** | P6, P7, P9 | Date-Wise & Category-Wise Customer Rates | Rates were entered manually. Updating customer master rates retroactively altered old saved bills. | Create `customer_rates` table with date range (`effective_from`, `effective_to`) & category. Auto-fetch rate on booking date. | [CustomerModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/CustomerModel.php)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php)<br>[erp-utils.js](file:///d:/Company%20Work/Company%20projects/MAlogistic/public/js/erp-utils.js) | High | Ready |
| **TSK-04** | P10 | Relocate Airline & Flight No Controls | Airline select & Flight No took space on Page 1 master booking form. | Move Airline select & Flight No input from Tab 3 to Tab 2 / flight details section. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php) | Medium | Ready |
| **TSK-05** | P11 | Invoice Grid Date = Booking Date | Invoice PDF printed invoice creation date instead of actual booking date. | Map grid date column and PDF invoice row date directly to `booking_date` from `bookings` table. | [invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php)<br>[InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php) | High | Ready |
| **TSK-06** | P14, P17, P24 | Auto-Generated FY Invoice Numbers | Manual invoice numbers caused duplicate or inconsistent invoice numbers. | Auto-generate FY invoice numbers (e.g. `MA-26-27/001`) sequentially on final save. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-07** | P16 | Exclude Zero-Value Charges from PDF | Surcharges with zero values (₹0) rendered on PDF invoice printouts. | Exclude charges where amount is 0 or null during PDF row aggregation in `InvoiceService`. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | Medium | Ready |
| **TSK-08** | P18 | Centralized "All Invoices" View Grid | Staff could not view or search all generated invoices in a single grid. | Add "All Invoices" view & grid under Logistics menu with filters by date, customer, and invoice number. | [manage_bookings.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/manage_bookings.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-09** | P19, P20 | Customer Address, SAC Code & GSTIN on Invoice | Invoices omitted customer GSTIN, SAC Code (996531), and full billing address. | Populate customer master GSTIN, state code, SAC code (996531), and address onto PDF invoice header. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | High | Ready |
| **TSK-10** | P25 | Billing Choice by Docket No or AWB No | Billing was enforced per AWB only; some clients require billing per Docket No. | Add radio toggle `Billing Mode: [AWB Mode / Docket Mode]` in billing filter and invoice generator. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php)<br>[manage_bookings.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/manage_bookings.php) | High | Ready |
| **TSK-11** | P27, P33 | Unique AWB Validation & Non-Destructive Edit | Duplicate AWBs could be saved; editing AWB cleared item drawer contents. | Add AJAX uniqueness check on `awb_no` input. In edit mode, allow modifying AWB while preserving item drawer data. | [BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php)<br>[Logistics.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Controllers/Logistics.php) | High | Ready |
| **TSK-12** | P35, P45 | Draft Saving & Error Recovery Trap | Browser crashes or navigation lost typed booking inputs. | Implement automatic local storage backup (`isDirty`) and explicit Draft booking state in `bookings` table. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php) | High | Ready |
| **TSK-13** | P46 | Piece & Weight Sanity Validation | Package weight could be saved as less than master AWB weight. | Enforce `Total Item Weight >= Minimum AWB Weight` JS & backend validation. | [erp-utils.js](file:///d:/Company%20Work/Company%20projects/MAlogistic/public/js/erp-utils.js)<br>[BookingService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/BookingService.php) | Medium | Ready |
| **TSK-14** | P58 | Remark / Note Field Addition | Booking form and invoice lacked custom note/PO number fields. | Add `remarks` text input to booking form and display in "REMARK" column on grid and invoice PDF. | [booking_form.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/logistics/booking_form.php)<br>[BookingModel.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Models/BookingModel.php)<br>[invoice.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Views/pdfs/invoice.php) | Medium | Ready |
| **TSK-15** | P59 | Clubbing Same LR / Docket Numbers | Multiple consignments with the same LR No printed as separate fragmented lines. | Add "Club by LR No" option in Invoice Generator to group items sharing the same LR No into a single consolidated invoice line. | [InvoiceService.php](file:///d:/Company%20Work/Company%20projects/MAlogistic/app/Services/InvoiceService.php) | Medium | Ready |

---

## 3. Phase 2 Feature Roadmap

| Task ID | Feature Category | Feature Name | Description | Target Phase | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **P2-001** | Address Lookup | Auto-Fill City & State from Pincode | Integrate Google Maps Pincode API to auto-fill City and State when user enters 6-digit Pincode | Phase 2 | Planned |
| **P2-002** | Communication | One-Click Custom Email Sender | Send PDF invoices and consignment tracking updates directly to clients via email popup | Phase 2 | Planned |
| **P2-003** | Grid Customization | Admin Grid Column Customizer | Drag-and-drop visibility and order controls for Manage Bookings grid columns | Phase 2 | Planned |
| **P2-004** | Reporting | 28-Column MIS Excel Export | Full financial and operational audit export for corporate clients with 28 detailed fields | Phase 2 | Planned |
| **P2-005** | Customer Portal | Client Self-Service Dashboard | Dedicated login portal for corporate customers to create draft bookings and view live tracking | Phase 2 | Planned |

---

## 4. Key Risks & Pre-emptive Mitigations

1. **Historical Invoice Mutation Risk**:
   - *Risk*: Updating a customer's master rate could alter past saved invoices.
   - *Mitigation*: Implementation of `customer_rates` effective date ranges (`01.04.26` to `30.04.26`). Rates are snapshot-copied into `sales_charges` at booking creation.
2. **TCPDF Layout Blowout Risk**:
   - *Risk*: Variable Terms & Conditions text expanding and pushing down the digital signature box.
   - *Mitigation*: Maintain Option C Architecture (independent side-by-side sub-tables $60\% / 40\%$) inside outer wrapper cells.

---

## 5. Artifact Deliverables Index

- **Master Task Tracker Spreadsheet**: `D:\Company Work\Company projects\MALogistic phase 1 issues\12 Aug 2026\MA_Logistics_Phase1_Fixes_and_Phase2_TaskTracker.xlsx`
- **Live Google Sheet**: [MARL EXPRESS ERP Software — Google Sheets](https://docs.google.com/spreadsheets/d/1W9Zi4OHg0hqVbSTgccItXIKHrBRXCDeXwyOteGqNaXk/edit?usp=sharing)
- **Video-to-Action Master Specification**: [video_to_action_explanations.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/video_to_action_explanations.md)
- **Known Issues Registry**: [known-issues.md](file:///d:/Company%20Work/Company%20projects/MAlogistic/docs/known-issues.md)
