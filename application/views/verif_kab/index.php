<div class="page-header">
  <div class="page-title"><i class="ti ti-shield-check"></i> Verifikasi SKPKD Kab/Kota TA <?= $tahun ?></div>
</div>

<?php
$status_labels = [
  'inspektorat_approved' => ['biru',   'Menunggu Verifikasi'],
  'skpkd_kab_verif'      => ['kuning', 'Sedang Diverifikasi'],
  'skpkd_kab_revisi'     => ['oranye', 'Dikembalikan'],
  'skpkd_kab_approved'   => ['teal',   'Disetujui → Menunggu Penyaluran'],
  'disalurkan'           => ['ungu',   'Dana Disalurkan'],
  'dikonfirmasi'         => ['hijau',  'Dikonfirmasi Diterima'],
];
?>

<!-- Statistik -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)"><i class="ti ti-list" style="color:var(--biru)"></i></div>
    <div class="stat-val" style="color:var(--biru)"><?= count($list) ?></div>
    <div class="stat-label">Total Antrian</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--teal-light)"><i class="ti ti-cash" style="color:var(--teal-mid)"></i></div>
    <div class="stat-val" style="font-size:15px;color:var(--teal-mid)"><?= rupiah_juta($rekap_nilai->total_nilai ?? 0) ?></div>
    <div class="stat-label">Total Nilai</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--kuning-light)"><i class="ti ti-clock" style="color:var(--kuning-mid)"></i></div>
    <div class="stat-val" style="font-size:15px;color:var(--kuning-mid)"><?= rupiah_juta($rekap_nilai->nilai_siap ?? 0) ?></div>
    <div class="stat-label">Siap Disalurkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)"><i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i></div>
    <div class="stat-val" style="font-size:15px;color:var(--hijau-mid)"><?= rupiah_juta($rekap_nilai->nilai_disalurkan ?? 0) ?></div>
    <div class="stat-label">Sudah Disalurkan</div>
  </div>
</div>

<!-- Badge status counts -->
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
  <?php foreach ($status_labels as $st => [$cl,$lab]): if (empty($count_status[$st])) continue; ?>
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
        <option value="">Semua</option>
        <?php foreach ($status_labels as $kode => [$cl,$lab]): ?>
        <option value="<?= $kode ?>" <?= ($filters['status']===$kode)?'selected':'' ?>><?= $lab ?></option>
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
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
    <a href="<?= site_url('verifikasi/kab') ?>" class="btn btn-outline btn-sm">Reset</a>
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
          <th style="text-align:right">Nilai Diajukan</th>
          <th>No. Surat Verif.</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $row): ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($row->kode_bkp) ?></div>
          <div class="text-xs text-muted"><?= $row->tahun ?></div>
        </td>
        <td>
          <div class="text-sm fw-500"><?= htmlspecialchars($row->nama_kegiatan_dok ?: $row->uraian_bkp) ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($row->nama_bidang) ?></div>
        </td>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <td class="text-sm"><?= htmlspecialchars($row->label_tahap) ?></td>
        <td><?= badge_jenis($row->jenis_penyaluran) ?></td>
        <td class="fw-500 text-sm" style="text-align:right"><?= rupiah($row->nilai_diajukan) ?></td>
        <td class="mono text-xs"><?= htmlspecialchars($row->no_surat_verif ?? '—') ?></td>
        <td>
          <?php
          $sl = $status_labels[$row->status] ?? ['abu', $row->status];
          echo '<span class="badge badge-'.$sl[0].'">'.$sl[1].'</span>';
          ?>
          <?php if ($row->tgl_verifikasi): ?>
          <div class="text-xs text-muted"><?= tgl_short($row->tgl_verifikasi) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <div class="aksi-row">
            <?php if ($this->rbac->can('verif_kab.input')
                && in_array($row->status,['inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi'])): ?>
            <a href="<?= site_url('verifikasi/kab/form/'.$row->id) ?>"
               class="btn btn-primary btn-xs">
              <i class="ti ti-shield-check"></i> Verifikasi
            </a>
            <?php elseif (in_array($row->status,['skpkd_kab_approved','disalurkan','dikonfirmasi'])): ?>
            <a href="<?= site_url('verifikasi/kab/form/'.$row->id) ?>"
               class="btn btn-outline btn-xs">
              <i class="ti ti-eye"></i> Detail
            </a>
            <?php endif; ?>
            <?php if ($this->rbac->can('verif_kab.cetak_rekap')
                && in_array($row->status,['skpkd_kab_approved','disalurkan','dikonfirmasi'])): ?>
            <a href="<?= site_url('verifikasi/kab/cetak-rekap/'.$row->id) ?>"
               target="_blank" class="btn-icon" title="Cetak Rekapitulasi">
              <i class="ti ti-printer"></i>
            </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-shield"></i></div>
          Tidak ada antrian verifikasi saat ini.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
