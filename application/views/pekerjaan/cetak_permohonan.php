<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Permohonan Reviu — <?= htmlspecialchars($pekerjaan->kode_bkp) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;color:#000;line-height:1.6;padding:2cm}
.kop{border-bottom:3px double #000;padding-bottom:8px;margin-bottom:16px;display:flex;align-items:center;gap:14px}.kop-logo{flex-shrink:0}.kop-text{flex:1;text-align:center}
.kop h1{font-size:14pt;text-transform:uppercase}
.kop p{font-size:11pt}
h2{font-size:12pt;text-align:center;text-transform:uppercase;margin:20px 0 8px}
.surat-head{margin-bottom:20px}
.surat-head table{width:auto}
.surat-head td{padding:2px 4px;vertical-align:top}
.surat-head td:first-child{width:140px}
p{margin-bottom:10px;text-align:justify}
.tbl-data{width:100%;border-collapse:collapse;margin:12px 0;font-size:11pt}
.tbl-data th,.tbl-data td{border:1px solid #000;padding:5px 8px;text-align:left}
.tbl-data th{background:#f5f5f5;font-weight:bold;text-align:center}
.ttd{display:flex;justify-content:space-between;margin-top:40px}
.ttd-blok{width:45%;text-align:center}
.ttd-blok .nama{margin-top:70px;font-weight:bold;text-decoration:underline}
.ttd-blok .nip{font-size:10pt}
.lampiran{margin-top:30px;border-top:1px solid #000;padding-top:16px}
@media print{
  body{padding:1.5cm}
  .no-print{display:none}
}
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:10px;right:10px;z-index:99">
  <button onclick="window.print()" style="padding:8px 16px;background:#1A5EA8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Cetak / Simpan PDF</button>
</div>

<?php $p = $pekerjaan; $kdh = $pejabat['kepala_daerah'] ?? NULL; $inspektur = $pejabat['inspektur'] ?? NULL; ?>

<!-- KOP SURAT -->
<div class="kop">
  <div class="kop-logo"><img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH" style="height:64px;width:64px;object-fit:contain"></div>
  <div class="kop-text"><h1>PEMERINTAH <?= strtoupper(htmlspecialchars($p->nama_kabkota)) ?></h1>
  <p><?= htmlspecialchars($p->opd_nama ?? 'DINAS TEKNIS') ?></p>
  <p>Alamat: ................................................................</p></div>
</div>

<!-- NOMOR SURAT -->
<div class="surat-head">
  <table>
    <tr><td>Nomor</td><td>:</td><td>............/............/<?= $p->tahun ?></td></tr>
    <tr><td>Sifat</td><td>:</td><td>Segera</td></tr>
    <tr><td>Lampiran</td><td>:</td><td>1 (satu) berkas</td></tr>
    <tr><td>Perihal</td><td>:</td><td><strong>Permohonan Reviu Bantuan Keuangan Provinsi</strong></td></tr>
  </table>
</div>

<p>Kepada Yth.</p>
<p><strong>Bapak/Ibu Inspektur <?= htmlspecialchars($p->nama_kabkota) ?></strong><br>di—Tempat</p>

<p>Dengan hormat,</p>
<p>
Dalam rangka memenuhi ketentuan SE Gubernur Sumatera Utara Nomor 900.1.1.3689 tentang Pedoman Bantuan Keuangan Provinsi kepada Kabupaten/Kota, bersama ini kami mengajukan permohonan reviu terhadap kegiatan yang dibiayai dari Bantuan Keuangan Provinsi (BKP) Tahun Anggaran <?= $p->tahun ?>, sebagai berikut:
</p>

<table class="tbl-data">
  <tr><th colspan="2">DATA KEGIATAN BKP</th></tr>
  <tr><td style="width:40%">Kode BKP</td><td class="mono"><?= htmlspecialchars($p->kode_bkp) ?></td></tr>
  <tr><td>Uraian BKP</td><td><?= htmlspecialchars($p->uraian_bkp) ?></td></tr>
  <tr><td>Nama Kegiatan</td><td><?= htmlspecialchars($p->nama_kegiatan_dok) ?></td></tr>
  <tr><td>Jenis Penyaluran</td><td><?= ucfirst(str_replace('_',' ',$p->jenis_penyaluran)) ?></td></tr>
  <tr><td>Bidang</td><td><?= htmlspecialchars($p->nama_bidang) ?></td></tr>
  <tr><td>Nilai BKP</td><td><?= rupiah($p->nilai_bkp) ?></td></tr>
  <tr><td>Nilai Kontrak</td><td><strong><?= rupiah($p->nilai_kontrak) ?></strong></td></tr>
  <tr><td>Nama Penyedia</td><td><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></td></tr>
  <tr><td>No. Kontrak / Dok. Pekerjaan</td><td><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?> <?= $p->tgl_dok_pekerjaan ? '/ '.tgl_indo($p->tgl_dok_pekerjaan) : '' ?></td></tr>
  <tr><td>No. SPMK</td><td><?= htmlspecialchars($p->no_spmk ?: '—') ?> <?= $p->tgl_spmk ? '/ '.tgl_indo($p->tgl_spmk) : '' ?></td></tr>
  <tr><td>Jangka Waktu</td><td><?= $p->jangka_waktu_hari ? $p->jangka_waktu_hari.' hari' : '—' ?></td></tr>
  <tr><td>Lokasi</td><td><?= htmlspecialchars($p->lokasi_deskripsi ?: '—') ?></td></tr>
  <?php if ($perda): ?><tr><td>Perda APBD</td><td>No. <?= htmlspecialchars($perda->nomor) ?> / <?= tgl_indo($perda->tanggal) ?></td></tr><?php endif; ?>
  <?php if ($perkada): ?><tr><td>Perkada/Pergub</td><td>No. <?= htmlspecialchars($perkada->nomor) ?> / <?= tgl_indo($perkada->tanggal) ?></td></tr><?php endif; ?>
</table>

<?php if (!empty($tahapan)): ?>
<table class="tbl-data">
  <tr><th colspan="4">TAHAPAN PENYALURAN</th></tr>
  <tr><th>Tahapan</th><th>Persentase</th><th>Nilai Diajukan</th><th>Batas Pengajuan</th></tr>
  <?php foreach ($tahapan as $t): ?>
  <tr>
    <td><?= htmlspecialchars($t->label_tahap) ?></td>
    <td style="text-align:center"><?= $t->persen_nilai ?>%</td>
    <td><?= rupiah($t->nilai_diajukan) ?></td>
    <td><?= tgl_indo($t->batas_tgl_pengajuan) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<p>Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami mengucapkan terima kasih.</p>

<p style="text-align:right"><?= htmlspecialchars($p->nama_kabkota) ?>, <?= $tgl_cetak ?></p>

<div class="ttd">
  <div class="ttd-blok">
    <p>Mengetahui,</p>
    <p><?= $kdh ? 'Bupati/Wali Kota '.htmlspecialchars($p->nama_kabkota) : '........................' ?></p>
    <div class="nama"><?= $kdh ? htmlspecialchars($kdh->nama) : '............................' ?></div>
    <?php if ($kdh && $kdh->nip): ?><div class="nip">NIP. <?= htmlspecialchars($kdh->nip) ?></div><?php endif; ?>
  </div>
  <div class="ttd-blok">
    <p><?= htmlspecialchars($p->opd_nama ?? 'Kepala Dinas Teknis') ?></p>
    <p>..........................................</p>
    <div class="nama">......................................</div>
    <div class="nip">NIP. ..............................</div>
  </div>
</div>

<!-- Lampiran -->
<div class="lampiran">
  <p><strong>Lampiran:</strong> Dokumen Persyaratan</p>
  <ol style="padding-left:20px;font-size:11pt">
    <li>Dokumen pekerjaan / kontrak</li>
    <li>SPMK</li>
    <li>Daftar pekerjaan</li>
    <li>Surat pernyataan Bupati/Wali Kota</li>
    <li>Perda APBD / APBD-P</li>
    <li>Perkada / Pergub BKP</li>
    <?php if ($p->jenis_penyaluran === 'bertahap'): ?><li>Berita acara kemajuan pekerjaan (Tahap II)</li><?php endif; ?>
  </ol>
  <br>
  <p style="font-size:10pt;color:#666">Dicetak melalui SIBERKAH SUMUT — <?= $tgl_cetak ?></p>
</div>
</body>
</html>
