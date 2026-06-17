<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Angka ke terbilang Indonesia
function _tb($n) {
    $n = abs((int)$n);
    if ($n === 0) return 'nol';
    $s = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    if ($n < 12)        return $s[$n];
    if ($n < 20)        return $s[$n-10].' belas';
    if ($n < 100)       return $s[(int)($n/10)].' puluh'.($n%10 ? ' '.$s[$n%10] : '');
    if ($n < 200)       return 'seratus'.($n%100   ? ' '._tb($n%100)   : '');
    if ($n < 1000)      return $s[(int)($n/100)].' ratus'.($n%100 ? ' '._tb($n%100) : '');
    if ($n < 2000)      return 'seribu'.($n%1000   ? ' '._tb($n%1000)  : '');
    if ($n < 1e6)       return _tb((int)($n/1000)).' ribu'.($n%1000    ? ' '._tb($n%1000)    : '');
    if ($n < 1e9)       return _tb((int)($n/1e6)).' juta'.($n%1e6      ? ' '._tb($n%1e6)     : '');
    if ($n < 1e12)      return _tb((int)($n/1e9)).' miliar'.($n%1e9    ? ' '._tb($n%1e9)     : '');
    return _tb((int)($n/1e12)).' triliun'.($n%1e12 ? ' '._tb($n%1e12) : '');
}

// Total nilai pengajuan
// Tahap II: 50% × NK = nilai_diajukan (pendukung tidak termasuk, sudah dibayar di Tahap I)
$_is_tahap2  = ($pm->jenis_penyaluran === 'bertahap' && $pm->kode_tahap === 'tahap_2');
$total_nilai = array_sum(array_map(function($it) use ($_is_tahap2) {
    if ($_is_tahap2) return ($it->nilai_diajukan ?? 0);
    return ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0);
}, (array)$items));
$total_rp        = 'Rp. ' . number_format($total_nilai, 0, ',', '.') . ',00';
$total_terbilang = _tb($total_nilai);

// Kab / Kota — strip prefix yang sudah ada di DB sebelum deteksi
$kota_sumut = ['Medan','Binjai','Tebing Tinggi','Pematangsiantar',
               'Padangsidimpuan','Gunungsitoli','Sibolga','Tanjungbalai'];
$nama_raw = trim(preg_replace('/^(Kab\.|Kab |Kota )/i', '', $pm->nama_kabkota));
$is_kota  = in_array($nama_raw, $kota_sumut)
            || (bool)preg_match('/^Kota /i', $pm->nama_kabkota);
$prefix   = $is_kota ? 'Kota' : 'Kab.';
$kdh_full = $is_kota ? 'Walikota' : 'Bupati';
$nama_wil = $nama_raw;
$nama_dp  = $prefix . ' ' . $nama_raw;  // mis. "Kab. Labuhanbatu Utara"

// Pejabat
$kabid   = isset($pejabat['kabid_anggaran'])  ? $pejabat['kabid_anggaran']  : null;
$kabadan = isset($pejabat['kepala_badan'])     ? $pejabat['kepala_badan']    : null;

// Label jenis penyaluran untuk field Hal
$_jp = $pm->jenis_penyaluran;
$_kt = $pm->kode_tahap ?? '';
if ($_jp === 'bertahap') {
    $label_jenis = $_kt === 'tahap_2' ? 'Tahap II' : 'Tahap I';
} elseif ($_jp === 'sekaligus') {
    $label_jenis = 'Sekaligus';
} elseif ($_jp === 'khusus_mendesak') {
    $label_jenis = 'Mendesak';
} elseif ($_jp === 'khusus_bencana') {
    $label_jenis = 'Bencana';
} else {
    $label_jenis = ucfirst(str_replace('_', ' ', $_jp));
}

// Suffix nomor — spasi kosong dirender di HTML, bukan di variabel PHP
$no_nota_suffix = '/KabidRan/' . $pm->tahun;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nota Kabid Anggaran — <?= htmlspecialchars($pm->no_permohonan) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;color:#000;background:#fff}
.page{padding:1.8cm 2.5cm;max-width:21cm;margin:0 auto}
@media print{.no-print{display:none!important}}
.no-print{position:fixed;top:12px;right:16px;z-index:999}

/* Header */
.hdr{display:flex;align-items:center;border-bottom:3px double #000;padding-bottom:6px;margin-bottom:18px}
.hdr-logo{width:90px;flex-shrink:0;text-align:center}
.hdr-logo img{width:80px;height:80px;object-fit:contain}
.hdr-logo-empty{width:80px;height:80px;border:1px dashed #aaa;display:flex;align-items:center;
  justify-content:center;font-size:8pt;color:#aaa;margin:auto}
.hdr-txt{flex:1;text-align:center;line-height:1.35}
.hdr-nm1{font-size:14pt;font-weight:bold}
.hdr-nm2{font-size:13pt;font-weight:bold}
.hdr-alamat{font-size:9pt;margin-top:3px}
.hdr-spacer{width:90px;flex-shrink:0}

/* Judul */
.nota-title{text-align:center;font-size:13pt;font-weight:bold;letter-spacing:3px;margin:18px 0}

/* Fields */
.fields{border-collapse:collapse;margin-bottom:18px}
.fields td{vertical-align:top;padding:2px 0;font-size:12pt;line-height:1.6}
.f-lbl{width:105px;white-space:nowrap}
.f-sep{width:18px;padding-right:4px}
.f-val{}
.f-hal{font-style:italic;font-weight:bold}

/* Paragraf */
.para{text-align:justify;text-indent:1.5cm;margin-bottom:14pt;line-height:2}

/* Tanda Tangan */
.sign-wrap{margin-top:28pt;display:flex;justify-content:flex-end}
.sign-block{text-align:left;min-width:260px}
.sign-ttl{font-weight:bold;text-transform:uppercase;line-height:1.45}
.sign-gap{height:80px}
.sign-name{font-weight:bold}
.sign-rank,.sign-nip{line-height:1.5}
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()"
    style="padding:7px 16px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px">
    &#128424; Cetak
  </button>
</div>

<div class="page">

  <!-- KOP SURAT -->
  <div class="hdr">
    <div class="hdr-logo">
      <?php if (!empty($logo_prov)): ?>
        <img src="<?= $logo_prov ?>" alt="Logo Provinsi">
      <?php else: ?>
        <div class="hdr-logo-empty">Logo</div>
      <?php endif; ?>
    </div>
    <div class="hdr-txt">
      <div class="hdr-nm1">Pemerintah Provinsi Sumatera Utara</div>
      <div class="hdr-nm2">Badan Keuangan dan Aset Daerah</div>
      <div class="hdr-alamat">
        Jalan Imam Bonjol Nomor 61, Medan, Kode Pos 20157<br>
        bpkad@sumutprov.go.id, bpkad.sumutprov.go.id
      </div>
    </div>
    <div class="hdr-spacer"></div>
  </div>

  <!-- JUDUL -->
  <div class="nota-title">N O T A - D I N A S</div>

  <!-- FIELD -->
  <table class="fields">
    <tr>
      <td class="f-lbl">Kepada Yth</td>
      <td class="f-sep">:</td>
      <td class="f-val">Bapak Kepala Badan Keuangan dan Aset Daerah Provsu</td>
    </tr>
    <tr>
      <td class="f-lbl">Dari</td>
      <td class="f-sep">:</td>
      <td class="f-val">Kepala Bidang Perencanaan Anggaran Daerah</td>
    </tr>
    <tr>
      <td class="f-lbl">Tanggal</td>
      <td class="f-sep">:</td>
      <td class="f-val"><?= $tgl_nota ?></td>
    </tr>
    <tr>
      <td class="f-lbl">Nomor</td>
      <td class="f-sep">:</td>
      <td class="f-val"><span style="display:inline-block;min-width:50px">&nbsp;</span><?= htmlspecialchars($no_nota_suffix) ?></td>
    </tr>
    <tr>
      <td class="f-lbl">Sifat</td>
      <td class="f-sep">:</td>
      <td class="f-val">Penting</td>
    </tr>
    <tr>
      <td class="f-lbl">Lampiran</td>
      <td class="f-sep">:</td>
      <td class="f-val">1 (satu) berkas</td>
    </tr>
    <tr>
      <td class="f-lbl">Hal</td>
      <td class="f-sep">:</td>
      <td class="f-val f-hal">Pencairan Bantuan Keuangan untuk <?= htmlspecialchars($nama_dp) ?> (<?= htmlspecialchars($label_jenis) ?>)</td>
    </tr>
  </table>

  <!-- ISI -->
  <p class="para">Sehubungan telah ditetapkannya Peraturan Gubernur Sumatera Utara
  Nomor 18 Tahun 2026 tanggal 12 Juni 2026 tentang Tata Cara Perencanaan, Penganggaran,
  Pelaksanaan, dan Penatausahaan, Pertanggungjawaban dan Pelaporan Serta Monitoring dan
  Evaluasi Belanja Bantuan Keuangan, Surat Keputusan Gubernur Sumatera Utara Nomor
  188.44/314/KPTS/2026 tanggal 07 Mei 2026 tentang Pemberian Bantuan Keuangan Bersifat
  Khusus Kepada Pemerintah Kabupaten/Kota di Wilayah Provinsi Sumatera Utara serta Surat
  Sekretaris Daerah Provinsi Sumatera Utara Nomor 900.1.1/3807/2026 tanggal 08 Mei 2026
  tentang Rincian Alokasi Bantuan Keuangan Provinsi TA 2026, terlampir kami sampaikan
  permohonan pencairan Bantuan Keuangan Provinsi dari <?= htmlspecialchars($nama_dp) ?>
  yang telah diajukan dan diverifikasi.</p>

  <p class="para">Adapun alokasi bantuan keuangan tersebut berjumlah
  <?= $total_rp ?> - (<?= $total_terbilang ?> rupiah).</p>

  <p class="para">Bersama ini kami sampaikan dokumen pendukung berupa Surat Permohonan dari
  <?= htmlspecialchars($kdh_full . ' ' . $nama_wil) ?>, jika Bapak sependapat
  kiranya dapat diteruskan kepada Bendahara Pengeluaran BKAD Provsu guna proses penerbitan
  Surat Permintaan Pembayaran Langsung (SPP &ndash; LS).</p>

  <p class="para">Demikian disampaikan, mohon arahan bapak selanjutnya.</p>

  <!-- TANDA TANGAN -->
  <div class="sign-wrap">
    <div class="sign-block">
      <div class="sign-ttl">Kepala Bidang Perencanaan<br>Anggaran Daerah,</div>
      <div class="sign-gap"></div>
      <div class="sign-name"><?= htmlspecialchars($kabid->nama ?? '............................') ?></div>
      <?php if (!empty($kabid->jabatan)): ?>
        <div class="sign-rank"><?= htmlspecialchars($kabid->jabatan) ?></div>
      <?php endif; ?>
      <?php if (!empty($kabid->nip)): ?>
        <div class="sign-nip">NIP. <?= htmlspecialchars($kabid->nip) ?></div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- .page -->
</body>
</html>
