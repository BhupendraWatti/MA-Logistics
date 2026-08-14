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
$totalBremboDocket = 0;
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

    $rowOtherChg = floatval($row['pickup'] ?? 0) + floatval($row['delivery'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0) + array_sum($row['itemCustomMap'] ?? []);
    $totalOtherChg += $rowOtherChg;

    $rowBremboDocket = floatval($row['docket'] ?? 0) + floatval($row['fuelAmt'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0) + array_sum($row['itemCustomMap'] ?? []);
    $totalBremboDocket += $rowBremboDocket;

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

$activeChargeKeys = array_keys($activeCharges ?? []);
$hasActiveCharge = static function (array $keys) use ($activeChargeKeys): bool {
    return count(array_intersect($keys, $activeChargeKeys)) > 0;
};
$hasActiveCustomCharge = count(array_filter($activeChargeKeys, static fn($key) => strpos($key, 'custom_') === 0)) > 0;

$showDocketCharge = $hasActiveCharge(['docket']) || $totalDocket > 0;
$showFuelCharge = $hasActiveCharge(['fuel_rate', 'fuel_amt']) || $totalFuelAmt > 0;
$showOtherCharge = $hasActiveCharge(['pickup', 'delivery', 'fov', 'handling', 'service', 'misc']) || $hasActiveCustomCharge || $totalOtherChg > 0;

$showNxDocCharge = $showDocketCharge;
$showNxFscCharge = $showFuelCharge || $showOtherCharge || $totalFsc > 0;
$nxTotalWidth = 12 + ($showNxDocCharge ? 0 : 5) + ($showNxFscCharge ? 0 : 5);

$showBremboDocketCharge = $hasActiveCharge(['docket', 'fuel_rate', 'fuel_amt', 'fov', 'handling', 'service', 'misc']) || $hasActiveCustomCharge || $totalBremboDocket > 0;
$showBremboPickupCharge = $hasActiveCharge(['pickup']) || $totalPickup > 0;
$showBremboDeliveryCharge = $hasActiveCharge(['delivery']) || $totalDelivery > 0;
$bremboTotalWidth = 15 + ($showBremboDocketCharge ? 0 : 8) + ($showBremboPickupCharge ? 0 : 7) + ($showBremboDeliveryCharge ? 0 : 8);

$defaultChargeCatalog = [
    'delivery' => ['label' => 'DELIVERY', 'field' => 'delivery'],
    'docket'   => ['label' => 'DOCKET',   'field' => 'docket'],
    'pickup'   => ['label' => 'PICKUP',   'field' => 'pickup'],
    'fuel_amt' => ['label' => 'FUEL AMT', 'field' => 'fuelAmt'],
    'fov'      => ['label' => 'FOV',      'field' => 'fov'],
    'handling' => ['label' => 'HANDLING', 'field' => 'handling'],
    'service'  => ['label' => 'SERVICE',  'field' => 'service'],
    'misc'     => ['label' => strtoupper($activeCharges['misc']['label'] ?? 'MISC'), 'field' => 'misc'],
];

$defaultActiveChargeColumns = [];
foreach ($defaultChargeCatalog as $key => $charge) {
    $sum = 0.0;
    foreach ($shipmentRows as $row) {
        $sum += (float) ($row[$charge['field']] ?? 0);
    }

    $hasCharge = $key === 'fuel_amt'
        ? ($hasActiveCharge(['fuel_amt', 'fuel_rate']) || $totalFuelAmt > 0)
        : ($hasActiveCharge([$key]) || $sum > 0);

    if ($hasCharge) {
        $defaultActiveChargeColumns[] = ['key' => $key] + $charge;
    }
}

foreach ($activeCharges ?? [] as $key => $charge) {
    if (strpos($key, 'custom_') === 0) {
        $defaultActiveChargeColumns[] = [
            'key'          => $key,
            'label'        => $charge['label'] ?? 'EXTRA',
            'custom_label' => $charge['custom_label'] ?? ($charge['label'] ?? 'EXTRA'),
            'custom'       => true,
        ];
    }
}

$defaultVisibleChargeColumns = array_slice($defaultActiveChargeColumns, 0, 4);
$defaultOtherChargeColumns = array_slice($defaultActiveChargeColumns, 4);
$defaultChargeValue = static function (array $row, array $charge): float {
    if (!empty($charge['custom'])) {
        return (float) ($row['itemCustomMap'][$charge['custom_label']] ?? 0);
    }

    return (float) ($row[$charge['field']] ?? 0);
};
$defaultOtherChargeValue = static function (array $row) use ($defaultOtherChargeColumns, $defaultChargeValue): float {
    $total = 0.0;
    foreach ($defaultOtherChargeColumns as $charge) {
        $total += $defaultChargeValue($row, $charge);
    }

    return $total;
};
$defaultChargeTotals = [];
foreach ($defaultVisibleChargeColumns as $charge) {
    $sum = 0.0;
    foreach ($shipmentRows as $row) {
        $sum += $defaultChargeValue($row, $charge);
    }
    $defaultChargeTotals[$charge['key']] = $sum;
}
$defaultOtherChgTotal = 0.0;
foreach ($shipmentRows as $row) {
    $defaultOtherChgTotal += $defaultOtherChargeValue($row);
}
$showDefaultOtherCharge = $defaultOtherChgTotal > 0;
$defaultTotalWidth = max(8, 38 - (count($defaultVisibleChargeColumns) * 5) - ($showDefaultOtherCharge ? 8 : 0));
?>
<?php if ($renderSection === 'header'): ?>
    <table border="1" cellpadding="2" cellspacing="0"
        style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica;">
        <tr>
            <td colspan="<?= $totalCols ?>" style="text-align:center;">
                <span
                    style="font-size:18px; font-weight:bold; font-family:times;"><?= esc($company['name'] ?? 'M.A.LOGISTICS') ?></span><br>
                <span style="font-size:8px;"><?= esc($company['address'] ?? '') ?></span><br>
                <span style="font-size:8px;">Mobile: <?= esc($company['mobile'] ?? '') ?> | Email ID:
                    <?= esc($company['email'] ?? '') ?></span>
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
                <td colspan="<?= $colSpanPart1 ?>" style="font-size:8px; font-weight:bold;">GSTIN :
                    <?= esc($bookingGstin ?? $company['gstin'] ?? '') ?>
                </td>
                <td colspan="<?= $colSpanPart2 ?>" style="font-size:8px; font-weight:bold; text-align:center;">SAC CODE :
                    <?= esc($bookingSacCode ?? $company['sac_code'] ?? '') ?>
                </td>
                <td colspan="<?= $colSpanPart3 ?>" style="font-size:8px; font-weight:bold; text-align:center;">PAN :
                    <?= esc($bookingPan ?? $company['pan'] ?? '') ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td colspan="<?= $totalCols ?>"
                style="text-align:center; font-size:11px; font-weight:bold; letter-spacing:2px;">INVOICE</td>
        </tr>
        <tr>
            <?php
            $leftColspan = floor($totalCols / 2);
            $rightColspan = $totalCols - $leftColspan;
            ?>
            <td colspan="<?= $leftColspan ?>" style="vertical-align:top;">
                <strong>Bill TO : <?= htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') ?></strong><br>
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
                    <tr>
                        <td style="width:40%;"><strong>Invoice No</strong></td>
                        <td>: <?= htmlspecialchars($invoiceNo, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Invoice Period Date</strong></td>
                        <td>: <?= htmlspecialchars($invoicePeriod, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Invoice Date</strong></td>
                        <td>: <?= htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Billing Branch</strong></td>
                        <td>: <?= htmlspecialchars($billingBranch, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td><strong>MODE</strong></td>
                        <td>: <?= htmlspecialchars($modeTransport, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php if (!empty($dueDate)): ?>
                        <tr>
                            <td><strong>Due Date</strong></td>
                            <td>: <?= htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
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
    </style>
    <table class="invoice-body-table" cellpadding="2" cellspacing="0"
        style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica; border-top:1px solid #000;">
        <thead>
            <tr style="text-align:center; font-weight:bold; font-size:8px; line-height:1.4;">
                <?php if ($isNx): ?>
                    <td style="width:3%; border-top:1px solid #000; border-bottom:1px solid #000;">SR<br>NO</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DATE</td>
                    <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">DOC NO.</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">ORIGIN</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DEST</td>
                    <td style="width:4%; border-top:1px solid #000; border-bottom:1px solid #000;">BOXES</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">WT</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">RATE</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">FREIGHT</td>
                    <?php if ($showNxDocCharge): ?>
                        <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">DOC</td><?php endif; ?>
                    <?php if ($showNxFscCharge): ?>
                        <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">FSC</td><?php endif; ?>
                    <td style="width:7%; border-top:1px solid #000; border-bottom:1px solid #000;">GROSS</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">C.GST
                        <?= (float) $cgstRate ?>
                        %
                    </td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">S.GST
                        <?= (float) $sgstRate ?>
                        %
                    </td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">I.GST
                        <?= (float) $igstRate ?>
                        %
                    </td>
                    <td style="width:<?= $nxTotalWidth ?>%; border-top:1px solid #000; border-bottom:1px solid #000;">TOTAL Amt.
                    </td>
                <?php elseif ($isBrembo): ?>
                    <td style="width:3%; border-top:1px solid #000; border-bottom:1px solid #000;">SR NO</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DATE</td>
                    <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">DOCKET NO.</td>
                    <td style="width:11%; border-top:1px solid #000; border-bottom:1px solid #000;">BREMBO INV NO.</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">ORIGIN</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DEST</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">BOXES</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">WT</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">RATE</td>
                    <td style="width:7%; border-top:1px solid #000; border-bottom:1px solid #000;">FREIGHT</td>
                    <?php if ($showBremboDocketCharge): ?>
                        <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">DOCKET CHG</td>
                    <?php endif; ?>
                    <?php if ($showBremboPickupCharge): ?>
                        <td style="width:7%; border-top:1px solid #000; border-bottom:1px solid #000;">PICKUP</td><?php endif; ?>
                    <?php if ($showBremboDeliveryCharge): ?>
                        <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">DELIVERY</td><?php endif; ?>
                    <td style="width:<?= $bremboTotalWidth ?>%; border-top:1px solid #000; border-bottom:1px solid #000;">TOTAL
                        Amt.</td>
                <?php else: ?>
                    <td style="width:3%; border-top:1px solid #000; border-bottom:1px solid #000;">SR NO</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DATE</td>
                    <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">LR NO.</td>
                    <td style="width:11%; border-top:1px solid #000; border-bottom:1px solid #000;">INVOICE NUMBER</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">ORIGIN</td>
                    <td style="width:6%; border-top:1px solid #000; border-bottom:1px solid #000;">DEST</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">BOXES</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">WT</td>
                    <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;">RATE</td>
                    <td style="width:7%; border-top:1px solid #000; border-bottom:1px solid #000;">FREIGHT</td>
                    <?php foreach ($defaultVisibleChargeColumns as $charge): ?>
                        <td style="width:5%; border-top:1px solid #000; border-bottom:1px solid #000;"><?= esc($charge['label']) ?></td>
                    <?php endforeach; ?>
                    <?php if ($showDefaultOtherCharge): ?>
                        <td style="width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">OTHER CHG</td><?php endif; ?>
                    <td style="width:<?= $defaultTotalWidth ?>%; border-top:1px solid #000; border-bottom:1px solid #000;">TOTAL
                        Amt.</td>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shipmentRows as $row): ?>
                <tr style="font-size:8px;">
                    <?php if ($isNx): ?>
                        <?php
                        $rowDoc = floatval($row['docket'] ?? 0);
                        $rowFsc = floatval($row['fuelAmt'] ?? 0) + floatval($row['pickup'] ?? 0) + floatval($row['delivery'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0) + array_sum($row['itemCustomMap'] ?? []);
                        $rowGross = floatval($row['freight'] ?? 0) + $rowDoc + $rowFsc;
                        $rowCgst = ($cgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $cgstRate / 100, 2) : 0.0;
                        $rowSgst = ($sgstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $sgstRate / 100, 2) : 0.0;
                        $rowIgst = ($igstRate > 0 && isset($booking['gst_applied']) && $booking['gst_applied'] == 1) ? round($rowGross * $igstRate / 100, 2) : 0.0;
                        $rowTotal = $rowGross + $rowCgst + $rowSgst + $rowIgst;
                        ?>
                        <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
                        <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:4%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['wt'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['rate'] ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float) $row['freight'] ?>
                        </td>
                        <?php if ($showNxDocCharge): ?>
                            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $rowDoc ?></td>
                        <?php endif; ?>
                        <?php if ($showNxFscCharge): ?>
                            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $rowFsc ?></td>
                        <?php endif; ?>
                        <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float) $rowGross ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float) $rowCgst ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float) $rowSgst ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= (float) $rowIgst ?></td>
                        <td nowrap="nowrap" style="width:<?= $nxTotalWidth ?>%; white-space: nowrap; text-align:center;">
                            <?= (float) $rowTotal ?>
                        </td>
                    <?php elseif ($isBrembo): ?>
                        <?php
                        $rowDocket = floatval($row['docket'] ?? 0) + floatval($row['fuelAmt'] ?? 0) + floatval($row['fov'] ?? 0) + floatval($row['handling'] ?? 0) + floatval($row['service'] ?? 0) + floatval($row['misc'] ?? 0) + array_sum($row['itemCustomMap'] ?? []);
                        $rowPickup = floatval($row['pickup'] ?? 0);
                        $rowDelivery = floatval($row['delivery'] ?? 0);
                        $rowTotal = floatval($row['taxable'] ?? 0);
                        ?>
                        <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
                        <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:11%; white-space: nowrap;">
                            <?= htmlspecialchars($row['invoiceNumber'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['wt'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['rate'] ?>
                        </td>
                        <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float) $row['freight'] ?>
                        </td>
                        <?php if ($showBremboDocketCharge): ?>
                            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= (float) $rowDocket ?></td>
                        <?php endif; ?>
                        <?php if ($showBremboPickupCharge): ?>
                            <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float) $rowPickup ?></td>
                        <?php endif; ?>
                        <?php if ($showBremboDeliveryCharge): ?>
                            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= (float) $rowDelivery ?>
                            </td><?php endif; ?>
                        <td nowrap="nowrap" style="width:<?= $bremboTotalWidth ?>%; white-space: nowrap; text-align:center;">
                            <?= (float) $rowTotal ?>
                        </td>
                    <?php else: ?>
                        <?php
                        $rowOtherChg = $defaultOtherChargeValue($row);
                        $rowTotal = floatval($row['taxable'] ?? 0);
                        ?>
                        <td nowrap="nowrap" style="width:3%; white-space: nowrap; text-align:center;"><?= $row['serial'] ?></td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;"><?= $row['date'] ?></td>
                        <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:11%; white-space: nowrap;">
                            <?= htmlspecialchars($row['invoiceNumber'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:6%; white-space: nowrap; text-align:center;">
                            <?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= $row['boxes'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['wt'] ?></td>
                        <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $row['rate'] ?>
                        </td>
                        <td nowrap="nowrap" style="width:7%; white-space: nowrap; text-align:center;"><?= (float) $row['freight'] ?>
                        </td>
                        <?php foreach ($defaultVisibleChargeColumns as $charge): ?>
                            <td nowrap="nowrap" style="width:5%; white-space: nowrap; text-align:center;"><?= (float) $defaultChargeValue($row, $charge) ?></td>
                        <?php endforeach; ?>
                        <?php if ($showDefaultOtherCharge): ?>
                            <td nowrap="nowrap" style="width:8%; white-space: nowrap; text-align:center;"><?= (float) $rowOtherChg ?>
                            </td><?php endif; ?>
                        <td nowrap="nowrap" style="width:<?= $defaultTotalWidth ?>%; white-space: nowrap; text-align:center;">
                            <?= (float) $rowTotal ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>

            <tr style="font-size:8px; font-weight:bold;">
                <?php if ($isNx): ?>
                    <td colspan="5" style="width:29%;">TOTAL</td>
                    <td style="width:4%; text-align:center;"><?= $totalBoxes ?></td>
                    <td style="width:5%; text-align:center;"><?= (float) $totalWt ?></td>
                    <td style="width:5%;"></td>
                    <td style="width:6%; text-align:center;"><?= (float) $totalFreight ?></td>
                    <?php if ($showNxDocCharge): ?>
                        <td style="width:5%; text-align:center;"><?= (float) $totalDocket ?></td><?php endif; ?>
                    <?php if ($showNxFscCharge): ?>
                        <td style="width:5%; text-align:center;"><?= (float) $totalFsc ?></td><?php endif; ?>
                    <td style="width:7%; text-align:center;"><?= round($totalTaxable) ?></td>
                    <td style="width:6%; text-align:center;"><?= (float) $totalRowCgst ?></td>
                    <td style="width:6%; text-align:center;"><?= (float) $totalRowSgst ?></td>
                    <td style="width:6%; text-align:center;"><?= (float) $totalRowIgst ?></td>
                    <td style="width:<?= $nxTotalWidth ?>%; text-align:center;"><?= round($totalRowTotal) ?></td>
                <?php elseif ($isBrembo): ?>
                    <td colspan="6" style="width:40%;">TOTAL</td>
                    <td style="width:5%; text-align:center;"><?= $totalBoxes ?></td>
                    <td style="width:5%; text-align:center;"><?= (float) $totalWt ?></td>
                    <td style="width:5%;"></td>
                    <td style="width:7%; text-align:center;"><?= (float) $totalFreight ?></td>
                    <?php if ($showBremboDocketCharge): ?>
                        <td style="width:8%; text-align:center;"><?= (float) $totalBremboDocket ?></td><?php endif; ?>
                    <?php if ($showBremboPickupCharge): ?>
                        <td style="width:7%; text-align:center;"><?= (float) $totalPickup ?></td><?php endif; ?>
                    <?php if ($showBremboDeliveryCharge): ?>
                        <td style="width:8%; text-align:center;"><?= (float) $totalDelivery ?></td><?php endif; ?>
                    <td style="width:<?= $bremboTotalWidth ?>%; text-align:center;"><?= round($totalTaxable) ?></td>
                <?php else: ?>
                    <td colspan="6" style="width:40%;">TOTAL</td>
                    <td style="width:5%; text-align:center;"><?= $totalBoxes ?></td>
                    <td style="width:5%; text-align:center;"><?= (float) $totalWt ?></td>
                    <td style="width:5%;"></td>
                    <td style="width:7%; text-align:center;"><?= (float) $totalFreight ?></td>
                    <?php foreach ($defaultVisibleChargeColumns as $charge): ?>
                        <td style="width:5%; text-align:center;"><?= (float) ($defaultChargeTotals[$charge['key']] ?? 0) ?></td>
                    <?php endforeach; ?>
                    <?php if ($showDefaultOtherCharge): ?>
                        <td style="width:8%; text-align:center;"><?= (float) $defaultOtherChgTotal ?></td><?php endif; ?>
                    <td style="width:<?= $defaultTotalWidth ?>%; text-align:center;"><?= round($totalTaxable) ?></td>
                <?php endif; ?>
            </tr>
    </table>

    <table nobr="true" cellpadding="2" cellspacing="0"
        style="width:100%; font-size:8px; border-collapse:collapse; font-family:helvetica; border: 1px solid #000; border-top: none;">
        <!-- Row 1: Remarks (colspan 12) & Net Payable (colspan 8) -->
        <tr>
            <td colspan="12" style="width:60%; vertical-align:top; border-right:1px solid #000; padding: 5px;">
                <?php $invoiceRemarks = $booking['remarks'] ?? ($booking['narration'] ?? ''); ?>
                <?php if (!empty($invoiceRemarks)): ?>
                    <strong>Remarks / Narration:</strong><br>
                    <?= nl2br(htmlspecialchars($invoiceRemarks, ENT_QUOTES, 'UTF-8')) ?>
                <?php endif; ?>
            </td>
            <td colspan="8" style="width:40%; padding:0; vertical-align:top;">
                <table cellpadding="2" cellspacing="0" style="width:100%; border:none;">
                    <?php
                    $gstRows = [];
                    if ($gstApplied) {
                        if ($cgstRate > 0)
                            $gstRows[] = ['label' => "C.GST - {$cgstRate}%", 'amount' => $cgst];
                        if ($sgstRate > 0)
                            $gstRows[] = ['label' => "S.GST - {$sgstRate}%", 'amount' => $sgst];
                        if ($igstRate > 0)
                            $gstRows[] = ['label' => "I.GST - {$igstRate}%", 'amount' => $igst];
                    }
                    foreach ($gstRows as $r):
                        ?>
                        <tr>
                            <td style="width:70%; border-bottom:1px solid #000; border-right:1px solid #000; font-weight:bold;">
                                <?= esc($r['label']) ?>
                            </td>
                            <td style="width:30%; border-bottom:1px solid #000; text-align:center; font-weight:bold;">
                                <?= number_format($r['amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="width:70%; font-weight:bold; background-color:#f5f5f5; border-right:1px solid #000;">NET
                            PAYABLE AMOUNT</td>
                        <td style="width:30%; text-align:center; font-weight:bold; background-color:#f5f5f5;">
                            <?= $netPayable ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Row 2: Rs. in Words (colspan 20) -->
        <tr>
            <td colspan="20"
                style="font-size:9px; font-weight:bold; padding: 5px; border-top:1px solid #000; border-bottom:1px solid #000;">
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
            <td colspan="7"
                style="width:35%; vertical-align:top; padding:8px; line-height:1.4; border-left:1px solid #000;">
                <strong>Terms &amp; Conditions :</strong><br><?php
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
            <td colspan="7"
                style="width:35%; vertical-align:bottom; text-align:center; font-size:9px; font-weight:bold; padding:8px; border-left:1px solid #000;">
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
