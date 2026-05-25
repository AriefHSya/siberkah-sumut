<?php
function label_kelompok_fb($jenis, $kode_tahap) {
    if ($jenis === 'bertahap') {
        return 'Bertahap — ' . ($kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    }
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}
function icon_kelompok_fb($jenis) {
    $m = ['sekaligus'=>'ti-cash','bertahap'=>'ti-calendar-stats','khusus_mendesak'=>'ti-urgent','khusus_bencana'=>'ti-alert-triangle'];
    return $m[$jenis] ?? 'ti-send';
}
?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-plus"></i> Buat Permohonan Pencairan</div>
    <div class="text-xs text-muted" style="margin-top:3px">TA <?= $tahun ?></div>
  </div>
  <a href="<?= site_url('permohonan') ?>" class="btn btn-outline btn-sm">
    <i class="ti ti-arrow-left"></i> Kembali
  </a>
</div>

<!-- ─── Step 1: Pilih Kelompok ─── -->
<div class="card mb-3">
  <div class="card-title"><i class="ti ti-list-check"></i> Pilih Kelompok Permohonan</div>

  <?php if (empty($kelompok)): ?>
  <div style="padding:24px;text-align:center;color:var(--text-muted)">
    <i class="ti ti-inbox" style="font-size:32px;margin-bottom:8px;display:block"></i>
    <div>Tidak ada pekerjaan yang siap diajukan saat ini.</div>
    <div class="text-xs mt-1">Pastikan pekerjaan sudah melalui verifikasi SKPKD dan mendapat persetujuan.</div>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-wrap:wrap;gap:12px">
    <?php foreach ($kelompok as $k):
      $is_active = ($jenis === $k->jenis_penyaluran && $kode_tahap === $k->kode_tahap);
      $label_k   = label_kelompok_fb($k->jenis_penyaluran, $k->kode_tahap);
      $icon_k    = icon_kelompok_fb($k->jenis_penyaluran);
      $url_k     = site_url('permohonan/buat?jenis='.$k->jenis_penyaluran.'&kode_tahap='.$k->kode_tahap);
    ?>
    <a href="<?= $url_k ?>" style="text-decoration:none">
      <div style="min-width:180px;padding:14px 18px;border:2px solid <?= $is_active ? 'var(--biru)' : 'var(--border)' ?>;
                  border-radius:var(--radius);background:<?= $is_active ? 'var(--biru-light)' : '#fff' ?>;
                  cursor:pointer;transition:.15s">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
          <i class="ti <?= $icon_k ?>" style="font-size:18px;color:<?= $is_active ? 'var(--biru)' : 'var(--text-muted)' ?>"></i>
          <span class="fw-500 text-sm" style="color:<?= $is_active ? 'var(--biru)' : '' ?>"><?= $label_k ?></span>
        </div>
        <div style="font-size:20px;font-weight:700;color:<?= $is_active ? 'var(--biru)' : 'var(--text)' ?>"><?= $k->jumlah ?></div>
        <div class="text-xs text-muted">pekerjaan tersedia</div>
        <div class="text-xs fw-500" style="color:var(--teal-mid);margin-top:2px"><?= rupiah($k->total_nilai ?? 0) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ─── Step 2: Form Permohonan (muncul setelah kelompok dipilih) ─── -->
<?php if ($jenis && $kode_tahap): ?>
<div id="formPermohonan">
  <?= form_open(site_url('permohonan/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <input type="hidden" name="jenis_penyaluran" value="<?= htmlspecialchars($jenis) ?>">
  <input type="hidden" name="kode_tahap" value="<?= htmlspecialchars($kode_tahap) ?>">

  <!-- Daftar Pekerjaan -->
  <div class="card mb-2">
    <div class="card-title">
      <i class="ti ti-building"></i>
      Daftar Pekerjaan — <?= label_kelompok_fb($jenis, $kode_tahap) ?>
      <span class="badge badge-biru" style="margin-left:6px"><?= count($eligible) ?> tersedia</span>
    </div>

    <?php if (empty($eligible)): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">
      <i class="ti ti-inbox"></i> Tidak ada pekerjaan eligible untuk kelompok ini.
    </div>
    <?php else: ?>
    <div style="margin-bottom:10px">
      <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
        <input type="checkbox" id="chkAll" onchange="toggleAll(this)">
        <span class="fw-500">Pilih Semua (<?= count($eligible) ?> pekerjaan)</span>
      </label>
    </div>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:32px;text-align:center">
              <i class="ti ti-check"></i>
            </th>
            <th>Kode BKP</th>
            <th>Nama Kegiatan</th>
            <th style="text-align:right">Nilai Kontrak</th>
            <th style="text-align:right">Nilai Diajukan</th>
            <th>No. Kontrak</th>
            <th>Penyedia</th>
          </tr>
        </thead>
        <tbody id="tbodyPekerjaan">
          <?php foreach ($eligible as $e): ?>
          <tr>
            <td class="center">
              <input type="checkbox" name="tahapan_ids[]"
                     value="<?= $e->tahapan_id ?>"
                     class="chk-item" onchange="updateSummary()">
            </td>
            <td>
              <div class="mono fw-500 text-sm"><?= htmlspecialchars($e->kode_bkp) ?></div>
              <div class="text-xs text-muted"><?= htmlspecialchars($e->uraian_bkp) ?></div>
            </td>
            <td class="text-sm"><?= htmlspecialchars($e->nama_kegiatan_dok ?: '—') ?></td>
            <td class="fw-500 text-sm" style="text-align:right"><?= rupiah($e->nilai_kontrak) ?></td>
            <td class="fw-500 text-sm" style="text-align:right;color:var(--biru)"><?= rupiah($e->nilai_diajukan) ?>
              <div class="text-xs text-muted"><?= $e->persen_nilai ?>%</div>
            </td>
            <td class="mono text-xs"><?= htmlspecialchars($e->no_dok_pekerjaan ?: '—') ?></td>
            <td class="text-xs"><?= htmlspecialchars($e->nama_penyedia ?: '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Summary dinamis -->
    <div id="summaryBar" style="display:none;margin-top:12px;padding:10px 14px;
         background:var(--biru-light);border-radius:var(--radius);font-size:13px">
      <span class="fw-500" style="color:var(--biru)">
        <i class="ti ti-circle-check"></i>
        <span id="jmlDipilih">0</span> pekerjaan dipilih
      </span>
      <span style="margin:0 10px;color:var(--text-muted)">|</span>
      Total Nilai: <strong id="totalDipilih" style="color:var(--biru)">Rp 0</strong>
    </div>
    <?php endif; ?>
  </div>

  <!-- Detail Permohonan -->
  <div class="card mb-3">
    <div class="card-title"><i class="ti ti-file-text"></i> Detail Permohonan</div>
    <div class="form-grid-2">
      <div class="form-group">
        <label>Nomor Permohonan <span class="req">*</span></label>
        <input type="text" name="no_permohonan" class="form-control" required
               value="<?= set_value('no_permohonan') ?>"
               placeholder="Contoh: 100/BKAD-MED/V/2026">
      </div>
      <div class="form-group">
        <label>Tanggal Permohonan <span class="req">*</span></label>
        <input type="date" name="tgl_permohonan" class="form-control" required
               value="<?= set_value('tgl_permohonan', date('Y-m-d')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Catatan / Perihal</label>
      <textarea name="catatan" class="form-control" rows="2"
                placeholder="Opsional — perihal atau catatan tambahan"><?= set_value('catatan') ?></textarea>
    </div>
  </div>

  <div class="form-actions" style="padding-bottom:20px">
    <a href="<?= site_url('permohonan') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" id="btnAjukan" class="btn btn-primary" disabled
            onclick="return confirm('Ajukan permohonan pencairan ini kepada BKAD Provinsi?\n\nPastikan semua pekerjaan yang dipilih sudah benar.')">
      <i class="ti ti-send"></i> Ajukan Permohonan
    </button>
  </div>

  <?= form_close() ?>
</div>

<script>
// Nilai setiap pekerjaan (tahapan_id → nilai_diajukan)
var nilaiMap = {
  <?php foreach ($eligible as $e): ?>
  <?= $e->tahapan_id ?>: <?= (float)$e->nilai_diajukan ?>,
  <?php endforeach; ?>
};

function toggleAll(src) {
  var chks = document.querySelectorAll('.chk-item');
  chks.forEach(function(c) { c.checked = src.checked; });
  updateSummary();
}

function updateSummary() {
  var checked = document.querySelectorAll('.chk-item:checked');
  var total = 0;
  checked.forEach(function(c) {
    total += nilaiMap[parseInt(c.value)] || 0;
  });
  var jml = checked.length;

  document.getElementById('jmlDipilih').textContent    = jml;
  document.getElementById('totalDipilih').textContent  = 'Rp ' + total.toLocaleString('id-ID');
  document.getElementById('summaryBar').style.display  = jml > 0 ? '' : 'none';
  document.getElementById('btnAjukan').disabled        = jml === 0;

  // Sinkron chkAll
  var all = document.querySelectorAll('.chk-item');
  document.getElementById('chkAll').checked = (jml > 0 && jml === all.length);
}
</script>
<?php endif; ?>
