<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tim_reviu_model extends CI_Model
{
    public function get_all($kabkota_id, $tahun)
    {
        return $this->db
            ->select('t.*, k.nama as nama_kabkota,
                      (SELECT COUNT(*) FROM trx_tim_reviu_anggota WHERE tim_id = t.id) as jml_anggota,
                      (SELECT COUNT(*) FROM trx_reviu_inspektorat WHERE tim_id = t.id) as jml_reviu')
            ->from('trx_tim_reviu t')
            ->join('ref_kabkota k', 'k.id = t.kabkota_id')
            ->where('t.kabkota_id', $kabkota_id)
            ->where('t.tahun', $tahun)
            ->order_by('t.tgl_sk', 'DESC')
            ->get()->result();
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('t.*, k.nama as nama_kabkota')
            ->from('trx_tim_reviu t')
            ->join('ref_kabkota k', 'k.id = t.kabkota_id')
            ->where('t.id', $id)
            ->get()->row();
    }

    public function get_anggota($tim_id)
    {
        return $this->db
            ->where('tim_id', $tim_id)
            ->order_by('urutan', 'ASC')
            ->get('trx_tim_reviu_anggota')->result();
    }

    public function insert($data)
    {
        $this->db->insert('trx_tim_reviu', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update('trx_tim_reviu', $data);
    }

    public function delete_anggota($tim_id)
    {
        $this->db->where('tim_id', $tim_id)->delete('trx_tim_reviu_anggota');
    }

    public function insert_anggota_batch($rows)
    {
        if (!empty($rows)) $this->db->insert_batch('trx_tim_reviu_anggota', $rows);
    }

    public function hapus($id)
    {
        $this->db->where('id', $id)->delete('trx_tim_reviu');
    }

    public function is_used($id)
    {
        return $this->db
            ->where('tim_id', $id)
            ->count_all_results('trx_reviu_inspektorat') > 0;
    }

    public function get_for_dropdown($kabkota_id, $tahun)
    {
        return $this->db
            ->select('t.id, t.no_sk, t.tgl_sk,
                      (SELECT COUNT(*) FROM trx_tim_reviu_anggota WHERE tim_id = t.id) as jml_anggota')
            ->from('trx_tim_reviu t')
            ->where('t.kabkota_id', $kabkota_id)
            ->where('t.tahun', $tahun)
            ->order_by('t.tgl_sk', 'DESC')
            ->get()->result();
    }
}
