<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan_model — Sprint 6
 * Statistik dashboard lengkap + rekap BKP + rekap penyaluran
 */
class Laporan_model extends CI_Model
{
    // ─── STATISTIK DASHBOARD ──────────────────────────────────

    public function get_stats_provinsi($tahun)
    {
        // Total BKP & nilai
        $bkp = $this->db->select('COUNT(*) as total, SUM(nilai) as total_nilai')
            ->where('tahun', $tahun)->where('is_active', 1)
            ->get('ref_bkp')->row();

        // Pekerjaan per status
        $pek_rows = $this->db
            ->select('p.status, COUNT(*) as total')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->group_by('p.status')->get()->result();
        $pek_status = [];
        foreach ($pek_rows as $r) $pek_status[$r->status] = (int)$r->total;

        // SP2D & realisasi
        $sp2d = $this->db
            ->select('COUNT(*) as total_sp2d,
                      SUM(pd.nilai_transfer) as total_transfer,
                      COUNT(CASE WHEN pd.status_transfer="selesai" THEN 1 END) as selesai')
            ->from('trx_penyaluran_dana pd')
            ->join('trx_tahapan_penyaluran t', 't.id = pd.tahapan_id')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->get()->row();

        // Kab/kota yang sudah punya pekerjaan
        $kab_aktif = $this->db
            ->select('COUNT(DISTINCT b.kabkota_id) as total')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->get()->row();

        return [
            'bkp'        => $bkp,
            'pek_status' => $pek_status,
            'sp2d'       => $sp2d,
            'kab_aktif'  => $kab_aktif,
        ];
    }

    public function get_stats_kabkota($tahun, $kabkota_id)
    {
        $bkp = $this->db->select('COUNT(*) as total, SUM(nilai) as total_nilai')
            ->where(['tahun'=>$tahun,'kabkota_id'=>$kabkota_id,'is_active'=>1])
            ->get('ref_bkp')->row();

        $pek_rows = $this->db
            ->select('p.status, COUNT(*) as total')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where(['b.tahun'=>$tahun, 'b.kabkota_id'=>$kabkota_id])
            ->group_by('p.status')->get()->result();
        $pek_status = [];
        foreach ($pek_rows as $r) $pek_status[$r->status] = (int)$r->total;

        $sp2d = $this->db
            ->select('COUNT(*) as total_sp2d, SUM(pd.nilai_transfer) as total_transfer')
            ->from('trx_penyaluran_dana pd')
            ->join('trx_tahapan_penyaluran t', 't.id = pd.tahapan_id')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where(['b.tahun'=>$tahun, 'b.kabkota_id'=>$kabkota_id])
            ->get()->row();

        return ['bkp'=>$bkp, 'pek_status'=>$pek_status, 'sp2d'=>$sp2d];
    }

    /** Distribusi pekerjaan per bidang untuk chart */
    public function get_per_bidang($tahun, $kabkota_id = NULL)
    {
        $this->db->select('bid.nama, COUNT(p.id) as total, SUM(p.nilai_kontrak) as total_nilai')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b',    'b.id = p.bkp_id')
            ->join('ref_bidang bid','bid.id = b.bidang_id')
            ->where('b.tahun', $tahun);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        return $this->db->group_by('bid.id')->order_by('total_nilai','DESC')->get()->result();
    }

    /** Distribusi per kabkota (untuk peta/chart provinsi) */
    public function get_per_kabkota($tahun)
    {
        return $this->db
            ->select('k.nama, k.id as kabkota_id,
                      COUNT(DISTINCT b.id) as total_bkp,
                      COUNT(DISTINCT p.id) as total_pekerjaan,
                      SUM(p.nilai_kontrak) as total_kontrak,
                      SUM(pd.nilai_transfer) as total_disalurkan,
                      COUNT(DISTINCT pd.id) as total_sp2d')
            ->from('ref_kabkota k')
            ->join('ref_bkp b',   'b.kabkota_id = k.id AND b.tahun = '.$this->db->escape($tahun).' AND b.is_active = 1', 'left')
            ->join('trx_pekerjaan p', 'p.bkp_id = b.id', 'left')
            ->join('trx_tahapan_penyaluran t', 't.pekerjaan_id = p.id', 'left')
            ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
            ->where('k.is_active', 1)
            ->group_by('k.id')
            ->order_by('total_disalurkan', 'DESC')
            ->get()->result();
    }

    /** Progress alur per tahapan (funnel view) */
    public function get_funnel($tahun, $kabkota_id = NULL)
    {
        $stages = [
            'total_bkp'     => 'BKP Terdaftar',
            'ada_pekerjaan' => 'Sudah Input Pekerjaan',
            'reviu_selesai' => 'Reviu Inspektorat Selesai',
            'verif_kab_ok'  => 'Verifikasi Kab Selesai',
            'disalurkan'    => 'Dana Disalurkan',
            'dikonfirmasi'  => 'Dikonfirmasi Kab',
        ];

        $q_bkp = $this->db->where('tahun',$tahun)->where('is_active',1);
        if ($kabkota_id) $q_bkp->where('kabkota_id',$kabkota_id);
        $total_bkp = $q_bkp->count_all_results('ref_bkp');

        $this->db->from('trx_pekerjaan p')->join('ref_bkp b','b.id=p.bkp_id')->where('b.tahun',$tahun);
        if ($kabkota_id) $this->db->where('b.kabkota_id',$kabkota_id);
        $c_pekerjaan = $this->db->count_all_results();

        $st_counts = $this->db
            ->select('t.status, COUNT(*) as total')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
            ->join('ref_bkp b','b.id=p.bkp_id')
            ->where('b.tahun',$tahun);
        if ($kabkota_id) $st_counts->where('b.kabkota_id',$kabkota_id);
        $st_rows = $st_counts->group_by('t.status')->get()->result();
        $sc = [];
        foreach ($st_rows as $r) $sc[$r->status] = (int)$r->total;

        return [
            'BKP Terdaftar'               => $total_bkp,
            'Input Pekerjaan'             => $c_pekerjaan,
            'Reviu Inspektorat Selesai'   => ($sc['inspektorat_approved'] ?? 0) + ($sc['skpkd_kab_verif'] ?? 0) + ($sc['skpkd_kab_approved'] ?? 0) + ($sc['skpkd_prov_verif'] ?? 0) + ($sc['disalurkan'] ?? 0) + ($sc['dikonfirmasi'] ?? 0),
            'Verifikasi Kab Selesai'      => ($sc['skpkd_kab_approved'] ?? 0) + ($sc['skpkd_prov_verif'] ?? 0) + ($sc['disalurkan'] ?? 0) + ($sc['dikonfirmasi'] ?? 0),
            'Dana Disalurkan'             => ($sc['disalurkan'] ?? 0) + ($sc['dikonfirmasi'] ?? 0),
            'Dikonfirmasi Kab'            => ($sc['dikonfirmasi'] ?? 0),
        ];
    }

    // ─── REKAP BKP ────────────────────────────────────────────

    public function get_rekap_bkp($tahun, $kabkota_id = NULL, $bidang_id = NULL)
    {
        $this->db
            ->select('b.kode_bkp, b.uraian_bkp, b.nilai as nilai_bkp,
                      k.nama as nama_kabkota, bid.nama as nama_bidang,
                      p.id as pekerjaan_id, p.status, p.nilai_kontrak,
                      p.jenis_penyaluran, p.nama_kegiatan_dok,
                      SUM(pd.nilai_transfer) as total_disalurkan,
                      COUNT(pd.id) as total_sp2d')
            ->from('ref_bkp b')
            ->join('ref_kabkota k',   'k.id = b.kabkota_id')
            ->join('ref_bidang bid',  'bid.id = b.bidang_id')
            ->join('trx_pekerjaan p', 'p.bkp_id = b.id', 'left')
            ->join('trx_tahapan_penyaluran t', 't.pekerjaan_id = p.id', 'left')
            ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
            ->where('b.tahun', $tahun)
            ->where('b.is_active', 1);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        if ($bidang_id)  $this->db->where('b.bidang_id',  $bidang_id);
        return $this->db->group_by('b.id')
            ->order_by('k.nama','ASC')->order_by('b.kode_bkp','ASC')
            ->get()->result();
    }

    public function get_rekap_summary($tahun, $kabkota_id = NULL)
    {
        $this->db->select('COUNT(b.id) as total_bkp,
                           SUM(b.nilai) as total_nilai_bkp,
                           SUM(p.nilai_kontrak) as total_kontrak,
                           COUNT(p.id) as total_pekerjaan,
                           SUM(pd.nilai_transfer) as total_disalurkan')
            ->from('ref_bkp b')
            ->join('trx_pekerjaan p', 'p.bkp_id = b.id', 'left')
            ->join('trx_tahapan_penyaluran t', 't.pekerjaan_id = p.id', 'left')
            ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
            ->where('b.tahun', $tahun)->where('b.is_active', 1);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        return $this->db->get()->row();
    }

    // ─── LAPORAN AKHIR KAB/KOTA ───────────────────────────────

    /**
     * Data lengkap BKP beserta pekerjaan, tahapan, penyaluran, dan capaian
     * untuk laporan akhir per kabupaten/kota.
     */
    public function get_laporan_akhir_kab($tahun, $kabkota_id)
    {
        $bkp_list = $this->db
            ->select('b.id as bkp_id, b.kode_bkp, b.uraian_bkp, b.nilai as nilai_bkp,
                      k.nama as nama_kabkota, bid.nama as nama_bidang, bid.kode as kode_bidang,
                      p.id as pekerjaan_id, p.status, p.jenis_penyaluran,
                      p.nama_kegiatan_dok, p.nilai_kontrak, p.nama_penyedia,
                      p.no_dok_pekerjaan, p.tgl_dok_pekerjaan,
                      p.no_spmk, p.tgl_spmk, p.no_bast, p.tgl_bast,
                      p.lokasi_deskripsi, p.latitude, p.longitude')
            ->from('ref_bkp b')
            ->join('ref_kabkota k',   'k.id = b.kabkota_id')
            ->join('ref_bidang bid',  'bid.id = b.bidang_id')
            ->join('trx_pekerjaan p', 'p.bkp_id = b.id', 'left')
            ->where('b.tahun',      $tahun)
            ->where('b.kabkota_id', $kabkota_id)
            ->where('b.is_active',  1)
            ->order_by('bid.nama', 'ASC')
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();

        // Untuk setiap BKP, ambil detail tahapan + penyaluran + capaian
        foreach ($bkp_list as &$bkp) {
            if (!$bkp->pekerjaan_id) {
                $bkp->tahapan = [];
                continue;
            }

            $tahapan = $this->db
                ->select('t.id, t.kode_tahap, t.urutan, t.persen_nilai, t.status as status_tahapan,
                          pd.no_sp2d, pd.tgl_sp2d, pd.nilai_transfer, pd.status_transfer,
                          c.persen_fisik, c.tgl_realisasi, c.no_ba_kemajuan,
                          c.tgl_ba_kemajuan, c.keterangan as keterangan_capaian, c.foto_path')
                ->from('trx_tahapan_penyaluran t')
                ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
                ->join('trx_capaian_output c',   'c.tahapan_id = t.id', 'left')
                ->where('t.pekerjaan_id', $bkp->pekerjaan_id)
                ->order_by('t.urutan', 'ASC')
                ->get()->result();

            $bkp->tahapan        = $tahapan;
            $bkp->total_disalurkan = array_sum(array_column((array)$tahapan, 'nilai_transfer'));
        }
        unset($bkp);

        return $bkp_list;
    }

    /** Summary statistik untuk header laporan akhir kab */
    public function get_summary_laporan_kab($tahun, $kabkota_id)
    {
        $total_bkp = $this->db->where(['tahun'=>$tahun,'kabkota_id'=>$kabkota_id,'is_active'=>1])
                              ->count_all_results('ref_bkp');

        $agg = $this->db
            ->select('SUM(b.nilai) as total_nilai_bkp,
                      SUM(p.nilai_kontrak) as total_kontrak,
                      SUM(pd.nilai_transfer) as total_disalurkan,
                      COUNT(DISTINCT p.id) as total_pekerjaan')
            ->from('ref_bkp b')
            ->join('trx_pekerjaan p', 'p.bkp_id = b.id', 'left')
            ->join('trx_tahapan_penyaluran t', 't.pekerjaan_id = p.id', 'left')
            ->join('trx_penyaluran_dana pd', 'pd.tahapan_id = t.id', 'left')
            ->where('b.tahun', $tahun)
            ->where('b.kabkota_id', $kabkota_id)
            ->where('b.is_active', 1)
            ->get()->row();

        return (object)[
            'total_bkp'        => $total_bkp,
            'total_nilai_bkp'  => $agg->total_nilai_bkp   ?? 0,
            'total_kontrak'    => $agg->total_kontrak       ?? 0,
            'total_disalurkan' => $agg->total_disalurkan    ?? 0,
            'total_pekerjaan'  => $agg->total_pekerjaan     ?? 0,
        ];
    }
}
