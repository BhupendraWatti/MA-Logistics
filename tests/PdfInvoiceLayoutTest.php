<?php

namespace Tests;

use App\Services\PdfInvoiceGenerator;
use PHPUnit\Framework\TestCase;

final class PdfInvoiceLayoutTest extends TestCase
{
    public function testGstIdentityAndTaxColumnsOnlyRenderForGstInvoices(): void
    {
        $gstData = $this->fixtureData(true, 'L', 2);
        $gstData['renderSection'] = 'header';
        $gstHeader = view('pdfs/invoice', $gstData);

        self::assertStringContainsString('GSTIN :', $gstHeader);
        self::assertStringContainsString('SAC CODE :', $gstHeader);
        self::assertStringContainsString('PAN :', $gstHeader);
        self::assertStringNotContainsString('<img', $gstHeader);

        $nonGstData = $this->fixtureData(false, 'L', 2);
        $nonGstData['renderSection'] = 'header';
        $nonGstHeader = view('pdfs/invoice', $nonGstData);
        self::assertStringNotContainsString('SAC CODE :', $nonGstHeader);

        $nonGstData['renderSection'] = 'body';
        $nonGstBody = view('pdfs/invoice', $nonGstData);
        self::assertStringNotContainsString('C.GST', $nonGstBody);
        self::assertStringNotContainsString('S.GST', $nonGstBody);
        self::assertStringNotContainsString('I.GST', $nonGstBody);
    }

    public function testLandscapeAndPortraitInvoicesRenderAsPdf(): void
    {
        $outputDir = ROOTPATH . 'tmp/pdfs/generated';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $generator = new PdfInvoiceGenerator();
        foreach (['L' => 'all-invoice-landscape.pdf', 'P' => 'all-invoice-portrait.pdf'] as $orientation => $name) {
            $path = $outputDir . '/' . $name;
            $generator->save($this->fixtureData(true, $orientation, 70), $path, $orientation);
            self::assertFileExists($path);
            self::assertGreaterThan(10_000, filesize($path));
            self::assertSame('%PDF-', file_get_contents($path, false, null, 0, 5));
        }

        $defaultPortrait = $this->fixtureData(true, 'P', 70);
        $defaultPortrait['recipientName'] = 'General Billing Customer';
        $defaultPortrait['activeCharges'] = [
            'docket' => ['label' => 'DOCKET CHG'],
            'pickup' => ['label' => 'PICKUP'],
            'delivery' => ['label' => 'DELIVERY'],
        ];
        $taxable = 0.0;
        foreach ($defaultPortrait['shipmentRows'] as $index => &$row) {
            $row['fuelAmt'] = 0.0;
            $row['pickup'] = $index === 0 ? 900.0 : 0.0;
            $row['delivery'] = $index === 0 ? 900.0 : 0.0;
            $row['taxable'] = $row['freight'] + $row['docket'] + $row['pickup'] + $row['delivery'];
            $taxable += $row['taxable'];
        }
        unset($row);
        $defaultPortrait['totalTaxable'] = $taxable;
        $defaultPortrait['cgst'] = round($taxable * 0.09, 2);
        $defaultPortrait['sgst'] = round($taxable * 0.09, 2);
        $defaultPortrait['netPayable'] = round($taxable + $defaultPortrait['cgst'] + $defaultPortrait['sgst']);

        $defaultPortraitPath = $outputDir . '/all-invoice-portrait-default.pdf';
        $generator->save($defaultPortrait, $defaultPortraitPath, 'P');
        self::assertFileExists($defaultPortraitPath);
        self::assertGreaterThan(10_000, filesize($defaultPortraitPath));
        self::assertSame('%PDF-', file_get_contents($defaultPortraitPath, false, null, 0, 5));
    }

    public function testGstHeaderMarginDoesNotDependOnCustomerGstin(): void
    {
        $generator = new PdfInvoiceGenerator();
        $method = new \ReflectionMethod($generator, 'calculateTopMargin');

        $withCustomerGst = $this->fixtureData(true, 'L', 1);
        $withoutCustomerGst = $withCustomerGst;
        $withoutCustomerGst['customerGst'] = '';

        self::assertSame(
            $method->invoke($generator, $withCustomerGst, 'L'),
            $method->invoke($generator, $withoutCustomerGst, 'L')
        );
    }

    public function testPortraitUsesNarrowerDefaultTotalColumn(): void
    {
        $portrait = $this->fixtureData(true, 'P', 2);
        $portrait['recipientName'] = 'General Billing Customer';
        $portrait['renderSection'] = 'body';
        $portraitBody = view('pdfs/invoice', $portrait);

        $landscape = $portrait;
        $landscape['layoutOrientation'] = 'L';
        $landscapeBody = view('pdfs/invoice', $landscape);

        self::assertStringContainsString('width:20%; border-top:1px solid #000; border-bottom:1px solid #000;">TOTAL', $portraitBody);
        self::assertStringContainsString('width:10%; border-top:1px solid #000; border-bottom:1px solid #000;">LR NO.', $portraitBody);
        self::assertStringContainsString('width:28%; border-top:1px solid #000; border-bottom:1px solid #000;">TOTAL', $landscapeBody);
        self::assertStringContainsString('width:8%; border-top:1px solid #000; border-bottom:1px solid #000;">LR NO.', $landscapeBody);
    }

    public function testTermsPreserveAuthoredSpacingWithoutInjectedBottomMargins(): void
    {
        $richTerms = '<p>Introductory text</p><ol><li>Point one</li><li>Point two</li></ol><p>Closing text</p>';
        $formatted = PdfInvoiceGenerator::formatTermsHtml($richTerms);

        self::assertStringNotContainsString('margin:', $formatted);
        self::assertStringNotContainsString('font-size:5px', $formatted);
        self::assertStringContainsString(
            '</p><ol>',
            $formatted
        );
        self::assertStringContainsString(
            '</li><br><li>',
            $formatted
        );

        $plainTerms = PdfInvoiceGenerator::formatTermsHtml("Point one\nPoint two\nPoint three");
        self::assertSame('Point one<br>Point two<br>Point three', $plainTerms);

        $blankLineTerms = PdfInvoiceGenerator::formatTermsHtml("Point one\n\nPoint two");
        self::assertSame('Point one<br><br>Point two', $blankLineTerms);

        $blankParagraphTerms = PdfInvoiceGenerator::formatTermsHtml('<p>Point one</p><p><br></p><p>Point two</p>');
        self::assertStringContainsString('</p><br><br><p>', $blankParagraphTerms);
    }

    public function testRichTermsRenderInBothPdfFormats(): void
    {
        $outputDir = ROOTPATH . 'tmp/pdfs/generated';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $terms = '<p>Introductory text</p><ol><li>Point one text</li><li>Point two text</li><li>Point three text</li></ol>';
        $generator = new PdfInvoiceGenerator();

        $invoiceData = $this->fixtureData(true, 'L', 2);
        $invoiceData['booking']['docket_terms'] = $terms;
        $invoicePath = $outputDir . '/terms-spacing-invoice.pdf';
        $generator->save($invoiceData, $invoicePath, 'L');

        $docketData = $invoiceData;
        $docketData['company']['company_name'] = $docketData['company']['name'];
        $docketData['booking'] = array_merge($docketData['booking'], [
            'booking_date' => '2026-08-25',
            'origin' => 'PUNE',
            'destination' => 'DELHI',
            'mode_transport' => 'AIR',
            'payment_type' => 'CREDIT',
        ]);
        $docketData['shipment'] = [
            'docket_no' => 'DCK-SPACING-001',
            'customer_name' => 'Spacing Test Shipper',
            'consignee' => 'Spacing Test Consignee',
            'pieces' => 2,
            'actual_weight' => 10,
            'volumetric_weight' => 12,
            'final_chargeable_weight' => 12,
            'rate' => 40,
            'contents' => 'TEST GOODS',
        ];
        $docketData['shipperCustomer'] = [];
        $docketData['docketNo'] = 'DCK-SPACING-001';
        $docketPath = $outputDir . '/terms-spacing-docket.pdf';
        $generator->save($docketData, $docketPath, 'P', 'pdfs/docket_pdf');

        foreach ([$invoicePath, $docketPath] as $path) {
            self::assertFileExists($path);
            self::assertGreaterThan(10_000, filesize($path));
            self::assertSame('%PDF-', file_get_contents($path, false, null, 0, 5));
        }
    }

    private function fixtureData(bool $gstApplied, string $orientation, int $rowCount): array
    {
        $rows = [];
        $taxable = 0.0;
        for ($i = 1; $i <= $rowCount; $i++) {
            $freight = 40.0 * (40 + $i);
            $docket = 190.0;
            $fuel = round($freight * 0.19, 2);
            $rowTaxable = $freight + $docket + $fuel;
            $taxable += $rowTaxable;
            $rows[] = [
                'serial' => $i,
                'date' => '01.11.25',
                'dateRaw' => sprintf('2026-07-%02d', (($i - 1) % 28) + 1),
                'lrNo' => 'DCK-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'invoiceNumber' => 'MA-26-27/001',
                'origin' => 'PUNE',
                'destination' => $i % 2 ? 'CHENNAI' : 'NEW DELHI',
                'boxes' => ($i % 5) + 1,
                'wt' => 40 + $i,
                'rate' => 40,
                'freight' => $freight,
                'fuelAmt' => $fuel,
                'docket' => $docket,
                'pickup' => 0,
                'delivery' => 0,
                'fov' => 0,
                'handling' => 0,
                'service' => 0,
                'misc' => 0,
                'itemCustomMap' => [],
                'taxable' => $rowTaxable,
            ];
        }

        $cgstRate = $gstApplied ? 9.0 : 0.0;
        $sgstRate = $gstApplied ? 9.0 : 0.0;
        $cgst = round($taxable * $cgstRate / 100, 2);
        $sgst = round($taxable * $sgstRate / 100, 2);
        $net = round($taxable + $cgst + $sgst);

        return [
            'layoutOrientation' => $orientation,
            'company' => [
                'name' => 'M.A.LOGISTICS',
                'address' => 'SR. No. 34/2, G-1, RAJKAMAL, LANE NO. 10, TINGRE NAGAR, PUNE 411032',
                'mobile' => '9373117048 / 7620829619',
                'email' => 'accounts@malogistics.in',
                'gstin' => '27AICPD8922A1ZQ',
                'pan' => 'AICPD8922A',
                'sac_code' => '996531',
                'logo_path' => 'uploads/logos/should-not-render.png',
                'terms_conditions' => "Difference, if any, must be notified within 7 days of receipt of bills.\nSubject to Pune Jurisdiction.\nE. & O.E.",
                'signature_path' => '',
            ],
            'recipientName' => 'Nx Logistics (India) Private Limited',
            'recipientAddress' => 'Gate No. 341, Chakan MIDC, Pune, Maharashtra 410501',
            'customerGst' => $gstApplied ? '27AADCN6042M1Z7' : '',
            'customerPan' => $gstApplied ? 'AADCN6042M' : '',
            'invoiceNo' => 'MA-26-27/001',
            'invoicePeriod' => '01/07/2026 TO 31/07/2026',
            'invoiceDate' => '31/07/2026',
            'billingBranch' => 'PUNE',
            'modeTransport' => 'AIR',
            'shipmentRows' => $rows,
            'totalBoxes' => array_sum(array_column($rows, 'boxes')),
            'totalWt' => array_sum(array_column($rows, 'wt')),
            'totalTaxable' => $taxable,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => 0.0,
            'cgstRate' => $cgstRate,
            'sgstRate' => $sgstRate,
            'igstRate' => 0.0,
            'netPayable' => $net,
            'amountInWords' => 'One Lakh Rupees',
            'booking' => ['gst_applied' => $gstApplied ? 1 : 0, 'narration' => 'Monthly billing'],
            'bookingGstin' => '27AICPD8922A1ZQ',
            'bookingPan' => 'AICPD8922A',
            'bookingSacCode' => '996531',
            'bookingSignaturePath' => '',
            'activeCharges' => [
                'docket' => ['label' => 'DOCKET'],
                'fuel_amt' => ['label' => 'FSC'],
            ],
            'bankDetails' => [
                'name' => 'M.A.LOGISTICS',
                'bank_name' => 'AXIS BANK',
                'ac_no' => '914020014273896',
                'ifsc' => 'UTIB0000073',
                'branch' => 'Bund Garden, Pune',
            ],
            'dueDate' => '',
        ];
    }
}
