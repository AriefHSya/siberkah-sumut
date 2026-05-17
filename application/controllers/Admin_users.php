<?php
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
        $d = $this->data;
        $d['title']        = 'Manajemen User — SIBERKAH SUMUT';
        $d['list']         = $this->User_model->get_all($filters);
        $d['roles']        = $this->Role_model->get_all(TRUE);
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['stats']        = $this->User_model->count_per_role();
        $d['filters']      = $filters;
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
        $data = [
            'username'       => $username,
            'password'       => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'nama'           => $this->input->post('nama',TRUE),
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
        $data = [
            'username'       => $username,
            'nama'           => $this->input->post('nama',TRUE),
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
        $pw_baru = 'password123';
        $this->User_model->update($id, ['password' => password_hash($pw_baru, PASSWORD_BCRYPT)]);
        $this->log_aktivitas('admin.user.reset_pw','Reset password user id='.$id);
        $this->session->set_flashdata('success','Password berhasil direset ke: <strong>'.$pw_baru.'</strong>');
        redirect('admin/users');
    }
}
