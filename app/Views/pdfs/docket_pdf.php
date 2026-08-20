<?php
/**
 * Dynamic Docket / Way Bill PDF Template
 * Pixel-Perfect match for 1.jpeg Waybill layout
 */

// Data extraction with safe fallbacks
$company      = $company ?? [];
$booking      = $booking ?? [];
$shipment     = $shipmentRows[0] ?? $shipment ?? [];

$logoPath     = $company['logo_path'] ?? $company['logo_image'] ?? '';
$hasLogo      = !empty($logoPath) && file_exists(FCPATH . $logoPath);
$printMode    = strtolower($printMode ?? $_GET['print_mode'] ?? 'full');
$isHalfPrint  = ($printMode === 'half');

$companyName  = $company['company_name'] ?? $company['name'] ?? 'M.A. LOGISTICS';
$companyAddr  = $company['address'] ?? 'Plot No. 99, Tingre Nagar, Road No. 14J, Pune - 411032';
$companyMob   = $company['mobile'] ?? $company['phone'] ?? '+91 9373117048';
$companyEmail = $company['email'] ?? 'malogistics.pune@gmail.com';
$companyGst   = $company['gst_no'] ?? $company['gstin'] ?? $bookingGstin ?? '';

$origin       = strtoupper($booking['origin'] ?? 'PUNE');
$destination  = strtoupper($booking['destination'] ?? '');

// Explicit Docket No resolution (ensuring Docket No is never shadowed by AWB No)
$docketNo     = !empty($docketNo) ? $docketNo : (!empty($shipment['docket_no']) ? $shipment['docket_no'] : (!empty($shipment['lrNo']) ? $shipment['lrNo'] : (!empty($shipment['docket']) ? $shipment['docket'] : '')));

$bookingDate  = !empty($shipment['invoice_date']) ? date('d.m.Y', strtotime($shipment['invoice_date'])) : (!empty($booking['booking_date']) ? date('d.m.Y', strtotime($booking['booking_date'])) : date('d.m.Y'));

$shipperName  = $shipperName ?? $shipment['customer_name'] ?? $shipment['customer'] ?? $recipientName ?? '';
$shipperAddr  = $shipperAddress ?? $recipientAddress ?? '';
$shipperPhone = $customerPhone ?? '';

$consigneeName  = $consigneeName ?? $shipment['consignee'] ?? $booking['consignee_name'] ?? '';
$consigneeAddr  = $consigneeAddress ?? '';
$consigneePhone = $consigneePhone ?? '';

$transportMode = strtoupper($booking['mode'] ?? $booking['mode_transport'] ?? $modeTransport ?? 'AIR');
$isAir  = str_contains($transportMode, 'AIR');
$isRoad = str_contains($transportMode, 'ROAD') || str_contains($transportMode, 'SURFACE');
$isRail = str_contains($transportMode, 'RAIL');

$paymentType = strtoupper($shipment['payment_type'] ?? $booking['payment_type'] ?? 'CREDIT');
$isCash   = str_contains($paymentType, 'CASH');
$isCredit = str_contains($paymentType, 'CREDIT');
$isToPay  = str_contains($paymentType, 'TO') || str_contains($paymentType, 'PAY');

$pcs       = (int) ($shipment['pieces'] ?? $shipment['pcs'] ?? 1);
$actWt     = (float) ($shipment['actual_weight'] ?? $shipment['act_wt'] ?? 0);
$volWt     = (float) ($shipment['volumetric_weight'] ?? $shipment['vol_wt'] ?? 0);
$chgWt     = (float) ($shipment['final_chargeable_weight'] ?? $shipment['chg_wt'] ?? max($actWt, $volWt));
$declaredWt= (float) ($shipment['declared_weight'] ?? $actWt);

$partNo     = !empty($shipment['part_no']) ? $shipment['part_no'] : '';
$partQty    = (int) ($shipment['part_qty'] ?? $pcs);
$invoiceNo  = !empty($shipment['invoice_no']) ? $shipment['invoice_no'] : '';
$delChallan = !empty($shipment['eway_bill_no']) ? $shipment['eway_bill_no'] : (!empty($shipment['eway_no']) ? $shipment['eway_no'] : (!empty($shipment['delivery_challan']) ? $shipment['delivery_challan'] : ''));
$formNo     = !empty($shipment['form_no']) ? $shipment['form_no'] : $docketNo;
$dimension  = (!empty($shipment['length']) || !empty($shipment['width']) || !empty($shipment['height']))
    ? sprintf('%.1fx%.1fx%.1f', $shipment['length'] ?? 0, $shipment['width'] ?? 0, $shipment['height'] ?? 0)
    : (!empty($shipment['dimension']) ? $shipment['dimension'] : '');

$pkgMethod  = !empty($shipment['method_of_pkg']) ? $shipment['method_of_pkg'] : 'Carton Box';
$contents   = !empty($shipment['contents']) ? $shipment['contents'] : (!empty($shipment['description']) ? $shipment['description'] : ($booking['material_category'] ?? 'Goods'));

// Charges calculation
$rate        = (float) ($shipment['rate'] ?? 0);
$freight     = $rate > 0 ? ($rate * $chgWt) : (float) ($shipment['freight'] ?? 0);
$docketChg   = (float) ($shipment['docket_charges'] ?? 0);
$pickupChg   = (float) ($shipment['pickup_charges'] ?? 0);
$deliveryChg = (float) ($shipment['delivery_charges'] ?? 0);
$fovChg      = (float) ($shipment['fov_charges'] ?? 0);
$miscChg     = (float) ($shipment['misc_charges'] ?? 0);

// GST Calculation based on Customer GSTIN
$hasCustomerGst = !empty($customerGst) || !empty($shipperInfo['gst']) || !empty($booking['gst_applied']);
$taxable        = $freight + $docketChg + $pickupChg + $deliveryChg + $fovChg + $miscChg;

if ($hasCustomerGst) {
    $taxAmount = (float) ($gstData['totalGst'] ?? round($taxable * 0.18, 2));
    $gstLabel  = !empty($gstData['rateLabel']) ? $gstData['rateLabel'] : '18%';
} else {
    $taxAmount = 0.00;
    $gstLabel  = '0%';
}

$netTotal   = $taxable + $taxAmount;

// Checkbox helper ensuring clear square brackets in PDF output
function renderBox(bool $checked): string {
    return $checked ? '[<b>X</b>]' : '[&nbsp;&nbsp;]';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: helvetica, sans-serif;
            font-size: 8pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        .main-container {
            border: 2px solid #000000;
            border-collapse: collapse;
        }

        .bg-header {
            background-color: #e6e6e6;
            font-weight: bold;
            font-size: 7.5pt;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .fs-sm { font-size: 7.5pt; }
        .fs-md { font-size: 9pt; }
        .fs-lg { font-size: 11pt; }
        .fs-title { font-size: 15pt; font-weight: bold; }
    </style>
</head>
<body>

    <!-- DOCKET SLIP CONTAINER -->
    <table cellpadding="0" cellspacing="0" class="main-container">

        <!-- HEADER BLOCK -->
        <tr>
            <td style="width: 38%; vertical-align: top; padding: 4px; border-bottom: 1px solid #000000; border-right: 1px solid #000000;">
                <table cellpadding="0" cellspacing="0" style="border: none;">
                    <tr>
                        <td style="border: none;">
                            <?php if ($hasLogo): ?>
                                <img src="<?= FCPATH . esc($logoPath) ?>" style="max-height: 42px; max-width: 150px; margin-bottom: 2px;"><br>
                            <?php endif; ?>
                            <span style="font-size: 7pt; color: #444444; line-height: 1.2;">
                                <?= esc($companyAddr) ?><br>
                                Mobile: <?= esc($companyMob) ?><br>
                                E-mail: <?= esc($companyEmail) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </td>

            <td style="width: 36%; text-align: center; vertical-align: top; padding: 4px; border-bottom: 1px solid #000000; border-right: 1px solid #000000;">
                <span style="font-size: 13pt; font-weight: bold; letter-spacing: 1px;">WAY BILL</span><br><br>
                <table cellpadding="2" cellspacing="0" style="width: 100%; border: 1px solid #000000;">
                    <tr class="bg-header">
                        <td style="width: 50%; border-right: 1px solid #000000;">ORIGIN</td>
                        <td style="width: 50%;">DESTINATION</td>
                    </tr>
                    <tr>
                        <td class="text-center fw-bold fs-lg" style="height: 22px; vertical-align: middle; border-top: 1px solid #000000; border-right: 1px solid #000000;">
                            <?= esc($origin) ?>
                        </td>
                        <td class="text-center fw-bold fs-lg" style="height: 22px; vertical-align: middle; border-top: 1px solid #000000;">
                            <?= esc($destination) ?>
                        </td>
                    </tr>
                </table>
            </td>

            <td style="width: 26%; vertical-align: top; padding: 4px; border-bottom: 1px solid #000000;">
                <div class="text-right fw-bold fs-sm" style="color: #444444;">SHIPPER COPY</div>
                <br>
                <span class="fw-bold fs-md">NO.</span> <span class="fw-bold fs-lg" style="color: #000080;"><?= esc($docketNo) ?></span><br><br>
                <span class="fw-bold fs-md">DATE:</span> <span class="fw-bold fs-md"><?= esc($bookingDate) ?></span>
            </td>
        </tr>

        <!-- SHIPPER & CONSIGNEE DETAILS -->
        <tr>
            <td style="width: 50%; height: 60px; padding: 4px; border-bottom: 1px solid #000000; border-right: 1px solid #000000;">
                <span class="bg-header" style="padding: 1px 5px; border: 1px solid #000;"> SHIPPER </span><br><br>
                <span class="fw-bold fs-md"><?= esc($shipperName) ?></span><br>
                <span class="fs-sm"><?= nl2br(esc($shipperAddr)) ?></span><br>
                <?php if (!empty($shipperPhone)): ?><span class="fs-sm"><b>PH:</b> <?= esc($shipperPhone) ?></span><?php endif; ?>
            </td>
            <td style="width: 50%; height: 60px; padding: 4px; border-bottom: 1px solid #000000;" colspan="2">
                <span class="bg-header" style="padding: 1px 5px; border: 1px solid #000;"> CONSIGNEE </span><br><br>
                <span class="fw-bold fs-md"><?= esc($consigneeName) ?></span><br>
                <span class="fs-sm"><?= nl2br(esc($consigneeAddr)) ?></span><br>
                <?php if (!empty($consigneePhone)): ?><span class="fs-sm"><b>PH:</b> <?= esc($consigneePhone) ?></span><?php endif; ?>
            </td>
        </tr>

        <!-- MODE & WEIGHT SPECIFICATIONS TABLE -->
        <tr>
            <td colspan="3" style="padding: 0; border-bottom: 1px solid #000000;">
                <table cellpadding="3" cellspacing="0" style="width: 100%;">
                    <tr class="bg-header">
                        <td style="width: 13%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">MODE</td>
                        <td style="width: 12%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">NO. OF PIECES</td>
                        <td style="width: 12%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">ACTUAL WEIGHT</td>
                        <td style="width: 13%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">VOLUMETRIC WEIGHT</td>
                        <td style="width: 14%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">CHARGEABLE WEIGHT</td>
                        <td style="width: 13%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">DECLARED WEIGHT</td>
                        <td style="width: 12%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">TO PAY RS.</td>
                        <td style="width: 11%; border-bottom: 1px solid #000000;">PAYMENT</td>
                    </tr>
                    <tr>
                        <td style="font-size: 7.5pt; vertical-align: middle; line-height: 1.5; border-right: 1px solid #000000;">
                            BY AIR &nbsp;&nbsp;<?= renderBox($isAir) ?><br>
                            BY ROAD <?= renderBox($isRoad) ?><br>
                            BY RAIL &nbsp;<?= renderBox($isRail) ?>
                        </td>
                        <td class="text-center fw-bold fs-lg" style="vertical-align: middle; border-right: 1px solid #000000;"><?= $pcs ?></td>
                        <td class="text-center fw-bold fs-md" style="vertical-align: middle; border-right: 1px solid #000000;"><?= number_format($actWt, 2) ?> kg</td>
                        <td class="text-center fw-bold fs-md" style="vertical-align: middle; border-right: 1px solid #000000;"><?= number_format($volWt, 2) ?> kg</td>
                        <td class="text-center fw-bold fs-lg" style="vertical-align: middle; color: #000080; border-right: 1px solid #000000;"><?= number_format($chgWt, 2) ?> kg</td>
                        <td class="text-center fw-bold fs-md" style="vertical-align: middle; border-right: 1px solid #000000;"><?= number_format($declaredWt, 2) ?> kg</td>
                        <td class="text-center fw-bold fs-md" style="vertical-align: middle; border-right: 1px solid #000000;">
                            Rs. <?= number_format($netTotal, 2) ?>
                        </td>
                        <td style="vertical-align: middle; font-size: 7.5pt; line-height: 1.4;">
                            <?= renderBox($isCash) ?> CASH<br>
                            <?= renderBox($isCredit) ?> CREDIT<br>
                            <?= renderBox($isToPay) ?> TO-PAY
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- PART NO & GST NO ROW (MATCHING 1.jpeg BORDERS) -->
        <tr>
            <td colspan="3" style="padding: 0; border-bottom: 1px solid #000000;">
                <table cellpadding="3" cellspacing="0" style="width: 100%;">
                    <tr>
                        <td style="width: 75%; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">
                            <b>PART NO.:</b> <span class="fs-md fw-bold"><?= esc($partNo) ?></span>
                        </td>
                        <td style="width: 25%; border-bottom: 1px solid #000000;">
                            <b>QTY.:</b> <span class="fs-md fw-bold"><?= $partQty ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <b>GST NO. <?= esc($companyGst) ?></b>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- MATRIX & CHARGES SECTION (MATCHING 1.jpeg INTERNAL CELL BORDERS & FULL HEIGHT) -->
        <tr>
            <td colspan="3" style="padding: 0;">
                <table cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                        <!-- COLUMN 1: SPECS (24%) WITH INNER BORDERS -->
                        <td style="width: 24%; vertical-align: top; padding: 0; border-right: 1px solid #000000;">
                            <table cellpadding="3" cellspacing="0" style="width: 100%;">
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; height: 28px;">
                                        <b>INVOICE NO.</b><br><span class="fw-bold"><?= esc($invoiceNo) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; height: 28px;">
                                        <b>DEL. CHALLAN</b><br><?= esc($delChallan) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; height: 28px;">
                                        <b>FORM NO.</b><br><span class="fw-bold"><?= esc($docketNo) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height: 28px;">
                                        <b>DIMENTION</b><br><?= esc($dimension) ?>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <!-- COLUMN 2: PACKAGING & SIGNATURE (24%) WITH INNER BORDERS -->
                        <td style="width: 24%; vertical-align: top; padding: 0; border-right: 1px solid #000000;">
                            <table cellpadding="3" cellspacing="0" style="width: 100%;">
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; height: 42px;">
                                        <b>METHOD OF PKG.:</b><br><?= esc($pkgMethod) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; height: 42px;">
                                        <b>SAID TO CONTAIN:</b><br><?= esc($contents) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height: 28px; text-align: center; vertical-align: bottom;">
                                        <span class="fw-bold" style="font-size: 8pt;"><?= esc($companyName) ?></span><br>
                                        <span style="font-size: 6.5pt;">SIGNATURE</span>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <!-- COLUMN 3: FINANCIAL CHARGES BREAKDOWN (32%) WITH INNER BORDERS -->
                        <td style="width: 32%; vertical-align: top; padding: 0; border-right: 1px solid #000000;">
                            <table cellpadding="2" cellspacing="0" style="width: 100%;">
                                <tr>
                                    <td style="font-size: 7.5pt; border-bottom: 1px solid #000000;">DOCKET CHARGES</td>
                                    <td class="text-right fw-bold" style="border-bottom: 1px solid #000000;">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($docketChg, 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 7.5pt; border-bottom: 1px solid #000000;">WEIGHT CHARGES</td>
                                    <td class="text-right fw-bold" style="border-bottom: 1px solid #000000;">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($freight, 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 7.5pt; border-bottom: 1px solid #000000;">DELIVERY CHARGES</td>
                                    <td class="text-right fw-bold" style="border-bottom: 1px solid #000000;">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($deliveryChg, 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 7.5pt; border-bottom: 1px solid #000000;">PICKUP CHARGES</td>
                                    <td class="text-right fw-bold" style="border-bottom: 1px solid #000000;">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($pickupChg, 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 7.5pt; border-bottom: 1px solid #000000;">GST TAX @ <?= esc($gstLabel) ?></td>
                                    <td class="text-right fw-bold" style="border-bottom: 1px solid #000000;">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($taxAmount, 2) ?>
                                    </td>
                                </tr>
                                <tr class="bg-header">
                                    <td class="fw-bold" style="font-size: 8.5pt;">TOTAL</td>
                                    <td class="text-right fw-bold fs-lg">
                                        <?= $isHalfPrint ? '-' : 'Rs. ' . number_format($netTotal, 2) ?>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <!-- COLUMN 4: INSURED & MANUAL SIGNATURE STAMP (20%) - FULL CONTAINER HEIGHT -->
                        <td style="width: 20%; text-align: center; font-size: 7.5pt; vertical-align: top; padding: 0;">
                            <table cellpadding="0" cellspacing="0" style="width: 100%; height: 112px;">
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; text-align: center; height: 18px; font-weight: bold; background-color: #e6e6e6;">
                                        INSURED
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000000; text-align: center; height: 22px; vertical-align: middle;">
                                        YES [&nbsp;&nbsp;] &nbsp;&nbsp;&nbsp;&nbsp; NO [&nbsp;&nbsp;]
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; vertical-align: bottom; height: 72px; padding-bottom: 4px;">
                                        <span class="fw-bold" style="font-size: 6.5pt;">SIGNATURE WITH STAMP</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

</body>
</html>