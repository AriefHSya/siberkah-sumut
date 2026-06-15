<?php $p = $pekerjaan; $v = $verif; ?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-shield-check"></i> Verifikasi — <?= htmlspecialchars($p->kode_bkp) ?></div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <?= badge_jenis($p->jenis_penyaluran) ?>
      <span class="badge badge-abu"><?= htmlspecialchars($tahapan->label_tahap) ?></span>
      <span class="text-xs text-muted"><?= htmlspecialchars($p->nama_kabkota) ?> · TA <?= $p->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('verif_kab.cetak_rekap')
          && in_array($tahapan->status,['skpkd_kab_approved','disalurkan','dikonfirmasi'])): ?>
    <a href="<?= site_url('verifikasi/kab/cetak-rekap/'.$tahapan->id) ?>"
       target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Rekapitulasi
    </a>
    <?php endif; ?>
    <a href="<?= site_url('verifikasi/kab') ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<!-- Status pekerjaan saat ini -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding:10px 14px;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
  <span class="text-sm text-muted">Status pekerjaan:</span>
  <?= badge_status($p->status) ?>
  <?php if ($v && $v->hasil_verifikasi): ?>
  <span class="text-muted">·</span>
  <span class="text-sm text-muted">Hasil verifikasi:</span>
  <?php $hmap=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
        $h = $hmap[$v->hasil_verifikasi] ?? ['abu',$v->hasil_verifikasi]; ?>
  <span class="badge badge-<?= $h[0] ?>"><?= $h[1] ?></span>
  <?php endif; ?>
</div>

<div class="g2">

  <!-- ── Kolom Kiri: Info Pekerjaan + Dokumen ── -->
  <div>
    <!-- Ringkasan Pekerjaan -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-file-description"></i> Data Pekerjaan</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:42%">Uraian BKP</td>
            <td class="text-sm"><?= htmlspecialchars($p->uraian_bkp) ?></td></tr>
        <tr><td class="text-muted text-sm">Nama Kegiatan</td>
            <td class="text-sm fw-500"><?= htmlspecialchars($p->nama_kegiatan_dok ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Jenis Penyaluran</td>
            <td><?= badge_jenis($p->jenis_penyaluran) ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai Kontrak</td>
            <td class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai Diajukan</td>
            <td class="fw-500"><?= rupiah($tahapan->nilai_diajukan) ?>
              <span class="text-xs text-muted">(<?= $tahapan->persen_nilai ?>%)</span></td></tr>
        <?php if (!empty($p->nilai_belanja_pendukung) && $p->nilai_belanja_pendukung > 0): ?>
        <tr><td class="text-muted text-sm">Nilai Pendukung</td>
            <td class="text-sm"><?= rupiah($p->nilai_belanja_pendukung) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted text-sm">No. Kontrak</td>
            <td class="mono text-sm"><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Penyedia</td>
            <td class="text-sm"><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></td></tr>
        <?php if ($reviu): ?>
        <tr><td class="text-muted text-sm">No. LHR Inspektorat</td>
            <td class="text-sm fw-500 text-hijau"><?= htmlspecialchars($reviu->no_lhr ?: '—') ?>
              <?= $reviu->tgl_lhr ? ' / '.tgl_indo($reviu->tgl_lhr) : '' ?>
              <?php if ($reviu->hasil_reviu === 'disetujui'): ?><span class="badge badge-hijau" style="font-size:9px">✓</span><?php endif; ?>
            </td></tr>
        <?php endif; ?>
        <tr><td class="text-muted text-sm">Batas Pengajuan</td>
            <td class="text-sm <?= is_deadline_lewat($tahapan->batas_tgl_pengajuan)?'text-danger':'' ?>">
              <?= tgl_indo($tahapan->batas_tgl_pengajuan) ?>
            </td></tr>
      </table>
      <div class="mt-2">
        <a href="<?= site_url('pekerjaan/detail/'.$p->id) ?>"
           target="_blank" class="btn btn-outline btn-xs">
          <i class="ti ti-external-link"></i> Lihat Detail Lengkap Pekerjaan
        </a>
      </div>
    </div>

    <!-- Dokumen -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-files"></i> Dokumen Persyaratan</div>

      <!-- LHR Inspektorat -->
      <?php if ($reviu && $reviu->file_lhr_path): ?>
      <div style="margin-bottom:10px">
        <div class="text-xs text-muted fw-500" style="margin-bottom:4px;text-transform:uppercase;letter-spacing:.4px">
          <i class="ti ti-clipboard-check" style="color:var(--hijau-mid)"></i> Laporan Hasil Reviu (Inspektorat)
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:8px 10px;border:1px solid var(--hijau-mid);border-radius:var(--radius);
                    background:var(--hijau-light);font-size:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="ti ti-file-type-pdf" style="color:var(--hijau-mid);font-size:20px"></i>
            <div>
              <div class="fw-500" style="color:var(--hijau-mid)">Laporan Hasil Reviu (LHR)</div>
              <?php if ($reviu->no_lhr): ?>
              <div class="text-muted">No. <?= htmlspecialchars($reviu->no_lhr) ?><?= $reviu->tgl_lhr ? ' · '.tgl_indo($reviu->tgl_lhr) : '' ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="aksi-row">
            <a href="<?= site_url('berkas/unduh/lhr/'.$reviu->id) ?>" target="_blank"
               class="btn btn-outline btn-xs" title="Lihat LHR">
              <i class="ti ti-eye"></i> Lihat
            </a>
            <a href="<?= site_url('berkas/unduh/lhr/'.$reviu->id) ?>"
               class="btn-icon" title="Unduh LHR">
              <i class="ti ti-download"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Dokumen Pekerjaan OPD Teknis (SPK/SPMK/BAST dari trx_pekerjaan + dokumen tambahan) -->
      <?php
      $dok_draft = [
          'spk'  => ['path' => $p->dok_spk_path  ?? NULL, 'label' => 'Surat Perintah Kerja (SPK)'],
          'spmk' => ['path' => $p->dok_spmk_path ?? NULL, 'label' => 'Surat Perintah Mulai Kerja (SPMK)'],
          'bast' => ['path' => $p->dok_bast_path  ?? NULL, 'label' => 'Berita Acara Serah Terima (BAST)'],
      ];
      $dok_opd = array_filter((array)$dokumen, function($d) {
          return $d->role_uploader === 'opd_teknis';
      });
      $ada_opd = array_filter($dok_draft, function($d) { return !empty($d['path']); });
      ?>
      <div style="margin-bottom:12px">
        <div class="text-xs text-muted fw-500" style="margin-bottom:4px;text-transform:uppercase;letter-spacing:.4px">
          <i class="ti ti-folder"></i> Dokumen Pekerjaan (OPD Teknis)
        </div>
        <?php foreach ($dok_draft as $_dk_jenis => $dok): if (empty($dok['path'])) continue; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);
                    margin-bottom:6px;font-size:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="ti <?= icon_file($dok['path']) ?>" style="color:var(--biru);font-size:18px"></i>
            <div class="fw-500"><?= $dok['label'] ?></div>
          </div>
          <div class="aksi-row">
            <a href="<?= site_url('berkas/unduh/draft/'.$p->id.'/'.$_dk_jenis) ?>" target="_blank"
               class="btn btn-outline btn-xs"><i class="ti ti-eye"></i> Lihat</a>
            <a href="<?= site_url('berkas/unduh/draft/'.$p->id.'/'.$_dk_jenis) ?>"
               class="btn-icon" title="Unduh"><i class="ti ti-download"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php foreach ($dok_opd as $d): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);
                    margin-bottom:6px;font-size:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--biru);font-size:18px"></i>
            <div>
              <div class="fw-500"><?= label_jenis_dok($d->jenis_dokumen) ?></div>
              <div class="text-muted"><?= $d->ukuran_kb ?> KB · <?= htmlspecialchars($d->nama_uploader ?? '—') ?></div>
              <?php if ($d->keterangan): ?><div class="text-muted"><?= htmlspecialchars($d->keterangan) ?></div><?php endif; ?>
            </div>
          </div>
          <div class="aksi-row">
            <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>" target="_blank"
               class="btn btn-outline btn-xs"><i class="ti ti-eye"></i> Lihat</a>
            <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>"
               class="btn-icon" title="Unduh"><i class="ti ti-download"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($ada_opd) && empty($dok_opd)): ?>
        <div style="padding:10px 12px;background:var(--bg);border-radius:var(--radius);
                    font-size:12px;color:var(--text-muted);border:1px dashed var(--border)">
          <i class="ti ti-info-circle"></i> OPD Teknis belum mengupload dokumen pekerjaan (SPK, SPMK, BAST).
        </div>
        <?php endif; ?>
      </div>

      <!-- Dokumen yang diupload SKPKD -->
      <?php
      $dok_skpkd = array_filter((array)$dokumen, function($d) {
          return $d->role_uploader === 'skpkd_kabkota';
      });
      if (!empty($dok_skpkd)): ?>
      <div style="margin-bottom:12px">
        <div class="text-xs text-muted fw-500" style="margin-bottom:4px;text-transform:uppercase;letter-spacing:.4px">
          <i class="ti ti-paperclip"></i> Dokumen Pendukung (SKPKD)
        </div>
        <?php foreach ($dok_skpkd as $d): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);
                    margin-bottom:6px;font-size:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--ungu);font-size:18px"></i>
            <div>
              <div class="fw-500"><?= label_jenis_dok($d->jenis_dokumen) ?></div>
              <div class="text-muted"><?= $d->ukuran_kb ?> KB · <?= htmlspecialchars($d->nama_uploader ?? '—') ?></div>
              <?php if ($d->keterangan): ?>
              <div class="text-muted"><?= htmlspecialchars($d->keterangan) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="aksi-row">
            <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>" target="_blank"
               class="btn btn-outline btn-xs" title="Lihat/Preview">
              <i class="ti ti-eye"></i> Lihat
            </a>
            <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>"
               class="btn-icon" title="Unduh">
              <i class="ti ti-download"></i>
            </a>
            <?php if ($this->rbac->can('pekerjaan.upload_dok')
                  && in_array($tahapan->status,['skpkd_kab_verif','inspektorat_approved','skpkd_kab_revisi'])): ?>
            <a href="<?= site_url('verifikasi/kab/hapus-dok/'.$d->id) ?>"
               class="btn-icon del" data-confirm="Hapus dokumen ini?">
              <i class="ti ti-trash"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Upload Dokumen Baru (SKPKD) -->
      <?php if ($this->rbac->can('pekerjaan.upload_dok')
            && in_array($tahapan->status,['inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi'])): ?>
      <button class="btn btn-outline btn-sm" onclick="openModal('modalUploadDok')">
        <i class="ti ti-upload"></i> Upload Dokumen
      </button>

      <div id="modalUploadDok" class="modal-overlay"
           style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
                  z-index:999;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw">
          <div class="card-title"><i class="ti ti-upload"></i> Upload Dokumen Permohonan</div>
          <?= form_open_multipart(site_url('verifikasi/kab/upload-dok/'.$tahapan->id)) ?>
          <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
          <div class="form-group mb-2">
            <label>Jenis Dokumen <span class="req">*</span></label>
            <select name="jenis_dokumen" class="form-control" required>
              <option value="">-- Pilih --</option>
              <option value="surat_permohonan_pencairan">Surat Permohonan Pencairan</option>
              <option value="surat_pernyataan_bupati">Surat Pernyataan Bupati/Wali Kota</option>
              <option value="rekapitulasi_kegiatan">Rekapitulasi Kegiatan</option>
              <option value="dokumen_pekerjaan_kontrak">Dokumen Pekerjaan / Kontrak</option>
              <option value="daftar_pekerjaan">Daftar Pekerjaan</option>
              <option value="ba_kemajuan_pekerjaan">Berita Acara Kemajuan Pekerjaan</option>
              <option value="bast">BAST</option>
              <option value="foto_dokumentasi">Foto Dokumentasi</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group mb-2">
            <label>File <span class="req">*</span></label>
            <input type="file" name="file_dok" class="form-control"
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <div class="form-hint">Format: PDF, DOC, JPG, PNG. Maks. 10 MB.</div>
          </div>
          <div class="form-group mb-2">
            <label>Keterangan</label>
            <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
          </div>
          <div class="form-actions">
            <button type="button" onclick="closeModal('modalUploadDok')" class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Upload</button>
          </div>
          <?= form_close() ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Data Penyaluran (jika sudah disalurkan) -->
    <?php if (in_array($tahapan->status, ['disalurkan','dikonfirmasi'])): ?>
    <?php if ($penyaluran): ?>
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-cash"></i> Data Penyaluran Dana</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:42%">No. SP2D</td>
            <td class="mono fw-500"><?= htmlspecialchars($penyaluran->no_sp2d) ?></td></tr>
        <tr><td class="text-muted text-sm">Tanggal SP2D</td>
            <td><?= tgl_indo($penyaluran->tgl_sp2d) ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai Transfer</td>
            <td class="fw-500 text-biru"><?= rupiah($penyaluran->nilai_transfer) ?></td></tr>
        <tr><td class="text-muted text-sm">Rekening Asal (Provinsi)</td>
            <td class="text-sm"><?= htmlspecialchars($penyaluran->rek_asal ?? '—') ?>
              <span class="text-muted"><?= htmlspecialchars($penyaluran->nama_bank_asal ?? '') ?></span></td></tr>
        <tr><td class="text-muted text-sm">Rekening Tujuan (RKUD)</td>
            <td class="text-sm"><?= htmlspecialchars($penyaluran->rek_tujuan ?? '—') ?>
              <span class="text-muted"><?= htmlspecialchars($penyaluran->nama_bank_tujuan ?? '') ?></span></td></tr>
        <tr><td class="text-muted text-sm">Status Transfer</td>
            <td><?php
              $st_map = ['proses'=>['biru','Dalam Proses'],'selesai'=>['hijau','Selesai'],'gagal'=>['merah','Gagal']];
              $st = $st_map[$penyaluran->status_transfer] ?? ['abu',$penyaluran->status_transfer];
              echo '<span class="badge badge-'.$st[0].'">'.$st[1].'</span>';
            ?></td></tr>
        <?php if ($penyaluran->bukti_path && $penyaluran->bukti_id): ?>
        <tr><td class="text-muted text-sm">Bukti Transfer RKUD</td>
            <td><a href="<?= site_url('berkas/unduh/bukti/'.$penyaluran->bukti_id) ?>" target="_blank"
                   class="btn btn-outline btn-xs"><i class="ti ti-download"></i> Download Bukti</a></td></tr>
        <?php endif; ?>
      </table>

      <!-- Status konfirmasi penerimaan (dikonfirmasi via menu Penyaluran SKPKD Kab/Kota) -->
      <?php if ($tahapan->status === 'disalurkan'): ?>
      <div style="margin-top:10px;padding:10px;background:var(--abu-light);border-radius:var(--radius)">
        <i class="ti ti-info-circle"></i>
        Konfirmasi penerimaan dana (RKUD) dilakukan melalui menu
        <strong>Penyaluran</strong>.
      </div>
      <?php elseif ($tahapan->status === 'dikonfirmasi'): ?>
      <div style="margin-top:10px;padding:10px;background:var(--hijau-light);border-radius:var(--radius)">
        <i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i>
        <strong style="color:var(--hijau-mid)"> Dana telah dikonfirmasi diterima.</strong>
        <?php if ($penyaluran->bukti_path && $penyaluran->bukti_id): ?>
        <a href="<?= site_url('berkas/unduh/bukti/'.$penyaluran->bukti_id) ?>" target="_blank"
           class="btn btn-outline btn-xs" style="margin-left:8px">
          <i class="ti ti-download"></i> Bukti RKUD
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ── Kolom Kanan: Form Verifikasi + Timeline ── -->
  <div>
    <!-- Form Keputusan Verifikasi -->
    <?php if ($this->rbac->can('verif_kab.input')
          && in_array($tahapan->status,['inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi'])): ?>
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-gavel"></i> Keputusan Verifikasi</div>

      <?php if ($v && $v->hasil_verifikasi): ?>
      <?php $hmap=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
            $h=$hmap[$v->hasil_verifikasi]; ?>
      <div style="padding:10px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius);margin-bottom:14px">
        <div class="fw-500">Keputusan sebelumnya: <span class="badge badge-<?= $h[0] ?>"><?= $h[1] ?></span></div>
        <?php if ($v->catatan): ?>
        <div class="text-sm mt-1">Catatan: <?= htmlspecialchars($v->catatan) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?= form_open(site_url('verifikasi/kab/putuskan/'.($v ? $v->id : 0))) ?>
      <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>

      <div class="form-group mb-2">
        <label>Hasil Verifikasi <span class="req">*</span></label>
        <select name="hasil_verifikasi" class="form-control" required>
          <option value="">— Pilih keputusan —</option>
          <option value="disetujui"       <?= ($v && $v->hasil_verifikasi==='disetujui')?'selected':'' ?>>
            ✅ Disetujui — Kirim permohonan ke Provinsi
          </option>
          <option value="perlu_perbaikan" <?= ($v && $v->hasil_verifikasi==='perlu_perbaikan')?'selected':'' ?>>
            ⚠️ Perlu Perbaikan — Kembalikan ke OPD
          </option>
          <option value="ditolak"         <?= ($v && $v->hasil_verifikasi==='ditolak')?'selected':'' ?>>
            ❌ Ditolak
          </option>
        </select>
      </div>
      <div class="form-group mb-3">
        <label>Catatan <span class="text-xs text-muted">(wajib jika Perlu Perbaikan / Ditolak)</span></label>
        <textarea name="catatan" class="form-control" rows="3"
          placeholder="Uraikan temuan atau alasan keputusan..."><?= htmlspecialchars($v->catatan ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"
        onclick="return confirm('Simpan keputusan verifikasi ini?')">
        <i class="ti ti-gavel"></i> Simpan Keputusan
      </button>
      <?= form_close() ?>
    </div>
    <?php elseif ($v && $v->hasil_verifikasi): ?>
    <!-- Tampilkan keputusan jika sudah final -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-gavel"></i> Keputusan Verifikasi</div>
      <?php $hmap=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
            $h=$hmap[$v->hasil_verifikasi]; ?>
      <div style="padding:12px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius)">
        <div class="fw-500 mb-1"><span class="badge badge-<?= $h[0] ?>"><?= $h[1] ?></span></div>
        <?php if ($v->tgl_verifikasi): ?><div class="text-xs text-muted">Tanggal: <?= tgl_indo($v->tgl_verifikasi) ?></div><?php endif; ?>
        <?php if ($v->catatan): ?><div class="text-sm mt-1"><?= htmlspecialchars($v->catatan) ?></div><?php endif; ?>
        <?php if ($v->nama_ppkd): ?><div class="text-xs text-muted mt-1">TTD: <?= htmlspecialchars($v->nama_ppkd) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Timeline Status -->
    <div class="card">
      <div class="card-title"><i class="ti ti-timeline"></i> Riwayat Status</div>
      <?php if (!empty($history)): ?>
      <div style="position:relative;padding-left:4px">
        <?php foreach ($history as $h): ?>
        <div style="margin-bottom:12px;padding-left:16px;border-left:2px solid var(--border);position:relative">
          <div style="position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:var(--biru)"></div>
          <div style="display:flex;flex-wrap:wrap;align-items:center;gap:4px;margin-bottom:2px">
            <?php if ($h->status_lama): ?>
            <?= badge_status($h->status_lama) ?>
            <i class="ti ti-arrow-right" style="font-size:11px;color:var(--text-muted)"></i>
            <?php endif; ?>
            <?= badge_status($h->status_baru) ?>
          </div>
          <div class="text-xs text-muted"><?= htmlspecialchars($h->nama_user ?? 'Sistem') ?> · <?= htmlspecialchars($h->nama_role ?? '') ?></div>
          <?php if ($h->catatan): ?>
          <div class="text-xs" style="margin-top:2px"><?= htmlspecialchars($h->catatan) ?></div>
          <?php endif; ?>
          <div class="text-xs text-muted"><?= tgl_indo($h->created_at) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted text-sm">Belum ada riwayat.</p>
      <?php endif; ?>
    </div>
  </div>

</div>
