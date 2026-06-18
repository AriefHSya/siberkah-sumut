<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Berkas.php — Controller unduhan file privat
 *
 * Semua file di uploads/dokumen, uploads/lhr, uploads/permohonan,
 * uploads/capaian, dan uploads/temp HARUS diunduh melalui controller ini.
 * Akses langsung via URL diblokir di .htaccess.
 *
 * Setiap unduhan melewati:
 *   1. Cek autentikasi (extends Auth_Controller)
 *   2. Cek permission RBAC sesuai jenis file
 *   3. Cek scope kabkota: user kab/kota hanya boleh akses file milik instansinya
 *   4. Lookup path dari DB (bukan dari URL) — mencegah path traversal / IDOR
 *   5. Log aktivitas unduhan
 *
 * ROUTES:
 *   GET /berkas/unduh/dok/{id}                 → unduh('dok', id)         — trx_dokumen_persyaratan
 *   GET /berkas/unduh/lhr/{reviu_id}           → unduh('lhr', id)         — trx_reviu_inspektorat LHR
 *   GET /berkas/unduh/capaian/{pekerjaan_id}   → unduh('capaian', id)     — trx_capaian_output foto
 *   GET /berkas/unduh/capaian-ba/{pekerjaan_id}→ unduh('capaian-ba', id)  — trx_capaian_output BA
 *   GET /berkas/unduh/draft/{pekerjaan_id}/{jenis} → unduh_sub()          — trx_pekerjaan SPK/SPMK/BAST
 *   GET /berkas/unduh/pm/{permohonan_id}/{jenis}   → unduh_sub()          — trx_permohonan surat
 */
class Berkas extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Tidak ada guard di constructor — tiap method punya permission check sendiri
    }

    // ─── DISPATCHER ──────────────────────────────────────────────

    /** Entry point untuk unduhan tanpa sub-jenis */
    public function unduh($jenis, $id)
    {
        switch ($jenis) {
            case 'dok':        $this->_unduh_dok($id);        break;
            case 'lhr':        $this->_unduh_lhr($id);        break;
            case 'capaian':    $this->_unduh_capaian($id);    break;
            case 'capaian-ba': $this->_unduh_capaian_ba($id); break;
            default: show_404();
        }
    }

    /** Entry point untuk unduhan dengan sub-jenis (draft, pm) */
    public function unduh_sub($jenis, $id, $sub)
    {
        switch ($jenis) {
            case 'draft': $this->_unduh_draft($id, $sub); break;
            case 'pm':    $this->_unduh_pm($id, $sub);    break;
            default: show_404();
        }
    }

    // ─── UNDUH DOKUMEN PERSYARATAN (trx_dokumen_persyaratan) ─────

    private function _unduh_dok($id)
    {
        $this->requirePerm('pekerjaan.download_dok');
        $this->load->model('Pekerjaan_model');

        $dok = $this->Pekerjaan_model->get_dokumen_by_id($id);
        if (!$dok || empty($dok->file_path)) { show_404(); return; }

        if ($this->rbac->isKabkota()) {
            $tahapan = $this->Pekerjaan_model->get_tahapan_by_id($dok->tahapan_id);
            if ($tahapan) {
                $pek = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
                if ($pek && (int)$pek->kabkota_id !== (int)$this->kabkota_id) {
                    show_404(); return;
                }
            }
        }

        $nama = !empty($dok->nama_asli) ? $dok->nama_asli : basename($dok->file_path);
        $this->log_aktivitas('berkas.unduh', 'Unduh dokumen id=' . $id);
        $this->_kirim_file($dok->file_path, $nama);
    }

    // ─── UNDUH LHR INSPEKTORAT (trx_reviu_inspektorat) ──────────

    private function _unduh_lhr($reviu_id)
    {
        $this->requirePerm('reviu.view');
        $this->load->model(['Reviu_model', 'Pekerjaan_model']);

        $reviu = $this->Reviu_model->get_by_id($reviu_id);
        if (!$reviu || empty($reviu->file_lhr_path)) { show_404(); return; }

        if ($this->rbac->isKabkota()) {
            $tahapan = $this->Pekerjaan_model->get_tahapan_by_id($reviu->tahapan_id);
            if ($tahapan) {
                $pek = $this->Pekerjaan_model->get_by_id($tahapan->pekerjaan_id);
                if ($pek && (int)$pek->kabkota_id !== (int)$this->kabkota_id) {
                    show_404(); return;
                }
            }
        }

        $nama = !empty($reviu->nama_lhr_asli) ? $reviu->nama_lhr_asli : basename($reviu->file_lhr_path);
        $this->log_aktivitas('berkas.unduh', 'Unduh LHR reviu_id=' . $reviu_id);
        $this->_kirim_file($reviu->file_lhr_path, $nama);
    }

    // ─── UNDUH FOTO CAPAIAN (trx_capaian_output) ─────────────────

    private function _unduh_capaian($pekerjaan_id)
    {
        $this->requirePerm('capaian.view');
        $this->load->model(['Capaian_model', 'Pekerjaan_model']);

        $detail = $this->Capaian_model->get_detail($pekerjaan_id);
        if (!$detail || empty($detail->foto_path)) { show_404(); return; }

        if ($this->rbac->isKabkota() && (int)$detail->kabkota_id !== (int)$this->kabkota_id) {
            show_404(); return;
        }

        // Ambil nama asli dari tabel trx_capaian_output langsung (kolom nama_foto_asli)
        $capaian = $this->db->get_where('trx_capaian_output',
            ['tahapan_id' => $detail->tahapan_id])->row();
        $nama = ($capaian && !empty($capaian->nama_foto_asli))
            ? $capaian->nama_foto_asli
            : basename($detail->foto_path);

        $this->log_aktivitas('berkas.unduh', 'Unduh foto capaian pekerjaan_id=' . $pekerjaan_id);
        $this->_kirim_file($detail->foto_path, $nama);
    }

    // ─── UNDUH BERITA ACARA KEMAJUAN (trx_capaian_output) ───────────

    private function _unduh_capaian_ba($pekerjaan_id)
    {
        $this->requirePerm('capaian.view');
        $this->load->model(['Capaian_model', 'Pekerjaan_model']);

        $detail = $this->Capaian_model->get_detail($pekerjaan_id);
        if (!$detail || empty($detail->ba_path)) { show_404(); return; }

        if ($this->rbac->isKabkota() && (int)$detail->kabkota_id !== (int)$this->kabkota_id) {
            show_404(); return;
        }

        $nama = !empty($detail->nama_ba_asli) ? $detail->nama_ba_asli : basename($detail->ba_path);
        $this->log_aktivitas('berkas.unduh', 'Unduh BA capaian pekerjaan_id=' . $pekerjaan_id);
        $this->_kirim_file($detail->ba_path, $nama);
    }

    // ─── UNDUH DOKUMEN DRAFT PEKERJAAN (trx_pekerjaan SPK/SPMK/BAST) ──

    private function _unduh_draft($pekerjaan_id, $jenis)
    {
        $this->requirePerm('pekerjaan.download_dok');
        $this->load->model('Pekerjaan_model');

        $allowed = ['spk', 'spmk', 'bast'];
        if (!in_array($jenis, $allowed, TRUE)) { show_404(); return; }

        $pek = $this->Pekerjaan_model->get_by_id($pekerjaan_id);
        if (!$pek) { show_404(); return; }

        if ($this->rbac->isKabkota() && (int)$pek->kabkota_id !== (int)$this->kabkota_id) {
            show_404(); return;
        }

        $kolom_path = 'dok_' . $jenis . '_path';
        $kolom_nama = 'nama_dok_' . $jenis;
        $file_path  = $pek->$kolom_path ?? NULL;
        if (!$file_path) { show_404(); return; }

        $nama = !empty($pek->$kolom_nama) ? $pek->$kolom_nama : basename($file_path);
        $this->log_aktivitas('berkas.unduh',
            'Unduh draft ' . strtoupper($jenis) . ' pekerjaan_id=' . $pekerjaan_id);
        $this->_kirim_file($file_path, $nama);
    }

    // ─── UNDUH SURAT PERMOHONAN (trx_permohonan) ─────────────────

    private function _unduh_pm($permohonan_id, $jenis)
    {
        $this->requirePerm('permohonan.view');
        $this->load->model('Permohonan_model');

        $allowed = ['surat_permohonan', 'surat_pernyataan', 'rekap_kegiatan'];
        if (!in_array($jenis, $allowed, TRUE)) { show_404(); return; }

        $pm = $this->Permohonan_model->get_by_id($permohonan_id);
        if (!$pm) { show_404(); return; }

        if ($this->rbac->isKabkota() && (int)$pm->kabkota_id !== (int)$this->kabkota_id) {
            show_404(); return;
        }

        $kolom_path = 'file_' . $jenis . '_path';
        $kolom_nama = 'nama_' . $jenis;
        $file_path  = $pm->$kolom_path ?? NULL;
        if (!$file_path) { show_404(); return; }

        $nama = !empty($pm->$kolom_nama) ? $pm->$kolom_nama : basename($file_path);
        $this->log_aktivitas('berkas.unduh',
            'Unduh permohonan ' . $jenis . ' pm_id=' . $permohonan_id);
        $this->_kirim_file($file_path, $nama);
    }

    // ─── HELPER: KIRIM FILE KE BROWSER ───────────────────────────

    private function _kirim_file($path_relatif, $nama_unduh)
    {
        $abs = FCPATH . $path_relatif;
        if (!file_exists($abs) || !is_file($abs)) { show_404(); return; }

        $mime = $this->_deteksi_mime($abs);

        // Bersihkan output buffer sebelum mengirim file binary
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($nama_unduh) . '"');
        header('Content-Length: ' . filesize($abs));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    private function _deteksi_mime($abs_path)
    {
        if (function_exists('finfo_open')) {
            $fi   = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $abs_path);
            finfo_close($fi);
            if ($mime) return $mime;
        }
        // Fallback berdasarkan ekstensi
        $ext_map = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];
        $ext = strtolower(pathinfo($abs_path, PATHINFO_EXTENSION));
        return isset($ext_map[$ext]) ? $ext_map[$ext] : 'application/octet-stream';
    }
}
