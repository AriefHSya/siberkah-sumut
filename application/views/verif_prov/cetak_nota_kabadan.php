<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Kab / Kota — strip prefix yang sudah ada di DB sebelum deteksi
$kota_sumut = ['Medan','Binjai','Tebing Tinggi','Pematangsiantar',
               'Padangsidimpuan','Gunungsitoli','Sibolga','Tanjungbalai'];
$nama_raw = trim(preg_replace('/^(Kab\.|Kab |Kota )/i', '', $pm->nama_kabkota));
$is_kota  = in_array($nama_raw, $kota_sumut)
            || (bool)preg_match('/^Kota /i', $pm->nama_kabkota);
$prefix   = $is_kota ? 'Kota' : 'Kab.';
$nama_wil = $nama_raw;
$nama_dp  = $prefix . ' ' . $nama_raw;  // mis. "Kab. Labuhanbatu Utara"

// Pejabat
$kabid   = isset($pejabat['kabid_anggaran']) ? $pejabat['kabid_anggaran'] : null;
$kabadan = isset($pejabat['kepala_badan'])   ? $pejabat['kepala_badan']   : null;

// Referensi Nota Kabid (dari timestamp saat digenerate)
$no_nota_kabid_suffix = '/KabidRan/' . $pm->tahun;
$tgl_nota_kabid       = !empty($pm->nota_kabid_at)
    ? tgl_indo(date('Y-m-d', strtotime($pm->nota_kabid_at)))
    : $tgl_nota;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nota Kepala Badan — <?= htmlspecialchars($pm->no_permohonan) ?></title>
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
.f-lbl{width:90px;white-space:nowrap}
.f-sep{width:18px;padding-right:4px}
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
      <td class="f-lbl">Kepada</td>
      <td class="f-sep">:</td>
      <td class="f-val">Bendahara Pengeluaran BKAD Provsu</td>
    </tr>
    <tr>
      <td class="f-lbl">Dari</td>
      <td class="f-sep">:</td>
      <td class="f-val">Kepala Badan Keuangan dan Aset Daerah Provsu</td>
    </tr>
    <tr>
      <td class="f-lbl">Tanggal</td>
      <td class="f-sep">:</td>
      <td class="f-val"><?= $tgl_nota ?></td>
    </tr>
    <tr>
      <td class="f-lbl">Nomor</td>
      <td class="f-sep">:</td>
      <td class="f-val">&nbsp;</td>
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
      <td class="f-val f-hal">Pencairan Bantuan Keuangan untuk <?= htmlspecialchars($nama_dp) ?></td>
    </tr>
  </table>

  <!-- ISI -->
  <p class="para">Sehubungan dengan Nota Dinas Kepala Bidang Perencanaan Anggaran Daerah
  BKAD Provsu nomor <span style="display:inline-block;min-width:50px">&nbsp;</span><?= htmlspecialchars($no_nota_kabid_suffix) ?> tanggal <?= $tgl_nota_kabid ?>
  tentang Penyaluran Bantuan Keuangan Provinsi kepada Pemerintah <?= htmlspecialchars($nama_dp) ?>
  yang menyatakan bahwa alokasi Bantuan Keuangan sudah dapat disalurkan.</p>

  <p class="para">Untuk itu diminta kepada saudara agar memproses penyalurannya dengan
  mempedomani ketentuan peraturan perundang-undangan.</p>

  <p class="para">Demikian disampaikan untuk dilaksanakan sebagaimana mestinya.</p>

  <!-- TANDA TANGAN -->
  <div class="sign-wrap">
    <div class="sign-block">
      <div class="sign-ttl">Kepala Badan Keuangan<br>dan Aset Daerah,</div>
      <div class="sign-gap"></div>
      <div class="sign-name"><?= htmlspecialchars($kabadan->nama ?? '............................') ?></div>
      <?php if (!empty($kabadan->jabatan)): ?>
        <div class="sign-rank"><?= htmlspecialchars($kabadan->jabatan) ?></div>
      <?php endif; ?>
      <?php if (!empty($kabadan->nip)): ?>
        <div class="sign-nip">NIP. <?= htmlspecialchars($kabadan->nip) ?></div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- .page -->
</body>
</html>
