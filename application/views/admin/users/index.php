<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-header">
  <div class="page-title"><i class="ti ti-users"></i> Manajemen User</div>
  <div class="page-actions">
    <?php if ($this->rbac->can('admin.user.create')): ?>
    <a href="<?= site_url('admin/users/tambah') ?>" class="btn-primary btn-sm"><i class="ti ti-user-plus"></i> Tambah User</a>
    <?php endif; ?>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
  <?php $warna_map=['superadmin'=>'purple','admin_provinsi'=>'biru','skpkd_kabkota'=>'teal','inspektorat'=>'kuning','opd_teknis'=>'hijau','pengawas'=>'abu'];
  foreach ($roles as $r): $w = $warna_map[$r->kode] ?? 'abu'; ?>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--<?= $w ?>-light)"><i class="ti ti-shield-half" style="color:var(--<?= $w ?>)"></i></div>
    <div class="stat-val" style="color:var(--<?= $w ?>)"><?= $stats[$r->kode] ?? 0 ?></div>
    <div class="stat-label"><?= htmlspecialchars($r->nama) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="filter-bar">
  <?= form_open(site_url('admin/users'),['method'=>'get','style'=>'display:contents']) ?>
  <div class="filter-group"><label class="filter-label">Role</label>
    <select name="role_id" class="form-control-sm">
      <option value="">Semua Role</option>
      <?php foreach ($roles as $r): ?><option value="<?= $r->id ?>" <?= ($filters['role_id']==$r->id)?'selected':'' ?>><?= $r->nama ?></option><?php endforeach; ?>
    </select>
  </div>
  <?php if ($this->rbac->isProvinsi()): ?>
  <div class="filter-group"><label class="filter-label">Kab/Kota</label>
    <select name="kabkota_id" class="form-control-sm">
      <option value="">Semua</option>
      <?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= ($filters['kabkota_id']==$k->id)?'selected':'' ?>><?= $k->nama ?></option><?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="filter-group"><label class="filter-label">Status</label>
    <select name="is_active" class="form-control-sm">
      <option value="">Semua</option><option value="1" <?= ($filters['is_active']==='1')?'selected':'' ?>>Aktif</option><option value="0" <?= ($filters['is_active']==='0')?'selected':'' ?>>Nonaktif</option>
    </select>
  </div>
  <div class="filter-group"><label class="filter-label">Cari</label>
    <input type="text" name="q" class="form-control-sm" placeholder="Nama / username..." value="<?= htmlspecialchars($filters['q']??'') ?>" style="width:180px">
  </div>
  <button type="submit" class="btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
  <a href="<?= site_url('admin/users') ?>" class="btn-outline btn-sm">Reset</a>
  <?= form_close() ?>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Nama</th><th style="width:130px">Username</th><th style="width:140px">Role</th><th style="width:110px">Instansi</th><th style="width:120px">Kab/Kota</th><th style="width:110px">Login Terakhir</th><th style="width:60px;text-align:center">Status</th><th style="width:100px;text-align:center">Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($list)): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="ti ti-users-group"></i>Belum ada user</div></td></tr>
        <?php else: foreach ($list as $u): ?>
        <tr>
          <td><div class="fw-500"><?= htmlspecialchars($u->nama) ?></div><div class="text-xs text-muted"><?= htmlspecialchars($u->email??'-') ?></div></td>
          <td class="text-mono"><?= htmlspecialchars($u->username) ?></td>
          <td><?= badge_role($u->role_kode??'',$u->role_nama??'') ?></td>
          <td class="text-xs"><?= label_instansi($u->instansi_jenis??'') ?><?= $u->opd_nama?'<br><span class="text-muted">'.htmlspecialchars($u->opd_nama).'</span>':'' ?></td>
          <td class="text-sm"><?= htmlspecialchars($u->nama_kabkota??'–') ?></td>
          <td class="text-xs text-muted"><?= $u->last_login ? tgl_short($u->last_login) : 'Belum pernah' ?></td>
          <td class="text-center"><?= $u->is_active ? '<span class="badge badge-hijau">Aktif</span>' : '<span class="badge badge-abu">Nonaktif</span>' ?></td>
          <td><div class="flex-gap" style="justify-content:center">
            <?php if ($this->rbac->canManageUser($u->role_level??99)): ?>
            <a href="<?= site_url('admin/users/edit/'.$u->id) ?>" class="btn-icon" title="Edit"><i class="ti ti-edit"></i></a>
            <?php if ($u->id != $this->session->userdata('user_id')): ?>
            <a href="<?= site_url('admin/users/toggle/'.$u->id) ?>" class="btn-icon" title="<?= $u->is_active?'Nonaktifkan':'Aktifkan' ?>" data-confirm="<?= $u->is_active?'Nonaktifkan':'Aktifkan' ?> user <?= $u->username ?>?"><i class="ti ti-<?= $u->is_active?'user-off':'user-check' ?>"></i></a>
            <a href="<?= site_url('admin/users/reset-password/'.$u->id) ?>" class="btn-icon" title="Reset Password" data-confirm="Reset password <?= $u->username ?> ke password123?"><i class="ti ti-key"></i></a>
            <?php endif; ?>
            <?php endif; ?>
          </div></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
