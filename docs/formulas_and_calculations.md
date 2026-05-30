# M.A. Logistics ERP - Formulas and Calculation Specifications

This document defines the mathematical formulas, rules, and computational logic used across the **M.A. Logistics ERP** system to calculate chargeable weights, taxable amounts, taxes, invoice totals, and the behavior of the **GST Applied** toggle.

---

## 1. Shipment Item Calculations

For each shipment item row in the booking grid, the system computes the volumetric weight and resolves the final chargeable weight.

### A. Volumetric Weight Formula
Volumetric weight translates the physical space (dimensions) occupied by a package into an equivalent weight value.

$$\text{Volumetric Weight (kg)} = \frac{\text{Length (cm)} \times \text{Width (cm)} \times \text{Height (cm)}}{\text{Volumetric Formula (default: 6000)}}$$

*Note: The volumetric formula denominator (default `6000`) is configurable at the top of the Item Manifest tab.*

### B. Chargeable Weight Formula (Per Item)
The chargeable weight is the billing weight for an individual item row. It selects the higher of the actual weight or volumetric weight:

$$\text{Chargeable Weight (kg)} = \max(\text{Actual Weight}, \text{Volumetric Weight})$$

---

## 2. Booking Totals & Surcharges (Frontend Screen)

On the **Charges** tab, the system calculates the base freight charge, adds surcharges, applies GST (if enabled), and calculates the final Net Payable.

### A. Base Freight Charge (Sales Cost)
$$\text{Base Freight Charge} = \text{Sales Rate} \times \text{Total Chargeable Weight}$$

### B. Total Taxable Amount
$$\text{Total Taxable Amount} = \text{Base Freight Charge} + \sum \text{Surcharges}$$

Where **Surcharges** are the sum of all inputs with the `.calc-surcharge` class on the form:
* Inbound TSP Charge
* Outbound TSP Charge
* TCP Surcharge
* Utility Surcharge
* X-Ray Surcharge
* ADO (Delivery Order Agent)
* AWB Fees (Agent)
* AWB Fees (Carrier)
* Admin Charges
* Delivery Order Surcharge
* Inbound Handling Charges
* Inbound Storage Charges
* Outbound Storage Charges
* Misc. Charges

### C. GST Tax Computations
Taxes are calculated based on the active rates in the selected **Company Master** (retrieved dynamically into Javascript):
* **CGST**: Central Goods and Services Tax (default: $9\%$)
* **SGST**: State Goods and Services Tax (default: $9\%$)
* **IGST**: Integrated Goods and Services Tax (default: $0\%$)

$$\text{CGST Amount} = \text{round}\left(\text{Total Taxable Amount} \times \frac{\text{Company CGST Rate}}{100}\right)$$
$$\text{SGST Amount} = \text{round}\left(\text{Total Taxable Amount} \times \frac{\text{Company SGST Rate}}{100}\right)$$
$$\text{IGST Amount} = \text{round}\left(\text{Total Taxable Amount} \times \frac{\text{Company IGST Rate}}{100}\right)$$

*Note: Taxes are rounded to the nearest integer using standard mathematical rounding (`Math.round`).*

### D. Net Payable
$$\text{Net Payable} = \text{Total Taxable Amount} + \text{CGST Amount} + \text{SGST Amount} + \text{IGST Amount}$$

---

## 3. Invoice PDF Tax & Total Calculations (Backend Generator)

When an invoice PDF is generated, the system performs a row-level itemized breakdown and aggregates them into the final invoice totals.

### A. Row-Level Taxable Amount (Per Item in PDF)
For each shipment item, a distinct taxable amount is calculated inside the PDF generator:

$$\text{Freight Charge} = \text{Actual Weight (wt)} \times \text{Rate}$$
$$\text{Fuel Surcharge Amount} = \text{Actual Weight (wt)} \times \text{Fuel Surcharge Rate}$$
$$\text{Item Taxable Amount} = \text{Freight Charge} + \text{Fuel Surcharge Amount} + \text{Docket Charge} + \text{Pickup Charge} + \text{Delivery Charge}$$

### B. Grand Total Taxable Amount
$$\text{Invoice Total Taxable} = \sum (\text{Item Taxable Amount})$$

### C. Invoice GST Application
If **GST Applied** was checked on the booking record:
$$\text{Invoice CGST} = \text{round}\left(\text{Invoice Total Taxable} \times \frac{\text{Company CGST Rate}}{100}\right)$$
$$\text{Invoice SGST} = \text{round}\left(\text{Invoice Total Taxable} \times \frac{\text{Company SGST Rate}}{100}\right)$$
$$\text{Invoice IGST} = \text{round}\left(\text{Invoice Total Taxable} \times \frac{\text{Company IGST Rate}}{100}\right)$$

*If "GST Applied" is not checked, CGST, SGST, and IGST default to 0.*

### D. Invoice Grand Total
$$\text{Invoice Grand Total} = \text{Invoice Total Taxable} + \text{Invoice CGST} + \text{Invoice SGST} + \text{Invoice IGST}$$

---

## 4. "GST Applied" Button / Checkbox Mechanics

The **GST Applied** checkbox is a crucial control that toggles the inclusion of GST taxes in both the frontend layout and the generated invoice.

### A. HTML Representation (`booking_form.php:L144-145`)
```html
<input class="form-check-input" type="checkbox" name="gst_applied" id="gst_applied" value="1" <?= (!isset($booking['id']) || !empty($booking['gst_applied'])) ? 'checked' : '' ?>>
<label class="form-check-label fw-bold text-dark" for="gst_applied">GST Applied</label>
```

### B. Trigger Event (`booking_form.php:L874`)
The script listens for the `'change'` event on the `#gst_applied` checkbox using jQuery:
```javascript
$(document).on('change', '#gst_applied', calcTotals);
```

### C. Computational Function (`booking_form.php:calcTotals()`)
When changed, the `calcTotals()` javascript function is executed, performing the following step-by-step logic:
1. **Reads Checkbox State**:
   ```javascript
   const isGstApplied = $('#gst_applied').is(':checked');
   ```
2. **Computes Base & Surcharges**: Calculates the total taxable amount by summing the base freight charge (`Rate × Weight`) and sifting through all `.calc-surcharge` input values.
3. **Applies Tax Math conditional on Checkbox State**:
   ```javascript
   let cgst = isGstApplied ? Math.round(taxable * (_companyGst.cgst / 100)) : 0;
   let sgst = isGstApplied ? Math.round(taxable * (_companyGst.sgst / 100)) : 0;
   let igst = isGstApplied ? Math.round(taxable * (_companyGst.igst / 100)) : 0;
   ```
4. **Calculates Net Payable**:
   ```javascript
   let netPayable = taxable + cgst + sgst + igst;
   ```
5. **Updates View Elements**: Sets the text labels `#totalTaxableAmount` and `#netPayableAmount` on the UI screen to reflect the recalculated amounts instantly.
