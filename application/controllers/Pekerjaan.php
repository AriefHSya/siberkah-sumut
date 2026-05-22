<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pekerjaan.php — Controller Manajemen Pekerjaan BKP
 *
 * Menangani seluruh siklus data pekerjaan dari input OPD Teknis
 * hingga submit ke Inspektorat untuk reviu.
 *
 * ROUTES (lihat config/routes.php untuk mapping lengkap):
 *   GET  /pekerjaan                    → index()           — daftar pekerjaan (paginated + filter)
 *   GET  /pekerjaan/input              → input()           — form tambah pekerjaan baru
 *   POST /pekerjaan/simpan             → simpan()          — proses simpan pekerjaan baru
 *   GET  /pekerjaan/edit/{id}          → edit()            — form edit pekerjaan
 *   POST /pekerjaan/update/{id}        → update()          — proses update
 *   GET  /pekerjaan/detail/{id}        → detail()          — detail + timeline status
 *   POST /pekerjaan/submit/{id}        → submit()          — kirim ke Inspektorat
 *   POST /pekerjaan/upload-dok         → upload_dok()      — upload dokumen persyaratan
 *   POST /pekerjaan/hapus-dok          → hapus_dok()       — hapus dokumen
 *   GET  /pekerjaan/cetak/{id}         → cetak_permohonan() — cetak PDF permohonan
 *
 * AKSES:
 *   - Semua method memerlukan permission 'pekerjaan.view'
 *   - input/simpan memerlukan 'pekerjaan.input'
 *   - submit memerlukan 'pekerjaan.submit'
 *   - Role kabkota hanya bisa melihat pekerjaan kabkota mereka sendiri (guard IDOR)
 */
class Pekerjaan extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('pekerjaan.view');
        $this->load->model(['Pekerjaan_model', 'Parameter_model']);
        $this->data['active_menu'] = 'pekerjaan';
    }

    // ─── DAFTAR ───────────────────────────────────────────────

    public function index()
    {
        $tahun    = $this->tahun;
        $per_page = 20;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tahun'           => $tahun,
            'kabkota_id'      => $this->input->get('kabkota_id'),
            'status'          => $this->input->get('status'),
            'jenis_penyaluran'=> $this->input->get('jenis'),
            'q'               => $this->input->get('q'),
        ];

        // Paksa filter kabkota untuk semua role kabkota (skpkd, inspektorat, opd)
        // Gunakan -1 sebagai fallback jika kabkota_id tidak terset agar tidak tampil semua data
        if ($this->rbac->isKabkota()) {
            $filters['kabkota_id'] = (int)$this->kabkota_id ?: -1;
        }

        $total        = $this->Pekerjaan_model->count_filtered($filters);
        $offset       = ($page - 1) * $per_page;
        $list         = $this->Pekerjaan_model->get_all($filters, $per_page, $offset);
        $count_status = $this->Pekerjaan_model->count_by_status($tahun, $filters['kabkota_id']);
        $kabkota_list = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];

        $this->render('pekerjaan/index', array_merge($this->data, [
            'title'        => 'Daftar Pekerjaan — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'count_status' => $count_status,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'pekerjaan'],
        ]));
    }

    // ─── INPUT BARU ───────────────────────────────────────────

    public function input()
    {
        $this->requirePerm('pekerjaan.input');
        $tahun      = $this->tahun;
        $kabkota_id = $this->kabkota_id;

        // BKP yang BELUM punya pekerjaan untuk kabkota ini
        $bkp_tersedia = $this->db
            ->select('b.*, k.nama as nama_kabkota, bid.nama as nama_bidang')
            ->from('ref_bkp b')
            ->join('ref_kabkota k',  'k.id = b.kabkota_id')
            ->join('ref_bidang bid', 'bid.id = b.bidang_id')
            ->where('b.tahun',      $tahun)
            ->where('b.is_active',  1)
            ->where('b.kabkota_id', $kabkota_id)
            ->where("b.id NOT IN (SELECT bkp_id FROM trx_pekerjaan)", NULL, FALSE)
            ->order_by('b.kode_bkp', 'ASC')
            ->get()->result();

        // Semua BKP milik kab/kota ini (untuk dropdown, termasuk yang sudah ada)
        $bkp_semua = $this->db
            ->select('b.*, k.nama as nama_kabkota')
            ->from('ref_bkp b')
            ->join('ref_kabkota k', 'k.id = b.kabkota_id')
            ->where(['b.tahun'=>$tahun,'b.is_active'=>1,'b.kabkota_id'=>$kabkota_id])
            ->order_by('b.kode_bkp','ASC')->get()->result();

        // Dokumen perda/perkada untuk dropdown (jenis sesuai pemda.php)
        $dokumen_perda = $this->db
            ->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
            ->where_in('jenis',['perda_apbd','perda_p_apbd'])
            ->get('ref_pemda_dokumen')->result();
        $dokumen_perkada = $this->db
            ->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
            ->where_in('jenis',['perkada_apbd','perkada_pergeseran','perkada_p_apbd'])
            ->get('ref_pemda_dokumen')->result();

        // Batas waktu TA ini (untuk info di form)
        $batas_waktu = $this->Parameter_model->get_batas_waktu($tahun);

        $this->render('pekerjaan/form', array_merge($this->data, [
            'title'           => 'Input Pekerjaan Baru',
            'edit'            => FALSE,
            'pekerjaan'       => NULL,
            'bkp_tersedia'    => $bkp_tersedia,
            'bkp_semua'       => $bkp_semua,
            'dokumen_perda'   => $dokumen_perda,
            'dokumen_perkada' => $dokumen_perkada,
            'batas_waktu'     => $batas_waktu,
            'tahun'           => $tahun,
        ]));
    }

    public function simpan()
    {
        $this->requirePerm('pekerjaan.input');

        $bkp_id = $this->input->post('bkp_id', TRUE);
        if (!$bkp_id) {
            $this->session->set_flashdata('error', 'BKP wajib dipilih.');
            redirect('pekerjaan/input'); return;
        }

        // Cek duplikat
        if ($this->Pekerjaan_model->bkp_sudah_ada($bkp_id)) {
            $this->session->set_flashdata('error', 'BKP ini sudah memiliki data pekerjaan.');
            redirect('pekerjaan/input'); return;
        }

        $nilai_kontrak = (int) str_replace(['.','Rp',' ',','], '', $this->input->post('nilai_kontrak'));
        $nilai_pendukung = (int) str_replace(['.','Rp',' ',','], '', $this->input->post('nilai_belanja_pendukung'));
        $jenis = $this->input->post('jenis_penyaluran', TRUE);

        // Validasi: bertahap wajib nilai_kontrak > 200jt
        if ($jenis === 'bertahap' && $nilai_kontrak <= 200000000) {
            $this->session->set_flashdata('error', 'Jenis Bertahap memerlukan nilai kontrak > Rp 200.000.000.');
            redirect('pekerjaan/input'); return;
        }

        // Validasi: pendukung maks 5% dari nilai BKP (untuk bertahap)
        if ($jenis === 'bertahap') {
            $bkp = $this->db->get_where('ref_bkp', ['id'=>$bkp_id])->row();
            if ($bkp && $nilai_pendukung > ($bkp->nilai * 0.05)) {
                $this->session->set_flashdata('error',
                    'Belanja pendukung tidak boleh melebihi 5% dari nilai BKP (' . rupiah($bkp->nilai * 0.05) . ').');
                redirect('pekerjaan/input'); return;
            }
        }

        $lat = $this->input->post('latitude',  TRUE);
        $lng = $this->input->post('longitude', TRUE);

        $data = [
            'bkp_id'                  => $bkp_id,
            'jenis_penyaluran'        => $jenis,
            'nama_kegiatan_dok'       => $this->input->post('nama_kegiatan_dok', TRUE),
            'volume_satuan'           => $this->input->post('volume_satuan', TRUE),
            'metode_pelaksanaan'      => $this->input->post('metode_pelaksanaan', TRUE),
            'jenis_pekerjaan'         => $this->input->post('jenis_pekerjaan', TRUE),
            'nama_penyedia'           => $this->input->post('nama_penyedia', TRUE),
            'alamat_penyedia'         => $this->input->post('alamat_penyedia', TRUE),
            'no_dok_pekerjaan'        => $this->input->post('no_dok_pekerjaan', TRUE),
            'tgl_dok_pekerjaan'       => $this->input->post('tgl_dok_pekerjaan', TRUE) ?: NULL,
            'no_spmk'                 => $this->input->post('no_spmk', TRUE),
            'tgl_spmk'                => $this->input->post('tgl_spmk', TRUE) ?: NULL,
            'no_bast'                 => $this->input->post('no_bast', TRUE),
            'tgl_bast'                => $this->input->post('tgl_bast', TRUE) ?: NULL,
            'jangka_waktu_hari'       => (int)$this->input->post('jangka_waktu_hari') ?: NULL,
            'nilai_kontrak'           => $nilai_kontrak,
            'nilai_belanja_pendukung' => $nilai_pendukung,
            'belanja_pendukung_json'  => $this->input->post('belanja_pendukung_json', TRUE) ?: '[]',
            'lokasi_deskripsi'        => $this->input->post('lokasi_deskripsi', TRUE),
            'latitude'                => is_numeric($lat) ? $lat : NULL,
            'longitude'               => is_numeric($lng) ? $lng : NULL,
            'ref_perda_id'            => $this->input->post('ref_perda_id', TRUE) ?: NULL,
            'ref_perkada_id'          => $this->input->post('ref_perkada_id', TRUE) ?: NULL,
            'status'                  => 'draft',
            'created_by'              => $this->user_id,
        ];

        $pekerjaan_id = $this->Pekerjaan_model->insert($data);

        // Status history awal
        $this->db->insert('trx_status_history', [
            'pekerjaan_id' => $pekerjaan_id,
            'status_lama'  => NULL,
            'status_baru'  => 'draft',
            'catatan'      => 'Pekerjaan dibuat oleh OPD Teknis',
            'user_id'      => $this->user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->log_aktivitas('pekerjaan.input', 'Input pekerjaan baru id='.$pekerjaan_id);
        $this->session->set_flashdata('success', 'Data pekerjaan berhasil disimpan sebagai Draft.');
        redirect('pekerjaan/detail/' . $pekerjaan_id); return;
    }

    // ─── EDIT ─────────────────────────────────────────────────

    public function edit($id)
    {
        $this->requirePerm('pekerjaan.edit');
        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        // Hanya bisa edit jika masih draft atau dikembalikan untuk revisi
        if (!in_array($pekerjaan->status, ['draft','inspektorat_revisi','skpkd_kab_revisi'])) {
            $this->session->set_flashdata('error', 'Pekerjaan tidak dapat diedit pada status ini.');
            redirect('pekerjaan/detail/'.$id); return;
        }

        // Hanya OPD pemilik atau admin yang bisa edit
        if (!$this->rbac->isProvinsi() && $pekerjaan->created_by != $this->user_id) {
            $this->session->set_flashdata('error', 'Anda tidak berwenang mengedit pekerjaan ini.');
            redirect('pekerjaan/detail/'.$id); return;
        }

        $kabkota_id = $pekerjaan->kabkota_id;
        $tahun      = $pekerjaan->tahun;

        $dokumen_perda = $this->db
            ->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
            ->where_in('jenis',['perda_apbd','perda_p_apbd'])
            ->get('ref_pemda_dokumen')->result();
        $dokumen_perkada = $this->db
            ->where(['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])
            ->where_in('jenis',['perkada_apbd','perkada_pergeseran','perkada_p_apbd'])
            ->get('ref_pemda_dokumen')->result();

        $this->render('pekerjaan/form', array_merge($this->data, [
            'title'           => 'Edit Pekerjaan',
            'edit'            => TRUE,
            'pekerjaan'       => $pekerjaan,
            'bkp_tersedia'    => [],
            'bkp_semua'       => [],
            'dokumen_perda'   => $dokumen_perda,
            'dokumen_perkada' => $dokumen_perkada,
            'batas_waktu'     => $this->Parameter_model->get_batas_waktu($tahun),
            'tahun'           => $tahun,
        ]));
    }

    public function update($id)
    {
        $this->requirePerm('pekerjaan.edit');
        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        $nilai_kontrak   = (int) str_replace(['.','Rp',' ',','], '', $this->input->post('nilai_kontrak'));
        $nilai_pendukung = (int) str_replace(['.','Rp',' ',','], '', $this->input->post('nilai_belanja_pendukung'));
        $lat = $this->input->post('latitude',  TRUE);
        $lng = $this->input->post('longitude', TRUE);

        $data = [
            'jenis_penyaluran'        => $this->input->post('jenis_penyaluran', TRUE),
            'nama_kegiatan_dok'       => $this->input->post('nama_kegiatan_dok', TRUE),
            'volume_satuan'           => $this->input->post('volume_satuan', TRUE),
            'metode_pelaksanaan'      => $this->input->post('metode_pelaksanaan', TRUE),
            'jenis_pekerjaan'         => $this->input->post('jenis_pekerjaan', TRUE),
            'nama_penyedia'           => $this->input->post('nama_penyedia', TRUE),
            'alamat_penyedia'         => $this->input->post('alamat_penyedia', TRUE),
            'no_dok_pekerjaan'        => $this->input->post('no_dok_pekerjaan', TRUE),
            'tgl_dok_pekerjaan'       => $this->input->post('tgl_dok_pekerjaan', TRUE) ?: NULL,
            'no_spmk'                 => $this->input->post('no_spmk', TRUE),
            'tgl_spmk'                => $this->input->post('tgl_spmk', TRUE) ?: NULL,
            'no_bast'                 => $this->input->post('no_bast', TRUE),
            'tgl_bast'                => $this->input->post('tgl_bast', TRUE) ?: NULL,
            'jangka_waktu_hari'       => (int)$this->input->post('jangka_waktu_hari') ?: NULL,
            'nilai_kontrak'           => $nilai_kontrak,
            'nilai_belanja_pendukung' => $nilai_pendukung,
            'belanja_pendukung_json'  => $this->input->post('belanja_pendukung_json', TRUE) ?: '[]',
            'lokasi_deskripsi'        => $this->input->post('lokasi_deskripsi', TRUE),
            'latitude'                => is_numeric($lat) ? $lat : NULL,
            'longitude'               => is_numeric($lng) ? $lng : NULL,
            'ref_perda_id'            => $this->input->post('ref_perda_id',  TRUE) ?: NULL,
            'ref_perkada_id'          => $this->input->post('ref_perkada_id', TRUE) ?: NULL,
        ];

        $this->Pekerjaan_model->update($id, $data, $this->user_id);
        $this->log_aktivitas('pekerjaan.edit', 'Edit pekerjaan id='.$id);
        $this->session->set_flashdata('success', 'Data pekerjaan berhasil diperbarui.');
        redirect('pekerjaan/detail/'.$id);
    }

    // ─── DETAIL ───────────────────────────────────────────────

    public function detail($id)
    {
        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        // Kabkota role hanya bisa lihat pekerjaan milik kabkotanya sendiri
        // isProvinsi dan pengawas (bukan isKabkota) bebas akses semua
        if ($this->rbac->isKabkota()
            && (int)$pekerjaan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('pekerjaan'); return;
        }

        $tahapan     = $this->Pekerjaan_model->get_tahapan($id);
        $semua_dok   = $this->Pekerjaan_model->get_semua_dokumen_pekerjaan($id);
        $history     = $this->Pekerjaan_model->get_status_history($id);

        // Ambil dokumen per tahapan
        $dok_per_tahapan = [];
        foreach ($semua_dok as $d) {
            $dok_per_tahapan[$d->tahapan_id][] = $d;
        }

        // Cek deadline untuk semua tahapan
        $deadline_info = [];
        foreach ($tahapan as $t) {
            $deadline_info[$t->id] = $this->Parameter_model->cek_deadline(
                $pekerjaan->tahun,
                $pekerjaan->jenis_penyaluran,
                $t->kode_tahap
            );
        }

        // Pejabat kab (untuk cetak)
        $pejabat = $this->db
            ->get_where('ref_pemda_pejabat', [
                'kabkota_id' => $pekerjaan->kabkota_id,
                'tahun'      => $pekerjaan->tahun,
            ])->result();
        $pejabat_map = [];
        foreach ($pejabat as $p) $pejabat_map[$p->jenis] = $p;

        $this->render('pekerjaan/detail', array_merge($this->data, [
            'title'           => 'Detail Pekerjaan — ' . $pekerjaan->kode_bkp,
            'pekerjaan'       => $pekerjaan,
            'tahapan'         => $tahapan,
            'dok_per_tahapan' => $dok_per_tahapan,
            'deadline_info'   => $deadline_info,
            'history'         => $history,
            'pejabat'         => $pejabat_map,
        ]));
    }

    // ─── SUBMIT KE INSPEKTORAT ────────────────────────────────

    public function submit($id)
    {
        $this->requirePerm('pekerjaan.submit');
        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        if ($pekerjaan->status !== 'draft') {
            $this->session->set_flashdata('error', 'Pekerjaan sudah diajukan sebelumnya.');
            redirect('pekerjaan/detail/'.$id); return;
        }

        // ── VALIDASI BATAS WAKTU ──────────────────────────────
        // Untuk bertahap cek Tahap I dulu
        $kode_tahap_cek = $pekerjaan->jenis_penyaluran === 'bertahap' ? 'tahap_1' : 'khusus';
        if (in_array($pekerjaan->jenis_penyaluran, ['sekaligus'])) $kode_tahap_cek = 'sekaligus';

        $cek = $this->Parameter_model->cek_deadline(
            $pekerjaan->tahun,
            $pekerjaan->jenis_penyaluran,
            $kode_tahap_cek
        );

        if (!$cek['ok']) {
            // BLOKIR — batas waktu sudah lewat
            $this->session->set_flashdata('error_deadline', $cek['pesan']);
            redirect('pekerjaan/detail/'.$id); return;
        }

        // ── VALIDASI KELENGKAPAN DATA ─────────────────────────
        $errors = $this->_validasi_kelengkapan($pekerjaan);
        if (!empty($errors)) {
            $this->session->set_flashdata('error',
                'Data belum lengkap: ' . implode('; ', $errors));
            redirect('pekerjaan/detail/'.$id); return;
        }

        // ── BUAT TAHAPAN ──────────────────────────────────────
        $this->Pekerjaan_model->buat_tahapan(
            $id,
            $pekerjaan->jenis_penyaluran,
            $pekerjaan->nilai_kontrak,
            $pekerjaan->tahun,
            $this->user_id
        );

        // Set status pekerjaan → opd_submitted
        $this->Pekerjaan_model->set_status($id, 'opd_submitted', $this->user_id,
            'Diajukan ke Inspektorat oleh OPD');

        // Set tahapan pertama → opd_input, tandai tgl_pengajuan
        $tahapan = $this->Pekerjaan_model->get_tahapan($id);
        if (!empty($tahapan)) {
            $this->db->where('id', $tahapan[0]->id)->update('trx_tahapan_penyaluran', [
                'status'       => 'opd_input',
                'tgl_pengajuan'=> date('Y-m-d'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // Notifikasi ke Inspektorat (semua user inspektorat kab/kota ini)
        $inspektorat_users = $this->db
            ->select('u.id')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->where('r.kode', 'inspektorat')
            ->where('u.kabkota_id', $pekerjaan->kabkota_id)
            ->where('u.is_active', 1)
            ->get()->result();
        foreach ($inspektorat_users as $iu) {
            $this->Notifikasi_model->kirim(
                $iu->id,
                'Pekerjaan Baru Masuk',
                'BKP ' . $pekerjaan->kode_bkp . ' — ' . $pekerjaan->nama_kegiatan_dok . ' telah diajukan untuk reviu.',
                'info',
                site_url('pekerjaan/detail/'.$id),
                $id
            );
        }

        $this->log_aktivitas('pekerjaan.submit', 'Submit pekerjaan id='.$id.' ke Inspektorat');
        $this->session->set_flashdata('success',
            'Pekerjaan berhasil diajukan ke Inspektorat untuk dilakukan reviu.');
        redirect('pekerjaan/detail/'.$id);
    }

    // ─── BATALKAN PENGAJUAN KE INSPEKTORAT ───────────────────
    // Hanya bisa saat status opd_submitted (belum diproses Inspektorat)

    public function batal_submit($id)
    {
        $this->requirePerm('pekerjaan.submit');

        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        // Hanya bisa dibatalkan saat masih opd_submitted
        if ($pekerjaan->status !== 'opd_submitted') {
            $this->session->set_flashdata('error',
                'Pengajuan tidak dapat dibatalkan. Pekerjaan sudah dalam proses reviu Inspektorat.');
            redirect('pekerjaan/detail/'.$id); return;
        }

        // Guard: hanya OPD pemilik pekerjaan
        if ($this->rbac->isKabkota() && (int)$pekerjaan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('pekerjaan'); return;
        }

        // Kembalikan status pekerjaan ke draft
        $this->Pekerjaan_model->set_status($id, 'draft', $this->user_id,
            'Pengajuan ke Inspektorat dibatalkan oleh OPD');

        // Reset tahapan pertama kembali ke status 'belum' dan hapus tgl_pengajuan
        $tahapan = $this->Pekerjaan_model->get_tahapan($id);
        if (!empty($tahapan)) {
            $this->db->where('id', $tahapan[0]->id)->update('trx_tahapan_penyaluran', [
                'status'       => 'belum',
                'tgl_pengajuan'=> NULL,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $this->log_aktivitas('pekerjaan.batal_submit', 'Batal submit pekerjaan id='.$id);
        $this->session->set_flashdata('success',
            'Pengajuan berhasil dibatalkan. Pekerjaan kembali ke status Draft dan dapat diedit.');
        redirect('pekerjaan/detail/'.$id);
    }

    private function _validasi_kelengkapan($pekerjaan)
    {
        $errors = [];
        if (empty($pekerjaan->nama_kegiatan_dok)) $errors[] = 'Nama kegiatan belum diisi';
        if (empty($pekerjaan->no_dok_pekerjaan))  $errors[] = 'Nomor dokumen pekerjaan/kontrak belum diisi';
        if (empty($pekerjaan->nama_penyedia))      $errors[] = 'Nama penyedia/rekanan belum diisi';
        if (empty($pekerjaan->nilai_kontrak) || $pekerjaan->nilai_kontrak <= 0) $errors[] = 'Nilai kontrak belum diisi';
        if (empty($pekerjaan->no_spmk))            $errors[] = 'Nomor SPMK belum diisi';

        // Validasi dokumen wajib pre-submit
        if (empty($pekerjaan->dok_spk_path))  $errors[] = 'File SPK belum diupload';
        if (empty($pekerjaan->dok_spmk_path)) $errors[] = 'File SPMK belum diupload';
        if ($pekerjaan->jenis_penyaluran === 'sekaligus' && empty($pekerjaan->dok_bast_path))
            $errors[] = 'File BAST belum diupload (wajib untuk penyaluran Sekaligus)';

        return $errors;
    }

    // ─── UPLOAD DOKUMEN DRAFT (SPK / SPMK / BAST) ────────────
    // Dipanggil sebelum submit, saat pekerjaan masih berstatus draft

    public function upload_dok_draft($pekerjaan_id, $jenis)
    {
        $this->requirePerm('pekerjaan.upload_dok');

        $jenis_allowed = ['spk', 'spmk', 'bast'];
        if (!in_array($jenis, $jenis_allowed)) { show_404(); return; }

        $pekerjaan = $this->Pekerjaan_model->get_by_id($pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }
        if ($pekerjaan->status !== 'draft') {
            $this->session->set_flashdata('error', 'Dokumen draft hanya bisa diupload saat status Draft.');
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }

        // Guard: hanya OPD pemilik pekerjaan ini
        if ($this->rbac->isKabkota() && (int)$pekerjaan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('pekerjaan'); return;
        }
        // Khusus BAST: hanya untuk jenis sekaligus
        if ($jenis === 'bast' && $pekerjaan->jenis_penyaluran !== 'sekaligus') {
            $this->session->set_flashdata('error', 'BAST hanya untuk jenis penyaluran Sekaligus.');
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }

        if ($_FILES['file_dok']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'File tidak valid atau tidak ada file yang dipilih.');
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }

        $upload_dir = FCPATH . 'uploads/dokumen/' . $pekerjaan_id . '/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, TRUE);

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $upload_dir,
            'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
            'max_size'      => 10240,
            'file_name'     => $jenis . '_' . $pekerjaan_id . '_' . time(),
        ]);

        if (!$this->upload->do_upload('file_dok')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }

        $file_info = $this->upload->data();
        $file_path = 'uploads/dokumen/' . $pekerjaan_id . '/' . $file_info['file_name'];

        // Hapus file lama jika ada
        $kolom     = 'dok_' . $jenis . '_path';
        $file_lama = $pekerjaan->$kolom ?? NULL;
        if ($file_lama && file_exists(FCPATH . $file_lama)) @unlink(FCPATH . $file_lama);

        // Simpan path ke kolom yang sesuai
        $this->db->where('id', $pekerjaan_id)->update('trx_pekerjaan', [
            $kolom       => $file_path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $label = strtoupper($jenis);
        $this->log_aktivitas('pekerjaan.upload_dok', 'Upload '.$label.' pekerjaan id='.$pekerjaan_id);
        $this->session->set_flashdata('success', 'File ' . $label . ' berhasil diupload.');
        redirect('pekerjaan/detail/'.$pekerjaan_id);
    }

    public function hapus_dok_draft($pekerjaan_id, $jenis)
    {
        $this->requirePerm('pekerjaan.upload_dok');

        $jenis_allowed = ['spk', 'spmk', 'bast'];
        if (!in_array($jenis, $jenis_allowed)) { show_404(); return; }

        $pekerjaan = $this->Pekerjaan_model->get_by_id($pekerjaan_id);
        if (!$pekerjaan || $pekerjaan->status !== 'draft') {
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }
        if ($this->rbac->isKabkota() && (int)$pekerjaan->kabkota_id !== (int)$this->kabkota_id) {
            redirect('pekerjaan'); return;
        }

        $kolom     = 'dok_' . $jenis . '_path';
        $file_path = $pekerjaan->$kolom ?? NULL;
        if ($file_path && file_exists(FCPATH . $file_path)) @unlink(FCPATH . $file_path);

        $this->db->where('id', $pekerjaan_id)->update('trx_pekerjaan', [
            $kolom       => NULL,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'File ' . strtoupper($jenis) . ' berhasil dihapus.');
        redirect('pekerjaan/detail/'.$pekerjaan_id);
    }

    // ─── UPLOAD DOKUMEN ───────────────────────────────────────

    public function upload_dok($tahapan_id)
    {
        $this->requirePerm('pekerjaan.upload_dok');
        $tahapan = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }

        $pekerjaan_id = $tahapan->pekerjaan_id;

        // Guard kabkota
        $pekerjaan_cek = $this->Pekerjaan_model->get_by_id($pekerjaan_id);
        if ($this->rbac->isKabkota()
            && $pekerjaan_cek
            && (int)$pekerjaan_cek->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('pekerjaan'); return;
        }
        $jenis_dok    = $this->input->post('jenis_dokumen', TRUE);
        $keterangan   = $this->input->post('keterangan', TRUE);

        // Konfigurasi upload
        $upload_dir = FCPATH . 'uploads/dokumen/' . $pekerjaan_id . '/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, TRUE);

        $this->load->library('upload', [
            'upload_path'   => $upload_dir,
            'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
            'max_size'      => 10240,
            'file_name'     => 'dok_' . $tahapan_id . '_' . $jenis_dok . '_' . time(),
        ]);

        if (!$this->upload->do_upload('file_dok')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            redirect('pekerjaan/detail/'.$pekerjaan_id); return;
        }

        $up = $this->upload->data();
        $this->Pekerjaan_model->insert_dokumen([
            'tahapan_id'    => $tahapan_id,
            'jenis_dokumen' => $jenis_dok,
            'nama_file'     => $up['file_name'],
            'file_path'     => 'uploads/dokumen/' . $pekerjaan_id . '/' . $up['file_name'],
            'ukuran_kb'     => (int)($up['file_size']),
            'keterangan'    => $keterangan,
            'is_required'   => 0,
            'user_upload'   => $this->user_id,
        ]);

        $this->log_aktivitas('pekerjaan.upload_dok',
            'Upload dok tahapan_id='.$tahapan_id.' jenis='.$jenis_dok);
        $this->session->set_flashdata('success', 'Dokumen berhasil diupload.');
        redirect('pekerjaan/detail/'.$pekerjaan_id);
    }

    public function hapus_dok($dok_id)
    {
        $this->requirePerm('pekerjaan.upload_dok');
        $dok = $this->Pekerjaan_model->get_dokumen_by_id($dok_id);
        if (!$dok) { show_404(); return; }

        $tahapan      = $this->Pekerjaan_model->get_tahapan_by_id($dok->tahapan_id);
        $pekerjaan_id = $tahapan ? $tahapan->pekerjaan_id : 0;

        // Guard kabkota
        if ($pekerjaan_id && $this->rbac->isKabkota()) {
            $pekerjaan_cek = $this->Pekerjaan_model->get_by_id($pekerjaan_id);
            if ($pekerjaan_cek && (int)$pekerjaan_cek->kabkota_id !== (int)$this->kabkota_id) {
                $this->session->set_flashdata('error', 'Akses ditolak.');
                redirect('pekerjaan'); return;
            }
        }

        $this->Pekerjaan_model->hapus_dokumen($dok_id);
        $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        redirect('pekerjaan/detail/'.$pekerjaan_id);
    }

    // ─── CETAK PERMOHONAN REVIU ───────────────────────────────

    public function cetak_permohonan($id)
    {
        $this->requirePerm('pekerjaan.cetak_permohonan');
        $pekerjaan = $this->Pekerjaan_model->get_by_id($id);
        if (!$pekerjaan) { show_404(); return; }

        $tahapan = $this->Pekerjaan_model->get_tahapan($id);

        // Ambil pejabat
        $pejabat = [];
        $rows = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
        ])->result();
        foreach ($rows as $p) $pejabat[$p->jenis] = $p;

        // Perda & Perkada
        $perda   = $pekerjaan->ref_perda_id
            ? $this->db->get_where('ref_pemda_dokumen', ['id'=>$pekerjaan->ref_perda_id])->row()
            : NULL;
        $perkada = $pekerjaan->ref_perkada_id
            ? $this->db->get_where('ref_pemda_dokumen', ['id'=>$pekerjaan->ref_perkada_id])->row()
            : NULL;

        $this->render_plain('pekerjaan/cetak_permohonan', [
            'pekerjaan' => $pekerjaan,
            'tahapan'   => $tahapan,
            'pejabat'   => $pejabat,
            'perda'     => $perda,
            'perkada'   => $perkada,
            'tgl_cetak' => tgl_indo(date('Y-m-d')),
        ]);
    }
}
