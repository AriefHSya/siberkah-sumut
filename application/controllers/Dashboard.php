<?php
/**
 * Dashboard.php — Controller Dashboard SIBERKAH SUMUT
 *
 * Menampilkan ringkasan data dan antrian aksi sesuai role user.
 * Konten berbeda per role: admin_provinsi melihat seluruh data,
 * role kabkota melihat data kabkota mereka saja.
 *
 * ROUTES:
 *   GET  /dashboard                → index()       — halaman utama
 *   GET  /dashboard/pilih-tahun   → pilih_tahun() — form ganti tahun anggaran
 *   POST /dashboard/set-tahun     → set_tahun()   — simpan tahun ke session
 *
 * DATA YANG DITAMPILKAN:
 *   - Stats total BKP, nilai, progress penyaluran
 *   - Funnel status (draft → selesai)
 *   - Per bidang kegiatan
 *   - Per kab/kota (hanya role provinsi)
 *   - Antrian aksi — hal yang perlu dilakukan user saat ini
 *   - Alert deadline batas waktu (3 hari sebelum/sesudah)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('dashboard.view');
        $this->load->model(['Laporan_model']);
        $this->data['active_menu'] = 'dashboard';
    }

    public function pilih_tahun()
    {
        $data          = $this->data;
        $data['title'] = 'Pilih Tahun Anggaran';
        $this->render('dashboard/pilih_tahun', $data);
    }

    public function set_tahun()
    {
        $tahun = $this->input->post('tahun', TRUE);
        if ($tahun) $this->session->set_userdata('tahun_anggaran', $tahun);
        redirect('dashboard');
    }

    public function index()
    {
        $tahun = $this->tahun;
        $is_provinsi = $this->rbac->isProvinsi();
        $kabkota_id  = $this->kabkota_id;

        // Stats utama
        if ($is_provinsi) {
            $stats = $this->Laporan_model->get_stats_provinsi($tahun);
        } else {
            $stats = $this->Laporan_model->get_stats_kabkota($tahun, $kabkota_id);
        }

        // Funnel progress
        $funnel = $this->Laporan_model->get_funnel($tahun, $is_provinsi ? NULL : $kabkota_id);

        // Per bidang (untuk chart)
        $per_bidang = $this->Laporan_model->get_per_bidang($tahun, $is_provinsi ? NULL : $kabkota_id);

        // Per kab/kota (hanya provinsi)
        $per_kabkota = $is_provinsi ? $this->Laporan_model->get_per_kabkota($tahun) : [];

        // Batas waktu & alert
        $bw_list = $this->Parameter_model->get_batas_waktu($tahun);
        $alert_deadlines = array_filter($bw_list, function($d) {
            $sisa = (strtotime($d->batas_pengajuan) - time()) / 86400;
            return $sisa >= -3 && $sisa <= 14;
        });

        // Antrian aksi — hal yang perlu dilakukan user saat ini
        $antrian_aksi = $this->_get_antrian_aksi($tahun, $kabkota_id);

        $this->render('dashboard/index', array_merge($this->data, [
            'title'           => 'Dashboard — SIBERKAH SUMUT',
            'tahun'           => $tahun,
            'stats'           => $stats,
            'funnel'          => $funnel,
            'per_bidang'      => $per_bidang,
            'per_kabkota'     => $per_kabkota,
            'bw_list'         => $bw_list,
            'alert_deadlines' => $alert_deadlines,
            'antrian_aksi'    => $antrian_aksi,
            'is_provinsi'     => $is_provinsi,
        ]));
    }

    /**
     * JSON endpoint: data lokasi semua pekerjaan yang punya koordinat
     * Digunakan oleh peta cluster Leaflet di dashboard
     */
    public function peta_data()
    {
        $tahun       = $this->input->get('tahun') ?? $this->tahun;
        $is_provinsi = $this->rbac->isProvinsi();

        $this->db->select('p.id, p.latitude, p.longitude, p.lokasi_deskripsi, p.status, p.nilai_kontrak, b.kode_bkp, b.uraian_bkp, k.nama as nama_kabkota')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->join('ref_kabkota k', 'k.id = b.kabkota_id')
            ->where('b.tahun', $tahun)
            ->where('p.latitude IS NOT NULL', NULL, FALSE)
            ->where('p.longitude IS NOT NULL', NULL, FALSE)
            ->where('p.latitude !=', '')
            ->where('p.longitude !=', '');

        // Provinsi & pengawas melihat seluruh kab/kota; role kabkota dibatasi ke kab/kota sendiri
        if (!$is_provinsi && $this->role_kode !== 'pengawas') {
            $this->db->where('b.kabkota_id', (int)$this->kabkota_id);
        }

        $rows = $this->db->get()->result();

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'lat'          => (float)$r->latitude,
                'lng'          => (float)$r->longitude,
                'id'           => (int)$r->id,
                'kode'         => $r->kode_bkp,
                'uraian'       => $r->uraian_bkp,
                'kab'          => $r->nama_kabkota,
                'status'       => $r->status,
                'status_label' => strip_tags(badge_status($r->status)),
                'nilai'        => (int)$r->nilai_kontrak,
                'lokasi'       => $r->lokasi_deskripsi,
            ];
        }

        $this->json($data);
    }

    /**
     * Kumpulkan daftar aksi mendesak sesuai role user saat ini.
     * Digunakan untuk blok "Perlu Dilakukan" di dashboard.
     * Return array item: [icon, label, url, warna]
     */
    private function _get_antrian_aksi($tahun, $kabkota_id)
    {
        $aksi = [];
        $role = $this->role_kode;

        if ($role === 'opd_teknis') {
            // Pekerjaan draft yang belum disubmit
            $n = $this->db->from('trx_pekerjaan p')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where(['b.tahun'=>$tahun,'p.status'=>'draft','b.kabkota_id'=>$kabkota_id,'p.created_by'=>$this->user_id])
                ->count_all_results();
            if ($n) $aksi[] = ['icon'=>'file-text','label'=>$n.' pekerjaan masih Draft — belum disubmit','url'=>'pekerjaan','warna'=>'kuning'];

            // Pekerjaan dikembalikan untuk revisi
            $n2 = $this->db->from('trx_pekerjaan p')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where('b.tahun',$tahun)->where('b.kabkota_id',$kabkota_id)
                ->where_in('p.status',['inspektorat_revisi','skpkd_kab_revisi'])
                ->count_all_results();
            if ($n2) $aksi[] = ['icon'=>'alert-triangle','label'=>$n2.' pekerjaan perlu perbaikan','url'=>'pekerjaan?status=inspektorat_revisi','warna'=>'oranye'];
        }

        if ($role === 'inspektorat') {
            $n = $this->db->from('trx_tahapan_penyaluran t')
                ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where(['b.tahun'=>$tahun,'b.kabkota_id'=>$kabkota_id,'t.status'=>'opd_input'])
                ->count_all_results();
            if ($n) $aksi[] = ['icon'=>'clipboard-check','label'=>$n.' pengajuan menunggu reviu','url'=>'reviu','warna'=>'biru'];
        }

        if ($role === 'skpkd_kabkota') {
            // Menunggu verifikasi kab
            $n = $this->db->from('trx_tahapan_penyaluran t')
                ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where(['b.tahun'=>$tahun,'b.kabkota_id'=>$kabkota_id])
                ->where_in('t.status',['inspektorat_approved','skpkd_kab_verif'])
                ->count_all_results();
            if ($n) $aksi[] = ['icon'=>'shield-check','label'=>$n.' tahapan menunggu verifikasi','url'=>'verifikasi/kab','warna'=>'biru'];

            // Dana disalurkan, belum dikonfirmasi
            $n2 = $this->db->from('trx_tahapan_penyaluran t')
                ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where(['b.tahun'=>$tahun,'b.kabkota_id'=>$kabkota_id,'t.status'=>'disalurkan'])
                ->count_all_results();
            if ($n2) $aksi[] = ['icon'=>'cash','label'=>$n2.' dana disalurkan — konfirmasi penerimaan RKUD','url'=>'penyaluran-kab','warna'=>'hijau'];
        }

        if (in_array($role, ['superadmin','admin_provinsi'])) {
            // Menunggu verifikasi provinsi
            $n = $this->db->from('trx_tahapan_penyaluran t')
                ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->where('b.tahun',$tahun)
                ->where_in('t.status',['skpkd_kab_approved','skpkd_prov_verif'])
                ->count_all_results();
            if ($n) $aksi[] = ['icon'=>'shield-check','label'=>$n.' permohonan menunggu verifikasi Provinsi','url'=>'verifikasi/prov','warna'=>'biru'];

            // Sudah diverifikasi, belum ada SP2D
            $n2 = $this->db->from('trx_verifikasi_skpkd_prov vp')
                ->join('trx_tahapan_penyaluran t','t.id=vp.tahapan_id')
                ->join('trx_pekerjaan p','p.id=t.pekerjaan_id')
                ->join('ref_bkp b','b.id=p.bkp_id')
                ->join('trx_penyaluran_dana pd','pd.tahapan_id=t.id','left')
                ->where(['b.tahun'=>$tahun,'vp.hasil_verifikasi'=>'disetujui'])
                ->where('pd.id IS NULL',NULL,FALSE)
                ->count_all_results();
            if ($n2) $aksi[] = ['icon'=>'file-invoice','label'=>$n2.' permohonan sudah disetujui — input SP2D','url'=>'verifikasi/prov?status=skpkd_prov_verif','warna'=>'kuning'];
        }

        return $aksi;
    }
}
