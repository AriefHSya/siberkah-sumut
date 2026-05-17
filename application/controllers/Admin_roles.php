<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_roles extends Auth_Controller
{
    public function __construct() {
        parent::__construct();
        $this->requirePerm('admin.role.view');
        $this->load->model(['Role_model','User_model']);
        $this->data['active_menu'] = 'admin';
        $this->data['active_sub']  = 'roles';
    }

    public function index() {
        $d = $this->data;
        $d['title']      = 'Role & Hak Akses — SIBERKAH SUMUT';
        $roles           = $this->Role_model->get_all();
        $d['count_role'] = $this->User_model->count_per_role();
        // Hitung jumlah permission per role di controller, bukan di view
        $perm_count = [];
        foreach ($roles as $r) {
            $perm_count[$r->id] = count($this->Role_model->get_permissions_by_role($r->id));
        }
        $d['roles']      = $roles;
        $d['perm_count'] = $perm_count;
        $this->render('admin/roles/index', $d);
    }

    public function tambah() {
        $this->requirePerm('admin.role.create');
        $d = $this->data;
        $d['title'] = 'Tambah Role';
        $d['edit']  = FALSE;
        $this->render('admin/roles/form', $d);
    }

    public function simpan() {
        $this->requirePerm('admin.role.create');
        $kode = strtolower(preg_replace('/[^a-z0-9_]/', '_', strtolower($this->input->post('kode',TRUE))));
        if ($this->db->get_where('roles',['kode'=>$kode])->num_rows()) {
            $this->session->set_flashdata('error','Kode role sudah ada.'); redirect('admin/roles/tambah'); return;
        }
        $level = max(3, min(99, (int)$this->input->post('level')));
        $this->Role_model->insert(['kode'=>$kode,'nama'=>$this->input->post('nama',TRUE),'deskripsi'=>$this->input->post('deskripsi',TRUE),'level'=>$level,'is_system'=>0,'is_active'=>1]);
        $this->log_aktivitas('admin.role.tambah','Tambah role '.$kode);
        $this->session->set_flashdata('success','Role berhasil ditambahkan. Atur hak aksesnya sekarang.');
        $role = $this->db->get_where('roles',['kode'=>$kode])->row();
        redirect('admin/roles/permissions/'.$role->id);
    }

    public function edit($id) {
        $this->requirePerm('admin.role.edit');
        $role = $this->Role_model->get_by_id($id);
        if (!$role) { show_404(); return; }
        $d = $this->data;
        $d['title'] = 'Edit Role';
        $d['role']  = $role;
        $d['edit']  = TRUE;
        $this->render('admin/roles/form', $d);
    }

    public function update($id) {
        $this->requirePerm('admin.role.edit');
        $role = $this->Role_model->get_by_id($id);
        if (!$role) { show_404(); return; }
        $data = ['nama'=>$this->input->post('nama',TRUE),'deskripsi'=>$this->input->post('deskripsi',TRUE)];
        if (!$role->is_system) $data['level'] = max(3, min(99, (int)$this->input->post('level')));
        $this->Role_model->update($id, $data);
        $this->log_aktivitas('admin.role.edit','Edit role id='.$id);
        $this->session->set_flashdata('success','Role berhasil diperbarui.');
        redirect('admin/roles');
    }

    public function hapus($id) {
        $this->requirePerm('admin.role.delete');
        $role = $this->Role_model->get_by_id($id);
        if (!$role || $role->is_system) {
            $this->session->set_flashdata('error','Role sistem tidak dapat dihapus.'); redirect('admin/roles'); return;
        }
        $jml_user = $this->db->where('role_id',$id)->count_all_results('users');
        if ($jml_user > 0) {
            $this->session->set_flashdata('error','Role ini masih digunakan oleh '.$jml_user.' user.'); redirect('admin/roles'); return;
        }
        $this->Role_model->hapus($id);
        $this->log_aktivitas('admin.role.hapus','Hapus role '.$role->kode);
        $this->session->set_flashdata('success','Role berhasil dihapus.');
        redirect('admin/roles');
    }

    public function permissions($id) {
        $this->requirePerm('admin.role.permission');
        $role = $this->Role_model->get_by_id($id);
        if (!$role) { show_404(); return; }
        $all_perms   = $this->Role_model->get_all_permissions();
        $role_perms  = $this->Role_model->get_permissions_by_role($id);
        $modul_meta  = $this->Role_model->get_modul_meta();
        // Group permission per modul
        $grouped = [];
        foreach ($all_perms as $p) {
            $grouped[$p->modul][] = $p;
        }
        $d = $this->data;
        $d['title']      = 'Hak Akses — ' . $role->nama;
        $d['role']       = $role;
        $d['grouped']    = $grouped;
        $d['role_perms'] = $role_perms;
        $d['modul_meta'] = $modul_meta;
        $this->render('admin/roles/permissions', $d);
    }

    public function save_permissions($id) {
        $this->requirePerm('admin.role.permission');
        $role = $this->Role_model->get_by_id($id);
        if (!$role) { show_404(); return; }
        $perm_kodes = $this->input->post('perms') ?? [];
        $old_perms  = $this->Role_model->get_permissions_by_role($id);
        // Log grant/revoke
        $granted = array_diff($perm_kodes, $old_perms);
        $revoked = array_diff($old_perms, $perm_kodes);
        foreach ($granted as $k) $this->Role_model->log_permission($id,$role->nama,'grant',$k,$this->user_id);
        foreach ($revoked as $k) $this->Role_model->log_permission($id,$role->nama,'revoke',$k,$this->user_id);
        $this->Role_model->save_permissions($id, $perm_kodes, $this->user_id);
        $this->log_aktivitas('admin.role.permission','Update permissions role '.$role->kode.' ('.count($perm_kodes).' permission)');
        $this->session->set_flashdata('success','Hak akses role <strong>'.$role->nama.'</strong> berhasil disimpan ('.count($perm_kodes).' permission aktif).');
        redirect('admin/roles');
    }

    public function logs() {
        $d = $this->data;
        $d['title']  = 'Log Perubahan Hak Akses';
        $d['logs']   = $this->db->select('l.*, u.nama as nama_user')->from('permission_logs l')->join('users u','u.id = l.user_id','left')->order_by('l.created_at','DESC')->limit(200)->get()->result();
        $this->render('admin/roles/logs', $d);
    }
}
