<?php
/**
 * XlsxWriter.php — Minimal XLSX Generator
 *
 * Membuat file Excel .xlsx tanpa dependensi Composer.
 * Menggunakan ZipArchive (bawaan PHP 7.4+) dan format Open XML.
 *
 * CARA PAKAI:
 *   $xlsx = new XlsxWriter();
 *   $xlsx->writeSheet('Sheet1', $rows, $headers);
 *   $xlsx->download('namafile.xlsx');
 *
 * $headers: array of [label, format]
 *   format: 'string' | 'number' | 'rupiah' | 'date'
 * $rows: array of arrays (indexed, sesuai urutan $headers)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class XlsxWriter
{
    private $sheets = [];

    /**
     * Tambah sheet ke workbook
     * @param string $name    Nama tab sheet
     * @param array  $rows    Data baris (array of arrays)
     * @param array  $headers Array of ['label'=>'...', 'format'=>'string|number|rupiah|date']
     */
    public function writeSheet($name, $rows, $headers = [])
    {
        $this->sheets[] = [
            'name'    => $name,
            'rows'    => $rows,
            'headers' => $headers,
        ];
    }

    /** Kirim file ke browser untuk diunduh */
    public function download($filename = 'export.xlsx')
    {
        $content = $this->_build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: max-age=0');
        echo $content;
        exit;
    }

    private function _build()
    {
        // Kumpulkan semua string unik untuk shared strings
        $strings     = [];
        $strIndex    = [];
        $sheetsXml   = [];
        $sheetRels   = [];

        foreach ($this->sheets as $si => $sheet) {
            $sheetNum = $si + 1;
            $xml      = $this->_buildSheet($sheet, $strings, $strIndex);
            $sheetsXml[$sheetNum] = $xml;
            $sheetRels[] = '<Relationship Id="rId' . $sheetNum . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetNum . '.xml"/>';
        }

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $ctypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach (array_keys($sheetsXml) as $n) {
            $ctypes .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ctypes .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $ctypes);

        // _rels/.rels
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            implode('', $sheetRels) .
            '<Relationship Id="rId' . (count($this->sheets)+1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' .
            '<Relationship Id="rId' . (count($this->sheets)+2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $sheetEntries = '';
        foreach ($this->sheets as $si => $sheet) {
            $sheetEntries .= '<sheet name="' . htmlspecialchars($sheet['name'], ENT_XML1) . '" sheetId="' . ($si+1) . '" r:id="rId' . ($si+1) . '"/>';
        }
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' . $sheetEntries . '</sheets>' .
            '</workbook>');

        // xl/styles.xml
        $zip->addFromString('xl/styles.xml', $this->_buildStyles());

        // xl/sharedStrings.xml
        $zip->addFromString('xl/sharedStrings.xml', $this->_buildSharedStrings($strings));

        // xl/worksheets/sheetN.xml
        foreach ($sheetsXml as $n => $xml) {
            $zip->addFromString("xl/worksheets/sheet{$n}.xml", $xml);
        }

        $zip->close();
        $content = file_get_contents($tmp);
        @unlink($tmp);
        return $content;
    }

    private function _buildSheet($sheet, &$strings, &$strIndex)
    {
        $headers = $sheet['headers'];
        $rows    = $sheet['rows'];
        $cols    = count($headers) ?: (isset($rows[0]) ? count($rows[0]) : 0);

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Column widths
        if ($cols > 0) {
            $xml .= '<cols>';
            for ($c = 1; $c <= $cols; $c++) {
                $fmt = isset($headers[$c-1]['format']) ? $headers[$c-1]['format'] : 'string';
                $w   = ($fmt === 'rupiah') ? 20 : (($fmt === 'date') ? 14 : 30);
                $xml .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $rowNum = 1;

        // Header row (style 1 = bold)
        if (!empty($headers)) {
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($headers as $ci => $h) {
                $label = is_array($h) ? ($h['label'] ?? '') : $h;
                $col   = $this->_colName($ci);
                $si    = $this->_strIdx($label, $strings, $strIndex);
                $xml  .= '<c r="' . $col . $rowNum . '" t="s" s="1"><v>' . $si . '</v></c>';
            }
            $xml .= '</row>';
            $rowNum++;
        }

        // Data rows
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            $values = array_values((array)$row);
            foreach ($values as $ci => $val) {
                $col = $this->_colName($ci);
                $fmt = isset($headers[$ci]['format']) ? $headers[$ci]['format'] : 'string';
                $xml .= $this->_cell($col, $rowNum, $val, $fmt, $strings, $strIndex);
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData>';
        $xml .= '<sheetView tabSelected="1" workbookViewId="0"><selection activeCell="A1" sqref="A1"/></sheetView>';
        $xml .= '</worksheet>';
        return $xml;
    }

    private function _cell($col, $row, $val, $fmt, &$strings, &$strIndex)
    {
        $ref = $col . $row;
        if ($val === NULL || $val === '') {
            return '<c r="' . $ref . '"><v></v></c>';
        }
        if ($fmt === 'number' || $fmt === 'rupiah') {
            $num = preg_replace('/[^0-9\.\-]/', '', str_replace(',', '', $val));
            $s   = ($fmt === 'rupiah') ? ' s="2"' : '';
            return '<c r="' . $ref . '"' . $s . '><v>' . (float)$num . '</v></c>';
        }
        // String
        $si = $this->_strIdx((string)$val, $strings, $strIndex);
        return '<c r="' . $ref . '" t="s"><v>' . $si . '</v></c>';
    }

    private function _strIdx($val, &$strings, &$strIndex)
    {
        if (!isset($strIndex[$val])) {
            $strIndex[$val] = count($strings);
            $strings[]      = $val;
        }
        return $strIndex[$val];
    }

    private function _colName($idx)
    {
        $col = '';
        $idx++;
        while ($idx > 0) {
            $idx--;
            $col = chr(65 + ($idx % 26)) . $col;
            $idx = (int)($idx / 26);
        }
        return $col;
    }

    private function _buildSharedStrings($strings)
    {
        $count = count($strings);
        $xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml  .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';
        foreach ($strings as $s) {
            $xml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    private function _buildStyles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>' .
        '<fonts count="2">' .
          '<font><sz val="11"/><name val="Calibri"/></font>' .
          '<font><b/><sz val="11"/><name val="Calibri"/></font>' .
        '</fonts>' .
        '<fills count="3">' .
          '<fill><patternFill patternType="none"/></fill>' .
          '<fill><patternFill patternType="gray125"/></fill>' .
          '<fill><patternFill patternType="solid"><fgColor rgb="FF1A5EA8"/></patternFill></fill>' .
        '</fills>' .
        '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' .
        '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
        '<cellXfs count="3">' .
          '<xf numFmtId="0"  fontId="0" fillId="0" borderId="0" xfId="0"/>' .
          '<xf numFmtId="0"  fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center"/></xf>' .
          '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"><alignment horizontal="right"/></xf>' .
        '</cellXfs>' .
        '</styleSheet>';
    }
}
