# M.A. Logistics ERP — Database Architecture & Schema

This document details all database tables, columns, indexes, foreign key relationships, and related CodeIgniter models in M.A. Logistics ERP.

## Current Backend Additions

### `customer_rates`
* **Purpose**: Tenant-scoped customer rate snapshots by date range and material category. `BookingService` uses this table to auto-fill blank/zero shipment item rates at save time while preserving historical saved rates on existing shipment rows.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Foreign Key**: `company_id` to `companies.id`
* **Columns**: `id`, `company_id`, `customer_id`, `customer_name`, `material_category`, `effective_from`, `effective_to`, `rate`, `created_at`, `updated_at`
* **Index**: `idx_customer_rates_lookup (company_id, customer_name, material_category, effective_from)`
* **Related Model**: `app/Models/CustomerRateModel.php`

### `invoice_sequences`
* **Purpose**: Tenant-scoped financial-year invoice sequence tracker for consolidated invoice finalization. `InvoiceService` locks the active company/prefix/FY row, increments `last_number`, and writes the formatted invoice number to selected shipment rows.
* **Primary Key**: `id` (INT, AUTO_INCREMENT)
* **Foreign Key**: `company_id` to `companies.id`
* **Columns**: `id`, `company_id`, `financial_year`, `prefix`, `last_number`, `created_at`, `updated_at`
* **Unique Key**: `uq_invoice_sequence_scope (company_id, financial_year, prefix)`

### Added Columns
* `shipment_items.payment_type` and `shipment_items.material_category` store item-level billing/category metadata when the form or API submits it; if omitted, `BookingService` falls back to booking-level `payment_type` and `material_category`.
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
* **Columns**: `id`, `company_name`, `company_code`, `gst_no`, `pan_no`, `sac_code`, `address`, `cgst_rate`, `sgst_rate`, `igst_rate`, `terms_conditions`, `signature_image`, `created_at`
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
* **Columns**: `id`, `booking_id`, `docket_no`, `part_no`, `invoice_date`, `pieces`, `length`, `width`, `height`, `actual_weight`, `volumetric_weight`, `chargeable_weight`, `description`, `misc_charges`, `misc_charges_name`, **`custom_charges`** (TEXT, NULL — JSON array of `{label, value}` pairs for AWB-protocol-specific charges)
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
