<?php
/**
 * Customer-facing docket / shipper-copy waybill.
 *
 * This template intentionally uses TCPDF-safe nested tables and explicit row
 * heights. The visual source of truth is the original printed M.A. Logistics
 * waybill, while every printed value comes from the active company, booking,
 * customer master, or shipment item supplied by Logistics::streamDocketPdf().
 */

$company  = $company ?? [];
$booking  = $booking ?? [];
$shipment = $shipment ?? ($shipmentRows[0] ?? []);
$gstData  = $gstData ?? [];

$printMode   = strtolower((string) ($printMode ?? 'full'));
$isHalfPrint = $printMode === 'half';

$logoPath       = trim((string) ($company['logo_path'] ?? $company['logo_image'] ?? ''));
$logoFile       = $logoPath !== '' ? FCPATH . ltrim($logoPath, '/\\') : '';
$hasLogo        = $logoFile !== '' && is_file($logoFile);
$companyName    = trim((string) ($company['company_name'] ?? $company['name'] ?? ''));
$companyAddress = trim((string) ($company['address'] ?? ''));
$companyMobile  = trim((string) ($company['mobile'] ?? ''));
$companyEmail   = trim((string) ($company['email'] ?? ''));
$companyGst     = trim((string) ($company['gst_no'] ?? $company['gstin'] ?? $bookingGstin ?? ''));

$origin      = strtoupper(trim((string) ($booking['origin'] ?? '')));
$destination = strtoupper(trim((string) ($booking['destination'] ?? '')));
$docketNo    = trim((string) ($docketNo ?? $shipment['docket_no'] ?? $shipment['lrNo'] ?? ''));

$formatDocketDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp === false ? '' : date('d.m.Y', $timestamp);
};
$bookingDate = $formatDocketDate($booking['booking_date'] ?? $shipment['invoice_date'] ?? null);

$shipperName    = trim((string) ($shipperName ?? $shipment['customer_name'] ?? $shipment['customer'] ?? ''));
$shipperAddress = trim((string) ($shipperAddress ?? ''));
$shipperPhone   = trim((string) ($customerPhone ?? ''));

$consigneeName    = trim((string) ($consigneeName ?? $shipment['consignee'] ?? $booking['consignee_name'] ?? ''));
$consigneeAddress = trim((string) ($consigneeAddress ?? ''));
$consigneePhone   = trim((string) ($consigneePhone ?? ''));

$contains = static function (string $value, string $needle): bool {
    return strpos($value, $needle) !== false;
};

$transportMode = strtoupper(trim((string) ($booking['mode_transport'] ?? $modeTransport ?? '')));
$isAir  = $contains($transportMode, 'AIR');
$isRoad = $contains($transportMode, 'ROAD') || $contains($transportMode, 'SURFACE');
$isRail = $contains($transportMode, 'RAIL');

$paymentType = strtoupper(trim((string) ($shipment['payment_type'] ?? $booking['payment_type'] ?? '')));
$isCash      = $contains($paymentType, 'CASH');
$isCredit    = $contains($paymentType, 'CREDIT');
$isToPay     = $contains($paymentType, 'TO-PAY') || $contains($paymentType, 'TO PAY') || $contains($paymentType, 'TOPAY');

$pieces           = (int) ($shipment['pieces'] ?? $shipment['pcs'] ?? 0);
$actualWeight     = (float) ($shipment['actual_weight'] ?? $shipment['act_wt'] ?? 0);
$volumetricWeight = (float) ($shipment['volumetric_weight'] ?? $shipment['vol_wt'] ?? 0);
$chargeableWeight = (float) ($shipment['final_chargeable_weight'] ?? $shipment['calculated_chargeable_weight'] ?? $shipment['chg_wt'] ?? max($actualWeight, $volumetricWeight));
$declaredWeight   = (float) ($shipment['declared_weight'] ?? $actualWeight);

$partNo          = trim((string) ($shipment['part_no'] ?? ''));
$partQty         = (int) ($shipment['part_qty'] ?? $pieces);
$invoiceNo       = trim((string) ($shipment['invoice_no'] ?? $invoiceNo ?? ''));
$deliveryChallan = trim((string) ($shipment['eway_bill_no'] ?? $shipment['eway_no'] ?? $shipment['delivery_challan'] ?? ''));
$formNo          = trim((string) ($shipment['form_no'] ?? $docketNo));
$dimension       = '';

if ((float) ($shipment['length'] ?? 0) > 0 || (float) ($shipment['width'] ?? 0) > 0 || (float) ($shipment['height'] ?? 0) > 0) {
    $dimension = rtrim(rtrim(number_format((float) ($shipment['length'] ?? 0), 1), '0'), '.')
        . ' x ' . rtrim(rtrim(number_format((float) ($shipment['width'] ?? 0), 1), '0'), '.')
        . ' x ' . rtrim(rtrim(number_format((float) ($shipment['height'] ?? 0), 1), '0'), '.') . ' cm';
}

$packageMethod = trim((string) ($shipment['method_of_pkg'] ?? $booking['material_type'] ?? ''));
$contents      = trim((string) ($shipment['contents'] ?? $shipment['description'] ?? $booking['material_details'] ?? $booking['material_category'] ?? $partNo));

$rate           = (float) ($shipment['rate'] ?? 0);
$freight        = $rate * $chargeableWeight;
$docketCharge   = (float) ($shipment['docket_charges'] ?? 0);
$pickupCharge   = (float) ($shipment['pickup_charges'] ?? 0);
$deliveryCharge = (float) ($shipment['delivery_charges'] ?? 0);
$taxable        = (float) ($totalTaxable ?? ($shipmentRows[0]['taxable'] ?? ($freight + $docketCharge + $pickupCharge + $deliveryCharge)));
$weightCharge   = max(0, $taxable - $docketCharge - $pickupCharge - $deliveryCharge);
$taxAmount      = (float) (($gstData['cgst'] ?? $cgst ?? 0) + ($gstData['sgst'] ?? $sgst ?? 0) + ($gstData['igst'] ?? $igst ?? 0));
$netTotal       = (float) ($gstData['netPayable'] ?? $netPayable ?? ($taxable + $taxAmount));
$toPayAmount    = $isToPay ? $netTotal : 0.0;

$taxRate = (float) ($gstData['igstRate'] ?? $igstRate ?? 0);
if ($taxRate <= 0) {
    $taxRate = (float) ($gstData['cgstRate'] ?? $cgstRate ?? 0) + (float) ($gstData['sgstRate'] ?? $sgstRate ?? 0);
}
$taxLabel = $taxRate > 0
    ? rtrim(rtrim(number_format($taxRate, 2), '0'), '.') . '%'
    : '0%';

$insuredValue = strtolower(trim((string) ($shipment['insured'] ?? '')));
$isInsuredYes = in_array($insuredValue, ['1', 'yes', 'y', 'true'], true);
$isInsuredNo  = in_array($insuredValue, ['0', 'no', 'n', 'false'], true);

if (!function_exists('renderDocketBox')) {
    function renderDocketBox(bool $checked): string
    {
        return $checked ? '[<b>X</b>]' : '[&nbsp;&nbsp;]';
    }
}

$renderCharge = static function (float $amount) use ($isHalfPrint): string {
    return $isHalfPrint ? '&nbsp;' : 'Rs. ' . number_format($amount, 2);
};
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; padding: 0; color: #000; font-family: helvetica, sans-serif; font-size: 8pt; }
        table { width: 100%; margin: 0; padding: 0; border-collapse: collapse; }
        td { line-height: 1.18; }
        .waybill { border: 1.5px solid #000; }
        .rule-r { border-right: 1px solid #000; }
        .rule-b { border-bottom: 1px solid #000; }
        .label { background-color: #dedede; font-weight: bold; }
        .small { font-size: 6.6pt; }
        .value { font-size: 8.5pt; font-weight: bold; }
        .value-large { font-size: 10pt; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .top { vertical-align: top; }
        .middle { vertical-align: middle; }
        .bottom { vertical-align: bottom; }
    </style>
</head>
<body>
<table class="waybill" cellpadding="0" cellspacing="0">
    <tr>
        <td width="35%" height="112" class="rule-r rule-b top" style="padding: 5px 6px 3px;">
            <?php if ($hasLogo): ?>
                <img src="<?= esc($logoFile) ?>" style="max-height: 58px; max-width: 185px;"><br>
            <?php elseif ($companyName !== ''): ?>
                <span style="font-size: 17pt; font-weight: bold;"><?= esc($companyName) ?></span><br>
            <?php endif; ?>
            <span class="small">
                <?= nl2br(esc($companyAddress)) ?>
                <?php if ($companyMobile !== ''): ?><br><b>Mobile:</b> <?= esc($companyMobile) ?><?php endif; ?>
                <?php if ($companyEmail !== ''): ?><br><b>E-mail:</b> <?= esc($companyEmail) ?><?php endif; ?>
            </span>
        </td>
        <td width="33%" height="112" class="rule-r rule-b top center" style="padding: 5px 7px;">
            <span style="font-size: 15pt; font-weight: bold;">WAY BILL</span><br><br>
            <table cellpadding="3" cellspacing="0" style="border: 1px solid #000;">
                <tr>
                    <td width="50%" class="label center rule-r rule-b">ORIGIN</td>
                    <td width="50%" class="label center rule-b">DESTINATION</td>
                </tr>
                <tr>
                    <td height="48" class="center middle rule-r"><span class="value-large"><?= esc($origin) ?></span></td>
                    <td height="48" class="center middle"><span class="value-large"><?= esc($destination) ?></span></td>
                </tr>
            </table>
        </td>
        <td width="32%" height="112" class="rule-b top" style="padding: 6px 7px;">
            <div class="right" style="font-size: 9pt; font-weight: bold;">SHIPPER COPY</div><br>
            <span style="font-size: 10pt; font-weight: bold;">NO.</span>
            <span style="font-size: 12pt; font-weight: bold;"> <?= esc($docketNo) ?></span><br><br>
            <span style="font-size: 9pt; font-weight: bold;">DATE:</span>
            <span class="value"> <?= esc($bookingDate) ?></span>
        </td>
    </tr>

    <tr>
        <td colspan="3" class="rule-b" style="padding: 0;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" class="rule-r top" style="padding: 0;">
                        <table cellpadding="4" cellspacing="0">
                            <tr><td height="23" class="top"><span class="label" style="border: 1px solid #000; padding: 2px 5px;">SHIPPER</span></td></tr>
                            <tr><td height="76" class="top" style="padding: 2px 7px;"><span class="value"><?= esc($shipperName) ?></span><br><span style="font-size: 7.4pt;"><?= nl2br(esc($shipperAddress)) ?></span></td></tr>
                            <tr><td height="22" style="border-top: 1px solid #000; padding: 4px 7px;"><b>PH:</b> <?= esc($shipperPhone) ?></td></tr>
                        </table>
                    </td>
                    <td width="50%" class="top" style="padding: 0;">
                        <table cellpadding="4" cellspacing="0">
                            <tr><td height="23" class="top"><span class="label" style="border: 1px solid #000; padding: 2px 5px;">CONSIGNEE</span></td></tr>
                            <tr><td height="76" class="top" style="padding: 2px 7px;"><span class="value"><?= esc($consigneeName) ?></span><br><span style="font-size: 7.4pt;"><?= nl2br(esc($consigneeAddress)) ?></span></td></tr>
                            <tr><td height="22" style="border-top: 1px solid #000; padding: 4px 7px;"><b>PH:</b> <?= esc($consigneePhone) ?></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="3" class="rule-b" style="padding: 0;">
            <table cellpadding="3" cellspacing="0">
                <tr class="label center">
                    <td width="13%" height="28" class="rule-r rule-b middle">MODE</td>
                    <td width="12%" height="28" class="rule-r rule-b middle">NO. OF PIECES</td>
                    <td width="12%" height="28" class="rule-r rule-b middle">ACTUAL<br>WEIGHT</td>
                    <td width="13%" height="28" class="rule-r rule-b middle">VOLUMETRIC<br>WEIGHT</td>
                    <td width="14%" height="28" class="rule-r rule-b middle">CHARGEABLE<br>WEIGHT</td>
                    <td width="13%" height="28" class="rule-r rule-b middle">DECLARED<br>WEIGHT</td>
                    <td width="12%" height="28" class="rule-r rule-b middle">TO PAY<br>RS.</td>
                    <td width="11%" height="28" class="rule-b middle">PAYMENT</td>
                </tr>
                <tr>
                    <td height="75" class="rule-r middle" style="font-size: 7.2pt; line-height: 1.65;">BY AIR &nbsp; <?= renderDocketBox($isAir) ?><br>BY ROAD <?= renderDocketBox($isRoad) ?><br>BY RAIL &nbsp; <?= renderDocketBox($isRail) ?></td>
                    <td height="75" class="rule-r center middle"><span class="value-large"><?= $pieces ?></span></td>
                    <td height="75" class="rule-r center middle"><span class="value"><?= number_format($actualWeight, 2) ?> kg</span></td>
                    <td height="75" class="rule-r center middle"><span class="value"><?= number_format($volumetricWeight, 2) ?> kg</span></td>
                    <td height="75" class="rule-r center middle"><span class="value-large"><?= number_format($chargeableWeight, 2) ?> kg</span></td>
                    <td height="75" class="rule-r center middle"><span class="value"><?= number_format($declaredWeight, 2) ?> kg</span></td>
                    <td height="75" class="rule-r center middle"><span class="value"><?= $isHalfPrint ? '&nbsp;' : 'Rs. ' . number_format($toPayAmount, 2) ?></span></td>
                    <td height="75" class="middle" style="font-size: 7pt; line-height: 1.55;"><?= renderDocketBox($isCash) ?> CASH<br><?= renderDocketBox($isCredit) ?> CREDIT<br><?= renderDocketBox($isToPay) ?> TO-PAY</td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="3" class="rule-b" style="padding: 0;">
            <table cellpadding="4" cellspacing="0">
                <tr>
                    <td width="75%" height="25" class="rule-r rule-b middle"><b>PART NO.:</b> <span class="value"><?= esc($partNo) ?></span></td>
                    <td width="25%" height="25" class="rule-b middle"><b>QTY.:</b> <span class="value"><?= $partQty ?></span></td>
                </tr>
                <tr><td colspan="2" height="24" class="middle"><b>GST NO. <?= esc($companyGst) ?></b></td></tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="3" style="padding: 0;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td width="25%" height="150" class="rule-r top" style="padding: 0;">
                        <table cellpadding="4" cellspacing="0">
                            <tr><td height="37" class="rule-b top"><b>INVOICE NO.</b><br><span class="value"><?= esc($invoiceNo) ?></span></td></tr>
                            <tr><td height="37" class="rule-b top"><b>DEL. CHALLAN</b><br><?= esc($deliveryChallan) ?></td></tr>
                            <tr><td height="37" class="rule-b top"><b>FORM NO.</b><br><span class="value"><?= esc($formNo) ?></span></td></tr>
                            <tr><td height="39" class="top"><b>DIMENSION</b><br><?= esc($dimension) ?></td></tr>
                        </table>
                    </td>
                    <td width="24%" height="150" class="rule-r top" style="padding: 0;">
                        <table cellpadding="4" cellspacing="0">
                            <tr><td height="55" class="rule-b top"><b>METHOD OF PKG.:</b><br><?= esc($packageMethod) ?></td></tr>
                            <tr><td height="55" class="rule-b top"><b>SAID TO CONTAIN:</b><br><?= esc($contents) ?></td></tr>
                            <tr><td height="40" class="center bottom"><span style="font-size: 8.5pt; font-weight: bold;"><?= esc($companyName) ?></span><br><span class="small">SIGNATURE</span></td></tr>
                        </table>
                    </td>
                    <td width="27%" height="150" class="rule-r top" style="padding: 0;">
                        <table cellpadding="3" cellspacing="0">
                            <tr><td height="22" class="rule-b">DOCKET CHARGES</td><td height="22" class="rule-b right"><b><?= $renderCharge($docketCharge) ?></b></td></tr>
                            <tr><td height="22" class="rule-b">WEIGHT CHARGES</td><td height="22" class="rule-b right"><b><?= $renderCharge($weightCharge) ?></b></td></tr>
                            <tr><td height="22" class="rule-b">DELIVERY CHARGES</td><td height="22" class="rule-b right"><b><?= $renderCharge($deliveryCharge) ?></b></td></tr>
                            <tr><td height="22" class="rule-b">PICKUP CHARGES</td><td height="22" class="rule-b right"><b><?= $renderCharge($pickupCharge) ?></b></td></tr>
                            <tr><td height="22" class="rule-b">GST TAX @ <?= esc($taxLabel) ?></td><td height="22" class="rule-b right"><b><?= $renderCharge($taxAmount) ?></b></td></tr>
                            <tr class="label"><td height="24"><b>TOTAL</b></td><td height="24" class="right"><span class="value"><?= $renderCharge($netTotal) ?></span></td></tr>
                            <tr><td colspan="2" height="16" class="bottom"><b>SIGNATURE</b></td></tr>
                        </table>
                    </td>
                    <td width="24%" height="150" class="top center" style="padding: 0;">
                        <table cellpadding="3" cellspacing="0">
                            <tr><td height="25" class="label rule-b center middle">INSURED</td></tr>
                            <tr><td height="28" class="rule-b center middle">YES <?= renderDocketBox($isInsuredYes) ?> &nbsp;&nbsp; NO <?= renderDocketBox($isInsuredNo) ?></td></tr>
                            <tr><td height="97" class="center top" style="padding-top: 8px;"><b>SIGNATURE WITH STAMP</b></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
