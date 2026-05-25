<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permohonan.php — Permohonan Pencairan BKP ke BKAD Provinsi
 *
 * SKPKD Kab/Kota mengelompokkan pekerjaan terverifikasi berdasarkan jenis
 * penyaluran + kode tahap, lalu mengajukan sebagai satu permohonan resmi
 * kepada BKAD Provinsi.
 *
 * ROUTES:
 *   GET  /permohonan                         → index()          — daftar permohonan
 *   GET  /permohonan/buat                    → buat()           — form buat
 *   POST /permohonan/simpan                  → simpan()         — simpan sebagai draft
 *   GET  /permohonan/detail/(:num)           → detail()         — detail permohonan
 *   POST /permohonan/batal/(:num)            → batal()          — batalkan pengajuan
 *   POST /permohonan/ajukan-kembali/(:num)   → ajukan_kembali() — ajukan ke provinsi
 *   GET  /permohonan/cetak/(:num)            → cetak()          — cetak rekap
 *   POST /permohonan/upload-dok/(:num)/(:any)→ upload_dok()     — upload surat
 *   POST /permohonan/hapus-dok/(:num)/(:any) → hapus_dok()      — hapus surat
 */
class Permohonan extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('permohonan.view');
        $this->load->model(['Permohonan_model', 'Pekerjaan_model', 'Parameter_model']);
        $this->data['active_menu'] = 'permohonan';
    }

    // ─── DAFTAR PERMOHONAN ────────────────────────────────────────

    public function index()
    {
        $tahun    = $this->tahun;
        $per_page = 15;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tahun'      => $tahun,
            'kabkota_id' => $this->input->get('kabkota_id'),
            'status'     => $this->input->get('status'),
            'jenis'      => $this->input->get('jenis'),
        ];

        if ($this->rbac->isKabkota()) {
            $filters['kabkota_id'] = (int)$this->kabkota_id ?: -1;
        }

        $total        = $this->Permohonan_model->count_filtered($filters);
        $offset       = ($page - 1) * $per_page;
        $list         = $this->Permohonan_model->get_all($filters, $per_page, $offset);
        $kabkota_list = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];

        $this->render('permohonan/index', array_merge($this->data, [
            'title'        => 'Permohonan Pencairan — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'permohonan'],
        ]));
    }

    // ─── FORM BUAT PERMOHONAN ─────────────────────────────────────

    public function buat()
    {
        $this->requirePerm('permohonan.create');

        if (!$this->rbac->isKabkota()) {
            $this->session->set_flashdata('error', 'Fitur ini hanya tersedia untuk akun SKPKD Kab/Kota.');
            redirect('permohonan'); return;
        }

        $kabkota_id = (int)$this->kabkota_id;
        $tahun      = $this->tahun;
        $jenis      = $this->input->get('jenis', TRUE);
        $kode_tahap = $this->input->get('kode_tahap', TRUE);

        $kelompok = $this->Permohonan_model->get_kelompok_tersedia($kabkota_id, $tahun);

        $eligible = [];
        if ($jenis && $kode_tahap) {
            $eligible = $this->Permohonan_model->get_eligible($kabkota_id, $tahun, $jenis, $kode_tahap);
        }

        $this->render('permohonan/form_buat', array_merge($this->data, [
            'title'      => 'Buat Permohonan Pencairan',
            'kelompok'   => $kelompok,
            'eligible'   => $eligible,
            'jenis'      => $jenis,
            'kode_tahap' => $kode_tahap,
            'tahun'      => $tahun,
        ]));
    }

    // ─── SIMPAN / AJUKAN ──────────────────────────────────────────

    public function simpan()
    {
        $this->requirePerm('permohonan.create');

        if (!$this->rbac->isKabkota()) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan'); return;
        }

        $jenis          = $this->input->post('jenis_penyaluran', TRUE);
        $kode_tahap     = $this->input->post('kode_tahap', TRUE);
        $tahapan_ids    = $this->input->post('tahapan_ids') ?: [];
        $no_permohonan  = $this->input->post('no_permohonan', TRUE);
        $tgl_permohonan = $this->input->post('tgl_permohonan', TRUE);
        $catatan        = $this->input->post('catatan', TRUE);
        $kabkota_id     = (int)$this->kabkota_id;
        $tahun          = $this->tahun;

        $redirect_back = 'permohonan/buat?jenis='.$jenis.'&kode_tahap='.$kode_tahap;

        if (empty($tahapan_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal 1 pekerjaan.');
            redirect($redirect_back); return;
        }

        if (empty($no_permohonan) || empty($tgl_permohonan)) {
            $this->session->set_flashdata('error', 'Nomor permohonan dan tanggal wajib diisi.');
            redirect($redirect_back); return;
        }

        // Validasi setiap tahapan_id masih eligible (belum masuk permohonan lain)
        $eligible     = $this->Permohonan_model->get_eligible($kabkota_id, $tahun, $jenis, $kode_tahap);
        $eligible_ids = array_map('intval', array_column($eligible, 'tahapan_id'));
        $valid_ids    = array_filter($tahapan_ids, function($id) use ($eligible_ids) {
            return in_array((int)$id, $eligible_ids);
        });

        if (empty($valid_ids)) {
            $this->session->set_flashdata('error', 'Tidak ada pekerjaan valid yang dipilih.');
            redirect($redirect_back); return;
        }

        $permohonan_id = $this->Permohonan_model->create([
            'kabkota_id'       => $kabkota_id,
            'tahun'            => $tahun,
            'jenis_penyaluran' => $jenis,
            'kode_tahap'       => $kode_tahap,
            'no_permohonan'    => $no_permohonan,
            'tgl_permohonan'   => $tgl_permohonan,
            'catatan'          => $catatan ?: NULL,
            'status'           => 'draft',
            'created_by'       => $this->user_id,
            'created_at'       => date('Y-m-d H:i:s'),
        ], array_values($valid_ids));

        $this->log_aktivitas('permohonan.buat',
            'Permohonan id='.$permohonan_id.' jenis='.$jenis.' kode_tahap='.$kode_tahap
            .' items='.count($valid_ids));
        $this->session->set_flashdata('success',
            'Permohonan berhasil disimpan sebagai Draft. Lengkapi dokumen dan klik Ajukan untuk mengirim ke BKAD Provinsi.');
        redirect('permohonan/detail/' . $permohonan_id);
    }

    // ─── CETAK REKAP ──────────────────────────────────────────────

    public function cetak($id)
    {
        $this->requirePerm('permohonan.view');

        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan'); return;
        }

        $items = $this->Permohonan_model->get_items($id);

        $ppkd = $this->db->get_where('ref_pemda_pejabat', [
            'kabkota_id' => $permohonan->kabkota_id,
            'tahun'      => $permohonan->tahun,
            'jenis'      => 'kepala_bkad',
        ])->row();

        $this->render_plain('permohonan/cetak_rekap', [
            'permohonan' => $permohonan,
            'items'      => $items,
            'ppkd'       => $ppkd,
            'tgl_cetak'  => tgl_indo(date('Y-m-d')),
        ]);
    }

    // ─── UPLOAD DOKUMEN ───────────────────────────────────────────

    public function upload_dok($id, $jenis)
    {
        $this->requirePerm('permohonan.create');

        $allowed = ['surat_permohonan', 'surat_pernyataan', 'rekap_kegiatan'];
        if (!in_array($jenis, $allowed)) { show_404(); return; }

        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan/detail/' . $id); return;
        }

        if ($permohonan->status !== 'draft') {
            $this->session->set_flashdata('error', 'Dokumen hanya bisa diupload saat status Draft.');
            redirect('permohonan/detail/' . $id); return;
        }

        $kolom = 'file_' . $jenis . '_path';
        $dir   = FCPATH . 'uploads/permohonan/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $dir,
            'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
            'max_size'      => 10240,
            'file_name'     => $jenis . '_' . $id . '_' . time(),
        ]);

        if (!$this->upload->do_upload('file_dok')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('',''));
            redirect('permohonan/detail/' . $id); return;
        }

        $fi        = $this->upload->data();
        $file_path = 'uploads/permohonan/' . $id . '/' . $fi['file_name'];

        // Hapus file lama
        $lama = $permohonan->$kolom ?? NULL;
        if ($lama && file_exists(FCPATH . $lama)) @unlink(FCPATH . $lama);

        $this->db->where('id', $id)->update('trx_permohonan', [
            $kolom       => $file_path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log_aktivitas('permohonan.upload_dok', 'Upload '.$jenis.' permohonan id='.$id);
        $this->session->set_flashdata('success', 'Dokumen berhasil diupload.');
        redirect('permohonan/detail/' . $id);
    }

    // ─── HAPUS DOKUMEN ────────────────────────────────────────────

    public function hapus_dok($id, $jenis)
    {
        $this->requirePerm('permohonan.create');

        $allowed = ['surat_permohonan', 'surat_pernyataan', 'rekap_kegiatan'];
        if (!in_array($jenis, $allowed)) { show_404(); return; }

        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan/detail/' . $id); return;
        }

        $kolom = 'file_' . $jenis . '_path';
        $lama  = $permohonan->$kolom ?? NULL;
        if ($lama && file_exists(FCPATH . $lama)) @unlink(FCPATH . $lama);

        $this->db->where('id', $id)->update('trx_permohonan', [
            $kolom       => NULL,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log_aktivitas('permohonan.hapus_dok', 'Hapus '.$jenis.' permohonan id='.$id);
        $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        redirect('permohonan/detail/' . $id);
    }

    // ─── BATALKAN PENGAJUAN ───────────────────────────────────────

    public function batal($id)
    {
        $this->requirePerm('permohonan.create');

        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan'); return;
        }

        if ($permohonan->status !== 'diajukan') {
            $this->session->set_flashdata('error', 'Hanya permohonan dengan status Diajukan yang dapat dibatalkan.');
            redirect('permohonan/detail/' . $id); return;
        }

        $this->db->where('id', $id)->update('trx_permohonan', [
            'status'     => 'draft',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log_aktivitas('permohonan.batal',
            'Pembatalan pengajuan permohonan id='.$id.' no='.$permohonan->no_permohonan);
        $this->session->set_flashdata('success', 'Pengajuan permohonan berhasil dibatalkan.');
        redirect('permohonan/detail/' . $id);
    }

    // ─── AJUKAN KEMBALI ───────────────────────────────────────────

    public function ajukan_kembali($id)
    {
        $this->requirePerm('permohonan.create');

        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan'); return;
        }

        if ($permohonan->status !== 'draft') {
            $this->session->set_flashdata('error', 'Hanya permohonan berstatus Draft yang dapat diajukan kembali.');
            redirect('permohonan/detail/' . $id); return;
        }

        // Validasi kelengkapan dokumen
        $dok_kurang = [];
        if (empty($permohonan->file_surat_permohonan_path)) $dok_kurang[] = 'Surat Permohonan Kepala Daerah';
        if (empty($permohonan->file_surat_pernyataan_path)) $dok_kurang[] = 'Surat Pernyataan Kepala Daerah';
        if (empty($permohonan->file_rekap_kegiatan_path))   $dok_kurang[] = 'Rekapitulasi Kegiatan yang Diajukan';
        if (!empty($dok_kurang)) {
            $this->session->set_flashdata('error',
                'Dokumen belum lengkap. Upload terlebih dahulu: ' . implode(', ', $dok_kurang) . '.');
            redirect('permohonan/detail/' . $id); return;
        }

        $this->db->where('id', $id)->update('trx_permohonan', [
            'status'     => 'diajukan',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Notifikasi in-app + Telegram ke admin provinsi
        $kab_nama   = $this->session->userdata('kabkota_nama');
        $pesan      = $kab_nama . ' mengajukan permohonan pencairan No. ' . $permohonan->no_permohonan;
        $admin_prov = $this->db
            ->select('u.id')->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->where_in('r.kode', ['superadmin','admin_provinsi'])
            ->where('u.is_active', 1)->get()->result();
        foreach ($admin_prov as $au) {
            $this->Notifikasi_model->kirim(
                $au->id,
                'Permohonan Pencairan Masuk',
                $pesan,
                'info',
                site_url('permohonan/detail/' . $id)
            );
        }

        $waktu     = date('d/m/Y H:i');
        $label_map = [
            'sekaligus'       => 'Sekaligus',
            'bertahap'        => 'Bertahap — '.($permohonan->kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I'),
            'khusus_mendesak' => 'Khusus Mendesak',
            'khusus_bencana'  => 'Khusus Bencana',
        ];
        $label_jenis = $label_map[$permohonan->jenis_penyaluran] ?? $permohonan->jenis_penyaluran;

        $items_notif = $this->Permohonan_model->get_items($id);
        $total_notif = array_sum(array_map(function($it) {
            return ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0);
        }, (array)$items_notif));

        $msg  = "📨 <b>PERMOHONAN PENCAIRAN BKP MASUK</b>\n\n";
        $msg .= "📍 <b>Kab/Kota   :</b> " . htmlspecialchars($permohonan->nama_kabkota) . "\n";
        $msg .= "📋 <b>Kelompok   :</b> " . $label_jenis . "\n";
        $msg .= "🔢 <b>No. Surat  :</b> " . htmlspecialchars($permohonan->no_permohonan) . "\n";
        $msg .= "📦 <b>Kegiatan   :</b> " . count($items_notif) . " kegiatan\n";
        $msg .= "💰 <b>Total Nilai :</b> Rp " . number_format($total_notif, 0, ',', '.') . "\n";
        $msg .= "\n📅 <b>" . $waktu . " WIB</b>\n";
        $msg .= "<i>— Sistem SIBERKAH SUMUT TA " . $permohonan->tahun . "</i>";

        telegram_notif_admin_prov($msg);

        $this->log_aktivitas('permohonan.ajukan_kembali',
            'Pengajuan kembali permohonan id='.$id.' no='.$permohonan->no_permohonan);
        $this->session->set_flashdata('success', 'Permohonan berhasil diajukan kembali kepada BKAD Provinsi.');
        redirect('permohonan/detail/' . $id);
    }

    // ─── DETAIL PERMOHONAN ────────────────────────────────────────

    public function detail($id)
    {
        $permohonan = $this->Permohonan_model->get_by_id($id);
        if (!$permohonan) { show_404(); return; }

        if ($this->rbac->isKabkota()
            && (int)$permohonan->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('permohonan'); return;
        }

        $items       = $this->Permohonan_model->get_items($id);
        $total_nilai = array_sum(array_column((array)$items, 'nilai_diajukan'));

        $this->render('permohonan/detail', array_merge($this->data, [
            'title'       => 'Permohonan — ' . $permohonan->no_permohonan,
            'permohonan'  => $permohonan,
            'items'       => $items,
            'total_nilai' => $total_nilai,
        ]));
    }
}
