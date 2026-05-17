<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Kegiatan BKP — <?= htmlspecialchars($pekerjaan->kode_bkp) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:11pt;color:#000;padding:1.5cm 2cm;line-height:1.6}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:10px;margin-bottom:18px}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
.kop p{font-size:10pt}
h2{font-size:12pt;text-align:center;text-transform:uppercase;margin:14px 0 10px;font-weight:bold}
.info-grid{display:grid;grid-template-columns:auto 8px 1fr;gap:2px;margin-bottom:16px;font-size:10pt}
.info-grid .lbl{font-weight:600;white-space:nowrap}
.tbl{width:100%;border-collapse:collapse;font-size:10pt;margin-bottom:14px}
.tbl th,.tbl td{border:1px solid #000;padding:5px 8px;text-align:left;vertical-align:top}
.tbl th{background:#e8e8e8;font-weight:bold;text-align:center}
.right{text-align:right}
.center{text-align:center}
.ttd-area{display:flex;justify-content:space-between;margin-top:40px}
.ttd-blok{width:42%;text-align:center}
.ttd-blok .garis{margin-top:65px;border-top:1px solid #000;padding-top:2px;font-weight:bold;text-decoration:underline;font-size:10pt}
.ttd-blok .nip{font-size:9pt}
.footer{font-size:9pt;color:#666;border-top:1px solid #ccc;padding-top:8px;margin-top:14px;text-align:center}
.box-status{padding:10px 14px;border:2px solid #000;margin:14px 0;text-align:center;font-size:11pt}
@media print{body{padding:1cm 1.5cm}.no-print{display:none}}
</style>
</head>
<body>

<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:7px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Cetak PDF</button>
</div>

<?php $p=$pekerjaan; $ppkd=$pejabat['kepala_bkad']??NULL; $kdh=$pejabat['kepala_daerah']??NULL; ?>

<!-- KOP SURAT -->
<div class="kop">
  <h1>PEMERINTAH <?= strtoupper(htmlspecialchars($p->nama_kabkota)) ?></h1>
  <p>SATUAN KERJA PENGELOLA KEUANGAN DAERAH (SKPKD)</p>
  <p>Alamat : ................................................................</p>
</div>

<h2>REKAPITULASI KEGIATAN<br>BANTUAN KEUANGAN PROVINSI SUMATERA UTARA<br>TAHUN ANGGARAN <?= $p->tahun ?></h2>

<!-- Data BKP & Pekerjaan -->
<div class="info-grid">
  <span class="lbl">Kode BKP</span><span>:</span><span class="mono"><?= htmlspecialchars($p->kode_bkp) ?></span>
  <span class="lbl">Uraian BKP</span><span>:</span><span><?= htmlspecialchars($p->uraian_bkp) ?></span>
  <span class="lbl">Nama Kegiatan</span><span>:</span><span><?= htmlspecialchars($p->nama_kegiatan_dok) ?></span>
  <span class="lbl">Tahapan</span><span>:</span><span><?= htmlspecialchars($tahapan->label_tahap) ?> (<?= $tahapan->persen_nilai ?>%)</span>
  <span class="lbl">Jenis Penyaluran</span><span>:</span><span><?= ucfirst(str_replace('_',' ',$p->jenis_penyaluran)) ?></span>
  <span class="lbl">Nilai BKP</span><span>:</span><span><?= rupiah($p->nilai_bkp) ?></span>
  <span class="lbl">Nilai Kontrak</span><span>:</span><span><strong><?= rupiah($p->nilai_kontrak) ?></strong></span>
  <span class="lbl">Nilai Diajukan</span><span>:</span><span><strong><?= rupiah($tahapan->nilai_diajukan) ?></strong></span>
  <span class="lbl">Nama Penyedia</span><span>:</span><span><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></span>
  <span class="lbl">No. Kontrak / MoU</span><span>:</span><span><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></span>
  <span class="lbl">No. SPMK</span><span>:</span><span><?= htmlspecialchars($p->no_spmk ?: '—') ?></span>
  <span class="lbl">Lokasi</span><span>:</span><span><?= htmlspecialchars($p->lokasi_deskripsi ?: '—') ?></span>
  <?php if ($perda): ?><span class="lbl">Perda APBD</span><span>:</span><span>No. <?= htmlspecialchars($perda->nomor) ?> / <?= tgl_indo($perda->tanggal) ?></span><?php endif; ?>
  <?php if ($perkada): ?><span class="lbl">Perkada/Pergub</span><span>:</span><span>No. <?= htmlspecialchars($perkada->nomor) ?> / <?= tgl_indo($perkada->tanggal) ?></span><?php endif; ?>
</div>

<!-- Tabel Dokumen Persyaratan -->
<?php if (!empty($dokumen)): ?>
<table class="tbl">
  <thead>
    <tr><th style="width:40px">No.</th><th>Jenis Dokumen</th><th style="width:80px">Ada/Tidak</th><th>Keterangan</th></tr>
  </thead>
  <tbody>
  <?php $no=1; foreach ($dokumen as $d): ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td><?= label_jenis_dok($d->jenis_dokumen) ?></td>
    <td class="center" style="color:green;font-weight:bold">✓</td>
    <td><?= htmlspecialchars($d->keterangan ?: '—') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- Status Reviu Inspektorat -->
<?php if ($reviu): ?>
<table class="tbl">
  <thead><tr><th colspan="2">HASIL REVIU INSPEKTORAT</th></tr></thead>
  <tbody>
    <tr><td style="width:45%">No. LHR</td><td><strong><?= htmlspecialchars($reviu->no_lhr ?: '—') ?></strong></td></tr>
    <tr><td>Tanggal LHR</td><td><?= tgl_indo($reviu->tgl_lhr) ?></td></tr>
    <tr><td>Hasil Reviu</td><td><strong><?= strtoupper(str_replace('_',' ',$reviu->hasil_reviu ?: '—')) ?></strong></td></tr>
    <?php if ($reviu->catatan): ?><tr><td>Catatan</td><td><?= htmlspecialchars($reviu->catatan) ?></td></tr><?php endif; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- Verifikasi SKPKD -->
<?php if ($verif): ?>
<table class="tbl">
  <thead><tr><th colspan="2">HASIL VERIFIKASI SKPKD KAB/KOTA</th></tr></thead>
  <tbody>
    <tr><td style="width:45%">No. Surat Verifikasi</td><td><?= htmlspecialchars($verif->no_surat_verif ?: '—') ?></td></tr>
    <tr><td>Tanggal Verifikasi</td><td><?= tgl_indo($verif->tgl_verifikasi) ?></td></tr>
    <tr><td>Hasil Verifikasi</td><td><strong><?= strtoupper(str_replace('_',' ',$verif->hasil_verifikasi ?: '—')) ?></strong></td></tr>
    <?php if ($verif->catatan): ?><tr><td>Catatan</td><td><?= htmlspecialchars($verif->catatan) ?></td></tr><?php endif; ?>
    <?php if ($verif->nama_ppkd): ?><tr><td>Verifikator (PPKD)</td><td><?= htmlspecialchars($verif->nama_ppkd) ?></td></tr><?php endif; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- Data Penyaluran jika sudah ada -->
<?php if ($penyaluran): ?>
<table class="tbl">
  <thead><tr><th colspan="2">DATA PENYALURAN DANA</th></tr></thead>
  <tbody>
    <tr><td style="width:45%">No. SP2D</td><td class="mono"><strong><?= htmlspecialchars($penyaluran->no_sp2d) ?></strong></td></tr>
    <tr><td>Tanggal SP2D</td><td><?= tgl_indo($penyaluran->tgl_sp2d) ?></td></tr>
    <tr><td>Nilai Transfer</td><td><strong><?= rupiah($penyaluran->nilai_transfer) ?></strong></td></tr>
    <tr><td>Rekening Tujuan (RKUD)</td><td><?= htmlspecialchars($penyaluran->rek_tujuan ?? '—') ?></td></tr>
    <tr><td>Status Transfer</td><td><strong><?= strtoupper($penyaluran->status_transfer) ?></strong></td></tr>
    <?php if ($penyaluran->bukti_path): ?><tr><td>Bukti Transfer RKUD</td><td>Tersedia</td></tr><?php endif; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- TTD -->
<div class="ttd-area">
  <div class="ttd-blok">
    <p><?= $kdh ? 'Bupati/Wali Kota '.htmlspecialchars($p->nama_kabkota) : 'Kepala Daerah' ?></p>
    <div class="garis"><?= $kdh ? htmlspecialchars($kdh->nama) : '....................................' ?></div>
    <?php if ($kdh && $kdh->nip): ?><div class="nip">NIP. <?= htmlspecialchars($kdh->nip) ?></div><?php else: ?><div class="nip">NIP. ..........................</div><?php endif; ?>
  </div>
  <div class="ttd-blok">
    <p><?= htmlspecialchars($p->nama_kabkota) ?>, <?= $tgl_cetak ?></p>
    <p>Kepala BKAD / PPKD</p>
    <div class="garis"><?= $ppkd ? htmlspecialchars($ppkd->nama) : '....................................' ?></div>
    <?php if ($ppkd && $ppkd->nip): ?><div class="nip">NIP. <?= htmlspecialchars($ppkd->nip) ?></div><?php else: ?><div class="nip">NIP. ..........................</div><?php endif; ?>
  </div>
</div>

<div class="footer">
  Dicetak melalui SIBERKAH SUMUT · Berdasarkan SE Gubernur Sumut No. 900.1.1.3689 · <?= $tgl_cetak ?>
</div>
</body>
</html>
