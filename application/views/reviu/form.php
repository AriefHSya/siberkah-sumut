<?php $p = $pekerjaan; $r = $reviu; ?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-clipboard-check"></i> Reviu — <?= htmlspecialchars($p->kode_bkp) ?></div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <?= badge_jenis($p->jenis_penyaluran) ?>
      <span class="badge badge-abu"><?= htmlspecialchars($tahapan->label_tahap) ?></span>
      <span class="text-xs text-muted"><?= htmlspecialchars($p->nama_kabkota) ?> · TA <?= $p->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($r && $this->rbac->can('reviu.cetak_kertas_kerja')): ?>
    <a href="<?= site_url('reviu/cetak-kertas-kerja/'.$r->id) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Kertas Kerja
    </a>
    <?php endif; ?>
    <?php if ($r && $r->hasil_reviu === 'disetujui' && $this->rbac->can('reviu.download_rekap')): ?>
    <a href="<?= site_url('reviu/cetak-rekap/'.$r->id) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-report"></i> Cetak Rekap
    </a>
    <?php endif; ?>
    <a href="<?= site_url('reviu') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
  </div>
</div>

<div class="g2">
  <!-- Kolom Kiri: Info + Checklist -->
  <div style="grid-column:1/3">

    <!-- Info Pekerjaan (ringkas) -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-info-circle"></i> Informasi Pekerjaan</div>
      <div class="g3">
        <div><div class="text-xs text-muted">Uraian BKP</div><div class="text-sm fw-500"><?= htmlspecialchars($p->uraian_bkp) ?></div></div>
        <div><div class="text-xs text-muted">Nama Kegiatan</div><div class="text-sm"><?= htmlspecialchars($p->nama_kegiatan_dok) ?></div></div>
        <div><div class="text-xs text-muted">Nilai Kontrak</div><div class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></div></div>
        <div><div class="text-xs text-muted">Penyedia</div><div class="text-sm"><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></div></div>
        <div><div class="text-xs text-muted">No. Kontrak</div><div class="mono text-sm"><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></div></div>
        <div><div class="text-xs text-muted">No. SPMK</div><div class="mono text-sm"><?= htmlspecialchars($p->no_spmk ?: '—') ?></div></div>
      </div>
      <div class="mt-2">
        <a href="<?= site_url('pekerjaan/detail/'.$p->id) ?>" class="btn btn-outline btn-xs" target="_blank">
          <i class="ti ti-external-link"></i> Lihat Detail Lengkap
        </a>
        <?php if ($this->rbac->can('pekerjaan.cetak_permohonan')): ?>
        <a href="<?= site_url('pekerjaan/cetak-permohonan/'.$p->id) ?>" class="btn btn-outline btn-xs" target="_blank">
          <i class="ti ti-printer"></i> Cetak Permohonan Reviu
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- Statistik Checklist -->
<?php if ($r): ?>
<div class="g4 mb-2">
  <?php
  $total_item = count($items);
  $pct_sesuai = $total_item > 0 ? round($stat['sesuai']/$total_item*100) : 0;
  ?>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)"><i class="ti ti-list-check" style="color:var(--biru)"></i></div>
    <div class="stat-val"><?= $total_item ?></div>
    <div class="stat-label">Total Item</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)"><i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i></div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= $stat['sesuai'] ?></div>
    <div class="stat-label">Sesuai</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--merah-light)"><i class="ti ti-circle-x" style="color:var(--merah-mid)"></i></div>
    <div class="stat-val" style="color:var(--merah-mid)"><?= $stat['tidak_sesuai'] ?></div>
    <div class="stat-label">Tidak Sesuai</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--abu-light)"><i class="ti ti-minus-circle" style="color:var(--abu)"></i></div>
    <div class="stat-val"><?= $stat['tidak_berlaku'] ?></div>
    <div class="stat-label">Tidak Berlaku</div>
  </div>
</div>
<!-- Progress bar -->
<div style="margin-bottom:16px">
  <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
    <span>Progress pengisian checklist</span>
    <span class="fw-500"><?= $stat['sesuai'] + $stat['tidak_sesuai'] + $stat['tidak_berlaku'] ?> / <?= $total_item ?> diisi</span>
  </div>
  <div style="height:6px;background:var(--bg);border-radius:4px;overflow:hidden">
    <?php $filled = $stat['sesuai'] + $stat['tidak_sesuai'] + $stat['tidak_berlaku']; $pct = $total_item > 0 ? round($filled/$total_item*100) : 0; ?>
    <div style="height:100%;background:var(--biru);width:<?= $pct ?>%;transition:width 0.3s"></div>
  </div>
</div>
<?php endif; ?>

<!-- FORM CHECKLIST -->
<?php if ($this->rbac->can('reviu.input') && $r): ?>
<?= form_open(site_url('reviu/simpan-checklist/'.$r->id), ['id'=>'formChecklist']) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
<?php endif; ?>

<div class="card mb-2">
  <div class="card-title">
    <i class="ti ti-list-check"></i> Checklist Reviu
    <span class="badge badge-biru" style="font-size:10px;margin-left:auto"><?= count($items) ?> item · <?= ucfirst($p->jenis_penyaluran) ?> <?= $tahapan->kode_tahap ?></span>
  </div>

  <?php if (empty($items)): ?>
  <div class="alert alert-info"><i class="ti ti-info-circle"></i>
    <div>Tidak ada item checklist yang berlaku untuk kombinasi jenis/tahapan ini.</div>
  </div>
  <?php else: ?>

  <table class="tbl" id="tblChecklist">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th>Uraian Item Pemeriksaan</th>
        <th style="width:200px">Hasil</th>
        <th style="width:220px">Catatan</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item):
      $is_val  = $isian[$item->id] ?? NULL;
      $nilai   = $is_val ? $is_val->nilai : 'tidak_berlaku';
    ?>
    <tr id="row-<?= $item->id ?>" class="<?= $nilai === 'tidak_sesuai' ? 'ck-row-masalah' : '' ?>">
      <td class="text-muted text-sm mono"><?= $item->kode ?></td>
      <td class="text-sm"><?= htmlspecialchars($item->uraian_item) ?>
        <?php if ($item->jenis_penyaluran): ?>
        <span class="badge badge-abu" style="font-size:9px;margin-left:4px"><?= $item->jenis_penyaluran ?></span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($this->rbac->can('reviu.input') && $r): ?>
        <div style="display:flex;flex-direction:column;gap:3px">
          <?php foreach (['sesuai'=>['hijau','Sesuai'],'tidak_sesuai'=>['merah','Tidak Sesuai'],'tidak_berlaku'=>['abu','N/A']] as $val=>[$cl,$lab]): ?>
          <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:12px">
            <input type="radio" name="nilai[<?= $item->id ?>]" value="<?= $val ?>"
              <?= $nilai===$val ? 'checked' : '' ?>
              onchange="onNilaiChange(<?= $item->id ?>, '<?= $val ?>')">
            <span class="badge badge-<?= $cl ?>" style="font-size:10px"><?= $lab ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <?php $cl_map = ['sesuai'=>'hijau','tidak_sesuai'=>'merah','tidak_berlaku'=>'abu'];
                $lb_map = ['sesuai'=>'Sesuai','tidak_sesuai'=>'Tidak Sesuai','tidak_berlaku'=>'N/A']; ?>
          <span class="badge badge-<?= $cl_map[$nilai] ?>"><?= $lb_map[$nilai] ?></span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($this->rbac->can('reviu.input') && $r): ?>
        <input type="text" name="catatan[<?= $item->id ?>]"
          class="form-control fc-sm"
          value="<?= htmlspecialchars($is_val->catatan ?? '') ?>"
          placeholder="Catatan (opsional)" maxlength="500">
        <?php else: ?>
        <span class="text-sm text-muted"><?= htmlspecialchars($is_val->catatan ?? '—') ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($this->rbac->can('reviu.input') && $r): ?>
  <div style="display:flex;justify-content:flex-end;margin-top:14px;gap:8px">
    <button type="button" class="btn btn-outline btn-sm" onclick="isiSemua('sesuai')">
      <i class="ti ti-checks"></i> Semua Sesuai
    </button>
    <button type="submit" class="btn btn-primary btn-sm" id="btnSimpanCk">
      <i class="ti ti-device-floppy"></i> Simpan Checklist
    </button>
  </div>
  <?php endif; ?>

  <?php endif; // end empty items ?>
</div>

<?php if ($this->rbac->can('reviu.input') && $r): ?>
<?= form_close() ?>
<?php endif; ?>

<!-- UPLOAD LHR + KEPUTUSAN -->
<?php if ($this->rbac->can('reviu.input') && $r): ?>
<div class="g2">
  <!-- Upload LHR -->
  <div class="card">
    <div class="card-title"><i class="ti ti-file-certificate"></i> Laporan Hasil Reviu (LHR)</div>
    <?php if ($r->no_lhr): ?>
    <div style="padding:10px;background:var(--hijau-light);border-radius:var(--radius);margin-bottom:12px">
      <div class="text-sm fw-500">LHR Sudah Terupload</div>
      <div class="text-sm">No. LHR: <strong><?= htmlspecialchars($r->no_lhr) ?></strong></div>
      <div class="text-sm">Tanggal: <?= tgl_indo($r->tgl_lhr) ?></div>
      <?php if ($r->file_lhr_path): ?>
      <a href="<?= base_url($r->file_lhr_path) ?>" target="_blank" class="btn btn-outline btn-xs mt-1">
        <i class="ti ti-download"></i> Download LHR
      </a>
      <?php endif; ?>
    </div>
    <p class="text-xs text-muted mb-2">Update LHR jika ada perubahan:</p>
    <?php endif; ?>

    <?= form_open_multipart(site_url('reviu/upload-lhr/'.$r->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group mb-2">
      <label>No. LHR <span class="req">*</span></label>
      <input type="text" name="no_lhr" class="form-control"
        value="<?= htmlspecialchars($r->no_lhr ?? '') ?>"
        placeholder="Contoh: 700/LHR-BKP/2026/001" required>
    </div>
    <div class="form-group mb-2">
      <label>Tanggal LHR</label>
      <input type="date" name="tgl_lhr" class="form-control"
        value="<?= $r->tgl_lhr ?? date('Y-m-d') ?>">
    </div>
    <?php if ($pejabat): ?>
    <div class="form-group mb-2">
      <label>Penandatangan (Inspektur)</label>
      <select name="ref_inspektur_id" class="form-control">
        <option value="">— Pilih atau ketik manual —</option>
        <option value="<?= $pejabat->id ?>"
          <?= ($r->ref_inspektur_id == $pejabat->id) ? 'selected' : '' ?>>
          <?= htmlspecialchars($pejabat->nama) ?> — <?= htmlspecialchars($pejabat->jabatan ?? 'Inspektur') ?>
        </option>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group mb-2">
      <label>File LHR <span class="text-xs text-muted">(PDF/DOC, maks 10 MB)</span></label>
      <input type="file" name="file_lhr" class="form-control" accept=".pdf,.doc,.docx">
      <?php if ($r->file_lhr_path): ?>
      <div class="form-hint">File sebelumnya akan diganti jika upload baru.</div>
      <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="ti ti-upload"></i> <?= $r->no_lhr ? 'Update LHR' : 'Upload LHR' ?>
    </button>
    <?= form_close() ?>
  </div>

  <!-- Keputusan Reviu -->
  <div class="card">
    <div class="card-title"><i class="ti ti-gavel"></i> Keputusan Reviu</div>

    <?php if ($r->hasil_reviu): ?>
    <?php $hmap = ['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']]; $h=$hmap[$r->hasil_reviu]; ?>
    <div style="padding:12px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius);margin-bottom:12px">
      <div class="fw-500">Keputusan: <span class="badge badge-<?= $h[0] ?>"><?= $h[1] ?></span></div>
      <div class="text-sm mt-1"><?= htmlspecialchars($r->catatan ?: '—') ?></div>
      <?php if ($r->tgl_reviu_selesai): ?>
      <div class="text-xs text-muted mt-1">Tanggal: <?= tgl_indo($r->tgl_reviu_selesai) ?></div>
      <?php endif; ?>
    </div>
    <p class="text-xs text-muted mb-2">Ubah keputusan jika diperlukan:</p>
    <?php endif; ?>

    <?php if ($stat['tidak_sesuai'] > 0): ?>
    <div class="alert alert-warning mb-2" style="font-size:12px">
      <i class="ti ti-alert-triangle"></i>
      <div>Ada <strong><?= $stat['tidak_sesuai'] ?></strong> item checklist yang ditandai <strong>Tidak Sesuai</strong>. Pertimbangkan kembali sebelum menyetujui.</div>
    </div>
    <?php endif; ?>

    <?php if (!$r->no_lhr): ?>
    <div class="alert alert-info mb-2" style="font-size:12px">
      <i class="ti ti-info-circle"></i>
      <div>Upload Nomor LHR terlebih dahulu sebelum menyetujui reviu.</div>
    </div>
    <?php endif; ?>

    <?= form_open(site_url('reviu/putuskan/'.$r->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group mb-2">
      <label>Hasil Reviu <span class="req">*</span></label>
      <select name="hasil_reviu" class="form-control" required>
        <option value="">— Pilih keputusan —</option>
        <option value="disetujui"       <?= ($r->hasil_reviu==='disetujui')?'selected':'' ?>>✅ Disetujui</option>
        <option value="perlu_perbaikan" <?= ($r->hasil_reviu==='perlu_perbaikan')?'selected':'' ?>>⚠️ Perlu Perbaikan (kembalikan ke OPD)</option>
        <option value="ditolak"         <?= ($r->hasil_reviu==='ditolak')?'selected':'' ?>>❌ Ditolak</option>
      </select>
    </div>
    <div class="form-group mb-2">
      <label>Catatan <span class="text-xs text-muted">(wajib jika Perlu Perbaikan/Ditolak)</span></label>
      <textarea name="catatan" class="form-control" rows="3"
        placeholder="Uraikan temuan atau alasan keputusan..."><?= htmlspecialchars($r->catatan ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"
      onclick="return confirm('Yakin dengan keputusan reviu ini? Tindakan ini akan mengubah status pekerjaan dan mengirim notifikasi.')">
      <i class="ti ti-gavel"></i> Simpan Keputusan
    </button>
    <?= form_close() ?>
  </div>
</div>

<!-- Dokumen Pendukung -->
<?php if (!empty($dokumen)): ?>
<div class="card mt-2">
  <div class="card-title"><i class="ti ti-files"></i> Dokumen Pendukung yang Diupload OPD</div>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($dokumen as $d): ?>
    <a href="<?= base_url($d->file_path) ?>" target="_blank"
       style="display:flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;text-decoration:none;color:var(--text)">
      <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--biru);font-size:16px"></i>
      <div>
        <div class="fw-500"><?= label_jenis_dok($d->jenis_dokumen) ?></div>
        <div class="text-xs text-muted"><?= $d->ukuran_kb ?> KB</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php endif; // end can reviu.input ?>

<style>
.ck-row-masalah td { background: #fff8f8; }
</style>
<script>
function onNilaiChange(itemId, nilai) {
  const row = document.getElementById('row-' + itemId);
  row.classList.toggle('ck-row-masalah', nilai === 'tidak_sesuai');
  // Auto-save via AJAX setiap perubahan (opsional)
}

function isiSemua(nilai) {
  document.querySelectorAll('input[type="radio"][value="' + nilai + '"]').forEach(r => {
    r.checked = true;
    onNilaiChange(r.name.match(/\d+/)[0], nilai);
  });
}

// Simpan checklist otomatis saat berpindah halaman
window.addEventListener('beforeunload', function(e) {
  const form = document.getElementById('formChecklist');
  if (form && form.dataset.dirty === 'true') {
    e.preventDefault();
    e.returnValue = '';
  }
});
document.querySelectorAll('#formChecklist input, #formChecklist textarea').forEach(el => {
  el.addEventListener('change', () => {
    const form = document.getElementById('formChecklist');
    if (form) form.dataset.dirty = 'true';
  });
});
document.getElementById('formChecklist')?.addEventListener('submit', function() {
  this.dataset.dirty = 'false';
});
</script>
