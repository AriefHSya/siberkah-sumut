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
 *   - Lockout: akun terkunci otomatis setelah 5x percobaan login gagal berturut-turut
 *     (kolom users.failed_login_attempts, users.locked_at). Hanya Admin Provinsi/
 *     Superadmin atau SKPKD Kab/Kota (untuk user di kab/kotanya) yang dapat membuka
 *     kembali via menu Manajemen User — Admin_users::unlock()
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

        $user = $this->User_model->get_for_login($username);

        if (!$user) {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('login'); return;
        }

        // Akun terkunci karena 5x percobaan login gagal — hanya Admin Provinsi atau
        // SKPKD Kab/Kota (menu Manajemen User) yang dapat membuka kembali.
        if (!empty($user->locked_at)) {
            $this->session->set_flashdata('error',
                'Akun ini terkunci karena terlalu banyak percobaan login gagal. '.
                'Hubungi Admin Provinsi atau SKPKD Kab/Kota untuk membuka kembali akun Anda.');
            redirect('login'); return;
        }

        if (!$user->is_active || !password_verify($password, $user->password)) {
            if ($user->is_active) {
                $attempts = (int)$user->failed_login_attempts + 1;
                $this->User_model->catat_login_gagal($user->id, $attempts);
                if ($attempts >= User_model::MAX_LOGIN_ATTEMPTS) {
                    $this->session->set_flashdata('error',
                        'Username atau password salah. Akun Anda telah dikunci karena 5x '.
                        'percobaan login gagal. Hubungi Admin Provinsi atau SKPKD Kab/Kota '.
                        'untuk membuka kembali akun Anda.');
                } else {
                    $sisa = User_model::MAX_LOGIN_ATTEMPTS - $attempts;
                    $this->session->set_flashdata('error',
                        'Username atau password salah. Sisa percobaan: '.$sisa.'.');
                }
            } else {
                $this->session->set_flashdata('error', 'Username atau password salah.');
            }
            redirect('login'); return;
        }

        // Login berhasil — reset penghitung percobaan gagal
        $this->User_model->reset_login_gagal($user->id);

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
