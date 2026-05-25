<?php
/**
 * Admin_users.php — Controller Manajemen User
 *
 * CRUD user dengan role-level guard: user tidak bisa manage user
 * yang role-level-nya sama atau lebih tinggi dari dirinya.
 *
 * ROUTES:
 *   GET  /admin/users                → index()      — daftar user + filter
 *   GET  /admin/users/tambah         → tambah()     — form tambah user baru
 *   POST /admin/users/simpan         → simpan()     — proses simpan user baru
 *   GET  /admin/users/edit/{id}      → edit()       — form edit user
 *   POST /admin/users/update/{id}    → update()     — proses update
 *   POST /admin/users/toggle/{id}    → toggle()     — aktifkan/nonaktifkan user
 *   POST /admin/users/hapus/{id}     → hapus()      — hapus user (soft-delete atau hard)
 *   POST /admin/users/reset/{id}     → reset_pw()   — reset password ke default
 *
 * KEAMANAN:
 *   - Guard role-level: admin hanya bisa manage user yang role-level > role-level-nya
 *   - Password di-hash dengan password_hash() bcrypt
 *   - Aksi dicatat di user_logs
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_users extends Auth_Controller
{
    public function __construct() {
        parent::__construct();
        $this->requirePerm('admin.user.view');
        $this->load->model(['User_model','Role_model','Parameter_model']);
        $this->data['active_menu'] = 'admin';
        $this->data['active_sub']  = 'users';
    }

    public function index() {
        $per_page = 25;
        $page     = max(1, (int)$this->input->get('page'));
        $filters = [
            'role_id'    => $this->input->get('role_id'),
            'kabkota_id' => $this->input->get('kabkota_id'),
            'is_active'  => $this->input->get('is_active'),
            'q'          => $this->input->get('q'),
        ];
        // Batasi: SKPKD Kab hanya lihat user kab/kota miliknya
        if ($this->role_kode === 'skpkd_kabkota') {
            $filters['kabkota_id'] = $this->kabkota_id;
        }
        $total  = $this->User_model->count_filtered($filters);
        $offset = ($page - 1) * $per_page;
        $d = $this->data;
        $d['title']        = 'Manajemen User — SIBERKAH SUMUT';
        $d['list']         = $this->User_model->get_all($filters, $per_page, $offset);
        $d['roles']        = $this->Role_model->get_all(TRUE);
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['stats']        = $this->User_model->count_per_role();
        $d['filters']      = $filters;
        $d['paging']       = ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'admin/users'];
        $this->render('admin/users/index', $d);
    }

    public function tambah() {
        $this->requirePerm('admin.user.create');
        $d = $this->data;
        $d['title']        = 'Tambah User';
        $d['roles']        = $this->Role_model->get_all(TRUE);
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['edit']         = FALSE;
        $this->render('admin/users/form', $d);
    }

    public function simpan() {
        $this->requirePerm('admin.user.create');
        $username = $this->input->post('username', TRUE);
        if ($this->User_model->username_exists($username)) {
            $this->session->set_flashdata('error','Username sudah digunakan.'); redirect('admin/users/tambah'); return;
        }
        $role = $this->Role_model->get_by_id($this->input->post('role_id',TRUE));
        if (!$role || !$this->rbac->canManageUser($role->level)) {
            $this->session->set_flashdata('error','Anda tidak berwenang membuat user dengan role ini.'); redirect('admin/users/tambah'); return;
        }
        // Role Pengawas hanya boleh dibuat oleh superadmin & admin provinsi
        if ($role->kode === 'pengawas' && !$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Role Pengawas hanya dapat dibuat oleh Admin Provinsi atau Superadmin.');
            redirect('admin/users/tambah'); return;
        }
        $nip_raw = preg_replace('/[^0-9]/', '', $this->input->post('nip', TRUE));
        if (strlen($nip_raw) !== 18) {
            $this->session->set_flashdata('error', 'NIP harus tepat 18 digit angka.');
            redirect('admin/users/tambah'); return;
        }
        if ($this->db->where('nip', $nip_raw)->count_all_results('users') > 0) {
            $this->session->set_flashdata('error', 'NIP ' . $nip_raw . ' sudah terdaftar untuk user lain.');
            redirect('admin/users/tambah'); return;
        }
        $data = [
            'username'       => $username,
            'password'       => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'nama'           => $this->input->post('nama',TRUE),
            'nip'            => $nip_raw,
            'email'          => $this->input->post('email',TRUE),
            'telepon'        => $this->input->post('telepon',TRUE),
            'role_id'        => $this->input->post('role_id',TRUE),
            'kabkota_id'     => $this->input->post('kabkota_id',TRUE) ?: NULL,
            'instansi_jenis' => $this->input->post('instansi_jenis',TRUE),
            'opd_nama'          => $this->input->post('opd_nama',TRUE),
            'jabatan'           => $this->input->post('jabatan',TRUE),
            'telegram_chat_id'  => $this->input->post('telegram_chat_id',TRUE) ?: NULL,
            'is_active'         => 1,
            'created_by'        => $this->user_id,
        ];
        $this->User_model->insert($data);
        $this->log_aktivitas('admin.user.tambah','Tambah user '.$username);
        $this->session->set_flashdata('success','User '.$username.' berhasil ditambahkan.');
        redirect('admin/users');
    }

    public function edit($id) {
        $this->requirePerm('admin.user.edit');
        $user = $this->User_model->get_by_id($id);
        if (!$user) { show_404(); return; }
        $d = $this->data;
        $d['title']        = 'Edit User';
        $d['user']         = $user;
        $d['roles']        = $this->Role_model->get_all(TRUE);
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['edit']         = TRUE;
        $this->render('admin/users/form', $d);
    }

    public function update($id) {
        $this->requirePerm('admin.user.edit');
        $user = $this->User_model->get_by_id($id);
        if (!$user) { show_404(); return; }
        $username = $this->input->post('username', TRUE);
        if ($this->User_model->username_exists($username, $id)) {
            $this->session->set_flashdata('error','Username sudah digunakan.'); redirect('admin/users/edit/'.$id); return;
        }
        // Role Pengawas hanya boleh di-assign oleh superadmin & admin provinsi
        $role_edit = $this->Role_model->get_by_id($this->input->post('role_id', TRUE));
        if ($role_edit && $role_edit->kode === 'pengawas' && !$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Role Pengawas hanya dapat di-assign oleh Admin Provinsi atau Superadmin.');
            redirect('admin/users/edit/'.$id); return;
        }
        $nip_raw = preg_replace('/[^0-9]/', '', $this->input->post('nip', TRUE));
        if (strlen($nip_raw) !== 18) {
            $this->session->set_flashdata('error', 'NIP harus tepat 18 digit angka.');
            redirect('admin/users/edit/'.$id); return;
        }
        if ($this->db->where('nip', $nip_raw)->where('id !=', $id)->count_all_results('users') > 0) {
            $this->session->set_flashdata('error', 'NIP ' . $nip_raw . ' sudah terdaftar untuk user lain.');
            redirect('admin/users/edit/'.$id); return;
        }
        $data = [
            'username'       => $username,
            'nama'           => $this->input->post('nama',TRUE),
            'nip'            => $nip_raw,
            'email'          => $this->input->post('email',TRUE),
            'telepon'        => $this->input->post('telepon',TRUE),
            'role_id'        => $this->input->post('role_id',TRUE),
            'kabkota_id'     => $this->input->post('kabkota_id',TRUE) ?: NULL,
            'instansi_jenis' => $this->input->post('instansi_jenis',TRUE),
            'opd_nama'         => $this->input->post('opd_nama',TRUE),
            'jabatan'          => $this->input->post('jabatan',TRUE),
            'telegram_chat_id' => $this->input->post('telegram_chat_id',TRUE) ?: NULL,
        ];
        $pw = $this->input->post('password');
        if (!empty($pw)) $data['password'] = password_hash($pw, PASSWORD_BCRYPT);
        $this->User_model->update($id, $data);
        $this->log_aktivitas('admin.user.edit','Edit user id='.$id.' username='.$username);
        $this->session->set_flashdata('success','Data user berhasil diperbarui.');
        redirect('admin/users');
    }

    public function toggle($id) {
        $this->requirePerm('admin.user.toggle');
        if ($id == $this->user_id) {
            $this->session->set_flashdata('error','Tidak bisa menonaktifkan akun sendiri.'); redirect('admin/users'); return;
        }
        $this->User_model->toggle($id);
        $this->session->set_flashdata('success','Status user berhasil diubah.');
        redirect('admin/users');
    }

    public function hapus($id) {
        $this->requirePerm('admin.user.delete');
        if ($id == $this->user_id) {
            $this->session->set_flashdata('error','Tidak bisa menghapus akun sendiri.'); redirect('admin/users'); return;
        }
        $user = $this->User_model->get_by_id($id);
        $this->User_model->hapus($id);
        $this->log_aktivitas('admin.user.hapus','Hapus user '.$user->username);
        $this->session->set_flashdata('success','User berhasil dihapus.');
        redirect('admin/users');
    }

    public function reset_pw($id) {
        $this->requirePerm('admin.user.reset_pw');
        $user = $this->User_model->get_by_id($id);
        if (!$user) { show_404(); return; }

        // Generate password acak 10 karakter
        $pw_baru = substr(str_shuffle('abcdefghijkmnpqrstuvwxyz23456789'), 0, 5)
                 . substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 2)
                 . substr(str_shuffle('23456789'), 0, 3);

        $this->User_model->update($id, ['password' => password_hash($pw_baru, PASSWORD_BCRYPT)]);
        $this->log_aktivitas('admin.user.reset_pw', 'Reset password user id='.$id.' username='.$user->username);

        // Kirim email jika user punya email & SMTP dikonfigurasi
        $email_terkirim = FALSE;
        if (!empty($user->email)) {
            $smtp = $this->db->where('kode', 'smtp_host')->get('ref_app_setting')->row();
            if ($smtp && !empty($smtp->nilai)) {
                $this->_kirim_email_reset($user, $pw_baru);
                $email_terkirim = TRUE;
            }
        }

        if ($email_terkirim) {
            $this->session->set_flashdata('success',
                'Password user <strong>'.$user->username.'</strong> berhasil direset. ' .
                'Password sementara telah dikirim ke email <strong>'.$user->email.'</strong>.');
        } else {
            $this->session->set_flashdata('success',
                'Password berhasil direset ke: <strong>'.$pw_baru.'</strong> — ' .
                'sampaikan ke user dan minta segera ganti setelah login.' .
                (!empty($user->email) ? ' (Email tidak terkirim — cek konfigurasi SMTP di Pengaturan)' : ' (User tidak punya email terdaftar)'));
        }
        redirect('admin/users');
    }

    private function _kirim_email_reset($user, $pw_baru)
    {
        $smtp_host = $this->db->get_where('ref_app_setting', ['kode'=>'smtp_host'])->row();
        $smtp_user = $this->db->get_where('ref_app_setting', ['kode'=>'smtp_user'])->row();
        $smtp_pass = $this->db->get_where('ref_app_setting', ['kode'=>'smtp_pass'])->row();
        $smtp_port = $this->db->get_where('ref_app_setting', ['kode'=>'smtp_port'])->row();
        $from_name = $this->db->get_where('ref_app_setting', ['kode'=>'smtp_from_name'])->row();

        if (!$smtp_host || empty($smtp_host->nilai)) return;

        $this->load->library('email');
        $this->email->initialize([
            'protocol'   => 'smtp',
            'smtp_host'  => $smtp_host->nilai,
            'smtp_user'  => $smtp_user ? $smtp_user->nilai : '',
            'smtp_pass'  => $smtp_pass ? $smtp_pass->nilai : '',
            'smtp_port'  => $smtp_port ? (int)$smtp_port->nilai : 587,
            'smtp_crypto'=> 'tls',
            'mailtype'   => 'html',
            'charset'    => 'UTF-8',
            'newline'    => "\r\n",
        ]);

        $from_addr = $smtp_user ? $smtp_user->nilai : 'noreply@siberkah.id';
        $from_nm   = $from_name ? $from_name->nilai : 'SIBERKAH SUMUT';

        $this->email->from($from_addr, $from_nm);
        $this->email->to($user->email);
        $this->email->subject('Reset Password SIBERKAH SUMUT');
        $this->email->message(
            '<div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto">' .
            '<h2 style="color:#1A5EA8">Reset Password SIBERKAH SUMUT</h2>' .
            '<p>Halo <strong>' . htmlspecialchars($user->nama) . '</strong>,</p>' .
            '<p>Password akun Anda telah direset oleh Administrator sistem SIBERKAH SUMUT.</p>' .
            '<p>Password sementara Anda:</p>' .
            '<div style="background:#f4f4f4;padding:16px;border-radius:8px;text-align:center;font-size:24px;font-weight:bold;letter-spacing:4px;color:#1A5EA8">' .
            htmlspecialchars($pw_baru) .
            '</div>' .
            '<p style="margin-top:16px"><strong>Segera ganti password</strong> setelah login pertama.</p>' .
            '<p style="color:#888;font-size:12px">Pesan ini dikirim otomatis oleh sistem SIBERKAH SUMUT. Jangan membalas email ini.</p>' .
            '</div>'
        );
        @$this->email->send();
    }
}
