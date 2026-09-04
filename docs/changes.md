# M.A. Logistics ERP — Implementation & Change Log

This file tracks every technical change, feature implementation, refactoring, and pending scope addition performed on the M.A. Logistics ERP project.

## TestSprite Remediation

### [CHG-034] WordPress CMS Tracking Component & Plugin Integration (Elementor Shortcode [ma_tracking])
* **Status**: Completed; deployed and verified live
* **Priority**: High
* **Requirement**: Decouple the WordPress tracking page from 17.8KB of hardcoded inline HTML/CSS/JS in Elementor. Provide a reusable, theme-independent WordPress component that connects to the existing MA Logistics ERP Tracking API, supports URL deep-linking, renders responsive milestone timelines, and allows Elementor to manage all static CMS marketing content.
* **Root Cause**: The tracking page previously embedded raw inline code inside an Elementor HTML widget. This was brittle, prevented clean CMS edits in Elementor, lacked URL query parameter auto-tracking for customer notifications (WhatsApp/SMS), forced horizontal scrolling on mobile screens with wide tables, and risked data loss on theme or builder updates.
* **Implementation**:
  - Developed the **MA Logistics Tracking** WordPress plugin (`wp-plugin/ma-logistics-tracking/`) exposing shortcode `[ma_tracking]`.
  - Registered scoped stylesheet (`assets/css/ma-tracking.css`) and pure Vanilla JavaScript controller (`assets/js/ma-tracking.js`) with zero external dependencies (no jQuery).
  - Enqueue assets conditionally only on pages containing `[ma_tracking]` or on the dedicated tracking page, preserving website performance.
  - Implemented **URL Deep-Linking**: Visiting `/track-your-order/?awb=04637824` auto-fills the search input and executes live tracking immediately on page load.
  - Added a **Share Tracking Link** button that copies direct deep-links to clipboard with visual feedback.
  - Designed an interactive **Vertical Milestone Timeline** alongside the Consignment Details table, featuring origin $\rightarrow$ destination route indicators and status color-coding (Green for Delivered, Blue for Active/In-Transit).
  - Uploaded and activated the plugin via FTP at `/website/wp-content/plugins/ma-logistics-tracking/` on `https://website.granthinfotech.online/`.
  - Migrated Elementor page ID 7 (`track-your-order`) to the clean `[ma_tracking]` shortcode widget, backing up original Elementor meta and clearing builder caches.
  - Deployed to production on `marlexpress.com` (`103.86.176.249:21` at `/public_html/wp-content/plugins/ma-logistics-tracking/`), purged previous malformed files, activated the plugin, and verified shortcode execution and asset delivery.
* **Files Modified / Created**:
  - `wp-plugin/ma-logistics-tracking/ma-logistics-tracking.php`
  - `wp-plugin/ma-logistics-tracking/assets/css/ma-tracking.css`
  - `wp-plugin/ma-logistics-tracking/assets/js/ma-tracking.js`
  - `wp-plugin/ma-logistics-tracking/README.txt`
  - `wp-plugin/ma-logistics-tracking.zip`
  - Synchronized documentation in `docs/changes.md`, `docs/architecture.md`, `docs/api.md`, `docs/functionality.md`, and `docs/project_summary.md`.
* **QA & Live Verification**: Verified live on `https://website.granthinfotech.online/track-your-order/?awb=04637824` with real consignment `04637824`, asserting live ERP API communication, route display, 51-box parcel manifest, and milestone event timeline.

---

### [CHG-033] Focus All Downloads on the Generated Invoice Billing Month
* **Status**: Completed and regression tested
* **Priority**: High
* **Requirement**: Preserve the client-required company-scoped consolidated-invoice archive while making a newly generated historical invoice immediately visible in All Downloads.
* **Root Cause**: PDF generation saved the correct billing month, but the browser refreshed the previously selected month, usually the current month, making a historical invoice appear missing.
* **Implementation**: The AJAX generation response now returns `history_month` from the persisted invoice billing date, and the All Invoices page selects that month before refreshing download history.
* **Preserved Rules**: Download history remains tenant-scoped, billing-month based, and dependent on real generated invoice records; no cross-company data or synthetic production invoices were introduced.
* **Files Modified**: `app/Controllers/Logistics.php`, `app/Views/logistics/all_invoices.php`, regression tests, and synchronized documentation.

### [CHG-032] Tracking Request Contract, Billing-Month History, Invoice Date Range, and Booking Party Guard
* **Status**: Completed and regression tested
* **Priority**: High
* **Requirement**: Re-investigate the remaining four failed TestSprite scenarios against documented product rules and correct only confirmed application defects.
* **Root Cause**: The public page inserted an unsupported search-type path segment into the single-value tracking API and advertised nonexistent sample AWBs; All Downloads filtered by generation timestamp instead of invoice billing month; shipment search treated the selected end date as midnight; and the item drawer allowed blank Bill To/Consignee values that the backend correctly rejected, rolling back booking creation.
* **Implementation**: Restored the documented `/api/track/{awb_or_docket}` request shape and removed fake live samples; changed download history to invoice/from-date billing months; made shipment date ranges end-date inclusive using a next-day exclusive boundary; and aligned drawer validation with backend party requirements.
* **Preserved Rules**: Total Chargeable Weight remains the read-only sum of shipment-item chargeable weights, and consolidated invoices still require real eligible shipment data. Upload tests still require actual image fixtures.
* **QA**: Added failing-first model/surface regressions, PHP lint, full PHPUnit, local HTTP/API smoke checks, and reassessment of all eight TestSprite outcomes.

---

### [CHG-031] Customer Integrity, Signature UI, and Public Tracking Entry Points
* **Status**: Completed and regression tested
* **Priority**: High
* **Requirement**: Resolve the actionable defects reported by the 52-case TestSprite run without changing valid data-dependent or calculated workflows.
* **Root Cause**: Customer deletion reported success without verifying a tenant-scoped delete or cleaning rate rows; customer validation allowed fewer characters than the database schema; signature upload handling existed without a visible Company Settings control; and the public tracking view/API existed without public page routes.
* **Implementation**: Added transactional tenant-scoped customer/rate deletion with explicit JSON outcomes, aligned customer names to 200 characters and preserved full addresses, exposed signature upload/preview/delete controls with upload-directory creation, and added unauthenticated `/track` and `/tracking` page aliases. The ERP root remains the authenticated login entry point.
* **Test Classification**: The remaining TestSprite results depend on seeded AWB/customer/invoice/shipment records, required upload fixtures, or intentional calculated/read-only controls. They are documented in `known-issues.md` rather than converted into product regressions.
* **Files Modified**: Customer service/controller/model/form, Company Settings/controller, tracking controller/routes/filters, regression tests, and project documentation.
* **QA**: PHP lint, transactional MySQL regression coverage, product-surface route/control assertions, full PHPUnit suite, and public-route smoke testing.

---

## Latest PDF Fix

### [CHG-030] Deterministic Terms & Conditions Spacing in TCPDF
* **Status**: Completed; rendered and verified
* **Priority**: High
* **Requirement**: Remove the oversized gap between introductory T&C text and numbered lists, and do not add bottom spacing that was not entered in the editor.
* **Root Cause**: TCPDF 6.11.3 parses CSS margins on ordinary block tags but does not apply them to paragraph/list HTML flow, and its list-item close handler forces zero vertical space. Raw inter-tag newlines are converted to spaces by TCPDF and were not responsible for the gap. The previous attempt disabled automatic block gaps but relied on ignored `margin-bottom: 5px` rules to restore item spacing.
* **Implementation**: Centralized T&C normalization in `PdfInvoiceGenerator::formatTermsHtml()`, disabled only TCPDF's automatic paragraph/list spacing, converted editor `<div>` blocks to controlled paragraphs, and inserted only TCPDF's missing normal line advance between consecutive list items. No bottom margin is synthesized. Explicit editor blank lines and empty paragraphs remain visible. Both PDF templates use the same formatter.
* **Files Modified**: `app/Services/PdfInvoiceGenerator.php`, `app/Views/pdfs/invoice.php`, `app/Views/pdfs/docket_pdf.php`, `tests/PdfInvoiceLayoutTest.php`, `docs/changes.md`
* **QA**: PHP lint, formatter regression assertions, full PHPUnit suite, generated invoice/docket PDFs, text-coordinate measurement, and rendered-page inspection.

---

## Latest Frontend Change

### [CHG-029] Rebalance Portrait All Invoice Column Widths
* **Status**: Completed; rendered and verified
* **Priority**: Medium
* **Requirement**: Reduce the oversized Total column in the portrait consolidated invoice and give descriptive columns more room.
* **Implementation**: Portrait default invoices now reclaim up to eight percentage points from the Total column, while preserving a 12% minimum, and distribute that width to LR No., Invoice Number, Origin, and Destination. Landscape and special NX/Brembo layouts retain their existing proportions.
* **Files Modified**: `app/Views/pdfs/invoice.php`, `tests/PdfInvoiceLayoutTest.php`, `docs/*`
* **QA**: PHPUnit layout assertions plus rendered first/final-page inspection of a 70-row A4 portrait fixture with Docket, Pickup, and Delivery columns.

---

### [CHG-028] Correct All Invoice Landscape/Portrait Forms and GST Rules
* **Status**: Completed; rendered and verified
* **Priority**: High
* **Requirement**: Correct the consolidated All Invoice without changing the finished individual docket. Match the supplied text-only invoice form in landscape and portrait, apply GST identity/tax visibility rules, and prevent portrait/header/footer overlap.
* **Implementation**: Removed uploaded logos from the All Invoice only; added orientation-aware PDF view data, portrait typography, padding, and date formatting; made company GSTIN/SAC/PAN, customer GST/PAN, and GST columns conditional on applied/configured GST; added Taxable Amount and Gross Amount summary rows; formatted monetary cells consistently; and rebuilt the final footer as the required independent 60/40 terms-and-bank/signature layout. The docket template remains unchanged.
* **Files Modified**: `app/Services/PdfInvoiceGenerator.php`, `app/Services/InvoiceService.php`, `app/Views/pdfs/invoice.php`, `tests/PdfInvoiceLayoutTest.php`, `docs/*`
* **QA**: PHP lint, PHPUnit layout assertions, PDF metadata checks, text extraction, and rendered first/final-page inspection for 70-row A4 landscape and portrait fixtures.

---

### [CHG-027] Single-Header Multi-Page All Invoice PDF
* **Status**: Completed; rendered and verified
* **Priority**: High
* **Requirement**: Print the company/invoice header only once and continue overflow billing rows on the next page without restarting serial numbers.
* **Implementation**: Limited the TCPDF invoice header callback to page 1 and switched automatic continuation pages to the compact top margin. The billing table still repeats its column headings through `<thead>`, while the existing shipment-row sequence continues unchanged. Added GST/address-aware first-page height reservation so billing metadata cannot overlap the item headings, and tightened the table header padding/type size to prevent narrow labels from breaking mid-word.
* **Files Modified**: `app/Services/PdfInvoiceGenerator.php`, `app/Views/pdfs/invoice.php`, `docs/*`
* **QA**: PHP lint plus multi-page PDF text extraction and rendered page inspection.

---

### [CHG-026] Dynamic Docket Content Binding and Layout Review
* **Status**: Completed; rendered and verified
* **Priority**: High
* **Requirement**: Keep all customer docket values form/database-backed while matching the original ruled waybill's spacing and proportions.
* **Implementation**:
  - Added `shipment_items.contents` so **Said to Contain** round-trips independently from Part No.
  - Made the booking drawer field visible and required; removed the static `Goods` default and the fallback that stored it as `part_no`.
  - Removed unbacked Declared Weight, Form No., and Method of Pkg. substitutions from the customer docket.
  - Reduced the logo to an explicit print width, tightened the ruled form's vertical proportions, normalized cell padding, widened Payment, and kept Mode/Insured headers white.
  - Added an explicit continuous top rule to the Mode/weight table so its dividers connect cleanly beneath the phone row without a floating left-edge stub.
  - Locked the Mode/weight body cells to the same percentage widths as their headers, preventing TCPDF from drawing doubled or offset vertical separators.
  - Added compatibility for the legacy stored payment spelling `CREADIT` so Credit is selected correctly.
  - Removed hardcoded Pune and GST-rate defaults from the individual docket controller path.
* **Files Modified**: `app/Controllers/Logistics.php`, `app/Services/BookingService.php`, `app/Models/ShipmentItemModel.php`, `app/Views/logistics/booking_form.php`, `app/Views/pdfs/docket_pdf.php`, `app/Database/Migrations/2026-08-20-120000_AddContentsToShipmentItems.php`, `Docs/*`
* **QA**: PHP lint, migration, PHPUnit, PDF text extraction, and rendered PNG inspection.

---

### [CHG-025] Customer Docket Fidelity & End-to-End Dynamic Field Binding
* **Status**: Completed; rendered and verified
* **Priority**: High
* **Requirement**: Make the individual customer docket match the original printed shipper-copy waybill and ensure that company, customer, booking, shipment, tax, and charge values reach the PDF without sample-data fallbacks.
* **Implementation**:
  - Rebuilt `pdfs/docket_pdf.php` as a taller, grayscale, TCPDF-safe paper form with balanced header metadata, ruled shipper/consignee areas, an expanded mode/weight grid, and a full-height lower document/charge/signature matrix.
  - Follow-up visual refinement removed the gray fill from the Mode/weight and Insured headers, removed the Delivery Challan block, redistributed Invoice/Form/Dimension space evenly, and constrained uploaded logos so wide artwork cannot touch the waybill border.
  - Fixed `Logistics::streamDocketPdf()` so raw shipment data, resolved Customer Master addresses and contact numbers, docket number, print mode, and calculated GST data survive the shared invoice assembly step.
  - Removed hardcoded Pune, phone, email, payment, package, and goods defaults. Missing master data now prints blank instead of displaying false customer-facing information.
  - Preserved Full Print and Half Print behavior. Half Print suppresses monetary values while keeping operational shipment details.
* **Files Modified**: `app/Controllers/Logistics.php`, `app/Views/pdfs/docket_pdf.php`, `.gitignore`, `docs/*`
* **QA**: Full and Half fixtures each rendered as one A4 portrait page. Visual PNG inspection found no clipping, overlap, broken borders, or extra pages. Dynamic text extraction confirmed company, docket, shipper, consignee, route, package, and charge values; Half Print contained no monetary total. Existing PHPUnit suite remains green (8 tests, 30 assertions).

---

### [CHG-024] Dual Invoice System (AWB Invoice vs Docket Bill), Company Logo Upload & Meeting Layout Fixes
* **Status**: Completed; verified
* **Priority**: High
* **Requirement**: Provide explicit dual invoice formats (AWB Invoice for All Invoices summary billing, Docket Bill for individual shipper copy waybill print), add Company Logo upload in settings, render logo on PDF headers, compact line spacing, enforce conditional 18% GST rules, and support Full/Half print modes.
* **Implementation**:
  - Added migration `2026-08-20-063240_AddLogoToCompanies.php` adding `logo_path` and `logo_image` columns to `companies` table. Updated `CompanyModel` allowed fields.
  - Implemented Company Logo upload & removal in `CompanyController.php` and `company/settings.php` (`uploads/logos/`).
  - Updated `app/Views/pdfs/invoice.php` (AWB Invoice) to render company logo on top-left of header, reduced table cell padding for maximum row density per page, and rendered multi-line numbered Terms & Conditions.
  - Updated `app/Views/pdfs/docket_pdf.php` (Docket Bill) matching sample `1.jpeg`: top-left company logo, explicit Docket No resolution (`NO.` and `FORM NO.`), inner cell borders across all 4 bottom matrix columns, `PART NO.` (75%) and `QTY.` (25%) bordered header cells, clean TCPDF square bracket checkboxes (`[X]` / `[ ]`), `DELIVERY CHARGES` replacing Octroi, separate Payment Mode column (`CASH`, `CREDIT`, `TO-PAY`), manual Insured checkboxes, and manual physical stamp/signature area.
  - Wired `streamDocketPdf` in `Logistics.php` to pass `'docketNo' => $row['docket_no']` and `print_mode` into view data.
* **Files Modified**: `app/Database/Migrations/2026-08-20-063240_AddLogoToCompanies.php`, `app/Models/CompanyModel.php`, `app/Controllers/CompanyController.php`, `app/Views/company/settings.php`, `app/Views/pdfs/invoice.php`, `app/Views/pdfs/docket_pdf.php`, `app/Controllers/Logistics.php`, `app/Config/Routes.php`, `docs/*`
* **QA**: `php spark migrate` applied, `php -l` syntax validation passed across all modified PHP files with 0 errors.

---

### [CHG-023] Immutable Customer Rates, Exact Route Lookup, Safe Save Picker, Session Path Repair
* **Status**: Implemented; browser smoke verification pending
* **Priority**: High
* **Requirement**: Preserve customer-rate history, serialize concurrent rate changes, prevent generic route fallback, invoke the native PDF picker from a user action, and restore the configured file-session path.
* **Implementation**:
  - Added forward migration `2026-08-14-000004_VersionCustomerRates.php` with `is_active`, nullable `active_scope_key`, duplicate backfill/closure, and unique active-scope enforcement by company/customer/normalized O&D/category.
  - Added `CustomerRateService` so customer writes and rate synchronization share one transaction and lock the tenant-scoped customer row. Changed and removed rates are closed, never deleted; repeated same-rate saves are idempotent; stale competing writes return HTTP `409`.
  - Made supplied origin and destination exact, case-insensitive lookup criteria. Generic route rows are considered only when neither location is supplied; exact category still precedes blank category.
  - Customer Master edits only active rows and displays closed versions in a read-only Rate History table.
  - PDF generation now finishes before an explicit “Choose save location” action. Picker cancellation is informational, while unsupported/insecure contexts use normal download and retain All Downloads history.
  - Removed the blank `.env` `session.savePath` override so `Config\Session::$savePath` resolves to `writable/session`.
* **QA**: 8 PHPUnit tests / 30 assertions pass on disposable MySQL databases, including migration backfill, exact lookup, history, tenant isolation, idempotency, stale-connection conflict, and database uniqueness. PHP syntax, routes, and `git diff --check` pass. Chrome/Edge/unsupported-browser manual cases remain explicitly pending.

---

### [CHG-022] Invoice Booking Date, Save-As PDF Download, Location Wise Customer Item Rates
* **Status**: Completed
* **Priority**: High
* **Requirement**: Show booking-date based invoice dates in PDF output, ask the browser where to save generated invoice PDFs, and implement customer item rates by origin/destination.
* **Implementation**:
  - Consolidated invoice PDF period/date now derives from the selected shipment booking dates instead of the manually entered invoice-date field.
  - PDF generation can return JSON for the web UI; All Invoices fetches the generated PDF and uses the browser save-file picker when available, with normal browser download fallback.
  - Extended `customer_rates` with `origin` and `destination`, added Customer Master location-wise rate rows, added runtime lookup/save endpoints, and wired booking item entry to auto-fill Item Rate by Customer + Origin + Destination.
  - Save Item now warns when no master rate exists and asks whether a typed rate should be saved to Customer Master or used one time only. If a master rate exists but the typed value differs, it asks whether to update the master.
* **Files Modified**: `app/Database/Migrations/2026-08-14-000003_AddOriginDestinationToCustomerRates.php`, `app/Models/CustomerRateModel.php`, `app/Controllers/MasterController.php`, `app/Controllers/Logistics.php`, `app/Services/BookingService.php`, `app/Views/masters/_customer_form_fields.php`, `app/Views/masters/customers.php`, `app/Views/masters/customer_form.php`, `app/Views/logistics/all_invoices.php`, `app/Views/logistics/booking_form.php`, `app/Config/Routes.php`, `docs/*`
* **QA**: PHP syntax checks passed, `php spark routes` registered the rate endpoints, and `php spark migrate` applied the customer-rate origin/destination migration.

---

### [CHG-021] Invoice Master and Docket Prefix Master
* **Status**: Completed
* **Priority**: High
* **Requirement**: Add selectable GST/Non-GST invoice prefixes and selectable auto/manual docket prefixes, while warning users about invoice GST mismatches without blocking them.
* **Implementation**:
  - Added `invoice_templates` for company-scoped Invoice Master rows with Name, GST/Non-GST type, Prefix, and Active state.
  - Added `docket_series` for company-scoped Docket Master rows with Name, Prefix, Auto Increment/Manual mode, and Active state.
  - Added admin master screens and sidebar links for Invoice Master and Docket Master.
  - Booking item entry now lets staff select an Invoice Master beside the Invoice No field. The selected prefix is applied at shipment-entry time, keeping All Invoices focused on customer/date selection and download generation.
  - All Invoices detects GST/Non-GST mismatches from the saved shipment invoice prefix and still shows a proceed/cancel warning without asking staff to pick an Invoice Master there.
  - Booking item entry now lets staff select a docket prefix. Auto series generate using row-locked `docket_series.current_number`; manual series keep the docket input editable.
* **Concurrency Note**: Consolidated invoice numbers still lock `invoice_sequences` by company + financial year + prefix. Docket auto generation locks the selected `docket_series` row before incrementing, so parallel users do not receive the same generated docket number.
* **Files Modified**: `app/Database/Migrations/2026-08-14-000002_CreateInvoiceTemplatesAndDocketSeries.php`, `app/Models/InvoiceTemplateModel.php`, `app/Models/DocketSeriesModel.php`, `app/Controllers/MasterController.php`, `app/Controllers/Logistics.php`, `app/Services/InvoiceService.php`, `app/Views/masters/invoice_templates.php`, `app/Views/masters/docket_series.php`, `app/Views/logistics/all_invoices.php`, `app/Views/logistics/booking_form.php`, `app/Views/layout.php`, `docs/*`
* **QA**: PHP syntax checks passed, `php spark migrate` applied the new tables, and `php spark routes` registered the new master routes.

---

### [CHG-020] Booking Table Widths, Download Calendar Summary, Download Delete, Draft Recovery Hardening
* **Status**: Completed
* **Priority**: High
* **Requirement**: Prevent the Customer column from forcing unnecessary horizontal scroll, show month-wise invoice download/billing history, allow deleting saved invoice downloads, and reduce draft data-loss risk when Save Draft is rejected.
* **Implementation**:
  - Added wrapping Customer cells with constrained width in Dashboard Recent Bookings and All Bookings while preserving horizontal scroll when the whole column set exceeds the screen.
  - Added a month selector and month summary to All Downloads, including invoice count and saved billing amount.
  - Added `invoice_downloads.total_amount`, persisted consolidated invoice totals during PDF generation, and added a tenant-scoped delete route that removes the saved PDF and history row.
  - Kept local booking drafts after draft submits so shipment rows can still be recovered if server-side validation rejects a draft save.
* **Files Modified**: `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/dashboard.php`, `app/Views/logistics/all_invoices.php`, `app/Controllers/Logistics.php`, `app/Models/InvoiceDownloadModel.php`, `app/Config/Routes.php`, `app/Database/Migrations/2026-08-14-000001_AddTotalAmountToInvoiceDownloads.php`, `docs/*`
* **QA**: PHP syntax checks passed, `php spark routes` registered the delete route, `git diff --check` passed, and `php spark migrate` applied the new total amount migration.

---

### [CHG-019] Booking Autosave, Invoice Charge Overflow, Runtime Uniqueness, Default Bank Selection, Boxes Wording
* **Status**: Completed
* **Priority**: High
* **Requirement**: Make edited shipment-item saves persist immediately, preselect the default bank on invoice generation, validate duplicate AWB/docket values before final save, route only the first four active item charges into invoice charge columns with the remainder in Other Charges, and display package counts as Boxes instead of PCS/Pieces.
* **Implementation**:
  - Added AJAX JSON support to `Logistics::update()` so edit-mode item drawer saves can persist the whole booking without redirecting back to the booking list.
  - Updated `booking_form.php` so Save Item in edit mode triggers an autosave after updating `items_json`, and delete-item autosaves too.
  - Tightened runtime AWB/docket uniqueness feedback by checking docket uniqueness on input/change/blur and rechecking before accepting a drawer item.
  - Updated the default invoice layout to show only the first four active charge columns in client order and sum remaining active charges/custom charges into the Other Charges column.
  - Preselected the company default bank account in All Invoices while keeping `InvoiceService::resolveBankDetails()` as the backend fallback.
  - Changed visible PCS/Pieces labels in booking and logistics views to Boxes while preserving existing database field names.
* **Files Modified**: `app/Controllers/Logistics.php`, `app/Views/logistics/booking_form.php`, `app/Views/logistics/all_invoices.php`, `app/Views/logistics/view_booking.php`, `app/Views/logistics/manage_bookings.php`, `app/Views/logistics/search_results.php`, `app/Views/logistics/dashboard.php`, `app/Views/pdfs/invoice.php`, `docs/*`
* **QA**: PHP syntax checks passed for all modified PHP files; `git diff --check` passed.

---

### [CHG-018] Move Payment Type To Shipment Item Drawer
* **Status**: Completed
* **Priority**: High
* **Requirement**: Move Payment Type out of Consignment Details and into the Shipment Item add/edit drawer while preserving the same behavior and values discussed in the client meeting.
* **Implementation**:
  - Replaced the visible Tab 1 Payment Type dropdown with a hidden legacy `payment_type` field so existing booking-level backend compatibility remains intact.
  - Added Payment Type to the shipment item drawer and persisted each item value into `items_json` as `payment_type`.
  - Updated add/edit item flows, validation restore, Customer Master autofill, quick master creation, and manifest grid display to understand item-level Payment Type.
  - Aligned frontend item validation with the backend/client rule that pieces and actual weight may be zero.
* **Files Modified**: `app/Views/logistics/booking_form.php`, `docs/*`
* **QA**: PHP syntax check passed for the booking form.

---

## Latest Backend Change

### [CHG-017] Hide Zero-Value Invoice Charges
* **Status**: Completed
* **Priority**: Medium
* **Requirement**: Do not show surcharge columns on PDF invoices when the selected shipment set has zero/null values for those charges.
* **Implementation**:
  - Updated `InvoiceService::resolveActiveCharges()` to return only non-zero charge columns instead of falling back to default zero-value docket/pickup/delivery columns.
  - Updated `invoice.php` fixed PDF layouts to suppress zero-value surcharge columns for default, NX Logistics, and Brembo invoice formats while expanding the total amount column to keep table widths stable.
  - Kept row taxable/net totals unchanged; special NX/Brembo charge buckets now include custom charges where those amounts are part of the taxable row total.
* **Files Modified**: `app/Services/InvoiceService.php`, `app/Views/pdfs/invoice.php`, `docs/*`
* **QA**: PHP syntax checks passed for the service and PDF template.

---

### [CHG-016] Financial-Year Invoice Auto-Numbering
* **Status**: Completed
* **Priority**: High
* **Requirement**: Replace manual consolidated invoice numbering with company-scoped financial-year sequences such as `MA-26-27/001`.
* **Implementation**:
  - Added migration `2026-08-13-000002_AddInvoiceSequences.php` to create `invoice_sequences` and optional `companies.invoice_prefix`.
  - Added `InvoiceService::finalizeConsolidatedInvoiceNumber()` to reuse a previously finalized invoice number for reprints, or allocate the next company/prefix/FY number atomically with row locking.
  - Persisted finalized invoice number/date back to selected `shipment_items` before consolidated PDF streaming, so the "All Invoices" grid and reprints show the same number.
  - Prefix resolution now prefers `companies.invoice_prefix`, then a typed invoice prefix from the existing form field, then company-name initials.
* **Files Modified**: `app/Database/Migrations/2026-08-13-000002_AddInvoiceSequences.php`, `app/Services/InvoiceService.php`, `app/Controllers/Logistics.php`, `docs/*`
* **QA**: PHP syntax checks passed, `php spark routes` loaded the consolidated invoice routes, and the new migration applied successfully with `php spark migrate`.

---

### [CHG-015] Phase 1 Backend Gap Fixes — Item Metadata, Rate Snapshots, Remarks & Invoice Fallbacks
* **Status**: Completed
* **Priority**: High
* **Requirement**: Close backend-only gaps from the Phase 1 client tracker without reworking completed frontend flows or Phase 2 items.
* **Implementation**:
  - Added migration `2026-08-13-000001_AddPhase1BackendGapFields.php` to add `shipment_items.payment_type`, `shipment_items.material_category`, `bookings.remarks`, and a new `customer_rates` table for date-wise/category-wise customer rates.
  - Added `CustomerRateModel` with tenant-scoped lookup by customer, material category, and booking date.
  - Updated `BookingService` to persist item-level payment type/material category when provided, fallback to booking-level values, auto-fill blank/zero item rates from `customer_rates`, and enforce backend sanity validation where total item actual weight must not be below declared master AWB weight.
  - Relaxed `ShipmentItemModel` actual weight validation to allow zero because the client document explicitly permits zero pieces/actual weight.
  - Updated `InvoiceService` and `Logistics` PDF paths to prefer Customer Master address/GST/PAN details where available, while preserving existing shipment free-text fallbacks.
  - Added optional backend LR/docket clubbing support through `club_by_lr=1` or consolidated billing `billing_mode=docket`; default invoice behavior remains unchanged.
  - Added remarks aliasing so both `remarks` and legacy `narration` can print on invoices.
* **Follow-up Completed**: Financial-year invoice persistence and auto-number sequencing were completed separately in CHG-016.
* **Files Modified**: `app/Database/Migrations/2026-08-13-000001_AddPhase1BackendGapFields.php`, `app/Models/CustomerRateModel.php`, `app/Models/ShipmentItemModel.php`, `app/Models/BookingModel.php`, `app/Services/BookingService.php`, `app/Services/InvoiceService.php`, `app/Controllers/Logistics.php`, `app/Views/pdfs/invoice.php`, `docs/*`
* **QA**: PHP syntax checks passed for modified backend PHP files, `php spark routes` loaded successfully, and the new migration applied successfully with `php spark migrate`.

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

### [CHG-020] Versioned JSON Automation API
* **Status**: Completed
* **Priority**: High
* **Requirement**: Allow TestSprite backend tests to authenticate without browser redirects and create/capture real booking, tracking, and invoice IDs.
* **Implementation**: Added isolated `/api/v1` Basic/session authentication, explicit `X-Company-ID` tenant context, structured JSON errors, resource producer/consumer endpoints, PDF streaming, and test-artifact cleanup. Legacy browser authentication and CSRF behavior is unchanged.
* **Files Modified**: `app/Filters/ApiBasicAuthFilter.php`, `app/Controllers/Api/V1Controller.php`, `app/Config/Filters.php`, `app/Config/Routes.php`, and API/testing documentation.

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

### [CHG-014] CMS WordPress Tracking Component — Shipment Tracking History Table & View Switcher
* **Module**: WordPress Tracking Plugin (`ma-logistics-tracking`) & Public Tracking API (`TrackingController.php`)
* **Status**: ✅ **Implemented & Verified Live**
* **Context & Problem**: The user reported that the tracking table was missing on the public tracking page (`marlexpress.com/track-your-order/`). The public component was previously hiding `#ma-history-table-box` and only rendering a dot timeline, which showed "0 Events" because `api/track/` was querying history solely by `booking_id`.
* **Changes Made**:
  1. **Table View Primary Display**: Added the **Shipment Tracking History Table** matching the ERP drawer layout (`#`, `DATE`, `TIME`, `LOCATION`, `STATUS`, `REMARKS`) with styled status pills (`Arrived at Hub`, `In Transit`, `Delivered`, `Picked Up`), location pin icon, and receiver tag (`Receiver: {name}`).
  2. **View Switcher**: Added an intuitive switcher allowing users to toggle between **Table View** (default) and **Milestone Timeline**.
  3. **Tracking Query Fallback**: Enhanced `TrackingController.php` to query `tracking_history` by both `booking_id` and `awb_no`.
  4. **Data Sync**: Verified and synced tracking events for `PA1020150` in database so real-time milestone events populate accurately.
  5. **Deployment & Cache**: Deployed plugin v1.0.3 to `marlexpress.com`, purged LiteSpeed cache, and verified via Playwright automated testing with full visual confirmation.

### [CHG-015] Delivery Status & Date-Time Display + ERP Tracking Drawer Fix
* **Module**: ERP Tracking Drawer (`pod_tracking_drawer.php`), Public Tracking API (`TrackingController.php`), & WordPress Tracking Plugin (`ma-logistics-tracking` v1.0.4)
* **Status**: ✅ **Implemented & Verified Live**
* **Context & Problem**:
  1. When updating AWB `PA1020150` to `Delivered` in the ERP tracking drawer, the client-side JavaScript threw a blocking error: *"Status Date & Time must be less than Expected Delivery Date & Time"*, preventing staff from submitting delivery updates or any event happening on/after expected delivery time.
  2. On the public tracking page (`marlexpress.com/track-your-order/`), the left-side Consignment Details table was not displaying `Expected Delivery`, `Delivered Date & Time`, and `Current Status` was not showing `Delivered`.
* **Changes Made**:
  1. **ERP Drawer Fix**: Removed erroneous JavaScript validation check in `app/Views/logistics/pod_tracking_drawer.php` (`eventDateTime >= expDateTime`), allowing valid delivery and delayed milestone submissions. Deployed to staging server.
  2. **Backend API Enhancements**: Updated `TrackingController.php` on `granthinfotech.online`:
     - Dynamically computes `currentStatus` prioritizing `$history[0]['status']` if available, falling back to `$booking['status']`.
     - Extracts `delivery_date` and `delivery_time` directly from `Delivered` history milestones.
     - Extracts `expected_delivery_date` and `expected_delivery_time` from booking data.
  3. **Plugin v1.0.4 Frontend**: Updated `wp-plugin/ma-logistics-tracking/assets/js/ma-tracking.js` and `ma-logistics-tracking.php` on `marlexpress.com`:
     - Populates `val-status` as bold green `Delivered` (`#15803d`).
     - Populates `val-expected-delivery` (e.g. `2026-09-04 at 18:00`).
     - Populates `val-delivery-datetime` (e.g. `2026-09-04 at 15:45`) in green bold.
     - Positions top route airplane icon at 100% destination upon delivery.
  4. **Deployment & Verification**: Deployed v1.0.4 to `marlexpress.com`, purged LiteSpeed cache, and verified with Playwright test. All fields confirmed active.

