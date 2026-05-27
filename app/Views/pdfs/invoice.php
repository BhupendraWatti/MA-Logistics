<table border="1" cellpadding="4" cellspacing="0" style="width:100%; font-size:9px; border-collapse:collapse; font-family:helvetica;">
    <tr>
        <td colspan="16" style="text-align:center;">
            <span style="font-size:24px; font-weight:bold; font-family:times;"><?= esc($company['name'] ?? 'M.A.LOGISTICS') ?></span><br>
            <span style="font-size:10px;"><?= esc($company['address'] ?? '') ?></span><br>
            <span style="font-size:10px;">Mobile: <?= esc($company['mobile'] ?? '') ?> | Email ID: <?= esc($company['email'] ?? '') ?></span>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="font-size:10px; font-weight:bold;">GSTIN : <?= esc($company['gstin'] ?? '') ?></td>
        <td colspan="5" style="font-size:10px; font-weight:bold; text-align:center;">SAC CODE : <?= esc($company['sac_code'] ?? '') ?></td>
        <td colspan="6" style="font-size:10px; font-weight:bold; text-align:center;">PAN : <?= esc($company['pan'] ?? '') ?></td>
    </tr>
    <tr>
        <td colspan="16" style="text-align:center; font-size:14px; font-weight:bold; letter-spacing:2px;">INVOICE</td>
    </tr>
    <tr>
        <td colspan="8" style="vertical-align:top;">
            <strong>TO : <?= htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') ?></strong><br>
            <?= htmlspecialchars($recipientAddress, ENT_QUOTES, 'UTF-8') ?>
        </td>
        <td colspan="8" style="vertical-align:top;">
            <table cellpadding="1" cellspacing="0" style="width:100%;">
                <tr><td style="width:40%;"><strong>Invoice No</strong></td><td>: <?= htmlspecialchars($invoiceNo, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Invoice Period Date</strong></td><td>: <?= htmlspecialchars($invoicePeriod, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Invoice Date</strong></td><td>: <?= htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>Billing Branch</strong></td><td>: <?= htmlspecialchars($billingBranch, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td><strong>MODE</strong></td><td>: <?= htmlspecialchars($modeTransport, ENT_QUOTES, 'UTF-8') ?></td></tr>
            </table>
        </td>
    </tr>
    <tr style="text-align:center; font-weight:bold; font-size:8px;">
        <td style="width:3%;">SR<br>NO</td>
        <td style="width:6%;">DATE</td>
        <td style="width:8%;">LR NO.</td>
        <td style="width:11%;">INVOICE NUMBER</td>
        <td style="width:6%;">ORIGIN</td>
        <td style="width:6%;">DEST</td>
        <td style="width:5%;">NO. OF<br>BOX</td>
        <td style="width:5%;">WT</td>
        <td style="width:5%;">RATE</td>
        <td style="width:5%;">Fuel<br>Surcharge</td>
        <td style="width:6%;">FREIGHT</td>
        <td style="width:7%;">Fuel<br>surcharge<br>Amount</td>
        <td style="width:7%;">DOCKET</td>
        <td style="width:6%;">PICK UP<br>CHARGE</td>
        <td style="width:6%;">DELIVER<br>CHARGE</td>
        <td style="width:8%;">TAXABLE<br>AMOUNT</td>
    </tr>

    <?php foreach ($shipmentRows as $row): ?>
    <tr style="font-size:8px;">
        <td style="text-align:center;"><?= $row['serial'] ?></td>
        <td style="text-align:center;"><?= $row['date'] ?></td>
        <td style="text-align:center;"><?= htmlspecialchars($row['lrNo'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($row['invoiceNumber'], ENT_QUOTES, 'UTF-8') ?></td>
        <td style="text-align:center;"><?= htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8') ?></td>
        <td style="text-align:center;"><?= htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8') ?></td>
        <td style="text-align:center;"><?= $row['boxes'] ?></td>
        <td style="text-align:center;"><?= (float)$row['wt'] ?></td>
        <td style="text-align:center;"><?= (float)$row['rate'] ?></td>
        <td style="text-align:center;"><?= (float)$row['fuelSur'] ?></td>
        <td style="text-align:center;"><?= (float)$row['freight'] ?></td>
        <td style="text-align:center;"><?= (float)$row['fuelAmt'] ?></td>
        <td style="text-align:center;"><?= (float)$row['docket'] ?></td>
        <td style="text-align:center;"><?= (float)$row['pickup'] ?></td>
        <td style="text-align:center;"><?= (float)$row['delivery'] ?></td>
        <td style="text-align:center;"><?= (float)$row['taxable'] ?></td>
    </tr>
    <?php endforeach; ?>

    <tr style="font-size:8px;">
        <td colspan="6"></td>
        <td style="text-align:center; font-weight:bold;"><?= $totalBoxes ?></td>
        <td style="text-align:center; font-weight:bold;"><?= (float)$totalWt ?></td>
        <td></td>
        <td></td>
        <td colspan="5" style="text-align:center; font-weight:bold;">TAXABLE AMOUNT</td>
        <td style="text-align:center; font-weight:bold;"><?= round($totalTaxable) ?></td>
    </tr>

    <?php 
    $gstRows = [];
    if ($cgstRate > 0) $gstRows[] = ['label' => "C.GST - {$cgstRate}%", 'amount' => $cgst];
    if ($sgstRate > 0) $gstRows[] = ['label' => "S.GST - {$sgstRate}%", 'amount' => $sgst];
    if ($igstRate > 0) $gstRows[] = ['label' => "I.GST - {$igstRate}%", 'amount' => $igst];
    $rowSpan = count($gstRows) + 1;
    ?>
    <tr style="font-size:8px; font-weight:bold;">
        <td colspan="10" rowspan="<?= $rowSpan ?>"></td>
        <?php if (count($gstRows) > 0): ?>
            <td colspan="5" style="text-align:left;"><?= $gstRows[0]['label'] ?></td>
            <td style="text-align:center;"><?= $gstRows[0]['amount'] ?></td>
    </tr>
            <?php for($i=1; $i<count($gstRows); $i++): ?>
    <tr style="font-size:8px; font-weight:bold;">
            <td colspan="5" style="text-align:left;"><?= $gstRows[$i]['label'] ?></td>
            <td style="text-align:center;"><?= $gstRows[$i]['amount'] ?></td>
    </tr>
            <?php endfor; ?>
    <tr style="font-size:8px; font-weight:bold;">
        <?php endif; ?>
        <td colspan="5" style="text-align:left;">NET PAYABLE AMOUNT</td>
        <td style="text-align:center;"><?= $netPayable ?></td>
    </tr>
    <tr>
        <td colspan="16" style="font-size:10px; font-weight:bold; padding: 5px;">
            Rs. (In Word) <?= strtoupper($amountInWords) ?> RUPEES ONLY ./-
        </td>
    </tr>
    <tr>
        <td colspan="10" style="vertical-align:top; font-size:9px; padding: 10px; line-height: 1.6;">
            <b>Service Category : Courier & Cargo</b><br><br>
            <b>Terms & Conditions :</b><br>
            <?= nl2br(esc($company['terms_conditions'] ?? 'No terms specified.')) ?>
        </td>
        <td colspan="6" style="vertical-align:bottom; text-align:right; font-size:10px; font-weight:bold; padding: 10px;">
            For <?= esc($company['name'] ?? 'M.A LOGISTICS') ?><br><br><br><br>
            <?php if (!empty($company['signature_path']) && file_exists(FCPATH . $company['signature_path'])): ?>
                <img src="<?= FCPATH . $company['signature_path'] ?>" style="height: 60px; max-width: 150px; margin-bottom: 10px;"><br>
            <?php else: ?>
                <br><br><br>
            <?php endif; ?>
            Authorised signatory&nbsp;&nbsp;&nbsp;
        </td>
    </tr>
</table>
