<div class="page-header">
  <div class="page-title"><i class="ti ti-clipboard-check"></i> Reviu Inspektorat TA <?= $tahun ?></div>
</div>

<?php
$status_labels = [
  'opd_input'             => ['biru',   'Menunggu Reviu'],
  'inspektorat_reviu'     => ['kuning', 'Dalam Reviu'],
  'inspektorat_revisi'    => ['oranye', 'Dikembalikan'],
  'inspektorat_approved'  => ['hijau',  'Disetujui'],
];
?>
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
  <div class="stat-card" style="min-width:100px;text-align:center">
    <div class="stat-val" style="color:var(--biru)"><?= count($list) ?></div>
    <div class="stat-label">Total Antrian</div>
  </div>
  <?php foreach ($status_labels as $st => [$cl,$lab]): if (empty($count_status[$st])) continue; ?>
  <div class="stat-card" style="min-width:90px;text-align:center">
    <div class="stat-val" style="font-size:20px;color:var(--<?= $cl ?>-mid,var(--biru))"><?= $count_status[$st] ?></div>
    <div class="stat-label"><?= $lab ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Cari</label>
      <input type="text" name="q" value="<?= htmlspecialchars($filters['q']??'') ?>" placeholder="Kode BKP / nama kegiatan...">
    </div>
    <?php if (!empty($kabkota_list)): ?>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group"><label>Status</label>
      <select name="status">
        <option value="">Semua Status</option>
        <?php foreach ($status_labels as $kode => [$cl,$lab]): ?>
        <option value="<?= $kode ?>" <?= ($filters['status']===$kode)?'selected':'' ?>><?= $lab ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group"><label>Jenis</label>
      <select name="jenis">
        <option value="">Semua Jenis</option>
        <option value="bertahap" <?= ($filters['jenis']==='bertahap')?'selected':'' ?>>Bertahap</option>
        <option value="sekaligus" <?= ($filters['jenis']==='sekaligus')?'selected':'' ?>>Sekaligus</option>
        <option value="khusus_mendesak" <?= ($filters['jenis']==='khusus_mendesak')?'selected':'' ?>>Mendesak</option>
        <option value="khusus_bencana" <?= ($filters['jenis']==='khusus_bencana')?'selected':'' ?>>Bencana</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
    <a href="<?= site_url('reviu') ?>" class="btn btn-outline btn-sm">Reset</a>
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
          <th>Tahapan</th>
          <th>Jenis</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <th>Status Reviu</th>
          <th>LHR</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $r): ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($r->kode_bkp) ?></div>
          <div class="text-xs text-muted"><?= $r->tahun ?></div>
        </td>
        <td>
          <div class="text-sm fw-500"><?= htmlspecialchars($r->nama_kegiatan_dok ?: $r->uraian_bkp) ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($r->nama_bidang) ?></div>
        </td>
        <td class="text-sm"><?= htmlspecialchars($r->nama_kabkota) ?></td>
        <td class="text-sm"><?= htmlspecialchars($r->label_tahap) ?></td>
        <td><?= badge_jenis($r->jenis_penyaluran) ?></td>
        <td class="text-sm fw-500" style="text-align:right"><?= rupiah($r->nilai_kontrak) ?></td>
        <td>
          <?php
          $st_label = [
            'opd_input'            => ['biru',  'Menunggu'],
            'inspektorat_reviu'    => ['kuning','Dalam Reviu'],
            'inspektorat_revisi'   => ['oranye','Dikembalikan'],
            'inspektorat_approved' => ['hijau', 'Disetujui'],
          ];
          $sl = $st_label[$r->status] ?? ['abu',$r->status];
          ?>
          <span class="badge badge-<?= $sl[0] ?>"><?= $sl[1] ?></span>
        </td>
        <td>
          <?php if ($r->no_lhr): ?>
          <div class="text-xs fw-500"><?= htmlspecialchars($r->no_lhr) ?></div>
          <?php else: ?><span class="text-muted text-xs">—</span><?php endif; ?>
        </td>
        <td>
          <div class="aksi-row">
            <?php if ($this->rbac->can('reviu.input') && in_array($r->status,['opd_input','inspektorat_reviu','inspektorat_revisi'])): ?>
            <a href="<?= site_url('reviu/form/'.$r->id) ?>" class="btn btn-primary btn-xs">
              <i class="ti ti-clipboard-check"></i> Reviu
            </a>
            <?php elseif ($this->rbac->can('reviu.cetak_kertas_kerja') && $r->reviu_id): ?>
            <a href="<?= site_url('reviu/form/'.$r->id) ?>" class="btn btn-outline btn-xs">
              <i class="ti ti-eye"></i> Lihat
            </a>
            <?php endif; ?>
            <?php if ($r->reviu_id && $this->rbac->can('reviu.cetak_kertas_kerja')): ?>
            <a href="<?= site_url('reviu/cetak-kertas-kerja/'.$r->reviu_id) ?>" target="_blank"
               class="btn-icon" title="Cetak Kertas Kerja"><i class="ti ti-printer"></i></a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-clipboard"></i></div>
          Tidak ada antrian reviu saat ini.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
