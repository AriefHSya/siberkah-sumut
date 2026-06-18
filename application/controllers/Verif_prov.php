<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verif_prov.php — Controller Verifikasi SKPKD Provinsi & Penyaluran Dana
 *
 * Menangani verifikasi final oleh Admin Provinsi/BKAD Provinsi,
 * input nomor SP2D, dan konfirmasi transfer dana ke RKUD Kab/Kota.
 *
 * ALUR:
 *   SKPKD Kab approve → status 'skpkd_kab_approved'
 *   → Admin Provinsi verifikasi → putuskan (disetujui/ditolak/revisi)
 *   → Input SP2D → konfirmasi transfer
 *   → SKPKD Kab konfirmasi penerimaan RKUD → selesai
 *
 * ROUTES:
 *   GET  /verifikasi/prov                   → index()              — antrian verifikasi
 *   GET  /verifikasi/prov/form/{id}         → form()               — form verifikasi provinsi
 *   POST /verifikasi/prov/putus/{id}        → putuskan()           — approve/tolak/revisi
 *   POST /verifikasi/prov/sp2d/{id}         → simpan_sp2d()        — input nomor SP2D
 *   POST /verifikasi/prov/transfer/{id}     → konfirmasi_transfer() — konfirmasi transfer dana
 *   GET  /verifikasi/prov/rekap             → cetak_rekap()        — cetak rekap penyaluran
 *
 * AKSES: Hanya admin_provinsi dan superadmin
 */
class Verif_prov extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('verif_prov.view');
        $this->load->model([
            'Verifikasi_prov_model',
            'Verifikasi_kab_model',
            'Pekerjaan_model',
            'Parameter_model',
            'Permohonan_model',
        ]);
        $this->data['active_menu'] = 'verif_prov';
    }

    // ─── DAFTAR PERMOHONAN MASUK ──────────────────────────────

    public function index()
    {
        $tahun    = $this->tahun;
        $per_page = 20;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tahun'      => $tahun,
            'kabkota_id' => $this->input->get('kabkota_id'),
            'jenis'      => $this->input->get('jenis'),
            'q'          => $this->input->get('q'),
        ];

        $total        = $this->Verifikasi_prov_model->count_permohonan_filtered($filters);
        $offset       = ($page - 1) * $per_page;
        $list         = $this->Verifikasi_prov_model->get_permohonan_list($filters, $per_page, $offset);
        $rekap        = $this->Verifikasi_prov_model->rekap_penyaluran($tahun);
        $kabkota_list = $this->Parameter_model->get_kabkota();

        $this->render('verif_prov/index', array_merge($this->data, [
            'title'        => 'Penyaluran Dana BKP — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'total'        => $total,
            'rekap'        => $rekap,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'verifikasi/prov'],
        ]));
    }

    // ─── DETAIL PERMOHONAN ────────────────────────────────────

    public function detail_permohonan($id)
    {
        $this->requirePerm('verif_prov.view');

        $pm = $this->Verifikasi_prov_model->get_permohonan_by_id($id);
        if (!$pm) { show_404(); return; }

        $items = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($id);

        $is_tahap2       = ($pm->jenis_penyaluran === 'bertahap' && $pm->kode_tahap === 'tahap_2');
        $is_tahap1       = ($pm->jenis_penyaluran === 'bertahap' && !$is_tahap2);
        $total_kontrak   = array_sum(array_column((array)$items, 'nilai_kontrak'));
        $total_tahap1    = $is_tahap1 ? array_sum(array_column((array)$items, 'nilai_diajukan')) : 0;
        $total_pendukung = array_sum(array_column((array)$items, 'nilai_belanja_pendukung'));
        $total_nilai     = array_sum(array_map(function($it) use ($is_tahap2) {
            // Tahap II: 50% × NK = nilai_diajukan (pendukung tidak termasuk)
            if ($is_tahap2) return ($it->nilai_diajukan ?? 0);
            return ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0);
        }, (array)$items));

        $this->render('verif_prov/detail_permohonan', array_merge($this->data, [
            'title'          => 'Permohonan — ' . ($pm->no_permohonan ?: '#'.$id),
            'pm'             => $pm,
            'items'          => $items,
            'is_tahap1'      => $is_tahap1,
            'is_tahap2'      => $is_tahap2,
            'total_kontrak'  => $total_kontrak,
            'total_tahap1'   => $total_tahap1,
            'total_pendukung'=> $total_pendukung,
            'total_nilai'    => $total_nilai,
        ]));
    }

    // ─── TOLAK PERMOHONAN (seluruh bundel) ────────────────────

    public function tolak_permohonan($pm_id)
    {
        $this->requirePerm('verif_prov.approve');

        $pm = $this->Verifikasi_prov_model->get_permohonan_by_id($pm_id);
        if (!$pm) { show_404(); return; }

        if ($pm->status !== 'diajukan') {
            $this->session->set_flashdata('error',
                'Hanya permohonan dengan status Diajukan yang dapat ditolak.');
            redirect('verifikasi/prov/permohonan/'.$pm_id); return;
        }

        $catatan = $this->input->post('catatan_tolak', TRUE);
        if (empty($catatan)) {
            $this->session->set_flashdata('error', 'Catatan alasan penolakan wajib diisi.');
            redirect('verifikasi/prov/permohonan/'.$pm_id); return;
        }

        // Tidak bisa ditolak jika verifikasi per-kegiatan sudah berjalan
        $items = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($pm_id);
        foreach ($items as $it) {
            if ($it->tahapan_status !== 'skpkd_kab_approved') {
                $this->session->set_flashdata('error',
                    'Permohonan tidak dapat ditolak karena verifikasi sudah berjalan untuk salah satu kegiatan di dalamnya. Proses kegiatan tersebut secara individual.');
                redirect('verifikasi/prov/permohonan/'.$pm_id); return;
            }
        }

        // Status diubah ke 'ditolak' (riwayat & item tetap tersimpan sebagai log).
        // Kegiatan di dalamnya menjadi eligible kembali untuk permohonan baru.
        $this->db->where('id', $pm_id)->update('trx_permohonan', [
            'status'        => 'ditolak',
            'catatan_tolak' => $catatan,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // Notifikasi + Telegram ke SKPKD Kab/Kota
        $skpkd_users = $this->db->select('u.id')->from('users u')
            ->join('roles r','r.id=u.role_id')
            ->where('r.kode','skpkd_kabkota')
            ->where('u.kabkota_id', $pm->kabkota_id)
            ->where('u.is_active',1)->get()->result();

        foreach ($skpkd_users as $su) {
            $this->Notifikasi_model->kirim(
                $su->id,
                'Permohonan Pencairan Ditolak',
                'Permohonan ' . ($pm->no_permohonan ?: '#'.$pm->id) . ' ditolak BKAD Provinsi. Catatan: ' . $catatan,
                'error',
                site_url('permohonan/detail/'.$pm->id),
                NULL
            );
        }

        telegram_notif_kabkota(
            $pm->kabkota_id,
            "\xE2\x9D\x8C <b>Permohonan Pencairan Ditolak</b>\n\n" .
            "No. Permohonan: <b>" . htmlspecialchars($pm->no_permohonan ?: '#'.$pm->id) . "</b>\n" .
            "Catatan: " . htmlspecialchars($catatan) . "\n\n" .
            "Buat permohonan baru untuk mengajukan kembali kegiatan yang dibutuhkan."
        );

        $this->log_aktivitas('verif_prov.tolak_permohonan',
            'Tolak permohonan id='.$pm_id.' no='.$pm->no_permohonan);
        $this->session->set_flashdata('success', 'Permohonan berhasil ditolak.');
        redirect('verifikasi/prov');
    }

    // ─── FORM VERIFIKASI + SP2D ───────────────────────────────

    public function form($tahapan_id)
    {
        $this->requirePerm('verif_prov.view');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }

        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        // Buat record verifikasi prov jika belum ada
        if ($this->rbac->can('verif_prov.approve')
            && $tahapan->status === 'skpkd_kab_approved') {
            $this->Verifikasi_prov_model->buat_atau_ambil_verif(
                $tahapan_id, $this->user_id);

            $this->db->where('id', $tahapan_id)
                ->update('trx_tahapan_penyaluran', [
                    'status'     => 'skpkd_prov_verif',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $this->Pekerjaan_model->set_status(
                $pekerjaan->id, 'skpkd_prov_verif', $this->user_id,
                'Admin Provinsi mulai verifikasi permohonan pencairan'
            );
            // Reload tahapan setelah update
            $tahapan = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        }

        $verif_prov = $this->Verifikasi_prov_model->get_verif_by_tahapan($tahapan_id);
        $verif_kab  = $this->Verifikasi_kab_model->get_by_tahapan($tahapan_id);
        $penyaluran = $this->Verifikasi_prov_model->get_penyaluran($tahapan_id);
        $reviu      = $this->db->get_where('trx_reviu_inspektorat',
                          ['tahapan_id' => $tahapan_id])->row();
        $dokumen    = $this->Verifikasi_kab_model->get_dokumen($tahapan_id);
        $history    = $this->Pekerjaan_model->get_status_history($pekerjaan->id);

        // Pejabat untuk TTD SP2D
        $pejabat_kab = [];
        $rows = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $pekerjaan->kabkota_id,
            'tahun'      => $pekerjaan->tahun,
        ])->result();
        foreach ($rows as $p) $pejabat_kab[$p->jenis] = $p;

        $this->render('verif_prov/form', array_merge($this->data, [
            'title'       => 'Verifikasi Provinsi — ' . $pekerjaan->kode_bkp,
            'tahapan'     => $tahapan,
            'pekerjaan'   => $pekerjaan,
            'verif_prov'  => $verif_prov,
            'verif_kab'   => $verif_kab,
            'penyaluran'  => $penyaluran,
            'reviu'       => $reviu,
            'dokumen'     => $dokumen,
            'history'     => $history,
            'pejabat_kab' => $pejabat_kab,
        ]));
    }

    // ─── KEPUTUSAN VERIFIKASI PROVINSI ───────────────────────

    public function putuskan($verif_id)
    {
        $this->requirePerm('verif_prov.approve');

        $verif_prov = $this->Verifikasi_prov_model->get_verif_by_id($verif_id);
        if (!$verif_prov) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($verif_prov->tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
        if (!$pekerjaan) { show_404(); return; }

        // Guard transisi status — hanya bisa diputuskan saat tahapan sedang diverifikasi provinsi
        // dan belum pernah diputuskan (status tetap 'skpkd_prov_verif' setelah disetujui,
        // sehingga hasil_verifikasi yang menandai keputusan sudah diambil).
        if ($tahapan->status !== 'skpkd_prov_verif' || $verif_prov->hasil_verifikasi === 'disetujui') {
            $this->session->set_flashdata('error',
                'Tahapan ini tidak dalam status menunggu keputusan verifikasi provinsi.');
            redirect('verifikasi/prov/form/' . $tahapan->id); return;
        }

        $hasil   = $this->input->post('hasil_verifikasi', TRUE);
        $catatan = $this->input->post('catatan', TRUE);

        if (!in_array($hasil, ['disetujui','ditolak','perlu_perbaikan'])) {
            $this->session->set_flashdata('error', 'Keputusan tidak valid.');
            redirect('verifikasi/prov/form/' . $tahapan->id); return;
        }
        if (in_array($hasil, ['ditolak','perlu_perbaikan']) && empty($catatan)) {
            $this->session->set_flashdata('error',
                'Catatan wajib diisi jika hasil adalah Ditolak atau Perlu Perbaikan.');
            redirect('verifikasi/prov/form/' . $tahapan->id); return;
        }

        $this->Verifikasi_prov_model->update_verif($verif_id, [
            'hasil_verifikasi' => $hasil,
            'catatan'          => $catatan,
            'tgl_verifikasi'   => date('Y-m-d'),
        ]);

        $status_map = [
            'disetujui'       => ['tahapan'=>'skpkd_prov_verif', 'pekerjaan'=>'skpkd_prov_verif'],
            'perlu_perbaikan' => ['tahapan'=>'skpkd_prov_revisi','pekerjaan'=>'skpkd_prov_revisi'],
            'ditolak'         => ['tahapan'=>'ditolak',          'pekerjaan'=>'ditolak'],
        ];
        // Disetujui: status TETAP di skpkd_prov_verif (menunggu SP2D)
        // Perlu perbaikan: kembalikan ke Kab
        $st = $status_map[$hasil];
        if ($hasil !== 'disetujui') {
            $this->db->where('id', $tahapan->id)
                ->update('trx_tahapan_penyaluran', [
                    'status'     => $st['tahapan'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $ket = [
                'perlu_perbaikan' => 'Verifikasi Provinsi: Perlu Perbaikan. '.$catatan,
                'ditolak'         => 'Verifikasi Provinsi: Ditolak. '.$catatan,
            ];
            $this->Pekerjaan_model->set_status(
                $pekerjaan->id, $st['pekerjaan'],
                $this->user_id, $ket[$hasil] ?? $hasil
            );
        }

        // Notifikasi SKPKD Kab/Kota
        $jenis_n = ['disetujui'=>'sukses','perlu_perbaikan'=>'peringatan','ditolak'=>'error'];
        $pesan_n = [
            'disetujui'       => 'Permohonan BKP '.$pekerjaan->kode_bkp.' disetujui Provinsi. SP2D sedang diproses.',
            'perlu_perbaikan' => 'Permohonan BKP '.$pekerjaan->kode_bkp.' dikembalikan Provinsi. '.$catatan,
            'ditolak'         => 'Permohonan BKP '.$pekerjaan->kode_bkp.' DITOLAK Provinsi. '.$catatan,
        ];
        $skpkd_users = $this->db->select('u.id')->from('users u')
            ->join('roles r','r.id=u.role_id')
            ->where('r.kode','skpkd_kabkota')
            ->where('u.kabkota_id', $pekerjaan->kabkota_id)
            ->where('u.is_active',1)->get()->result();
        foreach ($skpkd_users as $su) {
            $this->Notifikasi_model->kirim(
                $su->id, 'Verifikasi Provinsi',
                $pesan_n[$hasil], $jenis_n[$hasil],
                site_url('verifikasi/kab/form/'.$tahapan->id),
                $pekerjaan->id
            );
        }

        // Telegram ke SKPKD Kab berdasarkan hasil verifikasi provinsi
        $tg_msg_map = [
            'disetujui'       => "\xE2\x9C\x85 <b>Permohonan Disetujui Provinsi</b>\n\nBKP: <b>" . htmlspecialchars($pekerjaan->kode_bkp) . "</b>\n" . htmlspecialchars($pekerjaan->uraian_bkp) . "\n\nSP2D sedang diproses oleh Provinsi.",
            'perlu_perbaikan' => "\xE2\x9A\xA0\xEF\xB8\x8F <b>Permohonan Dikembalikan</b>\n\nBKP: <b>" . htmlspecialchars($pekerjaan->kode_bkp) . "</b>\nCatatan: " . htmlspecialchars($catatan),
            'ditolak'         => "\xE2\x9D\x8C <b>Permohonan Ditolak Provinsi</b>\n\nBKP: <b>" . htmlspecialchars($pekerjaan->kode_bkp) . "</b>\nCatatan: " . htmlspecialchars($catatan),
        ];
        telegram_notif_kabkota($pekerjaan->kabkota_id, $tg_msg_map[$hasil]);

        $flash = [
            'disetujui'       => 'Verifikasi disetujui. Lanjutkan dengan menginput data SP2D.',
            'perlu_perbaikan' => 'Dikembalikan ke SKPKD Kab/Kota untuk perbaikan.',
            'ditolak'         => 'Permohonan ditolak.',
        ];
        $this->log_aktivitas('verif_prov.approve', 'hasil='.$hasil.' pekerjaan='.$pekerjaan->id);
        $this->session->set_flashdata('success', $flash[$hasil]);
        redirect('verifikasi/prov/form/' . $tahapan->id);
    }

    // ─── INPUT SP2D + PENYALURAN DANA ────────────────────────

    public function simpan_sp2d($tahapan_id)
    {
        $this->requirePerm('penyaluran.input_sp2d');

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($tahapan_id);
        if (!$tahapan) { show_404(); return; }
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);

        // Cek verifikasi prov sudah disetujui
        $verif_prov = $this->Verifikasi_prov_model->get_verif_by_tahapan($tahapan_id);
        if (!$verif_prov || $verif_prov->hasil_verifikasi !== 'disetujui') {
            $this->session->set_flashdata('error',
                'Verifikasi Provinsi harus disetujui terlebih dahulu sebelum input SP2D.');
            redirect('verifikasi/prov/form/' . $tahapan_id); return;
        }

        $nilai_transfer = (int)str_replace(['.','Rp',' ',','], '',
            $this->input->post('nilai_transfer'));

        if (empty($this->input->post('no_sp2d')) || $nilai_transfer <= 0) {
            $this->session->set_flashdata('error',
                'Nomor SP2D dan Nilai Transfer wajib diisi.');
            redirect('verifikasi/prov/form/' . $tahapan_id); return;
        }

        $data = [
            'no_sp2d'         => $this->input->post('no_sp2d', TRUE),
            'tgl_sp2d'        => $this->input->post('tgl_sp2d', TRUE),
            'nilai_transfer'  => $nilai_transfer,
            'rek_asal'        => $this->input->post('rek_asal', TRUE),
            'nama_bank_asal'  => $this->input->post('nama_bank_asal', TRUE) ?: 'Bank Sumut',
            'rek_tujuan'      => $this->input->post('rek_tujuan', TRUE),
            'nama_bank_tujuan'=> $this->input->post('nama_bank_tujuan', TRUE),
            'status_transfer' => $this->input->post('status_transfer', TRUE) ?: 'proses',
            'keterangan'      => $this->input->post('keterangan', TRUE),
        ];

        $penyaluran_id = $this->Verifikasi_prov_model->simpan_sp2d(
            $tahapan_id, $data, $this->user_id);

        // Jika status_transfer = selesai → set tahapan & pekerjaan → disalurkan
        if ($data['status_transfer'] === 'selesai') {
            $this->_set_disalurkan($tahapan, $pekerjaan, $data['no_sp2d']);
        }

        $this->log_aktivitas('penyaluran.input_sp2d',
            'SP2D '.$data['no_sp2d'].' tahapan='.$tahapan_id);
        $this->session->set_flashdata('success',
            'Data SP2D berhasil disimpan.' .
            ($data['status_transfer'] === 'selesai'
                ? ' Status diperbarui: Dana Disalurkan.' : ' Update status ke "Selesai" untuk memproses penyaluran.'));
        redirect('verifikasi/prov/form/' . $tahapan_id);
    }

    // ─── KONFIRMASI TRANSFER (ubah status_transfer) ───────────

    public function konfirmasi_transfer($penyaluran_id)
    {
        $this->requirePerm('penyaluran.input_sp2d');

        $penyaluran = $this->db->get_where('trx_penyaluran_dana',
            ['id' => $penyaluran_id])->row();
        if (!$penyaluran) { show_404(); return; }

        $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($penyaluran->tahapan_id);
        $pekerjaan = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);

        // Guard idempotensi — transfer yang sudah selesai tidak boleh dikonfirmasi ulang
        if ($penyaluran->status_transfer === 'selesai') {
            $this->session->set_flashdata('warning', 'Transfer ini sudah dikonfirmasi selesai sebelumnya.');
            redirect('verifikasi/prov/form/' . $tahapan->id); return;
        }

        $this->Verifikasi_prov_model->update_status_transfer($penyaluran_id, 'selesai');
        $this->_set_disalurkan($tahapan, $pekerjaan, $penyaluran->no_sp2d);

        $this->log_aktivitas('penyaluran.konfirmasi_transfer',
            'Penyaluran id='.$penyaluran_id.' SP2D='.$penyaluran->no_sp2d);
        $this->session->set_flashdata('success',
            'Transfer dikonfirmasi selesai. Status pekerjaan diperbarui ke Dana Disalurkan.');
        redirect('verifikasi/prov/form/' . $tahapan->id);
    }

    /**
     * Helper: set status tahapan → disalurkan, pekerjaan → disalurkan_tahap1/sekaligus
     * + kirim notifikasi SKPKD Kab/Kota
     */
    private function _set_disalurkan($tahapan, $pekerjaan, $no_sp2d)
    {
        $this->db->where('id', $tahapan->id)
            ->update('trx_tahapan_penyaluran', [
                'status'        => 'disalurkan',
                'tgl_disalurkan'=> date('Y-m-d'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        // Tentukan status pekerjaan berdasarkan jenis
        if ($pekerjaan->jenis_penyaluran === 'bertahap') {
            $st_pekerjaan = ($tahapan->kode_tahap === 'tahap_1')
                ? 'disalurkan_tahap1' : 'disalurkan_tahap2';
        } else {
            $st_pekerjaan = 'disalurkan_sekaligus';
        }

        $this->Pekerjaan_model->set_status(
            $pekerjaan->id, $st_pekerjaan, $this->user_id,
            'Dana disalurkan. SP2D No. '.$no_sp2d
        );

        // Notifikasi SKPKD Kab/Kota
        $skpkd_users = $this->db->select('u.id')->from('users u')
            ->join('roles r','r.id=u.role_id')
            ->where('r.kode','skpkd_kabkota')
            ->where('u.kabkota_id', $pekerjaan->kabkota_id)
            ->where('u.is_active',1)->get()->result();

        foreach ($skpkd_users as $su) {
            $this->Notifikasi_model->kirim(
                $su->id,
                'Dana BKP Disalurkan',
                'Dana BKP '.$pekerjaan->kode_bkp.' ('.$tahapan->label_tahap.') telah disalurkan via SP2D No. '.$no_sp2d.'. Konfirmasi penerimaan RKUD.',
                'sukses',
                site_url('verifikasi/kab/form/'.$tahapan->id),
                $pekerjaan->id
            );
        }
        // Notifikasi OPD Teknis
        $this->Notifikasi_model->kirim(
            $pekerjaan->created_by,
            'Dana BKP Disalurkan',
            'Dana BKP '.$pekerjaan->kode_bkp.' telah disalurkan Provinsi. SP2D No. '.$no_sp2d,
            'sukses',
            site_url('pekerjaan/detail/'.$pekerjaan->id),
            $pekerjaan->id
        );

        // Telegram ke SKPKD Kab — dana sudah disalurkan, harap konfirmasi RKUD
        telegram_notif_kabkota(
            $pekerjaan->kabkota_id,
            "\xF0\x9F\x92\xB0 <b>Dana BKP Disalurkan</b>\n\n" .
            "BKP: <b>" . htmlspecialchars($pekerjaan->kode_bkp) . "</b>\n" .
            htmlspecialchars($pekerjaan->uraian_bkp) . "\n" .
            "SP2D: No. <b>" . htmlspecialchars($no_sp2d) . "</b>\n\n" .
            "Silakan konfirmasi penerimaan dana di RKUD."
        );
    }

    // ─── NOTA & RINGKASAN (per permohonan) ───────────────────

    private function _get_pm_for_cetak($pm_id)
    {
        $pm = $this->Verifikasi_prov_model->get_permohonan_by_id($pm_id);
        if (!$pm || !in_array($pm->status, ['diajukan', 'selesai'])) { show_404(); return NULL; }
        return $pm;
    }

    public function cetak_nota_kabid($pm_id)
    {
        $this->requirePerm('verif_prov.approve');
        $pm = $this->_get_pm_for_cetak($pm_id);
        if (!$pm) return;

        $items    = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($pm_id);
        $pejabat  = $this->Parameter_model->get_pejabat_bkad_prov($pm->tahun);
        $tgl_nota = tgl_indo(date('Y-m-d'));

        if (empty($pm->nota_kabid_at)) {
            $this->db->where('id', $pm_id)
                ->update('trx_permohonan', ['nota_kabid_at' => date('Y-m-d H:i:s')]);
        }

        $this->render_plain('verif_prov/cetak_nota_kabid', [
            'pm'        => $pm,
            'items'     => $items,
            'pejabat'   => $pejabat,
            'tgl_nota'  => $tgl_nota,
            'logo_prov' => $this->_get_logo_prov(),
        ]);
    }

    public function cetak_nota_kabadan($pm_id)
    {
        $this->requirePerm('verif_prov.approve');
        $pm = $this->_get_pm_for_cetak($pm_id);
        if (!$pm) return;

        $items    = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($pm_id);
        $pejabat  = $this->Parameter_model->get_pejabat_bkad_prov($pm->tahun);
        $tgl_nota = tgl_indo(date('Y-m-d'));

        if (empty($pm->nota_kabadan_at)) {
            $this->db->where('id', $pm_id)
                ->update('trx_permohonan', ['nota_kabadan_at' => date('Y-m-d H:i:s')]);
        }

        $this->render_plain('verif_prov/cetak_nota_kabadan', [
            'pm'        => $pm,
            'items'     => $items,
            'pejabat'   => $pejabat,
            'tgl_nota'  => $tgl_nota,
            'logo_prov' => $this->_get_logo_prov(),
        ]);
    }

    public function cetak_ringkasan($pm_id)
    {
        $this->requirePerm('verif_prov.approve');
        $pm = $this->_get_pm_for_cetak($pm_id);
        if (!$pm) return;

        $items     = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($pm_id);
        $pejabat   = $this->Parameter_model->get_pejabat_bkad_prov($pm->tahun);
        $tgl_cetak = tgl_indo(date('Y-m-d'));

        if (empty($pm->ringkasan_at)) {
            $this->db->where('id', $pm_id)
                ->update('trx_permohonan', ['ringkasan_at' => date('Y-m-d H:i:s')]);
        }

        $this->render_plain('verif_prov/cetak_ringkasan', [
            'pm'        => $pm,
            'items'     => $items,
            'pejabat'   => $pejabat,
            'tgl_cetak' => $tgl_cetak,
            'logo_prov' => $this->_get_logo_prov(),
        ]);
    }

    private function _get_logo_prov()
    {
        $row = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        return ($row && !empty($row->nilai)) ? base_url($row->nilai) : '';
    }

    // ─── SP2D KUMULATIF (per permohonan) ─────────────────────

    public function simpan_sp2d_permohonan($pm_id)
    {
        $this->requirePerm('penyaluran.input_sp2d');

        $pm = $this->Verifikasi_prov_model->get_permohonan_by_id($pm_id);
        if (!$pm) { show_404(); return; }

        // Guard idempotensi — SP2D yang sudah selesai tidak boleh diproses ulang
        if ($pm->status_sp2d === 'selesai') {
            $this->session->set_flashdata('warning', 'SP2D untuk permohonan ini sudah selesai diproses sebelumnya.');
            redirect('verifikasi/prov/permohonan/'.$pm_id); return;
        }

        // Validasi: semua nota sudah digenerate
        if (empty($pm->nota_kabid_at) || empty($pm->nota_kabadan_at) || empty($pm->ringkasan_at)) {
            $this->session->set_flashdata('error',
                'Harap download semua dokumen (Nota + Ringkasan) terlebih dahulu sebelum input SP2D.');
            redirect('verifikasi/prov/permohonan/'.$pm_id); return;
        }

        $nilai = (int)str_replace(['.','Rp',' ',','], '',
            $this->input->post('nilai_sp2d'));
        $no_sp2d = $this->input->post('no_sp2d', TRUE);

        if (empty($no_sp2d) || $nilai <= 0) {
            $this->session->set_flashdata('error', 'Nomor SP2D dan Nilai Transfer wajib diisi.');
            redirect('verifikasi/prov/permohonan/'.$pm_id); return;
        }

        $status_sp2d = $this->input->post('status_sp2d', TRUE) ?: 'proses';

        $tgl_sp2d    = $this->input->post('tgl_sp2d', TRUE);
        $rek_asal    = $this->input->post('rek_asal', TRUE);
        $bank_asal   = $this->input->post('nama_bank_asal', TRUE) ?: 'Bank Sumut';
        $rek_tujuan  = $this->input->post('rek_tujuan', TRUE);
        $bank_tujuan = $this->input->post('nama_bank_tujuan', TRUE);

        $this->db->where('id', $pm_id)->update('trx_permohonan', [
            'no_sp2d'          => $no_sp2d,
            'tgl_sp2d'         => $tgl_sp2d,
            'nilai_sp2d'       => $nilai,
            'rek_asal'         => $rek_asal,
            'nama_bank_asal'   => $bank_asal,
            'rek_tujuan'       => $rek_tujuan,
            'nama_bank_tujuan' => $bank_tujuan,
            'status_sp2d'      => $status_sp2d,
        ]);

        // Sync ke trx_penyaluran_dana per-tahapan (agar dashboard & laporan tetap akurat)
        $items = $this->Verifikasi_prov_model->get_permohonan_items_for_prov($pm_id);
        $status_transfer = ($status_sp2d === 'selesai') ? 'selesai' : 'proses';
        $dilewati = [];
        foreach ($items as $item) {
            // Cek status_transfer SEBELUM diupdate — cegah _set_disalurkan() terpanggil
            // ulang (notifikasi/Telegram dobel) untuk tahapan yang sudah disalurkan
            $penyaluran_lama = $this->Verifikasi_prov_model->get_penyaluran($item->tahapan_id);
            $sudah_disalurkan = $penyaluran_lama && $penyaluran_lama->status_transfer === 'selesai';

            $is_item_t2 = ($item->jenis_penyaluran === 'bertahap' && $item->kode_tahap === 'tahap_2');
            $nilai_item = $is_item_t2
                ? ($item->nilai_diajukan ?? 0)
                : ($item->nilai_diajukan ?? 0) + ($item->nilai_belanja_pendukung ?? 0);
            $this->Verifikasi_prov_model->simpan_sp2d($item->tahapan_id, [
                'no_sp2d'          => $no_sp2d,
                'tgl_sp2d'         => $tgl_sp2d,
                'nilai_transfer'   => $nilai_item,
                'rek_asal'         => $rek_asal,
                'nama_bank_asal'   => $bank_asal,
                'rek_tujuan'       => $rek_tujuan,
                'nama_bank_tujuan' => $bank_tujuan,
                'status_transfer'  => $status_transfer,
            ], $this->user_id);

            if ($status_sp2d === 'selesai' && !$sudah_disalurkan) {
                // Hanya item yang sudah disetujui verifikasi provinsi (status tahapan
                // 'skpkd_prov_verif' dan hasil_verifikasi='disetujui') boleh dilanjutkan
                // ke 'disalurkan'. Item yang masih perlu_perbaikan/ditolak/belum
                // diputuskan dilewati agar tidak melompati alur verifikasi.
                $item_disetujui = ($item->tahapan_status === 'skpkd_prov_verif'
                                    && $item->hasil_verif_prov === 'disetujui');
                if (!$item_disetujui) {
                    $dilewati[] = $item->kode_bkp;
                    continue;
                }

                $tahapan   = $this->Pekerjaan_model->get_tahapan_by_id($item->tahapan_id);
                $pekerjaan = $this->Pekerjaan_model->get_by_id($item->pekerjaan_id);
                if ($tahapan && $pekerjaan) {
                    $this->_set_disalurkan($tahapan, $pekerjaan, $no_sp2d);
                }
            }
        }

        $this->log_aktivitas('penyaluran.sp2d_permohonan',
            'SP2D '.$no_sp2d.' permohonan='.$pm_id.' status='.$status_sp2d);

        $pesan = 'SP2D berhasil disimpan.';
        if ($status_sp2d === 'selesai') {
            if (empty($dilewati)) {
                $pesan .= ' Seluruh pekerjaan dalam permohonan ini telah disalurkan.';
            } else {
                $pesan .= ' Sebagian pekerjaan belum disalurkan karena belum disetujui '.
                    'verifikasi provinsi: ' . implode(', ', $dilewati) . '.';
            }
        }
        $this->session->set_flashdata(empty($dilewati) ? 'success' : 'warning', $pesan);
        redirect('verifikasi/prov/permohonan/'.$pm_id);
    }

    // ─── CETAK REKAP PENYALURAN ───────────────────────────────

    public function cetak_rekap()
    {
        $this->requirePerm('laporan.cetak_rekap_penyaluran');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');

        $daftar_sp2d = $this->Verifikasi_prov_model->get_daftar_sp2d($tahun, $kabkota_id);
        foreach ($daftar_sp2d as $row) {
            $row->items = $this->Verifikasi_prov_model->get_items_ringkas($row->id);
        }
        $rekap       = $this->Verifikasi_prov_model->rekap_penyaluran($tahun);
        $kabkota     = $kabkota_id
            ? $this->db->get_where('ref_kabkota', ['id'=>$kabkota_id])->row() : NULL;

        $this->render_plain('verif_prov/cetak_rekap_penyaluran', [
            'daftar_sp2d' => $daftar_sp2d,
            'rekap'       => $rekap,
            'tahun'       => $tahun,
            'kabkota'     => $kabkota,
            'kabkota_list'=> $this->Parameter_model->get_kabkota(),
            'tgl_cetak'   => tgl_indo(date('Y-m-d')),
        ]);
    }
}
