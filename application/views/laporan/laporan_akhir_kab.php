<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-report"></i> Laporan Akhir Kab/Kota</div>
  <?php if ($kabkota_id && $data_laporan !== NULL): ?>
  <a href="<?= site_url('laporan/cetak-laporan-akhir-kab?tahun='.$tahun.'&kabkota_id='.$kabkota_id) ?>"
     target="_blank" class="btn btn-primary btn-sm">
    <i class="ti ti-printer"></i> Cetak Laporan
  </a>
  <?php endif; ?>
</div>

<!-- Filter -->
<div class="card">
  <?= form_open(site_url('laporan/laporan-akhir-kab'), ['method'=>'get','style'=>'display:contents']) ?>
  <div class="filter-row">
    <?php if ($this->rbac->isProvinsi()): ?>
    <div class="filter-group">
      <label class="filter-label">Kab/Kota <span class="text-danger">*</span></label>
      <select name="kabkota_id" class="form-control-sm" required>
        <option value="">— Pilih Kab/Kota —</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($k->id == $kabkota_id)?'selected':'' ?>><?= htmlspecialchars($k->nama) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group">
      <label class="filter-label">Tahun Anggaran</label>
      <select name="tahun" class="form-control-sm">
        <?php foreach ([2024,2025,2026] as $ty): ?>
        <option value="<?= $ty ?>" <?= ($ty==$tahun)?'selected':'' ?>><?= $ty ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group" style="align-self:flex-end">
      <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Tampilkan</button>
    </div>
  </div>
  <?= form_close() ?>
</div>

<?php if ($kabkota_id && $data_laporan !== NULL): ?>

<!-- Summary Cards -->
<div class="g4" style="margin-bottom:0">
  <div class="stat-card" style="background:var(--biru-light)">
    <div class="stat-val" style="color:var(--biru)"><?= $summary->total_bkp ?></div>
    <div class="stat-label">Total BKP</div>
  </div>
  <div class="stat-card" style="background:var(--hijau-light)">
    <div class="stat-val" style="color:var(--hijau-mid);font-size:18px"><?= rupiah_juta($summary->total_nilai_bkp) ?></div>
    <div class="stat-label">Total Nilai BKP</div>
  </div>
  <div class="stat-card" style="background:var(--teal-light)">
    <div class="stat-val" style="color:var(--teal-mid);font-size:18px"><?= rupiah_juta($summary->total_disalurkan) ?></div>
    <div class="stat-label">Total Disalurkan</div>
  </div>
  <div class="stat-card" style="background:var(--kuning-light)">
    <div class="stat-val" style="color:var(--kuning-mid)">
      <?= $summary->total_nilai_bkp > 0
          ? round(($summary->total_disalurkan / $summary->total_nilai_bkp) * 100, 1) . '%'
          : '—' ?>
    </div>
    <div class="stat-label">Realisasi Penyaluran</div>
  </div>
</div>

<!-- Tabel Detail per BKP -->
<div class="card" style="padding:0;overflow:hidden;margin-top:0">
<div style="overflow-x:auto">
<table class="tbl">
  <thead>
    <tr>
      <th style="width:36px">No</th>
      <th>Kode BKP &amp; Uraian</th>
      <th>Bidang</th>
      <th class="text-right">Nilai BKP</th>
      <th>Status</th>
      <th>Penyaluran</th>
      <th class="text-right">Total Disalurkan</th>
      <th style="width:100px">Capaian Fisik</th>
    </tr>
  </thead>
  <tbody>
  <?php $no = 0; foreach ($data_laporan as $bkp): $no++; ?>
  <tr>
    <td class="text-muted"><?= $no ?></td>
    <td>
      <div style="font-family:monospace;font-size:12px;font-weight:600"><?= htmlspecialchars($bkp->kode_bkp) ?></div>
      <div style="font-size:13px"><?= htmlspecialchars($bkp->nama_kegiatan_dok ?: $bkp->uraian_bkp) ?></div>
      <?php if ($bkp->nama_penyedia): ?>
      <div style="font-size:11px;color:var(--abu)"><?= htmlspecialchars($bkp->nama_penyedia) ?></div>
      <?php endif; ?>
    </td>
    <td style="white-space:nowrap"><?= htmlspecialchars($bkp->nama_bidang) ?></td>
    <td class="text-right"><?= rupiah($bkp->nilai_bkp) ?></td>
    <td><?= $bkp->status ? badge_status($bkp->status) : '<span class="badge badge-abu">Belum Input</span>' ?></td>
    <td style="font-size:12px">
      <?php if (!empty($bkp->tahapan)): foreach ($bkp->tahapan as $t): if (!$t->no_sp2d) continue; ?>
        <div style="margin-bottom:4px">
          <strong><?= strtoupper(str_replace('_',' ',$t->kode_tahap)) ?></strong>:
          <?= htmlspecialchars($t->no_sp2d) ?><br>
          <span class="text-muted"><?= tgl_short($t->tgl_sp2d) ?> &bull; <?= rupiah($t->nilai_transfer) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </td>
    <td class="text-right">
      <?= $bkp->total_disalurkan ? rupiah($bkp->total_disalurkan) : '—' ?>
    </td>
    <td>
      <?php
        $all_capaian = array_filter((array)$bkp->tahapan, fn($t) => $t->persen_fisik !== NULL);
        $avg_capaian = count($all_capaian)
            ? round(array_sum(array_column($all_capaian, 'persen_fisik')) / count($all_capaian), 1)
            : NULL;
      ?>
      <?php if ($avg_capaian !== NULL): ?>
        <div style="display:flex;align-items:center;gap:4px">
          <div style="flex:1;background:#e9ecef;border-radius:3px;height:5px">
            <div style="width:<?= $avg_capaian ?>%;background:var(--hijau-mid);height:100%"></div>
          </div>
          <span style="font-size:11px;font-weight:600;color:var(--hijau-mid)"><?= $avg_capaian ?>%</span>
        </div>
      <?php else: ?>
        <span class="text-muted" style="font-size:11px">—</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>

  <!-- Footer total -->
  <tr style="background:var(--biru-light);font-weight:600">
    <td colspan="3" class="text-right">TOTAL</td>
    <td class="text-right"><?= rupiah($summary->total_nilai_bkp) ?></td>
    <td></td>
    <td></td>
    <td class="text-right"><?= rupiah($summary->total_disalurkan) ?></td>
    <td></td>
  </tr>
  </tbody>
</table>
</div>
</div><!-- .card -->

<?php elseif ($this->rbac->isProvinsi()): ?>
<div class="alert alert-info" style="margin-top:0">
  <i class="ti ti-info-circle"></i> Pilih Kabupaten/Kota untuk menampilkan laporan.
</div>
<?php endif; ?>
