<?= sub_nav_parameter('smtp') ?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-mail-cog"></i> Pengaturan Email (SMTP)</div>
    <div class="text-muted text-sm" style="margin-top:4px">
      Konfigurasi server email untuk pengiriman notifikasi reset password kepada user.
    </div>
  </div>
</div>

<?php if ($f = $this->session->flashdata('success')): ?>
<div class="alert alert-success"><i class="ti ti-circle-check"></i> <?= $f ?></div>
<?php endif; ?>
<?php if ($f = $this->session->flashdata('error')): ?>
<div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= $f ?></div>
<?php endif; ?>

<div class="g2" style="align-items:start">

  <!-- Form konfigurasi SMTP -->
  <div class="card">
    <div class="card-title"><i class="ti ti-settings"></i> Konfigurasi Server SMTP</div>

    <?= form_open(site_url('parameter/smtp/simpan')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group">
      <label class="form-label">SMTP Host <span class="text-merah">*</span></label>
      <input type="text" name="smtp_host" class="form-control"
             value="<?= htmlspecialchars($cfg['smtp_host'] ?? '') ?>"
             placeholder="Contoh: smtp.gmail.com">
      <div class="text-muted text-xs" style="margin-top:4px">Alamat server SMTP provider email Anda</div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label">Port</label>
        <input type="number" name="smtp_port" class="form-control"
               value="<?= htmlspecialchars($cfg['smtp_port'] ?? '587') ?>"
               placeholder="587" min="1" max="65535">
      </div>
      <div class="form-group">
        <label class="form-label">Enkripsi</label>
        <select name="smtp_crypto" class="form-control">
          <option value="tls" <?= ($cfg['smtp_crypto'] ?? '') === 'tls'  ? 'selected' : '' ?>>TLS (port 587)</option>
          <option value="ssl" <?= ($cfg['smtp_crypto'] ?? '') === 'ssl'  ? 'selected' : '' ?>>SSL (port 465)</option>
          <option value=""    <?= ($cfg['smtp_crypto'] ?? '') === ''     ? 'selected' : '' ?>>Tanpa enkripsi</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Username SMTP</label>
      <input type="text" name="smtp_user" class="form-control"
             value="<?= htmlspecialchars($cfg['smtp_user'] ?? '') ?>"
             placeholder="Contoh: noreply@instansi.go.id" autocomplete="off">
    </div>

    <div class="form-group">
      <label class="form-label">Password SMTP</label>
      <input type="password" name="smtp_pass" class="form-control"
             value="" placeholder="Kosongkan jika tidak ingin mengubah" autocomplete="new-password">
      <?php if (!empty($cfg['smtp_pass'])): ?>
      <div class="text-muted text-xs" style="margin-top:4px">
        <i class="ti ti-lock" style="font-size:12px"></i>
        Password sudah tersimpan — kosongkan field ini untuk mempertahankannya.
      </div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="form-label">Alamat Pengirim (From Email)</label>
      <input type="email" name="smtp_from_email" class="form-control"
             value="<?= htmlspecialchars($cfg['smtp_from_email'] ?? '') ?>"
             placeholder="Contoh: noreply@instansi.go.id">
    </div>

    <div class="form-group">
      <label class="form-label">Nama Pengirim (From Name)</label>
      <input type="text" name="smtp_from_name" class="form-control"
             value="<?= htmlspecialchars($cfg['smtp_from_name'] ?? 'SIBERKAH SUMUT') ?>"
             placeholder="SIBERKAH SUMUT">
    </div>

    <div style="display:flex;gap:8px;margin-top:8px">
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> Simpan Pengaturan
      </button>
    </div>

    <?= form_close() ?>
  </div>

  <!-- Panel info + test email -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-title"><i class="ti ti-send"></i> Uji Coba Kirim Email</div>
      <p class="text-muted text-sm" style="margin-bottom:12px">
        Pastikan pengaturan di sebelah kiri sudah disimpan sebelum melakukan uji coba.
      </p>
      <?= form_open(site_url('parameter/smtp/test')) ?>
      <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
      <div class="form-group">
        <label class="form-label">Kirim ke Email</label>
        <input type="email" name="test_email" class="form-control fc-sm"
               placeholder="email@tujuan.com" required>
      </div>
      <button type="submit" class="btn btn-outline btn-sm">
        <i class="ti ti-mail-forward"></i> Kirim Email Uji Coba
      </button>
      <?= form_close() ?>
    </div>

    <div class="card">
      <div class="card-title"><i class="ti ti-info-circle"></i> Panduan Konfigurasi</div>
      <div class="text-sm" style="line-height:1.7">
        <p style="margin-bottom:10px"><strong>Gmail / Google Workspace:</strong></p>
        <ul style="margin:0 0 12px 16px;color:var(--abu)">
          <li>Host: <code>smtp.gmail.com</code></li>
          <li>Port: <code>587</code> · Enkripsi: <code>TLS</code></li>
          <li>Username: alamat Gmail Anda</li>
          <li>Password: <em>App Password</em> (bukan password login)</li>
          <li>Aktifkan 2FA, lalu buat App Password di Google Account → Security</li>
        </ul>
        <p style="margin-bottom:8px"><strong>Kegunaan fitur ini:</strong></p>
        <ul style="margin:0 0 0 16px;color:var(--abu)">
          <li>User lupa password → kirim link reset ke email</li>
          <li>Admin reset password user → kirim password sementara ke email user</li>
        </ul>
      </div>
    </div>
  </div>

</div>
