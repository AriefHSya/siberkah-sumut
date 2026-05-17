<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

// ─── AUTH ─────────────────────────────────────────────────────
$route['login']            = 'auth/login';
$route['login/proses']     = 'auth/proses';
$route['logout']           = 'auth/logout';

// ─── DASHBOARD ────────────────────────────────────────────────
$route['dashboard']              = 'dashboard/index';
$route['dashboard/pilih-tahun']  = 'dashboard/pilih_tahun';
$route['dashboard/set-tahun']    = 'dashboard/set_tahun';

// ─── PARAMETER ────────────────────────────────────────────────
$route['parameter']                           = 'parameter/index';
$route['parameter/tahun']                     = 'parameter/tahun';
$route['parameter/tahun/simpan']              = 'parameter/tahun_simpan';
$route['parameter/tahun/set-aktif/(:num)']    = 'parameter/tahun_set_aktif/$1';
$route['parameter/tahun/hapus/(:num)']        = 'parameter/tahun_hapus/$1';
$route['parameter/batas-waktu']               = 'parameter/batas_waktu';
$route['parameter/batas-waktu/simpan']        = 'parameter/batas_waktu_simpan';
$route['parameter/batas-waktu/update/(:num)'] = 'parameter/batas_waktu_update/$1';
$route['parameter/batas-waktu/log']           = 'parameter/batas_waktu_log';
$route['parameter/bkp']                       = 'parameter/bkp';
$route['parameter/bkp/simpan']                = 'parameter/bkp_simpan';
$route['parameter/bkp/update/(:num)']         = 'parameter/bkp_update/$1';
$route['parameter/bkp/hapus/(:num)']          = 'parameter/bkp_hapus/$1';
$route['parameter/bkp/import']                = 'parameter/bkp_import';
$route['parameter/bkp/import/preview']        = 'parameter/bkp_preview_import';
$route['parameter/bkp/import/proses']         = 'parameter/bkp_proses_import';
$route['parameter/bkp/import/template']       = 'parameter/bkp_template';
$route['parameter/bkp/cetak']                 = 'parameter/bkp_cetak';
$route['parameter/pemda']                     = 'parameter/pemda';
$route['parameter/pemda/simpan-pejabat']      = 'parameter/pemda_simpan_pejabat';
$route['parameter/pemda/simpan-dokumen']      = 'parameter/pemda_simpan_dokumen';
$route['parameter/pemda/hapus-dokumen/(:num)']= 'parameter/pemda_hapus_dokumen/$1';
$route['parameter/log']                       = 'parameter/log';

// ─── ADMIN / PENGATURAN ───────────────────────────────────────
$route['admin']                               = 'admin_users/index';
$route['admin/users']                         = 'admin_users/index';
$route['admin/users/tambah']                  = 'admin_users/tambah';
$route['admin/users/simpan']                  = 'admin_users/simpan';
$route['admin/users/edit/(:num)']             = 'admin_users/edit/$1';
$route['admin/users/update/(:num)']           = 'admin_users/update/$1';
$route['admin/users/toggle/(:num)']           = 'admin_users/toggle/$1';
$route['admin/users/hapus/(:num)']            = 'admin_users/hapus/$1';
$route['admin/users/reset-pw/(:num)']         = 'admin_users/reset_pw/$1';
$route['admin/roles']                         = 'admin_roles/index';
$route['admin/roles/tambah']                  = 'admin_roles/tambah';
$route['admin/roles/simpan']                  = 'admin_roles/simpan';
$route['admin/roles/edit/(:num)']             = 'admin_roles/edit/$1';
$route['admin/roles/update/(:num)']           = 'admin_roles/update/$1';
$route['admin/roles/hapus/(:num)']            = 'admin_roles/hapus/$1';
$route['admin/roles/permissions/(:num)']      = 'admin_roles/permissions/$1';
$route['admin/roles/save-permissions/(:num)'] = 'admin_roles/save_permissions/$1';
$route['admin/roles/logs']                    = 'admin_roles/logs';

// ─── PEKERJAAN ────────────────────────────────────────────────
$route['pekerjaan']                           = 'pekerjaan/index';
$route['pekerjaan/input']                     = 'pekerjaan/input';
$route['pekerjaan/simpan']                    = 'pekerjaan/simpan';
$route['pekerjaan/detail/(:num)']             = 'pekerjaan/detail/$1';
$route['pekerjaan/edit/(:num)']               = 'pekerjaan/edit/$1';
$route['pekerjaan/update/(:num)']             = 'pekerjaan/update/$1';
$route['pekerjaan/submit/(:num)']             = 'pekerjaan/submit/$1';
$route['pekerjaan/upload-dok/(:num)']         = 'pekerjaan/upload_dok/$1';
$route['pekerjaan/hapus-dok/(:num)']          = 'pekerjaan/hapus_dok/$1';
$route['pekerjaan/cetak-permohonan/(:num)']   = 'pekerjaan/cetak_permohonan/$1';

// ─── REVIU INSPEKTORAT ────────────────────────────────────────
$route['reviu']                               = 'reviu/index';
$route['reviu/form/(:num)']                   = 'reviu/form/$1';
$route['reviu/simpan-checklist/(:num)']       = 'reviu/simpan_checklist/$1';
$route['reviu/upload-lhr/(:num)']             = 'reviu/upload_lhr/$1';
$route['reviu/putuskan/(:num)']               = 'reviu/putuskan/$1';
$route['reviu/cetak-kertas-kerja/(:num)']     = 'reviu/cetak_kertas_kerja/$1';
$route['reviu/cetak-rekap/(:num)']            = 'reviu/cetak_rekap/$1';

// ─── VERIFIKASI KAB/KOTA ──────────────────────────────────────
$route['verifikasi/kab']                      = 'verif_kab/index';
$route['verifikasi/kab/form/(:num)']          = 'verif_kab/form/$1';
$route['verifikasi/kab/upload-dok/(:num)']    = 'verif_kab/upload_dok/$1';
$route['verifikasi/kab/hapus-dok/(:num)']     = 'verif_kab/hapus_dok/$1';
$route['verifikasi/kab/putuskan/(:num)']      = 'verif_kab/putuskan/$1';
$route['verifikasi/kab/konfirmasi/(:num)']    = 'verif_kab/konfirmasi/$1';
$route['verifikasi/kab/cetak-rekap/(:num)']   = 'verif_kab/cetak_rekap/$1';

// ─── VERIFIKASI & PENYALURAN PROVINSI ────────────────────────
$route['verifikasi/prov']                         = 'verif_prov/index';
$route['verifikasi/prov/form/(:num)']             = 'verif_prov/form/$1';
$route['verifikasi/prov/putuskan/(:num)']         = 'verif_prov/putuskan/$1';
$route['verifikasi/prov/simpan-sp2d/(:num)']      = 'verif_prov/simpan_sp2d/$1';
$route['verifikasi/prov/konfirmasi-transfer/(:num)'] = 'verif_prov/konfirmasi_transfer/$1';
$route['verifikasi/prov/cetak-rekap']             = 'verif_prov/cetak_rekap';

// ─── LAPORAN ──────────────────────────────────────────────────
$route['laporan']                    = 'laporan/index';
$route['laporan/rekap-bkp']          = 'laporan/rekap_bkp';
$route['laporan/cetak-rekap-bkp']    = 'laporan/cetak_rekap_bkp';
$route['laporan/rekap-penyaluran']   = 'laporan/rekap_penyaluran';
$route['laporan/export-bkp']         = 'laporan/export_bkp';
$route['laporan/export-penyaluran']  = 'laporan/export_penyaluran';
