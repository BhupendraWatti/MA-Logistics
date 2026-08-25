<?php

namespace App\Services;

use TCPDF;

/**
 * PdfInvoiceGenerator
 *
 * Encapsulates all TCPDF-specific logic for generating A4 invoice PDFs.
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
     * Convert stored Terms & Conditions without inventing vertical spacing.
     *
     * TCPDF parses CSS margins on normal block tags but does not apply those
     * margins during HTML flow. Its list-item close handler also forces zero
     * vertical space. We therefore remove the automatic block gaps in
     * createPdfInstance() and add only the normal line advance that TCPDF omits
     * between adjacent items. Explicit blank lines in the source are preserved.
     */
    public static function formatTermsHtml(?string $terms): string
    {
        $terms = trim((string) $terms);
        if ($terms === '') {
            return '';
        }

        $hasHtml = preg_match('/<\s*(?:p|span|div|ol|ul|li|br|b|strong|i|em)\b/i', $terms) === 1;
        if (!$hasHtml) {
            $lines = array_map('trim', preg_split('/\R/u', $terms) ?: []);

            return implode(
                '<br>',
                array_map(
                    static fn (string $line): string => htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    $lines
                )
            );
        }

        // Normalize editor output to the small block-tag set whose automatic
        // TCPDF spacing is controlled below. Inter-tag source whitespace is
        // cosmetic because TCPDF converts raw newlines to ordinary spaces.
        $html = preg_replace('/<div\b([^>]*)>/i', '<p$1>', $terms);
        $html = preg_replace('/<\/div\s*>/i', '</p>', (string) $html);
        $html = preg_replace('/>\s+</u', '><', (string) $html);
        // An authored empty paragraph represents a deliberate blank line.
        $html = preg_replace('/<p\b[^>]*>\s*(?:<br\s*\/?\s*>)?\s*<\/p>/i', '<br><br>', (string) $html);

        // TCPDF does not advance after </li> when custom tag spacing is zero.
        // Insert one normal break, not a bottom margin. Consecutive paragraphs
        // and a paragraph following a list use the same authored-line rule.
        $html = preg_replace('/<\/li>\s*(?=<li\b)/i', '</li><br>', (string) $html);
        $html = preg_replace('/<\/p>\s*(?=<p\b)/i', '</p><br>', (string) $html);
        $html = preg_replace('/<\/(ol|ul)>\s*(?=<p\b)/i', '</$1><br>', (string) $html);

        return trim((string) $html);
    }

    /**
     * Build and stream a TCPDF invoice to the browser as a file download.
     *
     * @param  array  $viewData     The fully assembled view data from InvoiceService::assembleViewData()
     * @param  string $fileName     Desired download filename (without path), e.g. "AWB_12345.pdf"
     * @throws \Throwable           Propagates TCPDF exceptions to the caller for logging
     */
    public function stream(array $viewData, string $fileName, string $orientation = 'L', bool $autoPrint = false, string $viewName = 'pdfs/invoice'): void
    {
        $pdf = $this->buildPdf($viewData, $orientation, $autoPrint, $viewName);
        $pdf->SetTitle(pathinfo($fileName, PATHINFO_FILENAME) ?: 'Invoice');

        // Flush any stale output buffers to prevent PDF binary corruption
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/pdf');
        }

        $pdf->Output($fileName, $autoPrint ? 'I' : 'D');
        exit;
    }

    public function save(array $viewData, string $absolutePath, string $orientation = 'L', string $viewName = 'pdfs/invoice'): void
    {
        $pdf = $this->buildPdf($viewData, $orientation, false, $viewName);
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdf->Output($absolutePath, 'F');
    }

    private function buildPdf(array $viewData, string $orientation = 'L', bool $autoPrint = false, string $viewName = 'pdfs/invoice'): TCPDF
    {
        $pdf = $this->createPdfInstance($orientation);
        
        if ($viewName === 'pdfs/docket_pdf') {
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
            $pdf->SetMargins(8, 8, 8);
            $pdf->SetAutoPageBreak(true, 8);
            $pdf->AddPage();
            $html = view($viewName, $viewData);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $viewData['layoutOrientation'] = strtoupper($orientation) === 'P' ? 'P' : 'L';
            $topMargin = $this->calculateTopMargin($viewData, $orientation);
            $pdf->SetMargins(8, $topMargin, 8);
            $pdf->SetAutoPageBreak(true, 15);

            // Render header section into the TCPDF page-header callback
            $viewData['renderSection'] = 'header';
            $pdf->headerHtml           = view($viewName, $viewData);

            // Render body section into the page body
            $pdf->AddPage();
            // Keep the full company/invoice header on page one only. Automatic
            // continuation pages should start near the top with the repeated
            // table headings and the next shipment serial number.
            $pdf->SetTopMargin(8);
            $viewData['renderSection'] = 'body';
            $bodyHtml                  = view($viewName, $viewData);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->writeHTML($bodyHtml, true, false, true, false, '');
        }

        if ($autoPrint && method_exists($pdf, 'IncludeJS')) {
            $pdf->IncludeJS('print(true);');
        }

        return $pdf;
    }

    /**
     * Instantiate and configure the TCPDF object.
     * Using an anonymous class so TCPDF's Header() callback can inject our custom HTML.
     */
    private function createPdfInstance(string $orientation = 'L'): TCPDF
    {
        $orientation = strtoupper($orientation) === 'P' ? 'P' : 'L';
        $pdf = new class($orientation, 'mm', 'A4') extends TCPDF {
            /** @var string  HTML string rendered by InvoiceService and injected before AddPage() */
            public string $headerHtml = '';

            public function Header(): void
            {
                if ($this->getPage() === 1 && !empty($this->headerHtml)) {
                    $this->writeHTMLCell(0, 0, 8, 8, $this->headerHtml, 0, 0, false, true, '', true);
                }
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, $this->getAliasNumPage() . ' out of ' . $this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
        };

        $pdf->SetCreator('Malogistics');
        $pdf->SetAuthor('Malogistics');
        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->setHtmlVSpace([
            'p'   => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]],
            'ol'  => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]],
            'ul'  => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]],
            'li'  => [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]],
        ]);

        return $pdf;
    }

    /**
     * Calculate the dynamic top margin required so that the page body starts
     * immediately below the header block without overlapping or leaving excess whitespace.
     *
     * Rules (cumulative additive to an orientation-specific base):
     *  +5mm  — if GST row is shown in the header (GSTIN, SAC, PAN row)
     *  +4mm  — if a Due Date row is shown
     *  +3.5mm per extra line — for multi-line addresses beyond the first line
     *
     * @param  array $viewData  Must contain: customerGst, cgstRate, sgstRate, igstRate,
     *                          booking['gst_applied'], dueDate, recipientAddress
     * @return int   Top margin in mm (rounded)
     */
    private function calculateTopMargin(array $viewData, string $orientation = 'L'): int
    {
        $gstApplied = !empty($viewData['booking']['gst_applied']);
        $cgstRate    = (float) ($viewData['cgstRate'] ?? 0);
        $sgstRate    = (float) ($viewData['sgstRate'] ?? 0);
        $igstRate    = (float) ($viewData['igstRate'] ?? 0);
        $dueDate     = $viewData['dueDate'] ?? '';
        $address     = $viewData['recipientAddress'] ?? '';
        $isPortrait = strtoupper($orientation) === 'P';
        $margin     = $isPortrait ? 59 : 56;

        $showGstRow = ($gstApplied && ($cgstRate > 0 || $sgstRate > 0 || $igstRate > 0));
        if ($showGstRow) {
            $margin += 5;
        }

        if (!empty($dueDate)) {
            $margin += 4;
        }

        $explicitLines = count(explode("\n", str_replace("\r", '', $address)));
        $wrappedLines  = (int) ceil(strlen($address) / ($isPortrait ? 58 : 92));
        $lines         = max($explicitLines, $wrappedLines);
        if ($lines > 1) {
            $margin += ($lines - 1) * 3.5;
        }

        return (int) round($margin);
    }
}
