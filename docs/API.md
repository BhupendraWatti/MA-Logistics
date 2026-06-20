# MA Logistics ERP — API Documentation

This document describes the JSON and public API endpoints available in the MA Logistics ERP application.

---

## 1. Authentication & Base URL
* **Base URL:** `http://localhost:8080/` (Local development) or the staging/production domain.
* **Internal APIs:** All internal endpoints located under `api/masters/*` and `tracking/*` require a valid logged-in session cookie (`ci_session`). If called without an active session, they will redirect to the `/login` page (`302 Found`).
* **Public APIs:** The tracking API endpoint `api/track/*` is publicly accessible and has CORS enabled (`Access-Control-Allow-Origin: *`) to allow integration with external tracking portals.

---

## 2. Public API Endpoints

### A. Public Shipment Tracking
Retrieve the current status and tracking logs of a shipment by its AWB number or Docket number.

* **URL:** `/api/track/{awb_or_docket_no}`
* **Method:** `GET`
* **Access:** Public (CORS Enabled)
* **URL Parameters:**
  * `awb_or_docket_no` (string) — The unique Air Waybill (AWB) number or Docket number.
* **Success Response (200 OK):**
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
        },
        {
          "date": "2026-06-03",
          "time": "08:00:00",
          "location": "Mumbai Airport",
          "activity": "In Transit",
          "remarks": "In flight to Delhi",
          "receiver_name": ""
        }
      ]
    }
  }
  ```
* **Error Response (404 Not Found / 400 Bad Request):**
  ```json
  {
    "status": "error",
    "message": "No tracking records found for AWB/Docket: 9999999"
  }
  ```

---

## 3. Internal Master Data APIs (JSON)
These endpoints are used internally (e.g., inside the Shipment Entry / Booking Form) to fetch autocomplete data and dynamic form presets.

### A. Get Customers
Get a list of all active customers for the selected company.
* **URL:** `/api/masters/customers`
* **Method:** `GET`
* **Response:** Array of customer objects.

### B. Get Customer Details
Get details (code, payment terms, currency) for a specific customer.
* **URL:** `/api/masters/customers/{id}`
* **Method:** `GET`
* **Response:**
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

### C. Get Transporters
Get all active transporters for the selected company.
* **URL:** `/api/masters/transporters`
* **Method:** `GET`

### E. Get Drivers
Get all active drivers for the selected company.
* **URL:** `/api/masters/drivers`
* **Method:** `GET`

### F. Get Airlines
Get all active airlines in the master database.
* **URL:** `/api/masters/airlines`
* **Method:** `GET`

### G. Get Lookup Values
Get standardized lookup dropdown list choices by lookup type category.
* **URL:** `/api/masters/lookup/{type}`
* **Method:** `GET`
* **URL Parameters:**
  * `type` (string) — One of: `origin`, `destination`, `mode`, `material_type`, `material_category`, `payment_type`.

### H. Get Company GST Configuration
Get the default Tax percentages (CGST, SGST, IGST) of the currently active company.
* **URL:** `/api/masters/company-gst`
* **Method:** `GET`
* **Response:**
  ```json
  {
    "cgst_rate": "9.00",
    "sgst_rate": "9.00",
    "igst_rate": "18.00"
  }
  ```

---

## 4. Tracking & POD (Proof of Delivery) Operations

### A. Get Booking Tracking History
Retrieve all tracking events logged for a specific booking.
* **URL:** `/tracking/history/{booking_id}`
* **Method:** `GET`
* **Response:**
  ```json
  {
    "status": "success",
    "booking": { ... },
    "data": [
      {
        "id": "5",
        "booking_id": "4",
        "current_location": "Bhiwandi Warehouse",
        "status": "In Transit",
        "event_date": "2026-06-03",
        "event_time": "14:20:00",
        "remarks": "Dispatched via vehicle MH-43-1234",
        "proof_image": null
      }
    ]
  }
  ```

### B. Save Tracking Event
Add a new tracking log event or update an existing one. If the status is "Delivered", a Proof of Delivery (POD) signature or delivery image upload is accepted.
* **URL:** `/tracking/save`
* **Method:** `POST`
* **Content-Type:** `multipart/form-data`
* **Form Parameters:**
  * `id` (integer, optional) — Pass to update an existing tracking record.
  * `booking_id` (integer, required)
  * `awb_no` (string, required)
  * `current_location` (string, required)
  * `status` (string, required) — e.g. "In Transit", "Out for Delivery", "Delivered".
  * `event_date` (string, required) — format `YYYY-MM-DD`.
  * `event_time` (string, required) — format `HH:MM:SS`.
  * `remarks` (string, optional)
  * `proof_image` (file, optional) — JPEG/PNG proof of delivery upload.
* **Response:**
  ```json
  {
    "status": "success",
    "message": "Tracking added successfully"
  }
  ```

### C. Delete Tracking Event
Delete a tracking log history entry. The booking's main status will automatically sync to the next remaining latest tracking event status, or revert to "Billed" if no events remain.
* **URL:** `/tracking/delete/{id}`
* **Method:** `POST`
* **Response:**
  ```json
  {
    "status": "success",
    "message": "Record deleted successfully"
  }
  ```
