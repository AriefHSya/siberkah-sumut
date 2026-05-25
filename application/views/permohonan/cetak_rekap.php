<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Permohonan — <?= htmlspecialchars($permohonan->no_permohonan) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:11pt;color:#000;padding:1.5cm 2cm;line-height:1.6}
.kop{border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px;display:flex;align-items:center;gap:14px}
.kop-text{flex:1;text-align:center}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
.kop p{font-size:10pt}
h2{font-size:12pt;text-align:center;text-transform:uppercase;margin:14px 0 10px;font-weight:bold}
.info-grid{display:grid;grid-template-columns:auto 8px 1fr;gap:2px;margin-bottom:16px;font-size:10pt}
.info-grid .lbl{font-weight:600;white-space:nowrap}
.tbl{width:100%;border-collapse:collapse;font-size:10pt;margin-bottom:14px}
.tbl th,.tbl td{border:1px solid #000;padding:5px 8px;vertical-align:top}
.tbl th{background:#e8e8e8;font-weight:bold;text-align:center}
.right{text-align:right}
.center{text-align:center}
.ttd-area{display:flex;justify-content:flex-end;margin-top:40px}
.ttd-blok{width:42%;text-align:center}
.ttd-blok .garis{margin-top:65px;border-top:1px solid #000;padding-top:2px;font-weight:bold;text-decoration:underline;font-size:10pt}
.ttd-blok .nip{font-size:9pt}
.footer{font-size:9pt;color:#666;border-top:1px solid #ccc;padding-top:8px;margin-top:14px;text-align:center}
@media print{body{padding:1cm 1.5cm}.no-print{display:none}}
</style>
</head>
<body>

<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:7px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Cetak PDF</button>
</div>

<?php
$pm  = $permohonan;
$is_tahap1       = ($pm->jenis_penyaluran === 'bertahap' && $pm->kode_tahap !== 'tahap_2');
$total_kontrak   = array_sum(array_column((array)$items, 'nilai_kontrak'));
$total_tahap1    = $is_tahap1 ? array_sum(array_column((array)$items, 'nilai_diajukan')) : 0;
$total_pendukung = array_sum(array_column((array)$items, 'nilai_belanja_pendukung'));
$total_nilai     = array_sum(array_map(function($it) {
    return ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0);
}, (array)$items));

$label_jenis_pm = [
    'sekaligus'       => 'Sekaligus',
    'bertahap'        => 'Bertahap — '.($pm->kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I'),
    'khusus_mendesak' => 'Khusus Mendesak',
    'khusus_bencana'  => 'Khusus Bencana',
];
$label_kelompok = $label_jenis_pm[$pm->jenis_penyaluran] ?? $pm->jenis_penyaluran;
?>

<!-- KOP -->
<div class="kop">
  <div class="kop-text">
    <h1>PEMERINTAH <?= strtoupper(htmlspecialchars($pm->nama_kabkota)) ?></h1>
    <p>SATUAN KERJA PENGELOLA KEUANGAN DAERAH (SKPKD)</p>
    <p>Alamat : ................................................................</p>
  </div>
</div>

<h2>REKAPITULASI KEGIATAN<br>PERMOHONAN PENCAIRAN BANTUAN KEUANGAN PROVINSI SUMATERA UTARA<br>TAHUN ANGGARAN <?= $pm->tahun ?></h2>

<!-- Info Permohonan -->
<div class="info-grid">
  <span class="lbl">No. Permohonan</span><span>:</span><span><?= htmlspecialchars($pm->no_permohonan) ?></span>
  <span class="lbl">Tanggal Permohonan</span><span>:</span><span><?= tgl_indo($pm->tgl_permohonan) ?></span>
  <span class="lbl">Kelompok Penyaluran</span><span>:</span><span><?= $label_kelompok ?></span>
  <span class="lbl">Kab/Kota</span><span>:</span><span><?= htmlspecialchars($pm->nama_kabkota) ?></span>
  <span class="lbl">Jumlah Pekerjaan</span><span>:</span><span><?= count($items) ?> kegiatan</span>
</div>

<!-- Tabel Pekerjaan -->
<table class="tbl">
  <thead>
    <tr>
      <th style="width:32px">No.</th>
      <th style="width:120px">Kode BKP</th>
      <th>Nama Kegiatan / Lokasi</th>
      <th style="width:100px">Penyedia</th>
      <th style="width:110px" class="right">Nilai Kontrak</th>
      <?php if ($is_tahap1): ?>
      <th style="width:100px" class="right">Nilai Tahap I (50%)</th>
      <?php endif; ?>
      <th style="width:100px" class="right">Nilai Pendukung</th>
      <th style="width:110px" class="right"><?= $is_tahap1 ? 'Nilai Pengajuan' : 'Nilai Diajukan' ?></th>
    </tr>
  </thead>
  <tbody>
  <?php $no = 1; foreach ($items as $item):
        $nilai_diajukan_item = ($item->nilai_diajukan ?? 0) + ($item->nilai_belanja_pendukung ?? 0);
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td><strong><?= htmlspecialchars($item->kode_bkp) ?></strong></td>
    <td>
      <?= htmlspecialchars($item->nama_kegiatan_dok ?: $item->uraian_bkp) ?>
      <?php if ($item->nama_penyedia): ?>
      <div style="font-size:9pt;color:#444">No. Kontrak: <?= htmlspecialchars($item->no_dok_pekerjaan ?: '—') ?></div>
      <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($item->nama_penyedia ?: '—') ?></td>
    <td class="right"><?= rupiah($item->nilai_kontrak) ?></td>
    <?php if ($is_tahap1): ?>
    <td class="right"><?= rupiah($item->nilai_diajukan ?? 0) ?></td>
    <?php endif; ?>
    <td class="right"><?= ($item->nilai_belanja_pendukung ?? 0) > 0 ? rupiah($item->nilai_belanja_pendukung) : '—' ?></td>
    <td class="right"><strong><?= rupiah($nilai_diajukan_item) ?></strong></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr style="background:#e8e8e8">
      <td colspan="4" style="text-align:right;font-weight:bold">TOTAL</td>
      <td class="right"><strong><?= rupiah($total_kontrak) ?></strong></td>
      <?php if ($is_tahap1): ?>
      <td class="right"><strong><?= rupiah($total_tahap1) ?></strong></td>
      <?php endif; ?>
      <td class="right"><strong><?= rupiah($total_pendukung) ?></strong></td>
      <td class="right"><strong><?= rupiah($total_nilai) ?></strong></td>
    </tr>
  </tfoot>
</table>

<?php if ($pm->catatan): ?>
<p style="font-size:10pt;margin-bottom:14px"><strong>Catatan:</strong> <?= htmlspecialchars($pm->catatan) ?></p>
<?php endif; ?>

<!-- TTD -->
<div class="ttd-area">
  <div class="ttd-blok">
    <p><?= htmlspecialchars($pm->nama_kabkota) ?>, <?= $tgl_cetak ?></p>
    <p>Kepala SKPKD / PPKD</p>
    <div class="garis"><?= $ppkd ? htmlspecialchars($ppkd->nama) : '....................................' ?></div>
    <?php if ($ppkd && $ppkd->nip): ?>
    <div class="nip">NIP. <?= htmlspecialchars($ppkd->nip) ?></div>
    <?php else: ?>
    <div class="nip">NIP. ..........................</div>
    <?php endif; ?>
  </div>
</div>

<div class="footer">
  Dicetak melalui SIBERKAH SUMUT · <?= $tgl_cetak ?>
</div>
</body>
</html>
