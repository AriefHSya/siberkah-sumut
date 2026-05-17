<div class="page-header">
  <div class="page-title"><i class="ti ti-report"></i> Rekap Data BKP TA <?= $tahun ?></div>
  <div class="aksi-row">
    <?php
    $q = http_build_query(['tahun'=>$tahun,'kabkota_id'=>$kabkota_id,'bidang_id'=>$bidang_id]);
    if ($this->rbac->can('laporan.cetak_rekap_bkp')): ?>
    <a href="<?= site_url('laporan/cetak-rekap-bkp?'.$q) ?>" target="_blank"
       class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak PDF
    </a>
    <?php endif; ?>
    <?php if ($this->rbac->can('laporan.export')): ?>
    <a href="<?= site_url('laporan/export-bkp?'.$q) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-table-export"></i> Export CSV
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Summary cards -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-val" style="color:var(--biru)"><?= number_format($summary->total_bkp ?? 0) ?></div>
    <div class="stat-label">Total BKP</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:16px;color:var(--biru)"><?= rupiah_juta($summary->total_nilai_bkp ?? 0) ?></div>
    <div class="stat-label">Total Nilai BKP</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:var(--hijau-mid)"><?= number_format($summary->total_pekerjaan ?? 0) ?></div>
    <div class="stat-label">Ada Pekerjaan</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:16px;color:var(--teal-mid)"><?= rupiah_juta($summary->total_disalurkan ?? 0) ?></div>
    <div class="stat-label">Total Disalurkan</div>
  </div>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Tahun</label>
      <select name="tahun">
        <?php foreach ($tahun_list as $t): ?>
        <option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun)?'selected':'' ?>><?= $t->tahun ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!empty($kabkota_list)): ?>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($k->id==$kabkota_id)?'selected':'' ?>><?= $k->nama ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group"><label>Bidang</label>
      <select name="bidang_id">
        <option value="">Semua Bidang</option>
        <?php foreach ($bidang_list as $b): ?>
        <option value="<?= $b->id ?>" <?= ($b->id==$bidang_id)?'selected':'' ?>><?= $b->nama ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Tampilkan</button>
    <a href="<?= site_url('laporan/rekap-bkp') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Kode BKP</th>
          <th>Kab/Kota</th>
          <th>Bidang</th>
          <th>Uraian BKP</th>
          <th style="text-align:right">Nilai BKP</th>
          <th>Jenis</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <th>Status</th>
          <th style="text-align:right">Disalurkan</th>
          <th style="text-align:center">SP2D</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $row): ?>
      <tr>
        <td class="mono text-sm"><?= htmlspecialchars($row->kode_bkp) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <td><span class="badge badge-abu"><?= htmlspecialchars($row->nama_bidang) ?></span></td>
        <td class="text-sm"><?= htmlspecialchars($row->uraian_bkp) ?>
          <?php if ($row->nama_kegiatan_dok && $row->nama_kegiatan_dok !== $row->uraian_bkp): ?>
          <div class="text-xs text-muted"><?= htmlspecialchars($row->nama_kegiatan_dok) ?></div>
          <?php endif; ?>
        </td>
        <td class="text-sm" style="text-align:right"><?= rupiah($row->nilai_bkp) ?></td>
        <td><?= $row->jenis_penyaluran ? badge_jenis($row->jenis_penyaluran) : '<span class="text-muted text-xs">—</span>' ?></td>
        <td class="text-sm" style="text-align:right">
          <?= $row->nilai_kontrak ? rupiah($row->nilai_kontrak) : '<span class="text-muted">—</span>' ?>
        </td>
        <td><?= $row->status ? badge_status($row->status) : '<span class="badge badge-abu">Belum Input</span>' ?></td>
        <td class="text-sm" style="text-align:right;color:var(--teal-mid);font-weight:500">
          <?= $row->total_disalurkan ? rupiah($row->total_disalurkan) : '—' ?>
        </td>
        <td class="center text-sm"><?= $row->total_sp2d ?: '—' ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted)">
        Tidak ada data BKP untuk filter ini.
      </td></tr>
      <?php endif; ?>
      </tbody>
      <?php if (!empty($list)): ?>
      <tfoot>
        <tr style="background:var(--bg);font-weight:600">
          <td colspan="4">Total (<?= count($list) ?> BKP)</td>
          <td style="text-align:right"><?= rupiah($summary->total_nilai_bkp ?? 0) ?></td>
          <td></td>
          <td style="text-align:right"><?= rupiah($summary->total_kontrak ?? 0) ?></td>
          <td></td>
          <td style="text-align:right;color:var(--teal-mid)"><?= rupiah($summary->total_disalurkan ?? 0) ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
