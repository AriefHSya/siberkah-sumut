<?php defined('BASEPATH') OR exit('No direct script access allowed');
$duplikat = array_filter($preview, fn($r) => $r['is_duplikat']);
$baru     = array_filter($preview, fn($r) => !$r['is_duplikat']);
?>
<div class="page-header">
  <div class="page-title"><i class="ti ti-eye"></i> Preview Import BKP TA <?= $tahun ?></div>
  <a href="<?= site_url('parameter/bkp/import') ?>" class="btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Upload Ulang</a>
</div>

<?php if (count($duplikat) > 0): ?>
<div class="alert alert-warning">
  <i class="ti ti-alert-triangle"></i>
  <strong><?= count($duplikat) ?> data duplikat</strong> ditemukan — Kode BKP sudah ada di database TA <?= $tahun ?>. Pilih tindakan sebelum proses.
</div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Total baris</div><div class="stat-val"><?= count($preview) ?></div></div>
  <div class="stat-card"><div class="stat-label">Data baru</div><div class="stat-val" style="color:var(--hijau)"><?= count($baru) ?></div></div>
  <div class="stat-card"><div class="stat-label">Duplikat</div><div class="stat-val" style="color:var(--kuning)"><?= count($duplikat) ?></div></div>
</div>

<?= form_open(site_url('parameter/bkp/proses-import')) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

<?php if (count($duplikat) > 0): ?>
<div class="card mb-3">
  <div class="card-title"><i class="ti ti-settings"></i> Tindakan untuk Data Duplikat</div>
  <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;margin-bottom:10px">
    <input type="radio" name="aksi_duplikat" value="skip" checked style="margin-top:2px">
    <div><div class="fw-500">Lewati (Skip)</div><div class="text-muted text-xs">Data lama dipertahankan, data baru diabaikan</div></div>
  </label>
  <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer">
    <input type="radio" name="aksi_duplikat" value="update" style="margin-top:2px">
    <div><div class="fw-500">Perbarui</div><div class="text-muted text-xs">Timpa dengan data baru — perubahan tercatat di log</div></div>
  </label>
</div>
<?php else: ?>
<input type="hidden" name="aksi_duplikat" value="skip">
<?php endif; ?>

<div class="card" style="padding:0;overflow:hidden">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th style="width:24px">#</th><th style="width:120px">Kode BKP</th><th>Uraian</th><th style="width:130px;text-align:right">Nilai</th><th style="width:80px;text-align:center">Status</th></tr></thead>
      <tbody>
        <?php foreach ($preview as $i => $row): ?>
        <tr <?= $row['is_duplikat'] ? 'style="background:#FFFBF0"' : '' ?>>
          <td class="text-muted"><?= $i+1 ?></td>
          <td class="text-mono"><?= htmlspecialchars($row['kode_bkp']) ?></td>
          <td class="text-sm"><?= htmlspecialchars($row['uraian_bkp']) ?></td>
          <td class="text-right"><?= rupiah($row['nilai']) ?></td>
          <td class="text-center">
            <?= $row['is_duplikat']
              ? '<span class="badge badge-kuning">Duplikat</span>'
              : '<span class="badge badge-hijau">Baru</span>' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="form-actions" style="margin-top:0;border:none">
  <a href="<?= site_url('parameter/bkp/import') ?>" class="btn-outline">Batal</a>
  <button type="submit" class="btn-primary"><i class="ti ti-database-import"></i> Proses Import (<?= count($preview) ?> baris)</button>
</div>
<?= form_close() ?>
