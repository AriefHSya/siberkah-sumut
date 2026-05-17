<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'SIBERKAH SUMUT') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/siberkah.css') ?>">
</head>
<body>
<div class="app-shell">
  <div class="main-wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <h1><i class="ti ti-globe"></i> SIBERKAH</h1>
        <p>BKP Provinsi Sumatera Utara</p>
      </div>
      <nav class="sidebar-nav">
        <?php foreach ($this->rbac->getMenus() as $m): ?>
        <a href="<?= site_url($m['url']) ?>" class="nav-item <?= ($active_menu === $m['key']) ? 'on' : '' ?>">
          <i class="ti ti-<?= $m['icon'] ?>" aria-hidden="true"></i>
          <?= htmlspecialchars($m['label']) ?>
        </a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-footer">
        <div style="font-size:10px;color:rgba(255,255,255,0.4);margin-bottom:6px">TA <?= $tahun_anggaran ?></div>
        <a href="<?= site_url('logout') ?>" class="nav-item" style="border-radius:6px;margin:0">
          <i class="ti ti-logout" aria-hidden="true"></i> Keluar
        </a>
      </div>
    </aside>

    <!-- CONTENT AREA -->
    <div class="content-area">

      <!-- TOP BAR -->
      <header class="top-bar">
        <div class="breadcrumb">
          <strong><?= htmlspecialchars($current_user->nama ?? '') ?></strong>
          <span style="margin:0 6px;color:#ccc">·</span>
          <?= badge_role($current_user->role_kode ?? '') ?>
          <?php if ($current_user->kabkota_nama ?? ''): ?>
          <span style="margin:0 6px;color:#ccc">·</span>
          <span class="text-sm text-muted"><?= htmlspecialchars($current_user->kabkota_nama) ?></span>
          <?php endif; ?>
        </div>

        <!-- Ganti Tahun -->
        <form method="post" action="<?= site_url('dashboard/set-tahun') ?>" style="display:flex;align-items:center;gap:6px">
          <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
          <label style="font-size:11px;color:var(--text-muted)">TA</label>
          <select name="tahun" onchange="this.form.submit()" style="padding:4px 8px;font-size:12px;border:1px solid var(--border);border-radius:6px">
            <?php foreach ($tahun_list_global as $ty): ?>
            <option value="<?= $ty->tahun ?>" <?= ($ty->tahun == $tahun_anggaran) ? 'selected' : '' ?>><?= $ty->tahun ?></option>
            <?php endforeach; ?>
          </select>
        </form>

        <!-- Notifikasi -->
        <div style="position:relative">
          <div class="notif-btn">
            <i class="ti ti-bell" aria-hidden="true"></i>
            <?php if ($notif_count > 0): ?>
            <span class="notif-badge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- User pill -->
        <div class="user-pill">
          <div class="user-ava"><?= strtoupper(substr($current_user->nama ?? 'U', 0, 1)) ?></div>
          <span class="text-sm"><?= htmlspecialchars(explode(' ', $current_user->nama ?? '')[0]) ?></span>
        </div>
      </header>

      <!-- SUB NAV -->
      <?php if ($active_menu === 'parameter'): ?>
      <?= sub_nav_parameter($active_sub ?? '') ?>
      <?php elseif ($active_menu === 'admin'): ?>
      <?= sub_nav_pengaturan($active_sub ?? '') ?>
      <?php endif; ?>

      <!-- PAGE BODY -->
      <main class="page-body">
        <!-- Flash Messages -->
        <?php if ($flash = $this->session->flashdata('success')): ?>
        <div class="alert alert-success auto-close"><i class="ti ti-circle-check"></i><div><?= $flash ?></div></div>
        <?php endif; ?>
        <?php if ($flash = $this->session->flashdata('error')): ?>
        <div class="alert alert-error"><i class="ti ti-alert-circle"></i><div><?= $flash ?></div></div>
        <?php endif; ?>
        <?php if ($flash = $this->session->flashdata('warning')): ?>
        <div class="alert alert-warning auto-close"><i class="ti ti-alert-triangle"></i><div><?= $flash ?></div></div>
        <?php endif; ?>
        <?php if ($flash = $this->session->flashdata('info')): ?>
        <div class="alert alert-info auto-close"><i class="ti ti-info-circle"></i><div><?= $flash ?></div></div>
        <?php endif; ?>

        <!-- KONTEN -->
        <?php $this->load->view($content_view, get_defined_vars()) ?>
      </main>

    </div><!-- .content-area -->
  </div><!-- .main-wrap -->
</div><!-- .app-shell -->

<script src="<?= base_url('assets/js/siberkah.js') ?>"></script>
</body>
</html>
