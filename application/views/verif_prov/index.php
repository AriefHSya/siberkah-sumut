<div class="page-header">
  <div class="page-title"><i class="ti ti-cash"></i> Penyaluran Dana BKP TA <?= $tahun ?></div>
  <div class="aksi-row">
    <a href="<?= site_url('verifikasi/prov/cetak-rekap?tahun='.$tahun) ?>"
       target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Rekap SP2D
    </a>
  </div>
</div>

<!-- Statistik -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)">
      <i class="ti ti-list" style="color:var(--biru)"></i>
    </div>
    <div class="stat-val" style="color:var(--biru)"><?= count($list) ?></div>
    <div class="stat-label">Total Antrian</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--kuning-light)">
      <i class="ti ti-file-invoice" style="color:var(--kuning-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--kuning-mid)"><?= $rekap->total_sp2d ?? 0 ?></div>
    <div class="stat-label">SP2D Diterbitkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--teal-light)">
      <i class="ti ti-cash" style="color:var(--teal-mid)"></i>
    </div>
    <div class="stat-val" style="font-size:14px;color:var(--teal-mid)">
      <?= rupiah_juta($rekap->total_disalurkan ?? 0) ?>
    </div>
    <div class="stat-label">Total Disalurkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)">
      <i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= $rekap->total_dikonfirmasi ?? 0 ?></div>
    <div class="stat-label">Dikonfirmasi Kab</div>
  </div>
</div>

<?php
$status_labels = [
  'skpkd_kab_approved' => ['biru',  'Menunggu Verifikasi'],
  'skpkd_prov_verif'   => ['kuning','Sedang Diverifikasi'],
  'skpkd_prov_revisi'  => ['oranye','Dikembalikan ke Kab'],
  'disalurkan'         => ['teal',  'Dana Disalurkan'],
  'dikonfirmasi'       => ['hijau', 'Dikonfirmasi Kab'],
];
?>
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
  <?php foreach ($status_labels as $st => [$cl,$lab]):
    if (empty($count_status[$st])) continue; ?>
  <div style="padding:5px 12px;background:var(--<?= $cl ?>-light);border-radius:12px;font-size:12px">
    <span style="color:var(--<?= $cl ?>-mid,var(--biru));font-weight:600"><?= $count_status[$st] ?></span>
    <span style="color:var(--text-muted)"> <?= $lab ?></span>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Cari</label>
      <input type="text" name="q" value="<?= htmlspecialchars($filters['q']??'') ?>"
             placeholder="Kode BKP / nama kegiatan / kab...">
    </div>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>"
          <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group"><label>Status</label>
      <select name="status">
        <option value="">Semua</option>
        <?php foreach ($status_labels as $kode => [$cl,$lab]): ?>
        <option value="<?= $kode ?>"
          <?= ($filters['status']===$kode)?'selected':'' ?>><?= $lab ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group"><label>Jenis</label>
      <select name="jenis">
        <option value="">Semua</option>
        <option value="bertahap" <?= ($filters['jenis']==='bertahap')?'selected':'' ?>>Bertahap</option>
        <option value="sekaligus" <?= ($filters['jenis']==='sekaligus')?'selected':'' ?>>Sekaligus</option>
        <option value="khusus_mendesak" <?= ($filters['jenis']==='khusus_mendesak')?'selected':'' ?>>Mendesak</option>
        <option value="khusus_bencana" <?= ($filters['jenis']==='khusus_bencana')?'selected':'' ?>>Bencana</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="ti ti-search"></i> Filter
    </button>
    <a href="<?= site_url('verifikasi/prov') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Kode BKP</th>
          <th>Kegiatan</th>
          <th>Kab/Kota</th>
          <th>Tahapan</th>
          <th style="text-align:right">Nilai Diajukan</th>
          <th>No. SP2D</th>
          <th>Tgl SP2D</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $row): ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($row->kode_bkp) ?></div>
          <div class="text-xs"><?= badge_jenis($row->jenis_penyaluran) ?></div>
        </td>
        <td>
          <div class="text-sm fw-500">
            <?= htmlspecialchars($row->nama_kegiatan_dok ?: $row->uraian_bkp) ?>
          </div>
          <div class="text-xs text-muted"><?= htmlspecialchars($row->nama_bidang) ?></div>
        </td>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->label_tahap) ?>
          <?php if ($row->no_lhr): ?>
          <div class="text-xs text-muted">LHR: <?= htmlspecialchars($row->no_lhr) ?></div>
          <?php endif; ?>
        </td>
        <td class="fw-500 text-sm" style="text-align:right">
          <?= rupiah($row->nilai_diajukan) ?>
        </td>
        <td>
          <?php if ($row->no_sp2d): ?>
          <div class="mono text-sm fw-500"><?= htmlspecialchars($row->no_sp2d) ?></div>
          <?php if ($row->nilai_transfer): ?>
          <div class="text-xs text-hijau"><?= rupiah($row->nilai_transfer) ?></div>
          <?php endif; ?>
          <?php else: ?>
          <span class="text-muted text-xs">Belum ada</span>
          <?php endif; ?>
        </td>
        <td class="text-sm"><?= $row->tgl_sp2d ? tgl_short($row->tgl_sp2d) : '—' ?></td>
        <td>
          <?php $sl = $status_labels[$row->status] ?? ['abu',$row->status];
          echo '<span class="badge badge-'.$sl[0].'">'.$sl[1].'</span>'; ?>
          <?php if ($row->status_transfer === 'selesai'): ?>
          <div class="text-xs text-hijau"><i class="ti ti-check"></i> Transfer selesai</div>
          <?php elseif ($row->status_transfer === 'proses'): ?>
          <div class="text-xs text-warning">Dalam proses</div>
          <?php endif; ?>
        </td>
        <td>
          <div class="aksi-row">
            <a href="<?= site_url('verifikasi/prov/form/'.$row->id) ?>"
               class="btn <?= $this->rbac->can('verif_prov.approve') && $row->status !== 'dikonfirmasi' ? 'btn-primary' : 'btn-outline' ?> btn-xs">
              <i class="ti ti-<?= $row->status === 'dikonfirmasi' ? 'eye' : 'shield-check' ?>"></i>
              <?= $row->status === 'dikonfirmasi' ? 'Detail' : 'Proses' ?>
            </a>
          </div>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-cash"></i></div>
          Tidak ada antrian penyaluran saat ini.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
