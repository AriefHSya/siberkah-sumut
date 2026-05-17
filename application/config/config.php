<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Auto-detect URL: pakai env var APP_URL jika ada (Railway), fallback ke localhost
$config['base_url'] = getenv('APP_URL') ?: 'http://localhost:8080/';
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
$config['controller_trigger']  = 'c';
$config['function_trigger']    = 'm';
$config['directory_trigger']   = 'd';
$config['error_prefix']        = '<p>';
$config['error_suffix']        = '</p>';
$config['log_threshold']       = getenv('APP_ENV') === 'production' ? 3 : 1;
$config['log_path']            = '';
$config['log_file_extension']  = '';
$config['log_file_permissions']= 0644;
$config['log_date_format']     = 'Y-m-d H:i:s';
$config['cache_path']          = '';
$config['cache_query_string']  = FALSE;
$config['encryption_key']      = 'siberkah_sumut_2026_s3cr3t_k3y!!';
$config['sess_driver']         = 'files';
$config['sess_cookie_name']    = 'siberkah_sess';
$config['sess_expiration']     = 7200;
$config['sess_save_path']      = APPPATH.'cache/sessions';
$config['sess_match_ip']       = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;
$config['cookie_prefix']       = 'siberkah_';
$config['cookie_domain']       = '';
$config['cookie_path']         = '/';
$config['cookie_secure']       = FALSE;
$config['cookie_httponly']     = FALSE;
$config['standardize_newlines']= FALSE;
$config['global_xss_filtering']= FALSE;
$config['csrf_protection']     = TRUE;
$config['csrf_token_name']     = 'csrf_token';
$config['csrf_cookie_name']    = 'csrf_cookie';
$config['csrf_expire']         = 7200;
$config['csrf_regenerate']     = TRUE;
$config['csrf_exclude_uris']   = [];
$config['compress_output']     = FALSE;
$config['time_reference']      = 'local';
$config['rewrite_short_tags']  = FALSE;
$config['proxy_ips']           = '';

// App custom config
$config['app_name']    = 'SIBERKAH SUMUT';
$config['app_tagline'] = 'Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota';
$config['app_version'] = '4.1.0';
$config['app_owner']   = 'BKAD Provinsi Sumatera Utara';

// Upload config
$config['upload_path']      = FCPATH . 'uploads/';
$config['upload_max_size']  = 10240; // 10MB
$config['upload_allowed']   = 'pdf|doc|docx|xls|xlsx|jpg|jpeg|png';
