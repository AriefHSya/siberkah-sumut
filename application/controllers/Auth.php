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
        $this->data['title']              = 'Login — SIBERKAH SUMUT';
        $this->data['logo_prov']          = $this->_get_logo_prov();
        $this->data['recaptcha_site_key'] = $this->config->item('recaptcha_site_key');
        $this->load->view('auth/login', $this->data);
    }

    public function proses() {
        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('password');

        if (empty($username) || empty($password)) {
            $this->session->set_flashdata('error', 'Username dan password wajib diisi.');
            redirect('login'); return;
        }

        // Verifikasi reCAPTCHA v2 — hanya jika secret key dikonfigurasi
        $secret = $this->config->item('recaptcha_secret_key');
        if (!empty($secret)) {
            $captcha_response = $this->input->post('g-recaptcha-response');
            if (empty($captcha_response)) {
                $this->session->set_flashdata('error', 'Harap selesaikan verifikasi CAPTCHA terlebih dahulu.');
                redirect('login'); return;
            }
            $verify = @file_get_contents(
                'https://www.google.com/recaptcha/api/siteverify?secret='
                . urlencode($secret) . '&response=' . urlencode($captcha_response)
                . '&remoteip=' . urlencode($this->input->ip_address())
            );
            $result = $verify ? json_decode($verify, TRUE) : ['success' => FALSE];
            if (empty($result['success'])) {
                $this->session->set_flashdata('error', 'Verifikasi CAPTCHA gagal. Silakan coba lagi.');
                redirect('login'); return;
            }
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

    // ─── LUPA PASSWORD ────────────────────────────────────────────

    public function lupa_password() {
        $this->data['title'] = 'Lupa Password — SIBERKAH SUMUT';
        $this->data['logo_prov'] = $this->_get_logo_prov();
        $this->load->view('auth/lupa_password', $this->data);
    }

    public function kirim_reset() {
        if ($this->input->method() !== 'post') { redirect('lupa-password'); return; }

        $input = trim($this->input->post('username_or_email', TRUE));
        if (empty($input)) {
            $this->session->set_flashdata('error', 'Username atau email wajib diisi.');
            redirect('lupa-password'); return;
        }

        // Cek SMTP tersedia: ref_app_setting (utama) atau config.php/env vars (fallback)
        $db_smtp = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_host'])->row();
        $smtp_ok = ($db_smtp && !empty($db_smtp->nilai)) || !empty($this->config->item('smtp_host'));
        if (!$smtp_ok) {
            $this->session->set_flashdata('error',
                'Fitur reset password via email belum dikonfigurasi. Hubungi administrator untuk mereset password Anda.');
            redirect('lupa-password'); return;
        }

        // Cari user berdasarkan username atau email (aktif saja)
        $user = $this->db->where('username', $input)->where('is_active', 1)->get('users')->row();
        if (!$user) {
            $user = $this->db->where('email', $input)->where('is_active', 1)->get('users')->row();
        }

        // Selalu tampilkan pesan generik — jangan bocorkan apakah user ditemukan (security)
        if (!$user || empty($user->email)) {
            $this->session->set_flashdata('success',
                'Jika username atau email terdaftar dan aktif, link reset password akan dikirimkan dalam beberapa menit.');
            redirect('lupa-password'); return;
        }

        // Invalidasi token lama yang belum terpakai
        $this->db->where('user_id', $user->id)->where('used_at IS NULL')
                 ->update('password_reset_tokens', ['used_at' => date('Y-m-d H:i:s')]);

        // Buat token baru, berlaku 1 jam
        $token      = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600);
        $this->db->insert('password_reset_tokens', [
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expires_at,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $sent = $this->_send_reset_email($user, $token);
        if ($sent) {
            $this->db->insert('user_logs', [
                'user_id'    => $user->id,
                'aksi'       => 'reset_password_request',
                'keterangan' => 'Permintaan reset password via email ke: ' . $user->email,
                'ip_address' => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->session->set_flashdata('success',
            'Jika username atau email terdaftar dan aktif, link reset password akan dikirimkan dalam beberapa menit.');
        redirect('lupa-password');
    }

    public function reset_password($token = '') {
        if (empty($token)) { redirect('lupa-password'); return; }

        $rec = $this->db->where('token', $token)->where('used_at IS NULL')
                        ->where('expires_at >=', date('Y-m-d H:i:s'))
                        ->get('password_reset_tokens')->row();
        if (!$rec) {
            $this->session->set_flashdata('error',
                'Link reset password tidak valid atau sudah kadaluarsa. Silakan minta link baru.');
            redirect('lupa-password'); return;
        }

        $user = $this->db->where('id', $rec->user_id)->where('is_active', 1)->get('users')->row();
        if (!$user) {
            $this->session->set_flashdata('error', 'Akun tidak ditemukan atau tidak aktif.');
            redirect('login'); return;
        }

        $this->data['title']    = 'Reset Password — SIBERKAH SUMUT';
        $this->data['logo_prov']= $this->_get_logo_prov();
        $this->data['token']    = $token;
        $this->data['user']     = $user;
        $this->load->view('auth/reset_password', $this->data);
    }

    public function proses_reset() {
        if ($this->input->method() !== 'post') { redirect('login'); return; }

        $token    = $this->input->post('token', TRUE);
        $password = $this->input->post('password');
        $confirm  = $this->input->post('password_confirm');

        if (empty($token) || empty($password) || empty($confirm)) {
            $this->session->set_flashdata('error', 'Semua field wajib diisi.');
            redirect('reset-password/' . urlencode($token)); return;
        }
        if ($password !== $confirm) {
            $this->session->set_flashdata('error', 'Konfirmasi password tidak cocok.');
            redirect('reset-password/' . urlencode($token)); return;
        }
        if (strlen($password) < 8) {
            $this->session->set_flashdata('error', 'Password minimal 8 karakter.');
            redirect('reset-password/' . urlencode($token)); return;
        }

        // Validasi token ulang (anti replay)
        $rec = $this->db->where('token', $token)->where('used_at IS NULL')
                        ->where('expires_at >=', date('Y-m-d H:i:s'))
                        ->get('password_reset_tokens')->row();
        if (!$rec) {
            $this->session->set_flashdata('error',
                'Link reset password tidak valid atau sudah kadaluarsa. Silakan minta link baru.');
            redirect('lupa-password'); return;
        }

        // Update password + buka kunci akun jika terkunci
        $this->db->where('id', $rec->user_id)->update('users', [
            'password'              => password_hash($password, PASSWORD_BCRYPT),
            'must_change_password'  => 0,
            'failed_login_attempts' => 0,
            'locked_at'             => NULL,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);
        // Tandai token sebagai sudah terpakai
        $this->db->where('id', $rec->id)->update('password_reset_tokens',
            ['used_at' => date('Y-m-d H:i:s')]);

        $this->db->insert('user_logs', [
            'user_id'    => $rec->user_id,
            'aksi'       => 'reset_password',
            'keterangan' => 'Password berhasil direset via email',
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'Password berhasil diperbarui. Silakan login dengan password baru Anda.');
        redirect('login');
    }

    // ─── PRIVATE HELPERS ──────────────────────────────────────────

    private function _get_logo_prov() {
        $s = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        return ($s && !empty($s->nilai)) ? base_url($s->nilai) : NULL;
    }

    private function _send_reset_email($user, $token) {
        if (empty($user->email)) return FALSE;

        // Baca SMTP dari ref_app_setting (dikelola via Parameter → Pengaturan Email)
        // Fallback ke config.php / env vars jika ref_app_setting kosong
        $db_host  = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_host'])->row();
        $smtp_host = ($db_host && !empty($db_host->nilai))
                   ? $db_host->nilai
                   : $this->config->item('smtp_host');
        if (empty($smtp_host)) return FALSE;

        $db_user  = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_user'])->row();
        $db_pass  = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_pass'])->row();
        $db_port  = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_port'])->row();
        $db_crypto= $this->db->get_where('ref_app_setting', ['kode' => 'smtp_crypto'])->row();
        $db_from  = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_from_email'])->row();
        $db_fname = $this->db->get_where('ref_app_setting', ['kode' => 'smtp_from_name'])->row();

        $smtp_user   = ($db_user  && $db_user->nilai)   ? $db_user->nilai   : $this->config->item('smtp_user');
        $smtp_pass   = ($db_pass  && $db_pass->nilai)   ? $db_pass->nilai   : $this->config->item('smtp_pass');
        $smtp_port   = ($db_port  && $db_port->nilai)   ? (int)$db_port->nilai : ($this->config->item('smtp_port') ?: 587);
        $smtp_crypto = ($db_crypto && $db_crypto->nilai !== NULL && $db_crypto->nilai !== '')
                     ? $db_crypto->nilai : ($this->config->item('smtp_crypto') ?: 'tls');
        $from_email  = ($db_from  && $db_from->nilai)   ? $db_from->nilai   : $this->config->item('smtp_from_email');
        $from_name   = ($db_fname && $db_fname->nilai)  ? $db_fname->nilai  : ($this->config->item('smtp_from_name') ?: 'SIBERKAH SUMUT');

        if (empty($from_email)) $from_email = $smtp_user;
        if (empty($from_email)) return FALSE;

        $app_name  = $this->config->item('app_name');
        $reset_url = site_url('reset-password/' . $token);
        $nama      = htmlspecialchars($user->nama);

        $body = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px">'
            . '<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08)">'
            . '<div style="background:#1A5EA8;padding:24px 32px;text-align:center">'
            . '<h1 style="color:#fff;margin:0;font-size:20px;letter-spacing:1px">' . htmlspecialchars($app_name) . '</h1>'
            . '<p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px">Sistem Informasi Bantuan Keuangan Daerah</p>'
            . '</div>'
            . '<div style="padding:32px">'
            . '<p style="margin:0 0 16px">Yth. <strong>' . $nama . '</strong>,</p>'
            . '<p style="margin:0 0 16px;color:#444;line-height:1.6">Kami menerima permintaan reset password untuk akun Anda di ' . htmlspecialchars($app_name) . '. Klik tombol di bawah untuk membuat password baru.</p>'
            . '<div style="text-align:center;margin:28px 0">'
            . '<a href="' . $reset_url . '" style="display:inline-block;background:#1A5EA8;color:#fff;text-decoration:none;padding:13px 32px;border-radius:8px;font-weight:bold;font-size:15px">Reset Password Saya</a>'
            . '</div>'
            . '<p style="margin:0 0 8px;color:#666;font-size:13px">Atau salin link berikut ke browser Anda:</p>'
            . '<p style="margin:0 0 20px;font-size:12px;word-break:break-all;background:#f4f6f9;padding:10px;border-radius:6px;color:#1A5EA8">' . $reset_url . '</p>'
            . '<p style="margin:0;color:#999;font-size:12px;border-top:1px solid #eee;padding-top:16px">Link ini berlaku selama <strong>1 jam</strong>. Jika Anda tidak merasa meminta reset password, abaikan email ini — password Anda tidak akan berubah.</p>'
            . '</div>'
            . '</div></body></html>';

        $this->load->library('email');
        $this->email->initialize([
            'protocol'   => 'smtp',
            'smtp_host'  => $smtp_host,
            'smtp_user'  => $smtp_user,
            'smtp_pass'  => $smtp_pass,
            'smtp_port'  => $smtp_port,
            'smtp_crypto'=> $smtp_crypto,
            'mailtype'   => 'html',
            'charset'    => 'utf-8',
            'newline'    => "\r\n",
        ]);
        $this->email->from($from_email, $from_name);
        $this->email->to($user->email);
        $this->email->subject('[' . $app_name . '] Permintaan Reset Password');
        $this->email->message($body);
        return @$this->email->send(FALSE);
    }
}
