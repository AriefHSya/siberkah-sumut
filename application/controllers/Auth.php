<?php
/**
 * Auth.php — Controller Autentikasi SIBERKAH SUMUT
 *
 * Menangani: halaman login, proses autentikasi, dan logout.
 * Extends Guest_Controller → redirect ke dashboard jika sudah login.
 *
 * ROUTES:
 *   GET  /login         → Auth::login()   — tampilkan form login
 *   POST /login/proses  → Auth::proses()  — proses autentikasi
 *   GET  /logout        → Auth::logout()  — hapus session, redirect login
 *
 * KEAMANAN:
 *   - Password diverifikasi dengan password_verify() (bcrypt)
 *   - CSRF token wajib di form (dihandle CI3 secara otomatis)
 *   - Login/logout dicatat di user_logs
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Guest_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Parameter_model');
    }

    public function login() {
        $this->data['title'] = 'Login — SIBERKAH SUMUT';
        $setting_logo = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        $this->data['logo_prov'] = ($setting_logo && !empty($setting_logo->nilai))
            ? base_url($setting_logo->nilai) : NULL;
        $this->load->view('auth/login', $this->data);
    }

    public function proses() {
        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('password');

        if (empty($username) || empty($password)) {
            $this->session->set_flashdata('error', 'Username dan password wajib diisi.');
            redirect('login'); return;
        }

        $user = $this->User_model->get_by_username($username);

        if (!$user || !password_verify($password, $user->password)) {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('login'); return;
        }

        // Set session
        $tahun_aktif = $this->Parameter_model->get_tahun_aktif();
        $this->session->set_userdata([
            'logged_in'      => TRUE,
            'user_id'        => $user->id,
            'username'       => $user->username,
            'nama'           => $user->nama,
            'email'          => $user->email,
            'role_id'        => $user->role_id,
            'role_kode'      => $user->role_kode,
            'role_nama'      => $user->role_nama,
            'role_level'     => $user->role_level,
            'kabkota_id'     => $user->kabkota_id,
            'kabkota_nama'   => $user->kabkota_nama,
            'instansi_jenis' => $user->instansi_jenis,
            'opd_nama'       => $user->opd_nama,
            'tahun_anggaran' => $tahun_aktif,
            'must_change_password' => (int)$user->must_change_password,
        ]);

        $this->User_model->update_last_login($user->id);
        $this->db->insert('user_logs',['user_id'=>$user->id,'aksi'=>'login','keterangan'=>'Login berhasil','ip_address'=>$this->input->ip_address(),'created_at'=>date('Y-m-d H:i:s')]);

        redirect('dashboard');
    }

    public function logout() {
        if ($this->session->userdata('logged_in')) {
            $this->db->insert('user_logs',['user_id'=>$this->session->userdata('user_id'),'aksi'=>'logout','keterangan'=>'Logout','ip_address'=>$this->input->ip_address(),'created_at'=>date('Y-m-d H:i:s')]);
        }
        $this->session->sess_destroy();
        redirect('login');
    }
}
