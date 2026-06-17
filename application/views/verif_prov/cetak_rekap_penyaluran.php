<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Penyaluran BKP TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:10.5pt;color:#000;padding:1cm 1.5cm;line-height:1.5}
.kop{border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px;display:flex;align-items:center;gap:14px}.kop-logo{flex-shrink:0}.kop-text{flex:1;text-align:center}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
h2{font-size:11pt;text-align:center;text-transform:uppercase;margin:10px 0 8px;font-weight:bold}
.filter-info{font-size:9pt;color:#555;margin-bottom:12px;text-align:center}
.tbl{width:100%;border-collapse:collapse;font-size:9.5pt;margin-bottom:14px}
.tbl th,.tbl td{border:1px solid #000;padding:4px 6px;text-align:left;vertical-align:top}
.tbl th{background:#e0e0e0;font-weight:bold;text-align:center}
.tbl .right{text-align:right}
.tbl .center{text-align:center}
.tbl .total{background:#f5f5f5;font-weight:bold}
.tbl-rincian{width:100%;border-collapse:collapse;font-size:8.5pt;margin:2px 0 6px}
.tbl-rincian th,.tbl-rincian td{border:1px solid #999;padding:3px 6px;text-align:left}
.tbl-rincian th{background:#f0f0f0;font-weight:bold;text-align:center}
.tbl-rincian .right{text-align:right}
.tbl-rincian .total{background:#f5f5f5;font-weight:bold}
.rincian-label{font-size:8.5pt;font-weight:bold;color:#444;margin-bottom:2px}
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
  <div class="kop-logo"><img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH" style="height:64px;width:64px;object-fit:contain"></div>
  <div class="kop-text"><h1>BADAN KEUANGAN DAN ASET DAERAH<br>PROVINSI SUMATERA UTARA</h1></div>
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
<?php
function _label_jenis_sp2d($jenis, $kode) {
    if ($jenis === 'bertahap') return 'Bertahap ' . ($kode === 'tahap_1' ? 'Tahap I' : 'Tahap II');
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return isset($m[$jenis]) ? $m[$jenis] : ucfirst(str_replace('_',' ',$jenis));
}
?>
<table class="tbl">
  <thead>
    <tr>
      <th style="width:30px">No.</th>
      <th>No. Permohonan</th>
      <th>Kab/Kota</th>
      <th>Jenis Penyaluran</th>
      <th>No. SP2D</th>
      <th>Tgl SP2D</th>
      <th class="center" style="width:60px">Jml Keg.</th>
      <th class="right">Nilai Transfer (Rp)</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $no = 1;
  $grand_total = 0;

  if (!empty($daftar_sp2d)):
    foreach ($daftar_sp2d as $row):
      $grand_total += $row->nilai_transfer;
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td style="font-size:9pt"><?= htmlspecialchars($row->no_permohonan) ?></td>
    <td><?= htmlspecialchars($row->nama_kabkota) ?></td>
    <td class="center" style="font-size:9pt"><?= _label_jenis_sp2d($row->jenis_penyaluran, $row->kode_tahap) ?></td>
    <td style="font-size:9pt"><?= htmlspecialchars($row->no_sp2d) ?></td>
    <td class="center" style="font-size:9pt"><?= tgl_short($row->tgl_sp2d) ?></td>
    <td class="center"><?= (int)($row->jumlah_item ?? 0) ?></td>
    <td class="right"><?= number_format($row->nilai_transfer, 0, ',', '.') ?></td>
    <td class="center" style="font-size:9pt"><?= strtoupper($row->status_transfer) ?></td>
  </tr>
  <?php if (!empty($row->items)): ?>
  <tr>
    <td></td>
    <td colspan="8" style="padding:6px 8px 8px">
      <div class="rincian-label">Rincian Kegiatan:</div>
      <table class="tbl-rincian">
        <thead>
          <tr>
            <th style="width:25px">No.</th>
            <th>Kode BKP</th>
            <th>Uraian Kegiatan</th>
            <th class="right" style="width:140px">Nilai Diajukan (Rp)</th>
            <th class="right" style="width:140px">Nilai Pendukung (Rp)</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sub_no = 1;
        $sub_total_diajukan  = 0;
        $sub_total_pendukung = 0;
        foreach ($row->items as $item):
          $sub_total_diajukan  += $item->nilai_diajukan;
          $sub_total_pendukung += $item->nilai_belanja_pendukung;
        ?>
        <tr>
          <td class="center"><?= $sub_no++ ?></td>
          <td><?= htmlspecialchars($item->kode_bkp) ?></td>
          <td><?= htmlspecialchars($item->uraian_bkp) ?></td>
          <td class="right"><?= number_format($item->nilai_diajukan, 0, ',', '.') ?></td>
          <td class="right"><?= $item->nilai_belanja_pendukung ? number_format($item->nilai_belanja_pendukung, 0, ',', '.') : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
          <td colspan="3" class="right">Sub Total</td>
          <td class="right"><?= number_format($sub_total_diajukan, 0, ',', '.') ?></td>
          <td class="right"><?= $sub_total_pendukung ? number_format($sub_total_pendukung, 0, ',', '.') : '-' ?></td>
        </tr>
        </tbody>
      </table>
    </td>
  </tr>
  <?php endif; ?>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="7" class="right">TOTAL (<?= count($daftar_sp2d) ?> SP2D)</td>
    <td class="right"><?= number_format($grand_total, 0, ',', '.') ?></td>
    <td></td>
  </tr>
  <?php else: ?>
  <tr><td colspan="9" class="center" style="padding:20px;color:#888">Belum ada data penyaluran.</td></tr>
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
  SIBERKAH SUMUT · Sistem Informasi Bantuan Keuangan Daerah · Prov. Sumatera Utara
</div>
</body>
</html>
