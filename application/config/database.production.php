<?php
/**
 * KONFIGURASI DATABASE PRODUCTION — SERVER PEMDA
 *
 * CARA PAKAI:
 * 1. Copy file ini → rename jadi database.php di server production
 * 2. Isi credentials sesuai database server pemda
 * 3. JANGAN commit file ini setelah diisi nilainya
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group  = 'default';
$query_builder = TRUE;

$db['default'] = [
    'dsn'          => '',
    'hostname'     => 'localhost',           // [GANTI] host DB server pemda
    'username'     => '[GANTI_DB_USER]',     // [GANTI] username DB production
    'password'     => '[GANTI_DB_PASSWORD]', // [GANTI] password DB production
    'database'     => 'siberkah_sumut',      // [GANTI jika nama DB berbeda]
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => FALSE,
    'db_debug'     => FALSE,                 // FALSE di production
    'cache_on'     => FALSE,
    'cachedir'     => '',
    'char_set'     => 'utf8mb4',
    'dbcollat'     => 'utf8mb4_unicode_ci',
    'swap_pre'     => '',
    'encrypt'      => FALSE,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => [],
    'save_queries' => FALSE,                 // FALSE di production (hemat memory)
];
