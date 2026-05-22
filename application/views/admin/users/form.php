<div class="page-header">
  <div class="page-title"><i class="ti ti-<?= $edit ? 'edit' : 'user-plus' ?>"></i> <?= $edit ? 'Edit User' : 'Tambah User' ?></div>
  <a href="<?= site_url('admin/users') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="card">
  <?= form_open(site_url($edit ? 'admin/users/update/'.$user->id : 'admin/users/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <div class="form-grid">
    <div class="form-group"><label>Nama Lengkap <span class="req">*</span></label><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user->nama??'') ?>" required></div>
    <div class="form-group">
      <label>NIP <span class="req">*</span> <span class="text-xs text-muted">(18 digit)</span></label>
      <input type="text" name="nip" id="inputNip" class="form-control mono"
             value="<?= htmlspecialchars($user->nip??'') ?>"
             placeholder="Contoh: 198001012005011001"
             maxlength="18" minlength="18"
             oninput="this.value=this.value.replace(/[^0-9]/g,'');updateNipHint(this)"
             pattern="[0-9]{18}"
             title="NIP harus tepat 18 digit angka"
             required>
      <div class="form-hint" id="nipHint" style="color:var(--text-muted)">
        <?= strlen($user->nip??'') === 18 ? '18/18 digit ✓' : '0/18 digit' ?>
      </div>
    </div>
    <div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username??'') ?>" required></div>
    <div class="form-group"><label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '<span class="req">*</span>' ?></label><input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?>></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email??'') ?>"></div>
    <div class="form-group"><label>No. Telepon</label><input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($user->telepon??'') ?>"></div>
    <div class="form-group"><label>Jabatan</label><input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($user->jabatan??'') ?>"></div>
  </div>
  <div class="form-grid mt-2">
    <div class="form-group"><label>Role <span class="req">*</span></label>
      <select name="role_id" id="selectRole" class="form-control" required onchange="onRoleChange(this)">
        <option value="" data-level="">-- Pilih Role --</option>
        <?php foreach ($roles as $r): if (!$this->rbac->canManageUser($r->level)) continue; ?>
        <option value="<?= $r->id ?>" data-level="<?= $r->level ?>"
          <?= (($user->role_id??'')==$r->id)?'selected':'' ?>><?= htmlspecialchars($r->nama) ?> (Level <?= $r->level ?>)</option>
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
    <div class="form-group" id="fieldTelegramWrap" style="display:none">
      <label>Telegram Chat ID <span class="text-xs text-muted">(notifikasi Admin Provinsi & Superadmin)</span></label>
      <input type="text" name="telegram_chat_id" id="inputTelegramChatId" class="form-control fc"
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

<script>
function updateNipHint(input) {
  var len  = input.value.length;
  var hint = document.getElementById('nipHint');
  if (!hint) return;
  if (len === 18) {
    hint.textContent = '18/18 digit ✓';
    hint.style.color = 'var(--hijau-mid)';
  } else if (len > 0) {
    hint.textContent = len + '/18 digit — kurang ' + (18 - len) + ' digit';
    hint.style.color = 'var(--kuning-mid)';
  } else {
    hint.textContent = '0/18 digit';
    hint.style.color = 'var(--text-muted)';
  }
}

function onRoleChange(sel) {
  var opt   = sel.options[sel.selectedIndex];
  var level = parseInt(opt.getAttribute('data-level')) || 99;
  var wrap  = document.getElementById('fieldTelegramWrap');
  var input = document.getElementById('inputTelegramChatId');
  // Tampilkan hanya untuk superadmin (level 1) dan admin_provinsi (level 2)
  var show  = (level <= 2);
  wrap.style.display = show ? '' : 'none';
  if (!show && input) input.value = '';
}

// Init saat halaman dimuat (mode edit: cek role yang sudah tersimpan)
(function() {
  var sel = document.getElementById('selectRole');
  if (sel) onRoleChange(sel);
})();
</script>
