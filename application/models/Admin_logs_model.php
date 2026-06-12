<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_logs_model.php — Model Log Aktivitas (read-only)
 *
 * Membaca dua sumber audit trail yang sudah ada:
 *   - user_logs            — log aktivitas user (log_aktivitas() di MY_Controller)
 *   - trx_status_history   — riwayat perubahan status pekerjaan/tahapan
 *
 * METHOD UTAMA:
 *   get_aktivitas($filters, $limit, $offset)   — daftar user_logs + join users/roles
 *   count_aktivitas($filters)                  — hitung untuk pagination
 *   get_distinct_aksi()                        — daftar aksi unik (dropdown filter)
 *   get_users_for_filter()                     — daftar user untuk dropdown filter
 *
 *   get_status_history($filters, $limit, $offset) — riwayat status + join pekerjaan/kabkota
 *   count_status_history($filters)                — hitung untuk pagination
 *   get_distinct_status()                          — daftar status_baru unik (dropdown filter)
 */
class Admin_logs_model extends CI_Model
{
    // ─── LOG AKTIVITAS (user_logs) ─────────────────────────────

    private function _filter_aktivitas($filters)
    {
        $this->db->from('user_logs l')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left');

        if (!empty($filters['tanggal_dari']))
            $this->db->where('DATE(l.created_at) >=', $filters['tanggal_dari']);
        if (!empty($filters['tanggal_sampai']))
            $this->db->where('DATE(l.created_at) <=', $filters['tanggal_sampai']);
        if (!empty($filters['user_id']))
            $this->db->where('l.user_id', $filters['user_id']);
        if (!empty($filters['aksi']))
            $this->db->where('l.aksi', $filters['aksi']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('l.aksi', $filters['q'])
                ->or_like('l.keterangan', $filters['q'])
                ->or_like('u.nama', $filters['q'])
                ->or_like('u.username', $filters['q'])
                ->group_end();
    }

    public function get_aktivitas($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('l.*, u.nama as nama_user, u.username, r.kode as role_kode, r.nama as role_nama');
        $this->_filter_aktivitas($filters);
        $this->db->order_by('l.created_at', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_aktivitas($filters = [])
    {
        $this->_filter_aktivitas($filters);
        return $this->db->count_all_results();
    }

    public function get_distinct_aksi()
    {
        return $this->db->select('aksi')->distinct()
            ->order_by('aksi', 'ASC')
            ->get('user_logs')->result();
    }

    public function get_users_for_filter()
    {
        return $this->db->select('u.id, u.nama, u.username')
            ->from('users u')
            ->order_by('u.nama', 'ASC')
            ->get()->result();
    }

    // ─── RIWAYAT STATUS (trx_status_history) ───────────────────

    private function _filter_status_history($filters)
    {
        $this->db->from('trx_status_history h')
            ->join('trx_pekerjaan p', 'p.id = h.pekerjaan_id')
            ->join('ref_bkp b',       'b.id = p.bkp_id')
            ->join('ref_kabkota k',   'k.id = b.kabkota_id')
            ->join('users u',         'u.id = h.user_id', 'left');

        if (!empty($filters['tanggal_dari']))
            $this->db->where('DATE(h.created_at) >=', $filters['tanggal_dari']);
        if (!empty($filters['tanggal_sampai']))
            $this->db->where('DATE(h.created_at) <=', $filters['tanggal_sampai']);
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['status_baru']))
            $this->db->where('h.status_baru', $filters['status_baru']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('b.uraian_bkp', $filters['q'])
                ->or_like('h.catatan', $filters['q'])
                ->or_like('u.nama', $filters['q'])
                ->group_end();
    }

    public function get_status_history($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('h.*, b.kode_bkp, b.uraian_bkp, k.nama as nama_kabkota,
                            u.nama as nama_user, u.username');
        $this->_filter_status_history($filters);
        $this->db->order_by('h.created_at', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_status_history($filters = [])
    {
        $this->_filter_status_history($filters);
        return $this->db->count_all_results();
    }

    public function get_distinct_status()
    {
        return $this->db->select('status_baru')->distinct()
            ->order_by('status_baru', 'ASC')
            ->get('trx_status_history')->result();
    }
}
