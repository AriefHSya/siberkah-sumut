<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kertas Kerja Reviu — <?= htmlspecialchars($pekerjaan->kode_bkp) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:11pt;color:#000;padding:1.5cm 2cm;line-height:1.5}
.kop{border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px;display:flex;align-items:center;gap:14px}.kop-logo{flex-shrink:0}.kop-text{flex:1;text-align:center}
.kop h1{font-size:13pt;text-transform:uppercase;font-weight:bold}
.kop p{font-size:10pt}
h2{font-size:12pt;text-align:center;text-transform:uppercase;margin:14px 0 10px;font-weight:bold}
.info-grid{display:grid;grid-template-columns:auto 10px 1fr;gap:2px 0;margin-bottom:14px;font-size:10pt}
.info-grid .lbl{font-weight:600;white-space:nowrap}
.tbl{width:100%;border-collapse:collapse;font-size:10pt;margin-bottom:12px}
.tbl th,.tbl td{border:1px solid #000;padding:5px 7px;vertical-align:top;text-align:left}
.tbl th{background:#e8e8e8;font-weight:bold;text-align:center}
.tbl .center{text-align:center}
.tbl .sesuai{color:#166534;font-weight:600}
.tbl .tidak_sesuai{color:#991b1b;font-weight:600}
.tbl .tidak_berlaku{color:#6b7280}
.tbl .masalah{background:#fff5f5}
.summary{display:flex;gap:20px;margin:10px 0;font-size:10pt}
.sum-item{padding:6px 14px;border:1px solid #000;text-align:center}
.sum-item strong{display:block;font-size:14pt}
.ttd{display:flex;justify-content:space-between;margin-top:30px}
.ttd-blok{width:44%;text-align:center}
.ttd-blok .nama{margin-top:60px;font-weight:bold;text-decoration:underline}
.ttd-blok .nip{font-size:9pt}
.footer-note{font-size:9pt;color:#555;border-top:1px solid #ccc;padding-top:8px;margin-top:14px}
@media print{body{padding:1cm 1.5cm}.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px">
  <button onclick="window.print()" style="padding:7px 14px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer">🖨️ Cetak</button>
</div>

<?php $p=$pekerjaan; $inspektur=$pejabat['inspektur']??NULL; $kdh=$pejabat['kepala_daerah']??NULL; ?>

<!-- KOP -->
<div class="kop">
  <div class="kop-logo"><img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH" style="height:64px;width:64px;object-fit:contain"></div>
  <div class="kop-text"><h1>INSPEKTORAT <?= strtoupper(htmlspecialchars($p->nama_kabkota)) ?></h1>
  <p>Pemerintah <?= htmlspecialchars($p->nama_kabkota) ?></p></div>
</div>

<h2>KERTAS KERJA REVIU<br>BANTUAN KEUANGAN PROVINSI SUMATERA UTARA<br>TAHUN ANGGARAN <?= $p->tahun ?></h2>

<!-- Info Pekerjaan -->
<div class="info-grid">
  <span class="lbl">Kode BKP</span><span>:</span><span class="mono"><?= htmlspecialchars($p->kode_bkp) ?></span>
  <span class="lbl">Uraian BKP</span><span>:</span><span><?= htmlspecialchars($p->uraian_bkp) ?></span>
  <span class="lbl">Nama Kegiatan</span><span>:</span><span><?= htmlspecialchars($p->nama_kegiatan_dok) ?></span>
  <span class="lbl">Tahapan</span><span>:</span><span><?= htmlspecialchars($tahapan->label_tahap) ?></span>
  <span class="lbl">Jenis Penyaluran</span><span>:</span><span><?= ucfirst(str_replace('_',' ',$p->jenis_penyaluran)) ?></span>
  <span class="lbl">Nilai Kontrak</span><span>:</span><span><?= rupiah($p->nilai_kontrak) ?></span>
  <span class="lbl">Nama Penyedia</span><span>:</span><span><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></span>
  <span class="lbl">No. Kontrak</span><span>:</span><span><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></span>
  <span class="lbl">No. SPMK</span><span>:</span><span><?= htmlspecialchars($p->no_spmk ?: '—') ?></span>
  <span class="lbl">Tgl. Reviu Mulai</span><span>:</span><span><?= tgl_indo($reviu->tgl_reviu_mulai) ?></span>
  <?php if ($reviu->tgl_reviu_selesai): ?>
  <span class="lbl">Tgl. Reviu Selesai</span><span>:</span><span><?= tgl_indo($reviu->tgl_reviu_selesai) ?></span>
  <?php endif; ?>
</div>

<!-- Ringkasan Checklist -->
<div class="summary">
  <div class="sum-item"><strong class="sesuai"><?= $stat['sesuai'] ?></strong>Sesuai</div>
  <div class="sum-item"><strong class="tidak_sesuai"><?= $stat['tidak_sesuai'] ?></strong>Tidak Sesuai</div>
  <div class="sum-item"><strong class="tidak_berlaku"><?= $stat['tidak_berlaku'] ?></strong>Tidak Berlaku</div>
  <div class="sum-item"><strong><?= count($items) ?></strong>Total Item</div>
</div>

<!-- Tabel Checklist -->
<table class="tbl">
  <thead>
    <tr>
      <th style="width:50px">Kode</th>
      <th>Uraian Item Pemeriksaan</th>
      <th style="width:110px">Hasil</th>
      <th style="width:180px">Catatan Pemeriksa</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $item):
    $is_val = $isian[$item->id] ?? NULL;
    $nilai  = $is_val ? $is_val->nilai : 'tidak_berlaku';
    $label_map = ['sesuai'=>'Sesuai','tidak_sesuai'=>'Tidak Sesuai','tidak_berlaku'=>'N/A'];
    $is_masalah = $nilai === 'tidak_sesuai';
  ?>
  <tr <?= $is_masalah ? 'class="masalah"' : '' ?>>
    <td class="center mono"><?= $item->kode ?></td>
    <td><?= htmlspecialchars($item->uraian_item) ?></td>
    <td class="center <?= $nilai ?>"><strong><?= $label_map[$nilai] ?></strong></td>
    <td><?= htmlspecialchars($is_val->catatan ?? '—') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Kesimpulan -->
<?php if ($reviu->hasil_reviu): ?>
<div style="margin:16px 0;padding:10px;border:2px solid #000">
  <strong>KESIMPULAN REVIU:</strong>
  <?php $hmap=['disetujui'=>'DISETUJUI','perlu_perbaikan'=>'PERLU PERBAIKAN','ditolak'=>'DITOLAK']; ?>
  <strong style="text-transform:uppercase"><?= $hmap[$reviu->hasil_reviu] ?? strtoupper($reviu->hasil_reviu) ?></strong>
  <?php if ($reviu->catatan): ?>
  <br><em>Catatan: <?= htmlspecialchars($reviu->catatan) ?></em>
  <?php endif; ?>
  <?php if ($reviu->no_lhr): ?>
  <br>No. LHR: <strong><?= htmlspecialchars($reviu->no_lhr) ?></strong> / <?= tgl_indo($reviu->tgl_lhr) ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- TTD -->
<div class="ttd">
  <div class="ttd-blok">
    <p>Pemeriksa,</p>
    <div class="nama">.................................................</div>
    <div class="nip">NIP. .......................................</div>
  </div>
  <div class="ttd-blok">
    <p><?= htmlspecialchars($p->nama_kabkota) ?>, <?= $tgl_cetak ?></p>
    <p><?= $inspektur ? htmlspecialchars($inspektur->jabatan ?? 'Inspektur') : 'Inspektur' ?></p>
    <div class="nama"><?= $inspektur ? htmlspecialchars($inspektur->nama) : '.................................................' ?></div>
    <?php if ($inspektur && $inspektur->nip): ?>
    <div class="nip">NIP. <?= htmlspecialchars($inspektur->nip) ?></div>
    <?php else: ?>
    <div class="nip">NIP. .......................................</div>
    <?php endif; ?>
  </div>
</div>

<div class="footer-note">Dicetak melalui SIBERKAH SUMUT · <?= $tgl_cetak ?> · <?= $p->kode_bkp ?> / <?= $tahapan->label_tahap ?></div>
</body>
</html>
