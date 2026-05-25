<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan.php — Controller Laporan & Rekap Data
 *
 * Menyediakan laporan rekap BKP, rekap penyaluran dana, dan
 * laporan akhir kab/kota. Mendukung export CSV dan cetak PDF.
 *
 * ROUTES:
 *   GET /laporan/rekap-bkp             → rekap_bkp()             — rekap semua BKP per kab/bidang
 *   GET /laporan/rekap-bkp/cetak       → cetak_rekap_bkp()       — cetak versi print
 *   GET /laporan/rekap-penyaluran      → rekap_penyaluran()      — rekap realisasi penyaluran dana
 *   GET /laporan/export/bkp            → export_bkp()            — download CSV rekap BKP
 *   GET /laporan/export/penyaluran     → export_penyaluran()     — download CSV penyaluran
 *   GET /laporan/akhir/{kabkota_id}    → laporan_akhir_kab()     — laporan akhir per kab/kota
 *   GET /laporan/akhir/{id}/cetak      → cetak_laporan_akhir_kab() — cetak laporan akhir
 *
 * AKSES: Semua role yang punya permission 'laporan.view'
 *   Admin provinsi melihat semua kab/kota; role kabkota hanya kabkota sendiri
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

        // Ambil data pejabat kepala BKAD Kab/Kota jika request dari role kab/kota
        $pejabat_ttd = NULL;
        if (!$this->rbac->isProvinsi() && $kabkota_id) {
            $pejabat_ttd = $this->db->get_where('ref_pemda_pejabat', [
                'kabkota_id' => $kabkota_id,
                'tahun'      => $tahun,
                'jenis'      => 'kepala_bkad',
            ])->row();
        }

        $this->render_plain('laporan/cetak_rekap_bkp', [
            'list'        => $list,
            'summary'     => $summary,
            'tahun'       => $tahun,
            'kabkota'     => $kabkota,
            'pejabat_ttd' => $pejabat_ttd,
            'is_provinsi' => $this->rbac->isProvinsi(),
            'tgl_cetak'   => tgl_indo(date('Y-m-d')),
            'nama_user'   => $this->data['current_user']->nama,
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

    // ─── EXPORT XLSX ─────────────────────────────────────────

    public function export_bkp_xlsx()
    {
        $this->requirePerm('laporan.export');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        if (!$this->rbac->isProvinsi() && $this->kabkota_id) {
            $kabkota_id = $this->kabkota_id;
        }
        $list = $this->Laporan_model->get_rekap_bkp($tahun, $kabkota_id);

        require_once APPPATH . 'libraries/XlsxWriter.php';
        $headers = [
            ['label'=>'Kode BKP',          'format'=>'string'],
            ['label'=>'Kab/Kota',           'format'=>'string'],
            ['label'=>'Bidang',             'format'=>'string'],
            ['label'=>'Uraian BKP',         'format'=>'string'],
            ['label'=>'Nilai BKP (Rp)',     'format'=>'rupiah'],
            ['label'=>'Status',             'format'=>'string'],
            ['label'=>'Jenis Penyaluran',   'format'=>'string'],
            ['label'=>'Nilai Kontrak (Rp)', 'format'=>'rupiah'],
            ['label'=>'Disalurkan (Rp)',    'format'=>'rupiah'],
            ['label'=>'Total SP2D',         'format'=>'number'],
        ];
        $rows = [];
        foreach ($list as $r) {
            $rows[] = [
                $r->kode_bkp,
                $r->nama_kabkota,
                $r->nama_bidang,
                $r->uraian_bkp,
                $r->nilai_bkp,
                $r->status ?? 'Belum Ada Pekerjaan',
                $r->jenis_penyaluran ?? '—',
                $r->nilai_kontrak ?? 0,
                $r->total_disalurkan ?? 0,
                $r->total_sp2d ?? 0,
            ];
        }
        $writer = new XlsxWriter();
        $writer->writeSheet('Rekap BKP TA '.$tahun, $rows, $headers);
        $writer->download('rekap_bkp_'.$tahun.'.xlsx');
    }

    public function export_penyaluran_xlsx()
    {
        $this->requirePerm('laporan.export');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        $list = $this->Verifikasi_prov_model->get_daftar_sp2d($tahun, $kabkota_id);

        require_once APPPATH . 'libraries/XlsxWriter.php';
        $headers = [
            ['label'=>'No. SP2D',          'format'=>'string'],
            ['label'=>'Tgl SP2D',          'format'=>'string'],
            ['label'=>'Kab/Kota',          'format'=>'string'],
            ['label'=>'Kode BKP',          'format'=>'string'],
            ['label'=>'Nama Kegiatan',     'format'=>'string'],
            ['label'=>'Tahapan',           'format'=>'string'],
            ['label'=>'Jenis',             'format'=>'string'],
            ['label'=>'Nilai Transfer (Rp)','format'=>'rupiah'],
            ['label'=>'Status Transfer',   'format'=>'string'],
        ];
        $rows = [];
        foreach ($list as $r) {
            $rows[] = [
                $r->no_sp2d,
                $r->tgl_sp2d,
                $r->nama_kabkota,
                $r->kode_bkp,
                $r->nama_kegiatan_dok ?: $r->uraian_bkp,
                $r->label_tahap,
                $r->jenis_penyaluran,
                $r->nilai_transfer,
                $r->status_transfer,
            ];
        }
        $writer2 = new XlsxWriter();
        $writer2->writeSheet('Rekap Penyaluran TA '.$tahun, $rows, $headers);
        $writer2->download('rekap_penyaluran_'.$tahun.'.xlsx');
    }

    // ─── LAPORAN AKHIR KAB/KOTA ──────────────────────────────

    public function laporan_akhir_kab()
    {
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');

        // Kab/Kota user hanya bisa lihat miliknya sendiri
        if ($this->kabkota_id && !$this->rbac->isProvinsi()) {
            $kabkota_id = $this->kabkota_id;
        }

        $kabkota_list = $this->Parameter_model->get_kabkota();
        $kabkota      = $kabkota_id
            ? $this->db->get_where('ref_kabkota', ['id' => $kabkota_id])->row()
            : NULL;

        // Data laporan hanya dimuat jika kabkota sudah dipilih
        $data_laporan = NULL;
        $summary      = NULL;
        $pejabat      = [];
        if ($kabkota_id) {
            $data_laporan = $this->Laporan_model->get_laporan_akhir_kab($tahun, $kabkota_id);
            $summary      = $this->Laporan_model->get_summary_laporan_kab($tahun, $kabkota_id);
            $pejabat_rows = $this->db->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
                                     ->get('ref_pemda_pejabat')->result();
            foreach ($pejabat_rows as $p) $pejabat[$p->jenis_jabatan] = $p;
        }

        $this->render('laporan/laporan_akhir_kab', array_merge($this->data, [
            'title'        => 'Laporan Akhir Kab/Kota — SIBERKAH SUMUT',
            'tahun'        => $tahun,
            'kabkota_id'   => $kabkota_id,
            'kabkota'      => $kabkota,
            'kabkota_list' => $kabkota_list,
            'data_laporan' => $data_laporan,
            'summary'      => $summary,
            'pejabat'      => $pejabat,
        ]));
    }

    public function cetak_laporan_akhir_kab()
    {
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = (int)$this->input->get('kabkota_id');

        if ($this->kabkota_id && !$this->rbac->isProvinsi()) {
            $kabkota_id = $this->kabkota_id;
        }
        if (!$kabkota_id) { redirect('laporan/laporan-akhir-kab'); return; }

        $kabkota      = $this->db->get_where('ref_kabkota', ['id'=>$kabkota_id])->row();
        $data_laporan = $this->Laporan_model->get_laporan_akhir_kab($tahun, $kabkota_id);
        $summary      = $this->Laporan_model->get_summary_laporan_kab($tahun, $kabkota_id);
        $pejabat_rows = $this->db->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
                                 ->get('ref_pemda_pejabat')->result();
        $pejabat = [];
        foreach ($pejabat_rows as $p) $pejabat[$p->jenis_jabatan] = $p;

        $this->render_plain('laporan/cetak_laporan_akhir_kab', [
            'tahun'        => $tahun,
            'kabkota'      => $kabkota,
            'data_laporan' => $data_laporan,
            'summary'      => $summary,
            'pejabat'      => $pejabat,
            'tgl_cetak'    => date('Y-m-d'),
            'user_nama'    => $this->data['current_user']->nama,
        ]);
    }
}
