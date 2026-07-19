<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password — SIBERKAH SUMUT</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/siberkah.css') ?>">
</head>
<body class="login-body">

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
    <p>Buat Password Baru</p>
  </div>
  <div class="login-card">
    <?php if ($flash = $this->session->flashdata('error')): ?>
    <div class="alert"><i class="ti ti-alert-circle"></i> <?= $flash ?></div>
    <?php endif; ?>

    <p style="color:rgba(255,255,255,0.8);font-size:13px;margin-bottom:18px;line-height:1.5">
      Halo, <strong><?= htmlspecialchars($user->nama) ?></strong>. Masukkan password baru Anda di bawah ini.
    </p>

    <?= form_open(site_url('reset-password/proses')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <?= form_hidden('token', htmlspecialchars($token)) ?>

    <div class="form-group">
      <label>Password Baru <span style="opacity:.6">(min. 8 karakter)</span></label>
      <div style="position:relative">
        <input type="password" name="password" id="inputPwBaru"
               placeholder="Masukkan password baru"
               autocomplete="new-password" required
               style="padding-right:42px">
        <button type="button" onclick="togglePw('inputPwBaru','iconPw1')"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.6);padding:0;line-height:1">
          <i id="iconPw1" class="ti ti-eye" style="font-size:18px"></i>
        </button>
      </div>
    </div>
    <div class="form-group">
      <label>Konfirmasi Password Baru</label>
      <div style="position:relative">
        <input type="password" name="password_confirm" id="inputPwKonfirm"
               placeholder="Ulangi password baru"
               autocomplete="new-password" required
               style="padding-right:42px">
        <button type="button" onclick="togglePw('inputPwKonfirm','iconPw2')"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.6);padding:0;line-height:1">
          <i id="iconPw2" class="ti ti-eye" style="font-size:18px"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn-login">Simpan Password Baru</button>
    <?= form_close() ?>
  </div>
  <div class="login-footer">
    <p>BKAD Provinsi Sumatera Utara &copy; <?= date('Y') ?> &middot; SIBERKAH SUMUT v<?= htmlspecialchars($app_version) ?></p>
  </div>
</div>

<script>
function togglePw(inputId, iconId) {
  var inp  = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  if (!inp) return;
  inp.type  = (inp.type === 'password') ? 'text' : 'password';
  icon.className = (inp.type === 'password') ? 'ti ti-eye' : 'ti ti-eye-off';
}
</script>
</body>
</html>
