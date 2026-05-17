<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Akhir BKP — <?= htmlspecialchars($kabkota->nama ?? '') ?> TA <?= $tahun ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background:#fff; }

  /* Header */
  .kop      { display:flex; align-items:center; gap:14px; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 16px; }
  .kop-logo { flex-shrink:0; }
  .kop-text { flex:1; text-align:center; }
  .kop h1   { font-size: 14px; text-transform:uppercase; margin-bottom: 4px; }
  .kop h2   { font-size: 12px; text-transform:uppercase; }
  .kop p    { font-size: 10px; }

  /* Judul dokumen */
  .doc-title { text-align:center; margin: 14px 0 6px; }
  .doc-title h3 { font-size: 13px; text-transform:uppercase; text-decoration:underline; }
  .doc-title p  { font-size: 11px; }

  /* Tabel info */
  .info-table { width:100%; margin-bottom:14px; }
  .info-table td { padding: 2px 6px; vertical-align:top; }
  .info-table td:first-child { width:30%; font-weight:bold; }

  /* Tabel data */
  .tbl { width:100%; border-collapse:collapse; margin-bottom:16px; }
  .tbl th, .tbl td { border:1px solid #555; padding:4px 6px; vertical-align:top; }
  .tbl th { background:#d0d8e4; text-align:center; font-size:10px; }
  .tbl td.num { text-align:right; }
  .tbl td.cen { text-align:center; }
  .tbl tr.total td { font-weight:bold; background:#e8eef5; }
  .tbl tr.sub td { background:#f5f7fa; font-style:italic; font-size:10px; }
  .tbl .kode { font-family:monospace; font-size:10px; }
  .badge-status { padding:1px 5px; border-radius:3px; font-size:9px; font-weight:bold; }

  /* Progress bar cetak */
  .progress-cetak { display:inline-block; width:60px; height:7px; background:#ddd; border-radius:3px; vertical-align:middle; margin-right:3px; }
  .progress-fill  { height:100%; background:#276b2d; border-radius:3px; }

  /* Tanda tangan */
  .ttd-section { margin-top:24px; display:flex; justify-content:space-between; }
  .ttd-box { width:45%; text-align:center; }
  .ttd-box .ttd-label { font-size:10px; margin-bottom:56px; }
  .ttd-box .ttd-name  { font-size:11px; font-weight:bold; border-top:1px solid #000; padding-top:4px; }
  .ttd-box .ttd-nip   { font-size:10px; }

  @media print {
    body { font-size: 10px; }
    @page { margin: 1.5cm 1.5cm 2cm 2cm; }
  }

  .page-break { page-break-before: always; }
  .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

<!-- KOP -->
<div class="kop">
  <div class="kop-logo">
    <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH"
         style="height:68px;width:68px;object-fit:contain">
  </div>
  <div class="kop-text">
    <h1>LAPORAN AKHIR PELAKSANAAN BANTUAN KEUANGAN PROVINSI SUMATERA UTARA</h1>
    <h2>TAHUN ANGGARAN <?= $tahun ?></h2>
    <p><?= htmlspecialchars($kabkota->nama ?? '') ?></p>
  </div>
</div>

<div class="doc-title">
  <h3>Rekapitulasi Penyaluran dan Capaian Output Fisik</h3>
  <p>Berdasarkan SE Gubernur Sumatera Utara No. 900.1.1.3689 Tanggal 8 Mei 2026</p>
</div>

<table class="info-table">
  <tr><td>Kabupaten/Kota</td><td>: <?= htmlspecialchars($kabkota->nama ?? '') ?></td></tr>
  <tr><td>Tahun Anggaran</td><td>: <?= $tahun ?></td></tr>
  <tr><td>Total BKP</td><td>: <?= count($data_laporan) ?> Kegiatan</td></tr>
  <tr><td>Total Nilai BKP</td><td>: <?= rupiah($summary->total_nilai_bkp) ?></td></tr>
  <tr><td>Total Disalurkan</td><td>: <?= rupiah($summary->total_disalurkan) ?></td></tr>
  <tr><td>Dicetak oleh</td><td>: <?= htmlspecialchars($user_nama) ?> pada <?= tgl_indo($tgl_cetak) ?></td></tr>
</table>

<!-- ═══ TABEL REKAPITULASI ══════════════════════════════════════════════ -->
<table class="tbl">
  <thead>
    <tr>
      <th style="width:4%">No</th>
      <th style="width:12%">Kode BKP</th>
      <th>Uraian Kegiatan</th>
      <th style="width:9%">Bidang</th>
      <th style="width:12%" class="num">Nilai BKP</th>
      <th style="width:8%">Status</th>
      <th style="width:12%" class="num">Disalurkan</th>
      <th style="width:9%">Capaian Fisik</th>
    </tr>
  </thead>
  <tbody>
  <?php $no = 0; $grand_bkp = 0; $grand_disalurkan = 0;
  foreach ($data_laporan as $bkp):
    $no++;
    $grand_bkp += $bkp->nilai_bkp;
    $grand_disalurkan += $bkp->total_disalurkan;

    // Hitung rata-rata capaian
    $capaian_list = array_filter((array)$bkp->tahapan, fn($t) => $t->persen_fisik !== NULL);
    $avg_capaian  = count($capaian_list)
        ? round(array_sum(array_column($capaian_list, 'persen_fisik')) / count($capaian_list), 1)
        : NULL;
  ?>
  <tr class="no-break">
    <td class="cen"><?= $no ?></td>
    <td class="kode"><?= htmlspecialchars($bkp->kode_bkp) ?></td>
    <td>
      <strong><?= htmlspecialchars($bkp->nama_kegiatan_dok ?: $bkp->uraian_bkp) ?></strong>
      <?php if ($bkp->nama_penyedia): ?>
        <br><em><?= htmlspecialchars($bkp->nama_penyedia) ?></em>
      <?php endif; ?>
      <?php if ($bkp->no_dok_pekerjaan): ?>
        <br>Kontrak: <?= htmlspecialchars($bkp->no_dok_pekerjaan) ?>
        <?= $bkp->tgl_dok_pekerjaan ? ' / '.tgl_short($bkp->tgl_dok_pekerjaan) : '' ?>
      <?php endif; ?>
    </td>
    <td class="cen"><?= htmlspecialchars($bkp->kode_bidang) ?></td>
    <td class="num"><?= rupiah($bkp->nilai_bkp) ?></td>
    <td class="cen" style="font-size:9px">
      <?php
        $s_map = [
          'draft'=>'Draft','opd_submitted'=>'Diajukan',
          'inspektorat_reviu'=>'Reviu','inspektorat_approved'=>'Reviu OK',
          'skpkd_kab_verif'=>'Verif Kab','skpkd_kab_approved'=>'Verif Kab OK',
          'skpkd_prov_verif'=>'Verif Prov',
          'disalurkan_tahap1'=>'Disalurkan Thp I',
          'dikonfirmasi_tahap1'=>'Dikonfirmasi Thp I',
          'opd_capaian_tahap1'=>'Capaian Thp I',
          'disalurkan_sekaligus'=>'Disalurkan',
          'disalurkan_tahap2'=>'Disalurkan Thp II',
          'selesai'=>'Selesai','ditolak'=>'Ditolak',
        ];
        echo htmlspecialchars($s_map[$bkp->status] ?? ($bkp->status ?? 'Belum Input'));
      ?>
    </td>
    <td class="num"><?= $bkp->total_disalurkan ? rupiah($bkp->total_disalurkan) : '—' ?></td>
    <td class="cen">
      <?php if ($avg_capaian !== NULL): ?>
        <span class="progress-cetak"><span class="progress-fill" style="width:<?= $avg_capaian ?>%"></span></span>
        <strong><?= $avg_capaian ?>%</strong>
      <?php else: ?>—<?php endif; ?>
    </td>
  </tr>

  <!-- Sub-baris: detail penyaluran per tahapan -->
  <?php foreach ($bkp->tahapan as $t): if (!$t->no_sp2d && !$t->persen_fisik) continue; ?>
  <tr class="sub">
    <td></td>
    <td colspan="2" style="padding-left:16px">
      &rsaquo; <?= strtoupper(str_replace('_',' ',$t->kode_tahap)) ?>:
      <?php if ($t->no_sp2d): ?>
        SP2D <?= htmlspecialchars($t->no_sp2d) ?> tgl <?= tgl_short($t->tgl_sp2d) ?>
      <?php endif; ?>
      <?php if ($t->no_ba_kemajuan): ?>
        | BA <?= htmlspecialchars($t->no_ba_kemajuan) ?> tgl <?= tgl_short($t->tgl_ba_kemajuan) ?>
      <?php endif; ?>
      <?php if ($t->keterangan_capaian): ?>
        <br>&nbsp;&nbsp;&nbsp;&nbsp; <?= htmlspecialchars(substr($t->keterangan_capaian, 0, 120)) . (strlen($t->keterangan_capaian) > 120 ? '…' : '') ?>
      <?php endif; ?>
    </td>
    <td></td>
    <td class="num"><?= $t->nilai_transfer ? rupiah($t->nilai_transfer) : '' ?></td>
    <td></td>
    <td class="num"><?= $t->nilai_transfer ? rupiah($t->nilai_transfer) : '' ?></td>
    <td class="cen"><?= $t->persen_fisik !== NULL ? (float)$t->persen_fisik . '%' : '' ?></td>
  </tr>
  <?php endforeach; ?>

  <?php endforeach; ?>

  <tr class="total">
    <td colspan="4" class="cen">JUMLAH TOTAL</td>
    <td class="num"><?= rupiah($grand_bkp) ?></td>
    <td></td>
    <td class="num"><?= rupiah($grand_disalurkan) ?></td>
    <td class="cen">
      <?= $grand_bkp > 0 ? round(($grand_disalurkan / $grand_bkp) * 100, 1) . '%' : '—' ?>
    </td>
  </tr>
  </tbody>
</table>

<!-- ═══ TANDA TANGAN ════════════════════════════════════════════════════ -->
<div class="ttd-section">
  <div class="ttd-box">
    <div class="ttd-label">
      Mengetahui,<br>
      <?php if (!empty($pejabat['KDH'])): ?>
        Bupati/Walikota <?= htmlspecialchars(preg_replace('/^(Kab\.|Kota)\s+/i','',$kabkota->nama??'')) ?>
      <?php else: ?>
        Kepala Daerah
      <?php endif; ?>
    </div>
    <div class="ttd-name"><?= htmlspecialchars($pejabat['KDH']->nama ?? '____________________') ?></div>
    <?php if (!empty($pejabat['KDH']->nip)): ?>
    <div class="ttd-nip">NIP. <?= htmlspecialchars($pejabat['KDH']->nip) ?></div>
    <?php endif; ?>
  </div>

  <div class="ttd-box">
    <div class="ttd-label">
      <?= htmlspecialchars($kabkota->nama ?? '') ?>, <?= tgl_indo($tgl_cetak) ?><br>
      <?php if (!empty($pejabat['PPKD'])): ?>
        Pejabat Pengelola Keuangan Daerah (PPKD)
      <?php else: ?>
        Kepala BPKD/BKAD
      <?php endif; ?>
    </div>
    <div class="ttd-name"><?= htmlspecialchars($pejabat['PPKD']->nama ?? '____________________') ?></div>
    <?php if (!empty($pejabat['PPKD']->nip)): ?>
    <div class="ttd-nip">NIP. <?= htmlspecialchars($pejabat['PPKD']->nip) ?></div>
    <?php endif; ?>
  </div>
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
