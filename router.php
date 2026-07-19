<?php
/**
 * router.php — Router untuk PHP Built-in Development Server
 * Penggunaan: php -S localhost:8080 router.php
 */
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . DIRECTORY_SEPARATOR . ltrim($uri, '/');

// Layani file statis langsung (CSS, JS, gambar, font, dll)
if ($uri !== '/' && is_file($file)) {
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'pdf'  => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    if (isset($mime[$ext])) {
        header('Content-Type: ' . $mime[$ext]);
    }
    readfile($file);
    exit;
}

// Semua request lain → CI3 front controller
require_once __DIR__ . '/index.php';
