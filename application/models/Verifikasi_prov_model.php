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
 *   trx_bukti_transfer         — bukti transfer dari kab (dibaca saja)
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
    // ─── ANTRIAN ──────────────────────────────────────────────

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
            ->select('pd.*, u.nama as nama_input,
                      bt.file_path as bukti_path, bt.keterangan as bukti_ket,
                      bt.created_at as tgl_bukti')
            ->from('trx_penyaluran_dana pd')
            ->join('users u',              'u.id = pd.user_input', 'left')
            ->join('trx_bukti_transfer bt','bt.penyaluran_id = pd.id', 'left')
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
        return $this->db
            ->select('COUNT(*) as total_tahapan,
                      SUM(t.nilai_diajukan) as total_nilai_diajukan,
                      SUM(pd.nilai_transfer) as total_disalurkan,
                      COUNT(pd.id) as total_sp2d,
                      COUNT(CASE WHEN t.status="dikonfirmasi" THEN 1 END) as total_dikonfirmasi')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',       'b.id = p.bkp_id')
            ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
            ->where('b.tahun', $tahun)
            ->where_in('t.status', ['disalurkan','dikonfirmasi'])
            ->get()->row();
    }

    /** Daftar SP2D per tahun untuk laporan */
    public function get_daftar_sp2d($tahun, $kabkota_id = NULL)
    {
        $this->db
            ->select('pd.*, t.label_tahap, t.nilai_diajukan, t.kode_tahap,
                      p.jenis_penyaluran, p.nama_kegiatan_dok,
                      b.kode_bkp, b.uraian_bkp,
                      k.nama as nama_kabkota, bid.nama as nama_bidang')
            ->from('trx_penyaluran_dana pd')
            ->join('trx_tahapan_penyaluran t', 't.id = pd.tahapan_id')
            ->join('trx_pekerjaan p',           'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                 'b.id = p.bkp_id')
            ->join('ref_kabkota k',             'k.id = b.kabkota_id')
            ->join('ref_bidang bid',            'bid.id = b.bidang_id')
            ->where('b.tahun', $tahun);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        return $this->db->order_by('pd.tgl_sp2d', 'ASC')
            ->order_by('k.nama', 'ASC')->get()->result();
    }
}
