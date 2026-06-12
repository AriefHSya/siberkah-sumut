<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-header">
  <div class="page-title"><i class="ti ti-history"></i> Log Aktivitas</div>
</div>

<div class="aksi-row" style="margin-bottom:12px">
  <a href="<?= site_url('admin/logs') ?>" class="btn btn-primary btn-sm"><i class="ti ti-list-details"></i> Log Aktivitas</a>
  <a href="<?= site_url('admin/logs/status') ?>" class="btn btn-outline btn-sm"><i class="ti ti-timeline"></i> Riwayat Status</a>
</div>

<div class="filter-row">
  <?= form_open(site_url('admin/logs'), ['method'=>'get']) ?>
  <div class="filter-group">
    <label>Dari Tanggal</label>
    <input type="date" name="tanggal_dari" class="form-control fc-sm" value="<?= htmlspecialchars($filters['tanggal_dari'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>Sampai Tanggal</label>
    <input type="date" name="tanggal_sampai" class="form-control fc-sm" value="<?= htmlspecialchars($filters['tanggal_sampai'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>User</label>
    <select name="user_id" class="form-control fc-sm">
      <option value="">Semua User</option>
      <?php foreach ($users_list as $u): ?>
      <option value="<?= $u->id ?>" <?= ($filters['user_id'] == $u->id) ? 'selected' : '' ?>>
        <?= htmlspecialchars($u->nama) ?> (<?= htmlspecialchars($u->username) ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Aksi</label>
    <select name="aksi" class="form-control fc-sm">
      <option value="">Semua Aksi</option>
      <?php foreach ($aksi_list as $a): ?>
      <option value="<?= htmlspecialchars($a->aksi) ?>" <?= ($filters['aksi'] === $a->aksi) ? 'selected' : '' ?>>
        <?= htmlspecialchars($a->aksi) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Pencarian</label>
    <input type="text" name="q" class="form-control fc-sm" placeholder="Nama / aksi / keterangan..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
  </div>
  <div class="filter-group">
    <label>&nbsp;</label>
    <div style="display:flex;gap:6px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
      <a href="<?= site_url('admin/logs') ?>" class="btn btn-outline btn-sm">Reset</a>
    </div>
  </div>
  <?= form_close() ?>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="card-title" style="display:flex;justify-content:space-between;align-items:center">
    <span>Daftar Aktivitas</span>
    <a href="<?= site_url('admin/logs/export?'.http_build_query(array_filter($filters, function($v){return $v!==null && $v!=='';}))) ?>"
       class="btn btn-outline btn-xs"><i class="ti ti-download"></i> Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:140px">Waktu</th>
          <th>User</th>
          <th style="width:130px">Role</th>
          <th style="width:160px">Aksi</th>
          <th>Deskripsi</th>
          <th style="width:110px">IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($list)): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="ti ti-history"></i>Belum ada log aktivitas</div></td></tr>
        <?php else: foreach ($list as $row): ?>
        <tr>
          <td class="text-xs text-muted"><?= tgl_short($row->created_at) ?></td>
          <td>
            <div class="fw-500"><?= htmlspecialchars($row->nama_user ?? '-') ?></div>
            <div class="text-xs text-muted"><?= htmlspecialchars($row->username ?? '-') ?></div>
          </td>
          <td><?= $row->role_kode ? badge_role($row->role_kode) : '-' ?></td>
          <td class="text-mono text-xs"><?= htmlspecialchars($row->aksi) ?></td>
          <td class="text-sm"><?= htmlspecialchars($row->keterangan ?? '-') ?></td>
          <td class="text-xs text-muted"><?= htmlspecialchars($row->ip_address ?? '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
