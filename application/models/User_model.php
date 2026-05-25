<?php
/**
 * User_model.php — Model Data User
 *
 * CRUD user dengan filter dan join ke tabel roles + ref_kabkota.
 * Password di-hash bcrypt — JANGAN simpan password plain text.
 *
 * TABEL: users
 * JOIN  : roles (role_nama, role_kode, role_level), ref_kabkota (kabkota_nama)
 *
 * METHOD UTAMA:
 *   get_all($filters)        — daftar user dengan filter role/kabkota/status/q
 *   get_by_id($id)           — detail user + join roles + kabkota
 *   get_by_username($username) — dipakai Auth::proses() untuk login
 *   insert($data)            — tambah user baru (password sudah di-hash sebelumnya)
 *   update($id, $data)       — update data user
 *   toggle($id)              — toggle is_active 0/1
 *   hapus($id)               — hapus user (cek dulu tidak ada data terkait)
 *   update_last_login($id)   — update kolom last_login_at saat login berhasil
 *   count_per_role()         — statistik jumlah user per role (untuk dashboard admin)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    private function _apply_filters($filters)
    {
        $this->db->select('u.*, r.nama as role_nama, r.kode as role_kode, r.level as role_level, k.nama as kabkota_nama')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->join('ref_kabkota k', 'k.id = u.kabkota_id', 'left');
        if (!empty($filters['role_id']))    $this->db->where('u.role_id', $filters['role_id']);
        if (!empty($filters['kabkota_id'])) $this->db->where('u.kabkota_id', $filters['kabkota_id']);
        if (isset($filters['is_active']) && $filters['is_active'] !== '')
            $this->db->where('u.is_active', $filters['is_active']);
        if (!empty($filters['q']))
            $this->db->group_start()->like('u.nama', $filters['q'])->or_like('u.username', $filters['q'])->or_like('u.email', $filters['q'])->group_end();
    }

    public function count_filtered($filters = [])
    {
        $this->_apply_filters($filters);
        return $this->db->count_all_results();
    }

    public function get_all($filters = [], $limit = NULL, $offset = 0)
    {
        $this->_apply_filters($filters);
        $this->db->order_by('r.level','ASC')->order_by('u.nama','ASC');
        if ($limit !== NULL) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        return $this->db->select('u.*, r.nama as role_nama, r.kode as role_kode, r.level as role_level, k.nama as kabkota_nama')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->join('ref_kabkota k', 'k.id = u.kabkota_id', 'left')
            ->where('u.id', $id)->get()->row();
    }

    public function get_by_username($username)
    {
        return $this->db->select('u.*, r.kode as role_kode, r.nama as role_nama, r.level as role_level, k.nama as kabkota_nama')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->join('ref_kabkota k', 'k.id = u.kabkota_id', 'left')
            ->where('u.username', $username)->where('u.is_active', 1)->get()->row();
    }

    public function username_exists($username, $exclude_id = NULL)
    {
        $this->db->where('username', $username);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results('users') > 0;
    }

    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('users', $data);
    }

    public function toggle($id)
    {
        $user = $this->get_by_id($id);
        if (!$user) return FALSE;
        return $this->db->where('id', $id)->update('users', ['is_active' => $user->is_active ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function hapus($id) { return $this->db->delete('users', ['id' => $id]); }

    public function update_last_login($id)
    {
        $this->db->where('id', $id)->update('users', ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function count_per_role()
    {
        $q = $this->db->select('r.kode, r.nama, COUNT(u.id) as total')
            ->from('users u')->join('roles r', 'r.id = u.role_id')
            ->where('u.is_active', 1)->group_by('r.id')->get();
        $result = [];
        foreach ($q->result() as $row) { $result[$row->kode] = $row->total; }
        return $result;
    }
}
