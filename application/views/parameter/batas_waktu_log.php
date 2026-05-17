<div class="page-header">
  <div class="page-title"><i class="ti ti-history"></i> Log Perubahan Batas Waktu</div>
  <a href="<?= site_url('parameter/batas-waktu') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>
<div class="card">
  <table class="tbl">
    <thead><tr><th>Waktu</th><th>Tahun</th><th>Jenis / Label</th><th>Field</th><th>Nilai Lama</th><th>Nilai Baru</th><th>Oleh</th></tr></thead>
    <tbody>
    <?php if (!empty($log_list)): foreach ($log_list as $l): ?>
    <tr>
      <td class="text-xs"><?= tgl_indo($l->created_at) ?></td>
      <td><?= $l->tahun ?></td>
      <td><?= badge_jenis($l->jenis_penyaluran) ?> <span class="text-sm"><?= htmlspecialchars($l->label??'-') ?></span></td>
      <td class="mono text-sm"><?= $l->field_ubah ?></td>
      <td class="text-sm" style="text-decoration:line-through;color:var(--text-muted)"><?= tgl_indo($l->nilai_lama) ?></td>
      <td class="fw-500 text-sm"><?= tgl_indo($l->nilai_baru) ?></td>
      <td class="text-sm"><?= htmlspecialchars($l->nama_user??'-') ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)">Belum ada log perubahan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
