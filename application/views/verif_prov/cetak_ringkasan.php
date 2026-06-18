<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Kab / Kota
$kota_sumut = ['Medan','Binjai','Tebing Tinggi','Pematangsiantar',
               'Padangsidimpuan','Gunungsitoli','Sibolga','Tanjungbalai'];
$nama_raw = trim(preg_replace('/^(Kab\.|Kab |Kota )/i', '', $pm->nama_kabkota));
$is_kota  = in_array($nama_raw, $kota_sumut) || (bool)preg_match('/^Kota /i', $pm->nama_kabkota);
$prefix   = $is_kota ? 'Kota' : 'Kabupaten';
$nama_dp  = $prefix . ' ' . $nama_raw;

// Label jenis tahapan
function _label_tahapan($jenis, $kode) {
    if ($jenis === 'bertahap') return ($kode === 'tahap_1') ? 'Tahap I' : 'Tahap II';
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return isset($m[$jenis]) ? $m[$jenis] : ucfirst($jenis);
}

// Pejabat
$kabid = isset($pejabat['kabid_anggaran']) ? $pejabat['kabid_anggaran'] : null;

// Totals
// Kolom 3 berbeda per jenis:
//   Tahap II → Nilai Salur Tahap I = nilai_diajukan + pendukung (50%NK + pendukung)
//   Lainnya  → Nilai Pendukung = nilai_belanja_pendukung
$_is_tahap2_ring = ($pm->jenis_penyaluran === 'bertahap' && $pm->kode_tahap === 'tahap_2');
$total_pagu = $total_kontrak = $total_col3 = $total_salur = 0;
foreach ((array)$items as $it) {
    $total_pagu    += ($it->pagu_bkp    ?? 0);
    $total_kontrak += ($it->nilai_kontrak ?? 0);
    $total_col3    += $_is_tahap2_ring
        ? ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0)
        : ($it->nilai_belanja_pendukung ?? 0);
    $total_salur += ($it->nilai_diajukan ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ringkasan Penyaluran — <?= htmlspecialchars($pm->no_permohonan) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Times New Roman',Times,serif;font-size:10.5pt;color:#000;background:#fff}
.page{padding:1.2cm 1.5cm;max-width:29.7cm;margin:0 auto}
@media print{
  .no-print{display:none!important}
  @page{size:A4 landscape;margin:1.2cm 1.5cm}
}
.no-print{position:fixed;top:12px;right:16px;z-index:999}

/* ── KOP ── */
.kop{display:flex;align-items:center;gap:12px;padding-bottom:8px;
     border-bottom:4px double #000;margin-bottom:16px}
.kop-logo{flex-shrink:0;width:72px;height:72px;object-fit:contain}
.kop-logo-empty{flex-shrink:0;width:72px;height:72px}
.kop-txt{flex:1;text-align:center;line-height:1.35}
.kop-nm1{font-size:13pt;font-weight:bold}
.kop-nm2{font-size:12pt;font-weight:bold}
.kop-alamat{font-size:8.5pt;margin-top:3px;color:#333}
.kop-logo-siberkah{flex-shrink:0;width:72px;height:72px;object-fit:contain}

/* ── JUDUL ── */
.judul{text-align:center;margin-bottom:16px;line-height:1.5}
.judul-utama{font-size:12pt;font-weight:bold;text-transform:uppercase;letter-spacing:.5px}
.judul-sub{font-size:11.5pt;font-weight:bold}


/* ── TABEL ── */
.tbl{width:100%;border-collapse:collapse;font-size:9.5pt;margin-bottom:20px}
.tbl th{background:#1A5EA8;color:#fff;text-align:center;font-weight:bold;
        padding:6px 5px;border:1px solid #0d3f78;line-height:1.3}
.tbl td{border:1px solid #bbb;padding:4px 6px;vertical-align:top;line-height:1.4}
.tbl tbody tr:nth-child(even) td{background:#f7f9fc}
.tbl tbody tr:hover td{background:#eef3fb}
.td-no{text-align:center;width:28px;vertical-align:middle!important}
.td-nama{text-align:left}
.td-rp{text-align:right;white-space:nowrap;vertical-align:middle!important;font-variant-numeric:tabular-nums}
.td-ctr{text-align:center;vertical-align:middle!important}
.tbl tfoot td{border:1px solid #888;font-weight:bold;padding:5px 6px;background:#dde6f5}
.tbl tfoot .td-rp{background:#c8d8f0}
.tbl tfoot .td-label{text-align:right;background:#dde6f5}

/* ── TTD ── */
.ttd-area{display:flex;justify-content:flex-end;margin-top:24px}
.ttd-block{min-width:270px}
.ttd-kota{font-size:10pt;margin-bottom:4px}
.ttd-jabatan{font-weight:bold;text-transform:uppercase;line-height:1.45;font-size:10pt}
.ttd-gap{height:72px}
.ttd-nama{font-weight:bold;font-size:10pt}
.ttd-pangkat,.ttd-nip{font-size:10pt;line-height:1.5}
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()"
    style="padding:7px 18px;background:#1A5EA8;color:#fff;border:none;
           border-radius:6px;cursor:pointer;font-size:12px;font-family:sans-serif">
    &#128424; Cetak
  </button>
</div>

<div class="page">

  <!-- KOP SURAT -->
  <div class="kop">
    <?php if (!empty($logo_prov)): ?>
      <img class="kop-logo" src="<?= $logo_prov ?>" alt="Logo Pemprov">
    <?php else: ?>
      <div class="kop-logo-empty"></div>
    <?php endif; ?>

    <div class="kop-txt">
      <div class="kop-nm1">Pemerintah Provinsi Sumatera Utara</div>
      <div class="kop-nm2">Badan Keuangan dan Aset Daerah</div>
      <div class="kop-alamat">Jalan Imam Bonjol Nomor 61, Medan 20157 &nbsp;|&nbsp; bpkad.sumutprov.go.id</div>
    </div>

    <img class="kop-logo-siberkah" src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH">
  </div>

  <!-- JUDUL -->
  <div class="judul">
    <div class="judul-utama">Rekapitulasi Kegiatan Bantuan Keuangan Provinsi TA <?= $pm->tahun ?></div>
    <div class="judul-sub"><?= htmlspecialchars($nama_dp) ?></div>

  </div>

  <!-- TABEL -->
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:28px">No</th>
        <th>Nama Kegiatan</th>
        <th style="width:125px">Pagu BKP (Rp.)</th>
        <th style="width:125px">Nilai Kontrak (Rp.)</th>
        <?php if ($_is_tahap2_ring): ?>
        <th style="width:135px">Nilai Salur Tahap I (Rp.)<br><span style="font-weight:normal;font-size:8pt">(50% Kontrak + Pendukung)</span></th>
        <?php else: ?>
        <th style="width:120px">Nilai Pendukung (Rp.)</th>
        <?php endif; ?>
        <th style="width:85px">Jenis Tahapan</th>
        <th style="width:125px">Nilai Salur (Rp.)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ((array)$items as $i => $it): ?>
      <tr>
        <td class="td-no"><?= $i + 1 ?></td>
        <td class="td-nama"><?= htmlspecialchars($it->nama_kegiatan_dok) ?></td>
        <td class="td-rp"><?= number_format($it->pagu_bkp ?? 0, 2, ',', '.') ?></td>
        <td class="td-rp"><?= number_format($it->nilai_kontrak ?? 0, 2, ',', '.') ?></td>
        <td class="td-rp"><?= number_format(
            $_is_tahap2_ring
                ? ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0)
                : ($it->nilai_belanja_pendukung ?? 0),
            2, ',', '.') ?></td>
        <td class="td-ctr"><?= _label_tahapan($it->jenis_penyaluran, $it->kode_tahap) ?></td>
        <td class="td-rp"><strong><?= number_format($it->nilai_diajukan ?? 0, 2, ',', '.') ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="td-label">Jumlah</td>
        <td class="td-rp"><?= number_format($total_pagu,      2, ',', '.') ?></td>
        <td class="td-rp"><?= number_format($total_kontrak, 2, ',', '.') ?></td>
        <td class="td-rp"><?= number_format($total_col3,    2, ',', '.') ?></td>
        <td></td>
        <td class="td-rp"><?= number_format($total_salur,     2, ',', '.') ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- TANDA TANGAN -->
  <div class="ttd-area">
    <div class="ttd-block">
      <div class="ttd-kota">Medan, <?= $tgl_cetak ?></div>
      <div class="ttd-jabatan">Kepala Bidang Perencanaan<br>Anggaran Daerah,</div>
      <div class="ttd-gap"></div>
      <div class="ttd-nama"><?= htmlspecialchars($kabid->nama ?? '............................') ?></div>
      <?php if (!empty($kabid->jabatan)): ?>
        <div class="ttd-pangkat"><?= htmlspecialchars($kabid->jabatan) ?></div>
      <?php endif; ?>
      <?php if (!empty($kabid->nip)): ?>
        <div class="ttd-nip">NIP. <?= htmlspecialchars($kabid->nip) ?></div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- .page -->
</body>
</html>
