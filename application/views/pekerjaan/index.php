<div class="page-header">
  <div class="page-title"><i class="ti ti-file-text"></i> Pekerjaan BKP TA <?= $tahun ?></div>
  <?php if ($this->rbac->can('pekerjaan.input')): ?>
  <a href="<?= site_url('pekerjaan/input') ?>" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Input Pekerjaan Baru</a>
  <?php endif; ?>
</div>

<!-- Statistik Status -->
<?php
$status_labels = [
  'draft'                => ['abu',    'Draft'],
  'opd_submitted'        => ['biru',   'Diajukan'],
  'inspektorat_reviu'    => ['kuning', 'Dalam Reviu'],
  'inspektorat_revisi'   => ['oranye', 'Revisi Inspektorat'],
  'inspektorat_approved' => ['hijau',  'Reviu Selesai'],
  'skpkd_kab_verif'      => ['biru',   'Verif. Kab'],
  'skpkd_kab_approved'   => ['teal',   'Verif. Kab Selesai'],
  'disalurkan_tahap1'    => ['teal',   'Disalurkan T.I'],
  'disalurkan_sekaligus' => ['hijau',  'Disalurkan'],
  'selesai'              => ['hijau',  'Selesai'],
  'ditolak'              => ['merah',  'Ditolak'],
];
$total_all = array_sum($count_status);
?>
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
  <div class="stat-card" style="min-width:100px;text-align:center">
    <div class="stat-val" style="font-size:22px;color:var(--biru)"><?= $total_all ?></div>
    <div class="stat-label">Total</div>
  </div>
  <?php foreach (['draft','opd_submitted','inspektorat_reviu','inspektorat_approved','selesai','ditolak'] as $st):
    if (empty($count_status[$st])) continue;
    $sl = $status_labels[$st]; ?>
  <div class="stat-card" style="min-width:90px;text-align:center">
    <div class="stat-val" style="font-size:22px;color:var(--<?= $sl[0] ?>-mid,var(--biru))"><?= $count_status[$st] ?></div>
    <div class="stat-label"><?= $sl[1] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Cari</label><input type="text" name="q" value="<?= htmlspecialchars($filters['q']??'') ?>" placeholder="Kode BKP / nama kegiatan..."></div>
    <?php if (!empty($kabkota_list)): ?>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group"><label>Jenis</label>
      <select name="jenis">
        <option value="">Semua Jenis</option>
        <option value="bertahap" <?= ($filters['jenis_penyaluran']==='bertahap')?'selected':'' ?>>Bertahap</option>
        <option value="sekaligus" <?= ($filters['jenis_penyaluran']==='sekaligus')?'selected':'' ?>>Sekaligus</option>
        <option value="khusus_mendesak" <?= ($filters['jenis_penyaluran']==='khusus_mendesak')?'selected':'' ?>>Kondisi Mendesak</option>
        <option value="khusus_bencana" <?= ($filters['jenis_penyaluran']==='khusus_bencana')?'selected':'' ?>>Darurat Bencana</option>
      </select>
    </div>
    <div class="filter-group"><label>Status</label>
      <select name="status">
        <option value="">Semua Status</option>
        <?php foreach ($status_labels as $kode => [$cl,$lab]): ?><option value="<?= $kode ?>" <?= ($filters['status']===$kode)?'selected':'' ?>><?= $lab ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
    <a href="<?= site_url('pekerjaan') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Kode BKP</th>
          <th>Nama Kegiatan</th>
          <th>Kab/Kota</th>
          <th>Jenis</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $p): ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($p->kode_bkp) ?></div>
          <div class="text-xs text-muted"><?= $p->tahun ?></div>
        </td>
        <td>
          <div class="fw-500 text-sm"><?= htmlspecialchars($p->nama_kegiatan_dok ?: $p->uraian_bkp) ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($p->nama_bidang) ?></div>
        </td>
        <td class="text-sm"><?= htmlspecialchars($p->nama_kabkota) ?></td>
        <td><?= badge_jenis($p->jenis_penyaluran) ?></td>
        <td style="text-align:right">
          <div class="fw-500 text-sm"><?= rupiah($p->nilai_kontrak) ?></div>
          <?php if ($p->nilai_belanja_pendukung > 0): ?>
          <div class="text-xs text-muted">+<?= rupiah($p->nilai_belanja_pendukung) ?> pend.</div>
          <?php endif; ?>
        </td>
        <td><?= badge_status($p->status) ?></td>
        <td>
          <div class="aksi-row">
            <a href="<?= site_url('pekerjaan/detail/'.$p->id) ?>" class="btn btn-outline btn-xs"><i class="ti ti-eye"></i> Detail</a>
            <?php if ($this->rbac->can('pekerjaan.edit') && in_array($p->status, ['draft','inspektorat_revisi','skpkd_kab_revisi'])): ?>
            <a href="<?= site_url('pekerjaan/edit/'.$p->id) ?>" class="btn-icon" title="Edit"><i class="ti ti-edit"></i></a>
            <?php endif; ?>
            <?php if ($this->rbac->can('pekerjaan.cetak_permohonan') && $p->status !== 'draft'): ?>
            <a href="<?= site_url('pekerjaan/cetak-permohonan/'.$p->id) ?>" target="_blank" class="btn-icon" title="Cetak Permohonan Reviu"><i class="ti ti-printer"></i></a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-file-text"></i></div>
          Belum ada data pekerjaan untuk TA <?= $tahun ?>.
          <?php if ($this->rbac->can('pekerjaan.input')): ?>
          <br><a href="<?= site_url('pekerjaan/input') ?>" class="btn btn-primary btn-sm" style="margin-top:10px;display:inline-flex"><i class="ti ti-plus"></i> Input Pekerjaan Pertama</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
