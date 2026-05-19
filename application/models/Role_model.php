<?php
/**
 * Role_model.php — Model RBAC: Role & Permission
 *
 * Akses data role dan permission untuk sistem RBAC dinamis.
 * Permission di-load dari DB setiap request via library Rbac.php.
 *
 * TABEL UTAMA:
 *   roles             — daftar role (id, kode, nama, level, is_active)
 *   permissions       — daftar permission kode (id, kode, nama, modul, jenis)
 *   role_permissions  — relasi M:N role ↔ permission
 *
 * LEVEL ROLE (semakin kecil = semakin tinggi):
 *   1 = superadmin, 2 = admin_provinsi, 3 = skpkd_kabkota,
 *   4 = inspektorat, 5 = opd_teknis, 8 = pengawas
 *
 * METHOD UTAMA:
 *   get_all($only_active)        — daftar role urut level ASC
 *   get_by_id($id)               — detail role
 *   get_all_permissions()        — semua permission dikelompokkan per modul
 *   get_permissions_by_role($id) — permission yang dimiliki role ini
 *   insert($data)                — tambah role baru
 *   update($id, $data)           — update role
 *   hapus($id)                   — hapus role (jika tidak ada user yang memakai)
 *   save_permissions($role_id)   — ganti semua permission role sekaligus (replace)
 *   get_modul_meta()             — metadata modul untuk UI assignment permission
 *   log_permission($role_id, ...) — catat perubahan permission ke role_permission_logs
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model
{
    public function get_all($only_active = FALSE)
    {
        if ($only_active) $this->db->where('is_active', 1);
        return $this->db->order_by('level', 'ASC')->get('roles')->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('roles', ['id' => $id])->row();
    }

    public function get_all_permissions()
    {
        return $this->db->order_by('modul','ASC')->order_by('kode','ASC')->get('permissions')->result();
    }

    public function get_permissions_by_role($role_id)
    {
        $q = $this->db->select('p.kode')->from('role_permissions rp')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $role_id)->get();
        return array_column($q->result_array(), 'kode');
    }

    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('roles', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('roles', $data);
    }

    public function hapus($id)
    {
        // Tidak bisa hapus role sistem
        $role = $this->get_by_id($id);
        if ($role && $role->is_system) return FALSE;
        $this->db->delete('role_permissions', ['role_id' => $id]);
        return $this->db->delete('roles', ['id' => $id]);
    }

    public function save_permissions($role_id, array $perm_kodes, $granted_by)
    {
        $this->db->delete('role_permissions', ['role_id' => $role_id]);
        if (empty($perm_kodes)) return;
        $rows = [];
        foreach ($perm_kodes as $kode) {
            $p = $this->db->get_where('permissions', ['kode' => $kode])->row();
            if ($p) $rows[] = ['role_id' => $role_id, 'permission_id' => $p->id, 'granted_by' => $granted_by, 'created_at' => date('Y-m-d H:i:s')];
        }
        if ($rows) $this->db->insert_batch('role_permissions', $rows);
    }

    public function get_modul_meta()
    {
        $CI =& get_instance();
        return $CI->rbac->getModulMeta();
    }

    public function log_permission($role_id, $role_nama, $aksi, $kode, $user_id)
    {
        $this->db->insert('permission_logs', [
            'role_id' => $role_id, 'role_nama' => $role_nama,
            'aksi' => $aksi, 'permission_kode' => $kode,
            'user_id' => $user_id, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
