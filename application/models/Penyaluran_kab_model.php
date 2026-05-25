<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyaluran_kab_model.php — Data Penyaluran Dana untuk SKPKD Kab/Kota
 *
 * Menyediakan data permohonan beserta SP2D dari Provinsi dan status
 * konfirmasi RKUD oleh SKPKD Kab/Kota.
 *
 * TABEL:
 *   trx_permohonan         — permohonan pencairan + kolom SP2D + kolom RKUD
 *   trx_permohonan_item    — daftar kegiatan (tahapan) dalam permohonan
 *   trx_tahapan_penyaluran — status per tahapan
 *   ref_kabkota            — nama kabkota
 *
 * ALUR:
 *   Admin Provinsi input SP2D (no_sp2d, nilai_sp2d, status_sp2d)
 *   → SKPKD Kab/Kota konfirmasi penerimaan RKUD (kode_transaksi_rkud, nilai_rkud, tgl_rkud)
 *   → Tahapan status → dikonfirmasi
 *   → Pekerjaan status → dikonfirmasi_tahap1 (bertahap-1) atau selesai (lainnya)
 */
class Penyaluran_kab_model extends CI_Model
{
    public function get_list($kabkota_id, $tahun, $filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('pm.*,
            k.nama as nama_kabkota,
            (SELECT COUNT(*) FROM trx_permohonan_item pi WHERE pi.permohonan_id = pm.id) as jumlah_kegiatan,
            (SELECT SUM(t2.nilai_diajukan + IFNULL(pk2.nilai_belanja_pendukung,0))
             FROM trx_permohonan_item pi2
             JOIN trx_tahapan_penyaluran t2 ON t2.id = pi2.tahapan_id
             JOIN trx_pekerjaan pk2 ON pk2.id = t2.pekerjaan_id
             WHERE pi2.permohonan_id = pm.id) as total_nilai')
            ->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->where('pm.kabkota_id', $kabkota_id)
            ->where('pm.tahun', $tahun)
            ->where('pm.status', 'diajukan');

        if (!empty($filters['q'])) {
            $this->db->group_start()
                ->like('pm.no_permohonan', $filters['q'])
                ->or_like('pm.no_sp2d', $filters['q'])
                ->group_end();
        }
        if (!empty($filters['status_rkud'])) {
            if ($filters['status_rkud'] === 'belum_sp2d') {
                $this->db->where('pm.no_sp2d IS NULL', NULL, FALSE);
            } elseif ($filters['status_rkud'] === 'menunggu') {
                $this->db->where('pm.no_sp2d IS NOT NULL', NULL, FALSE)
                    ->where('pm.kode_transaksi_rkud IS NULL', NULL, FALSE);
            } elseif ($filters['status_rkud'] === 'dikonfirmasi') {
                $this->db->where('pm.kode_transaksi_rkud IS NOT NULL', NULL, FALSE);
            }
        }

        $this->db->order_by('pm.created_at', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_list($kabkota_id, $tahun, $filters = [])
    {
        $this->db->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->where('pm.kabkota_id', $kabkota_id)
            ->where('pm.tahun', $tahun)
            ->where('pm.status', 'diajukan');

        if (!empty($filters['q'])) {
            $this->db->group_start()
                ->like('pm.no_permohonan', $filters['q'])
                ->or_like('pm.no_sp2d', $filters['q'])
                ->group_end();
        }
        if (!empty($filters['status_rkud'])) {
            if ($filters['status_rkud'] === 'belum_sp2d') {
                $this->db->where('pm.no_sp2d IS NULL', NULL, FALSE);
            } elseif ($filters['status_rkud'] === 'menunggu') {
                $this->db->where('pm.no_sp2d IS NOT NULL', NULL, FALSE)
                    ->where('pm.kode_transaksi_rkud IS NULL', NULL, FALSE);
            } elseif ($filters['status_rkud'] === 'dikonfirmasi') {
                $this->db->where('pm.kode_transaksi_rkud IS NOT NULL', NULL, FALSE);
            }
        }
        return $this->db->count_all_results();
    }

    public function get_by_id($pm_id)
    {
        return $this->db
            ->select('pm.*, k.nama as nama_kabkota')
            ->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->where('pm.id', $pm_id)
            ->get()->row();
    }

    public function get_items($pm_id)
    {
        return $this->db
            ->select('pi.id as item_id, t.id as tahapan_id, t.kode_tahap, t.label_tahap,
                      t.nilai_diajukan, t.status as tahapan_status, t.pekerjaan_id,
                      p.nama_kegiatan_dok, p.jenis_penyaluran, p.nilai_belanja_pendukung,
                      b.kode_bkp, b.uraian_bkp')
            ->from('trx_permohonan_item pi')
            ->join('trx_tahapan_penyaluran t', 't.id = pi.tahapan_id')
            ->join('trx_pekerjaan p',          'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->where('pi.permohonan_id', $pm_id)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();
    }

    public function simpan_konfirmasi($pm_id, $data)
    {
        $data['tgl_konfirmasi_rkud'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $pm_id)->update('trx_permohonan', $data);
    }

    public function rekap($kabkota_id, $tahun)
    {
        return $this->db->query("
            SELECT
                COUNT(*) as total_permohonan,
                COUNT(CASE WHEN no_sp2d IS NOT NULL THEN 1 END) as ada_sp2d,
                COUNT(CASE WHEN kode_transaksi_rkud IS NOT NULL THEN 1 END) as dikonfirmasi,
                COALESCE(SUM(CASE WHEN kode_transaksi_rkud IS NOT NULL THEN nilai_rkud ELSE 0 END),0) as total_rkud
            FROM trx_permohonan
            WHERE kabkota_id = ? AND tahun = ? AND status = 'diajukan'
        ", [$kabkota_id, $tahun])->row();
    }
}
