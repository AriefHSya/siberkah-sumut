<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Password — SIBERKAH SUMUT</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/siberkah.css') ?>">
</head>
<body class="login-body">
<a href="<?= site_url('login') ?>" class="back-to-landing">
  <i class="ti ti-arrow-left"></i> Kembali ke Login
</a>

<div class="login-wrap">
  <div class="login-header">
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:4px">
      <?php if (!empty($logo_prov)): ?>
      <img src="<?= $logo_prov ?>" alt="Logo Provinsi"
           style="height:90px;width:90px;object-fit:contain">
      <?php endif; ?>
      <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH"
           class="login-logo" style="margin-bottom:0">
    </div>
    <h1>SIBERKAH SUMUT</h1>
    <p>Reset Password Akun</p>
  </div>
  <div class="login-card">
    <?php if ($flash = $this->session->flashdata('error')): ?>
    <div class="alert"><i class="ti ti-alert-circle"></i> <?= $flash ?></div>
    <?php endif; ?>
    <?php if ($flash = $this->session->flashdata('success')): ?>
    <div class="alert" style="background:rgba(39,80,10,0.25);border-color:rgba(39,80,10,0.4)">
      <i class="ti ti-circle-check"></i> <?= $flash ?>
    </div>
    <?php endif; ?>

    <p style="color:rgba(255,255,255,0.8);font-size:13px;margin-bottom:18px;line-height:1.5">
      Masukkan username atau email yang terdaftar. Kami akan mengirimkan link reset password ke alamat email Anda.
    </p>

    <?= form_open(site_url('lupa-password/kirim')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group">
      <label>Username atau Email</label>
      <input type="text" name="username_or_email" placeholder="Masukkan username atau email" autocomplete="username email" required autofocus>
    </div>
    <button type="submit" class="btn-login">Kirim Link Reset Password</button>
    <?= form_close() ?>
  </div>
  <div class="login-footer">
    <p>BKAD Provinsi Sumatera Utara &copy; <?= date('Y') ?> &middot; SIBERKAH SUMUT v<?= htmlspecialchars($app_version) ?></p>
  </div>
</div>
</body>
</html>
