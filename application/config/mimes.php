<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| MIME TYPES
| -------------------------------------------------------------------
| Dipakai oleh CI Upload library untuk validasi tipe file.
*/

$mimes = [
    // ─── Gambar ──────────────────────────────────────────────────
    'hpg'   => 'image/x-hpgl',
    'png'   => ['image/png', 'image/x-png'],
    'jpg'   => ['image/jpeg', 'image/pjpeg'],
    'jpe'   => ['image/jpeg', 'image/pjpeg'],
    'jpeg'  => ['image/jpeg', 'image/pjpeg'],
    'gif'   => 'image/gif',
    'bmp'   => ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp'],
    'webp'  => 'image/webp',
    'svg'   => ['image/svg+xml', 'text/xml'],
    'ico'   => ['image/x-icon', 'image/vnd.microsoft.icon'],
    'tiff'  => 'image/tiff',
    'tif'   => 'image/tiff',

    // ─── Dokumen ─────────────────────────────────────────────────
    'pdf'   => ['application/pdf', 'application/x-download'],
    'doc'   => 'application/msword',
    'docx'  => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword', 'application/zip'],
    'xls'   => ['application/vnd.ms-excel', 'application/msexcel'],
    'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel', 'application/zip',
                'application/octet-stream'],
    'ppt'   => ['application/vnd.ms-powerpoint', 'application/mspowerpoint'],
    'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'   => ['text/plain', 'text/html'],
    'rtf'   => 'text/rtf',
    'odt'   => 'application/vnd.oasis.opendocument.text',
    'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',

    // ─── Arsip ───────────────────────────────────────────────────
    'zip'   => ['application/x-zip', 'application/zip', 'application/x-zip-compressed',
                'application/octet-stream'],
    'csv'   => ['text/csv', 'text/plain', 'application/csv'],
];

return $mimes;
