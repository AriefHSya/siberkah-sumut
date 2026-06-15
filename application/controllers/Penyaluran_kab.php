<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyaluran_kab.php — Penyaluran Dana untuk SKPKD Kab/Kota
 *
 * Menampilkan daftar permohonan pencairan beserta data SP2D dari Admin Provinsi,
 * dan memungkinkan SKPKD Kab/Kota melakukan konfirmasi penerimaan dana di RKUD.
 *
 * ALUR:
 *   Admin Provinsi input SP2D (verifikasi/prov)
 *   → SKPKD Kab/Kota lihat di menu Penyaluran → konfirmasi RKUD (kode transaksi, nilai, tanggal)
 *   → Tahapan status → dikonfirmasi
 *   → Pekerjaan bertahap-1 → dikonfirmasi_tahap1 (muncul di menu Capaian)
 *   → Pekerjaan sekaligus/khusus/bertahap-2 → selesai
 *
 * ROUTES:
 *   GET  /penyaluran-kab                     → index()
 *   POST /penyaluran-kab/konfirmasi/(:num)   → konfirmasi($pm_id)
 *
 * AKSES: skpkd_kabkota (dan provinsi untuk monitoring)
 */
class Penyaluran_kab extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('penyaluran_kab.view');
        $this->load->model(['Penyaluran_kab_model', 'Pekerjaan_model', 'Parameter_model']);
        $this->data['active_menu'] = 'penyaluran_kab';
    }

    // ─── INDEX ────────────────────────────────────────────────

    public function index()
    {
        if (!$this->rbac->isKabkota()) {
            $this->session->set_flashdata('error', 'Menu ini hanya untuk SKPKD Kab/Kota.');
            redirect('dashboard'); return;
        }

        $kabkota_id = (int)$this->kabkota_id;
        $tahun      = $this->tahun;
        $per_page   = 20;
        $page       = max(1, (int)$this->input->get('page'));

        $filters = [
            'q'           => $this->input->get('q'),
            'status_rkud' => $this->input->get('status_rkud'),
        ];

        $total  = $this->Penyaluran_kab_model->count_list($kabkota_id, $tahun, $filters);
        $offset = ($page - 1) * $per_page;
        $list   = $this->Penyaluran_kab_model->get_list($kabkota_id, $tahun, $filters, $per_page, $offset);
        $rekap  = $this->Penyaluran_kab_model->rekap($kabkota_id, $tahun);

        $this->render('penyaluran_kab/index', array_merge($this->data, [
            'title'   => 'Penyaluran Dana BKP — SIBERKAH SUMUT',
            'list'    => $list,
            'filters' => $filters,
            'rekap'   => $rekap,
            'tahun'   => $tahun,
            'paging'  => [
                'total'    => $total,
                'per_page' => $per_page,
                'page'     => $page,
                'base_url' => 'penyaluran-kab',
            ],
        ]));
    }

    // ─── KONFIRMASI PENYALURAN RKUD ───────────────────────────

    public function konfirmasi($pm_id)
    {
        $this->requirePerm('penyaluran_kab.konfirmasi');

        $pm = $this->Penyaluran_kab_model->get_by_id($pm_id);
        if (!$pm) { show_404(); return; }

        // Guard: hanya kabkota pemilik permohonan
        if ((int)$pm->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('penyaluran-kab'); return;
        }

        // SP2D dari Provinsi harus sudah ada
        if (empty($pm->no_sp2d)) {
            $this->session->set_flashdata('error',
                'SP2D dari Provinsi belum diinput. Belum dapat dikonfirmasi.');
            redirect('penyaluran-kab'); return;
        }

        // Cegah konfirmasi ganda
        if (!empty($pm->kode_transaksi_rkud)) {
            $this->session->set_flashdata('warning',
                'Permohonan ini sudah dikonfirmasi sebelumnya.');
            redirect('penyaluran-kab'); return;
        }

        // Validasi input
        $kode_transaksi = trim($this->input->post('kode_transaksi_rkud', TRUE));
        $tgl_rkud       = $this->input->post('tgl_rkud', TRUE);
        $nilai_rkud_raw = $this->input->post('nilai_rkud', TRUE);
        $nilai_rkud     = (int)str_replace(['.', ',', 'Rp', ' '], '', $nilai_rkud_raw);

        if (empty($kode_transaksi) || empty($tgl_rkud) || $nilai_rkud <= 0) {
            $this->session->set_flashdata('error',
                'Kode Transaksi RKUD, Tanggal, dan Nilai wajib diisi.');
            redirect('penyaluran-kab'); return;
        }

        // Simpan data konfirmasi RKUD ke trx_permohonan + tandai permohonan selesai
        // (efek: SKPKD Kab/Kota tidak lagi dapat membatalkan permohonan ini)
        $this->Penyaluran_kab_model->simpan_konfirmasi($pm_id, [
            'kode_transaksi_rkud' => $kode_transaksi,
            'nilai_rkud'          => $nilai_rkud,
            'tgl_rkud'            => $tgl_rkud,
            'status'              => 'selesai',
        ]);

        // Update status setiap tahapan → dikonfirmasi + pekerjaan sesuai jenis
        $items = $this->Penyaluran_kab_model->get_items($pm_id);
        foreach ($items as $item) {
            // Hanya update jika tahapan masih di status disalurkan
            if ($item->tahapan_status !== 'disalurkan') continue;

            $this->db->where('id', $item->tahapan_id)
                ->update('trx_tahapan_penyaluran', [
                    'status'     => 'dikonfirmasi',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Tentukan status pekerjaan berdasarkan jenis + tahap
            $is_tahap1 = ($item->jenis_penyaluran === 'bertahap'
                          && $item->kode_tahap === 'tahap_1');
            $status_pek = $is_tahap1 ? 'dikonfirmasi_tahap1' : 'selesai';

            $this->Pekerjaan_model->set_status(
                $item->pekerjaan_id,
                $status_pek,
                $this->user_id,
                'Dana diterima di RKUD. Kode Transaksi: ' . $kode_transaksi
            );
        }

        // Notifikasi Admin Provinsi
        $admin_prov = $this->db->select('u.id')
            ->from('users u')->join('roles r', 'r.id = u.role_id')
            ->where_in('r.kode', ['superadmin', 'admin_provinsi'])
            ->where('u.is_active', 1)->get()->result();
        foreach ($admin_prov as $au) {
            $this->Notifikasi_model->kirim(
                $au->id,
                'Dana BKP Dikonfirmasi Diterima',
                $pm->nama_kabkota . ' telah mengkonfirmasi penerimaan dana No. ' .
                $pm->no_sp2d . ' di RKUD. Kode Transaksi: ' . $kode_transaksi,
                'sukses',
                site_url('verifikasi/prov'),
                NULL
            );
        }

        $this->log_aktivitas('penyaluran_kab.konfirmasi',
            'Konfirmasi RKUD permohonan='.$pm_id.' kode='.$kode_transaksi);
        $this->session->set_flashdata('success',
            'Penerimaan dana berhasil dikonfirmasi. Kode Transaksi RKUD: <strong>' .
            htmlspecialchars($kode_transaksi) . '</strong>');
        redirect('penyaluran-kab');
    }
}
