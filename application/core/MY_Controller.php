<?php
/**
 * MY_Controller.php — Base Controller SIBERKAH SUMUT
 *
 * Tiga class hierarki controller:
 *   MY_Controller    — base: inject $data global, helper render(), json(), log_aktivitas()
 *   Auth_Controller  — extends MY_Controller: guard session, load RBAC, shortcut properti user
 *   Guest_Controller — extends MY_Controller: redirect ke dashboard jika sudah login
 *
 * POLA PENGGUNAAN:
 *   - Halaman terproteksi  → extends Auth_Controller
 *   - Halaman publik/login → extends Guest_Controller
 *   - Halaman landing      → extends MY_Controller (atau langsung CI_Controller)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller — Base controller semua controller SIBERKAH
 *
 * Menyediakan:
 *   - $data[]             : array shared yang otomatis di-pass ke semua view
 *   - render($view)       : load view dalam layout main.php
 *   - render_plain($view) : load view tanpa layout (untuk cetak/PDF)
 *   - json($data)         : output JSON + header Content-Type
 *   - log_aktivitas()     : tulis ke tabel user_logs
 */
class MY_Controller extends CI_Controller
{
    /** @var array Data yang di-pass ke semua view (app_name, base_url, dll.) */
    protected $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->data['app_name']    = $this->config->item('app_name');
        $this->data['app_tagline'] = $this->config->item('app_tagline');
        $this->data['app_version'] = $this->config->item('app_version');
        $this->data['app_owner']   = $this->config->item('app_owner');
        $this->data['base_url']    = base_url();
    }

    /**
     * Render view dalam layout admin (layouts/main.php).
     * Gunakan untuk semua halaman terproteksi normal.
     *
     * @param string $view  Path view relatif dari application/views/
     * @param array  $extra Data tambahan (merge dengan $this->data)
     */
    protected function render($view, $extra = [])
    {
        $data = array_merge($this->data, $extra);
        $data['content_view'] = $view;
        $this->load->view('layouts/main', $data);
    }

    /**
     * Render view tanpa layout — untuk halaman cetak/print/PDF.
     * Output HTML murni tanpa sidebar/topbar.
     *
     * @param string $view  Path view
     * @param array  $extra Data tambahan
     */
    protected function render_plain($view, $extra = [])
    {
        $data = array_merge($this->data, $extra);
        $this->load->view($view, $data);
    }

    /**
     * Output JSON dan langsung exit.
     * Digunakan untuk endpoint AJAX (drag-drop urutan, mark notif, dll.)
     *
     * @param mixed $data  Data yang di-json_encode
     * @param int   $code  HTTP status code (default 200)
     */
    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Catat aktivitas user ke tabel user_logs.
     * Panggil setelah setiap aksi penting (simpan, hapus, approve, dll.)
     *
     * @param string $aksi       Kode aksi, mis. 'pekerjaan.submit'
     * @param string $keterangan Deskripsi singkat, mis. 'Submit pekerjaan id=42'
     */
    protected function log_aktivitas($aksi, $keterangan = '')
    {
        $uid = $this->session->userdata('user_id');
        if (!$uid) return;
        $this->db->insert('user_logs', [
            'user_id'    => $uid,
            'aksi'       => $aksi,
            'keterangan' => $keterangan,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

/**
 * Auth_Controller — Base controller untuk semua halaman terproteksi
 *
 * Guard session: redirect ke /login jika belum login.
 * Properti shortcut dari session tersedia di semua controller turunan:
 *   $this->user_id, $this->role_kode, $this->role_level,
 *   $this->kabkota_id, $this->tahun
 *
 * Method helper:
 *   requirePerm($kode) — redirect ke dashboard jika tidak punya permission
 */
class Auth_Controller extends MY_Controller
{
    /** @var int ID user yang sedang login */
    protected $user_id;
    /** @var string Kode role: 'superadmin'|'admin_provinsi'|'skpkd_kabkota'|dll. */
    protected $role_kode;
    /** @var int Level role: 1=superadmin … 9 (semakin kecil = semakin tinggi) */
    protected $role_level;
    /** @var int|null ID kabkota user; NULL untuk role provinsi */
    protected $kabkota_id;
    /** @var string Tahun anggaran aktif dari session, mis. '2026' */
    protected $tahun;

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('login'); exit;
        }
        $this->load->library('Rbac');
        $this->user_id    = $this->session->userdata('user_id');
        $this->role_kode  = $this->session->userdata('role_kode');
        $this->role_level = $this->session->userdata('role_level');
        $this->kabkota_id = $this->session->userdata('kabkota_id');
        $this->tahun      = $this->session->userdata('tahun_anggaran') ?? date('Y');

        $this->data['current_user'] = (object)[
            'id'            => $this->user_id,
            'nama'          => $this->session->userdata('nama'),
            'username'      => $this->session->userdata('username'),
            'role_id'       => $this->session->userdata('role_id'),
            'role_kode'     => $this->role_kode,
            'role_nama'     => $this->session->userdata('role_nama'),
            'role_level'    => $this->role_level,
            'kabkota_id'    => $this->kabkota_id,
            'kabkota_nama'  => $this->session->userdata('kabkota_nama'),
            'instansi_jenis'=> $this->session->userdata('instansi_jenis'),
            'opd_nama'      => $this->session->userdata('opd_nama'),
        ];
        $this->data['tahun_anggaran'] = $this->tahun;
        $this->data['active_menu']    = '';
        $this->data['active_sub']     = '';

        $this->load->model('Notifikasi_model');
        $this->data['notif_count']  = $this->Notifikasi_model->count_unread($this->user_id);
        $this->data['notif_recent'] = $this->Notifikasi_model->get_recent($this->user_id, 5);

        // Selalu tersedia di semua view (untuk dropdown ganti tahun di top-bar)
        $this->load->model('Parameter_model');
        $this->data['tahun_list_global'] = $this->Parameter_model->get_all_tahun();
    }

    /**
     * Guard permission — redirect jika user tidak punya kode permission ini.
     * Panggil di constructor (guard seluruh controller) atau di method individual.
     *
     * @param string $kode        Kode permission, mis. 'pekerjaan.input'
     * @param string $redirect_to URL redirect jika ditolak (default: dashboard)
     */
    protected function requirePerm($kode, $redirect_to = 'dashboard')
    {
        if (!$this->rbac->can($kode)) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            redirect($redirect_to); exit;
        }
    }
}

/**
 * Guest_Controller — Base controller untuk halaman publik (login)
 *
 * Redirect ke dashboard jika user sudah login,
 * kecuali method 'logout' (diizinkan agar Auth::logout() bisa berjalan
 * meski session masih aktif).
 */
class Guest_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in')
            && $this->router->fetch_method() !== 'logout') {
            redirect('dashboard'); exit;
        }
    }
}
