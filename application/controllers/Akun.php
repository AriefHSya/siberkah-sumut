<?php
/**
 * Akun.php — Controller Akun Pribadi User
 *
 * ROUTES:
 *   GET  /ganti-password         → ganti_password()  — form ganti password
 *   POST /ganti-password/simpan  → update_password() — proses ganti password
 *
 * KEAMANAN:
 *   - Password lama wajib diisi & diverifikasi, KECUALI saat
 *     must_change_password=1 (reset oleh admin / akun baru)
 *   - Password baru minimal 8 karakter & tidak boleh sama dengan username
 *   - Password di-hash dengan password_hash() bcrypt
 *   - Aksi dicatat di user_logs (tanpa menyimpan password)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Akun extends Auth_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }

    public function ganti_password() {
        $d = $this->data;
        $d['title'] = 'Ganti Password';
        $d['wajib'] = (bool)$this->session->userdata('must_change_password');
        $this->render('akun/ganti_password', $d);
    }

    public function update_password() {
        $wajib          = (bool)$this->session->userdata('must_change_password');
        $password_lama  = $this->input->post('password_lama');
        $password_baru  = (string)$this->input->post('password_baru');
        $password_baru2 = (string)$this->input->post('password_baru2');

        $user = $this->User_model->get_by_id($this->user_id);

        if (!$wajib) {
            if (empty($password_lama) || !password_verify($password_lama, $user->password)) {
                $this->session->set_flashdata('error', 'Password lama salah.');
                redirect('ganti-password'); return;
            }
        }

        if (strlen($password_baru) < 8) {
            $this->session->set_flashdata('error', 'Password baru minimal 8 karakter.');
            redirect('ganti-password'); return;
        }
        if ($password_baru !== $password_baru2) {
            $this->session->set_flashdata('error', 'Konfirmasi password baru tidak sama.');
            redirect('ganti-password'); return;
        }
        if (strcasecmp($password_baru, $user->username) === 0) {
            $this->session->set_flashdata('error', 'Password baru tidak boleh sama dengan username.');
            redirect('ganti-password'); return;
        }

        $this->User_model->update($this->user_id, [
            'password'             => password_hash($password_baru, PASSWORD_BCRYPT),
            'must_change_password' => 0,
        ]);
        $this->session->set_userdata('must_change_password', 0);
        $this->log_aktivitas('akun.ganti_password', 'Ganti password user id='.$this->user_id);

        $this->session->set_flashdata('success', 'Password berhasil diganti.');
        redirect('dashboard');
    }
}
