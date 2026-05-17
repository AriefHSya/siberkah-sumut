<div class="page-header">
  <div class="page-title"><i class="ti ti-key"></i> Hak Akses: <?= htmlspecialchars($role->nama) ?></div>
  <a href="<?= site_url('admin/roles') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="alert alert-info mb-2"><i class="ti ti-info-circle"></i><div>Perubahan hak akses berlaku saat user melakukan login berikutnya. Setiap perubahan dicatat dalam log audit.</div></div>
<?= form_open(site_url('admin/roles/save-permissions/'.$role->id)) ?>
<?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
<?php foreach ($grouped as $modul => $perms):
  $meta = $modul_meta[$modul] ?? ['label'=>$modul,'icon'=>'lock'];
  $all_checked = !array_diff(array_column($perms,'kode'), $role_perms);
?>
<div class="card mb-2">
  <div class="perm-modul">
    <i class="ti ti-<?= $meta['icon'] ?>"></i> <?= htmlspecialchars($meta['label']) ?>
    <label style="margin-left:auto;font-size:11px;cursor:pointer">
      <input type="checkbox" <?= $all_checked?'checked':'' ?> onchange="togglePermGroup('<?= $modul ?>',this.checked)"> Pilih semua
    </label>
  </div>
  <div class="perm-items">
    <?php foreach ($perms as $p): $ck = in_array($p->kode,$role_perms); ?>
    <label class="perm-item <?= $ck?'active':'' ?>">
      <input type="checkbox" name="perms[]" value="<?= $p->kode ?>" class="perm-check" data-modul="<?= $modul ?>" <?= $ck?'checked':'' ?>>
      <div>
        <div class="pn"><?= htmlspecialchars($p->nama) ?></div>
        <div class="pk"><?= $p->kode ?></div>
      </div>
    </label>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<div class="form-actions" style="border:none;padding:0">
  <a href="<?= site_url('admin/roles') ?>" class="btn btn-outline">Batal</a>
  <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan Hak Akses</button>
</div>
<?= form_close() ?>
