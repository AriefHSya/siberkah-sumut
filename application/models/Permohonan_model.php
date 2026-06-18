<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permohonan_model.php — Permohonan Pencairan BKP oleh SKPKD Kab/Kota
 *
 * SKPKD mengelompokkan pekerjaan yang sudah diverifikasi (skpkd_kab_approved)
 * berdasarkan jenis penyaluran + kode_tahap, lalu mengajukan ke BKAD Provinsi.
 */
class Permohonan_model extends CI_Model
{
    // ─── QUERY BUILDER ───────────────────────────────────────────

    private function _build_query($filters)
    {
        $this->db->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id');
        if (!empty($filters['kabkota_id']))
            $this->db->where('pm.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['tahun']))
            $this->db->where('pm.tahun', $filters['tahun']);
        if (!empty($filters['status']))
            $this->db->where('pm.status', $filters['status']);
        if (!empty($filters['jenis']))
            $this->db->where('pm.jenis_penyaluran', $filters['jenis']);
    }

    public function get_all($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select("pm.*, k.nama as nama_kabkota,
            (SELECT COUNT(*) FROM trx_permohonan_item WHERE permohonan_id = pm.id) as jumlah_item,
            (SELECT SUM(
                CASE WHEN pm.jenis_penyaluran = 'bertahap' AND pm.kode_tahap = 'tahap_2'
                     THEN tp.nilai_diajukan
                     ELSE tp.nilai_diajukan + IFNULL(pk.nilai_belanja_pendukung, 0)
                END)
             FROM trx_permohonan_item pi
             JOIN trx_tahapan_penyaluran tp ON tp.id = pi.tahapan_id
             JOIN trx_pekerjaan pk ON pk.id = tp.pekerjaan_id
             WHERE pi.permohonan_id = pm.id) as total_nilai", FALSE);
        $this->_build_query($filters);
        $this->db->order_by('pm.created_at', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->_build_query($filters);
        return $this->db->count_all_results();
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('pm.*, k.nama as nama_kabkota, u.nama as nama_pembuat')
            ->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->join('users u', 'u.id = pm.created_by', 'left')
            ->where('pm.id', $id)
            ->get()->row();
    }

    // ─── ITEMS ───────────────────────────────────────────────────

    public function get_items($permohonan_id)
    {
        return $this->db
            ->select('pi.id as item_id, t.id as tahapan_id, t.kode_tahap,
                      t.nilai_diajukan, t.persen_nilai, t.status as tahapan_status,
                      p.id as pekerjaan_id, p.nama_kegiatan_dok, p.nilai_kontrak,
                      p.nilai_belanja_pendukung,
                      p.no_dok_pekerjaan, p.nama_penyedia, p.jenis_penyaluran,
                      p.status as pekerjaan_status,
                      b.kode_bkp, b.uraian_bkp, b.nilai as pagu_bkp')
            ->from('trx_permohonan_item pi')
            ->join('trx_tahapan_penyaluran t', 't.id = pi.tahapan_id')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('pi.permohonan_id', $permohonan_id)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();
    }

    // ─── KELOMPOK TERSEDIA ────────────────────────────────────────

    /**
     * Kelompok (jenis+kode_tahap) yang punya pekerjaan siap diajukan
     * (status skpkd_kab_approved, belum masuk permohonan manapun)
     */
    /**
     * Subquery tahapan_id yang masih terikat ke permohonan AKTIF
     * (draft/diajukan). Tahapan dari permohonan yang batal/ditolak
     * tidak dikecualikan, sehingga bisa dipilih kembali ke
     * permohonan baru.
     */
    private function _subquery_tahapan_terikat()
    {
        return 'SELECT pi.tahapan_id FROM trx_permohonan_item pi '
            . 'JOIN trx_permohonan pm ON pm.id = pi.permohonan_id '
            . "WHERE pm.status IN ('draft','diajukan')";
    }

    public function get_kelompok_tersedia($kabkota_id, $tahun)
    {
        return $this->db
            ->select("p.jenis_penyaluran, t.kode_tahap,
                      COUNT(*) as jumlah,
                      SUM(CASE WHEN p.jenis_penyaluran = 'bertahap' AND t.kode_tahap = 'tahap_2'
                               THEN t.nilai_diajukan
                               ELSE t.nilai_diajukan + IFNULL(p.nilai_belanja_pendukung, 0)
                          END) as total_nilai", FALSE)
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('t.status', 'skpkd_kab_approved')
            ->where('b.kabkota_id', $kabkota_id)
            ->where('b.tahun', $tahun)
            ->where('t.id NOT IN (' . $this->_subquery_tahapan_terikat() . ')', NULL, FALSE)
            ->group_by(['p.jenis_penyaluran', 't.kode_tahap'])
            ->order_by('p.jenis_penyaluran', 'ASC')
            ->get()->result();
    }

    /** Pekerjaan yang eligible untuk satu kelompok permohonan */
    public function get_eligible($kabkota_id, $tahun, $jenis, $kode_tahap)
    {
        return $this->db
            ->select('t.id as tahapan_id, t.kode_tahap, t.nilai_diajukan, t.persen_nilai,
                      p.id as pekerjaan_id, p.nama_kegiatan_dok, p.nilai_kontrak,
                      p.no_dok_pekerjaan, p.nama_penyedia, p.jenis_penyaluran,
                      b.kode_bkp, b.uraian_bkp')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('t.status', 'skpkd_kab_approved')
            ->where('b.kabkota_id', $kabkota_id)
            ->where('b.tahun', $tahun)
            ->where('p.jenis_penyaluran', $jenis)
            ->where('t.kode_tahap', $kode_tahap)
            ->where('t.id NOT IN (' . $this->_subquery_tahapan_terikat() . ')', NULL, FALSE)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();
    }

    // ─── CREATE ───────────────────────────────────────────────────

    public function create($data, $tahapan_ids)
    {
        $this->db->trans_start();

        $this->db->insert('trx_permohonan', $data);
        $permohonan_id = $this->db->insert_id();

        foreach ($tahapan_ids as $tid) {
            $this->db->insert('trx_permohonan_item', [
                'permohonan_id' => $permohonan_id,
                'tahapan_id'    => (int)$tid,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() ? $permohonan_id : FALSE;
    }
}
