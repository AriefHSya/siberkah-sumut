<div class="page-header">
  <div class="page-title"><i class="ti ti-<?= $edit ? 'edit' : 'plus-circle' ?>"></i> <?= $edit ? 'Edit Role' : 'Tambah Role' ?></div>
  <a href="<?= site_url('admin/roles') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="card">
  <?= form_open(site_url($edit ? 'admin/roles/update/'.$role->id : 'admin/roles/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <?php if (!$edit): ?>
  <div class="form-group mb-2"><label>Kode Role <span class="req">*</span></label>
    <input type="text" name="kode" class="form-control" placeholder="misal: operator_kab" required pattern="[a-z0-9_]+">
    <div class="form-hint">Huruf kecil, angka, dan underscore saja. Tidak bisa diubah setelah dibuat.</div>
  </div>
  <?php endif; ?>
  <div class="form-group mb-2"><label>Nama Role <span class="req">*</span></label><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($role->nama??'') ?>" required></div>
  <div class="form-group mb-2"><label>Deskripsi</label><input type="text" name="deskripsi" class="form-control" value="<?= htmlspecialchars($role->deskripsi??'') ?>"></div>
  <?php if (!$edit || !($role->is_system??FALSE)): ?>
  <div class="form-group mb-2"><label>Level <span class="req">*</span></label>
    <input type="number" name="level" class="form-control" value="<?= $role->level??9 ?>" min="3" max="99" style="width:120px">
    <div class="form-hint">Level menentukan hirarki. Lebih kecil = lebih tinggi. Min: 3 (reservasi 1-2 untuk sistem).</div>
  </div>
  <?php endif; ?>
  <div class="form-actions">
    <a href="<?= site_url('admin/roles') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> <?= $edit ? 'Simpan Perubahan' : 'Tambah Role & Atur Hak Akses' ?></button>
  </div>
  <?= form_close() ?>
</div>
