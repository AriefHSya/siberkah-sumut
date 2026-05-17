<div class="page-header">
  <div class="page-title"><i class="ti ti-cash"></i> Rekap Penyaluran Dana TA <?= $tahun ?></div>
  <div class="aksi-row">
    <?php $q = http_build_query(['tahun'=>$tahun,'kabkota_id'=>$kabkota_id]); ?>
    <a href="<?= site_url('verifikasi/prov/cetak-rekap?'.$q) ?>"
       target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Resmi
    </a>
    <?php if ($this->rbac->can('laporan.export')): ?>
    <a href="<?= site_url('laporan/export-penyaluran?'.$q) ?>"
       class="btn btn-outline btn-sm">
      <i class="ti ti-table-export"></i> Export CSV
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-val" style="color:var(--kuning-mid)"><?= $rekap->total_sp2d ?? 0 ?></div>
    <div class="stat-label">SP2D Diterbitkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="font-size:15px;color:var(--teal-mid)"><?= rupiah_juta($rekap->total_disalurkan ?? 0) ?></div>
    <div class="stat-label">Total Disalurkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:var(--hijau-mid)"><?= $rekap->total_dikonfirmasi ?? 0 ?></div>
    <div class="stat-label">Dikonfirmasi Kab</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:var(--biru)"><?= $rekap->total_tahapan ?? 0 ?></div>
    <div class="stat-label">Total Tahapan</div>
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
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Tampilkan</button>
    <a href="<?= site_url('laporan/rekap-penyaluran') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No. SP2D</th>
          <th>Tgl SP2D</th>
          <th>Kab/Kota</th>
          <th>Kode BKP</th>
          <th>Nama Kegiatan</th>
          <th>Tahapan</th>
          <th>Jenis</th>
          <th style="text-align:right">Nilai Transfer</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $grand = 0;
      if (!empty($list)): foreach ($list as $row): $grand += $row->nilai_transfer; ?>
      <tr>
        <td class="mono fw-500"><?= htmlspecialchars($row->no_sp2d) ?></td>
        <td class="text-sm"><?= tgl_short($row->tgl_sp2d) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <td class="mono text-sm"><?= htmlspecialchars($row->kode_bkp) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kegiatan_dok ?: $row->uraian_bkp) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->label_tahap) ?></td>
        <td><?= badge_jenis($row->jenis_penyaluran) ?></td>
        <td class="fw-500" style="text-align:right;color:var(--teal-mid)"><?= rupiah($row->nilai_transfer) ?></td>
        <td>
          <?php $stm=['proses'=>['biru','Proses'],'selesai'=>['hijau','Selesai'],'gagal'=>['merah','Gagal']];
                $s=$stm[$row->status_transfer]??['abu','—'];
                echo '<span class="badge badge-'.$s[0].'">'.$s[1].'</span>'; ?>
        </td>
      </tr>
      <?php endforeach;
      else: ?>
      <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">Belum ada data penyaluran.</td></tr>
      <?php endif; ?>
      </tbody>
      <?php if (!empty($list)): ?>
      <tfoot>
        <tr style="background:var(--bg);font-weight:600">
          <td colspan="7">Total (<?= count($list) ?> SP2D)</td>
          <td style="text-align:right;color:var(--teal-mid)"><?= rupiah($grand) ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
