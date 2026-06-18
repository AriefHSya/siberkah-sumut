<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="page-header">
  <div class="page-title"><i class="ti ti-photo"></i> Logo Provinsi</div>
</div>

<div class="card" style="max-width:540px">
  <div class="card-title"><i class="ti ti-upload"></i> Upload Logo Pemerintah Provinsi</div>
  <p class="text-sm text-muted mb-3">
    Logo ini akan ditampilkan di <strong>Landing Page</strong> dan <strong>Halaman Login</strong>
    bersama logo SIBERKAH. Format: JPG, PNG, SVG, WebP. Maks. 2 MB.
  </p>

  <!-- Preview logo saat ini -->
  <?php if (!empty($logo_path)): ?>
  <div class="mb-3" style="padding:16px;background:var(--bg);border-radius:var(--radius);text-align:center">
    <div class="text-xs text-muted mb-2">Logo saat ini:</div>
    <img src="<?= base_url($logo_path) ?>" alt="Logo Provinsi"
         style="max-height:100px;max-width:200px;object-fit:contain">
    <div class="mt-2">
      <a href="<?= site_url('parameter/logo/hapus') ?>"
         class="btn btn-xs" style="background:var(--merah-light);color:var(--merah-mid);border:1px solid #F7C1C1"
         onclick="return confirm('Hapus logo provinsi?')">
        <i class="ti ti-trash"></i> Hapus Logo
      </a>
    </div>
  </div>
  <?php else: ?>
  <div class="mb-3 text-sm text-muted" style="padding:16px;background:var(--bg);border-radius:var(--radius);text-align:center">
    <i class="ti ti-photo-off" style="font-size:32px;display:block;opacity:0.3;margin-bottom:6px"></i>
    Belum ada logo yang diupload.
  </div>
  <?php endif; ?>

  <!-- Form upload -->
  <?= form_open_multipart(site_url('parameter/logo/upload')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="form-group mb-3">
    <label>Pilih File Logo <span class="req">*</span></label>
    <input type="file" name="file_logo" class="form-control"
           accept=".jpg,.jpeg,.png,.webp" required>
    <div class="form-hint">Format: JPG, PNG, WebP &nbsp;·&nbsp; Maks. 2 MB &nbsp;·&nbsp; Rekomendasi: transparan background (PNG)</div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="ti ti-upload"></i> <?= $logo_path ? 'Ganti Logo' : 'Upload Logo' ?>
    </button>
  </div>
  <?= form_close() ?>
</div>
