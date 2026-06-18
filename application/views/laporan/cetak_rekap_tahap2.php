<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Pengajuan Tahap II TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',serif;font-size:10pt;padding:1cm 1.5cm;line-height:1.4}
.kop{border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px;display:flex;align-items:center;gap:14px}
.kop-logo{flex-shrink:0}
.kop-text{flex:1;text-align:center}
.kop h1{font-size:12pt;font-weight:bold;text-transform:uppercase}
h2{font-size:11pt;text-align:center;text-transform:uppercase;margin:10px 0 4px;font-weight:bold}
.sub-title{text-align:center;font-size:9.5pt;margin-bottom:10px}
.filter-info{text-align:center;font-size:9pt;color:#555;margin-bottom:12px}
.sum-row{display:flex;gap:16px;justify-content:center;margin:10px 0}
.sum-item{padding:6px 14px;border:1px solid #000;text-align:center;min-width:110px}
.sum-item strong{display:block;font-size:12pt}
.tbl{width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:12px}
.tbl th,.tbl td{border:1px solid #000;padding:3px 5px;text-align:left;vertical-align:top}
.tbl th{background:#e0e0e0;font-weight:bold;text-align:center;vertical-align:middle}
.tbl .right{text-align:right}
.tbl .center{text-align:center}
.tbl .total{background:#f0f0f0;font-weight:bold}
.tbl .sub-th{font-size:7.5pt;font-weight:normal;font-style:italic;background:#eeeeee}
.ttd{display:flex;justify-content:flex-end;margin-top:24px}
.ttd-blok{width:40%;text-align:center}
.ttd-blok .ttd-space{height:60px}
.ttd-blok .ttd-nama{border-top:1px solid #000;padding-top:2px;font-weight:bold;font-size:9pt;display:inline-block;min-width:200px}
.ttd-blok .ttd-nip{font-size:8pt;margin-top:2px}
.footer{font-size:8pt;color:#666;border-top:1px solid #ccc;padding-top:6px;margin-top:12px;text-align:center}
@media print{body{padding:0.8cm 1.2cm}.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:6px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:4px;cursor:pointer">&#128424; Cetak</button>
</div>

<div class="kop">
  <div class="kop-logo"><img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH" style="height:64px;width:64px;object-fit:contain"></div>
  <div class="kop-text"><h1>BADAN KEUANGAN DAN ASET DAERAH PROVINSI SUMATERA UTARA</h1></div>
</div>

<h2>REKAPITULASI PENGAJUAN TAHAP II BANTUAN KEUANGAN PROVINSI</h2>
<div class="sub-title">TAHUN ANGGARAN <?= $tahun ?><?= $kabkota ? ' — '.strtoupper(htmlspecialchars($kabkota->nama)) : '' ?></div>
<div class="filter-info">Dicetak: <?= $tgl_cetak ?> oleh <?= htmlspecialchars($nama_user) ?></div>

<?php
$g_kontrak = 0; $g_tahap1 = 0; $g_tahap2 = 0;
foreach ($list as $r) {
    $v50   = (float)($r->persen_tahap1 / 100) * $r->nilai_kontrak;
    $vpend = (float)$r->nilai_belanja_pendukung;
    $g_kontrak += (float)$r->nilai_kontrak;
    $g_tahap1  += $v50;
    $g_tahap2  += $r->nilai_kontrak - $v50;
}
?>

<div class="sum-row">
  <div class="sum-item"><strong><?= count($list) ?></strong><span>Kegiatan Tahap II</span></div>
  <div class="sum-item"><strong><?= number_format($g_kontrak/1000000,1,',','.') ?> Jt</strong><span>Total Nilai Kontrak</span></div>
  <div class="sum-item"><strong><?= number_format($g_tahap1/1000000,1,',','.') ?> Jt</strong><span>Nilai Salur Tahap I</span></div>
  <div class="sum-item"><strong><?= number_format($g_tahap2/1000000,1,',','.') ?> Jt</strong><span>Nilai Pengajuan Tahap II</span></div>
</div>

<table class="tbl">
  <thead>
    <tr>
      <th rowspan="2" style="width:22px">No.</th>
      <th rowspan="2" style="width:70px">Kode BKP</th>
      <?php if ($is_provinsi): ?><th rowspan="2" style="width:80px">Kab/Kota</th><?php endif; ?>
      <th rowspan="2">Nama Kegiatan</th>
      <th rowspan="2" style="width:90px">Lokasi</th>
      <th rowspan="2" class="right" style="width:85px">Nilai Kontrak (Rp)</th>
      <th colspan="2" class="center" style="background:#d4e8f5">Tahap I yang Telah Disalurkan</th>
      <th rowspan="2" class="right" style="width:85px;background:#dff5e0">Nilai Pengajuan Tahap II (Rp)</th>
      <th rowspan="2" style="width:60px;text-align:center">Status Tahap II</th>
    </tr>
    <tr>
      <th class="sub-th right" style="width:75px;background:#dbeaf8">50% Kontrak (Rp)</th>
      <th class="sub-th right" style="width:70px;background:#dbeaf8">Pendukung (Rp)</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $no = 1;
  $g_kontrak2 = 0; $g_50pct = 0; $g_pend = 0; $g_t2 = 0;
  $st_label = [
      'opd_input'            => 'Diajukan',
      'inspektorat_reviu'    => 'Reviu',
      'inspektorat_revisi'   => 'Revisi',
      'inspektorat_approved' => 'Reviu OK',
      'skpkd_kab_verif'      => 'Verif. Kab',
      'skpkd_kab_revisi'     => 'Revisi Kab',
      'skpkd_kab_approved'   => 'Verif. Kab OK',
      'skpkd_prov_verif'     => 'Verif. Prov',
      'skpkd_prov_revisi'    => 'Revisi Prov',
      'disalurkan'           => 'Disalurkan',
      'dikonfirmasi'         => 'Dikonfirmasi',
      'ditolak'              => 'Ditolak',
  ];
  foreach ($list as $r):
      $val_50pct  = (float)($r->persen_tahap1 / 100) * $r->nilai_kontrak;
      $val_pend   = (float)$r->nilai_belanja_pendukung;
      // Nilai Pengajuan Tahap II = 50% × Nilai Kontrak (pendukung tidak dikurangi)
      $val_tahap2 = $r->nilai_kontrak - $val_50pct;
      $g_kontrak2 += $r->nilai_kontrak;
      $g_50pct    += $val_50pct;
      $g_pend     += $val_pend;
      $g_t2       += $val_tahap2;
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td class="mono" style="font-size:8pt"><?= htmlspecialchars($r->kode_bkp) ?></td>
    <?php if ($is_provinsi): ?><td style="font-size:8pt"><?= htmlspecialchars($r->nama_kabkota) ?></td><?php endif; ?>
    <td style="font-size:8pt"><?= htmlspecialchars($r->nama_kegiatan_dok ?: $r->uraian_bkp) ?></td>
    <td style="font-size:8pt"><?= htmlspecialchars($r->lokasi_deskripsi ?: '—') ?></td>
    <td class="right"><?= number_format($r->nilai_kontrak, 0, ',', '.') ?></td>
    <td class="right" style="background:#f0f7fd"><?= number_format($val_50pct, 0, ',', '.') ?></td>
    <td class="right" style="background:#f0f7fd"><?= $val_pend > 0 ? number_format($val_pend, 0, ',', '.') : '—' ?></td>
    <td class="right" style="background:#f0fbf0"><?= number_format($val_tahap2, 0, ',', '.') ?></td>
    <td class="center" style="font-size:7.5pt"><?= $st_label[$r->status_tahap2] ?? htmlspecialchars($r->status_tahap2) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="<?= $is_provinsi ? 5 : 4 ?>" class="right">TOTAL (<?= count($list) ?> kegiatan)</td>
    <td class="right"><?= number_format($g_kontrak2, 0, ',', '.') ?></td>
    <td class="right"><?= number_format($g_50pct,    0, ',', '.') ?></td>
    <td class="right"><?= number_format($g_pend,     0, ',', '.') ?></td>
    <td class="right"><?= number_format($g_t2,       0, ',', '.') ?></td>
    <td></td>
  </tr>
  </tbody>
</table>

<div class="ttd">
  <div class="ttd-blok">
    <?php if ($is_provinsi): ?>
    <p>Medan, <?= $tgl_cetak ?></p>
    <p>Kepala BKAD Provinsi Sumatera Utara</p>
    <div class="ttd-space"></div>
    <div class="ttd-nama">.................................................</div>
    <div class="ttd-nip">NIP. ................................</div>
    <?php else: ?>
    <p><?= $kabkota ? htmlspecialchars($kabkota->nama) : '.....................' ?>, <?= $tgl_cetak ?></p>
    <p>Kepala BKAD <?= $kabkota ? htmlspecialchars($kabkota->nama) : '.....................' ?></p>
    <div class="ttd-space"></div>
    <div class="ttd-nama">.................................................</div>
    <div class="ttd-nip">NIP. ................................</div>
    <?php endif; ?>
  </div>
</div>

<div class="footer">
  Dicetak melalui SIBERKAH SUMUT — <?= $tgl_cetak ?>
</div>
</body>
</html>
