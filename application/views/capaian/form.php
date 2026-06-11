<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div class="page-title">
    <i class="ti ti-chart-bar"></i>
    <?= $detail->capaian_id ? 'Edit' : 'Input' ?> Capaian Output Fisik
  </div>
  <a href="<?= site_url('capaian') ?>" class="btn btn-outline btn-sm">
    <i class="ti ti-arrow-left"></i> Kembali
  </a>
</div>

<!-- Info Pekerjaan -->
<div class="card">
  <div class="card-title"><i class="ti ti-file-text"></i> Informasi Pekerjaan</div>
  <div class="form-grid-3">
    <div>
      <div class="form-label text-muted" style="font-size:11px">KODE BKP</div>
      <div style="font-family:monospace;font-weight:600"><?= htmlspecialchars($detail->kode_bkp) ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">BIDANG</div>
      <div><?= htmlspecialchars($detail->nama_bidang) ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">KAB/KOTA</div>
      <div><?= htmlspecialchars($detail->nama_kabkota) ?></div>
    </div>
    <div style="grid-column:1/-1">
      <div class="form-label text-muted" style="font-size:11px">URAIAN KEGIATAN</div>
      <div><?= htmlspecialchars($detail->nama_kegiatan_dok ?: $detail->uraian_bkp) ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">NILAI KONTRAK</div>
      <div><?= rupiah($detail->nilai_kontrak) ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">PENYEDIA</div>
      <div><?= htmlspecialchars($detail->nama_penyedia ?? '—') ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">STATUS PEKERJAAN</div>
      <div><?= badge_status($detail->status) ?></div>
    </div>
  </div>
</div>

<!-- Info Penyaluran Tahap I -->
<div class="card">
  <div class="card-title"><i class="ti ti-cash"></i> Penyaluran Dana Tahap I</div>
  <div class="form-grid-3">
    <div>
      <div class="form-label text-muted" style="font-size:11px">NO. SP2D</div>
      <div><?= htmlspecialchars($detail->no_sp2d ?? '—') ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">TANGGAL SP2D</div>
      <div><?= tgl_indo($detail->tgl_sp2d ?? '') ?></div>
    </div>
    <div>
      <div class="form-label text-muted" style="font-size:11px">NILAI TRANSFER TAHAP I</div>
      <div style="font-weight:700;color:var(--hijau-mid)"><?= rupiah($detail->nilai_transfer ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- Form Capaian -->
<?php if ($this->rbac->can('capaian.input')): ?>
<div class="card">
  <div class="card-title"><i class="ti ti-chart-line"></i> <?= $detail->capaian_id ? 'Perbarui' : 'Input' ?> Capaian Output</div>

  <?= form_open_multipart(site_url('capaian/simpan/'.$detail->pekerjaan_id)) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

  <div class="form-grid-2">
    <!-- Persentase Fisik -->
    <div class="form-group" style="grid-column:1/-1">
      <label class="form-label">Persentase Capaian Fisik <span class="text-danger">*</span></label>
      <div style="display:flex;align-items:center;gap:12px">
        <input type="range" id="range-persen" min="0" max="100" step="1"
               value="<?= htmlspecialchars($detail->persen_fisik ?? 0) ?>"
               oninput="document.getElementById('inp-persen').value=this.value;updateBar(this.value)"
               style="flex:1;accent-color:var(--hijau-mid)">
        <div style="display:flex;align-items:center;gap:4px">
          <input type="number" name="persen_fisik" id="inp-persen" class="form-control fc"
                 style="width:80px;text-align:center"
                 min="0" max="100" step="0.01" required
                 value="<?= htmlspecialchars($detail->persen_fisik ?? 0) ?>"
                 oninput="document.getElementById('range-persen').value=this.value;updateBar(this.value)">
          <span style="font-weight:600">%</span>
        </div>
      </div>
      <!-- Progress bar preview -->
      <div style="background:#e9ecef;border-radius:6px;height:10px;margin-top:8px;overflow:hidden">
        <div id="preview-bar" style="height:100%;background:var(--hijau-mid);transition:width .2s;width:<?= (float)($detail->persen_fisik ?? 0) ?>%"></div>
      </div>
    </div>

    <!-- Tanggal Realisasi -->
    <div class="form-group">
      <label class="form-label">Tanggal Realisasi</label>
      <input type="date" name="tgl_realisasi" class="form-control fc"
             value="<?= htmlspecialchars($detail->tgl_realisasi ?? '') ?>">
    </div>

    <!-- No. BA Kemajuan -->
    <div class="form-group">
      <label class="form-label">No. Berita Acara Kemajuan Pekerjaan</label>
      <input type="text" name="no_ba_kemajuan" class="form-control fc"
             placeholder="Contoh: 001/BA-KEM/PU/2026"
             value="<?= htmlspecialchars($detail->no_ba_kemajuan ?? '') ?>">
    </div>

    <!-- Tanggal BA -->
    <div class="form-group">
      <label class="form-label">Tanggal Berita Acara</label>
      <input type="date" name="tgl_ba_kemajuan" class="form-control fc"
             value="<?= htmlspecialchars($detail->tgl_ba_kemajuan ?? '') ?>">
    </div>

    <!-- Keterangan -->
    <div class="form-group" style="grid-column:1/-1">
      <label class="form-label">Keterangan / Deskripsi Capaian <span class="text-danger">*</span></label>
      <textarea name="keterangan" class="form-control" rows="4" required
                placeholder="Uraikan progress pekerjaan, kondisi lapangan, kendala (jika ada)…"><?= htmlspecialchars($detail->keterangan ?? '') ?></textarea>
    </div>

    <!-- Foto Dokumentasi -->
    <div class="form-group" style="grid-column:1/-1">
      <label class="form-label">Foto / Dokumen Pendukung</label>
      <?php if ($detail->foto_path): ?>
      <div class="alert alert-info" style="margin-bottom:8px;font-size:13px">
        <i class="ti ti-photo"></i> Foto sudah ada:
        <a href="<?= site_url('berkas/unduh/capaian/'.$detail->id) ?>" target="_blank"><?= htmlspecialchars($detail->nama_foto_asli ?? basename($detail->foto_path)) ?></a>
        &nbsp;—&nbsp; Upload baru untuk mengganti.
      </div>
      <?php endif; ?>
      <input type="file" name="foto_dokumentasi" class="form-control"
             accept="image/jpeg,image/png,application/pdf"
             style="padding:6px">
      <small class="text-muted">Format: JPG, PNG, atau PDF &bull; Maks 5 MB</small>
    </div>
  </div>

  <div class="aksi-row" style="margin-top:8px">
    <a href="<?= site_url('capaian') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">
      <i class="ti ti-device-floppy"></i>
      <?= $detail->capaian_id ? 'Perbarui Capaian' : 'Simpan Capaian' ?>
    </button>
  </div>
  <?= form_close() ?>
</div>

<?php else: ?>
<!-- View-only jika tidak punya permission input -->
<?php if ($detail->capaian_id): ?>
<div class="card">
  <div class="card-title"><i class="ti ti-chart-line"></i> Data Capaian Output</div>
  <div class="form-grid-2">
    <div><div class="form-label text-muted" style="font-size:11px">PERSENTASE FISIK</div>
      <div style="font-size:24px;font-weight:700;color:var(--hijau-mid)"><?= (float)$detail->persen_fisik ?>%</div></div>
    <div><div class="form-label text-muted" style="font-size:11px">TANGGAL REALISASI</div>
      <div><?= tgl_indo($detail->tgl_realisasi ?? '') ?></div></div>
    <div><div class="form-label text-muted" style="font-size:11px">NO. BA KEMAJUAN</div>
      <div><?= htmlspecialchars($detail->no_ba_kemajuan ?? '—') ?></div></div>
    <div><div class="form-label text-muted" style="font-size:11px">TANGGAL BA</div>
      <div><?= tgl_indo($detail->tgl_ba_kemajuan ?? '') ?></div></div>
    <div style="grid-column:1/-1"><div class="form-label text-muted" style="font-size:11px">KETERANGAN</div>
      <div><?= nl2br(htmlspecialchars($detail->keterangan ?? '—')) ?></div></div>
    <?php if ($detail->foto_path): ?>
    <div style="grid-column:1/-1"><div class="form-label text-muted" style="font-size:11px">DOKUMENTASI</div>
      <a href="<?= site_url('berkas/unduh/capaian/'.$detail->id) ?>" target="_blank" class="btn btn-outline btn-sm">
        <i class="ti ti-download"></i> Lihat Foto/Dokumen
      </a></div>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Belum ada data capaian yang diinput.</div>
<?php endif; ?>
<?php endif; ?>

<script>
function updateBar(v) {
  document.getElementById('preview-bar').style.width = Math.min(100, Math.max(0, v)) + '%';
}
</script>
