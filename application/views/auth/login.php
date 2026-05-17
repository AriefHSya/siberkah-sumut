<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — SIBERKAH SUMUT</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#0d3f7a,#1A5EA8);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-wrap{width:100%;max-width:440px}
.login-header{text-align:center;margin-bottom:28px;color:#fff}
.login-header h1{font-size:28px;font-weight:700;letter-spacing:1px}
.login-header p{font-size:13px;opacity:0.7;margin-top:6px}
.login-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
.alert{padding:12px 14px;border-radius:8px;margin-bottom:18px;font-size:13px;display:flex;align-items:center;gap:8px;background:#FCEBEB;color:#791F1F;border:1px solid #F7C1C1}
.form-group{margin-bottom:16px}
label{display:block;font-size:12px;font-weight:600;color:#4b5563;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.4px}
input{width:100%;padding:10px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;transition:border-color 0.15s}
input:focus{outline:none;border-color:#1A5EA8;box-shadow:0 0 0 3px rgba(26,94,168,0.12)}
.btn-login{width:100%;padding:12px;background:#1A5EA8;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:8px;transition:background 0.15s}
.btn-login:hover{background:#134a8a}
.login-footer{text-align:center;margin-top:24px;color:rgba(255,255,255,0.6);font-size:12px}
.csrf{display:none}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-header">
    <h1><i class="ti ti-globe"></i> SIBERKAH SUMUT</h1>
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
    <p style="margin-top:4px">Berdasarkan SE Gubernur No. 900.1.1.3689</p>
  </div>
</div>
</body>
</html>
