<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifikasi_kab_model — Sprint 4
 * Mengelola trx_verifikasi_skpkd_kab, konfirmasi penerimaan (trx_bukti_transfer)
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

    /** Semua dokumen tahapan (termasuk yang diupload OPD & SKPKD) */
    public function get_dokumen($tahapan_id)
    {
        return $this->db
            ->select('d.*, u.nama as nama_uploader')
            ->from('trx_dokumen_persyaratan d')
            ->join('users u', 'u.id = d.user_upload', 'left')
            ->where('d.tahapan_id', $tahapan_id)
            ->order_by('d.created_at', 'ASC')
            ->get()->result();
    }

    // ─── KONFIRMASI PENERIMAAN ────────────────────────────────

    public function get_penyaluran($tahapan_id)
    {
        return $this->db
            ->select('pd.*, bt.id as bukti_id, bt.file_path as bukti_path,
                      bt.nama_file as bukti_nama, bt.keterangan as bukti_ket')
            ->from('trx_penyaluran_dana pd')
            ->join('trx_bukti_transfer bt', 'bt.penyaluran_id = pd.id', 'left')
            ->where('pd.tahapan_id', $tahapan_id)
            ->get()->row();
    }

    public function simpan_bukti_transfer($penyaluran_id, $file_path, $nama_file, $keterangan, $user_id)
    {
        // Hapus bukti lama jika ada
        $lama = $this->db->get_where('trx_bukti_transfer', ['penyaluran_id' => $penyaluran_id])->row();
        if ($lama && $lama->file_path && file_exists(FCPATH . $lama->file_path)) {
            unlink(FCPATH . $lama->file_path);
        }
        $this->db->delete('trx_bukti_transfer', ['penyaluran_id' => $penyaluran_id]);

        $this->db->insert('trx_bukti_transfer', [
            'penyaluran_id' => $penyaluran_id,
            'file_path'     => $file_path,
            'nama_file'     => $nama_file,
            'keterangan'    => $keterangan,
            'user_upload'   => $user_id,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
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
