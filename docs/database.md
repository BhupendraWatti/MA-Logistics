# M.A. Logistics ERP — Database Architecture & Schema

This document details all database tables, columns, indexes, foreign key relationships, and related CodeIgniter models in M.A. Logistics ERP.

## Current Backend Additions

### `customer_rates`
* **Purpose**: Immutable, tenant-scoped customer rate versions by origin/destination, date range, and optional material category. `BookingService` uses the version applicable on the booking date; Customer Master changes close old versions rather than deleting them.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Foreign Key**: `company_id` to `companies.id`
* **Columns**: `id`, `company_id`, `customer_id`, `customer_name`, `origin`, `destination`, `material_category`, `effective_from`, `effective_to`, `rate`, `is_active`, `active_scope_key`, `created_at`, `updated_at`
* **Indexes**: `idx_customer_rates_lookup (company_id, customer_name, material_category, effective_from)`, `idx_customer_rates_od_lookup (company_id, customer_name, origin, destination, effective_from)`, unique `uq_customer_rates_active_scope (company_id, customer_id, active_scope_key)`
* **Invariant**: Exactly one active row may exist for a normalized customer/O&D/category scope. Active rows hold the SHA-256 `active_scope_key`; closed rows set it to `NULL`, allowing unlimited immutable history.
* **Backfill**: Migration `2026-08-14-000004_VersionCustomerRates.php` keeps the newest `effective_from`, then highest `id`, active and closes older duplicates without deleting them.
* **Related Model**: `app/Models/CustomerRateModel.php`

### `invoice_sequences`
* **Purpose**: Tenant-scoped financial-year invoice sequence tracker for consolidated invoice finalization. `InvoiceService` locks the active company/prefix/FY row, increments `last_number`, and writes the formatted invoice number to selected shipment rows.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Foreign Key**: `company_id` to `companies.id`
* **Columns**: `id`, `company_id`, `financial_year`, `prefix`, `last_number`, `created_at`, `updated_at`
* **Unique Key**: `uq_invoice_sequence_scope (company_id, financial_year, prefix)`

### `invoice_templates`
* **Purpose**: Company-scoped Invoice Master rows that classify invoice prefixes as GST or Non-GST.
* **Columns**: `id`, `company_id`, `name`, `gst_type`, `prefix`, `is_active`, `created_at`, `updated_at`
* **Unique Key**: `uq_invoice_template_prefix (company_id, prefix)`

### `docket_series`
* **Purpose**: Company-scoped Docket Master prefix policies. Auto rows allocate docket numbers with a locked `current_number`; manual rows only guide user entry.
* **Columns**: `id`, `company_id`, `name`, `prefix`, `entry_mode`, `current_number`, `is_active`, `created_at`, `updated_at`
* **Unique Key**: `uq_docket_series_prefix (company_id, prefix)`

### Added Columns
* `shipment_items.payment_type` and `shipment_items.material_category` store item-level billing/category metadata when the form or API submits it; if omitted, `BookingService` falls back to booking-level `payment_type` and `material_category`.
* `shipment_items.contents` stores the booking drawer's **Said to Contain** value independently from `part_no`, so customer dockets never substitute a description into the Part No. box.
* `bookings.remarks` stores explicit client remarks while preserving legacy `bookings.narration` compatibility.
* `companies.invoice_prefix` optionally overrides the generated invoice prefix; if blank, `InvoiceService` derives the prefix from the typed invoice field or company name.

---

## 1. Table Inventory

### A. `users`
* **Purpose**: User authentication credentials and role permissions.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Columns**: `id`, `company_id`, `username`, `password`, `email`, `role`, `can_create`, `can_edit`, `can_delete`, `created_at`, `updated_at`
* **Related Model**: `app/Models/UserModel.php`

### B. `companies`
* **Purpose**: Multi-tenant company context and profile configuration.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Columns**: `id`, `company_name`, `company_code`, `gst_no`, `pan_no`, `sac_code`, `address`, `cgst_rate`, `sgst_rate`, `igst_rate`, `terms_conditions`, `signature_image`, **`logo_path`** (VARCHAR(255), NULL — relative path to uploaded company logo image, e.g. `uploads/logos/logo_1.png`), **`logo_image`** (VARCHAR(255), NULL — original uploaded filename), `created_at`
* **Related Model**: `app/Models/CompanyModel.php`

### C. `bookings`
* **Purpose**: Core Air Waybill (AWB) consignment header records.
* **Primary Key**: `id` (BIGINT, AUTO_INCREMENT)
* **Foreign Keys**: `company_id` $\rightarrow$ `companies(id)`, `customer_id` $\rightarrow$ `customer_master(id)`
* **Columns**: `id`, `company_id`, `awb_no`, `booking_date`, `branch_id`, `customer_id`, `customer_name`, `consignor_name`, `consignee_name`, `origin`, `destination`, `payment_type`, `mode`, `carrier_id`, `driver_id`, `current_status`, `gst_applied`, `chargeable_weight_manual`, `total_amount`, `created_at`, `updated_at`
* **Indexes**: `PRIMARY (id)`, `idx_bookings_company_id (company_id, id DESC)`, `idx_bookings_awb (awb_no)`
* **Related Model**: `app/Models/BookingModel.php`

### D. `shipment_items`
* **Purpose**: Child package details belonging to a booking record.
* **Primary Key**: `id` (BIGINT, AUTO_INCREMENT)
* **Foreign Key**: `booking_id` $\rightarrow$ `bookings(id)` ON DELETE CASCADE
* **Columns**: `id`, `booking_id`, `docket_no`, `part_no`, **`contents`**, `invoice_date`, `pieces`, `length`, `width`, `height`, `actual_weight`, `volumetric_weight`, `chargeable_weight`, `misc_charges`, `misc_charges_name`, **`custom_charges`** (TEXT, NULL — JSON array of `{label, value}` pairs for AWB-protocol-specific charges)
* **Related Model**: `app/Models/ShipmentItemModel.php`

### E. `sales_charges`
* **Purpose**: Child financial sales rate and surcharge entries per booking.
* **Primary Key**: `id` (BIGINT, AUTO_INCREMENT)
* **Foreign Key**: `booking_id` $\rightarrow$ `bookings(id)` ON DELETE CASCADE
* **Columns**: `id`, `booking_id`, `sales_rate`, `tsp_inbound`, `tsp_outbound`, `tcp_charge`, `utility_charge`, `xray_charge`, `ado_charge`, `awb_agent_fee`, `awb_carrier_fee`, `admin_charge`, `delivery_order_charge`, `inbound_handling`, `inbound_storage`, `outbound_storage`, `misc_charge`, `total_taxable`, `cgst_amount`, `sgst_amount`, `igst_amount`, `net_payable`, **`custom_charges`** (TEXT, NULL — JSON array of `{label, value}` pairs for global booking-level surcharges, e.g. Super Charges)
* **Related Model**: `app/Models/SalesChargeModel.php`

### F. `customer_master`
* **Purpose**: Customer registry, credit terms, and GST identifiers.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Columns**: `id`, `company_id`, `name`, `code`, `gst_no`, `gst_state`, `billing_address`, `payment_type`, `currency`, `created_at`
* **Related Model**: `app/Models/CustomerModel.php`

### G. `transporters`, `drivers`, `airlines`
* **Purpose**: Master operational asset registries.
* **Primary Keys**: `id`
* **Related Models**: `TransporterModel`, `DriverModel`, `AirlineModel`

### H. `lookup_values`
* **Purpose**: Standardized dropdown choice repository.
* **Columns**: `id`, `company_id`, `lookup_type`, `lookup_value`
* **Indexes**: `idx_lookup_type (company_id, lookup_type)`
* **Related Model**: `app/Models/LookupValueModel.php`

### I. `audit_logs`
* **Purpose**: Tracks manual overrides of chargeable weight.
* **Columns**: `id`, `booking_id`, `user_id`, `old_weight`, `new_weight`, `reason`, `created_at`
* **Related Model**: `app/Models/AuditLogModel.php`

### J. `tracking_status_logs`
* **Purpose**: History of transit events and proof-of-delivery uploads.
* **Columns**: `id`, `booking_id`, `current_location`, `status`, `event_date`, `event_time`, `remarks`, `proof_image`, `created_at`
* **Related Model**: `app/Models/TrackingStatusLogModel.php`
