<?php
$q_params = ['tahun' => $tahun, 'kabkota_id' => $kabkota_id];
$q_str    = http_build_query($q_params);
?>
<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-cash-banknote"></i> Rekap Pengajuan Tahap II TA <?= $tahun ?></div>
    <div class="text-sm text-muted" style="margin-top:4px">
      Kegiatan penyaluran bertahap yang sudah memasuki proses Tahap II
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('laporan.cetak_rekap_penyaluran')): ?>
    <a href="<?= site_url('laporan/cetak-rekap-tahap2?'.$q_str) ?>" target="_blank"
       class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group">
      <label>Tahun</label>
      <select name="tahun">
        <?php foreach ($tahun_list as $t): ?>
        <option value="<?= $t->tahun ?>" <?= ($t->tahun == $tahun) ? 'selected' : '' ?>><?= $t->tahun ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($this->rbac->isProvinsi() && !empty($kabkota_list)): ?>
    <div class="filter-group">
      <label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">— Semua —</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($k->id == $kabkota_id) ? 'selected' : '' ?>><?= htmlspecialchars($k->nama) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Tampilkan</button>
  </form>
</div>

<!-- Summary stats -->
<?php
$g_kontrak = 0; $g_tahap1 = 0; $g_tahap2 = 0;
foreach ($list as $r) {
    $v50   = (float)($r->persen_tahap1 / 100) * $r->nilai_kontrak;
    $vpend = (float)$r->nilai_belanja_pendukung;
    $g_kontrak += (float)$r->nilai_kontrak;
    $g_tahap1  += $v50;
    $g_tahap2  += $r->nilai_kontrak - $v50;
}
?>
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-val" style="color:var(--teal-mid)"><?= count($list) ?></div>
    <div class="stat-label">Kegiatan Tahap II</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:15px;color:var(--biru)"><?= rupiah_juta($g_kontrak) ?></div>
    <div class="stat-label">Total Nilai Kontrak</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:15px;color:var(--kuning-mid)"><?= rupiah_juta($g_tahap1) ?></div>
    <div class="stat-label">Nilai Salur Tahap I</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:15px;color:var(--hijau-mid)"><?= rupiah_juta($g_tahap2) ?></div>
    <div class="stat-label">Nilai Pengajuan Tahap II</div>
  </div>
</div>

<!-- Tabel -->
<div class="card">
  <?php if (empty($list)): ?>
  <p class="text-muted text-sm" style="padding:16px">Belum ada kegiatan yang memasuki proses Tahap II pada TA <?= $tahun ?>.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table class="tbl" style="font-size:12px;min-width:900px">
    <thead>
      <tr>
        <th style="width:30px">No.</th>
        <th style="width:90px">Kode BKP</th>
        <?php if ($this->rbac->isProvinsi()): ?><th>Kab/Kota</th><?php endif; ?>
        <th>Nama Kegiatan</th>
        <th style="width:100px">Lokasi</th>
        <th class="right" style="width:110px">Nilai Kontrak</th>
        <th class="right" style="width:110px">Salur Tahap I<br><span style="font-weight:normal;font-size:10px">(50% Nilai Kontrak)</span></th>
        <th class="right" style="width:110px">Pengajuan Tahap II<br><span style="font-weight:normal;font-size:10px">(50% Nilai Kontrak)</span></th>
        <th style="width:80px;text-align:center">Status</th>
      </tr>
    </thead>
    <tbody>
    <?php $no = 1; foreach ($list as $r):
      $val_50pct  = (float)($r->persen_tahap1 / 100) * $r->nilai_kontrak;
      $val_tahap1 = $val_50pct;  // Nilai Salur Tahap I = 50% × Nilai Kontrak
      $val_tahap2 = $r->nilai_kontrak - $val_50pct;  // Nilai Pengajuan Tahap II = 50% × NK
      $st_badge = [
          'opd_input'            => ['biru',   'Diajukan'],
          'inspektorat_reviu'    => ['ungu',   'Reviu'],
          'inspektorat_revisi'   => ['kuning', 'Revisi'],
          'inspektorat_approved' => ['hijau',  'Reviu OK'],
          'skpkd_kab_verif'      => ['biru',   'Verif. Kab'],
          'skpkd_kab_revisi'     => ['kuning', 'Revisi Kab'],
          'skpkd_kab_approved'   => ['hijau',  'Verif. Kab OK'],
          'skpkd_prov_verif'     => ['teal',   'Verif. Prov'],
          'skpkd_prov_revisi'    => ['kuning', 'Revisi Prov'],
          'disalurkan'           => ['teal',   'Disalurkan'],
          'dikonfirmasi'         => ['hijau',  'Dikonfirmasi'],
          'ditolak'              => ['merah',  'Ditolak'],
      ];
      $bs = $st_badge[$r->status_tahap2] ?? ['abu', htmlspecialchars($r->status_tahap2)];
    ?>
    <tr>
      <td class="center"><?= $no++ ?></td>
      <td class="mono text-xs"><?= htmlspecialchars($r->kode_bkp) ?></td>
      <?php if ($this->rbac->isProvinsi()): ?>
      <td class="text-xs"><?= htmlspecialchars($r->nama_kabkota) ?></td>
      <?php endif; ?>
      <td style="font-size:11px"><?= htmlspecialchars($r->nama_kegiatan_dok ?: $r->uraian_bkp) ?></td>
      <td style="font-size:11px"><?= htmlspecialchars($r->lokasi_deskripsi ?: '—') ?></td>
      <td class="right"><?= rupiah($r->nilai_kontrak) ?></td>
      <td class="right"><?= rupiah($val_tahap1) ?></td>
      <td class="right fw-500" style="color:var(--teal-mid)"><?= rupiah($val_tahap2) ?></td>
      <td class="center"><span class="badge badge-<?= $bs[0] ?>"><?= $bs[1] ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:var(--bg);font-weight:600">
        <td colspan="<?= $this->rbac->isProvinsi() ? 5 : 4 ?>" class="right text-sm">TOTAL</td>
        <td class="right"><?= rupiah($g_kontrak) ?></td>
        <td class="right"><?= rupiah($g_tahap1) ?></td>
        <td class="right" style="color:var(--teal-mid)"><?= rupiah($g_tahap2) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  </div>
  <?php endif; ?>
</div>
