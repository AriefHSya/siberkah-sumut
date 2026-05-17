<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * XlsxReader — Baca file .xlsx tanpa dependensi eksternal
 * Menggunakan ZipArchive + SimpleXML (built-in PHP)
 * Mendukung: string, angka, formula (nilai cache), shared strings
 */
class XlsxReader
{
    private $zip;
    private $sharedStrings = [];
    private $sheets        = [];

    /**
     * Load file .xlsx
     * @throws RuntimeException jika file tidak valid
     */
    public function load($filepath)
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException('File tidak ditemukan: ' . $filepath);
        }

        $this->zip = new ZipArchive();
        if ($this->zip->open($filepath) !== TRUE) {
            throw new RuntimeException('Gagal membuka file Excel. Pastikan format .xlsx.');
        }

        $this->_loadSharedStrings();
        $this->_loadSheetList();
        return $this;
    }

    /** Ambil data sheet pertama sebagai array 2D */
    public function getRows($sheet_index = 0)
    {
        if (empty($this->sheets)) {
            throw new RuntimeException('Tidak ada sheet ditemukan dalam file.');
        }

        $sheet_name = $this->sheets[$sheet_index] ?? reset($this->sheets);
        $xml_string = $this->zip->getFromName('xl/worksheets/' . $sheet_name);
        if ($xml_string === FALSE) {
            throw new RuntimeException('Sheet tidak dapat dibaca.');
        }

        $xml  = simplexml_load_string($xml_string);
        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $row_data  = [];
            $row_index = (int)$row['r'] - 1;

            foreach ($row->c as $cell) {
                $col_letter = preg_replace('/[0-9]/', '', (string)$cell['r']);
                $col_index  = $this->_colToIndex($col_letter);
                $value      = $this->_cellValue($cell);

                // Isi kolom yang mungkin kosong (gap) dengan string kosong
                while (count($row_data) < $col_index) {
                    $row_data[] = '';
                }
                $row_data[$col_index] = $value;
            }

            $rows[$row_index] = $row_data;
        }

        $this->zip->close();
        return $rows;
    }

    /** Ambil nama sheet */
    public function getSheetNames()
    {
        return $this->sheets;
    }

    // ─── PRIVATE ─────────────────────────────────────────────────

    private function _loadSharedStrings()
    {
        $xml_string = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml_string === FALSE) return; // File bisa tidak ada jika semua sel numerik

        $xml = simplexml_load_string($xml_string);
        foreach ($xml->si as $si) {
            // Tangani teks biasa dan rich text (beberapa <r><t> di dalamnya)
            if (isset($si->t)) {
                $this->sharedStrings[] = (string)$si->t;
            } else {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                $this->sharedStrings[] = $text;
            }
        }
    }

    private function _loadSheetList()
    {
        $xml_string = $this->zip->getFromName('xl/workbook.xml');
        if ($xml_string === FALSE) {
            throw new RuntimeException('Format file tidak valid (workbook.xml tidak ditemukan).');
        }

        $xml = simplexml_load_string($xml_string);
        $xml->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        foreach ($xml->sheets->sheet as $sheet) {
            $this->sheets[] = 'sheet' . (count($this->sheets) + 1) . '.xml';
        }

        // Mapping lebih akurat via relationship
        $rel_string = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rel_string !== FALSE) {
            $rel = simplexml_load_string($rel_string);
            $this->sheets = [];
            foreach ($xml->sheets->sheet as $sheet) {
                $rid = (string)$sheet->attributes('r', TRUE)['id'];
                foreach ($rel->Relationship as $r) {
                    if ((string)$r['Id'] === $rid) {
                        $target = (string)$r['Target'];
                        // Normalisasi path
                        $this->sheets[] = ltrim(str_replace('worksheets/', '', $target), '/');
                        break;
                    }
                }
            }
            // Fallback jika mapping gagal
            if (empty($this->sheets)) {
                $this->sheets = ['sheet1.xml'];
            }
        }
    }

    private function _cellValue($cell)
    {
        $type  = (string)$cell['t'];
        $value = isset($cell->v) ? (string)$cell->v : '';

        switch ($type) {
            case 's': // Shared string
                $idx = (int)$value;
                return isset($this->sharedStrings[$idx]) ? $this->sharedStrings[$idx] : '';

            case 'b': // Boolean
                return $value === '1' ? 'TRUE' : 'FALSE';

            case 'str': // Formula string
                return isset($cell->v) ? (string)$cell->v : '';

            case 'inlineStr': // Inline string
                return isset($cell->is->t) ? (string)$cell->is->t : '';

            default: // Numerik / date
                return $value;
        }
    }

    private function _colToIndex($col)
    {
        $col   = strtoupper($col);
        $index = 0;
        $len   = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
