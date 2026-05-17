<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-brand-telegram"></i> Pengaturan Notifikasi Telegram</div>
</div>

<div class="g2" style="align-items:start">

  <!-- Konfigurasi Bot Token -->
  <div class="card">
    <div class="card-title"><i class="ti ti-robot"></i> Konfigurasi Bot Telegram</div>

    <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
      <strong>Langkah setup:</strong><br>
      1. Buka <a href="https://t.me/BotFather" target="_blank">@BotFather</a> di Telegram → buat bot baru dengan <code>/newbot</code><br>
      2. Salin <strong>Token</strong> yang diberikan BotFather → paste di bawah<br>
      3. Setiap Admin Provinsi kirim <code>/start</code> ke bot → dapatkan Chat ID mereka<br>
      4. Isi Chat ID di halaman <a href="<?= site_url('admin/users') ?>">Edit User</a> masing-masing admin<br>
      5. Klik <strong>Test</strong> untuk verifikasi koneksi
    </div>

    <?= form_open(site_url('admin/telegram/simpan-token')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group">
      <label class="form-label">Bot Token <span class="text-danger">*</span></label>
      <input type="text" name="telegram_bot_token" class="form-control fc"
             value="<?= htmlspecialchars($setting->nilai ?? '') ?>"
             placeholder="1234567890:ABCdefGHIjklMNOpqrSTUvwxYZ">
      <small class="text-muted">Didapat dari @BotFather saat membuat bot.</small>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="ti ti-device-floppy"></i> Simpan Token
    </button>
    <?= form_close() ?>

    <?php if (!empty($setting->nilai)): ?>
    <div class="alert alert-success" style="margin-top:14px;font-size:12px">
      <i class="ti ti-circle-check"></i> Bot Token sudah terkonfigurasi.
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-top:14px;font-size:12px">
      <i class="ti ti-alert-triangle"></i> Bot Token belum diisi. Notifikasi Telegram tidak akan terkirim.
    </div>
    <?php endif; ?>
  </div>

  <!-- Status per Admin -->
  <div class="card">
    <div class="card-title"><i class="ti ti-users"></i> Status Notifikasi Admin Provinsi</div>
    <table class="tbl">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Role</th>
          <th>Status</th>
          <th style="width:80px">Test</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($admins as $admin): ?>
      <tr>
        <td>
          <div style="font-weight:500"><?= htmlspecialchars($admin->nama) ?></div>
          <small class="text-muted"><?= htmlspecialchars($admin->username) ?></small>
        </td>
        <td><?= badge_role($admin->role_kode) ?></td>
        <td>
          <?php if ($admin->telegram_chat_id): ?>
            <span class="badge badge-hijau"><i class="ti ti-check" style="font-size:10px"></i> Aktif</span>
            <div style="font-size:11px;color:var(--abu);margin-top:2px">
              Chat ID: <code><?= htmlspecialchars($admin->telegram_chat_id) ?></code>
            </div>
          <?php else: ?>
            <span class="badge badge-abu">Belum diset</span>
            <div style="font-size:11px;color:var(--abu);margin-top:2px">
              <a href="<?= site_url('admin/users/edit/'.$admin->id) ?>">Set Chat ID</a>
            </div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($admin->telegram_chat_id && !empty($setting->nilai)): ?>
          <a href="<?= site_url('admin/telegram/test/'.$admin->id) ?>"
             class="btn btn-outline btn-sm btn-icon"
             title="Kirim pesan test"
             onclick="return confirm('Kirim pesan test Telegram ke <?= htmlspecialchars($admin->nama) ?>?')">
            <i class="ti ti-send"></i>
          </a>
          <?php else: ?>
          <span class="text-muted" style="font-size:12px">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($admins)): ?>
      <tr><td colspan="4" style="text-align:center;color:var(--abu);padding:20px">Tidak ada Admin Provinsi aktif</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <div style="margin-top:14px;padding:12px;background:var(--biru-light);border-radius:8px;font-size:12px">
      <strong><i class="ti ti-bell"></i> Kapan notifikasi dikirim?</strong><br>
      Setiap kali SKPKD Kab/Kota menyetujui verifikasi dan meneruskan permohonan ke Provinsi.
      Notifikasi berisi: Kab/Kota, jenis pengajuan, kode BKP, kegiatan, nilai, total pending, dan waktu pengajuan.
    </div>
  </div>

</div>
