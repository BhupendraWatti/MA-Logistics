# M.A. Logistics ERP — API Documentation

This document describes all public and internal JSON API endpoints provided by the M.A. Logistics ERP application.

---

## 1. Authentication & Base URL
* **Base URL**: `http://localhost:8080/` (Dev) / Staging / Production Domain.
* **Automation JSON API**: `/api/v1/*` accepts HTTP Basic Auth or the session returned by `POST /api/v1/auth/login`. Company-scoped calls require `X-Company-ID`; invalid requests return JSON `401/422/404` responses instead of redirects.
* **Internal APIs**: All endpoints under `/api/masters/*` and `/tracking/*` require a valid session cookie (`ci_session`).
* **Public APIs**: Tracking endpoint `/api/track/*` is publicly accessible and enables `Access-Control-Allow-Origin: *`.
* **Public UI**: `GET /track` and `GET /tracking` render the public tracking page. `GET /` remains the authenticated ERP entry point.

The `/api/v1` namespace is CSRF-exempt because it has its own HTTP authentication filter. The exemption does not apply to legacy browser form routes.

### Versioned automation resources

- Auth/company: `/api/v1/auth/login`, `/api/v1/auth/logout`, `/api/v1/companies`, `/api/v1/companies/select`
- Masters: company, customers, rate lookup, transporters, drivers, airlines, and lookups under `/api/v1/masters`
- Bookings: create/search/read/update/delete, AWB check, docket PDF, and tracking history under `/api/v1/bookings`
- Tracking: create/update/delete under `/api/v1/tracking`
- Consolidated invoices: generate/download/delete under `/api/v1/invoices`

All resource URLs use positive integer IDs returned by producer responses. Placeholder strings and UUIDs are not valid resource identifiers.

---

## 2. Public API Endpoints

### A. Public Shipment Tracking
Retrieve current status and history timeline of a consignment by AWB or Docket number.

* **URL**: `GET /api/track/{awb_or_docket_no}`
* **Access**: Public (CORS Enabled)
* **Parameters**: `awb_or_docket_no` (string)
* **Client contract**: Supply the identifier as the only path value. A UI may send `?type=awb` or `?type=docket` as an optional hint, but must not add the type as another path segment.
* **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": {
      "booking": {
        "awb_no": "25611541",
        "current_status": "Out for Delivery",
        "booking_date": "2026-06-03 12:00:00",
        "consignee_name": "Acme Warehouse, Bhiwandi",
        "destination": "DEL , New Delhi",
        "total_pieces": "12",
        "delivery_date": "-",
        "delivery_time": "-",
        "receiver_name": "-",
        "forwarding_no": "EWAY-998822",
        "expected_delivery_date": "2026-06-05",
        "expected_delivery_time": "18:00"
      },
      "history": [
        {
          "date": "2026-06-03",
          "time": "17:30:00",
          "location": "Delhi Hub",
          "activity": "Out for Delivery",
          "remarks": "Assigned to delivery agent",
          "receiver_name": "John Doe"
        }
      ]
    }
  }
  ```
* **Error Response (404 Not Found)**:
  ```json
  {
    "status": "error",
    "message": "No tracking records found for AWB/Docket: 9999999"
  }
  ```

* **Client Consumers**:
  1. **Built-in ERP Public UI**: `GET /track` and `GET /tracking` (renders `app/Views/public_track.php`).
  2. **WordPress CMS Plugin (`[ma_tracking]`)**: Embedded on marketing site `https://website.granthinfotech.online/track-your-order/`. Calls this endpoint asynchronously via Vanilla JS, parses booking/history payloads, supports URL deep-linking (`?awb={awb_no}`), and renders responsive consignment metadata and vertical milestone event timelines.


---

## 3. Internal Master Data APIs (JSON)

### Customer Rate Endpoints

* `POST /masters/customers/rate-lookup` accepts `customer_name`, `origin`, `destination`, `material_category`, and `booking_date`. When O&D are supplied both must match exactly, case-insensitively; an exact-route miss returns HTTP 200 with `status: "success"` and `found: false`. Category-specific rows precede blank-category rows.
* `POST /masters/customers/rate-save` accepts the existing fields `customer_name`, `origin`, `destination`, `material_category`, `rate`, and `rate_id`. Success preserves the existing JSON fields and returns `id` for the newly active version. A stale competing value returns HTTP 409 with `status: "error"`, a reload message, and `csrf_hash`; a repeated same-rate request returns the existing active version id.
* Both endpoints are session/company scoped and cannot read, lock, or mutate another company’s customer/rate rows.

### Endpoints
1. `GET /api/masters/customers` — Get all active customers.
2. `GET /api/masters/customers/{id}` — Get customer profile:
   ```json
   {
     "id": "1",
     "company_id": "1",
     "name": "Acme Pharmaceuticals",
     "code": "ACME01",
     "payment_type": "Credit",
     "currency": "INR",
     "gst_no": "27AAAAA1111A1Z1"
   }
   ```
3. `GET /api/masters/transporters` — Get transporters list.
4. `GET /api/masters/drivers` — Get drivers list.
5. `GET /api/masters/airlines` — Get airlines list.
6. `GET /api/masters/lookup/{type}` — Get lookup options (`type` = `origin`, `destination`, `mode`, `material_type`, `material_category`, `payment_type`).
7. `GET /api/masters/company-gst` — Returns active company tax configuration:
   ```json
   {
     "cgst_rate": "9.00",
     "sgst_rate": "9.00",
     "igst_rate": "18.00"
   }
   ```

---

## 4. Tracking & POD Operations

### Endpoints
1. `GET /tracking/history/{booking_id}` — Get tracking history array for internal views.
2. `POST /tracking/save` — Save or update tracking log (`multipart/form-data`).
   * Parameters: `id` (optional), `booking_id`, `awb_no`, `current_location`, `status`, `event_date`, `event_time`, `remarks`, `proof_image` (file upload).
   * Response:
     ```json
     {
       "status": "success",
       "message": "Tracking added successfully"
     }
     ```
3. `POST /tracking/delete/{id}` — Delete tracking log entry. Updates main booking status to next remaining latest log event.
