<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifikasi_prov_model.php — Model Verifikasi SKPKD Provinsi & Penyaluran
 *
 * Akses data verifikasi final oleh Admin Provinsi dan pencairan dana (SP2D).
 *
 * TABEL UTAMA:
 *   trx_verifikasi_skpkd_prov — record verifikasi provinsi per tahapan (UNIQUE)
 *   trx_penyaluran_dana        — data SP2D + tanggal + nominal per tahapan
 *
 * ALUR DATA:
 *   SKPKD Kab approve → verifikasi provinsi bisa dibuat
 *   → Admin Provinsi putuskan (disetujui/ditolak/revisi)
 *   → Jika disetujui: input SP2D → konfirmasi transfer
 *   → SKPKD Kab konfirmasi RKUD → status = selesai
 *
 * VALIDASI BISNIS (di controller, model hanya akses data):
 *   SP2D hanya bisa diinput jika verifikasi prov = 'disetujui'
 *   Konfirmasi transfer hanya jika SP2D sudah ada
 *
 * METHOD UTAMA:
 *   get_antrian($filters)            — daftar tahapan siap diverifikasi provinsi
 *   get_verif_by_tahapan($id)        — detail verifikasi satu tahapan
 *   buat_atau_ambil_verif($id)       — upsert record verifikasi provinsi
 *   update_verif($id, $data)         — simpan hasil verifikasi
 *   simpan_sp2d($tahapan_id, $data)  — insert/update data SP2D
 *   update_status_transfer($id)      — update status setelah konfirmasi transfer
 *   rekap_penyaluran()               — rekap total penyaluran untuk laporan
 *   get_daftar_sp2d()               — daftar semua SP2D untuk export
 */
class Verifikasi_prov_model extends CI_Model
{
    // ─── DAFTAR PERMOHONAN DARI KAB/KOTA ─────────────────────

    public function get_permohonan_list($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select("pm.*, k.nama as nama_kabkota, u.nama as nama_pembuat,
            (SELECT COUNT(*) FROM trx_permohonan_item pi
             WHERE pi.permohonan_id = pm.id) as jumlah_item,
            (SELECT COUNT(*) FROM trx_permohonan_item pi2
             JOIN trx_tahapan_penyaluran t2 ON t2.id = pi2.tahapan_id
             WHERE pi2.permohonan_id = pm.id
               AND t2.status IN ('disalurkan','dikonfirmasi')) as item_disalurkan,
            (SELECT SUM(t3.nilai_diajukan + IFNULL(pk3.nilai_belanja_pendukung,0))
             FROM trx_permohonan_item pi3
             JOIN trx_tahapan_penyaluran t3 ON t3.id = pi3.tahapan_id
             JOIN trx_pekerjaan pk3 ON pk3.id = t3.pekerjaan_id
             WHERE pi3.permohonan_id = pm.id) as total_nilai");
        $this->_build_pm_query($filters);
        $this->db->order_by('pm.created_at', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_permohonan_filtered($filters = [])
    {
        $this->_build_pm_query($filters);
        return $this->db->count_all_results();
    }

    private function _build_pm_query($filters)
    {
        $this->db->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->join('users u', 'u.id = pm.created_by', 'left')
            ->where('pm.status', 'diajukan');
        if (!empty($filters['tahun']))
            $this->db->where('pm.tahun', $filters['tahun']);
        if (!empty($filters['kabkota_id']))
            $this->db->where('pm.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['jenis']))
            $this->db->where('pm.jenis_penyaluran', $filters['jenis']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('pm.no_permohonan', $filters['q'])
                ->or_like('k.nama', $filters['q'])
                ->group_end();
    }

    public function get_permohonan_by_id($id)
    {
        return $this->db
            ->select('pm.*, k.nama as nama_kabkota, u.nama as nama_pembuat')
            ->from('trx_permohonan pm')
            ->join('ref_kabkota k', 'k.id = pm.kabkota_id')
            ->join('users u', 'u.id = pm.created_by', 'left')
            ->where('pm.id', $id)
            ->get()->row();
    }

    public function get_permohonan_items_for_prov($permohonan_id)
    {
        return $this->db
            ->select('pi.id as item_id, t.id as tahapan_id, t.kode_tahap, t.label_tahap,
                      t.nilai_diajukan, t.persen_nilai, t.status as tahapan_status,
                      p.id as pekerjaan_id, p.nama_kegiatan_dok, p.nilai_kontrak,
                      p.nilai_belanja_pendukung, p.jenis_penyaluran,
                      p.no_dok_pekerjaan, p.nama_penyedia,
                      b.kode_bkp, b.uraian_bkp, b.nilai as pagu_bkp,
                      pd.id as penyaluran_id, pd.no_sp2d, pd.tgl_sp2d,
                      pd.nilai_transfer, pd.status_transfer,
                      vp.hasil_verifikasi as hasil_verif_prov')
            ->from('trx_permohonan_item pi')
            ->join('trx_tahapan_penyaluran t',    't.id = pi.tahapan_id')
            ->join('trx_pekerjaan p',              'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                    'b.id = p.bkp_id')
            ->join('trx_penyaluran_dana pd',       'pd.tahapan_id = t.id', 'left')
            ->join('trx_verifikasi_skpkd_prov vp', 'vp.tahapan_id = t.id', 'left')
            ->where('pi.permohonan_id', $permohonan_id)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();
    }

    // ─── ANTRIAN (TAHAPAN INDIVIDUAL) ────────────────────────

    public function get_antrian($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('t.*, p.id as pekerjaan_id, p.jenis_penyaluran,
                      p.nama_kegiatan_dok, p.nilai_kontrak,
                      p.no_dok_pekerjaan, p.nama_penyedia, p.no_spmk,
                      p.status as status_pekerjaan,
                      b.kode_bkp, b.uraian_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      vk.hasil_verifikasi as hasil_verif_kab,
                      vk.no_surat_verif,
                      vp.id as verif_prov_id, vp.hasil_verifikasi as hasil_verif_prov,
                      vp.tgl_verifikasi as tgl_verif_prov,
                      pd.id as penyaluran_id, pd.no_sp2d, pd.tgl_sp2d,
                      pd.nilai_transfer, pd.status_transfer,
                      r.no_lhr');
        $this->_filter_verif_prov($filters);
        $this->db->order_by('t.updated_at', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->_filter_verif_prov($filters);
        return $this->db->count_all_results();
    }

    private function _filter_verif_prov($filters)
    {
        $this->db->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p',              'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                    'b.id = p.bkp_id')
            ->join('ref_kabkota k',                'k.id = b.kabkota_id')
            ->join('ref_bidang bid',               'bid.id = b.bidang_id')
            ->join('trx_verifikasi_skpkd_kab vk',  'vk.tahapan_id = t.id', 'left')
            ->join('trx_verifikasi_skpkd_prov vp', 'vp.tahapan_id = t.id', 'left')
            ->join('trx_penyaluran_dana pd',       'pd.tahapan_id = t.id', 'left')
            ->join('trx_reviu_inspektorat r',      'r.tahapan_id = t.id', 'left')
            ->where_in('t.status', [
                'skpkd_kab_approved','skpkd_prov_verif','skpkd_prov_revisi',
                'disalurkan','dikonfirmasi',
            ]);
        if (!empty($filters['tahun']))
            $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['status']))
            $this->db->where('t.status', $filters['status']);
        if (!empty($filters['jenis']))
            $this->db->where('p.jenis_penyaluran', $filters['jenis']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('p.nama_kegiatan_dok', $filters['q'])
                ->or_like('k.nama', $filters['q'])
                ->group_end();
    }

    // ─── VERIFIKASI PROV RECORD ───────────────────────────────

    public function get_verif_by_tahapan($tahapan_id)
    {
        return $this->db->get_where('trx_verifikasi_skpkd_prov',
            ['tahapan_id' => $tahapan_id])->row();
    }

    public function get_verif_by_id($id)
    {
        return $this->db->get_where('trx_verifikasi_skpkd_prov',
            ['id' => $id])->row();
    }

    public function buat_atau_ambil_verif($tahapan_id, $user_id)
    {
        $ada = $this->db->get_where('trx_verifikasi_skpkd_prov',
            ['tahapan_id' => $tahapan_id])->row();
        if ($ada) return $ada->id;

        $this->db->insert('trx_verifikasi_skpkd_prov', [
            'tahapan_id'     => $tahapan_id,
            'user_admin_prov'=> $user_id,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function update_verif($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('trx_verifikasi_skpkd_prov', $data);
    }

    // ─── PENYALURAN DANA / SP2D ───────────────────────────────

    public function get_penyaluran($tahapan_id)
    {
        return $this->db
            ->select('pd.*, u.nama as nama_input')
            ->from('trx_penyaluran_dana pd')
            ->join('users u', 'u.id = pd.user_input', 'left')
            ->where('pd.tahapan_id', $tahapan_id)
            ->get()->row();
    }

    public function simpan_sp2d($tahapan_id, $data, $user_id)
    {
        $data['user_input'] = $user_id;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $ada = $this->db->get_where('trx_penyaluran_dana',
            ['tahapan_id' => $tahapan_id])->row();

        if ($ada) {
            $this->db->where('id', $ada->id)->update('trx_penyaluran_dana', $data);
            return $ada->id;
        }

        $data['tahapan_id'] = $tahapan_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('trx_penyaluran_dana', $data);
        return $this->db->insert_id();
    }

    public function update_status_transfer($penyaluran_id, $status)
    {
        return $this->db->where('id', $penyaluran_id)
            ->update('trx_penyaluran_dana', [
                'status_transfer' => $status,
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
    }

    // ─── STATISTIK ────────────────────────────────────────────

    public function count_by_status($tahun, $kabkota_id = NULL)
    {
        $this->db
            ->select('t.status, COUNT(*) as total')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',       'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->where_in('t.status', [
                'skpkd_kab_approved','skpkd_prov_verif',
                'skpkd_prov_revisi','disalurkan','dikonfirmasi',
            ]);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        $rows = $this->db->group_by('t.status')->get()->result();
        $map  = [];
        foreach ($rows as $r) $map[$r->status] = (int)$r->total;
        return $map;
    }

    public function rekap_penyaluran($tahun)
    {
        return $this->db->query("
            SELECT
                COUNT(CASE WHEN pm.no_sp2d IS NOT NULL THEN 1 END)         AS total_sp2d,
                COALESCE(SUM(CASE WHEN pm.status_sp2d IS NOT NULL
                               THEN COALESCE(pm.nilai_sp2d, 0) ELSE 0 END), 0) AS total_disalurkan,
                COUNT(CASE WHEN pm.status_sp2d = 'selesai' THEN 1 END)     AS total_dikonfirmasi
            FROM trx_permohonan pm
            WHERE pm.tahun = ?
        ", [$tahun])->row();
    }

    /** Rincian kegiatan (BKP) dalam satu permohonan — untuk cetak rekap SP2D */
    public function get_items_ringkas($permohonan_id)
    {
        return $this->db
            ->select('b.kode_bkp, b.uraian_bkp, t.nilai_diajukan, p.nilai_belanja_pendukung')
            ->from('trx_permohonan_item pi')
            ->join('trx_tahapan_penyaluran t', 't.id = pi.tahapan_id')
            ->join('trx_pekerjaan p',          'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->where('pi.permohonan_id', $permohonan_id)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();
    }

    /** Daftar SP2D per tahun untuk laporan (per permohonan) */
    public function get_daftar_sp2d($tahun, $kabkota_id = NULL)
    {
        $sql = "
            SELECT pm.id, pm.no_permohonan, pm.no_sp2d, pm.tgl_sp2d,
                   pm.nilai_sp2d as nilai_transfer, pm.status_sp2d as status_transfer,
                   pm.rek_asal, pm.nama_bank_asal, pm.rek_tujuan, pm.nama_bank_tujuan,
                   pm.jenis_penyaluran, pm.kode_tahap,
                   k.nama as nama_kabkota,
                   (SELECT COUNT(*) FROM trx_permohonan_item pi
                    WHERE pi.permohonan_id = pm.id) as jumlah_item
            FROM trx_permohonan pm
            JOIN ref_kabkota k ON k.id = pm.kabkota_id
            WHERE pm.tahun = ? AND pm.no_sp2d IS NOT NULL
        ";
        $binds = [$tahun];
        if ($kabkota_id) { $sql .= " AND pm.kabkota_id = ?"; $binds[] = $kabkota_id; }
        $sql .= " ORDER BY pm.tgl_sp2d ASC, k.nama ASC";
        return $this->db->query($sql, $binds)->result();
    }
}
