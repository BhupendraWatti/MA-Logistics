<?php

namespace App\Services;

use TCPDF;

/**
 * PdfInvoiceGenerator
 *
 * Encapsulates all TCPDF-specific logic for generating A4 Landscape invoice PDFs.
 * Decouples the PDF framework from the controller and InvoiceService, so swapping
 * the PDF library in the future only requires changes here.
 *
 * Responsibilities:
 *  - TCPDF instance configuration (orientation, margins, fonts)
 *  - Dynamic top-margin calculation (prevents header/body overlap)
 *  - Rendering the PDF header and body from the invoice view
 *  - Streaming the output to the browser as a download
 */
class PdfInvoiceGenerator
{
    /**
     * Build and stream a TCPDF invoice to the browser as a file download.
     *
     * @param  array  $viewData     The fully assembled view data from InvoiceService::assembleViewData()
     * @param  string $fileName     Desired download filename (without path), e.g. "AWB_12345.pdf"
     * @throws \Throwable           Propagates TCPDF exceptions to the caller for logging
     */
    public function stream(array $viewData, string $fileName): void
    {
        $pdf = $this->createPdfInstance();

        $topMargin = $this->calculateTopMargin($viewData);
        $pdf->SetMargins(8, $topMargin, 8);
        $pdf->SetAutoPageBreak(true, 15);

        // Render header section into the TCPDF page-header callback
        $viewData['renderSection'] = 'header';
        $pdf->headerHtml           = view('pdfs/invoice', $viewData);

        // Render body section into the page body
        $pdf->AddPage();
        $viewData['renderSection'] = 'body';
        $bodyHtml                  = view('pdfs/invoice', $viewData);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($bodyHtml, true, false, true, false, '');

        // Flush any stale output buffers to prevent PDF binary corruption
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $pdf->Output($fileName, 'D');
        exit;
    }

    /**
     * Instantiate and configure the TCPDF object.
     * Using an anonymous class so TCPDF's Header() callback can inject our custom HTML.
     */
    private function createPdfInstance(): TCPDF
    {
        $pdf = new class('L', 'mm', 'A4') extends TCPDF {
            /** @var string  HTML string rendered by InvoiceService and injected before AddPage() */
            public string $headerHtml = '';

            public function Header(): void
            {
                if (!empty($this->headerHtml)) {
                    $this->writeHTMLCell(0, 0, 8, 8, $this->headerHtml, 0, 0, false, true, '', true);
                }
            }
        };

        $pdf->SetCreator('Malogistics');
        $pdf->SetAuthor('Malogistics');
        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(false);

        return $pdf;
    }

    /**
     * Calculate the dynamic top margin required so that the page body starts
     * immediately below the header block without overlapping or leaving excess whitespace.
     *
     * Rules (cumulative additive to a 61mm base):
     *  +5mm  — if GST row is shown in the header (GSTIN, SAC, PAN row)
     *  +4mm  — if a Due Date row is shown
     *  +3.5mm per extra line — for multi-line addresses beyond the first line
     *
     * @param  array $viewData  Must contain: customerGst, cgstRate, sgstRate, igstRate,
     *                          booking['gst_applied'], dueDate, recipientAddress
     * @return int   Top margin in mm (rounded)
     */
    private function calculateTopMargin(array $viewData): int
    {
        $gstApplied = !empty($viewData['booking']['gst_applied']);
        $customerGst = $viewData['customerGst'] ?? '';
        $cgstRate    = (float) ($viewData['cgstRate'] ?? 0);
        $sgstRate    = (float) ($viewData['sgstRate'] ?? 0);
        $igstRate    = (float) ($viewData['igstRate'] ?? 0);
        $dueDate     = $viewData['dueDate'] ?? '';
        $address     = $viewData['recipientAddress'] ?? '';

        $margin = 56;

        $showGstRow = ($gstApplied && !empty($customerGst) && ($cgstRate > 0 || $sgstRate > 0 || $igstRate > 0));
        if ($showGstRow) {
            $margin += 5;
        }

        if (!empty($dueDate)) {
            $margin += 4;
        }

        $lines = count(explode("\n", str_replace("\r", '', $address)));
        if ($lines > 1) {
            $margin += ($lines - 1) * 3.5;
        }

        return (int) round($margin);
    }
}