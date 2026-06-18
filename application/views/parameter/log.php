<div class="page-header">
  <div class="page-title"><i class="ti ti-history"></i> Log Perubahan Parameter</div>
  <select class="fc fc-sm" onchange="location='<?= site_url('parameter/log?tahun=') ?>'+this.value">
    <?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun)?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?>
  </select>
</div>
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-database"></i> Log Perubahan Data BKP</div>
  <table class="tbl"><thead><tr><th>Waktu</th><th>Kode BKP</th><th>Tahun</th><th>Field</th><th>Nilai Lama</th><th>Nilai Baru</th><th>Oleh</th></tr></thead>
  <tbody>
  <?php if (!empty($log_bkp)): foreach ($log_bkp as $l): ?>
  <tr><td class="text-xs"><?= tgl_short($l->created_at) ?></td><td class="mono"><?= $l->kode_bkp ?></td><td><?= $l->tahun ?></td><td class="mono text-sm"><?= $l->field_ubah ?></td>
  <td class="text-sm" style="text-decoration:line-through;color:var(--text-muted)"><?= $l->field_ubah==='nilai'?rupiah($l->nilai_lama):htmlspecialchars($l->nilai_lama) ?></td>
  <td class="fw-500 text-sm"><?= $l->field_ubah==='nilai'?rupiah($l->nilai_baru):htmlspecialchars($l->nilai_baru) ?></td>
  <td class="text-sm"><?= htmlspecialchars($l->nama_user??'-') ?></td></tr>
  <?php endforeach; else: ?><tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted)">Belum ada log.</td></tr><?php endif; ?>
  </tbody></table>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => ['tahun' => $tahun]]); ?>
</div>
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-calendar-time"></i> Log Perubahan Batas Waktu</div>
  <table class="tbl"><thead><tr><th>Waktu</th><th>Label</th><th>Field</th><th>Nilai Lama</th><th>Nilai Baru</th><th>Oleh</th></tr></thead>
  <tbody>
  <?php if (!empty($log_bw)): foreach ($log_bw as $l): ?>
  <tr><td class="text-xs"><?= tgl_short($l->created_at) ?></td><td class="text-sm"><?= htmlspecialchars($l->label??'-') ?></td><td class="mono text-sm"><?= $l->field_ubah ?></td>
  <td style="text-decoration:line-through;color:var(--text-muted)"><?= tgl_indo($l->nilai_lama) ?></td><td class="fw-500"><?= tgl_indo($l->nilai_baru) ?></td>
  <td class="text-sm"><?= htmlspecialchars($l->nama_user??'-') ?></td></tr>
  <?php endforeach; else: ?><tr><td colspan="6" style="text-align:center;padding:16px;color:var(--text-muted)">Belum ada log.</td></tr><?php endif; ?>
  </tbody></table>
</div>
