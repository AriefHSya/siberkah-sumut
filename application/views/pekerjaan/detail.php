<?php $p = $pekerjaan; ?>

<!-- Header -->
<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-file-text"></i> <?= htmlspecialchars($p->kode_bkp) ?></div>
    <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
      <?= badge_status($p->status) ?>
      <?= badge_jenis($p->jenis_penyaluran) ?>
      <span class="text-xs text-muted"><?= htmlspecialchars($p->nama_kabkota) ?> · TA <?= $p->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('pekerjaan.edit') && in_array($p->status,['draft','inspektorat_revisi','skpkd_kab_revisi'])): ?>
    <a href="<?= site_url('pekerjaan/edit/'.$p->id) ?>" class="btn btn-outline btn-sm"><i class="ti ti-edit"></i> Edit</a>
    <?php endif; ?>
    <?php if ($this->rbac->can('pekerjaan.cetak_permohonan') && $p->status !== 'draft'): ?>
    <a href="<?= site_url('pekerjaan/cetak-permohonan/'.$p->id) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="ti ti-printer"></i> Cetak Permohonan</a>
    <?php endif; ?>
    <?php if ($this->rbac->can('pekerjaan.submit') && $p->status === 'draft'): ?>
    <a href="<?= site_url('pekerjaan/submit/'.$p->id) ?>" class="btn btn-primary btn-sm"
       onclick="return confirm('Ajukan pekerjaan ini ke Inspektorat?\n\nPastikan semua data sudah lengkap dan benar.')">
      <i class="ti ti-send"></i> Submit ke Inspektorat
    </a>
    <?php endif; ?>
    <a href="<?= site_url('pekerjaan') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
  </div>
</div>

<!-- Flash deadline error -->
<?php if ($flash = $this->session->flashdata('error_deadline')): ?>
<div class="deadline-block mb-2">
  <div style="font-weight:600;display:flex;align-items:center;gap:6px"><i class="ti ti-lock"></i> Batas Waktu Terlewat — Pengajuan Diblokir</div>
  <div class="mt-1"><?= $flash ?></div>
  <div class="mt-1 text-sm">Untuk perubahan batas waktu, hubungi Admin Provinsi melalui menu <a href="<?= site_url('parameter/batas-waktu') ?>">Parameter → Batas Waktu</a>.</div>
</div>
<?php endif; ?>

<div class="g2">
  <!-- Kolom Kiri: Info Pekerjaan -->
  <div>
    <!-- Identitas BKP -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-database"></i> Identitas BKP</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:40%">Kode BKP</td><td class="mono fw-500"><?= $p->kode_bkp ?></td></tr>
        <tr><td class="text-muted text-sm">Uraian BKP</td><td class="text-sm"><?= htmlspecialchars($p->uraian_bkp) ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai BKP</td><td class="fw-500"><?= rupiah($p->nilai_bkp) ?></td></tr>
        <tr><td class="text-muted text-sm">Bidang</td><td><?= htmlspecialchars($p->nama_bidang) ?></td></tr>
        <tr><td class="text-muted text-sm">Kabupaten/Kota</td><td><?= htmlspecialchars($p->nama_kabkota) ?></td></tr>
        <tr><td class="text-muted text-sm">Jenis Penyaluran</td><td><?= badge_jenis($p->jenis_penyaluran) ?></td></tr>
        <tr><td class="text-muted text-sm">Diinput oleh</td><td class="text-sm"><?= htmlspecialchars($p->nama_opd ?? '-') ?> <?= $p->opd_nama ? '<span class="text-muted">('.htmlspecialchars($p->opd_nama).')</span>' : '' ?></td></tr>
      </table>
    </div>

    <!-- 17 Field Kegiatan -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-clipboard-list"></i> Data Kegiatan / Kontrak</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:40%">1. Nama Kegiatan</td><td class="text-sm"><?= htmlspecialchars($p->nama_kegiatan_dok ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">2. Volume & Satuan</td><td class="text-sm"><?= htmlspecialchars($p->volume_satuan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">3. Metode Pelaksanaan</td><td class="text-sm"><?= htmlspecialchars($p->metode_pelaksanaan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">4. Jenis Pekerjaan</td><td class="text-sm"><?= htmlspecialchars($p->jenis_pekerjaan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">5. Jangka Waktu</td><td class="text-sm"><?= $p->jangka_waktu_hari ? $p->jangka_waktu_hari.' hari' : '—' ?></td></tr>
        <tr><td class="text-muted text-sm">6. Nama Penyedia</td><td class="text-sm fw-500"><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">7. Alamat Penyedia</td><td class="text-sm"><?= htmlspecialchars($p->alamat_penyedia ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">8. No. Dok. Pekerjaan</td><td class="mono text-sm"><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">9. Tgl. Dok. Pekerjaan</td><td class="text-sm"><?= tgl_indo($p->tgl_dok_pekerjaan) ?></td></tr>
        <tr><td class="text-muted text-sm">10. No. SPMK</td><td class="mono text-sm"><?= htmlspecialchars($p->no_spmk ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">11. Tgl. SPMK</td><td class="text-sm"><?= tgl_indo($p->tgl_spmk) ?></td></tr>
        <tr><td class="text-muted text-sm">12. No. BAST</td><td class="mono text-sm"><?= htmlspecialchars($p->no_bast ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">13. Tgl. BAST</td><td class="text-sm"><?= tgl_indo($p->tgl_bast) ?></td></tr>
        <tr><td class="text-muted text-sm">14. Nilai Kontrak</td><td class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></td></tr>
        <tr><td class="text-muted text-sm">15. Belanja Pendukung</td><td class="text-sm"><?= rupiah($p->nilai_belanja_pendukung) ?></td></tr>
        <tr><td class="text-muted text-sm">16. Perda APBD</td><td class="text-sm"><?= $p->no_perda ? 'No. '.htmlspecialchars($p->no_perda).' / '.tgl_indo($p->tgl_perda) : '—' ?></td></tr>
        <tr><td class="text-muted text-sm">17. Perkada/Pergub BKP</td><td class="text-sm"><?= $p->no_perkada ? 'No. '.htmlspecialchars($p->no_perkada).' / '.tgl_indo($p->tgl_perkada) : '—' ?></td></tr>
      </table>
    </div>

    <!-- Lokasi Peta -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-map-pin"></i> Lokasi Kegiatan</div>
      <?php if ($p->latitude && $p->longitude): ?>
      <div class="text-sm mb-1"><?= htmlspecialchars($p->lokasi_deskripsi ?: 'Lihat peta') ?></div>
      <div class="text-xs text-muted mb-2">📍 <?= $p->latitude ?>, <?= $p->longitude ?></div>
      <div id="mapDetail" style="height:280px;border-radius:var(--radius);border:1px solid var(--border)"></div>
      <?php else: ?>
      <div class="text-muted text-sm" style="padding:20px;text-align:center">
        <i class="ti ti-map-off" style="font-size:28px;display:block;margin-bottom:4px"></i>
        Lokasi belum ditentukan.
        <?php if ($this->rbac->can('pekerjaan.edit') && in_array($p->status,['draft'])): ?>
        <br><a href="<?= site_url('pekerjaan/edit/'.$p->id) ?>">Edit untuk menentukan lokasi →</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Kolom Kanan: Tahapan, Dokumen, Timeline -->
  <div>
    <!-- Tahapan Penyaluran -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-layers-intersect"></i> Tahapan Penyaluran</div>
      <?php if (!empty($tahapan)): foreach ($tahapan as $t):
        $dl = $deadline_info[$t->id] ?? ['ok'=>TRUE,'pesan'=>'','bw'=>NULL];
        $lewat = isset($dl['bw']) && $dl['bw'] && date('Y-m-d') > $dl['bw']->batas_pengajuan;
      ?>
      <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;<?= $lewat?'border-color:#F7C1C1;background:#fff5f5':'' ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <div>
            <span class="fw-500"><?= htmlspecialchars($t->label_tahap) ?></span>
            <span class="badge badge-abu" style="font-size:10px;margin-left:4px"><?= $t->persen_nilai ?>%</span>
          </div>
          <?= badge_status($t->status === 'belum' ? 'draft' : $t->status) ?>
        </div>
        <div class="g2 text-sm">
          <div><span class="text-muted">Nilai diajukan:</span><br><strong><?= rupiah($t->nilai_diajukan) ?></strong></div>
          <div><span class="text-muted">Batas pengajuan:</span><br>
            <span class="<?= $lewat ? 'text-danger fw-500' : '' ?>"><?= tgl_indo($t->batas_tgl_pengajuan) ?></span>
            <?= $lewat ? '<span class="badge badge-merah text-xs">Lewat</span>' : '' ?>
          </div>
        </div>
        <?php if ($t->tgl_pengajuan): ?>
        <div class="text-xs text-muted mt-1">Diajukan: <?= tgl_indo($t->tgl_pengajuan) ?></div>
        <?php endif; ?>

        <!-- Upload dokumen per tahapan -->
        <?php $dok_t = $dok_per_tahapan[$t->id] ?? []; ?>
        <?php if (!empty($dok_t)): ?>
        <div style="margin-top:8px;border-top:1px solid var(--border);padding-top:8px">
          <div class="text-xs text-muted fw-500 mb-1">Dokumen terupload:</div>
          <?php foreach ($dok_t as $d): ?>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:12px">
            <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--biru)"></i>
            <span><?= label_jenis_dok($d->jenis_dokumen) ?></span>
            <span class="text-muted">(<?= $d->ukuran_kb ?> KB)</span>
            <a href="<?= base_url($d->file_path) ?>" target="_blank" class="btn-icon" title="Download" style="width:22px;height:22px;font-size:13px"><i class="ti ti-download"></i></a>
            <?php if ($this->rbac->can('pekerjaan.upload_dok')): ?>
            <a href="<?= site_url('pekerjaan/hapus-dok/'.$d->id) ?>" class="btn-icon del" data-confirm="Hapus dokumen ini?" style="width:22px;height:22px;font-size:13px"><i class="ti ti-trash"></i></a>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form upload dokumen -->
        <?php if ($this->rbac->can('pekerjaan.upload_dok') && in_array($t->status,['belum','opd_input','inspektorat_revisi','skpkd_kab_revisi'])): ?>
        <div style="margin-top:8px">
          <button class="btn btn-outline btn-xs" onclick="openModal('modalUpload<?= $t->id ?>')">
            <i class="ti ti-upload"></i> Upload Dokumen
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Modal Upload per tahapan -->
      <?php if ($this->rbac->can('pekerjaan.upload_dok')): ?>
      <div id="modalUpload<?= $t->id ?>" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw">
          <div class="card-title"><i class="ti ti-upload"></i> Upload Dokumen — <?= $t->label_tahap ?></div>
          <?= form_open_multipart(site_url('pekerjaan/upload-dok/'.$t->id)) ?>
          <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
          <div class="form-group mb-2">
            <label>Jenis Dokumen <span class="req">*</span></label>
            <select name="jenis_dokumen" class="form-control" required>
              <option value="">-- Pilih jenis --</option>
              <option value="surat_permohonan_pencairan">Surat Permohonan Pencairan</option>
              <option value="surat_pernyataan_bupati">Surat Pernyataan Bupati/Wali Kota</option>
              <option value="dokumen_pekerjaan_kontrak">Dokumen Pekerjaan / Kontrak</option>
              <option value="daftar_pekerjaan">Daftar Pekerjaan</option>
              <option value="laporan_reviu_inspektorat">Laporan Hasil Reviu (LHR)</option>
              <option value="ba_kemajuan_pekerjaan">Berita Acara Kemajuan Pekerjaan</option>
              <option value="bast">BAST</option>
              <option value="foto_dokumentasi">Foto Dokumentasi</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group mb-2">
            <label>File <span class="req">*</span></label>
            <input type="file" name="file_dok" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <div class="form-hint">Format: PDF, DOC, DOCX, JPG, PNG. Maks. 10 MB.</div>
          </div>
          <div class="form-group mb-2">
            <label>Keterangan</label>
            <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
          </div>
          <div class="form-actions">
            <button type="button" onclick="closeModal('modalUpload<?= $t->id ?>')" class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Upload</button>
          </div>
          <?= form_close() ?>
        </div>
      </div>
      <?php endif; ?>

      <?php endforeach; else: ?>
      <div class="text-muted text-sm" style="padding:16px;text-align:center">
        <i class="ti ti-info-circle"></i>
        Tahapan akan terbentuk otomatis setelah pekerjaan disubmit ke Inspektorat.
      </div>
      <?php endif; ?>
    </div>

    <!-- Timeline Status -->
    <div class="card">
      <div class="card-title"><i class="ti ti-timeline"></i> Riwayat Status</div>
      <?php if (!empty($history)): ?>
      <div style="position:relative;padding-left:20px">
        <?php foreach ($history as $h): ?>
        <div style="position:relative;margin-bottom:14px;padding-left:14px;border-left:2px solid var(--border)">
          <div style="position:absolute;left:-6px;top:3px;width:10px;height:10px;border-radius:50%;background:var(--biru)"></div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px">
            <?php if ($h->status_lama): ?><?= badge_status($h->status_lama) ?><i class="ti ti-arrow-right" style="font-size:11px;color:var(--text-muted)"></i><?php endif; ?>
            <?= badge_status($h->status_baru) ?>
          </div>
          <div class="text-xs text-muted"><?= htmlspecialchars($h->nama_user??'Sistem') ?> · <?= htmlspecialchars($h->nama_role??'') ?></div>
          <?php if ($h->catatan): ?><div class="text-xs mt-1"><?= htmlspecialchars($h->catatan) ?></div><?php endif; ?>
          <div class="text-xs text-muted mt-1"><?= tgl_indo($h->created_at) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted text-sm">Belum ada riwayat.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($p->latitude && $p->longitude): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const mapD = L.map('mapDetail').setView([<?= $p->latitude ?>, <?= $p->longitude ?>], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
}).addTo(mapD);
L.marker([<?= $p->latitude ?>, <?= $p->longitude ?>])
    .addTo(mapD)
    .bindPopup('<b><?= htmlspecialchars($p->kode_bkp, ENT_QUOTES) ?></b><br><?= htmlspecialchars($p->nama_kegiatan_dok ?: $p->uraian_bkp, ENT_QUOTES) ?>')
    .openPopup();
</script>
<?php endif; ?>
