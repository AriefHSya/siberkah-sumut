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
    <?php
    $darurat     = in_array($p->jenis_penyaluran, ['khusus_mendesak','khusus_bencana']);
    $dok_spk_ok  = $darurat || (!empty($p->dok_spk_path)  && file_exists(FCPATH . $p->dok_spk_path));
    $dok_spmk_ok = $darurat || (!empty($p->dok_spmk_path) && file_exists(FCPATH . $p->dok_spmk_path));
    $dok_bast_ok = $p->jenis_penyaluran !== 'sekaligus'
        || (!empty($p->dok_bast_path) && file_exists(FCPATH . $p->dok_bast_path));
    $dok_siap = $dok_spk_ok && $dok_spmk_ok && $dok_bast_ok;
    ?>
    <?php if ($dok_siap): ?>
    <a href="<?= site_url('pekerjaan/submit/'.$p->id) ?>" class="btn btn-primary btn-sm"
       onclick="return confirm('Ajukan pekerjaan ini ke Inspektorat?\n\nPastikan semua data sudah lengkap dan benar.')">
      <i class="ti ti-send"></i> Submit ke Inspektorat
    </a>
    <?php else: ?>
    <button class="btn btn-sm" disabled title="Upload dokumen <?= $p->jenis_penyaluran==='sekaligus'?'BAST':'SPK, SPMK' ?> terlebih dahulu"
            style="background:var(--abu-light);color:var(--abu);border:1px solid var(--border);cursor:not-allowed">
      <i class="ti ti-lock"></i> Submit ke Inspektorat
    </button>
    <?php endif; ?>
    <?php endif; ?>
    <?php
    // Tombol batal pengajuan — hanya saat status opd_submitted, sebelum Inspektorat approve
    $bisa_batal = $this->rbac->can('pekerjaan.submit')
        && $p->status === 'opd_submitted';
    ?>
    <?php if ($bisa_batal): ?>
    <a href="<?= site_url('pekerjaan/batal-submit/'.$p->id) ?>"
       class="btn btn-sm"
       style="background:var(--merah-light);color:var(--merah-mid);border:1px solid #F7C1C1"
       onclick="return confirm('Batalkan pengajuan ke Inspektorat?\n\nPekerjaan akan kembali ke status Draft dan dapat diedit kembali.')">
      <i class="ti ti-arrow-back-up"></i> Batalkan Pengajuan
    </a>
    <?php endif; ?>
    <?php if ($this->rbac->can('pekerjaan.submit') && $p->status === 'inspektorat_revisi'): ?>
    <a href="<?= site_url('pekerjaan/kirim-revisi/'.$p->id) ?>"
       class="btn btn-primary btn-sm"
       onclick="return confirm('Kirim perbaikan ke Inspektorat?\n\nPastikan pekerjaan sudah diperbaiki sesuai catatan Inspektorat.')">
      <i class="ti ti-send"></i> Kirim Revisi ke Inspektorat
    </a>
    <?php endif; ?>
    <a href="<?= $back_url ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
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

  <!-- Kolom Kanan: Upload Dokumen Draft, Tahapan, Timeline -->
  <div>

    <!-- ══ UPLOAD DOKUMEN WAJIB (hanya saat Draft) ══════════════ -->
    <?php if ($p->status === 'draft' && $this->rbac->can('pekerjaan.upload_dok')): ?>
    <?php
    $darurat_dok = in_array($p->jenis_penyaluran, ['khusus_mendesak','khusus_bencana']);
    $dok_list = [
        'spk'  => ['label'=>'SPK',  'desc'=>'Surat Perintah Kerja',      'path'=>$p->dok_spk_path  ?? NULL, 'wajib'=>!$darurat_dok],
        'spmk' => ['label'=>'SPMK', 'desc'=>'Surat Perintah Mulai Kerja','path'=>$p->dok_spmk_path ?? NULL, 'wajib'=>!$darurat_dok],
        'bast' => ['label'=>'BAST', 'desc'=>'Berita Acara Serah Terima', 'path'=>$p->dok_bast_path ?? NULL, 'wajib'=>$p->jenis_penyaluran === 'sekaligus'],
    ];
    $semua_lengkap = ($darurat_dok || (!empty($p->dok_spk_path) && !empty($p->dok_spmk_path)))
        && ($p->jenis_penyaluran !== 'sekaligus' || !empty($p->dok_bast_path));
    ?>
    <div class="card mb-2" style="border-left:4px solid <?= $semua_lengkap ? 'var(--hijau-mid)' : 'var(--kuning-mid)' ?>">
      <div class="card-title">
        <i class="ti ti-file-upload"></i> Dokumen Wajib Pre-Submit
        <?php if ($semua_lengkap): ?>
        <span class="badge badge-hijau" style="margin-left:auto">Lengkap — Siap Submit</span>
        <?php else: ?>
        <span class="badge badge-kuning" style="margin-left:auto">Belum Lengkap</span>
        <?php endif; ?>
      </div>
      <div class="text-xs text-muted mb-2">
        Upload dokumen berikut sebelum submit ke Inspektorat.
      </div>

      <?php foreach ($dok_list as $jenis => $dok):
        if (!$dok['wajib'] && $jenis === 'bast') continue; // skip BAST jika bukan sekaligus
        $uploaded = !empty($dok['path']) && file_exists(FCPATH . $dok['path']);
      ?>
      <div style="display:flex;align-items:center;gap:10px;padding:10px 0;
                  border-bottom:1px solid var(--border)">
        <!-- Status icon -->
        <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;
                    align-items:center;justify-content:center;font-size:16px;
                    background:<?= $uploaded ? 'var(--hijau-light)' : 'var(--kuning-light)' ?>;
                    color:<?= $uploaded ? 'var(--hijau-mid)' : 'var(--kuning-mid)' ?>">
          <i class="ti ti-<?= $uploaded ? 'circle-check' : 'clock' ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div class="fw-500 text-sm"><?= $dok['label'] ?>
            <?php if ($dok['wajib']): ?><span class="text-danger">*</span><?php endif; ?>
          </div>
          <div class="text-xs text-muted"><?= $dok['desc'] ?></div>
          <?php if ($uploaded): ?>
          <div class="text-xs" style="color:var(--hijau-mid);margin-top:2px">
            <i class="ti ti-file"></i> <?= basename($dok['path']) ?>
          </div>
          <?php endif; ?>
        </div>
        <!-- Aksi -->
        <div class="aksi-row" style="flex-shrink:0">
          <?php if ($uploaded): ?>
          <a href="<?= site_url('berkas/unduh/draft/'.$p->id.'/'.$jenis) ?>" target="_blank"
             class="btn btn-outline btn-xs" title="Download / Lihat">
            <i class="ti ti-eye"></i> Lihat
          </a>
          <a href="<?= site_url('pekerjaan/hapus-dok-draft/'.$p->id.'/'.$jenis) ?>"
             class="btn btn-xs" style="background:var(--merah-light);color:var(--merah-mid);border:1px solid #F7C1C1"
             data-confirm="Hapus file <?= $dok['label'] ?>?"
             onclick="return confirm('Hapus file <?= $dok['label'] ?>?')">
            <i class="ti ti-trash"></i>
          </a>
          <?php endif; ?>
          <button type="button" class="btn btn-primary btn-xs"
                  onclick="openModal('modalDraft_<?= $jenis ?>')">
            <i class="ti ti-upload"></i> <?= $uploaded ? 'Ganti' : 'Upload' ?>
          </button>
        </div>
      </div>

      <!-- Modal upload per jenis -->
      <div id="modalDraft_<?= $jenis ?>"
           style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);
                  z-index:1000;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:24px;width:460px;max-width:95vw">
          <div class="card-title" style="margin-bottom:16px">
            <i class="ti ti-upload"></i> Upload <?= $dok['label'] ?>
            <button type="button" onclick="closeModal('modalDraft_<?= $jenis ?>')"
                    style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted)">
              <i class="ti ti-x"></i>
            </button>
          </div>
          <div class="alert alert-info mb-2" style="font-size:12px">
            <i class="ti ti-info-circle"></i>
            <div><strong><?= $dok['label'] ?>:</strong> <?= $dok['desc'] ?></div>
          </div>
          <?= form_open_multipart(site_url('pekerjaan/upload-dok-draft/'.$p->id.'/'.$jenis)) ?>
          <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
          <div class="form-group mb-3">
            <label>Pilih File <span class="req">*</span></label>
            <input type="file" name="file_dok" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <div class="form-hint">Format: PDF, DOC, DOCX, JPG, PNG &nbsp;·&nbsp; Maks. 10 MB</div>
          </div>
          <div class="form-actions">
            <button type="button" onclick="closeModal('modalDraft_<?= $jenis ?>')" class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Upload</button>
          </div>
          <?= form_close() ?>
        </div>
      </div>

      <?php endforeach; ?>

      <?php if (!$semua_lengkap): ?>
      <div class="text-xs text-muted mt-2" style="font-style:italic">
        <i class="ti ti-info-circle"></i>
        Lengkapi semua dokumen bertanda <span class="text-danger">*</span> sebelum submit ke Inspektorat.
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Tahapan Penyaluran -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-layers-intersect"></i> Tahapan Penyaluran</div>
      <?php if (!empty($tahapan)):
        // Untuk bertahap: Tahap I selalu tampil.
        // Tahap II tampil juga setelah diajukan OPD (status != 'belum').
        $tahapan_tampil = ($p->jenis_penyaluran === 'bertahap')
            ? array_filter($tahapan, fn($t) => $t->kode_tahap === 'tahap_1' || $t->status !== 'belum')
            : $tahapan;
      foreach ($tahapan_tampil as $t):
        $dl = $deadline_info[$t->id] ?? ['ok'=>TRUE,'pesan'=>'','bw'=>NULL];
        $lewat = isset($dl['bw']) && $dl['bw'] && date('Y-m-d') > $dl['bw']->batas_pengajuan;
      ?>
      <?php
      // Formula nilai pengajuan per tahapan:
      //   Bertahap Tahap I : (persen_nilai% × nilai_kontrak) + 100% nilai_pendukung
      //   Bertahap Tahap II: persen_nilai% × nilai_kontrak (pendukung sudah di Tahap I)
      //   Sekaligus/Khusus : nilai_kontrak + nilai_pendukung
      $pct      = (float)($t->persen_nilai ?? 100) / 100;
      $pendukung= (float)($p->nilai_belanja_pendukung ?? 0);
      if ($p->jenis_penyaluran === 'bertahap') {
          if ($t->kode_tahap === 'tahap_1') {
              $nilai_pengajuan = ($p->nilai_kontrak * $pct) + $pendukung;
              $label_formula   = rupiah($p->nilai_kontrak * $pct).' (kontrak) + '.rupiah($pendukung).' (pendukung)';
          } else {
              // Tahap II: 50% × Nilai Kontrak (pendukung tidak termasuk, sudah dibayar di Tahap I)
              $nilai_pengajuan = $p->nilai_kontrak * $pct;
              $label_formula   = '50% × '.rupiah($p->nilai_kontrak);
          }
      } else {
          $nilai_pengajuan = ($p->nilai_kontrak ?? 0) + $pendukung;
          $label_formula   = $pendukung > 0 ? rupiah($p->nilai_kontrak).' + '.rupiah($pendukung) : NULL;
      }
      // Dokumen draft yang diupload sebelum submit
      $dok_draft_tampil = [
          'SPK'  => ['path' => $p->dok_spk_path  ?? NULL, 'icon' => 'file-text'],
          'SPMK' => ['path' => $p->dok_spmk_path ?? NULL, 'icon' => 'file-text'],
          'BAST' => ['path' => $p->dok_bast_path  ?? NULL, 'icon' => 'file-check'],
      ];
      ?>
      <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;<?= $lewat?'border-color:#F7C1C1;background:#fff5f5':'' ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <div>
            <span class="fw-500"><?= htmlspecialchars($t->label_tahap) ?></span>
            <span class="badge badge-abu" style="font-size:10px;margin-left:4px"><?= $t->persen_nilai ?>%</span>
          </div>
          <?= badge_status($t->status === 'belum' ? 'draft' : $t->status) ?>
        </div>

        <!-- Nilai pengajuan dengan formula per jenis penyaluran -->
        <div class="g2 text-sm mb-2">
          <div>
            <span class="text-muted">Nilai Pengajuan:</span><br>
            <strong style="color:var(--biru);font-size:15px"><?= rupiah($nilai_pengajuan) ?></strong>
            <?php if ($label_formula): ?>
            <div class="text-xs text-muted mt-1"><?= $label_formula ?></div>
            <?php endif; ?>
          </div>
          <div>
            <span class="text-muted">Batas Pengajuan:</span><br>
            <span class="<?= $lewat ? 'text-danger fw-500' : '' ?>"><?= tgl_indo($t->batas_tgl_pengajuan) ?></span>
            <?= $lewat ? '<span class="badge badge-merah text-xs">Lewat</span>' : '' ?>
            <?php if ($t->tgl_pengajuan): ?>
            <div class="text-xs text-muted mt-1">Diajukan: <?= tgl_indo($t->tgl_pengajuan) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Dokumen pendukung: Tahap I = SPK/SPMK/BAST, Tahap II = Foto Dokumentasi + BA Kemajuan -->
        <?php if ($t->kode_tahap !== 'tahap_1'): ?>
          <?php if (!empty($capaian) && !empty($capaian->capaian_id) && ($capaian->foto_path || !empty($capaian->ba_path))): ?>
          <div style="border-top:1px solid var(--border);padding-top:8px;margin-top:4px">
            <div class="text-xs text-muted fw-600 mb-2" style="text-transform:uppercase;letter-spacing:0.4px">
              <i class="ti ti-files"></i> Dokumen Pendukung Pengajuan
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php if ($capaian->foto_path): ?>
              <div style="display:flex;align-items:center;gap:6px;padding:6px 10px;
                          border:1px solid var(--hijau-mid);border-radius:var(--radius);
                          background:var(--hijau-light)">
                <i class="ti ti-photo" style="color:var(--hijau-mid);font-size:14px"></i>
                <span class="text-xs fw-600" style="color:var(--hijau-mid)"><?= htmlspecialchars($capaian->nama_foto_asli ?? 'Foto Dokumentasi') ?></span>
                <a href="<?= site_url('berkas/unduh/capaian/'.$capaian->pekerjaan_id) ?>" target="_blank"
                   class="btn btn-xs" style="padding:2px 8px;background:var(--hijau-mid);color:#fff;border:none"
                   title="Preview / Download Foto Dokumentasi">
                  <i class="ti ti-eye"></i> Lihat
                </a>
                <a href="<?= site_url('berkas/unduh/capaian/'.$capaian->pekerjaan_id) ?>"
                   class="btn btn-xs" style="padding:2px 8px;background:#fff;color:var(--hijau-mid);border:1px solid var(--hijau-mid)"
                   title="Download Foto Dokumentasi">
                  <i class="ti ti-download"></i>
                </a>
              </div>
              <?php endif; ?>
              <?php if (!empty($capaian->ba_path)): ?>
              <div style="display:flex;align-items:center;gap:6px;padding:6px 10px;
                          border:1px solid var(--hijau-mid);border-radius:var(--radius);
                          background:var(--hijau-light)">
                <i class="ti ti-file-check" style="color:var(--hijau-mid);font-size:14px"></i>
                <span class="text-xs fw-600" style="color:var(--hijau-mid)"><?= htmlspecialchars($capaian->nama_ba_asli ?? 'BA Kemajuan Pekerjaan') ?></span>
                <a href="<?= site_url('berkas/unduh/capaian-ba/'.$capaian->pekerjaan_id) ?>" target="_blank"
                   class="btn btn-xs" style="padding:2px 8px;background:var(--hijau-mid);color:#fff;border:none"
                   title="Preview / Download BA Kemajuan">
                  <i class="ti ti-eye"></i> Lihat
                </a>
                <a href="<?= site_url('berkas/unduh/capaian-ba/'.$capaian->pekerjaan_id) ?>"
                   class="btn btn-xs" style="padding:2px 8px;background:#fff;color:var(--hijau-mid);border:1px solid var(--hijau-mid)"
                   title="Download BA Kemajuan">
                  <i class="ti ti-download"></i>
                </a>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <?php $ada_dok_draft = array_filter(array_column($dok_draft_tampil, 'path')); ?>
          <?php if (!empty($ada_dok_draft)): ?>
          <div style="border-top:1px solid var(--border);padding-top:8px;margin-top:4px">
            <div class="text-xs text-muted fw-600 mb-2" style="text-transform:uppercase;letter-spacing:0.4px">
              <i class="ti ti-files"></i> Dokumen Pendukung Pengajuan
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php foreach ($dok_draft_tampil as $label => $dok):
                if (!$dok['path'] || !file_exists(FCPATH . $dok['path'])) continue;
              ?>
              <div style="display:flex;align-items:center;gap:6px;padding:6px 10px;
                          border:1px solid var(--hijau-mid);border-radius:var(--radius);
                          background:var(--hijau-light)">
                <i class="ti ti-<?= $dok['icon'] ?>" style="color:var(--hijau-mid);font-size:14px"></i>
                <span class="text-xs fw-600" style="color:var(--hijau-mid)"><?= $label ?></span>
                <a href="<?= site_url('berkas/unduh/draft/'.$p->id.'/'.strtolower($label)) ?>" target="_blank"
                   class="btn btn-xs" style="padding:2px 8px;background:var(--hijau-mid);color:#fff;border:none"
                   title="Preview / Download <?= $label ?>">
                  <i class="ti ti-eye"></i> Lihat
                </a>
                <a href="<?= site_url('berkas/unduh/draft/'.$p->id.'/'.strtolower($label)) ?>"
                   class="btn btn-xs" style="padding:2px 8px;background:#fff;color:var(--hijau-mid);border:1px solid var(--hijau-mid)"
                   title="Download <?= $label ?>">
                  <i class="ti ti-download"></i>
                </a>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Dokumen tahapan — view/download saja, upload dikelola SKPKD Kab/Kota -->
        <?php $dok_t = $dok_per_tahapan[$t->id] ?? []; ?>
        <?php if (!empty($dok_t)): ?>
        <div style="margin-top:8px;border-top:1px solid var(--border);padding-top:8px">
          <div class="text-xs text-muted fw-500 mb-1">Dokumen tahapan:</div>
          <?php foreach ($dok_t as $d): ?>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:12px">
            <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--biru)"></i>
            <span><?= label_jenis_dok($d->jenis_dokumen) ?></span>
            <span class="text-muted">(<?= $d->ukuran_kb ?> KB)</span>
            <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>" target="_blank" class="btn-icon" title="Lihat / Download" style="width:22px;height:22px;font-size:13px"><i class="ti ti-download"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php endforeach; else: ?>
      <div class="text-muted text-sm" style="padding:16px;text-align:center">
        <i class="ti ti-info-circle"></i>
        Tahapan akan terbentuk otomatis setelah pekerjaan disubmit ke Inspektorat.
      </div>
      <?php endif; ?>

      <?php
      // Cari data Tahap II untuk cek status-nya
      $tahap2_info = NULL;
      foreach ($tahapan as $_t) { if ($_t->kode_tahap === 'tahap_2') { $tahap2_info = $_t; break; } }
      if ($p->jenis_penyaluran === 'bertahap' && !empty($tahapan) && (!$tahap2_info || $tahap2_info->status === 'belum')): ?>
      <div class="alert alert-info mt-1" style="font-size:12px">
        <i class="ti ti-info-circle"></i>
        <div>Jenis <strong>Bertahap</strong>: Tahap II akan tampil di sini setelah OPD Teknis mengajukan
        Tahap II melalui menu <strong>Capaian</strong>.</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Capaian Output Fisik (hanya bertahap + sudah diisi OPD) -->
    <?php if (!empty($capaian) && !empty($capaian->capaian_id)): ?>
    <div class="card mb-2" style="border-left:4px solid var(--hijau-mid)">
      <div class="card-title">
        <i class="ti ti-chart-bar"></i> Capaian Output Fisik Tahap I
        <span class="badge badge-hijau" style="margin-left:auto"><?= (float)$capaian->persen_fisik ?>%</span>
      </div>

      <!-- Progress bar -->
      <div style="background:#e9ecef;border-radius:6px;height:10px;margin-bottom:14px;overflow:hidden">
        <div style="height:100%;background:var(--hijau-mid);width:<?= min(100,(float)$capaian->persen_fisik) ?>%;transition:width .3s"></div>
      </div>

      <table class="tbl" style="margin-bottom:10px">
        <tr>
          <td class="text-muted text-sm" style="width:40%">No. BA Kemajuan</td>
          <td class="mono text-sm"><?= htmlspecialchars($capaian->no_ba_kemajuan ?: '—') ?></td>
        </tr>
        <tr>
          <td class="text-muted text-sm">Tanggal BA</td>
          <td class="text-sm"><?= tgl_indo($capaian->tgl_ba_kemajuan) ?: '—' ?></td>
        </tr>
        <tr>
          <td class="text-muted text-sm">Persentase Fisik</td>
          <td><strong style="font-size:16px;color:var(--hijau-mid)"><?= (float)$capaian->persen_fisik ?>%</strong></td>
        </tr>
        <tr>
          <td class="text-muted text-sm">Tanggal Realisasi</td>
          <td class="text-sm"><?= tgl_indo($capaian->tgl_realisasi) ?: '—' ?></td>
        </tr>
        <?php if ($capaian->keterangan): ?>
        <tr>
          <td class="text-muted text-sm" style="vertical-align:top;padding-top:8px">Keterangan</td>
          <td class="text-sm"><?= nl2br(htmlspecialchars($capaian->keterangan)) ?></td>
        </tr>
        <?php endif; ?>
      </table>

      <!-- Tombol unduhan dokumen capaian -->
      <?php if ($capaian->foto_path || !empty($capaian->ba_path)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:8px;border-top:1px solid var(--border);padding-top:10px">
        <?php if ($capaian->foto_path): ?>
        <a href="<?= site_url('berkas/unduh/capaian/'.$capaian->pekerjaan_id) ?>" target="_blank"
           class="btn btn-outline btn-sm">
          <i class="ti ti-photo"></i>
          <?= htmlspecialchars($capaian->nama_foto_asli ?? 'Foto Dokumentasi') ?>
        </a>
        <?php endif; ?>
        <?php if (!empty($capaian->ba_path)): ?>
        <a href="<?= site_url('berkas/unduh/capaian-ba/'.$capaian->pekerjaan_id) ?>" target="_blank"
           class="btn btn-outline btn-sm">
          <i class="ti ti-file-check"></i>
          <?= htmlspecialchars($capaian->nama_ba_asli ?? 'Berita Acara Kemajuan') ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Timeline Status -->
    <div class="card">
      <div class="card-title"><i class="ti ti-timeline"></i> Riwayat Status</div>
      <?php if (!empty($history)): ?>
      <?php
      // Tentukan cut-off Tahap II (hanya untuk pekerjaan bertahap):
      // Cari waktu paling awal saat status_baru masuk ke fase Tahap II.
      // Semua entry sejak waktu itu ditandai sebagai proses Tahap II.
      $tahap2_cutoff = NULL;
      if ($p->jenis_penyaluran === 'bertahap') {
          $statuses_tahap2 = ['dikonfirmasi_tahap1','opd_capaian_tahap1','disalurkan_tahap2'];
          foreach (array_reverse((array)$history) as $_h) {
              if (in_array($_h->status_baru, $statuses_tahap2)
                  || in_array($_h->status_lama, $statuses_tahap2)) {
                  $tahap2_cutoff = $_h->created_at;
                  break;
              }
          }
      }
      ?>
      <div style="position:relative;padding-left:20px">
        <?php foreach ($history as $h):
          $is_tahap2 = $tahap2_cutoff !== NULL && $h->created_at >= $tahap2_cutoff;
        ?>
        <div style="position:relative;margin-bottom:14px;padding-left:14px;border-left:2px solid <?= $is_tahap2 ? 'var(--teal-mid)' : 'var(--border)' ?>">
          <div style="position:absolute;left:-6px;top:3px;width:10px;height:10px;border-radius:50%;background:<?= $is_tahap2 ? 'var(--teal-mid)' : 'var(--biru)' ?>"></div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;flex-wrap:wrap">
            <?php if ($h->status_lama): ?><?= badge_status($h->status_lama) ?><i class="ti ti-arrow-right" style="font-size:11px;color:var(--text-muted)"></i><?php endif; ?>
            <?= badge_status($h->status_baru) ?>
            <?php if ($is_tahap2): ?>
            <span class="badge badge-teal" style="font-size:10px;padding:2px 6px;vertical-align:middle">Tahap II</span>
            <?php endif; ?>
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
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl:       'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl:     'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});
const mapD = L.map('mapDetail').setView([<?= $p->latitude ?>, <?= $p->longitude ?>], 15);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
}).addTo(mapD);
L.marker([<?= $p->latitude ?>, <?= $p->longitude ?>])
    .addTo(mapD)
    .bindPopup('<b><?= htmlspecialchars($p->kode_bkp, ENT_QUOTES) ?></b><br><?= htmlspecialchars($p->nama_kegiatan_dok ?: $p->uraian_bkp, ENT_QUOTES) ?>')
    .openPopup();
</script>
<?php endif; ?>
