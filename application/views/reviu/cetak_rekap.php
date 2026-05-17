<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Hasil Reviu — <?= htmlspecialchars($pekerjaan->kode_bkp) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:11pt;color:#000;padding:1.5cm 2cm;line-height:1.5}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
h2{font-size:12pt;text-align:center;text-transform:uppercase;margin:14px 0 10px;font-weight:bold}
.info-grid{display:grid;grid-template-columns:auto 10px 1fr;gap:2px 0;margin-bottom:14px;font-size:10pt}
.info-grid .lbl{font-weight:600}
.tbl{width:100%;border-collapse:collapse;font-size:10pt;margin-bottom:12px}
.tbl th,.tbl td{border:1px solid #000;padding:5px 8px;text-align:left}
.tbl th{background:#e8e8e8;font-weight:bold;text-align:center}
.tbl .center{text-align:center}
.tbl .sesuai{color:#166534}
.tbl .tidak_sesuai{color:#991b1b;font-weight:600;background:#fff5f5}
.hasil-box{padding:12px;border:2px solid #000;margin:12px 0;text-align:center;font-size:12pt}
.ttd{display:flex;justify-content:flex-end;margin-top:30px}
.ttd-blok{width:45%;text-align:center}
.ttd-blok .nama{margin-top:60px;font-weight:bold;text-decoration:underline}
.ttd-blok .nip{font-size:9pt}
@media print{body{padding:1cm 1.5cm}.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:7px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer">🖨️ Cetak</button>
</div>

<?php $p=$pekerjaan; $inspektur=$pejabat['inspektur']??NULL; ?>

<div class="kop">
  <h1>INSPEKTORAT <?= strtoupper(htmlspecialchars($p->nama_kabkota)) ?></h1>
  <p>Lampiran Laporan Hasil Reviu (LHR)</p>
</div>

<h2>REKAPITULASI HASIL REVIU<br>BANTUAN KEUANGAN PROVINSI SUMATERA UTARA</h2>

<div class="info-grid">
  <span class="lbl">No. LHR</span><span>:</span><span><strong><?= htmlspecialchars($reviu->no_lhr ?: '—') ?></strong></span>
  <span class="lbl">Tanggal LHR</span><span>:</span><span><?= tgl_indo($reviu->tgl_lhr) ?></span>
  <span class="lbl">Kode BKP</span><span>:</span><span class="mono"><?= htmlspecialchars($p->kode_bkp) ?></span>
  <span class="lbl">Nama Kegiatan</span><span>:</span><span><?= htmlspecialchars($p->nama_kegiatan_dok) ?></span>
  <span class="lbl">Tahapan</span><span>:</span><span><?= htmlspecialchars($tahapan->label_tahap) ?></span>
  <span class="lbl">Nilai Kontrak</span><span>:</span><span><?= rupiah($p->nilai_kontrak) ?></span>
  <span class="lbl">Periode Reviu</span><span>:</span><span><?= tgl_indo($reviu->tgl_reviu_mulai) ?> s/d <?= tgl_indo($reviu->tgl_reviu_selesai) ?></span>
</div>

<!-- Tabel hanya item bermasalah / semua -->
<?php $ada_masalah = $stat['tidak_sesuai'] > 0; ?>
<table class="tbl">
  <thead>
    <tr>
      <th style="width:50px">Kode</th>
      <th>Uraian Item</th>
      <th style="width:110px">Hasil</th>
      <th style="width:200px">Catatan</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $item):
    $is_val = $isian[$item->id] ?? NULL;
    $nilai  = $is_val ? $is_val->nilai : 'tidak_berlaku';
    $lmap   = ['sesuai'=>'Sesuai','tidak_sesuai'=>'Tidak Sesuai','tidak_berlaku'=>'N/A'];
  ?>
  <tr class="<?= $nilai ?>">
    <td class="center mono"><?= $item->kode ?></td>
    <td><?= htmlspecialchars($item->uraian_item) ?></td>
    <td class="center"><?= $lmap[$nilai] ?></td>
    <td><?= htmlspecialchars($is_val->catatan ?? '—') ?></td>
  </tr>
  <?php endforeach; ?>
  <tr style="background:#f5f5f5">
    <td colspan="2" style="text-align:right"><strong>Ringkasan:</strong></td>
    <td colspan="2">
      Sesuai: <strong><?= $stat['sesuai'] ?></strong> |
      Tidak Sesuai: <strong style="color:#991b1b"><?= $stat['tidak_sesuai'] ?></strong> |
      N/A: <?= $stat['tidak_berlaku'] ?> |
      Total: <?= count($items) ?>
    </td>
  </tr>
  </tbody>
</table>

<!-- Kesimpulan -->
<div class="hasil-box">
  <?php $hmap=['disetujui'=>'DISETUJUI ✓','perlu_perbaikan'=>'PERLU PERBAIKAN ⚠','ditolak'=>'DITOLAK ✗']; ?>
  HASIL REVIU: <strong><?= $hmap[$reviu->hasil_reviu] ?? strtoupper($reviu->hasil_reviu) ?></strong>
  <?php if ($reviu->catatan): ?>
  <div style="font-size:10pt;margin-top:6px;font-style:italic"><?= htmlspecialchars($reviu->catatan) ?></div>
  <?php endif; ?>
</div>

<div class="ttd">
  <div class="ttd-blok">
    <p><?= htmlspecialchars($p->nama_kabkota) ?>, <?= $tgl_cetak ?></p>
    <p><?= $inspektur ? htmlspecialchars($inspektur->jabatan ?? 'Inspektur') : 'Inspektur' ?></p>
    <div class="nama"><?= $inspektur ? htmlspecialchars($inspektur->nama) : '......................................' ?></div>
    <?php if ($inspektur && $inspektur->nip): ?>
    <div class="nip">NIP. <?= htmlspecialchars($inspektur->nip) ?></div>
    <?php else: ?>
    <div class="nip">NIP. ............................</div>
    <?php endif; ?>
  </div>
</div>

<div style="font-size:9pt;color:#666;border-top:1px solid #ccc;padding-top:6px;margin-top:14px">
  SIBERKAH SUMUT · Berdasarkan SE Gubernur No. 900.1.1.3689 · <?= $tgl_cetak ?>
</div>
</body>
</html>
