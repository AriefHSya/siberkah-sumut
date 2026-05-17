<div class="page-header">
  <div class="page-title"><i class="ti ti-shield-lock"></i> Role & Hak Akses</div>
  <?php if ($this->rbac->can('admin.role.create')): ?>
  <a href="<?= site_url('admin/roles/tambah') ?>" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Role</a>
  <?php endif; ?>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:12px">
  <?php foreach ($roles as $r):
    $jml_user = $count_role[$r->kode] ?? 0;
    $bar_colors = ['superadmin'=>'merah','admin_provinsi'=>'biru','skpkd_kabkota'=>'teal','inspektorat'=>'ungu','opd_teknis'=>'hijau','pengawas'=>'abu'];
    $bc = $bar_colors[$r->kode] ?? 'biru';
    $perms_count = $perm_count[$r->id] ?? 0;
  ?>
  <div class="card" style="border-left:4px solid var(--<?= $bc ?>-mid,var(--biru));padding-left:14px;margin-bottom:0">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
      <div>
        <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($r->nama) ?><?php if ($r->is_system): ?> <span class="badge badge-kuning text-xs">Sistem</span><?php endif; ?></div>
        <div class="mono text-xs text-muted"><?= $r->kode ?></div>
      </div>
      <div style="text-align:right"><div class="text-xs text-muted">Level</div><div style="font-size:22px;font-weight:700;color:var(--<?= $bc ?>-mid,var(--biru))"><?= $r->level ?></div></div>
    </div>
    <div class="text-sm text-muted mb-2"><?= htmlspecialchars($r->deskripsi??'-') ?></div>
    <div class="g2 mb-2">
      <div class="stat-card" style="padding:8px;text-align:center"><div style="font-size:18px;font-weight:600"><?= $jml_user ?></div><div class="text-xs text-muted">User aktif</div></div>
      <div class="stat-card" style="padding:8px;text-align:center"><div style="font-size:18px;font-weight:600"><?= $perms_count ?></div><div class="text-xs text-muted">Permission</div></div>
    </div>
    <div class="aksi-row">
      <?php if ($this->rbac->can('admin.role.permission')): ?>
      <a href="<?= site_url('admin/roles/permissions/'.$r->id) ?>" class="btn btn-primary btn-xs" style="flex:1;justify-content:center"><i class="ti ti-key"></i> Atur Hak Akses</a>
      <?php endif; ?>
      <?php if ($this->rbac->can('admin.role.edit') && !$r->is_system): ?>
      <a href="<?= site_url('admin/roles/edit/'.$r->id) ?>" class="btn-icon" title="Edit"><i class="ti ti-edit"></i></a>
      <?php endif; ?>
      <?php if ($this->rbac->can('admin.role.delete') && !$r->is_system && ($count_role[$r->kode]??0)==0): ?>
      <a href="<?= site_url('admin/roles/hapus/'.$r->id) ?>" class="btn-icon del" title="Hapus" data-confirm="Hapus role <?= $r->nama ?>?"><i class="ti ti-trash"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
