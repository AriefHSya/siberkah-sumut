<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verif_kab.php — Controller Verifikasi SKPKD Kab/Kota
 *
 * Menangani verifikasi oleh SKPKD Kab/Kota setelah Inspektorat approve,
 * upload dokumen permohonan pencairan, dan konfirmasi penerimaan dana RKUD.
 *
 * ALUR:
 *   Inspektorat approve → status 'inspektorat_approved'
 *   → SKPKD Kab buka form verifikasi → upload permohonan
 *   → putuskan (disetujui/ditolak/revisi) → kirim ke Verifikasi Provinsi
 *   → setelah dana disalurkan → konfirmasi penerimaan + upload bukti RKUD
 *
 * ROUTES:
 *   GET  /verifikasi/kab                   → index()        — antrian verifikasi
 *   GET  /verifikasi/kab/form/{tahapan_id} → form()         — form verifikasi
 *   POST /verifikasi/kab/upload/{id}       → upload_dok()   — upload dokumen permohonan
 *   POST /verifikasi/kab/hapus-dok/{id}    → hapus_dok()    — hapus dokumen
 *   POST /verifikasi/kab/putus/{id}        → putuskan()     — approve/tolak/revisi
 *   GET  /verifikasi/kab/rekap/{id}        → cetak_rekap()  — cetak rekap kegiatan
 *   POST /verifikasi/kab/konfirmasi/{id}   → konfirmasi()   — konfirmasi penerimaan RKUD
 *
 * KEAMANAN:
 *   - Guard IDOR: SKPKD hanya bisa akses data kabkota mereka sendiri
 *   - Notifikasi Telegram ke admin provinsi setelah permohonan diajukan
 */
class Verif_kab extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('verif_kab.view');
        $this->load->model([
            'Verifikasi_kab_model',
            'Pekerjaan_model',
            'Parameter_model',
        ]);
        $this->data['active_menu'] = 'verif_kab';
    }

    // ─── ANTRIAN ──────────────────────────────────────────────

    public function index()
    {
        $tahun    = $this->tahun;
        $per_page = 20;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tahun'      => $tahun,
            'kabkota_id' => $this->input->get('kabkota_id'),
            'status'     => $this->input->get('status'),
            'jenis'      => $this->input->get('jenis'),
            'q'          => $this->input->get('q'),
        ];

        if ($this->rbac->isKabkota()) {
            $filters['kabkota_id'] = (int)$this->kabkota_id ?: -1;
        }

        $total        = $this->Verifikasi_kab_model->count_filtered($filters);
        $offset       = ($page - 1) * $per_page;
        $list         = $this->Verifikasi_kab_model->get_antrian($filters, $per_page, $offset);
        $count_status = $this->Verifikasi_kab_model->count_by_status($tahun, $filters['kabkota_id']);
        $rekap_nilai  = $this->Verifikasi_kab_model->rekap_nilai($tahun, $filters['kabkota_id']);
        $kabkota_list = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];

        $this->render('verif_kab/index', array_merge($this->data, [
            'title'        => 'Verifikasi SKPKD Kab/Kota — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'count_status' => $count_status,
            'rekap_nilai'  => $rekap_nilai,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'verifikasi/kab'],
        ]));
    }

    // ─── FORM VERIFIKASI ──────────────────────────────────────

    public function form($tahapan_id)
    {
        $this->requirePerm('verif_kab.input');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }

        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        // Guard kabkota
        if ($this->role_kode === 'skpkd_kabkota'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('verifikasi/kab'); return;
        }

        // Status valid untuk diverifikasi
        if (!in_array($tahapan->status, [
            'inspektorat_approved', 'skpkd_kab_verif', 'skpkd_kab_revisi'
        ])) {
            $this->session->set_flashdata('error',
                'Tahapan tidak dalam status yang dapat diverifikasi.');
            redirect('verifikasi/kab'); return;
        }

        // Buat atau ambil record verifikasi
        $verif_id = $this->Verifikasi_kab_model->buat_atau_ambil($tahapan_id, $this->user_id);

        // Set status → skpkd_kab_verif jika baru masuk
        if ($tahapan->status === 'inspektorat_approved') {
            $this->db->where('id', $tahapan_id)
                ->update('trx_tahapan_penyaluran', [
                    'status'     => 'skpkd_kab_verif',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $this->Pekerjaan_model->set_status(
                $pekerjaan->id, 'skpkd_kab_verif', $this->user_id,
                'SKPKD Kab/Kota mulai melakukan verifikasi'
            );
        }

        $verif   = $this->Verifikasi_kab_model->get_by_id($verif_id);
        $dokumen = $this->Verifikasi_kab_model->get_dokumen($tahapan_id);

        // Reviu inspektorat (untuk referensi & tampil nomor LHR)
        $reviu = $this->db->get_where('trx_reviu_inspektorat',
            ['tahapan_id' => $tahapan_id])->row();

        // Pejabat PPKD (Kepala BKAD) untuk TTD dokumen
        $pejabat_ppkd = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
            'jenis'      => 'kepala_bkad',
        ])->row();
        $pejabat_kdh = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
            'jenis'      => 'kepala_daerah',
        ])->row();

        // Status history pekerjaan
        $history = $this->Pekerjaan_model->get_status_history($pekerjaan->id);

        // Inject penyaluran agar view tidak perlu akses model langsung
        $penyaluran = $this->Verifikasi_kab_model->get_penyaluran($tahapan_id);

        $this->render('verif_kab/form', array_merge($this->data, [
            'title'        => 'Verifikasi — ' . $pekerjaan->kode_bkp,
            'tahapan'      => $tahapan,
            'pekerjaan'    => $pekerjaan,
            'verif'        => $verif,
            'dokumen'      => $dokumen,
            'reviu'        => $reviu,
            'penyaluran'   => $penyaluran,
            'pejabat_ppkd' => $pejabat_ppkd,
            'pejabat_kdh'  => $pejabat_kdh,
            'history'      => $history,
        ]));
    }

    // ─── UPLOAD DOKUMEN PERMOHONAN ────────────────────────────

    public function upload_dok($tahapan_id)
    {
        $this->requirePerm('pekerjaan.upload_dok');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        if ($this->role_kode === 'skpkd_kabkota'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('verifikasi/kab'); return;
        }

        $dir = FCPATH . 'uploads/permohonan/' . $pekerjaan->id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
            'max_size'      => 10240,
            'file_name'     => 'dok_kab_' . $tahapan_id . '_' . time(),
        ]);

        if (!$this->upload->do_upload('file_dok')) {
            $this->session->set_flashdata('error',
                $this->upload->display_errors('', ''));
            redirect('verifikasi/kab/form/' . $tahapan_id); return;
        }

        $up       = $this->upload->data();
        $jenis    = $this->input->post('jenis_dokumen', TRUE);
        $filepath = 'uploads/permohonan/' . $pekerjaan->id . '/' . $up['file_name'];

        $this->Pekerjaan_model->insert_dokumen([
            'tahapan_id'    => $tahapan_id,
            'jenis_dokumen' => $jenis,
            'nama_file'     => $up['file_name'],
            'file_path'     => $filepath,
            'ukuran_kb'     => (int)$up['file_size'],
            'keterangan'    => $this->input->post('keterangan', TRUE),
            'is_required'   => 0,
            'user_upload'   => $this->user_id,
        ]);

        $this->log_aktivitas('verif_kab.upload_dok',
            'Upload dok tahapan='.$tahapan_id.' jenis='.$jenis);
        $this->session->set_flashdata('success', 'Dokumen berhasil diupload.');
        redirect('verifikasi/kab/form/' . $tahapan_id);
    }

    public function hapus_dok($dok_id)
    {
        $this->requirePerm('pekerjaan.upload_dok');
        $dok     = $this->Pekerjaan_model->get_dokumen_by_id($dok_id);
        if (!$dok) { show_404(); return; }
        $tahapan = $this->Pekerjaan_model->get_tahapan_by_id($dok->tahapan_id);

        $this->Pekerjaan_model->hapus_dokumen($dok_id);
        $this->session->set_flashdata('success', 'Dokumen dihapus.');
        redirect('verifikasi/kab/form/' . $dok->tahapan_id);
    }

    // ─── KEPUTUSAN VERIFIKASI ─────────────────────────────────

    public function putuskan($verif_id)
    {
        $this->requirePerm('verif_kab.approve');

        $verif   = $this->Verifikasi_kab_model->get_by_id($verif_id);
        if (!$verif) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($verif->tahapan_id);
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);

        $hasil        = $this->input->post('hasil_verifikasi', TRUE);
        $catatan      = $this->input->post('catatan', TRUE);
        $no_surat     = $this->input->post('no_surat_verif', TRUE);
        $ref_ppkd_id  = $this->input->post('ref_ppkd_id', TRUE) ?: NULL;

        if (!in_array($hasil, ['disetujui','ditolak','perlu_perbaikan'])) {
            $this->session->set_flashdata('error', 'Keputusan tidak valid.');
            redirect('verifikasi/kab/form/' . $verif->tahapan_id); return;
        }

        if (in_array($hasil, ['ditolak','perlu_perbaikan']) && empty($catatan)) {
            $this->session->set_flashdata('error',
                'Catatan wajib diisi jika hasil adalah Ditolak atau Perlu Perbaikan.');
            redirect('verifikasi/kab/form/' . $verif->tahapan_id); return;
        }

        // Update record verifikasi
        $this->Verifikasi_kab_model->update($verif_id, [
            'hasil_verifikasi' => $hasil,
            'catatan'          => $catatan,
            'no_surat_verif'   => $no_surat,
            'tgl_verifikasi'   => date('Y-m-d'),
            'ref_ppkd_id'      => $ref_ppkd_id,
        ]);

        // Map keputusan → status
        $status_map = [
            'disetujui'       => ['tahapan'=>'skpkd_kab_approved', 'pekerjaan'=>'skpkd_kab_approved'],
            'perlu_perbaikan' => ['tahapan'=>'skpkd_kab_revisi',   'pekerjaan'=>'skpkd_kab_revisi'],
            'ditolak'         => ['tahapan'=>'ditolak',            'pekerjaan'=>'ditolak'],
        ];
        $st = $status_map[$hasil];

        $this->db->where('id', $tahapan->id)
            ->update('trx_tahapan_penyaluran', [
                'status'     => $st['tahapan'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $ket_map = [
            'disetujui'       => 'Verifikasi SKPKD Kab/Kota: Disetujui. Permohonan pencairan dikirim ke Provinsi.',
            'perlu_perbaikan' => 'Verifikasi SKPKD Kab/Kota: Perlu Perbaikan. ' . $catatan,
            'ditolak'         => 'Verifikasi SKPKD Kab/Kota: Ditolak. ' . $catatan,
        ];
        $this->Pekerjaan_model->set_status(
            $pekerjaan->id, $st['pekerjaan'],
            $this->user_id, $ket_map[$hasil]
        );

        // Notifikasi OPD Teknis
        $jenis_notif = ['disetujui'=>'sukses','perlu_perbaikan'=>'peringatan','ditolak'=>'error'];
        $pesan_notif = [
            'disetujui'       => 'Verifikasi SKPKD untuk BKP '.$pekerjaan->kode_bkp.' DISETUJUI. Permohonan diteruskan ke Provinsi.',
            'perlu_perbaikan' => 'Verifikasi SKPKD untuk BKP '.$pekerjaan->kode_bkp.' dikembalikan. Catatan: '.$catatan,
            'ditolak'         => 'Verifikasi SKPKD untuk BKP '.$pekerjaan->kode_bkp.' DITOLAK. Catatan: '.$catatan,
        ];
        $this->Notifikasi_model->kirim(
            $pekerjaan->created_by,
            'Hasil Verifikasi SKPKD',
            $pesan_notif[$hasil],
            $jenis_notif[$hasil],
            site_url('pekerjaan/detail/' . $pekerjaan->id),
            $pekerjaan->id
        );

        // Jika disetujui → notifikasi in-app + Telegram ke Admin Provinsi
        if ($hasil === 'disetujui') {
            $admin_prov_users = $this->db
                ->select('u.id')->from('users u')
                ->join('roles r', 'r.id = u.role_id')
                ->where_in('r.kode', ['superadmin','admin_provinsi'])
                ->where('u.is_active', 1)->get()->result();
            foreach ($admin_prov_users as $au) {
                $this->Notifikasi_model->kirim(
                    $au->id,
                    'Permohonan Pencairan Masuk',
                    'BKP '.$pekerjaan->kode_bkp.' dari '.$pekerjaan->nama_kabkota.' telah diverifikasi SKPKD. Siap diproses penyalurannya.',
                    'info',
                    site_url('verifikasi/prov/form/' . $tahapan->id),
                    $pekerjaan->id
                );
            }

            // ── Telegram Notification ──────────────────────────
            // Hitung total pending dari kabkota ini (status skpkd_kab_approved)
            $total_pending = $this->db
                ->from('trx_pekerjaan p')
                ->join('ref_bkp b', 'b.id = p.bkp_id')
                ->where('b.kabkota_id', $pekerjaan->kabkota_id)
                ->where('b.tahun', $pekerjaan->tahun)
                ->where('p.status', 'skpkd_kab_approved')
                ->count_all_results();

            $total_nilai_pending = $this->db
                ->select('SUM(p.nilai_kontrak) as total')
                ->from('trx_pekerjaan p')
                ->join('ref_bkp b', 'b.id = p.bkp_id')
                ->where('b.kabkota_id', $pekerjaan->kabkota_id)
                ->where('b.tahun', $pekerjaan->tahun)
                ->where('p.status', 'skpkd_kab_approved')
                ->get()->row();

            $label_jenis = [
                'bertahap'       => 'Bertahap',
                'sekaligus'      => 'Sekaligus',
                'khusus_mendesak'=> 'Khusus Mendesak',
                'khusus_bencana' => 'Darurat Bencana',
            ];
            $jenis_label = $label_jenis[$pekerjaan->jenis_penyaluran] ?? $pekerjaan->jenis_penyaluran;
            $kode_tahap  = strtoupper(str_replace('_',' ',$tahapan->kode_tahap ?? ''));
            $jenis_full  = $kode_tahap ? $jenis_label . ' – ' . $kode_tahap : $jenis_label;

            $waktu = date('d/m/Y \p\u\k\u\l H:i:s');

            $msg  = "🏛 <b>PERMOHONAN PENCAIRAN BKP MASUK</b>\n\n";
            $msg .= "📍 <b>Kab/Kota :</b> " . htmlspecialchars($pekerjaan->nama_kabkota) . "\n";
            $msg .= "📋 <b>Jenis     :</b> " . $jenis_full . "\n";
            $msg .= "🏗 <b>Kode BKP  :</b> " . htmlspecialchars($pekerjaan->kode_bkp) . "\n";
            $msg .= "📌 <b>Kegiatan  :</b> " . htmlspecialchars($pekerjaan->nama_kegiatan_dok ?: $pekerjaan->uraian_bkp) . "\n";
            $msg .= "💰 <b>Nilai     :</b> Rp " . number_format($pekerjaan->nilai_kontrak, 0, ',', '.') . "\n";
            $msg .= "\n";
            $msg .= "📊 <b>Total Pending dari " . htmlspecialchars($pekerjaan->nama_kabkota) . ":</b>\n";
            $msg .= "   • <b>" . $total_pending . " kegiatan</b>";
            if ($total_nilai_pending && $total_nilai_pending->total) {
                $msg .= " | Rp " . number_format($total_nilai_pending->total, 0, ',', '.');
            }
            $msg .= "\n\n";
            $msg .= "📅 <b>" . $waktu . " WIB</b>\n";
            $msg .= "<i>— Sistem SIBERKAH SUMUT TA " . $pekerjaan->tahun . "</i>";

            telegram_notif_admin_prov($msg);
        }

        $flash_map = [
            'disetujui'       => 'Verifikasi disetujui. Permohonan pencairan dikirim ke Provinsi.',
            'perlu_perbaikan' => 'Dikembalikan untuk perbaikan.',
            'ditolak'         => 'Pekerjaan ditolak.',
        ];
        $this->log_aktivitas('verif_kab.putuskan',
            'Verif id='.$verif_id.' hasil='.$hasil.' pekerjaan_id='.$pekerjaan->id);
        $this->session->set_flashdata('success', $flash_map[$hasil]);
        redirect('verifikasi/kab');
    }

    // ─── KONFIRMASI PENERIMAAN DANA ───────────────────────────

    public function konfirmasi($tahapan_id)
    {
        $this->requirePerm('verif_kab.konfirmasi');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        if ($this->role_kode === 'skpkd_kabkota'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('verifikasi/kab'); return;
        }

        if ($tahapan->status !== 'disalurkan') {
            $this->session->set_flashdata('error',
                'Konfirmasi hanya dapat dilakukan setelah dana disalurkan oleh Provinsi.');
            redirect('verifikasi/kab/form/' . $tahapan_id); return;
        }

        // Ambil data penyaluran
        $penyaluran = $this->Verifikasi_kab_model->get_penyaluran($tahapan_id);
        if (!$penyaluran) {
            $this->session->set_flashdata('error', 'Data penyaluran tidak ditemukan.');
            redirect('verifikasi/kab/form/' . $tahapan_id); return;
        }

        // Upload bukti transfer RKUD
        $dir = FCPATH . 'uploads/permohonan/' . $pekerjaan->id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size'      => 10240,
            'file_name'     => 'bukti_transfer_' . $tahapan_id . '_' . time(),
        ]);

        $file_path = NULL; $nama_file = NULL;
        if (!empty($_FILES['file_bukti']['name'])) {
            if (!$this->upload->do_upload('file_bukti')) {
                $this->session->set_flashdata('error',
                    'Upload bukti gagal: ' . $this->upload->display_errors('', ''));
                redirect('verifikasi/kab/form/' . $tahapan_id); return;
            }
            $up        = $this->upload->data();
            $file_path = 'uploads/permohonan/' . $pekerjaan->id . '/' . $up['file_name'];
            $nama_file = $up['file_name'];
        }

        if (!$file_path) {
            $this->session->set_flashdata('error',
                'File bukti transfer RKUD wajib diupload.');
            redirect('verifikasi/kab/form/' . $tahapan_id); return;
        }

        $keterangan = $this->input->post('keterangan', TRUE);
        $this->Verifikasi_kab_model->simpan_bukti_transfer(
            $penyaluran->id, $file_path, $nama_file, $keterangan, $this->user_id
        );

        // Update status tahapan → dikonfirmasi
        $this->db->where('id', $tahapan_id)
            ->update('trx_tahapan_penyaluran', [
                'status'     => 'dikonfirmasi',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Tentukan status pekerjaan berikutnya
        // Cek apakah semua tahapan sudah dikonfirmasi
        $semua_tahapan = $this->Pekerjaan_model->get_tahapan($pekerjaan->id);
        $semua_selesai = TRUE;
        foreach ($semua_tahapan as $t) {
            if ($t->id == $tahapan_id) continue; // sudah dikonfirmasi
            if (!in_array($t->status, ['dikonfirmasi','ditolak','belum'])) {
                $semua_selesai = FALSE; break;
            }
        }

        $status_baru = $semua_selesai ? 'selesai' : 'dikonfirmasi_tahap1';
        $this->Pekerjaan_model->set_status(
            $pekerjaan->id, $status_baru,
            $this->user_id,
            'Dana diterima di RKUD ' . $pekerjaan->nama_kabkota . '. Bukti transfer diupload.'
        );

        // Notifikasi Admin Provinsi
        $admin_prov_users = $this->db
            ->select('u.id')->from('users u')
            ->join('roles r','r.id = u.role_id')
            ->where_in('r.kode', ['superadmin','admin_provinsi'])
            ->where('u.is_active', 1)->get()->result();
        foreach ($admin_prov_users as $au) {
            $this->Notifikasi_model->kirim(
                $au->id,
                'Dana Dikonfirmasi Diterima',
                $pekerjaan->nama_kabkota . ' telah mengkonfirmasi penerimaan dana BKP ' . $pekerjaan->kode_bkp . '.',
                'sukses',
                site_url('pekerjaan/detail/' . $pekerjaan->id),
                $pekerjaan->id
            );
        }

        $this->log_aktivitas('verif_kab.konfirmasi',
            'Konfirmasi dana tahapan='.$tahapan_id);
        $this->session->set_flashdata('success',
            'Penerimaan dana berhasil dikonfirmasi. Bukti transfer RKUD telah disimpan.');
        redirect('verifikasi/kab/form/' . $tahapan_id);
    }

    // ─── CETAK REKAPITULASI KEGIATAN ──────────────────────────

    public function cetak_rekap($tahapan_id)
    {
        $this->requirePerm('verif_kab.cetak_rekap');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        $verif     = $this->Verifikasi_kab_model->get_by_tahapan($tahapan_id);
        $dokumen   = $this->Verifikasi_kab_model->get_dokumen($tahapan_id);
        $reviu     = $this->db->get_where('trx_reviu_inspektorat',
                         ['tahapan_id'=>$tahapan_id])->row();
        $penyaluran = $this->Verifikasi_kab_model->get_penyaluran($tahapan_id);

        // Pejabat
        $pejabat = [];
        $rows = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
        ])->result();
        foreach ($rows as $p) $pejabat[$p->jenis] = $p;

        // Dokumen referensi (perda/perkada)
        $perda   = $pekerjaan->ref_perda_id
            ? $this->db->get_where('ref_pemda_dokumen', ['id'=>$pekerjaan->ref_perda_id])->row() : NULL;
        $perkada = $pekerjaan->ref_perkada_id
            ? $this->db->get_where('ref_pemda_dokumen', ['id'=>$pekerjaan->ref_perkada_id])->row() : NULL;

        $this->render_plain('verif_kab/cetak_rekap', [
            'pekerjaan'  => $pekerjaan,
            'tahapan'    => $tahapan,
            'verif'      => $verif,
            'reviu'      => $reviu,
            'penyaluran' => $penyaluran,
            'dokumen'    => $dokumen,
            'pejabat'    => $pejabat,
            'perda'      => $perda,
            'perkada'    => $perkada,
            'tgl_cetak'  => tgl_indo(date('Y-m-d')),
        ]);
    }
}
