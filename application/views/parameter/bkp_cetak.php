<?php
// Cek apakah user adalah admin (superadmin atau admin_provinsi) — akses ke $current_user dari $this->data
$role_kode  = $current_user->role_kode ?? '';
$is_admin   = in_array($role_kode, ['superadmin', 'admin_provinsi']);
$tgl_print  = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap BKP TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;color:#000;padding:20px}
/* Header resmi (hanya admin) */
.kop{display:flex;align-items:center;gap:16px;border-bottom:3px solid #000;padding-bottom:10px;margin-bottom:14px}
.kop img{width:64px;height:64px;object-fit:contain}
.kop-text h1{font-size:14px;font-weight:700;text-transform:uppercase}
.kop-text p{font-size:11px;color:#333}
h2{font-size:14px;text-align:center;text-transform:uppercase;margin-bottom:4px}
.subtitle{text-align:center;margin-bottom:16px;font-size:11px;color:#444}
/* Tabel */
table{width:100%;border-collapse:collapse;font-size:11px}
th,td{border:1px solid #333;padding:5px 7px;vertical-align:top}
th{background:#e8e8e8;font-weight:600;text-align:left}
.right{text-align:right}
.total{font-weight:600;background:#f0f0f0}
.mono{font-family:monospace}
/* Penandatangan (hanya admin) */
.ttd-section{margin-top:32px;display:flex;justify-content:flex-end}
.ttd-blok{text-align:center;width:260px}
.ttd-blok .ttd-space{height:56px}
.ttd-blok .ttd-nama{font-weight:700;border-top:1px solid #000;padding-top:4px}
.ttd-blok .ttd-nip{font-size:10px}
/* Footer sederhana */
.footer{margin-top:20px;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:6px;text-align:center}
/* Print */
@media print{
  .no-print{display:none!important}
  body{padding:10px}
}
</style>
</head>
<body>

<?php if ($is_admin): ?>
<!-- ══ HEADER RESMI (hanya superadmin & admin provinsi) ══ -->
<div class="kop">
  <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo">
  <div class="kop-text">
    <h1>SIBERKAH SUMUT</h1>
    <p>Sistem Informasi Bantuan Keuangan Daerah Provinsi Sumatera Utara</p>
    <p>BKAD Provinsi Sumatera Utara</p>
  </div>
</div>
<h2>Rekapitulasi Data BKP TA <?= $tahun ?></h2>
<div class="subtitle">
  <?= $kab ? htmlspecialchars($kab->nama) : 'Seluruh Kabupaten/Kota' ?>
  &nbsp;|&nbsp; Dicetak: <?= $tgl_print ?>
  &nbsp;|&nbsp; Oleh: <?= htmlspecialchars($user_nama) ?>
</div>
<?php endif; ?>

<!-- Tombol cetak (hilang saat print) -->
<div class="no-print" style="margin-bottom:12px">
  <button onclick="window.print()" style="padding:6px 14px;cursor:pointer;margin-right:8px">
    🖨 Cetak / Simpan PDF
  </button>
  <button onclick="window.close()" style="padding:6px 14px;cursor:pointer">Tutup</button>
</div>

<!-- ══ TABEL DATA ══ -->
<table>
  <thead>
    <tr>
      <th style="width:30px">#</th>
      <th style="width:130px">Kode BKP</th>
      <?php if ($is_admin || !$kab): ?>
      <th>Kab/Kota</th>
      <?php endif; ?>
      <th>Uraian BKP</th>
      <th style="width:110px">Bidang</th>
      <th class="right" style="width:130px">Nilai (Rp)</th>
    </tr>
  </thead>
  <tbody>
  <?php $no = 1; foreach ($list as $b): ?>
  <tr>
    <td style="text-align:center"><?= $no++ ?></td>
    <td class="mono" style="font-size:10px"><?= htmlspecialchars($b->kode_bkp) ?></td>
    <?php if ($is_admin || !$kab): ?>
    <td><?= htmlspecialchars($b->nama_kabkota) ?></td>
    <?php endif; ?>
    <td><?= htmlspecialchars($b->uraian_bkp) ?></td>
    <td><?= htmlspecialchars($b->nama_bidang) ?></td>
    <td class="right"><?= rupiah($b->nilai) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="<?= ($is_admin || !$kab) ? 5 : 4 ?>" style="text-align:right">
      Total (<?= $rekap->total ?> BKP)
    </td>
    <td class="right"><?= rupiah($rekap->total_nilai ?? 0) ?></td>
  </tr>
  </tbody>
</table>

<?php if ($is_admin): ?>
<!-- ══ PENANDATANGAN (hanya admin) ══ -->
<div class="ttd-section">
  <div class="ttd-blok">
    <p>Dicetak oleh,</p>
    <div class="ttd-space"></div>
    <div class="ttd-nama"><?= htmlspecialchars($user_nama) ?></div>
    <div class="ttd-nip"><?= htmlspecialchars($role_kode) ?></div>
  </div>
</div>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<div class="footer">
  <?php if ($is_admin): ?>
  SIBERKAH SUMUT — BKAD Provinsi Sumatera Utara &nbsp;|&nbsp;
  Dicetak: <?= $tgl_print ?> &nbsp;|&nbsp; Dokumen ini sah tanpa tanda tangan basah
  <?php else: ?>
  Sumber: SIBERKAH SUMUT &nbsp;|&nbsp; Dicetak: <?= $tgl_print ?>
  <?php endif; ?>
</div>

</body>
</html>
