<div class="page-header">
  <div class="page-title"><i class="ti ti-history"></i> Log Perubahan Hak Akses</div>
  <a href="<?= site_url('admin/roles') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="card">
  <table class="tbl">
    <thead><tr><th>Waktu</th><th>Role</th><th>Aksi</th><th>Permission</th><th>Oleh</th></tr></thead>
    <tbody>
    <?php if (!empty($logs)): foreach ($logs as $l): ?>
    <tr>
      <td class="text-xs"><?= tgl_short($l->created_at) ?></td>
      <td class="mono text-sm"><?= htmlspecialchars($l->role_nama) ?></td>
      <td><?= $l->aksi==='grant' ? '<span class="badge badge-hijau"><i class="ti ti-plus"></i> Grant</span>' : '<span class="badge badge-merah"><i class="ti ti-minus"></i> Revoke</span>' ?></td>
      <td class="mono text-sm"><?= htmlspecialchars($l->permission_kode) ?></td>
      <td class="text-sm"><?= htmlspecialchars($l->nama_user??'-') ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">Belum ada log perubahan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
