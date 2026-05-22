<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — SIBERKAH SUMUT</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/siberkah.css') ?>">
</head>
<body class="login-body">
<!-- Tombol kembali ke landing — class .back-to-landing di siberkah.css -->
<a href="<?= site_url('/') ?>" class="back-to-landing">
  <i class="ti ti-arrow-left"></i> Beranda
</a>

<div class="login-wrap">
  <div class="login-header">
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:4px">
      <?php if (!empty($logo_prov)): ?>
      <img src="<?= $logo_prov ?>" alt="Logo Provinsi"
           style="height:72px;width:72px;object-fit:contain">
      <?php endif; ?>
      <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH"
           class="login-logo" style="margin-bottom:0">
    </div>
    <h1>SIBERKAH SUMUT</h1>
    <p>Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota</p>
  </div>
  <div class="login-card">
    <?php if ($flash = $this->session->flashdata('error')): ?>
    <div class="alert"><i class="ti ti-alert-circle"></i> <?= $flash ?></div>
    <?php endif; ?>

    <?= form_open(site_url('login/proses')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Masukkan username" autocomplete="username" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-login">Masuk ke Sistem</button>
    <?= form_close() ?>
  </div>
  <div class="login-footer">
    <p>BKAD Provinsi Sumatera Utara &copy; <?= date('Y') ?></p>
    <p>Berdasarkan SE Gubernur No. 900.1.1.3689</p>
  </div>
</div>
</body>
</html>
