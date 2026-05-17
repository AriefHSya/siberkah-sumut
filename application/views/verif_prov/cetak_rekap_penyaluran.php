<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Penyaluran BKP TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:10.5pt;color:#000;padding:1cm 1.5cm;line-height:1.5}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:14px}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
h2{font-size:11pt;text-align:center;text-transform:uppercase;margin:10px 0 8px;font-weight:bold}
.filter-info{font-size:9pt;color:#555;margin-bottom:12px;text-align:center}
.tbl{width:100%;border-collapse:collapse;font-size:9.5pt;margin-bottom:14px}
.tbl th,.tbl td{border:1px solid #000;padding:4px 6px;text-align:left;vertical-align:top}
.tbl th{background:#e0e0e0;font-weight:bold;text-align:center}
.tbl .right{text-align:right}
.tbl .center{text-align:center}
.tbl .total{background:#f5f5f5;font-weight:bold}
.summary-box{display:flex;gap:20px;margin:12px 0;justify-content:center}
.sum-item{padding:8px 16px;border:1px solid #000;text-align:center;min-width:130px}
.sum-item strong{display:block;font-size:14pt}
.sum-item span{font-size:9pt;color:#555}
.ttd{display:flex;justify-content:flex-end;margin-top:30px}
.ttd-blok{width:40%;text-align:center}
.ttd-blok .garis{margin-top:60px;border-top:1px solid #000;padding-top:2px;font-weight:bold;text-decoration:underline}
.ttd-blok .nip{font-size:9pt}
.footer{font-size:9pt;color:#666;border-top:1px solid #ccc;padding-top:6px;margin-top:14px;text-align:center}
@media print{body{padding:0.8cm 1.2cm}.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px;display:flex;gap:6px">
  <form method="get" style="display:flex;gap:4px">
    <select name="kabkota_id" style="font-size:12px;padding:4px 6px;border:1px solid #ccc;border-radius:4px">
      <option value="">Semua Kab/Kota</option>
      <?php foreach ($kabkota_list as $k): ?>
      <option value="<?= $k->id ?>" <?= ($kabkota && $kabkota->id==$k->id)?'selected':'' ?>>
        <?= $k->nama ?>
      </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="tahun" value="<?= $tahun ?>">
    <button type="submit" style="padding:4px 8px;font-size:12px;border:1px solid #ccc;border-radius:4px;cursor:pointer">Filter</button>
  </form>
  <button onclick="window.print()" style="padding:6px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px">🖨️ Cetak</button>
</div>

<div class="kop">
  <h1>BADAN KEUANGAN DAN ASET DAERAH<br>PROVINSI SUMATERA UTARA</h1>
</div>

<h2>REKAPITULASI PENYALURAN BANTUAN KEUANGAN PROVINSI<br>
KEPADA KABUPATEN/KOTA SE-SUMATERA UTARA<br>
TAHUN ANGGARAN <?= $tahun ?></h2>

<div class="filter-info">
  <?= $kabkota ? 'Kabupaten/Kota: <strong>'.htmlspecialchars($kabkota->nama).'</strong>' : 'Seluruh Kabupaten/Kota' ?>
  · Dicetak: <?= $tgl_cetak ?>
</div>

<!-- Ringkasan -->
<div class="summary-box">
  <div class="sum-item">
    <strong><?= $rekap->total_sp2d ?? 0 ?></strong>
    <span>SP2D Diterbitkan</span>
  </div>
  <div class="sum-item">
    <strong><?= number_format(($rekap->total_disalurkan ?? 0)/1000000, 2, ',', '.') ?> Jt</strong>
    <span>Total Disalurkan</span>
  </div>
  <div class="sum-item">
    <strong><?= $rekap->total_dikonfirmasi ?? 0 ?></strong>
    <span>Dikonfirmasi Kab</span>
  </div>
</div>

<!-- Tabel SP2D -->
<table class="tbl">
  <thead>
    <tr>
      <th style="width:30px">No.</th>
      <th>Kab/Kota</th>
      <th>Kode BKP</th>
      <th>Nama Kegiatan</th>
      <th>Tahapan</th>
      <th>Jenis</th>
      <th>No. SP2D</th>
      <th>Tgl SP2D</th>
      <th class="right">Nilai Transfer (Rp)</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $no = 1;
  $grand_total = 0;
  $kab_saat_ini = '';

  if (!empty($daftar_sp2d)):
    foreach ($daftar_sp2d as $row):
      $grand_total += $row->nilai_transfer;
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row->nama_kabkota) ?></td>
    <td class="mono" style="font-size:9pt"><?= htmlspecialchars($row->kode_bkp) ?></td>
    <td style="font-size:9pt"><?= htmlspecialchars($row->nama_kegiatan_dok ?: $row->uraian_bkp) ?></td>
    <td class="center" style="font-size:9pt"><?= htmlspecialchars($row->label_tahap) ?></td>
    <td class="center" style="font-size:9pt"><?= ucfirst(str_replace('_',' ',$row->jenis_penyaluran)) ?></td>
    <td class="mono" style="font-size:9pt"><?= htmlspecialchars($row->no_sp2d) ?></td>
    <td class="center" style="font-size:9pt"><?= tgl_short($row->tgl_sp2d) ?></td>
    <td class="right"><?= number_format($row->nilai_transfer, 0, ',', '.') ?></td>
    <td class="center" style="font-size:9pt">
      <?= strtoupper($row->status_transfer) ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="8" class="right">TOTAL (<?= count($daftar_sp2d) ?> SP2D)</td>
    <td class="right"><?= number_format($grand_total, 0, ',', '.') ?></td>
    <td></td>
  </tr>
  <?php else: ?>
  <tr><td colspan="10" class="center" style="padding:20px;color:#888">Belum ada data penyaluran.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<div class="ttd">
  <div class="ttd-blok">
    <p>Medan, <?= $tgl_cetak ?></p>
    <p>Kepala BKAD Provinsi Sumatera Utara</p>
    <div class="garis">............................................</div>
    <div class="nip">NIP. ..............................</div>
  </div>
</div>

<div class="footer">
  SIBERKAH SUMUT · Sistem Informasi Bantuan Keuangan Daerah · Prov. Sumatera Utara ·
  Berdasarkan SE Gubernur No. 900.1.1.3689 · <?= $tgl_cetak ?>
</div>
</body>
</html>
