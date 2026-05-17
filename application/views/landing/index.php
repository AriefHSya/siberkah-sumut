<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIBERKAH SUMUT — Platform Kolaborasi Bantuan Keuangan Provinsi dan Kab/Kota</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
  min-height: 100vh;
}

/* ── Layout ───────────────────────────────────── */
#landing {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(150deg, #0D2A5E 0%, #1A5EA8 45%, #1D7A45 100%);
  align-items: center;
  position: relative;
  overflow: hidden;
}

/* Latar grid transparan */
.grid-bg {
  position: absolute; inset: 0; opacity: 0.04;
  background-image:
    repeating-linear-gradient(0deg,  #fff 0, #fff 1px, transparent 1px, transparent 44px),
    repeating-linear-gradient(90deg, #fff 0, #fff 1px, transparent 1px, transparent 44px);
  pointer-events: none;
}

/* Gelombang bawah */
.wave-bg {
  position: absolute; bottom: 0; left: 0; right: 0; height: 180px;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 180'%3E%3Cpath fill='rgba(255,255,255,0.05)' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L0,320Z'/%3E%3C/svg%3E") no-repeat bottom/cover;
  pointer-events: none;
}

/* ── Hero ─────────────────────────────────────── */
.hero-content {
  position: relative; z-index: 1;
  text-align: center; color: #fff;
  padding: 56px 24px 80px;
  max-width: 720px; width: 100%;
  flex: 1;
  display: flex; flex-direction: column; align-items: center;
}

/* Logo */
.logo-mark {
  display: flex; align-items: center; justify-content: center;
  gap: 22px; margin-bottom: 24px;
}
.logo-text-wrap { text-align: left; }
.logo-name {
  font-size: 52px; font-weight: 900; letter-spacing: 5px;
  background: linear-gradient(180deg, #fff 40%, #A8D8EA 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text; line-height: 1;
}
.logo-divider {
  display: flex; align-items: center; gap: 10px; margin: 5px 0;
}
.logo-divider::before, .logo-divider::after {
  content: ''; flex: 1; height: 2px; background: rgba(255,255,255,0.35);
}
.logo-divider span {
  font-size: 11px; color: rgba(255,255,255,0.7);
  letter-spacing: 1px; white-space: nowrap;
}
.logo-sub {
  font-size: 12px; letter-spacing: 2px;
  color: rgba(255,255,255,0.7); font-weight: 400; margin-top: 4px;
}

/* Tagline & deskripsi */
.hero-tagline {
  font-size: 17px; font-weight: 600;
  color: rgba(255,255,255,0.95);
  margin: 20px 0 12px; letter-spacing: 0.3px;
}
.hero-sub {
  font-size: 13px; color: rgba(255,255,255,0.72);
  line-height: 1.85; margin-bottom: 36px;
  border-left: 3px solid rgba(255,255,255,0.3);
  padding-left: 14px; text-align: left;
  max-width: 540px;
}

/* Tombol masuk */
.btn-masuk {
  display: inline-flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, #F5A623, #E8861A);
  color: #fff; border: none; padding: 15px 52px;
  font-size: 16px; font-weight: 700; border-radius: 8px;
  cursor: pointer; letter-spacing: 0.5px;
  box-shadow: 0 4px 24px rgba(245,166,35,0.45);
  text-decoration: none;
  transition: opacity 0.2s, transform 0.15s;
}
.btn-masuk:hover {
  opacity: 0.9; transform: translateY(-2px); text-decoration: none; color: #fff;
}
.btn-masuk i { font-size: 20px; }

/* Kartu 4 pilar */
.pilar-row {
  display: flex; gap: 12px; justify-content: center;
  flex-wrap: wrap; margin-top: 40px; width: 100%;
}
.pilar-card {
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.18);
  border-radius: 12px; padding: 16px 14px; text-align: center;
  min-width: 130px; flex: 1; max-width: 165px;
  transition: background 0.2s;
}
.pilar-card:hover { background: rgba(255,255,255,0.16); }
.pilar-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 10px; font-size: 21px;
}
.pilar-card h4 {
  font-size: 11px; font-weight: 700; color: #fff;
  letter-spacing: 0.8px; margin-bottom: 5px;
}
.pilar-card p {
  font-size: 11px; color: rgba(255,255,255,0.62); line-height: 1.5;
}

/* Slogan & footer */
.slogan-bar {
  position: relative; z-index: 1; width: 100%;
  background: rgba(255,255,255,0.08);
  border-top: 1px solid rgba(255,255,255,0.12);
  padding: 14px 20px; text-align: center;
  font-size: 13px; font-style: italic; color: rgba(255,255,255,0.82);
}
.landing-footer {
  width: 100%;
  background: rgba(0,0,0,0.28);
  padding: 11px 20px; text-align: center;
  font-size: 11px; color: rgba(255,255,255,0.5);
  position: relative; z-index: 1;
}

/* ── Box foto pejabat ────────────────────── */
.foto-box {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 2;
  width: 160px;
}
.foto-box-kiri  { left: 28px; }
.foto-box-kanan { right: 28px; }

.foto-card {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 10px;
  overflow: hidden;
  text-align: center;
  backdrop-filter: blur(4px);
}
.foto-card img {
  width: 100%;
  height: 130px;
  object-fit: cover;
  display: block;
}
.foto-card-info {
  padding: 8px 6px;
}
.foto-card-nama {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  line-height: 1.3;
  margin-bottom: 2px;
}
.foto-card-jabatan {
  font-size: 10px;
  color: rgba(255,255,255,0.65);
  line-height: 1.3;
}

/* ── Slideshow bawah ─────────────────────── */
.slideshow-wrap {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 900px;
  margin: 0 auto 0;
  padding: 0 20px 20px;
}
.slideshow-box {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(0,0,0,0.3);
  height: 220px;
}
.slide {
  position: absolute; inset: 0;
  opacity: 0;
  transition: opacity 0.6s ease;
}
.slide.active { opacity: 1; }
.slide img {
  width: 100%; height: 100%;
  object-fit: cover;
}
.slide-caption {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: linear-gradient(transparent, rgba(0,0,0,0.65));
  padding: 20px 14px 10px;
  font-size: 12px; color: rgba(255,255,255,0.9);
}
.slide-judul {
  font-weight: 600; font-size: 13px;
  margin-bottom: 2px;
}
.slide-btn {
  position: absolute;
  top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,0.4);
  border: none; color: #fff;
  width: 32px; height: 32px;
  border-radius: 50%;
  cursor: pointer; font-size: 16px;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
  z-index: 3;
}
.slide-btn:hover { background: rgba(0,0,0,0.65); }
.slide-btn-prev { left: 10px; }
.slide-btn-next { right: 10px; }
.slide-dots {
  position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 5px; z-index: 3;
}
.slide-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: rgba(255,255,255,0.4); cursor: pointer;
  border: none; padding: 0;
  transition: background 0.2s;
}
.slide-dot.active { background: #fff; }

/* Responsive */
@media (max-width: 900px) {
  .foto-box { display: none; }
  .slideshow-box { height: 160px; }
}
@media (max-width: 540px) {
  .logo-name    { font-size: 36px; letter-spacing: 3px; }
  .logo-mark    { gap: 14px; }
  .logo-mark img { width: 80px; height: 80px; }
  .btn-masuk    { padding: 13px 36px; font-size: 15px; }
  .pilar-card   { min-width: 110px; }
  .hero-content { padding: 40px 16px 60px; }
  .hero-tagline { font-size: 15px; }
}
</style>
</head>
<body>

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
      <img src="<?= base_url('assets/img/logo-siberkah.png') ?>" alt="Logo SIBERKAH SUMUT"
           style="width:110px;height:110px;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.3))">

      <div class="logo-text-wrap">
        <div class="logo-name">SIBERKAH</div>
        <div class="logo-divider"><span>— SUMUT —</span></div>
        <div class="logo-sub">SUMATERA UTARA</div>
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
        <div class="pilar-icon" style="background:rgba(26,94,168,0.25)">
          <i class="ti ti-users" style="color:#A8D4F5"></i>
        </div>
        <h4>KOLABORASI</h4>
        <p>Bersinergi Membangun Sumatera Utara</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon" style="background:rgba(29,122,69,0.25)">
          <i class="ti ti-shield-check" style="color:#6ED49A"></i>
        </div>
        <h4>TRANSPARANSI</h4>
        <p>Terbuka dan Akuntabel</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon" style="background:rgba(245,166,35,0.25)">
          <i class="ti ti-chart-bar" style="color:#F5C469"></i>
        </div>
        <h4>EFEKTIF</h4>
        <p>Tepat Sasaran, Berdampak Nyata</p>
      </div>
      <div class="pilar-card">
        <div class="pilar-icon" style="background:rgba(204,45,45,0.25)">
          <i class="ti ti-refresh" style="color:#F08080"></i>
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
    &nbsp;&middot;&nbsp; SIBERKAH SUMUT v4.1.0
    &nbsp;&middot;&nbsp; SE Gubernur No. 900.1.1.3689 / 8 Mei 2026
  </div>

</div><!-- #landing -->

</body>
</html>
