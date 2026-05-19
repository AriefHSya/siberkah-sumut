<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIBERKAH SUMUT — Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/siberkah.css') ?>">
</head>
<body class="landing-body">

<?php
// Cek apakah ada foto pejabat yang sudah diupload
$has_kiri  = (!empty($pejabat['gubernur']->foto_path)       && file_exists(FCPATH . $pejabat['gubernur']->foto_path))
           || (!empty($pejabat['wakil_gubernur']->foto_path) && file_exists(FCPATH . $pejabat['wakil_gubernur']->foto_path));
$has_kanan = (!empty($pejabat['sekda']->foto_path)          && file_exists(FCPATH . $pejabat['sekda']->foto_path))
           || (!empty($pejabat['kepala_bkad']->foto_path)    && file_exists(FCPATH . $pejabat['kepala_bkad']->foto_path));
?>

<div id="landing">
  <div class="grid-bg"></div>
  <div class="wave-bg"></div>

  <?php if ($has_kiri): ?>
  <!-- BOX KIRI: Gubernur + Wakil Gubernur -->
  <div class="foto-box foto-box-kiri">
    <?php foreach (['gubernur','wakil_gubernur'] as $jk):
      $fp = $pejabat[$jk] ?? NULL;
      if (!$fp || !$fp->foto_path || !file_exists(FCPATH . $fp->foto_path)) continue;
    ?>
    <div class="foto-card">
      <img src="<?= base_url($fp->foto_path) ?>" alt="<?= htmlspecialchars($fp->nama ?? '') ?>">
      <div class="foto-card-info">
        <?php if ($fp->nama): ?>
        <div class="foto-card-nama"><?= htmlspecialchars($fp->nama) ?></div>
        <?php endif; ?>
        <div class="foto-card-jabatan"><?= htmlspecialchars($fp->jabatan ?? '') ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($has_kanan): ?>
  <!-- BOX KANAN: Sekda + Kepala BKAD -->
  <div class="foto-box foto-box-kanan">
    <?php foreach (['sekda','kepala_bkad'] as $jk):
      $fp = $pejabat[$jk] ?? NULL;
      if (!$fp || !$fp->foto_path || !file_exists(FCPATH . $fp->foto_path)) continue;
    ?>
    <div class="foto-card">
      <img src="<?= base_url($fp->foto_path) ?>" alt="<?= htmlspecialchars($fp->nama ?? '') ?>">
      <div class="foto-card-info">
        <?php if ($fp->nama): ?>
        <div class="foto-card-nama"><?= htmlspecialchars($fp->nama) ?></div>
        <?php endif; ?>
        <div class="foto-card-jabatan"><?= htmlspecialchars($fp->jabatan ?? '') ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="hero-content">

    <!-- Logo + Nama -->
    <div class="logo-mark">
      <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH SUMUT" class="logo-img">

      <div class="logo-text-wrap">
        <div class="logo-name">SIBERKAH</div>
        <div class="logo-divider"><span>— SUMUT —</span></div>
        <div class="logo-sub">PEMERINTAH PROVINSI SUMATERA UTARA</div>
      </div>
    </div>

    <p class="hero-tagline">Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota</p>

    <p class="hero-sub">
      Salah satu bentuk dukungan Pemerintah Provinsi Sumatera Utara dalam rangka pencapaian
      keberhasilan program prioritas Pemerintah Kabupaten/Kota yang bersinergi dengan program
      prioritas Pemerintah Provinsi Sumatera Utara, demi mewujudkan pengelolaan keuangan daerah
      yang efisien, efektif, akuntabel, dan berintegritas.
    </p>

    <a href="<?= site_url('login') ?>" class="btn-masuk">
      <i class="ti ti-login"></i> Masuk ke Sistem
    </a>

    <!-- 4 Pilar -->
    <div class="pilar-row">
      <div class="pilar-card">
        <div class="pilar-icon pilar-icon-biru">
          <i class="ti ti-users pilar-ic-biru"></i>
        </div>
        <h4>KOLABORASI</h4>
        <p>Bersinergi Membangun Sumatera Utara</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon pilar-icon-hijau">
          <i class="ti ti-shield-check pilar-ic-hijau"></i>
        </div>
        <h4>TRANSPARANSI</h4>
        <p>Terbuka dan Akuntabel</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon pilar-icon-kuning">
          <i class="ti ti-chart-bar pilar-ic-kuning"></i>
        </div>
        <h4>EFEKTIF</h4>
        <p>Tepat Sasaran, Berdampak Nyata</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon pilar-icon-merah">
          <i class="ti ti-refresh pilar-ic-merah"></i>
        </div>
        <h4>BERKELANJUTAN</h4>
        <p>Untuk Pembangunan Sumut yang Maju</p>
      </div>
    </div>

  </div><!-- .hero-content -->

  <?php if (!empty($slideshow)): ?>
  <!-- SLIDESHOW KINERJA PEMPROVSU -->
  <div class="slideshow-wrap">
    <div class="slideshow-box" id="slideshow"
         onmouseenter="pauseSlide()" onmouseleave="resumeSlide()">

      <?php foreach ($slideshow as $si => $slide): ?>
      <div class="slide <?= $si === 0 ? 'active' : '' ?>" data-idx="<?= $si ?>">
        <img src="<?= base_url($slide->foto_path) ?>"
             alt="<?= htmlspecialchars($slide->judul ?? '') ?>"
             loading="<?= $si === 0 ? 'eager' : 'lazy' ?>">
        <?php if ($slide->judul || $slide->caption): ?>
        <div class="slide-caption">
          <?php if ($slide->judul): ?>
          <div class="slide-judul"><?= htmlspecialchars($slide->judul) ?></div>
          <?php endif; ?>
          <?php if ($slide->caption): ?>
          <div><?= htmlspecialchars($slide->caption) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <?php if (count($slideshow) > 1): ?>
      <button class="slide-btn slide-btn-prev" onclick="changeSlide(-1)" aria-label="Sebelumnya">
        <i class="ti ti-chevron-left"></i>
      </button>
      <button class="slide-btn slide-btn-next" onclick="changeSlide(1)" aria-label="Berikutnya">
        <i class="ti ti-chevron-right"></i>
      </button>
      <div class="slide-dots">
        <?php foreach ($slideshow as $si => $slide): ?>
        <button class="slide-dot <?= $si === 0 ? 'active' : '' ?>"
                onclick="goSlide(<?= $si ?>)" aria-label="Slide <?= $si+1 ?>"></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script>
  (function(){
    var slides  = document.querySelectorAll('#slideshow .slide');
    var dots    = document.querySelectorAll('#slideshow .slide-dot');
    var current = 0;
    var total   = slides.length;
    var timer   = null;
    var paused  = false;

    function show(idx) {
      slides[current].classList.remove('active');
      if (dots[current]) dots[current].classList.remove('active');
      current = (idx + total) % total;
      slides[current].classList.add('active');
      if (dots[current]) dots[current].classList.add('active');
    }

    window.changeSlide = function(dir) { show(current + dir); };
    window.goSlide     = function(idx) { show(idx); };
    window.pauseSlide  = function() { paused = true; };
    window.resumeSlide = function() { paused = false; };

    if (total > 1) {
      timer = setInterval(function(){ if (!paused) show(current + 1); }, 4000);
    }
  })();
  </script>
  <?php endif; ?>

  <div class="slogan-bar">
    "Bersama Membangun Sumatera Utara yang Maju, Unggul dan Berkelanjutan"
  </div>

  <div class="landing-footer">
    &copy; <?= date('Y') ?> BKAD Provinsi Sumatera Utara
    &nbsp;&middot;&nbsp; SIBERKAH SUMUT v2.0.0
    <!-- &nbsp;&middot;&nbsp; SE Gubernur No. 900.1.1.3689 / 8 Mei 2026 -->
  </div>

</div><!-- #landing -->

</body>
</html>
