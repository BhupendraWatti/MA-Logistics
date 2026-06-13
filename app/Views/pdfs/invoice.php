<?php
$recipientNameClean = strtolower($recipientName ?? '');
$isNx = (strpos($recipientNameClean, 'nx logistics') !== false);
$isBrembo = (strpos($recipientNameClean, 'brembo') !== false);
$totalCols = $isNx ? 16 : 14;
$gstApplied = (isset($booking['gst_applied']) && $booking['gst_applied'] == 1);

// Common variables calculation
$totalFreight = 0;
$totalDocket = 0;
$totalFuelAmt = 0;
$totalPickup = 0;
$totalDelivery = 0;
$totalOtherChg = 0;
$totalFsc = 0;
$totalRowCgst = 0;
$totalRowSgst = 0;
$totalRowIgst = 0;
$totalRowTotal = 0;

foreach ($shipmentRows as $row) {
    $totalFreight += floatval($row['freight'] ?? 0);
    $totalDocket += floatval($row['docket'] ?? 0);
    $totalFuelAmt += floatval($row['fuelAmt'] ?? 0);
    $totalPickup += floatval($row['pickup'] ?? 0);
    $totalDelivery += floatval($row['delivery'] ?? 0);
    
    $rowOtherChg = floatval($row['pickup'] ?? 0) + floatval($row['delivery'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0);
    $totalOtherChg += $rowOtherChg;
    
    $rowFsc = floatval($row['fuelAmt'] ?? 0) + $rowOtherChg;
    $totalFsc += $rowFsc;
    
    $rowCgst = ($cgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($row['taxable'] * $cgstRate / 100, 2) : 0.0;
    $rowSgst = ($sgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($row['taxable'] * $sgstRate / 100, 2) : 0.0;
    $rowIgst = ($igstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($row['taxable'] * $igstRate / 100, 2) : 0.0;
    
    $totalRowCgst += $rowCgst;
    $totalRowSgst += $rowSgst;
    $totalRowIgst += $rowIgst;
    $totalRowTotal += ($row['taxable'] + $rowCgst + $rowSgst + $rowIgst);
}
?>
<?php if ($renderSection === 'header'): ?>
<table border="1" cellpadding="2" cellspacing="0" style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica;">
    <tr>
        <td colspan="<?= $totalCols ?>" style="text-align:center;">
            <span style="font-size:18px; font-weight:bold; font-family:times;"><?= esc($company['name'] ?? 'M.A.LOGISTICS') ?></span><br>
            <span style="font-size:8px;"><?= esc($company['address'] ?? '') ?></span><br>
            <span style="font-size:8px;">Mobile: <?= esc($company['mobile'] ?? '') ?> | Email ID: <?= esc($company['email'] ?? '') ?></span>
        </td>
    </tr>
    <?php 
    $showGstRow = (!empty($booking['gst_applied']) && !empty($customerGst) && ($cgstRate > 0 || $sgstRate > 0 || $igstRate > 0));
    if ($showGstRow): 
        $colSpanPart1 = floor($totalCols / 3);
        $colSpanPart2 = floor($totalCols / 3);
        $colSpanPart3 = $totalCols - $colSpanPart1 - $colSpanPart2;
    ?>
    <tr>
        <td colspan="<?= $colSpanPart1 ?>" style="font-size:8px; font-weight:bold;">GSTIN : <?= esc($bookingGstin ?? $company['gstin'] ?? '') ?></td>
        <td colspan="<?= $colSpanPart2 ?>" style="font-size:8px; font-weight:bold; text-align:center;">SAC CODE : <?= esc($bookingSacCode ?? $company['sac_code'] ?? '') ?></td>
        <td colspan="<?= $colSpanPart3 ?>" style="font-size:8px; font-weight:bold; text-align:center;">PAN : <?= esc($bookingPan ?? $company['pan'] ?? '') ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td colspan="<?= $totalCols ?>" style="text-align:center; font-size:11px; font-weight:bold; letter-spacing:2px;">INVOICE</td>
    </tr>
    <tr>
        <?php 
        $leftColspan = floor($totalCols / 2);
        $rightColspan = $totalCols - $leftColspan;
        ?>
        <td colspan="<?= $leftColspan ?>" style="vertical-align:top;">
            <strong>TO : <?= htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') ?></strong><br>
            <?= htmlspecialchars($recipientAddress, ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($customerGst)): ?><br>
                <strong>GSTIN : <?= htmlspecialchars($customerGst, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            <?php if (!empty($customerPan)): ?><br>
                <strong>PAN : <?= htmlspecialchars($customerPan, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
        </td>
        <td colspan="<?= $rightColspan ?>" style="vertical-align:top;">
            <table cellpadding="1" cellspacing="0" style="width:100%;">
                <tr><td style="width:40%;"><strong>Invoice No</strong></td><td>: <?= htmlspecialchars($invoiceNo, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Invoice Period Date</strong></td><td>: <?= htmlspecialchars($invoicePeriod, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Invoice Date</strong></td><td>: <?= htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Billing Branch</strong></td><td>: <?= htmlspecialchars($billingBranch, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>MODE</strong></td><td>: <?= htmlspecialchars($modeTransport, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if (!empty($dueDate)): ?>
                    <tr><td><strong>Due Date</strong></td><td>: <?= htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>
<?php elseif ($renderSection === 'body'): ?>
<style>
    .invoice-body-table td {
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
    }
    .invoice-body-table thead td {
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        font-weight: bold;
        text-align: center;
        background-color: #f5f5f5;
    }
</style>
<table class="invoice-body-table" cellpadding="2" cellspacing="0" style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica;">
    <thead>
        <tr style="text-align:center; font-weight:bold; font-size:8px; line-height:1.4;">
            <?php if ($isNx): ?>
                <td style="width:3%;">SR<br>NO</td>
                <td style="width:6%;">DATE</td>
                <td style="width:8%;">DOC NO.</td>
                <td style="width:6%;">ORIGIN</td>
                <td style="width:6%;">DEST</td>
                <td style="width:4%;">PCS</td>
                <td style="width:5%;">WT</td>
                <td style="width:5%;">RATE</td>
                <td style="width:6%;">FREIGHT</td>
                <td style="width:5%;">DOC</td>
                <td style="width:5%;">FSC</td>
                <td style="width:7%;">GROSS</td>
                <td style="width:6%;">C.GST <?= (float)$cgstRate ?> %</td>
                <td style="width:6%;">S.GST <?= (float)$sgstRate ?> %</td>
                <td style="width:6%;">I.GST <?= (float)$igstRate ?> %</td>
                <td style="width:12%;">TOTAL Amt.</td>
            <?php elseif ($isBrembo): ?>
                <td style="width:3%;">SR NO</td>
                <td style="width:6%;">DATE</td>
                <td style="width:8%;">MA LR NO.</td>
                <td style="width:11%;">BREMBO INV NO.</td>
                <td style="width:6%;">ORIGIN</td>
                <td style="width:6%;">DEST</td>
                <td style="width:5%;">PCS</td>
                <td style="width:5%;">WT</td>
                <td style="width:5%;">RATE</td>
                <td style="width:7%;">FREIGHT</td>
                <td style="width:8%;">DOCKET CHG</td>
                <td style="width:7%;">PICKUP</td>
                <td style="width:8%;">DELIVERY</td>
                <td style="width:15%;">TOTAL Amt.</td>
            <?php else: ?>
                <td style="width:3%;">SR NO</td>
                <td style="width:6%;">DATE</td>
                <td style="width:8%;">LR NO.</td>
                <td style="width:11%;">INVOICE NUMBER</td>
                <td style="width:6%;">ORIGIN</td>
                <td style="width:6%;">DEST</td>
                <td style="width:5%;">PCS</td>
                <td style="width:5%;">WT</td>
                <td style="width:5%;">RATE</td>
                <td style="width:7%;">FREIGHT</td>
                <td style="width:7%;">DOCKET</td>
                <td style="width:7%;">FUEL AMT</td>
                <td style="width:9%;">OTHER CHG</td>
                <td style="width:15%;">TOTAL Amt.</td>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($shipmentRows as $row): ?>
    <tr style="font-size:8px;">
        <?php if ($isNx): ?>
            <?php
            $rowDoc = floatval($row['docket'] ?? 0);
            $rowFsc = floatval($row['fuelAmt'] ?? 0) + floatval($row['pickup'] ?? 0) + floatval($row['delivery'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0);
            $rowGross = floatval($row['freight'] ?? 0) + $rowDoc + $rowFsc;
            $rowCgst = ($cgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $cgstRate / 100, 2) : 0.0;
            $rowSgst = ($sgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $sgstRate / 100, 2) : 0.0;
            $rowIgst = ($igstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $igstRate / 100, 2) : 0.0;
            $rowTotal = $rowGross + $rowCgst + $rowSgst + $rowIgst;
            ?>
            <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:4%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['wt'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['rate'] ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float)$row['freight'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$rowDoc ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$rowFsc ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$rowGross ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float)$rowCgst ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float)$rowSgst ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float)$rowIgst ?></td>
            <td nowrap="nowrap" style="width:12%; white-space: nowrap; text-align:center;"><?= (float)$rowTotal ?></td>
        <?php elseif ($isBrembo): ?>
            <?php
            $rowDocket = floatval($row['docket'] ?? 0) + floatval($row['fuelAmt'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0);
            $rowPickup = floatval($row['pickup'] ?? 0);
            $rowDelivery = floatval($row['delivery'] ?? 0);
            $rowTotal = floatval($row['taxable'] ?? 0);
            ?>
            <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:11%; white-space: nowrap;"><?= htmlspecialchars($row['invoiceNumber'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['wt'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['rate'] ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$row['freight'] ?></td>
            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= (float)$rowDocket ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$rowPickup ?></td>
            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= (float)$rowDelivery ?></td>
            <td nowrap="nowrap" style="width:15%; white-space: nowrap; text-align:center;"><?= (float)$rowTotal ?></td>
        <?php else: ?>
            <?php
            $rowDocket = floatval($row['docket'] ?? 0);
            $rowFuelAmt = floatval($row['fuelAmt'] ?? 0);
            $rowOtherChg = floatval($row['pickup'] ?? 0) + floatval($row['delivery'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0);
            $rowTotal = floatval($row['taxable'] ?? 0);
            ?>
            <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:11%; white-space: nowrap;"><?= htmlspecialchars($row['invoiceNumber'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['wt'] ?></td>
            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float)$row['rate'] ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$row['freight'] ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$rowDocket ?></td>
            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float)$rowFuelAmt ?></td>
            <td nowrap="nowrap" style="width:9%; white-space: nowrap; text-align:center;"><?= (float)$rowOtherChg ?></td>
            <td nowrap="nowrap" style="width:15%; white-space: nowrap; text-align:center;"><?= (float)$rowTotal ?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>

    <tr style="font-size:8px; font-weight:bold;">
        <?php if ($isNx): ?>
            <td colspan="5" style="width:29%;">TOTAL</td>
            <td style="width:4%; text-align:center;"><?= $totalBoxes ?></td>
            <td style="width:5%; text-align:center;"><?= (float)$totalWt ?></td>
            <td style="width:5%;"></td>
            <td style="width:6%; text-align:center;"><?= (float)$totalFreight ?></td>
            <td style="width:5%; text-align:center;"><?= (float)$totalDocket ?></td>
            <td style="width:5%; text-align:center;"><?= (float)$totalFsc ?></td>
            <td style="width:7%; text-align:center;"><?= round($totalTaxable) ?></td>
            <td style="width:6%; text-align:center;"><?= (float)$totalRowCgst ?></td>
            <td style="width:6%; text-align:center;"><?= (float)$totalRowSgst ?></td>
            <td style="width:6%; text-align:center;"><?= (float)$totalRowIgst ?></td>
            <td style="width:12%; text-align:center;"><?= round($totalRowTotal) ?></td>
        <?php elseif ($isBrembo): ?>
            <td colspan="6" style="width:40%;">TOTAL</td>
            <td style="width:5%; text-align:center;"><?= $totalBoxes ?></td>
            <td style="width:5%; text-align:center;"><?= (float)$totalWt ?></td>
            <td style="width:5%;"></td>
            <td style="width:7%; text-align:center;"><?= (float)$totalFreight ?></td>
            <td style="width:8%; text-align:center;"><?= (float)$totalDocket ?></td>
            <td style="width:7%; text-align:center;"><?= (float)$totalPickup ?></td>
            <td style="width:8%; text-align:center;"><?= (float)$totalDelivery ?></td>
            <td style="width:15%; text-align:center;"><?= round($totalTaxable) ?></td>
        <?php else: ?>
            <td colspan="6" style="width:40%;">TOTAL</td>
            <td style="width:5%; text-align:center;"><?= $totalBoxes ?></td>
            <td style="width:5%; text-align:center;"><?= (float)$totalWt ?></td>
            <td style="width:5%;"></td>
            <td style="width:7%; text-align:center;"><?= (float)$totalFreight ?></td>
            <td style="width:7%; text-align:center;"><?= (float)$totalDocket ?></td>
            <td style="width:7%; text-align:center;"><?= (float)$totalFuelAmt ?></td>
            <td style="width:9%; text-align:center;"><?= (float)$totalOtherChg ?></td>
            <td style="width:15%; text-align:center;"><?= round($totalTaxable) ?></td>
        <?php endif; ?>
    </tr>
</table>

<table nobr="true" cellpadding="2" cellspacing="0" style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica; border: 1px solid #000; border-top: none;">
    <!-- Row 1: Remarks (colspan 12) & Net Payable (colspan 8) -->
    <tr>
        <td colspan="12" style="width:60%; vertical-align:top; border-right:1px solid #000; padding: 5px;">
            <?php if (!empty($booking['narration'])): ?>
                <strong>Remarks / Narration:</strong><br>
                <?= nl2br(htmlspecialchars($booking['narration'], ENT_QUOTES, 'UTF-8')) ?>
            <?php endif; ?>
        </td>
        <td colspan="8" style="width:40%; padding:0; vertical-align:top;">
            <table cellpadding="2" cellspacing="0" style="width:100%; border:none;">
                <?php 
                $gstRows = [];
                if ($gstApplied) {
                    if ($cgstRate > 0) $gstRows[] = ['label' => "C.GST - {$cgstRate}%", 'amount' => $cgst];
                    if ($sgstRate > 0) $gstRows[] = ['label' => "S.GST - {$sgstRate}%", 'amount' => $sgst];
                    if ($igstRate > 0) $gstRows[] = ['label' => "I.GST - {$igstRate}%", 'amount' => $igst];
                }
                foreach ($gstRows as $r): 
                ?>
                <tr>
                    <td style="width:70%; border-bottom:1px solid #000; border-right:1px solid #000; font-weight:bold;"><?= esc($r['label']) ?></td>
                    <td style="width:30%; border-bottom:1px solid #000; text-align:center; font-weight:bold;"><?= number_format($r['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td style="width:70%; font-weight:bold; background-color:#f5f5f5; border-right:1px solid #000;">NET PAYABLE AMOUNT</td>
                    <td style="width:30%; text-align:center; font-weight:bold; background-color:#f5f5f5;"><?= $netPayable ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Row 2: Rs. in Words (colspan 20) -->
    <tr>
        <td colspan="20" style="font-size:9px; font-weight:bold; padding: 5px; border-top:1px solid #000; border-bottom:1px solid #000;">
            Rs. (In Word) : <?= strtoupper($amountInWords) ?> ONLY
        </td>
    </tr>

    <!-- Row 3: Bank Details (colspan 6), Terms & Conditions (colspan 7), Signature (colspan 7) -->
    <tr>
        <td colspan="6" style="width:30%; vertical-align:top; padding:8px; line-height:1.4;">
            <strong>Bank Details:</strong><br>
            Account Name : <?= esc($bankDetails['name'] ?? '') ?><br>
            Bank Name : <?= esc($bankDetails['bank_name'] ?? '') ?><br>
            Account No. : <?= esc($bankDetails['ac_no'] ?? '') ?><br>
            IFSC Code : <?= esc($bankDetails['ifsc'] ?? '') ?><br>
            Branch : <?= esc($bankDetails['branch'] ?? '') ?><br><br>
            <strong>Service Category : Courier &amp; Cargo</strong>
        </td>
        <td colspan="7" style="width:35%; vertical-align:top; padding:8px; line-height:1.4; border-left:1px solid #000;">
            <strong>Terms &amp; Conditions :</strong><br>
            <?php 
            $tcText = $company['terms_conditions'] ?? 'No terms specified.';
            $tcLines = explode("\n", str_replace("\r", "", $tcText));
            foreach ($tcLines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    echo esc($trimmed) . '<br>';
                }
            }
            ?>
        </td>
        <td colspan="7" style="width:35%; vertical-align:bottom; text-align:center; font-size:9px; font-weight:bold; padding:8px; border-left:1px solid #000;">
            For <?= esc($company['name'] ?? 'M.A LOGISTICS') ?><br><br>
            <?php 
            $sigPath = !empty($bookingSignaturePath) ? $bookingSignaturePath : ($company['signature_path'] ?? '');
            if (!empty($sigPath) && file_exists(FCPATH . $sigPath)): 
            ?>
                <img src="<?= FCPATH . $sigPath ?>" style="height: 55px; max-width: 140px; margin-bottom: 5px;"><br>
            <?php else: ?>
                <br><br><br>
            <?php endif; ?>
            Authorised signatory
        </td>
    </tr>
</table>
<?php endif; ?>
