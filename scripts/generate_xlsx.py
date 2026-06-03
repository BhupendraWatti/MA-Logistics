import sys
import json
import zipfile

def get_column_letter(col_idx):
    letter = ""
    while col_idx > 0:
        col_idx, remainder = divmod(col_idx - 1, 26)
        letter = chr(65 + remainder) + letter
    return letter

def escape_xml(val):
    if val is None:
        return ""
    s = str(val)
    # Remove control characters that are invalid in XML
    cleaned = []
    for char in s:
        cp = ord(char)
        if (cp == 0x9 or cp == 0xA or cp == 0xD or 
            (0x20 <= cp <= 0xD7FF) or 
            (0xE000 <= cp <= 0xFFFD) or 
            (0x10000 <= cp <= 0x10FFFF)):
            cleaned.append(char)
    s = "".join(cleaned)
    return s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;").replace("'", "&apos;")

def generate_xlsx(input_json_path, output_xlsx_path):
    with open(input_json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    headers = data['headers']
    rows = data['rows']
    
    # Calculate column widths
    col_widths = [11] * len(headers)
    for col_idx, header in enumerate(headers):
        col_widths[col_idx] = max(col_widths[col_idx], len(str(header)) + 3)
    for row in rows:
        for col_idx, val in enumerate(row):
            if col_idx < len(col_widths):
                col_widths[col_idx] = max(col_widths[col_idx], len(str(val or '')) + 3)

    # Apply safety limit of 60 characters to column width
    for i in range(len(col_widths)):
        col_widths[i] = min(col_widths[i], 60)

    # Build cols XML
    cols_xml = "<cols>"
    for idx, width in enumerate(col_widths, 1):
        cols_xml += f'<col min="{idx}" max="{idx}" width="{width}" customWidth="1"/>'
    cols_xml += "</cols>"

    # Build rows XML
    rows_xml = ""
    
    # Write header row (Row 1)
    rows_xml += '<row r="1" ht="28" customHeight="1">'
    for col_idx, header in enumerate(headers, 1):
        ref = f"{get_column_letter(col_idx)}1"
        escaped = escape_xml(header)
        # Header style is index 1
        rows_xml += f'<c r="{ref}" s="1" t="inlineStr"><is><t>{escaped}</t></is></c>'
    rows_xml += '</row>'
    
    # Write data rows
    for row_idx, row in enumerate(rows, 2):
        rows_xml += f'<row r="{row_idx}" ht="20" customHeight="1">'
        for col_idx, val in enumerate(row, 1):
            ref = f"{get_column_letter(col_idx)}{row_idx}"
            header_name = headers[col_idx - 1] if col_idx - 1 < len(headers) else ""
            
            # Determine alignment/style index
            is_numeric = any(term in header_name for term in ["WEIGHT", "LENGTH", "WIDTH", "HEIGHT", "PIECES", "RATE", "FREIGHT", "SURCHARGE", "AMOUNT", "CHARGES", "TAXABLE", "DDC", "SSC", "BTC", "FLC", "DOC", "TSP", "TCP", "ADO", "FEES", "STORAGE", "HANDLING"])
            
            if val is None or val == "":
                rows_xml += f'<c r="{ref}" s="0"/>'
            elif is_numeric:
                # Try to parse as float/int
                try:
                    num_val = float(val)
                    if num_val.is_integer() and "PIECES" in header_name:
                        # Pieces (style 6)
                        rows_xml += f'<c r="{ref}" s="6" t="n"><v>{int(num_val)}</v></c>'
                    elif any(term in header_name for term in ["RATE", "FREIGHT", "AMOUNT", "CHARGES", "TAXABLE", "DDC", "SSC", "BTC", "FLC", "DOC", "TSP", "TCP", "ADO", "FEES", "STORAGE", "HANDLING"]):
                        # Currency (style 5)
                        rows_xml += f'<c r="{ref}" s="5" t="n"><v>{num_val}</v></c>'
                    elif "PIECES" in header_name:
                        # Pieces but not integer
                        rows_xml += f'<c r="{ref}" s="6" t="n"><v>{num_val}</v></c>'
                    else:
                        # Decimal/other numeric (style 7)
                        rows_xml += f'<c r="{ref}" s="7" t="n"><v>{num_val}</v></c>'
                except ValueError:
                    # Fallback to string if not parseable
                    escaped = escape_xml(val)
                    rows_xml += f'<c r="{ref}" s="2" t="inlineStr"><is><t>{escaped}</t></is></c>'
            else:
                escaped = escape_xml(val)
                # Check for center alignment dates/IDs
                is_center = any(term in header_name for term in ["DATE", "TIME", "SR NO", "AWB NO", "DOCKET NO", "GST APPLIED", "GSTIN", "PAN", "SAC CODE"])
                style_idx = 3 if is_center else 2
                rows_xml += f'<c r="{ref}" s="{style_idx}" t="inlineStr"><is><t>{escaped}</t></is></c>'
        rows_xml += '</row>'

    # Build XML files
    content_types_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>"""

    rels_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>"""

    workbook_rels_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>"""

    workbook_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="AWB Export" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>"""

    styles_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="2">
    <numFmt numFmtId="164" formatCode="[$₹-380A] #,##0.00"/>
    <numFmt numFmtId="165" formatCode="#,##0.0"/>
  </numFmts>
  <fonts count="2">
    <font>
      <sz val="10"/>
      <color rgb="FF000000"/>
      <name val="Segoe UI"/>
    </font>
    <font>
      <b/>
      <sz val="10"/>
      <color rgb="FFFFFFFF"/>
      <name val="Segoe UI"/>
    </font>
  </fonts>
  <fills count="3">
    <fill>
      <patternFill patternType="none"/>
    </fill>
    <fill>
      <patternFill patternType="gray125"/>
    </fill>
    <fill>
      <patternFill patternType="solid">
        <fgColor rgb="FF1F4E78"/>
      </patternFill>
    </fill>
  </fills>
  <borders count="2">
    <border>
      <left/>
      <right/>
      <top/>
      <bottom/>
      <diagonal/>
    </border>
    <border>
      <left style="thin"><color rgb="FFD9D9D9"/></left>
      <right style="thin"><color rgb="FFD9D9D9"/></right>
      <top style="thin"><color rgb="FFD9D9D9"/></top>
      <bottom style="thin"><color rgb="FFD9D9D9"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="8">
    <!-- Style 0: Default / Data Left -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center" wrapText="1"/>
    </xf>
    <!-- Style 1: Header -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- Style 2: Data Left -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center" wrapText="1"/>
    </xf>
    <!-- Style 3: Data Center -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- Style 4: Data Right -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- Style 5: Currency Right -->
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- Style 6: Pieces (Integer) Right -->
    <xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- Style 7: Decimal Right -->
    <xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
  <dxfs count="0"/>
  <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>"""

    # Generate dimensions and autofilter
    last_col = get_column_letter(len(headers))
    dimensions_xml = f'<dimension ref="A1:{last_col}{len(rows) + 1}"/>' if headers else ""
    autofilter_xml = f'<autoFilter ref="A1:{last_col}{len(rows) + 1}"/>' if headers else ""

    sheet_xml = f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  {dimensions_xml}
  <sheetViews>
    <sheetView tabSelected="1" workbookViewId="0">
      <showGridLines>1</showGridLines>
    </sheetView>
  </sheetViews>
  <sheetFormatPr defaultRowHeight="15"/>
  {cols_xml}
  <sheetData>
    {rows_xml}
  </sheetData>
  {autofilter_xml}
</worksheet>"""

    # Pack into zip
    with zipfile.ZipFile(output_xlsx_path, 'w', zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", content_types_xml.encode('utf-8'))
        z.writestr("_rels/.rels", rels_xml.encode('utf-8'))
        z.writestr("xl/_rels/workbook.xml.rels", workbook_rels_xml.encode('utf-8'))
        z.writestr("xl/workbook.xml", workbook_xml.encode('utf-8'))
        z.writestr("xl/styles.xml", styles_xml.encode('utf-8'))
        z.writestr("xl/worksheets/sheet1.xml", sheet_xml.encode('utf-8'))

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python generate_xlsx.py <input_json> <output_xlsx>")
        sys.exit(1)
    generate_xlsx(sys.argv[1], sys.argv[2])
