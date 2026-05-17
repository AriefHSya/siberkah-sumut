<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan Controller — Sprint 6
 * Rekap BKP, rekap penyaluran, export data
 */
class Laporan extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('laporan.view');
        $this->load->model(['Laporan_model','Parameter_model','Verifikasi_prov_model']);
        $this->data['active_menu'] = 'laporan';
    }

    public function index()
    {
        redirect('laporan/rekap-bkp');
    }

    // ─── REKAP BKP ────────────────────────────────────────────

    public function rekap_bkp()
    {
        $this->requirePerm('laporan.cetak_rekap_bkp');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        $bidang_id  = $this->input->get('bidang_id');

        if (!$this->rbac->isProvinsi() && $this->kabkota_id) {
            $kabkota_id = $this->kabkota_id;
        }

        $list    = $this->Laporan_model->get_rekap_bkp($tahun, $kabkota_id, $bidang_id);
        $summary = $this->Laporan_model->get_rekap_summary($tahun, $kabkota_id);

        $this->render('laporan/rekap_bkp', array_merge($this->data, [
            'title'        => 'Rekap Data BKP — SIBERKAH SUMUT',
            'tahun'        => $tahun,
            'kabkota_id'   => $kabkota_id,
            'bidang_id'    => $bidang_id,
            'list'         => $list,
            'summary'      => $summary,
            'tahun_list'   => $this->Parameter_model->get_all_tahun(),
            'kabkota_list' => $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [],
            'bidang_list'  => $this->Parameter_model->get_bidang(),
        ]));
    }

    public function cetak_rekap_bkp()
    {
        $this->requirePerm('laporan.cetak_rekap_bkp');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        $bidang_id  = $this->input->get('bidang_id');

        if (!$this->rbac->isProvinsi() && $this->kabkota_id) {
            $kabkota_id = $this->kabkota_id;
        }

        $list    = $this->Laporan_model->get_rekap_bkp($tahun, $kabkota_id, $bidang_id);
        $summary = $this->Laporan_model->get_rekap_summary($tahun, $kabkota_id);
        $kabkota = $kabkota_id
            ? $this->db->get_where('ref_kabkota',['id'=>$kabkota_id])->row() : NULL;

        $this->render_plain('laporan/cetak_rekap_bkp', [
            'list'      => $list,
            'summary'   => $summary,
            'tahun'     => $tahun,
            'kabkota'   => $kabkota,
            'tgl_cetak' => tgl_indo(date('Y-m-d')),
            'nama_user' => $this->data['current_user']->nama,
        ]);
    }

    // ─── REKAP PENYALURAN ─────────────────────────────────────

    public function rekap_penyaluran()
    {
        $this->requirePerm('laporan.cetak_rekap_penyaluran');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');

        if (!$this->rbac->isProvinsi() && $this->kabkota_id) {
            $kabkota_id = $this->kabkota_id;
        }

        $list  = $this->Verifikasi_prov_model->get_daftar_sp2d($tahun, $kabkota_id);
        $rekap = $this->Verifikasi_prov_model->rekap_penyaluran($tahun);

        $this->render('laporan/rekap_penyaluran', array_merge($this->data, [
            'title'        => 'Rekap Penyaluran Dana — SIBERKAH SUMUT',
            'tahun'        => $tahun,
            'kabkota_id'   => $kabkota_id,
            'list'         => $list,
            'rekap'        => $rekap,
            'tahun_list'   => $this->Parameter_model->get_all_tahun(),
            'kabkota_list' => $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [],
        ]));
    }

    // ─── EXPORT CSV ───────────────────────────────────────────

    public function export_bkp()
    {
        $this->requirePerm('laporan.export');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        if (!$this->rbac->isProvinsi() && $this->kabkota_id) {
            $kabkota_id = $this->kabkota_id;
        }
        $list = $this->Laporan_model->get_rekap_bkp($tahun, $kabkota_id);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap_bkp_'.$tahun.'.csv"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF"; // BOM UTF-8 agar Excel terbaca

        $f = fopen('php://output', 'w');
        fputcsv($f, ['Kode BKP','Kab/Kota','Bidang','Uraian BKP',
                     'Nilai BKP','Status','Jenis Penyaluran',
                     'Nilai Kontrak','Total Disalurkan','Total SP2D'], ';');
        foreach ($list as $row) {
            fputcsv($f, [
                $row->kode_bkp,
                $row->nama_kabkota,
                $row->nama_bidang,
                $row->uraian_bkp,
                $row->nilai_bkp,
                $row->status ?? 'Belum Ada Pekerjaan',
                $row->jenis_penyaluran ?? '—',
                $row->nilai_kontrak ?? 0,
                $row->total_disalurkan ?? 0,
                $row->total_sp2d ?? 0,
            ], ';');
        }
        fclose($f);
        exit;
    }

    public function export_penyaluran()
    {
        $this->requirePerm('laporan.export');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        $list = $this->Verifikasi_prov_model->get_daftar_sp2d($tahun, $kabkota_id);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap_penyaluran_'.$tahun.'.csv"');
        echo "\xEF\xBB\xBF";

        $f = fopen('php://output', 'w');
        fputcsv($f, ['No. SP2D','Tgl SP2D','Kab/Kota','Kode BKP','Nama Kegiatan',
                     'Tahapan','Jenis','Nilai Transfer','Status Transfer'], ';');
        foreach ($list as $row) {
            fputcsv($f, [
                $row->no_sp2d, $row->tgl_sp2d,
                $row->nama_kabkota, $row->kode_bkp,
                $row->nama_kegiatan_dok ?: $row->uraian_bkp,
                $row->label_tahap, $row->jenis_penyaluran,
                $row->nilai_transfer, $row->status_transfer,
            ], ';');
        }
        fclose($f);
        exit;
    }
}
