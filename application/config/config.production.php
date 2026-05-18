<?php
/**
 * KONFIGURASI PRODUCTION — SERVER PEMDA
 *
 * CARA PAKAI:
 * 1. Copy file ini → rename jadi config.php di server production
 * 2. Isi semua nilai [GANTI] sesuai server pemda
 * 3. JANGAN commit file ini setelah diisi nilainya
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// [GANTI] URL domain production pemda
$config['base_url']            = 'https://siberkah.sumutprov.go.id/';
$config['index_page']          = '';
$config['uri_protocol']        = 'REQUEST_URI';
$config['url_suffix']          = '';
$config['language']            = 'english';
$config['charset']             = 'UTF-8';
$config['enable_hooks']        = FALSE;
$config['subclass_prefix']     = 'MY_';
$config['composer_autoload']   = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['allow_get_array']     = TRUE;
$config['enable_query_strings']= FALSE;

// Log: error saja di production
$config['log_threshold']       = 3;
$config['log_path']            = APPPATH . 'logs/';
$config['log_file_extension']  = '';
$config['log_file_permissions']= 0640;
$config['log_date_format']     = 'Y-m-d H:i:s';
$config['cache_path']          = '';
$config['cache_query_string']  = FALSE;

// [GANTI] Generate key baru: php -r "echo bin2hex(random_bytes(32));"
$config['encryption_key']      = '[GANTI_RANDOM_KEY_64_CHAR_HEX]';

// Session: database driver lebih aman untuk production
$config['sess_driver']             = 'database';
$config['sess_cookie_name']        = 'siberkah_sess';
$config['sess_expiration']         = 7200;
$config['sess_save_path']          = 'ci_sessions'; // nama tabel di DB
$config['sess_match_ip']           = TRUE;
$config['sess_time_to_update']     = 300;
$config['sess_regenerate_destroy'] = TRUE;

// Cookie security — aktifkan HTTPS
$config['cookie_prefix']       = 'siberkah_';
$config['cookie_domain']       = '.sumutprov.go.id';
$config['cookie_path']         = '/';
$config['cookie_secure']       = TRUE;
$config['cookie_httponly']     = TRUE;

$config['standardize_newlines']= FALSE;
$config['global_xss_filtering']= FALSE;
$config['csrf_protection']     = TRUE;
$config['csrf_token_name']     = 'csrf_token';
$config['csrf_cookie_name']    = 'csrf_cookie';
$config['csrf_expire']         = 7200;
$config['csrf_regenerate']     = TRUE;
$config['csrf_exclude_uris']   = [];
$config['compress_output']     = TRUE;
$config['time_reference']      = 'local';
$config['rewrite_short_tags']  = FALSE;
$config['proxy_ips']           = '';

// App identity
$config['app_name']    = 'SIBERKAH SUMUT';
$config['app_tagline'] = 'Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota';
$config['app_version'] = '4.1.0';
$config['app_owner']   = 'BKAD Provinsi Sumatera Utara';

// Upload config
$config['upload_path']     = FCPATH . 'uploads/';
$config['upload_max_size'] = 10240;
$config['upload_allowed']  = 'pdf|doc|docx|xls|xlsx|jpg|jpeg|png';
