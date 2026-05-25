<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rbac.php — Library RBAC Dinamis SIBERKAH SUMUT
 *
 * Role-Based Access Control berbasis database.
 * Permission di-load dari DB sekali per request dan di-cache di properti $_perms.
 * Role 'superadmin' bypass semua permission check (return TRUE langsung).
 *
 * CARA PAKAI DI CONTROLLER:
 *   $this->rbac->can('pekerjaan.input')          — cek 1 permission
 *   $this->rbac->canAny(['a.view','b.view'])      — cek salah satu
 *   $this->rbac->isProvinsi()                     — cek role provinsi
 *   $this->rbac->isKabkota()                      — cek role kabkota
 *   $this->rbac->isSuperadmin()                   — cek superadmin
 *   $this->rbac->canManageUser($target_level)     — cek bisa manage user berdasar level
 *
 * CARA PAKAI DI VIEW:
 *   <?php if ($this->rbac->can('parameter.tahun.manage')): ?>
 *
 * SIDEBAR:
 *   $this->rbac->getMenus()   — daftar menu utama yang bisa diakses user
 *   getSubParameter()         — sub-menu Parameter
 *   getSubAdmin()             — sub-menu Admin
 *
 * POLA MENU:
 *   ['key','url','label','icon','perm']
 *   'perm' = NULL berarti tidak perlu cek permission (selalu tampil jika login)
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
            ['key'=>'verif_kab',        'url'=>'verifikasi/kab',  'label'=>'Verifikasi',   'icon'=>'shield-check',     'perm'=>'verif_kab.view'],
            ['key'=>'permohonan',       'url'=>'permohonan',      'label'=>'Permohonan',   'icon'=>'send',             'perm'=>'permohonan.view'],
            ['key'=>'penyaluran_kab',   'url'=>'penyaluran-kab',  'label'=>'Penyaluran',   'icon'=>'cash',             'perm'=>'penyaluran_kab.view'],
            ['key'=>'verif_prov',       'url'=>'verifikasi/prov', 'label'=>'Penyaluran',   'icon'=>'cash',             'perm'=>'verif_prov.view'],
            ['key'=>'capaian',          'url'=>'capaian',         'label'=>'Capaian',      'icon'=>'chart-bar',        'perm'=>'capaian.view'],
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
            ['key'=>'pejabat_prov','url'=>'parameter/pejabat-provinsi','label'=>'Pejabat BKAD Provinsi','icon'=>'user-star', 'perm'=>'parameter.pemda.view','provinsi_only'=>TRUE],
            ['key'=>'landing',     'url'=>'parameter/landing',      'label'=>'Tampilan Landing',    'icon'=>'photo',         'perm'=>'parameter.landing.view'],
            ['key'=>'logo',        'url'=>'parameter/logo',         'label'=>'Logo Provinsi',       'icon'=>'trademark',     'perm'=>'parameter.view',       'provinsi_only'=>TRUE],
            ['key'=>'log',         'url'=>'parameter/log',          'label'=>'Log Perubahan',       'icon'=>'history',       'perm'=>'parameter.tahun.view'],
        ];
        return array_values(array_filter($sub, function($s) {
            if (!$this->can($s['perm'])) return FALSE;
            if (!empty($s['provinsi_only']) && !$this->isProvinsi()) return FALSE;
            return TRUE;
        }));
    }

    /** Sub-menu Pengaturan — difilter berdasarkan permission + role */
    public function getSubPengaturan()
    {
        $sub = [
            ['key'=>'users',    'url'=>'admin/users',    'label'=>'Manajemen User',   'icon'=>'users',         'perm'=>'admin.user.view', 'provinsi_only'=>FALSE],
            ['key'=>'roles',    'url'=>'admin/roles',    'label'=>'Role & Hak Akses', 'icon'=>'shield-lock',   'perm'=>'admin.role.view', 'provinsi_only'=>FALSE],
            ['key'=>'telegram', 'url'=>'admin/telegram', 'label'=>'Notif Telegram',   'icon'=>'brand-telegram','perm'=>'admin.view',      'provinsi_only'=>TRUE],
        ];
        return array_values(array_filter($sub, function($s) {
            if (!$this->can($s['perm'])) return FALSE;
            if (!empty($s['provinsi_only']) && !$this->isProvinsi()) return FALSE;
            return TRUE;
        }));
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
            'verif_kab'   => ['label' => 'Verifikasi Kab/Kota',  'icon' => 'shield-check'],
            'permohonan'  => ['label' => 'Permohonan Pencairan','icon' => 'send'],
            'verif_prov'  => ['label' => 'Verifikasi Provinsi', 'icon' => 'cash'],
            'capaian'        => ['label' => 'Capaian Output',            'icon' => 'chart-bar'],
            'penyaluran_kab' => ['label' => 'Penyaluran Dana (Kab)',  'icon' => 'cash'],
            'penyaluran'     => ['label' => 'Penyaluran Dana (Prov)', 'icon' => 'transfer'],
            'laporan'   => ['label' => 'Laporan & Cetak',      'icon' => 'report'],
            'admin'     => ['label' => 'Pengaturan & RBAC',    'icon' => 'settings'],
        ];
    }
}
