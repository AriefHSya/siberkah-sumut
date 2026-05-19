<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Capaian_model.php — Model Capaian Output Fisik
 *
 * Akses data capaian realisasi output fisik pekerjaan.
 * Capaian wajib diisi OPD setelah Tahap I dikonfirmasi,
 * sebelum bisa mengajukan Tahap II (untuk jenis bertahap).
 *
 * TABEL UTAMA: trx_capaian_output
 * JOIN       : trx_tahapan_penyaluran, trx_pekerjaan, ref_bkp
 *
 * SKEMA PENTING (jangan salah nama kolom):
 *   trx_tahapan_penyaluran.kode_tahap = 'tahap_1' (DENGAN underscore, bukan 'tahap1')
 *   trx_tahapan_penyaluran.persen_nilai (bukan porsi_persen)
 *   Konstanta KODE_TAHAP1 = 'tahap_1' tersedia untuk referensi
 *
 * METHOD UTAMA:
 *   get_list($filters)              — daftar pekerjaan perlu input capaian (paginated)
 *   count_filtered($filters)        — hitung untuk pagination
 *   get_detail($pekerjaan_id)       — detail pekerjaan + tahapan untuk form capaian
 *   get_by_tahapan($tahapan_id)     — data capaian satu tahapan (jika sudah ada)
 *   simpan($tahapan_id, $data)      — upsert capaian (insert atau update)
 */
class Capaian_model extends CI_Model
{
    // kode_tahap Tahap I di DB
    const KODE_TAHAP1 = 'tahap_1';

    // ─── LIST ─────────────────────────────────────────────────

    public function get_list($filters = [], $limit = 0, $offset = 0)
    {
        $this->db
            ->select('p.id as pekerjaan_id, p.status, p.jenis_penyaluran,
                      p.nama_kegiatan_dok, p.nilai_kontrak, p.nama_penyedia,
                      b.kode_bkp, b.uraian_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      t.id as tahapan_id, t.kode_tahap, t.status as status_tahapan,
                      t.persen_nilai,
                      pd.nilai_transfer, pd.tgl_sp2d, pd.no_sp2d,
                      c.id as capaian_id, c.persen_fisik, c.tgl_realisasi')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->join('ref_kabkota k',            'k.id = b.kabkota_id')
            ->join('ref_bidang bid',           'bid.id = b.bidang_id')
            ->join('trx_tahapan_penyaluran t',
                   't.pekerjaan_id = p.id AND t.kode_tahap = \'' . self::KODE_TAHAP1 . '\'')
            ->join('trx_penyaluran_dana pd',   'pd.tahapan_id = t.id', 'left')
            ->join('trx_capaian_output c',     'c.tahapan_id = t.id', 'left')
            ->where_in('p.status', ['dikonfirmasi_tahap1', 'opd_capaian_tahap1']);

        if (!empty($filters['tahun']))
            $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['status']))
            $this->db->where('p.status', $filters['status']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('p.nama_kegiatan_dok', $filters['q'])
                ->group_end();

        $this->db->order_by('b.kode_bkp', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->db
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->join('ref_kabkota k',            'k.id = b.kabkota_id')
            ->join('trx_tahapan_penyaluran t',
                   't.pekerjaan_id = p.id AND t.kode_tahap = \'' . self::KODE_TAHAP1 . '\'')
            ->where_in('p.status', ['dikonfirmasi_tahap1', 'opd_capaian_tahap1']);
        if (!empty($filters['tahun']))      $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['kabkota_id'])) $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['status']))     $this->db->where('p.status', $filters['status']);
        return $this->db->count_all_results();
    }

    // ─── DETAIL ───────────────────────────────────────────────

    public function get_detail($pekerjaan_id)
    {
        return $this->db
            ->select('p.*, b.kode_bkp, b.uraian_bkp, b.nilai as nilai_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      t.id as tahapan_id, t.status as status_tahapan, t.persen_nilai,
                      pd.no_sp2d, pd.tgl_sp2d, pd.nilai_transfer,
                      c.id as capaian_id, c.persen_fisik, c.tgl_realisasi,
                      c.no_ba_kemajuan, c.tgl_ba_kemajuan, c.keterangan, c.foto_path')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->join('ref_kabkota k',            'k.id = b.kabkota_id')
            ->join('ref_bidang bid',           'bid.id = b.bidang_id')
            ->join('trx_tahapan_penyaluran t',
                   't.pekerjaan_id = p.id AND t.kode_tahap = \'' . self::KODE_TAHAP1 . '\'')
            ->join('trx_penyaluran_dana pd',   'pd.tahapan_id = t.id', 'left')
            ->join('trx_capaian_output c',     'c.tahapan_id = t.id', 'left')
            ->where('p.id', $pekerjaan_id)
            ->get()->row();
    }

    public function get_by_tahapan($tahapan_id)
    {
        return $this->db->get_where('trx_capaian_output',
            ['tahapan_id' => $tahapan_id])->row();
    }

    // ─── WRITE ────────────────────────────────────────────────

    public function simpan($tahapan_id, $data, $user_id)
    {
        $existing = $this->get_by_tahapan($tahapan_id);
        $data['tahapan_id'] = $tahapan_id;
        $data['user_input'] = $user_id;

        if ($existing) {
            $this->db->where('id', $existing->id)->update('trx_capaian_output', $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('trx_capaian_output', $data);
        }
    }
}
