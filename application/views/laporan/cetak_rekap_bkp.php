<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap BKP TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',serif;font-size:10pt;padding:1cm 1.5cm;line-height:1.4}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:12px}
.kop h1{font-size:12pt;font-weight:bold;text-transform:uppercase}
h2{font-size:11pt;text-align:center;text-transform:uppercase;margin:10px 0 8px;font-weight:bold}
.filter-info{text-align:center;font-size:9pt;color:#555;margin-bottom:12px}
.sum-row{display:flex;gap:16px;justify-content:center;margin:10px 0}
.sum-item{padding:6px 14px;border:1px solid #000;text-align:center;min-width:110px}
.sum-item strong{display:block;font-size:12pt}
.tbl{width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:12px}
.tbl th,.tbl td{border:1px solid #000;padding:3px 5px;text-align:left;vertical-align:top}
.tbl th{background:#e0e0e0;font-weight:bold;text-align:center}
.tbl .right{text-align:right} .tbl .center{text-align:center}
.tbl .total{background:#f0f0f0;font-weight:bold}
.ttd{display:flex;justify-content:flex-end;margin-top:24px}
.ttd-blok{width:38%;text-align:center}
.ttd-blok .garis{margin-top:55px;border-top:1px solid #000;padding-top:2px;font-weight:bold;font-size:9pt}
.footer{font-size:8pt;color:#666;border-top:1px solid #ccc;padding-top:6px;margin-top:12px;text-align:center}
@media print{body{padding:0.8cm 1.2cm}.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:6px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:4px;cursor:pointer">🖨️ Cetak</button>
</div>

<div class="kop">
  <h1>BADAN KEUANGAN DAN ASET DAERAH PROVINSI SUMATERA UTARA</h1>
</div>
<h2>REKAPITULASI DATA BKP TA <?= $tahun ?><?= $kabkota ? '<br>'.strtoupper(htmlspecialchars($kabkota->nama)) : '' ?></h2>
<div class="filter-info">Dicetak: <?= $tgl_cetak ?> oleh <?= htmlspecialchars($nama_user) ?></div>

<div class="sum-row">
  <div class="sum-item"><strong><?= number_format($summary->total_bkp ?? 0) ?></strong><span>Total BKP</span></div>
  <div class="sum-item"><strong><?= number_format(($summary->total_nilai_bkp??0)/1000000,1,',','.') ?> Jt</strong><span>Nilai BKP</span></div>
  <div class="sum-item"><strong><?= number_format($summary->total_pekerjaan ?? 0) ?></strong><span>Ada Pekerjaan</span></div>
  <div class="sum-item"><strong><?= number_format(($summary->total_disalurkan??0)/1000000,1,',','.') ?> Jt</strong><span>Disalurkan</span></div>
</div>

<table class="tbl">
  <thead>
    <tr>
      <th style="width:22px">No.</th>
      <th style="width:80px">Kode BKP</th>
      <th>Kab/Kota</th>
      <th>Uraian BKP</th>
      <th style="width:55px">Bidang</th>
      <th class="right" style="width:80px">Nilai BKP</th>
      <th style="width:55px">Jenis</th>
      <th class="right" style="width:80px">Kontrak</th>
      <th style="width:70px">Status</th>
      <th class="right" style="width:80px">Disalurkan</th>
    </tr>
  </thead>
  <tbody>
  <?php $no=1; $g_bkp=0; $g_kontrak=0; $g_sal=0;
  foreach ($list as $r):
    $g_bkp+=$r->nilai_bkp; $g_kontrak+=($r->nilai_kontrak??0); $g_sal+=($r->total_disalurkan??0);
    $st_label=['draft'=>'Draft','opd_submitted'=>'Diajukan','inspektorat_approved'=>'Reviu OK',
               'skpkd_kab_approved'=>'Verif. Kab','disalurkan_tahap1'=>'Salur T.I',
               'disalurkan_sekaligus'=>'Disalurkan','dikonfirmasi'=>'Konfirmasi','selesai'=>'Selesai',
               'ditolak'=>'Ditolak'];
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td class="mono" style="font-size:8pt"><?= htmlspecialchars($r->kode_bkp) ?></td>
    <td><?= htmlspecialchars($r->nama_kabkota) ?></td>
    <td><?= htmlspecialchars($r->uraian_bkp) ?></td>
    <td class="center"><?= htmlspecialchars($r->nama_bidang) ?></td>
    <td class="right"><?= number_format($r->nilai_bkp,0,',','.') ?></td>
    <td class="center" style="font-size:7.5pt"><?= $r->jenis_penyaluran ? ucfirst(str_replace(['_',' '],'-',$r->jenis_penyaluran)) : '—' ?></td>
    <td class="right"><?= $r->nilai_kontrak ? number_format($r->nilai_kontrak,0,',','.') : '—' ?></td>
    <td class="center" style="font-size:7.5pt"><?= $r->status ? ($st_label[$r->status] ?? $r->status) : 'Blm Input' ?></td>
    <td class="right"><?= $r->total_disalurkan ? number_format($r->total_disalurkan,0,',','.') : '—' ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="5" class="right">TOTAL (<?= count($list) ?> BKP)</td>
    <td class="right"><?= number_format($g_bkp,0,',','.') ?></td>
    <td></td>
    <td class="right"><?= number_format($g_kontrak,0,',','.') ?></td>
    <td></td>
    <td class="right"><?= number_format($g_sal,0,',','.') ?></td>
  </tr>
  </tbody>
</table>

<div class="ttd">
  <div class="ttd-blok">
    <p>Medan, <?= $tgl_cetak ?></p>
    <p>Kepala BKAD Provinsi Sumatera Utara</p>
    <div class="garis">.................................................</div>
    <div style="font-size:8pt">NIP. ................................</div>
  </div>
</div>
<div class="footer">SIBERKAH SUMUT · SE Gubernur Sumut No. 900.1.1.3689 · <?= $tgl_cetak ?></div>
</body>
</html>
