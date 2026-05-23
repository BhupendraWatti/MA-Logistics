<table border="1" cellpadding="4" cellspacing="0" style="width:100%; font-size:9px; border-collapse:collapse; font-family:helvetica;">
    <tr>
        <td colspan="16" style="text-align:center;">
            <span style="font-size:24px; font-weight:bold; font-family:times;">M.A.LOGISTICS</span><br>
            <span style="font-size:10px;">Sr.No.34/2, plot No. -69, Rajkamal Bldg, Lane No.10(10A) Vidya Nagar, Tingre Nagar, Pune 411 032</span><br>
            <span style="font-size:10px;">Office Ph.7719868468, Mob.7620829619. Email ID : malogistics.pune@gmail.com</span>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="font-size:10px; font-weight:bold;">GSTIN : 27AICPD8922A1ZQ</td>
        <td colspan="5" style="font-size:10px; font-weight:bold; text-align:center;">SAC CODE : 996531</td>
        <td colspan="6" style="font-size:10px; font-weight:bold; text-align:center;">PAN : AICPD8922A</td>
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

    <tr style="font-size:8px; font-weight:bold;">
        <td colspan="10" rowspan="4"></td>
        <td colspan="5" style="text-align:left;">C.GST - 9%</td>
        <td style="text-align:center;"><?= $cgst ?></td>
    </tr>
    <tr style="font-size:8px; font-weight:bold;">
        <td colspan="5" style="text-align:left;">S.GST - 9%</td>
        <td style="text-align:center;"><?= $sgst ?></td>
    </tr>
    <tr style="font-size:8px; font-weight:bold;">
        <td colspan="5" style="text-align:left;">I.GST - 18%</td>
        <td style="text-align:center;"><?= $igst ?></td>
    </tr>
    <tr style="font-size:8px; font-weight:bold;">
        <td colspan="5" style="text-align:left;">NET PAYABLE AMOUNT</td>
        <td style="text-align:center;"><?= $netPayable ?></td>
    </tr>
    <tr>
        <td colspan="16" style="font-size:10px; font-weight:bold;">
            Rs. (In Word) <?= strtoupper($amountInWords) ?> RUPEES ONLY ./-
        </td>
    </tr>
    <tr>
        <td colspan="11" style="vertical-align:top; font-size:9px;">
            <b>Service Catagory : Courier & Cargo</b><br><br>
            <b>Terms & Conditions :</b><br>
            i. Difference if any may be notified within 7 days of receipt of bills.<br>
            ii. Subject to Pune Jurisdiction<br>
            iii. E & O.E.<br>
            iv. Draw Cheque in favour of "MA LOGISTICS"<br>
            v. For NEFT/RTGS: Bank Details are as follows :-<br>
            &nbsp;&nbsp;&nbsp;Bank Name : AXIS BANK, &nbsp;&nbsp; Branch: Bund Garden,Pune<br>
            &nbsp;&nbsp;&nbsp;Current Account No : 914020014273896<br>
            &nbsp;&nbsp;&nbsp;IFSC : UTIB0000073
        </td>
        <td colspan="5" style="vertical-align:top; text-align:center; font-size:10px; font-weight:bold;">
            For M.A LOGISTICS<br><br><br><br><br><br><br><br>Authorised signatory
        </td>
    </tr>
</table>
