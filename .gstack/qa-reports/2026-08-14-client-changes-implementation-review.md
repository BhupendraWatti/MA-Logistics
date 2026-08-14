# M.A. Logistics ERP Client Changes Implementation Review

Date: 2026-08-14
Mode: Report-only QA, DevEx, Design, migration, and concurrency review

## Sources Used

- `docs/*.md`
- `C:\Users\bhupe\Downloads\CHANGES REQUIRED.docx`
- `MALogistic phase 1 issues\12 Aug 2026\*.md`
- `graphify-out\GRAPH_REPORT.md`
- `graphify-out\graph.json`

## Graphify Update

- Ran `graphify update .`
- Updated graph: 593 nodes, 579 edges, 167 communities.
- Main graph hubs for this review: `MasterController`, `Logistics`, `InvoiceService`, `BookingService`, `CustomerRateModel`, `InvoiceDownloadModel`.
- Graph query surfaced the new nodes `AddOriginDestinationToCustomerRates`, `CustomerRateModel`, and `InvoiceDownloadModel`.

## Verification Commands

- PASS: `php -l` on touched controllers, services, models, views, and migrations.
- PASS: `php spark routes` shows new invoice download, customer rate, invoice master, and docket master routes.
- PASS: `php spark migrate:status` shows migrations `2026-08-14-000001`, `000002`, and `000003` applied.
- PASS: `git diff --check` found no whitespace errors, only CRLF warnings.
- BLOCKED: `vendor\bin\phpunit --colors=never` was denied by Windows; `php vendor\bin\phpunit --colors=never` only printed PHPUnit usage because the project has no default PHPUnit configuration/test target.
- BLOCKED: `gstack-model-benchmark` dry-run was denied by Windows, so no cross-model benchmark was run.
- BLOCKED: live HTTP smoke test returned 500 because session save path `D:\xampp\tmp` is not writable by this PHP process.

## Client Change Checklist

| Area | Status | Evidence |
|---|---|---|
| Customer column too wide on Dashboard/All Bookings | Implemented | `manage_bookings.php` and `dashboard.php` set `.customer-cell` wrapping with `width: 40%`, max width, and min width. |
| All Downloads monthly calendar, billing total, user, delete | Implemented with caveat | `all_invoices.php` has `input type="month"`, amount/count/user/delete UI; `Logistics::ajaxInvoiceDownloads()` and `deleteInvoiceDownload()` are registered. |
| Invoice Master GST/Non-GST prefix | Implemented | `invoice_templates` migration/model/master screen; booking item drawer has Invoice Master dropdown; All Invoices no longer exposes the dropdown. |
| Docket Master auto/manual prefix | Implemented | `docket_series` migration/model/master screen; booking item drawer supports selected series and auto/manual behavior. |
| Invoice mismatch warning should warn but allow proceed | Implemented | All Invoices detects saved invoice prefix type from shipment invoice number and shows proceed/cancel warning. |
| Invoice PDF date should be booking date | Implemented | `Logistics::generateConsolidatedInvoice()` derives invoice date/period from selected shipment booking dates; `InvoiceService::buildShipmentRows()` uses `booking_date` before `invoice_date`. |
| Download should ask save location and remember last folder | Partially implemented | `all_invoices.php` uses `window.showSaveFilePicker` with stable id `ma-logistics-invoices`; unsupported browsers fall back to direct download, so this is browser-dependent. |
| Customer item rate by Origin/Destination | Implemented with risk | Customer Master has origin/destination dropdowns from lookup master; booking drawer looks up/saves item rate by customer + route. See concurrency/history findings below. |
| Origin/Destination dropdowns in Customer Master rates | Implemented | `_customer_form_fields.php` uses `<select name="rate_origin[]">` and `<select name="rate_destination[]">` populated from lookup values. |
| Save Draft preserves shipment item data | Implemented locally; server draft unverified | Booking form stores local draft on input/change and does not clear local draft on Draft submit; live server test blocked by session path. |
| Edit item Save Item autosaves booking | Implemented | `saveItemToGrid()` calls `autosaveBookingChanges()` in edit mode. |
| Runtime AWB/Docket uniqueness | Implemented | `Logistics::checkAwbUnique()` and `MasterController::checkDocketUnique()` routes exist; drawer rechecks docket before accepting item. |
| PCS changed to Boxes in visible booking/invoice screens | Mostly implemented | Main visible labels show Boxes. Some internal JS ids/variables still use `pcs`, but those are not visible UI. |

## Findings

### P1: Customer rate history is not preserved in Customer Master edits

`MasterController::syncCustomerRates()` deletes all `customer_rates` rows for the customer and reinserts only the submitted rows. This conflicts with the client/document requirement that previous rate entries remain as old entries and new rates become second entries/date-wise history.

Evidence:
- `app\Controllers\MasterController.php:186-221`
- `docs\video_to_action_explanations.md` and `CHANGES REQUIRED.docx`: old rate/bill should remain stored.

Risk: Editing Customer Master can remove historical rate rows and make future lookups unable to resolve old date ranges.

### P1: Customer rate save/update is not concurrency-safe

`saveCustomerRate()` performs read-then-update/insert without a transaction, row lock, or unique key for `(company_id, customer_id, origin, destination, material_category, effective_from/effective_to)`. Two users can save the same route simultaneously and create duplicates or overwrite the wrong row.

Evidence:
- `app\Controllers\MasterController.php:296-328`
- `app\Database\Migrations\2026-08-14-000003_AddOriginDestinationToCustomerRates.php:40` adds a non-unique lookup index only.

Contrast: invoice numbers and auto docket numbers use `FOR UPDATE` locks.

### P2: Location-wise rate lookup falls back to generic origin/destination rows

`CustomerRateModel::findRate()` allows `origin IS NULL OR origin = ''` and `destination IS NULL OR destination = ''` when origin/destination are provided. This is useful fallback behavior, but it means a route-specific miss may silently use a generic rate instead of showing the "rate not filled for this O&D" prompt the client requested.

Evidence:
- `app\Models\CustomerRateModel.php:61-74`

### P2: Save picker cannot be guaranteed in all browsers

The app uses the File System Access API (`window.showSaveFilePicker`), which should ask the user where to save in Chromium browsers and may remember the picker context through the stable id. Firefox/Safari and older browsers will use normal download behavior.

Evidence:
- `app\Views\logistics\all_invoices.php:653-675`

### P2: Runtime QA is blocked by environment session config

The local app boot returns 500 before login because `D:\xampp\tmp` is not writable by the PHP process.

Evidence:
- `writable\logs\log-2026-08-14.log`

## Design Review Notes

- Customer column wrapping is a good operational-table choice: it keeps dense tables scan-friendly while preserving horizontal scroll when the full column set needs it.
- All Downloads is functional, but the table stays `text-nowrap`; long customer/bill-to names may still force horizontal scroll in the downloads tab.
- Customer Master rate UI uses a card inside an existing form; acceptable for a repeated editor, but it should stay compact because the master form is already dense.
- `shadcn` is not applicable: the repo has no `components.json` and uses CodeIgniter/PHP with Bootstrap/vanilla JS.

## DevEx Notes

- Docs are updated with CHG-019 through CHG-022 and matching test cases TC024 through TC036.
- PHPUnit dependency exists, but no default test configuration was found, so `php vendor\bin\phpunit` cannot run a suite without explicit targets/config.
- The session save path issue prevents any developer from running reliable local browser QA unless they have a writable `D:\xampp\tmp`.

## Recommended Next Fixes

1. Preserve customer-rate history instead of deleting all rows on Customer Master update. Close the old row with `effective_to` and insert the new row with `effective_from`.
2. Add a unique constraint or transactional lock for active route rates to prevent duplicate route rows under parallel users.
3. Decide whether route lookup should allow generic fallback; if the client expects a prompt when exact O&D is missing, require exact origin/destination in booking item lookup.
4. Move local session save path to `writable/session` or ensure `D:\xampp\tmp` is writable so browser QA can run.
5. Add a small PHPUnit or CodeIgniter feature-test config for invoice sequence, docket sequence, customer-rate lookup/save, and invoice-download deletion.

