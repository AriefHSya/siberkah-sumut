<div class="page-header">
  <div class="page-title"><i class="ti ti-<?= $edit ? 'edit' : 'user-plus' ?>"></i> <?= $edit ? 'Edit User' : 'Tambah User' ?></div>
  <a href="<?= site_url('admin/users') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="card">
  <?= form_open(site_url($edit ? 'admin/users/update/'.$user->id : 'admin/users/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <div class="form-grid">
    <div class="form-group"><label>Nama Lengkap <span class="req">*</span></label><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user->nama??'') ?>" required></div>
    <div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username??'') ?>" required></div>
    <div class="form-group"><label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '<span class="req">*</span>' ?></label><input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?>></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email??'') ?>"></div>
    <div class="form-group"><label>No. Telepon</label><input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($user->telepon??'') ?>"></div>
    <div class="form-group"><label>Jabatan</label><input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($user->jabatan??'') ?>"></div>
  </div>
  <div class="form-grid mt-2">
    <div class="form-group"><label>Role <span class="req">*</span></label>
      <select name="role_id" class="form-control" required>
        <option value="">-- Pilih Role --</option>
        <?php foreach ($roles as $r): if (!$this->rbac->canManageUser($r->level)) continue; ?>
        <option value="<?= $r->id ?>" <?= (($user->role_id??'')==$r->id)?'selected':'' ?>><?= htmlspecialchars($r->nama) ?> (Level <?= $r->level ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Jenis Instansi</label>
      <select name="instansi_jenis" class="form-control">
        <option value="">-- Pilih --</option>
        <option value="bkad_provinsi" <?= (($user->instansi_jenis??'')=='bkad_provinsi')?'selected':'' ?>>BKAD Provinsi</option>
        <option value="skpkd_kabkota" <?= (($user->instansi_jenis??'')=='skpkd_kabkota')?'selected':'' ?>>SKPKD Kab/Kota</option>
        <option value="inspektorat"   <?= (($user->instansi_jenis??'')=='inspektorat')?'selected':'' ?>>Inspektorat</option>
        <option value="opd_teknis"    <?= (($user->instansi_jenis??'')=='opd_teknis')?'selected':'' ?>>OPD/Dinas Teknis</option>
        <option value="lainnya"       <?= (($user->instansi_jenis??'')=='lainnya')?'selected':'' ?>>Lainnya</option>
      </select>
    </div>
    <div class="form-group"><label>Kabupaten/Kota</label>
      <select name="kabkota_id" class="form-control">
        <option value="">— Provinsi / tidak terikat wilayah —</option>
        <?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= (($user->kabkota_id??'')==$k->id)?'selected':'' ?>><?= htmlspecialchars($k->nama) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Nama OPD/Dinas <span class="text-xs text-muted">(jika OPD Teknis)</span></label><input type="text" name="opd_nama" class="form-control" value="<?= htmlspecialchars($user->opd_nama??'') ?>" placeholder="Dinas PUPR Kab. Samosir"></div>
    <div class="form-group">
      <label>Telegram Chat ID <span class="text-xs text-muted">(notifikasi — Admin Provinsi & Superadmin)</span></label>
      <input type="text" name="telegram_chat_id" class="form-control fc"
             value="<?= htmlspecialchars($user->telegram_chat_id??'') ?>"
             placeholder="Contoh: 123456789">
      <small class="text-muted">Cara dapat Chat ID: kirim /start ke bot → buka <code>t.me/userinfobot</code> atau gunakan <code>/getUpdates</code> di API bot.</small>
    </div>
  </div>
  <div class="form-actions">
    <a href="<?= site_url('admin/users') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> <?= $edit ? 'Simpan Perubahan' : 'Tambah User' ?></button>
  </div>
  <?= form_close() ?>
</div>
