# QA Report: Consolidated (All) Invoice Layout

Date: 2026-08-20
Mode: report-only (`qa-only`)

## Scope

- Consolidated invoice only; the individual docket invoice was excluded from modification and regression scope.
- A4 landscape layout based on `MAL_25-26_126.pdf`.
- A4 portrait layout based on the repository's portrait sample and documented rules.
- GST identity, tax-column, logo, continuation-page, totals, terms, bank, and signatory behavior.

## Results

| Check | Result | Evidence |
|---|---|---|
| Application starts and redirects anonymous users to login | PASS | Live browser, `http://localhost:8080/login` |
| Browser console on login boundary | PASS | No error-level messages |
| Landscape first page: text-only header, metadata, dense table, no logo | PASS | `tmp/pdfs/generated/all-invoice-landscape-first-v2.png` |
| Landscape continuation/final page: repeating table headings and complete footer | PASS | `tmp/pdfs/generated/all-invoice-landscape-last-v2.png` |
| Portrait first page: compact readable columns, no overlap, no logo | PASS | `tmp/pdfs/generated/all-invoice-portrait-first-v2.png` |
| Portrait final page: totals, words, terms/bank, and signatory remain inside page | PASS | `tmp/pdfs/generated/all-invoice-portrait-last-v2.png` |
| GST invoice identities and tax columns | PASS | Automated layout test |
| Non-GST invoice hides GSTIN/SAC/PAN and tax columns | PASS | Automated layout test |
| Multi-page serial continuation | PASS | 70-row landscape and portrait generated fixtures |

## Issues

No layout defects were found in the generated consolidated invoices.

## Coverage limitation

The authenticated invoice-generation UI could not be exercised in report-only browser QA because no test login was supplied. The PDF service/view path was exercised directly by automated generation tests and inspected from rendered page images instead.

## Verdict

PASS with the authenticated-UI coverage limitation noted above.
