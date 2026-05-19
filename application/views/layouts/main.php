<?php
/**
 * layouts/main.php — Layout Utama Admin Panel SIBERKAH SUMUT
 *
 * Digunakan oleh semua halaman terproteksi via MY_Controller::render().
 * JANGAN load view ini langsung — selalu via $this->render('modul/view', $data).
 *
 * VARIABEL YANG WAJIB ADA (di-inject Auth_Controller):
 *   $title          — judul halaman untuk tag <title>
 *   $current_user   — object data user yang login (nama, role_kode, dll.)
 *   $tahun_anggaran — tahun anggaran aktif saat ini
 *   $active_menu    — key menu yang aktif (untuk highlight sidebar)
 *   $active_sub     — key sub-menu yang aktif (untuk highlight sub-nav)
 *   $notif_count    — jumlah notifikasi belum dibaca
 *   $notif_recent   — array 5 notifikasi terbaru
 *   $content_view   — path view konten yang di-render (set oleh render())
 *
 * STRUKTUR HTML:
 *   .app-shell → .main-wrap → .sidebar + .content-area
 *   .content-area → .top-bar + [.sub-nav] + .page-body → {content_view}
 *
 * CSS: assets/css/siberkah.css (bagian 3–18: Layout Admin Panel)
 */
?>
<!DOCTYPE html>
<html lang="id" class="app-html">
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
        <div style="display:flex;align-items:center;gap:10px">
          <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH"
               style="width:36px;height:36px;object-fit:contain;flex-shrink:0">
          <div>
            <h1 style="font-size:15px;letter-spacing:1px">SIBERKAH</h1>
            <p style="margin-top:0">BKP Provinsi Sumatera Utara</p>
          </div>
        </div>
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
