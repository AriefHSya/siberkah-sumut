<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-chart-bar"></i> Capaian Output Fisik</div>
</div>

<!-- Filter -->
<div class="card">
  <?= form_open(site_url('capaian'), ['method'=>'get','style'=>'display:contents']) ?>
  <div class="filter-row">
    <?php if ($this->rbac->isProvinsi()): ?>
    <div class="filter-group">
      <label class="filter-label">Kab/Kota</label>
      <select name="kabkota_id" class="form-control-sm">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($filters['kabkota_id']==$k->id)?'selected':'' ?>><?= htmlspecialchars($k->nama) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select name="status" class="form-control-sm">
        <option value="">Semua</option>
        <option value="dikonfirmasi_tahap1" <?= ($filters['status']==='dikonfirmasi_tahap1')?'selected':'' ?>>Belum Input Capaian</option>
        <option value="opd_capaian_tahap1"  <?= ($filters['status']==='opd_capaian_tahap1') ?'selected':'' ?>>Sudah Input — Belum Ajukan Tahap II</option>
        <option value="opd_submitted"       <?= ($filters['status']==='opd_submitted')       ?'selected':'' ?>>Tahap II Sudah Diajukan</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Cari</label>
      <input type="text" name="q" class="form-control-sm" placeholder="Kode BKP / Uraian…"
             value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
    </div>
    <div class="filter-group" style="align-self:flex-end;display:flex;gap:6px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
      <a href="<?= site_url('capaian') ?>" class="btn btn-outline btn-sm">Reset</a>
    </div>
  </div>
  <?= form_close() ?>
</div>

<!-- Tabel -->
<div class="card" style="padding:0;overflow:hidden">
<div style="overflow-x:auto">
<table class="tbl">
  <thead>
    <tr>
      <th>Kode BKP</th>
      <th>Uraian Kegiatan</th>
      <?php if ($this->rbac->isProvinsi()): ?><th>Kab/Kota</th><?php endif; ?>
      <th>Dana Tahap I</th>
      <th>SP2D</th>
      <th style="width:110px">Capaian Fisik</th>
      <th style="width:100px">Status</th>
      <th style="width:70px">Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!empty($list)): foreach ($list as $row): ?>
  <tr>
    <td>
      <strong style="font-family:monospace;font-size:12px"><?= htmlspecialchars($row->kode_bkp) ?></strong><br>
      <small class="text-muted"><?= htmlspecialchars($row->nama_bidang) ?></small>
    </td>
    <td>
      <?= htmlspecialchars($row->nama_kegiatan_dok ?: $row->uraian_bkp) ?>
      <?php if ($row->nama_penyedia): ?>
      <br><small class="text-muted"><?= htmlspecialchars($row->nama_penyedia) ?></small>
      <?php endif; ?>
    </td>
    <?php if ($this->rbac->isProvinsi()): ?>
    <td><?= htmlspecialchars($row->nama_kabkota) ?></td>
    <?php endif; ?>
    <td class="text-right">
      <?= rupiah($row->nilai_transfer ?? 0) ?>
      <?php if ($row->nilai_kontrak): ?>
      <br><small class="text-muted"><?= round(($row->nilai_transfer / $row->nilai_kontrak) * 100, 0) ?>% kontrak</small>
      <?php endif; ?>
    </td>
    <td style="font-size:12px">
      <?= $row->no_sp2d ? htmlspecialchars($row->no_sp2d) : '<span class="text-muted">—</span>' ?>
      <?php if ($row->tgl_sp2d): ?><br><small class="text-muted"><?= tgl_short($row->tgl_sp2d) ?></small><?php endif; ?>
    </td>
    <td>
      <?php if ($row->capaian_id): ?>
        <div style="display:flex;align-items:center;gap:6px">
          <div style="flex:1;background:#e9ecef;border-radius:4px;height:6px;overflow:hidden">
            <div style="width:<?= (float)$row->persen_fisik ?>%;background:var(--hijau-mid);height:100%"></div>
          </div>
          <span style="font-size:12px;font-weight:600;color:var(--hijau-mid);white-space:nowrap"><?= (float)$row->persen_fisik ?>%</span>
        </div>
        <?php if ($row->tgl_realisasi): ?>
        <small class="text-muted"><?= tgl_short($row->tgl_realisasi) ?></small>
        <?php endif; ?>
      <?php else: ?>
        <span class="text-muted" style="font-size:12px">Belum diisi</span>
      <?php endif; ?>
    </td>
    <td>
      <?php if ($row->status === 'dikonfirmasi_tahap1'): ?>
        <span class="badge badge-kuning">Perlu Input</span>
      <?php else: ?>
        <span class="badge badge-hijau">Selesai</span>
      <?php endif; ?>
    </td>
    <td>
      <div class="aksi-row">
        <?php if ($row->capaian_id): ?>
        <a href="<?= site_url('capaian/form/'.$row->pekerjaan_id) ?>"
           class="btn btn-outline btn-sm" title="Lihat Data Capaian">
          <i class="ti ti-eye"></i> Lihat
        </a>
        <?php endif; ?>
        <?php if ($this->rbac->can('capaian.input')): ?>
        <a href="<?= site_url('capaian/form/'.$row->pekerjaan_id) ?>"
           class="btn btn-sm <?= $row->capaian_id ? 'btn-outline' : 'btn-primary' ?>"
           title="<?= $row->capaian_id ? 'Edit Capaian' : 'Input Capaian' ?>">
          <i class="ti ti-<?= $row->capaian_id ? 'edit' : 'plus' ?>"></i>
          <?= $row->capaian_id ? 'Edit' : 'Input' ?>
        </a>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endforeach; else: ?>
  <tr>
    <td colspan="<?= $this->rbac->isProvinsi() ? 8 : 7 ?>" style="text-align:center;padding:40px;color:var(--text-muted)">
      <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-chart-bar"></i></div>
      Belum ada pekerjaan yang menunggu input capaian output.
    </td>
  </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<?php $this->load->view('partials/pagination', ['paging'=>$paging,'filters'=>$filters]); ?>
</div>
