<?php
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');
switch (ENVIRONMENT) {
    case 'development': error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1); break;
    case 'production':  ini_set('display_errors', 0); error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT); break;
    default: header('HTTP/1.1 503 Service Unavailable.', TRUE, 503); echo 'Invalid environment.'; exit(1);
}
$system_path        = 'system';
$application_folder = 'application';
$view_folder        = '';
if (($_temp = realpath($system_path)) !== FALSE) $system_path = $_temp.'/';
else $system_path = rtrim($system_path, '/').'/';
define('SELF',    pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH',  dirname(__FILE__).'/');
define('SYSDIR',  basename($system_path));
define('BASEPATH',$system_path);
if (is_dir($application_folder)) {
    if (($_temp = realpath($application_folder)) !== FALSE) $application_folder = $_temp;
    define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);
} else {
    define('APPPATH', BASEPATH.$application_folder.DIRECTORY_SEPARATOR);
}
define('VIEWPATH', APPPATH.'views'.DIRECTORY_SEPARATOR);
date_default_timezone_set('Asia/Jakarta');
require_once BASEPATH.'core/CodeIgniter.php';
