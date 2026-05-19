<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-file-import"></i> Import Data BKP</div>
  <a href="<?= site_url('parameter/bkp') ?>" class="btn btn-outline btn-sm">
    <i class="ti ti-arrow-left"></i> Kembali ke Daftar BKP
  </a>
</div>

<?php if (!isset($preview)): ?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- PANEL 1 — FORM UPLOAD                                      -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="g2" style="align-items:start">

  <div class="card">
    <div class="card-title"><i class="ti ti-upload"></i> Upload File</div>

    <?= form_open_multipart(site_url('parameter/bkp/import/preview')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group">
      <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
      <select name="tahun_import" class="form-control fc" required>
        <option value="">— Pilih Tahun —</option>
        <?php foreach ($tahun_list as $t): ?>
        <option value="<?= $t->tahun ?>" <?= $t->tahun == $tahun_anggaran ? 'selected' : '' ?>>
          <?= $t->tahun ?><?= $t->is_aktif ? ' (Aktif)' : '' ?>
        </option>
        <?php endforeach; ?>
      </select>
      <small class="text-muted">Digunakan jika kolom Tahun di file dikosongkan.</small>
    </div>

    <div class="form-group">
      <label class="form-label">File Excel atau CSV <span class="text-danger">*</span></label>
      <div id="drop-zone" class="drop-zone" onclick="document.getElementById('file_import').click()">
        <i class="ti ti-file-spreadsheet" style="font-size:36px;color:var(--biru)"></i>
        <p style="margin:8px 0 4px;font-weight:600">Klik atau seret file ke sini</p>
        <small class="text-muted">Format: .xlsx atau .csv &nbsp;&bull;&nbsp; Maks 5 MB</small>
        <p id="file-label" style="margin-top:8px;font-size:13px;color:var(--abu)"></p>
      </div>
      <input type="file" id="file_import" name="file_import" accept=".xlsx,.csv"
             style="display:none" onchange="onFileSelect(this)">
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%">
      <i class="ti ti-eye"></i> Preview & Validasi
    </button>
    <?= form_close() ?>
  </div>

  <div>
    <div class="card">
      <div class="card-title"><i class="ti ti-table"></i> Format Kolom File</div>
      <table class="tbl" style="font-size:13px">
        <thead><tr><th>Kolom</th><th>Nama Kolom</th><th>Contoh</th></tr></thead>
        <tbody>
          <tr><td><strong>A</strong></td><td>No Urut</td><td>1, 2, 3 …</td></tr>
          <tr><td><strong>B</strong></td><td>Tahun</td><td>2026</td></tr>
          <tr><td><strong>C</strong></td><td>Kab/Kota</td><td>Kota Medan, Kab. Deli Serdang</td></tr>
          <tr><td><strong>D</strong></td><td>Bidang Pekerjaan</td><td>Infrastruktur, Kesehatan</td></tr>
          <tr><td><strong>E</strong></td><td>Uraian</td><td>Pembangunan Jalan A</td></tr>
          <tr><td><strong>F</strong></td><td>Nilai</td><td>1000000000</td></tr>
        </tbody>
      </table>
      <ul style="font-size:12px;margin:12px 0 0;padding-left:18px;color:var(--abu);line-height:2">
        <li>Baris pertama adalah <strong>header</strong> — otomatis dilewati</li>
        <li>Kode BKP akan <strong>digenerate otomatis</strong> oleh sistem</li>
        <li>Nama Kab/Kota boleh disingkat, sistem akan mencocokkan otomatis</li>
        <li>Nama Bidang boleh menggunakan nama umum (mis: <em>Pangan → Pertanian</em>)</li>
        <li>Nilai cukup angka tanpa titik atau Rp (mis: <code>1000000000</code>)</li>
        <li>Duplikat dideteksi berdasarkan Tahun + Kab/Kota + Uraian yang sama</li>
      </ul>
      <div style="margin-top:14px">
        <a href="<?= site_url('parameter/bkp/import/template') ?>" class="btn btn-outline btn-sm">
          <i class="ti ti-download"></i> Download Template CSV
        </a>
      </div>
    </div>

    <div class="card" style="margin-top:0">
      <div class="card-title" style="cursor:pointer" onclick="toggleRef()">
        <i class="ti ti-list"></i> Bidang yang Didukung
        <i class="ti ti-chevron-down" id="ref-chevron" style="float:right"></i>
      </div>
      <div id="ref-box" style="display:none">
        <table class="tbl" style="font-size:12px">
          <thead><tr><th>Nama Bidang</th><th>Alias yang Diterima</th></tr></thead>
          <tbody>
            <tr><td>Infrastruktur</td><td>Jalan, Jembatan, Gedung, Irigasi</td></tr>
            <tr><td>Pertanian</td><td>Pangan, Ketahanan Pangan, Perkebunan, Peternakan</td></tr>
            <tr><td>Ekonomi & UMKM</td><td>UMKM, Koperasi</td></tr>
            <tr><td>Pendidikan</td><td>—</td></tr>
            <tr><td>Kesehatan</td><td>—</td></tr>
            <tr><td>Sanitasi & Air Bersih</td><td>Sanitasi, Air Minum, Air Bersih</td></tr>
            <tr><td>Pariwisata</td><td>—</td></tr>
            <tr><td>Perikanan & Kelautan</td><td>Perikanan, Kelautan</td></tr>
            <tr><td>Lingkungan Hidup</td><td>Lingkungan, Persampahan</td></tr>
            <tr><td>Teknologi Informasi</td><td>IT, Digital, Teknologi</td></tr>
            <tr><td>Sosial</td><td>Bansos, Sosial Kemasyarakatan</td></tr>
            <tr><td>Lainnya</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- .g2 -->

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- PANEL 2 — PREVIEW HASIL PARSING                            -->
<!-- ═══════════════════════════════════════════════════════════ -->

<div class="card" style="padding:16px 20px;margin-bottom:12px">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <div style="display:flex;gap:12px;flex:1;flex-wrap:wrap">
      <div class="stat-card" style="min-width:120px;background:var(--biru-light)">
        <div class="stat-val" style="color:var(--biru)"><?= $total_baru ?></div>
        <div class="stat-label">Data Baru</div>
      </div>
      <div class="stat-card" style="min-width:120px;background:var(--kuning-light)">
        <div class="stat-val" style="color:var(--kuning-mid)"><?= $total_duplikat ?></div>
        <div class="stat-label">Duplikat</div>
      </div>
      <div class="stat-card" style="min-width:120px;background:var(--merah-light)">
        <div class="stat-val" style="color:var(--merah-mid)"><?= $total_error ?></div>
        <div class="stat-label">Error</div>
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <a href="<?= site_url('parameter/bkp/import') ?>" class="btn btn-outline btn-sm">
        <i class="ti ti-refresh"></i> Upload Ulang
      </a>
      <?php if ($total_baru + $total_duplikat > 0): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="submitImport()">
        <i class="ti ti-database-import"></i> Proses Import
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($total_duplikat > 0): ?>
<div class="alert alert-warning" style="margin-bottom:12px">
  <strong><?= $total_duplikat ?></strong> data ditemukan sudah ada di database (uraian & kab/kota sama).
  Pilih <strong>Update</strong> untuk menimpa, atau <strong>Skip</strong> untuk lewati.
  &nbsp;
  <button class="btn btn-xs btn-outline" onclick="setAllDuplikat('update')">Semua Update</button>
  <button class="btn btn-xs btn-outline" style="margin-left:4px" onclick="setAllDuplikat('skip')">Semua Skip</button>
</div>
<?php endif; ?>

<?= form_open(site_url('parameter/bkp/import/proses'), ['id'=>'form-proses']) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
<input type="hidden" id="rows-data-input" name="rows_data">

<div class="card" style="padding:0;overflow:hidden">
<div style="overflow-x:auto">
<table class="tbl" id="tbl-preview">
  <thead>
    <tr>
      <th style="width:36px">#</th>
      <th>Kode BKP<br><small style="font-weight:400;color:var(--abu)">(auto)</small></th>
      <th>Tahun</th>
      <th>Kab/Kota</th>
      <th>Bidang</th>
      <th>Uraian Kegiatan</th>
      <th class="text-right">Nilai (Rp)</th>
      <th style="width:80px">Status</th>
      <th style="width:140px">Tindakan</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($preview as $i => $row):
    $has_err = !empty($row['errors']);
    $tr_cls  = $has_err ? 'tr-error' : ($row['status'] === 'duplikat' ? 'tr-warning' : '');
  ?>
  <tr class="<?= $tr_cls ?>">
    <td class="text-muted" style="font-size:12px"><?= $row['row'] ?></td>

    <!-- Kode BKP -->
    <td style="font-family:monospace;font-size:12px;white-space:nowrap">
      <?php if ($row['status'] === 'duplikat' && $row['existing_kode']): ?>
        <span style="color:var(--abu)"><?= htmlspecialchars($row['existing_kode']) ?></span><br>
        <small style="color:var(--kuning-mid)">sudah ada</small>
      <?php else: ?>
        <span style="color:var(--biru-dark)"><?= htmlspecialchars($row['preview_kode']) ?></span>
      <?php endif; ?>
    </td>

    <td><?= $row['tahun'] ?: '<span class="text-danger">-</span>' ?></td>

    <!-- Kab/Kota -->
    <td>
      <?= htmlspecialchars($row['kabkota_nama']) ?>
      <?php if ($row['kabkota_input'] !== $row['kabkota_mapped'] && $row['kabkota_mapped']): ?>
      <br><small style="color:var(--abu);font-size:11px">
        dari: "<?= htmlspecialchars($row['kabkota_input']) ?>"
      </small>
      <?php endif; ?>
    </td>

    <!-- Bidang -->
    <td>
      <?= htmlspecialchars($row['bidang_nama']) ?>
      <?php if ($row['bidang_warning']): ?>
      <br><small style="color:var(--kuning-mid);font-size:11px">
        <i class="ti ti-alert-circle" style="font-size:10px"></i>
        <?= htmlspecialchars($row['bidang_warning']) ?>
      </small>
      <?php endif; ?>
    </td>

    <!-- Uraian -->
    <td>
      <?= htmlspecialchars($row['uraian_bkp']) ?>
      <?php if ($has_err): ?>
      <ul style="margin:4px 0 0;padding-left:16px;font-size:11px;color:var(--merah-mid)">
        <?php foreach ($row['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </td>

    <!-- Nilai -->
    <td class="text-right" style="white-space:nowrap">
      <?= number_format($row['nilai'], 0, ',', '.') ?>
      <?php if ($row['status'] === 'duplikat' && $row['existing_nilai'] != $row['nilai']): ?>
      <br><small style="color:var(--abu);font-size:11px">
        lama: <?= number_format($row['existing_nilai'], 0, ',', '.') ?>
      </small>
      <?php endif; ?>
    </td>

    <!-- Badge status -->
    <td>
      <?php if ($has_err): ?>
        <span class="badge badge-merah">Error</span>
      <?php elseif ($row['status'] === 'baru'): ?>
        <span class="badge badge-biru">Baru</span>
      <?php else: ?>
        <span class="badge badge-kuning">Duplikat</span>
      <?php endif; ?>
    </td>

    <!-- Tombol tindakan -->
    <td>
      <?php if ($has_err): ?>
        <span style="font-size:12px;color:var(--abu)">Dilewati</span>
      <?php elseif ($row['status'] === 'baru'): ?>
        <span style="font-size:12px;color:var(--hijau-mid)">
          <i class="ti ti-circle-check"></i> Import
        </span>
      <?php else: ?>
        <div class="aksi-row" style="gap:4px">
          <button type="button" id="btn-u-<?= $i ?>"
            class="btn btn-xs btn-success"
            onclick="setAksi(<?= $i ?>,'update')"
            style="<?= $row['aksi']==='update' ? '' : 'opacity:.4' ?>">Update</button>
          <button type="button" id="btn-s-<?= $i ?>"
            class="btn btn-xs btn-outline"
            onclick="setAksi(<?= $i ?>,'skip')"
            style="<?= $row['aksi']==='skip' ? '' : 'opacity:.4' ?>">Skip</button>
        </div>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div><!-- .card -->
<?= form_close() ?>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
  <a href="<?= site_url('parameter/bkp/import') ?>" class="btn btn-outline">
    <i class="ti ti-refresh"></i> Upload Ulang
  </a>
  <?php if ($total_baru + $total_duplikat > 0): ?>
  <button type="button" class="btn btn-primary" onclick="submitImport()">
    <i class="ti ti-database-import"></i> Proses Import Sekarang
  </button>
  <?php endif; ?>
</div>

<?php endif; ?>


<script>
// Drag & drop
(function(){
  var dz = document.getElementById('drop-zone');
  if (!dz) return;
  dz.addEventListener('dragover',  function(e){ e.preventDefault(); this.classList.add('over'); });
  dz.addEventListener('dragleave', function(){ this.classList.remove('over'); });
  dz.addEventListener('drop', function(e){
    e.preventDefault(); this.classList.remove('over');
    if (e.dataTransfer.files.length) {
      var fi = document.getElementById('file_import');
      fi.files = e.dataTransfer.files;
      onFileSelect(fi);
    }
  });
})();

function onFileSelect(input) {
  if (input.files && input.files[0]) {
    var f = input.files[0];
    document.getElementById('file-label').textContent =
      f.name + ' (' + (f.size/1024/1024).toFixed(2) + ' MB)';
    document.getElementById('drop-zone').style.borderColor = 'var(--hijau-mid)';
  }
}

function toggleRef() {
  var el = document.getElementById('ref-box');
  var ic = document.getElementById('ref-chevron');
  var show = el.style.display === 'none';
  el.style.display = show ? 'block' : 'none';
  ic.className = show ? 'ti ti-chevron-up' : 'ti ti-chevron-down';
}

<?php if (isset($preview)): ?>
var rows    = <?= json_encode($preview) ?>;
var aksiMap = {};

rows.forEach(function(r, i) {
  aksiMap[i] = r.aksi;
});

function setAksi(i, aksi) {
  aksiMap[i] = aksi;
  rows[i].aksi = aksi;
  var bu = document.getElementById('btn-u-' + i);
  var bs = document.getElementById('btn-s-' + i);
  if (bu) bu.style.opacity = aksi === 'update' ? '1' : '.4';
  if (bs) bs.style.opacity = aksi === 'skip'   ? '1' : '.4';
}

function setAllDuplikat(aksi) {
  rows.forEach(function(r, i) {
    if (r.status === 'duplikat' && !r.errors.length) setAksi(i, aksi);
  });
}

function submitImport() {
  rows.forEach(function(r, i) { r.aksi = aksiMap[i] || r.aksi; });
  document.getElementById('rows-data-input').value = JSON.stringify(rows);

  // Tambah hidden input aksi per baris (row index sebagai key)
  Object.keys(aksiMap).forEach(function(i) {
    var inp = document.createElement('input');
    inp.type  = 'hidden';
    inp.name  = 'aksi[' + i + ']';
    inp.value = aksiMap[i];
    document.getElementById('form-proses').appendChild(inp);
  });

  document.getElementById('form-proses').submit();
}
<?php endif; ?>
</script>
