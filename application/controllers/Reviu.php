<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reviu.php — Controller Reviu Inspektorat
 *
 * Menangani proses reviu oleh Inspektorat Kab/Kota terhadap
 * dokumen pekerjaan yang diajukan OPD Teknis.
 *
 * ALUR:
 *   OPD submit → status 'opd_input' → Inspektorat buka form reviu
 *   → isi 21-item checklist → upload LHR → putuskan (disetujui/ditolak/revisi)
 *
 * ROUTES:
 *   GET  /reviu                      → index()            — antrian menunggu reviu
 *   GET  /reviu/form/{tahapan_id}    → form()             — form checklist + upload LHR
 *   POST /reviu/checklist/{reviu_id} → simpan_checklist() — simpan isian checklist
 *   POST /reviu/lhr/{reviu_id}       → upload_lhr()       — upload file LHR
 *   POST /reviu/putus/{reviu_id}     → putuskan()         — approve/tolak/minta revisi
 *   GET  /reviu/kertas/{reviu_id}    → cetak_kertas_kerja() — cetak PDF kertas kerja
 *   GET  /reviu/rekap/{reviu_id}     → cetak_rekap()      — cetak rekap hasil reviu
 *
 * AKSES:
 *   - Inspektorat: akses penuh (reviu + putuskan)
 *   - Role kabkota lain: view only (index, cetak)
 *   - Guard IDOR: inspektorat hanya bisa reviu tahapan kabkota mereka
 */
class Reviu extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('reviu.view');
        $this->load->model(['Reviu_model', 'Pekerjaan_model', 'Parameter_model']);
        $this->data['active_menu'] = 'reviu';
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

        // Semua role kabkota hanya lihat antrian milik kabkotanya sendiri
        if ($this->rbac->isKabkota()) {
            $filters['kabkota_id'] = (int)$this->kabkota_id ?: -1;
        }

        $total        = $this->Reviu_model->count_filtered($filters);
        $offset       = ($page - 1) * $per_page;
        $list         = $this->Reviu_model->get_antrian($filters, $per_page, $offset);
        $count_status = $this->Reviu_model->count_by_status($tahun, $filters['kabkota_id']);
        $kabkota_list = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];

        $this->render('reviu/index', array_merge($this->data, [
            'title'        => 'Reviu Inspektorat — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'count_status' => $count_status,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'reviu'],
        ]));
    }

    // ─── FORM REVIU + CHECKLIST ───────────────────────────────

    public function form($tahapan_id)
    {
        $this->requirePerm('reviu.input');

        // Ambil data tahapan + pekerjaan
        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }

        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        // Guard kabkota
        if ($this->role_kode === 'inspektorat'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('reviu'); return;
        }

        // Cek status valid untuk direviu atau lihat hasil reviu
        $reviu_selesai = $this->Reviu_model->get_by_tahapan($tahapan_id);
        $bisa_lihat    = $reviu_selesai && $reviu_selesai->hasil_reviu === 'disetujui';
        if (!in_array($tahapan->status, ['opd_input','inspektorat_reviu','inspektorat_revisi','inspektorat_approved'])
            && !$bisa_lihat) {
            $this->session->set_flashdata('error',
                'Tahapan ini tidak dalam status yang dapat direviu.');
            redirect('reviu'); return;
        }

        // Buat atau ambil record reviu
        $reviu_id = $this->Reviu_model->buat_atau_ambil($tahapan_id, $this->user_id);

        // Jika baru dibuat, set status tahapan → inspektorat_reviu
        if ($tahapan->status === 'opd_input') {
            $this->db->where('id', $tahapan_id)
                ->update('trx_tahapan_penyaluran', [
                    'status'     => 'inspektorat_reviu',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $this->Pekerjaan_model->set_status(
                $tahapan->pekerjaan_id,
                'inspektorat_reviu',
                $this->user_id,
                'Inspektorat mulai melakukan reviu'
            );
        }

        $reviu         = $this->Reviu_model->get_by_id($reviu_id);
        $items         = $this->Reviu_model->get_checklist_items(
                            $pekerjaan->jenis_penyaluran,
                            $tahapan->kode_tahap);
        $isian         = $this->Reviu_model->get_isian($reviu_id);
        $stat          = $this->Reviu_model->hitung_checklist($reviu_id);
        $dokumen       = $this->Pekerjaan_model->get_dokumen($tahapan_id);

        // Inspektur kab/kota (untuk dropdown TTD)
        $pejabat       = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
            'jenis'      => 'inspektur',
        ])->row();

        // Untuk Tahap II: ambil data capaian output OPD Teknis
        $capaian = NULL;
        if ($tahapan->kode_tahap === 'tahap_2') {
            $this->load->model('Capaian_model');
            $capaian = $this->Capaian_model->get_detail($tahapan->pekerjaan_id);
        }

        $this->render('reviu/form', array_merge($this->data, [
            'title'     => 'Reviu — ' . $pekerjaan->kode_bkp,
            'tahapan'   => $tahapan,
            'pekerjaan' => $pekerjaan,
            'reviu'     => $reviu,
            'items'     => $items,
            'isian'     => $isian,
            'stat'      => $stat,
            'dokumen'   => $dokumen,
            'pejabat'   => $pejabat,
            'capaian'   => $capaian,
        ]));
    }

    // ─── SIMPAN CHECKLIST (auto-save) ─────────────────────────

    public function simpan_checklist($reviu_id)
    {
        $this->requirePerm('reviu.input');

        $reviu = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu) { show_404(); return; }

        $tahapan_chk = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
        $pekerjaan_chk = $tahapan_chk ? $this->Pekerjaan_model->get_by_id($tahapan_chk->pekerjaan_id) : NULL;
        if ($pekerjaan_chk && $this->role_kode === 'inspektorat'
            && $pekerjaan_chk->kabkota_id != $this->kabkota_id) {
            $this->json(['ok' => FALSE, 'error' => 'Akses ditolak.']);
            return;
        }

        // Kumpulkan isian dari POST
        $raw_nilai   = $this->input->post('nilai')   ?? [];
        $raw_catatan = $this->input->post('catatan')  ?? [];

        $isian = [];
        foreach ($raw_nilai as $item_id => $nilai) {
            $isian[(int)$item_id] = [
                'nilai'   => $nilai,
                'catatan' => $raw_catatan[$item_id] ?? '',
            ];
        }

        $this->Reviu_model->simpan_checklist($reviu_id, $isian);
        $stat = $this->Reviu_model->hitung_checklist($reviu_id);

        // Simpan data reviewer jika dikirim
        $reviewer_nama    = $this->input->post('reviewer_nama', TRUE);
        $reviewer_nip     = $this->input->post('reviewer_nip',  TRUE);
        $reviewer_jabatan = $this->input->post('reviewer_jabatan', TRUE);
        if ($reviewer_nama || $reviewer_nip || $reviewer_jabatan) {
            $upd = array_filter([
                'reviewer_nama'    => $reviewer_nama    ?: NULL,
                'reviewer_nip'     => $reviewer_nip     ?: NULL,
                'reviewer_jabatan' => $reviewer_jabatan ?: NULL,
            ], fn($v) => $v !== NULL);
            if ($upd) $this->Reviu_model->update($reviu_id, $upd);
        }

        // Jika dari AJAX return JSON
        if ($this->input->is_ajax_request()) {
            $this->json(['ok' => TRUE, 'stat' => $stat]);
            return;
        }

        // Aksi "kunci checklist" — tidak bisa dibuka lagi
        $action = $this->input->post('action');
        if ($action === 'confirm') {
            $this->Reviu_model->update($reviu_id, [
                'checklist_confirmed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->log_aktivitas('reviu.kunci_checklist', 'Checklist reviu dikunci, reviu_id='.$reviu_id);
            $this->session->set_flashdata('success',
                'Checklist berhasil dikunci. Silakan cetak Kertas Kerja dan upload LHR.');
        } else {
            $this->session->set_flashdata('success', 'Checklist berhasil disimpan.');
        }

        redirect('reviu/form/' . $reviu->tahapan_id);
    }

    // ─── UPLOAD LHR ───────────────────────────────────────────

    public function upload_lhr($reviu_id)
    {
        $this->requirePerm('reviu.input');

        $reviu = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        if ($this->role_kode === 'inspektorat'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('reviu'); return;
        }

        // Upload file LHR
        $dir = FCPATH . 'uploads/lhr/' . $pekerjaan->id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $file_path = NULL;
        $orig_lhr  = NULL;
        if (!empty($_FILES['file_lhr']['name'])) {
            $mime_ok = ['application/pdf','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!$this->_mime_valid($_FILES['file_lhr']['tmp_name'], $mime_ok)) {
                $this->session->set_flashdata('error',
                    'Jenis file tidak diizinkan. LHR harus berformat PDF, DOC, atau DOCX.');
                redirect('reviu/form/' . $reviu->tahapan_id); return;
            }
            $orig_lhr  = basename($_FILES['file_lhr']['name']);
            $ext       = strtolower(pathinfo($orig_lhr, PATHINFO_EXTENSION));
            $rand_name = $this->_random_filename($ext);

            $this->load->library('upload', [
                'upload_path'   => $dir,
                'allowed_types' => 'pdf|doc|docx',
                'max_size'      => 10240,
                'file_name'     => $rand_name,
            ]);

            if (!$this->upload->do_upload('file_lhr')) {
                $this->session->set_flashdata('error',
                    'Upload LHR gagal: ' . $this->upload->display_errors('', ''));
                redirect('reviu/form/' . $reviu->tahapan_id); return;
            }
            $up        = $this->upload->data();
            $file_path = 'uploads/lhr/' . $pekerjaan->id . '/' . $up['file_name'];
        }

        $no_lhr           = $this->input->post('no_lhr', TRUE);
        $tgl_lhr          = $this->input->post('tgl_lhr', TRUE);
        $ref_inspektur_id = $this->input->post('ref_inspektur_id', TRUE) ?: NULL;

        $this->Reviu_model->update_lhr(
            $reviu_id,
            $no_lhr,
            $tgl_lhr ?: NULL,
            $file_path ?? $reviu->file_lhr_path,
            $ref_inspektur_id,
            $orig_lhr
        );

        // Juga simpan sebagai dokumen tahapan agar terlihat di tab dokumen
        if ($file_path) {
            $this->Pekerjaan_model->insert_dokumen([
                'tahapan_id'    => $reviu->tahapan_id,
                'jenis_dokumen' => 'laporan_reviu_inspektorat',
                'nama_file'     => basename($file_path),
                'nama_asli'     => $orig_lhr,
                'file_path'     => $file_path,
                'ukuran_kb'     => 0,
                'keterangan'    => 'LHR No. ' . $no_lhr,
                'user_upload'   => $this->user_id,
            ]);
        }

        $this->session->set_flashdata('success', 'LHR berhasil disimpan.');
        redirect('reviu/form/' . $reviu->tahapan_id);
    }

    // ─── KEPUTUSAN REVIU: SETUJUI / TOLAK / KEMBALIKAN ───────

    public function putuskan($reviu_id)
    {
        $this->requirePerm('reviu.approve');

        $reviu     = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);

        // Guard kabkota — inspektorat hanya bisa memutuskan reviu kabkota sendiri
        if ($this->role_kode === 'inspektorat'
            && $pekerjaan->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('reviu'); return;
        }

        // Guard transisi status — hanya bisa diputuskan saat tahapan sedang direviu
        if ($tahapan->status !== 'inspektorat_reviu') {
            $this->session->set_flashdata('error',
                'Tahapan ini tidak dalam status menunggu keputusan reviu.');
            redirect('reviu/form/' . $reviu->tahapan_id); return;
        }

        $hasil  = $this->input->post('hasil_reviu', TRUE);
        $catatan = $this->input->post('catatan', TRUE);

        if (!in_array($hasil, ['disetujui','ditolak','perlu_perbaikan'])) {
            $this->session->set_flashdata('error', 'Keputusan tidak valid.');
            redirect('reviu/form/' . $reviu->tahapan_id); return;
        }

        // Wajib ada LHR jika mau setujui
        if ($hasil === 'disetujui' && !$reviu->no_lhr) {
            $this->session->set_flashdata('error',
                'Nomor LHR wajib diisi sebelum menyetujui reviu.');
            redirect('reviu/form/' . $reviu->tahapan_id); return;
        }

        // Wajib ada catatan jika tolak/perlu perbaikan
        if (in_array($hasil, ['ditolak','perlu_perbaikan']) && empty($catatan)) {
            $this->session->set_flashdata('error',
                'Catatan wajib diisi jika hasil reviu adalah Ditolak atau Perlu Perbaikan.');
            redirect('reviu/form/' . $reviu->tahapan_id); return;
        }

        // Update record reviu
        $this->Reviu_model->update($reviu_id, [
            'hasil_reviu'      => $hasil,
            'catatan'          => $catatan,
            'tgl_reviu_selesai'=> date('Y-m-d'),
        ]);

        // Map keputusan → status tahapan & pekerjaan
        $status_map = [
            'disetujui'       => ['tahapan' => 'inspektorat_approved', 'pekerjaan' => 'inspektorat_approved'],
            'perlu_perbaikan' => ['tahapan' => 'inspektorat_revisi',   'pekerjaan' => 'inspektorat_revisi'],
            'ditolak'         => ['tahapan' => 'ditolak',              'pekerjaan' => 'ditolak'],
        ];

        $st = $status_map[$hasil];

        // Update status tahapan
        $this->db->where('id', $tahapan->id)
            ->update('trx_tahapan_penyaluran', [
                'status'     => $st['tahapan'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Update status pekerjaan
        $catatan_history = [
            'disetujui'       => 'Reviu Inspektorat: Disetujui. LHR No. ' . $reviu->no_lhr,
            'perlu_perbaikan' => 'Reviu Inspektorat: Perlu Perbaikan. ' . $catatan,
            'ditolak'         => 'Reviu Inspektorat: Ditolak. ' . $catatan,
        ];
        $this->Pekerjaan_model->set_status(
            $pekerjaan->id,
            $st['pekerjaan'],
            $this->user_id,
            $catatan_history[$hasil]
        );

        // Notifikasi ke OPD Teknis pembuat pekerjaan
        $pesan_map = [
            'disetujui'       => 'Reviu Inspektorat untuk BKP ' . $pekerjaan->kode_bkp . ' telah DISETUJUI. LHR No. ' . $reviu->no_lhr,
            'perlu_perbaikan' => 'Reviu Inspektorat untuk BKP ' . $pekerjaan->kode_bkp . ' dikembalikan. Catatan: ' . $catatan,
            'ditolak'         => 'Reviu Inspektorat untuk BKP ' . $pekerjaan->kode_bkp . ' DITOLAK. Catatan: ' . $catatan,
        ];
        $jenis_notif = ['disetujui'=>'sukses','perlu_perbaikan'=>'peringatan','ditolak'=>'error'];
        $this->Notifikasi_model->kirim(
            $pekerjaan->created_by,
            'Hasil Reviu Inspektorat',
            $pesan_map[$hasil],
            $jenis_notif[$hasil],
            site_url('pekerjaan/detail/' . $pekerjaan->id),
            $pekerjaan->id
        );

        // Jika disetujui, notifikasi ke SKPKD Kab/Kota
        if ($hasil === 'disetujui') {
            $skpkd_users = $this->db
                ->select('u.id')
                ->from('users u')->join('roles r','r.id = u.role_id')
                ->where('r.kode','skpkd_kabkota')
                ->where('u.kabkota_id', $pekerjaan->kabkota_id)
                ->where('u.is_active', 1)->get()->result();
            foreach ($skpkd_users as $su) {
                $this->Notifikasi_model->kirim(
                    $su->id,
                    'Reviu Selesai — Siap Verifikasi',
                    'BKP ' . $pekerjaan->kode_bkp . ' telah selesai direviu Inspektorat. Silakan lakukan verifikasi.',
                    'info',
                    site_url('pekerjaan/detail/' . $pekerjaan->id),
                    $pekerjaan->id
                );
            }

            // Telegram ke Admin Provinsi — reviu selesai, siap verifikasi provinsi
            telegram_notif_admin_prov(
                "\xE2\x9C\x85 <b>Reviu Inspektorat Selesai</b>\n\n" .
                "BKP: <b>" . htmlspecialchars($pekerjaan->kode_bkp) . "</b>\n" .
                htmlspecialchars($pekerjaan->uraian_bkp) . "\n" .
                "LHR: No. " . htmlspecialchars($reviu->no_lhr) . "\n\n" .
                "Pekerjaan siap untuk verifikasi SKPKD Provinsi."
            );
        }

        $pesan_flash = [
            'disetujui'       => 'Reviu disetujui. Status diperbarui ke Reviu Selesai.',
            'perlu_perbaikan' => 'Pekerjaan dikembalikan ke OPD untuk perbaikan.',
            'ditolak'         => 'Pekerjaan ditolak.',
        ];
        $this->log_aktivitas('reviu.putuskan',
            'Reviu id='.$reviu_id.' hasil='.$hasil.' pekerjaan_id='.$pekerjaan->id);
        $this->session->set_flashdata('success', $pesan_flash[$hasil]);
        redirect('reviu');
    }

    // ─── CETAK KERTAS KERJA ───────────────────────────────────

    public function cetak_kertas_kerja($reviu_id)
    {
        $this->requirePerm('reviu.cetak_kertas_kerja');

        $reviu     = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }
        $items     = $this->Reviu_model->get_checklist_items(
                        $pekerjaan->jenis_penyaluran, $tahapan->kode_tahap);
        $isian     = $this->Reviu_model->get_isian($reviu->id);
        $stat      = $this->Reviu_model->hitung_checklist($reviu->id);

        // Pejabat inspektur
        $pejabat = [];
        $rows = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
        ])->result();
        foreach ($rows as $p) $pejabat[$p->jenis] = $p;

        // Prioritas reviewer: GET param → data tersimpan di DB → data pejabat
        $insp = $pejabat['inspektur'] ?? NULL;
        $reviewer = [
            'nama'    => $this->input->get('nama',    TRUE)
                      ?: ($reviu->reviewer_nama    ?? ($insp->nama    ?? '')),
            'nip'     => $this->input->get('nip',     TRUE)
                      ?: ($reviu->reviewer_nip     ?? ($insp->nip     ?? '')),
            'jabatan' => $this->input->get('jabatan', TRUE)
                      ?: ($reviu->reviewer_jabatan ?? 'Inspektur'),
        ];

        $this->render_plain('reviu/cetak_kertas_kerja', [
            'reviu'    => $reviu,
            'tahapan'  => $tahapan,
            'pekerjaan'=> $pekerjaan,
            'items'    => $items,
            'isian'    => $isian,
            'stat'     => $stat,
            'pejabat'  => $pejabat,
            'reviewer' => $reviewer,
            'tgl_cetak'=> tgl_indo(date('Y-m-d')),
        ]);
    }

    // ─── CETAK REKAPITULASI HASIL REVIU ──────────────────────

    public function cetak_rekap($reviu_id)
    {
        $this->requirePerm('reviu.download_rekap');

        $reviu     = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }
        $items     = $this->Reviu_model->get_checklist_items(
                        $pekerjaan->jenis_penyaluran, $tahapan->kode_tahap);
        $isian     = $this->Reviu_model->get_isian($reviu->id);
        $stat      = $this->Reviu_model->hitung_checklist($reviu->id);

        $pejabat = [];
        $rows = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
        ])->result();
        foreach ($rows as $p) $pejabat[$p->jenis] = $p;

        $this->render_plain('reviu/cetak_rekap', [
            'reviu'     => $reviu,
            'tahapan'   => $tahapan,
            'pekerjaan' => $pekerjaan,
            'items'     => $items,
            'isian'     => $isian,
            'stat'      => $stat,
            'pejabat'   => $pejabat,
            'tgl_cetak' => tgl_indo(date('Y-m-d')),
        ]);
    }
}
