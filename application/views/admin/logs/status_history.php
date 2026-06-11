<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-header">
  <div class="page-title"><i class="ti ti-history"></i> Riwayat Status</div>
</div>

<div class="aksi-row" style="margin-bottom:12px">
  <a href="<?= site_url('admin/logs') ?>" class="btn btn-outline btn-sm"><i class="ti ti-list-details"></i> Log Aktivitas</a>
  <a href="<?= site_url('admin/logs/status') ?>" class="btn btn-primary btn-sm"><i class="ti ti-timeline"></i> Riwayat Status</a>
</div>

<div class="filter-row">
  <?= form_open(site_url('admin/logs/status'), ['method'=>'get']) ?>
  <div class="filter-group">
    <label>Dari Tanggal</label>
    <input type="date" name="tanggal_dari" class="form-control fc-sm" value="<?= htmlspecialchars($filters['tanggal_dari'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>Sampai Tanggal</label>
    <input type="date" name="tanggal_sampai" class="form-control fc-sm" value="<?= htmlspecialchars($filters['tanggal_sampai'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>Kab/Kota</label>
    <select name="kabkota_id" class="form-control fc-sm">
      <option value="">Semua Kab/Kota</option>
      <?php foreach ($kabkota_list as $k): ?>
      <option value="<?= $k->id ?>" <?= ($filters['kabkota_id'] == $k->id) ? 'selected' : '' ?>><?= htmlspecialchars($k->nama) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Status Baru</label>
    <select name="status_baru" class="form-control fc-sm">
      <option value="">Semua Status</option>
      <?php foreach ($status_list as $s): ?>
      <option value="<?= htmlspecialchars($s->status_baru) ?>" <?= ($filters['status_baru'] === $s->status_baru) ? 'selected' : '' ?>>
        <?= htmlspecialchars($s->status_baru) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Pencarian</label>
    <input type="text" name="q" class="form-control fc-sm" placeholder="Kode BKP / catatan / user..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>&nbsp;</label>
    <div style="display:flex;gap:6px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
      <a href="<?= site_url('admin/logs/status') ?>" class="btn btn-outline btn-sm">Reset</a>
    </div>
  </div>
  <?= form_close() ?>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="card-title" style="display:flex;justify-content:space-between;align-items:center">
    <span>Riwayat Perubahan Status</span>
    <a href="<?= site_url('admin/logs/export-status?'.http_build_query(array_filter($filters, function($v){return $v!==null && $v!=='';}))) ?>"
       class="btn btn-outline btn-xs"><i class="ti ti-download"></i> Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:140px">Waktu</th>
          <th style="width:130px">Kode BKP</th>
          <th>Uraian / Kab-Kota</th>
          <th style="width:160px">Status Lama → Baru</th>
          <th>Catatan</th>
          <th style="width:140px">Diubah Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($list)): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="ti ti-timeline"></i>Belum ada riwayat status</div></td></tr>
        <?php else: foreach ($list as $row): ?>
        <tr>
          <td class="text-xs text-muted"><?= tgl_short($row->created_at) ?></td>
          <td class="text-mono text-xs"><?= htmlspecialchars($row->kode_bkp) ?></td>
          <td>
            <div class="text-sm"><?= htmlspecialchars($row->uraian_bkp) ?></div>
            <div class="text-xs text-muted"><?= htmlspecialchars($row->nama_kabkota) ?></div>
          </td>
          <td class="text-xs">
            <?php if ($row->status_lama): ?>
            <?= badge_status($row->status_lama) ?> <i class="ti ti-arrow-right"></i>
            <?php endif; ?>
            <?= badge_status($row->status_baru) ?>
          </td>
          <td class="text-sm"><?= htmlspecialchars($row->catatan ?? '-') ?></td>
          <td class="text-xs text-muted"><?= htmlspecialchars($row->nama_user ?? '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
