<?php
/**
 * siberkah_helper.php — Helper Functions Global SIBERKAH SUMUT
 *
 * Di-autoload via application/config/autoload.php.
 * Tersedia di semua view dan controller tanpa perlu load manual.
 *
 * FUNGSI TERSEDIA:
 *
 *   FORMAT ANGKA:
 *     rupiah($angka)          → "Rp 1.000.000"
 *     rupiah_juta($angka)     → "Rp 1,00 Jt"
 *
 *   FORMAT TANGGAL:
 *     tgl_indo($tgl)          → "15 Oktober 2026"  (dari 'Y-m-d')
 *     tgl_short($tgl)         → "15/10/2026" atau "15/10/2026 14:30"
 *
 *   BADGE HTML (output HTML, selalu escape sebelum concat dengan user input):
 *     badge_status($status)   → <span class="badge badge-{warna}">{label}</span>
 *     badge_jenis($jenis)     → badge untuk jenis_penyaluran
 *     badge_role($kode)       → badge untuk kode role
 *
 *   DEADLINE:
 *     deadline_info($tgl)     → string keterangan sisa hari / sudah lewat
 *     is_deadline_lewat($tgl) → bool: TRUE jika batas waktu sudah terlewati
 *
 *   DOKUMEN:
 *     label_jenis_dok($jenis) → label teks untuk jenis dokumen
 *     icon_file($path)        → class Tabler icon sesuai ekstensi file
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// ── Format Angka ──────────────────────────────────────────────
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
function rupiah_juta($angka) {
    $juta = $angka / 1000000;
    return 'Rp ' . number_format($juta, 2, ',', '.') . ' Jt';
}

// ── Format Tanggal ────────────────────────────────────────────
function tgl_indo($tgl) {
    if (!$tgl || $tgl === '0000-00-00') return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $t = explode('-', $tgl);
    return (int)$t[2] . ' ' . $bulan[(int)$t[1]] . ' ' . $t[0];
}
function tgl_short($tgl) {
    if (!$tgl || $tgl === '0000-00-00' || $tgl === '0000-00-00 00:00:00') return '-';
    $ts = strtotime($tgl);
    if ($ts === false) return '-';
    return (strpos($tgl, ' ') !== false && substr($tgl, 11, 5) !== '00:00')
        ? date('d/m/Y H:i', $ts)
        : date('d/m/Y', $ts);
}

// ── Badge Status Pekerjaan ────────────────────────────────────
function badge_status($status) {
    $map = [
        'draft'                 => ['abu',    'Draft'],
        'opd_submitted'         => ['biru',   'Diajukan'],
        'inspektorat_reviu'     => ['kuning', 'Dalam Reviu'],
        'inspektorat_revisi'    => ['oranye', 'Perlu Revisi'],
        'inspektorat_approved'  => ['hijau',  'Reviu Selesai'],
        'skpkd_kab_verif'       => ['biru',   'Verif. Kab'],
        'skpkd_kab_revisi'      => ['oranye', 'Revisi Kab'],
        'skpkd_kab_approved'    => ['hijau',  'Verif. Kab OK'],
        'skpkd_prov_verif'      => ['ungu',   'Verif. Prov'],
        'skpkd_prov_revisi'     => ['oranye', 'Revisi Prov'],
        'disalurkan_tahap1'     => ['teal',   'Disalurkan Tahap I'],
        'dikonfirmasi_tahap1'   => ['hijau',  'Dikonfirmasi Tahap I'],
        'opd_capaian_tahap1'    => ['biru',   'Input Capaian'],
        'disalurkan_sekaligus'  => ['teal',   'Disalurkan'],
        'disalurkan_tahap2'     => ['teal',   'Disalurkan Tahap II'],
        'selesai'               => ['hijau',  'Selesai'],
        'ditolak'               => ['merah',  'Ditolak'],
    ];
    $s = $map[$status] ?? ['abu', $status];
    return '<span class="badge badge-'.$s[0].'">'.$s[1].'</span>';
}

// ── Badge Jenis Penyaluran ────────────────────────────────────
function badge_jenis($jenis) {
    $map = [
        'bertahap'        => ['biru',   'Bertahap'],
        'sekaligus'       => ['teal',   'Sekaligus'],
        'khusus_mendesak' => ['kuning', 'Mendesak'],
        'khusus_bencana'  => ['merah',  'Bencana'],
    ];
    $j = $map[$jenis] ?? ['abu', $jenis];
    return '<span class="badge badge-'.$j[0].'">'.$j[1].'</span>';
}

// ── Badge Role ────────────────────────────────────────────────
function badge_role($kode) {
    $map = [
        'superadmin'    => ['merah',  'Super Admin'],
        'admin_provinsi'=> ['biru',   'Admin Provinsi'],
        'skpkd_kabkota' => ['teal',   'SKPKD Kab/Kota'],
        'inspektorat'   => ['ungu',   'Inspektorat'],
        'opd_teknis'    => ['hijau',  'OPD Teknis'],
        'pengawas'      => ['abu',    'Pengawas'],
    ];
    $r = $map[$kode] ?? ['abu', $kode];
    return '<span class="badge badge-'.$r[0].'">'.$r[1].'</span>';
}

// ── Deadline Info (untuk batas waktu) ─────────────────────────
function deadline_info($tgl_batas) {
    if (!$tgl_batas || $tgl_batas === '0000-00-00') return '';
    $hari_sisa = (strtotime($tgl_batas) - strtotime(date('Y-m-d'))) / 86400;
    if ($hari_sisa < 0) {
        return '<span class="text-danger text-xs"><i class="ti ti-alert-triangle"></i> Melewati batas ' . abs((int)$hari_sisa) . ' hari</span>';
    } elseif ($hari_sisa <= 7) {
        return '<span class="text-warning text-xs"><i class="ti ti-clock"></i> Sisa ' . (int)$hari_sisa . ' hari</span>';
    }
    return '<span class="text-muted text-xs"><i class="ti ti-calendar"></i> ' . tgl_indo($tgl_batas) . '</span>';
}

function is_deadline_lewat($tgl_batas) {
    if (!$tgl_batas) return FALSE;
    return date('Y-m-d') > $tgl_batas;
}

// ── Label Helper ──────────────────────────────────────────────
function label_instansi($jenis) {
    $map = [
        'bkad_provinsi' => 'BKAD Provinsi',
        'skpkd_kabkota' => 'SKPKD Kab/Kota',
        'inspektorat'   => 'Inspektorat',
        'opd_teknis'    => 'OPD/Dinas Teknis',
        'lainnya'       => 'Lainnya',
    ];
    return $map[$jenis] ?? $jenis;
}

function label_jenis_dok($jenis) {
    $map = [
        'surat_permohonan_pencairan' => 'Surat Permohonan Pencairan',
        'surat_pernyataan_bupati'    => 'Surat Pernyataan Bupati/Wali Kota',
        'dokumen_pekerjaan_kontrak'  => 'Dokumen Pekerjaan / Kontrak',
        'daftar_pekerjaan'           => 'Daftar Pekerjaan',
        'laporan_reviu_inspektorat'  => 'Laporan Hasil Reviu (LHR)',
        'ba_kemajuan_pekerjaan'      => 'Berita Acara Kemajuan Pekerjaan',
        'rekapitulasi_kegiatan'      => 'Rekapitulasi Kegiatan',
        'bast'                       => 'Berita Acara Serah Terima (BAST)',
        'kwitansi_sts'               => 'Kwitansi / STS',
        'foto_dokumentasi'           => 'Foto Dokumentasi',
        'lainnya'                    => 'Lainnya',
    ];
    return $map[$jenis] ?? $jenis;
}

function icon_file($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $icons = [
        'pdf'  => 'ti-file-type-pdf',
        'doc'  => 'ti-file-type-doc',
        'docx' => 'ti-file-type-doc',
        'xls'  => 'ti-file-type-xls',
        'xlsx' => 'ti-file-type-xls',
        'jpg'  => 'ti-photo',
        'jpeg' => 'ti-photo',
        'png'  => 'ti-photo',
    ];
    return $icons[$ext] ?? 'ti-file';
}

// ── Sub-nav HTML builder ───────────────────────────────────────
function sub_nav_parameter($active_sub) {
    $CI =& get_instance();
    $items = $CI->rbac->getSubParameter();
    $html = '<div class="sub-nav">';
    foreach ($items as $it) {
        $on = ($active_sub === $it['key']) ? 'on' : '';
        $html .= '<a href="'.site_url($it['url']).'" class="sub-nav-item '.$on.'">'
               . '<i class="ti ti-'.$it['icon'].'" aria-hidden="true"></i> '
               . htmlspecialchars($it['label']).'</a>';
    }
    $html .= '</div>';
    return $html;
}

function sub_nav_pengaturan($active_sub) {
    $CI =& get_instance();
    $items = $CI->rbac->getSubPengaturan();
    $html = '<div class="sub-nav">';
    foreach ($items as $it) {
        $on = ($active_sub === $it['key']) ? 'on' : '';
        $html .= '<a href="'.site_url($it['url']).'" class="sub-nav-item '.$on.'">'
               . '<i class="ti ti-'.$it['icon'].'" aria-hidden="true"></i> '
               . htmlspecialchars($it['label']).'</a>';
    }
    $html .= '</div>';
    return $html;
}

