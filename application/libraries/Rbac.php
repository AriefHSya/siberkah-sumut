<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rbac Library — SIBERKAH SUMUT v4
 * Dinamis dari database, mendukung role custom
 */
class Rbac
{
    protected $CI;
    protected $_perms = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Cek 1 permission */
    public function can($kode)
    {
        if (!$this->CI->session->userdata('logged_in')) return FALSE;
        if ($this->CI->session->userdata('role_kode') === 'superadmin') return TRUE;
        return in_array($kode, $this->_load());
    }

    /** Cek salah satu dari beberapa permission */
    public function canAny(array $kodes)
    {
        foreach ($kodes as $k) { if ($this->can($k)) return TRUE; }
        return FALSE;
    }

    /** Require permission — exit jika tidak punya */
    public function requirePermission($kode, $redirect = 'dashboard')
    {
        if (!$this->can($kode)) {
            $this->CI->session->set_flashdata('error', 'Akses ditolak.');
            redirect($redirect); exit;
        }
    }

    /** Cek apakah sudah login */
    public function requireLogin()
    {
        if (!$this->CI->session->userdata('logged_in')) {
            redirect('login'); exit;
        }
    }

    /** Load permission dari DB (cache per request) */
    private function _load()
    {
        if ($this->_perms !== NULL) return $this->_perms;
        $role_id = $this->CI->session->userdata('role_id');
        if (!$role_id) { $this->_perms = []; return []; }
        $q = $this->CI->db
            ->select('p.kode')
            ->from('role_permissions rp')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $role_id)
            ->get();
        $this->_perms = array_column($q->result_array(), 'kode');
        return $this->_perms;
    }

    public function resetCache() { $this->_perms = NULL; }

    /** Semua permission untuk role tertentu (untuk form edit role) */
    public function getPermsByRole($role_id)
    {
        $q = $this->CI->db
            ->select('p.kode')
            ->from('role_permissions rp')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $role_id)->get();
        return array_column($q->result_array(), 'kode');
    }

    /** Daftar menu navigasi berdasarkan permission user */
    public function getMenus()
    {
        $all = [
            ['key'=>'dashboard',  'url'=>'dashboard',       'label'=>'Dashboard',    'icon'=>'layout-dashboard', 'perm'=>'dashboard.view'],
            ['key'=>'pekerjaan',  'url'=>'pekerjaan',       'label'=>'Pekerjaan',    'icon'=>'file-text',        'perm'=>'pekerjaan.view'],
            ['key'=>'reviu',      'url'=>'reviu',           'label'=>'Reviu',        'icon'=>'clipboard-check',  'perm'=>'reviu.view'],
            ['key'=>'verif_kab',  'url'=>'verifikasi/kab',  'label'=>'Verifikasi',   'icon'=>'shield-check',     'perm'=>'verif_kab.view'],
            ['key'=>'verif_prov', 'url'=>'verifikasi/prov', 'label'=>'Penyaluran',   'icon'=>'cash',             'perm'=>'verif_prov.view'],
            ['key'=>'laporan',    'url'=>'laporan',         'label'=>'Laporan',      'icon'=>'report',           'perm'=>'laporan.view'],
            ['key'=>'parameter',  'url'=>'parameter',       'label'=>'Parameter',    'icon'=>'adjustments-horizontal','perm'=>'parameter.view'],
            ['key'=>'admin',      'url'=>'admin/users',     'label'=>'Pengaturan',   'icon'=>'settings',         'perm'=>'admin.view'],
        ];
        return array_values(array_filter($all, fn($m) => $this->can($m['perm'])));
    }

    /** Sub-menu Parameter berdasarkan permission */
    public function getSubParameter()
    {
        $sub = [
            ['key'=>'tahun',       'url'=>'parameter/tahun',        'label'=>'Data Tahun',          'icon'=>'calendar',      'perm'=>'parameter.tahun.view'],
            ['key'=>'batas_waktu', 'url'=>'parameter/batas-waktu',  'label'=>'Batas Waktu',         'icon'=>'calendar-time', 'perm'=>'parameter.batas_waktu.view'],
            ['key'=>'bkp',         'url'=>'parameter/bkp',          'label'=>'Data BKP',            'icon'=>'database',      'perm'=>'parameter.bkp.view'],
            ['key'=>'pemda',       'url'=>'parameter/pemda',        'label'=>'Data Umum Pemda',     'icon'=>'building-community','perm'=>'parameter.pemda.view'],
            ['key'=>'log',         'url'=>'parameter/log',          'label'=>'Log Perubahan',       'icon'=>'history',       'perm'=>'parameter.tahun.view'],
        ];
        return array_values(array_filter($sub, fn($s) => $this->can($s['perm'])));
    }

    /** Sub-menu Pengaturan */
    public function getSubPengaturan()
    {
        return [
            ['key'=>'users', 'url'=>'admin/users', 'label'=>'Manajemen User',   'icon'=>'users'],
            ['key'=>'roles', 'url'=>'admin/roles', 'label'=>'Role & Hak Akses', 'icon'=>'shield-lock'],
        ];
    }

    // Helpers role
    public function isProvinsi()   { return in_array($this->CI->session->userdata('role_kode'), ['superadmin','admin_provinsi']); }
    public function isKabkota()    { return in_array($this->CI->session->userdata('role_kode'), ['skpkd_kabkota','inspektorat','opd_teknis']); }
    public function isSuperadmin() { return $this->CI->session->userdata('role_kode') === 'superadmin'; }
    public function getLevel()     { return (int)($this->CI->session->userdata('role_level') ?? 99); }

    /**
     * Cek apakah user boleh manage user lain
     * Rule: hanya bisa manage user dengan level LEBIH TINGGI (angka lebih besar)
     */
    public function canManageUser($target_role_level)
    {
        if ($this->isSuperadmin()) return TRUE;
        return $this->getLevel() < $target_role_level;
    }

    /** Daftar modul metadata untuk form permission */
    public function getModulMeta()
    {
        return [
            'dashboard' => ['label' => 'Dashboard',            'icon' => 'layout-dashboard'],
            'parameter' => ['label' => 'Parameter',            'icon' => 'adjustments-horizontal'],
            'pekerjaan' => ['label' => 'Input Pekerjaan',      'icon' => 'file-text'],
            'reviu'     => ['label' => 'Reviu Inspektorat',    'icon' => 'clipboard-check'],
            'verif_kab' => ['label' => 'Verifikasi Kab/Kota',  'icon' => 'shield-check'],
            'verif_prov'=> ['label' => 'Verifikasi Provinsi',  'icon' => 'cash'],
            'capaian'   => ['label' => 'Capaian Output',       'icon' => 'chart-bar'],
            'penyaluran'=> ['label' => 'Penyaluran Dana',      'icon' => 'transfer'],
            'laporan'   => ['label' => 'Laporan & Cetak',      'icon' => 'report'],
            'admin'     => ['label' => 'Pengaturan & RBAC',    'icon' => 'settings'],
        ];
    }
}
