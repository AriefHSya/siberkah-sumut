<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifikasi_kab_model.php — Model Verifikasi SKPKD Kab/Kota
 *
 * Akses data verifikasi oleh SKPKD Kab/Kota dan konfirmasi penerimaan dana.
 *
 * TABEL UTAMA:
 *   trx_verifikasi_skpkd_kab — record verifikasi per tahapan (UNIQUE)
 *   trx_dokumen_persyaratan  — dokumen permohonan yang diupload SKPKD Kab
 *   trx_penyaluran_dana      — data SP2D (dibaca saja dari model ini)
 *
 * POLA UPSERT:
 *   buat_atau_ambil($tahapan_id) — sama dengan Reviu_model,
 *   mencegah duplikat verifikasi untuk tahapan yang sama.
 *
 * METHOD UTAMA:
 *   get_antrian($filters)            — daftar tahapan perlu diverifikasi kab
 *   count_filtered($filters)         — hitung untuk pagination
 *   get_by_tahapan($tahapan_id)      — detail verifikasi satu tahapan
 *   buat_atau_ambil($tahapan_id)     — upsert record verifikasi
 *   update($id, $data)               — update hasil verifikasi
 *   get_dokumen($tahapan_id)         — daftar dokumen yang diupload
 *   get_penyaluran($tahapan_id)      — data SP2D terkait (jika sudah ada)
 *   count_by_status()                — untuk statistik dashboard
 *   rekap_nilai()                    — total nilai yang sudah diverifikasi kab
 */
class Verifikasi_kab_model extends CI_Model
{
    // ─── ANTRIAN VERIFIKASI ───────────────────────────────────

    /**
     * Daftar tahapan yang siap diverifikasi SKPKD Kab/Kota
     * (status: inspektorat_approved, skpkd_kab_verif, skpkd_kab_revisi,
     *          skpkd_kab_approved, dikonfirmasi)
     */
    public function get_antrian($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('t.*, p.id as pekerjaan_id, p.jenis_penyaluran,
                      p.nama_kegiatan_dok, p.nilai_kontrak, p.nilai_belanja_pendukung,
                      p.status as status_pekerjaan, p.no_dok_pekerjaan,
                      p.nama_penyedia, p.no_spmk, p.lokasi_deskripsi,
                      b.kode_bkp, b.uraian_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      u_opd.nama as nama_opd, u_opd.opd_nama,
                      v.id as verif_id, v.hasil_verifikasi, v.no_surat_verif,
                      v.tgl_verifikasi, v.catatan as catatan_verif,
                      r.hasil_reviu, r.no_lhr, r.tgl_lhr,
                      pnya.id as penyaluran_id, pnya.status_transfer');
        $this->_filter_verif_kab($filters);
        $this->db->order_by('t.created_at', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->_filter_verif_kab($filters);
        return $this->db->count_all_results();
    }

    private function _filter_verif_kab($filters)
    {
        $this->db->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p',            'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                  'b.id = p.bkp_id')
            ->join('ref_kabkota k',              'k.id = b.kabkota_id')
            ->join('ref_bidang bid',             'bid.id = b.bidang_id')
            ->join('users u_opd',                'u_opd.id = p.created_by', 'left')
            ->join('trx_verifikasi_skpkd_kab v', 'v.tahapan_id = t.id', 'left')
            ->join('trx_reviu_inspektorat r',    'r.tahapan_id = t.id', 'left')
            ->join('trx_penyaluran_dana pnya',   'pnya.tahapan_id = t.id', 'left')
            ->where_in('t.status', [
                'inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi',
                'skpkd_kab_approved','disalurkan','dikonfirmasi',
            ]);
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['tahun']))
            $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['status']))
            $this->db->where('t.status', $filters['status']);
        if (!empty($filters['jenis']))
            $this->db->where('p.jenis_penyaluran', $filters['jenis']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('p.nama_kegiatan_dok', $filters['q'])
                ->group_end();
    }

    // ─── VERIFIKASI RECORD ────────────────────────────────────

    public function get_by_tahapan($tahapan_id)
    {
        return $this->db
            ->select('v.*, pj.nama as nama_ppkd, pj.nip as nip_ppkd, pj.jabatan as jab_ppkd')
            ->from('trx_verifikasi_skpkd_kab v')
            ->join('ref_pemda_pejabat pj', 'pj.id = v.ref_ppkd_id', 'left')
            ->where('v.tahapan_id', $tahapan_id)
            ->get()->row();
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('v.*, pj.nama as nama_ppkd, pj.nip as nip_ppkd')
            ->from('trx_verifikasi_skpkd_kab v')
            ->join('ref_pemda_pejabat pj', 'pj.id = v.ref_ppkd_id', 'left')
            ->where('v.id', $id)->get()->row();
    }

    /** Buat atau ambil record verifikasi */
    public function buat_atau_ambil($tahapan_id, $user_id)
    {
        $ada = $this->db->get_where('trx_verifikasi_skpkd_kab', ['tahapan_id' => $tahapan_id])->row();
        if ($ada) return $ada->id;

        $this->db->insert('trx_verifikasi_skpkd_kab', [
            'tahapan_id'  => $tahapan_id,
            'user_skpkd'  => $user_id,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('trx_verifikasi_skpkd_kab', $data);
    }

    // ─── DOKUMEN PERMOHONAN ───────────────────────────────────

    /** Semua dokumen tahapan — include role uploader untuk filtering di view */
    public function get_dokumen($tahapan_id)
    {
        return $this->db
            ->select('d.*, u.nama as nama_uploader, r.kode as role_uploader')
            ->from('trx_dokumen_persyaratan d')
            ->join('users u', 'u.id = d.user_upload', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('d.tahapan_id', $tahapan_id)
            ->order_by('d.created_at', 'ASC')
            ->get()->result();
    }

    // ─── KONFIRMASI PENERIMAAN ────────────────────────────────

    public function get_penyaluran($tahapan_id)
    {
        return $this->db
            ->select('pd.*')
            ->from('trx_penyaluran_dana pd')
            ->where('pd.tahapan_id', $tahapan_id)
            ->get()->row();
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
                'inspektorat_approved','skpkd_kab_verif',
                'skpkd_kab_revisi','skpkd_kab_approved',
                'disalurkan','dikonfirmasi',
            ]);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        $rows = $this->db->group_by('t.status')->get()->result();
        $map  = [];
        foreach ($rows as $r) $map[$r->status] = (int)$r->total;
        return $map;
    }

    /** Rekap nilai per status untuk dashboard */
    public function rekap_nilai($tahun, $kabkota_id = NULL)
    {
        $this->db
            ->select('SUM(t.nilai_diajukan) as total_nilai,
                      SUM(CASE WHEN t.status="skpkd_kab_approved" THEN t.nilai_diajukan ELSE 0 END) as nilai_siap,
                      SUM(CASE WHEN t.status IN ("disalurkan","dikonfirmasi") THEN t.nilai_diajukan ELSE 0 END) as nilai_disalurkan')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',       'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->where_in('t.status', [
                'inspektorat_approved','skpkd_kab_verif',
                'skpkd_kab_revisi','skpkd_kab_approved',
                'disalurkan','dikonfirmasi',
            ]);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        return $this->db->get()->row();
    }
}
