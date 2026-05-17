<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
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

    protected function render($view, $extra = [])
    {
        $data = array_merge($this->data, $extra);
        $data['content_view'] = $view;
        $this->load->view('layouts/main', $data);
    }

    protected function render_plain($view, $extra = [])
    {
        $data = array_merge($this->data, $extra);
        $this->load->view($view, $data);
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

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

class Auth_Controller extends MY_Controller
{
    protected $user_id;
    protected $role_kode;
    protected $role_level;
    protected $kabkota_id;
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

    protected function requirePerm($kode, $redirect_to = 'dashboard')
    {
        if (!$this->rbac->can($kode)) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            redirect($redirect_to); exit;
        }
    }
}

class Guest_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Izinkan logout meski sudah login; blokir halaman lain jika sudah login
        if ($this->session->userdata('logged_in')
            && $this->router->fetch_method() !== 'logout') {
            redirect('dashboard'); exit;
        }
    }
}
