<div class="page-header">
  <div class="page-title"><i class="ti ti-key"></i> Ganti Password</div>
</div>

<?php if ($wajib): ?>
<div class="alert alert-warning">
  Password akun Anda baru saja direset / dibuat oleh Admin. Silakan buat password baru
  sebelum melanjutkan menggunakan aplikasi.
</div>
<?php endif; ?>

<div class="card" style="max-width:480px">
  <?= form_open(site_url('ganti-password/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

  <?php if (!$wajib): ?>
  <div class="form-group">
    <label>Password Lama <span class="req">*</span></label>
    <input type="password" name="password_lama" class="form-control" required autocomplete="current-password">
  </div>
  <?php endif; ?>

  <div class="form-group">
    <label>Password Baru <span class="req">*</span> <span class="text-xs text-muted">(minimal 8 karakter)</span></label>
    <input type="password" name="password_baru" class="form-control" minlength="8" required autocomplete="new-password">
  </div>

  <div class="form-group">
    <label>Ulangi Password Baru <span class="req">*</span></label>
    <input type="password" name="password_baru2" class="form-control" minlength="8" required autocomplete="new-password">
  </div>

  <div class="aksi-row mt-2">
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan Password</button>
    <?php if (!$wajib): ?>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-outline">Batal</a>
    <?php endif; ?>
  </div>

  <?= form_close() ?>
</div>
