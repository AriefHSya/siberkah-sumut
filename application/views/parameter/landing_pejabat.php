<?php defined('BASEPATH') OR exit('No direct script access allowed');
$can_manage = $this->rbac->can('parameter.landing.manage');

$jenis_label = [
    'gubernur'      => 'Gubernur',
    'wakil_gubernur'=> 'Wakil Gubernur',
    'sekda'         => 'Sekretaris Daerah',
    'kepala_bkad'   => 'Kepala BKAD Provinsi',
];
?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-photo"></i> Tampilan Landing — Foto Pejabat</div>
</div>

<!-- Sub-tab: Pejabat | Slideshow -->
<div class="sub-nav" style="margin-bottom:16px">
  <a href="<?= site_url('parameter/landing') ?>" class="sub-nav-item on">
    <i class="ti ti-user-circle"></i> Foto Pejabat
  </a>
  <a href="<?= site_url('parameter/landing/slideshow') ?>" class="sub-nav-item">
    <i class="ti ti-slideshow"></i> Slideshow Kinerja
  </a>
</div>

<div class="alert alert-info" style="margin-bottom:16px">
  <i class="ti ti-info-circle"></i>
  Foto pejabat akan ditampilkan di sisi kiri dan kanan landing page.
  Format: JPG/PNG, maks 2 MB. Rasio foto potret (3:4) direkomendasikan.
</div>

<div class="g2">

  <!-- ── KIRI: Gubernur + Wakil Gubernur ── -->
  <div class="card">
    <div class="card-title"><i class="ti ti-user-star"></i> Box Kiri — Pimpinan Daerah</div>

    <?php foreach (['gubernur','wakil_gubernur'] as $jenis):
      $p = $pejabat[$jenis] ?? NULL;
    ?>
    <div style="border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
      <div style="display:flex;gap:14px;align-items:flex-start">

        <!-- Preview foto -->
        <div style="flex-shrink:0;width:90px;height:110px;border-radius:6px;overflow:hidden;
                    background:var(--bg);border:1px solid var(--border);display:flex;
                    align-items:center;justify-content:center">
          <?php if ($p && $p->foto_path && file_exists(FCPATH . $p->foto_path)): ?>
            <img src="<?= base_url($p->foto_path) ?>?v=<?= filemtime(FCPATH.$p->foto_path) ?>"
                 alt="<?= htmlspecialchars($jenis_label[$jenis]) ?>"
                 style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <div style="text-align:center;color:var(--abu)">
              <i class="ti ti-user" style="font-size:28px"></i>
              <div style="font-size:10px;margin-top:4px">Belum ada foto</div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Info + Form -->
        <div style="flex:1">
          <div style="font-weight:600;font-size:13px;margin-bottom:10px;color:var(--biru)">
            <?= htmlspecialchars($jenis_label[$jenis]) ?>
          </div>

          <?php if ($can_manage): ?>
          <?= form_open_multipart(site_url('parameter/landing/pejabat/simpan/' . $jenis)) ?>
          <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

          <div class="form-group" style="margin-bottom:8px">
            <label class="form-label" style="font-size:11px">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control fc fc-sm"
                   placeholder="Nama pejabat..."
                   value="<?= htmlspecialchars($p->nama ?? '') ?>">
          </div>
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:11px">Jabatan</label>
            <input type="text" name="jabatan" class="form-control fc fc-sm"
                   value="<?= htmlspecialchars($p->jabatan ?? $jenis_label[$jenis]) ?>">
          </div>
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:11px">Upload Foto Baru (opsional)</label>
            <input type="file" name="foto" accept="image/jpeg,image/png"
                   class="form-control" style="padding:4px;font-size:12px">
          </div>
          <div style="display:flex;gap:6px">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ti ti-device-floppy"></i> Simpan
            </button>
            <?php if ($p && $p->foto_path): ?>
            <a href="<?= site_url('parameter/landing/pejabat/hapus/'.$jenis) ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Hapus foto <?= htmlspecialchars($jenis_label[$jenis]) ?>?')">
              <i class="ti ti-trash"></i> Hapus Foto
            </a>
            <?php endif; ?>
          </div>
          <?= form_close() ?>
          <?php else: ?>
          <div style="font-size:13px">
            <div><strong><?= htmlspecialchars($p->nama ?? '—') ?></strong></div>
            <div class="text-muted" style="font-size:12px"><?= htmlspecialchars($p->jabatan ?? '') ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── KANAN: Sekda + Kepala BKAD ── -->
  <div class="card">
    <div class="card-title"><i class="ti ti-building-community"></i> Box Kanan — Pejabat Provinsi</div>

    <?php foreach (['sekda','kepala_bkad'] as $jenis):
      $p = $pejabat[$jenis] ?? NULL;
    ?>
    <div style="border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
      <div style="display:flex;gap:14px;align-items:flex-start">

        <!-- Preview foto -->
        <div style="flex-shrink:0;width:90px;height:110px;border-radius:6px;overflow:hidden;
                    background:var(--bg);border:1px solid var(--border);display:flex;
                    align-items:center;justify-content:center">
          <?php if ($p && $p->foto_path && file_exists(FCPATH . $p->foto_path)): ?>
            <img src="<?= base_url($p->foto_path) ?>?v=<?= filemtime(FCPATH.$p->foto_path) ?>"
                 alt="<?= htmlspecialchars($jenis_label[$jenis]) ?>"
                 style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <div style="text-align:center;color:var(--abu)">
              <i class="ti ti-user" style="font-size:28px"></i>
              <div style="font-size:10px;margin-top:4px">Belum ada foto</div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Info + Form -->
        <div style="flex:1">
          <div style="font-weight:600;font-size:13px;margin-bottom:10px;color:var(--biru)">
            <?= htmlspecialchars($jenis_label[$jenis]) ?>
          </div>

          <?php if ($can_manage): ?>
          <?= form_open_multipart(site_url('parameter/landing/pejabat/simpan/' . $jenis)) ?>
          <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

          <div class="form-group" style="margin-bottom:8px">
            <label class="form-label" style="font-size:11px">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control fc fc-sm"
                   placeholder="Nama pejabat..."
                   value="<?= htmlspecialchars($p->nama ?? '') ?>">
          </div>
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:11px">Jabatan</label>
            <input type="text" name="jabatan" class="form-control fc fc-sm"
                   value="<?= htmlspecialchars($p->jabatan ?? $jenis_label[$jenis]) ?>">
          </div>
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:11px">Upload Foto Baru (opsional)</label>
            <input type="file" name="foto" accept="image/jpeg,image/png"
                   class="form-control" style="padding:4px;font-size:12px">
          </div>
          <div style="display:flex;gap:6px">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ti ti-device-floppy"></i> Simpan
            </button>
            <?php if ($p && $p->foto_path): ?>
            <a href="<?= site_url('parameter/landing/pejabat/hapus/'.$jenis) ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Hapus foto <?= htmlspecialchars($jenis_label[$jenis]) ?>?')">
              <i class="ti ti-trash"></i> Hapus Foto
            </a>
            <?php endif; ?>
          </div>
          <?= form_close() ?>
          <?php else: ?>
          <div style="font-size:13px">
            <div><strong><?= htmlspecialchars($p->nama ?? '—') ?></strong></div>
            <div class="text-muted" style="font-size:12px"><?= htmlspecialchars($p->jabatan ?? '') ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div><!-- .g2 -->
