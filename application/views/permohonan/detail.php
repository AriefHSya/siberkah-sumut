<?php
function label_kelompok_det($jenis, $kode_tahap) {
    if ($jenis === 'bertahap') {
        return 'Bertahap — ' . ($kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    }
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}
$pm = $permohonan;

$is_tahap1 = ($pm->jenis_penyaluran === 'bertahap' && $pm->kode_tahap !== 'tahap_2');

// Total nilai = nilai_diajukan + nilai_belanja_pendukung per item
$total_kontrak   = array_sum(array_column((array)$items, 'nilai_kontrak'));
$total_tahap1    = $is_tahap1 ? array_sum(array_column((array)$items, 'nilai_diajukan')) : 0;
$total_pendukung = array_sum(array_column((array)$items, 'nilai_belanja_pendukung'));
$total_nilai     = array_sum(array_map(function($it) {
    return ($it->nilai_diajukan ?? 0) + ($it->nilai_belanja_pendukung ?? 0);
}, (array)$items));

$dok_types = [
    'surat_permohonan' => ['label'=>'Surat Permohonan Kepala Daerah', 'icon'=>'ti-file-text'],
    'surat_pernyataan' => ['label'=>'Surat Pernyataan Kepala Daerah', 'icon'=>'ti-file-certificate'],
    'rekap_kegiatan'   => ['label'=>'Rekapitulasi Kegiatan yang Diajukan', 'icon'=>'ti-table'],
];
?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-send"></i> <?= htmlspecialchars($pm->no_permohonan ?: 'Permohonan #'.$pm->id) ?></div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <span class="badge badge-biru"><?= label_kelompok_det($pm->jenis_penyaluran, $pm->kode_tahap) ?></span>
      <?= badge_status($pm->status) ?>
      <span class="text-xs text-muted"><?= htmlspecialchars($pm->nama_kabkota) ?> · TA <?= $pm->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('permohonan.create')): ?>
      <?php if ($pm->status === 'diajukan'): ?>
      <?= form_open(site_url('permohonan/batal/'.$pm->id)) ?>
      <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
      <button type="submit" class="btn btn-danger btn-sm"
        onclick="return confirm('Batalkan pengajuan permohonan ini? Permohonan akan ditandai Dibatalkan dan kegiatan di dalamnya dapat diajukan kembali melalui permohonan baru.')">
        <i class="ti ti-x"></i> Batalkan Pengajuan
      </button>
      <?= form_close() ?>
      <?php elseif ($pm->status === 'draft'):
            $dok_lengkap = !empty($pm->file_surat_permohonan_path)
                        && !empty($pm->file_surat_pernyataan_path)
                        && !empty($pm->file_rekap_kegiatan_path);
      ?>
      <?= form_open(site_url('permohonan/ajukan-kembali/'.$pm->id)) ?>
      <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
      <?php if ($dok_lengkap): ?>
      <button type="submit" class="btn btn-primary btn-sm"
        onclick="return confirm('Ajukan permohonan ini ke BKAD Provinsi?')">
        <i class="ti ti-send"></i> Ajukan ke Provinsi
      </button>
      <?php else: ?>
      <button type="button" class="btn btn-primary btn-sm" disabled
        title="Lengkapi seluruh dokumen sebelum mengajukan"
        style="opacity:.5;cursor:not-allowed">
        <i class="ti ti-send"></i> Ajukan ke Provinsi
      </button>
      <?php endif; ?>
      <?= form_close() ?>
      <?php endif; ?>
    <?php endif; ?>
    <a href="<?= site_url('permohonan/cetak/'.$pm->id) ?>" target="_blank"
       class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Rekap
    </a>
    <a href="<?= site_url('permohonan') ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="g2">

  <!-- Kolom Kiri: Detail Permohonan -->
  <div>
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-file-description"></i> Informasi Permohonan</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:44%">No. Permohonan</td>
            <td class="mono fw-500"><?= htmlspecialchars($pm->no_permohonan ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Tanggal</td>
            <td><?= $pm->tgl_permohonan ? tgl_indo($pm->tgl_permohonan) : '—' ?></td></tr>
        <tr><td class="text-muted text-sm">Kelompok</td>
            <td><span class="badge badge-biru"><?= label_kelompok_det($pm->jenis_penyaluran, $pm->kode_tahap) ?></span></td></tr>
        <tr><td class="text-muted text-sm">Kab/Kota</td>
            <td class="fw-500"><?= htmlspecialchars($pm->nama_kabkota) ?></td></tr>
        <tr><td class="text-muted text-sm">Tahun Anggaran</td>
            <td><?= $pm->tahun ?></td></tr>
        <tr><td class="text-muted text-sm">Jumlah Pekerjaan</td>
            <td class="fw-500"><?= count($items) ?> kegiatan</td></tr>
        <tr><td class="text-muted text-sm">Total Nilai Kontrak</td>
            <td class="fw-500"><?= rupiah($total_kontrak) ?></td></tr>
        <tr><td class="text-muted text-sm">Total Nilai Pendukung</td>
            <td class="fw-500"><?= rupiah($total_pendukung) ?></td></tr>
        <tr><td class="text-muted text-sm">Total Nilai Diajukan</td>
            <td class="fw-500 text-biru"><?= rupiah($total_nilai) ?></td></tr>
        <tr><td class="text-muted text-sm">Dibuat Oleh</td>
            <td class="text-sm"><?= htmlspecialchars($pm->nama_pembuat ?? '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Waktu Pengajuan</td>
            <td class="text-sm"><?= tgl_indo($pm->created_at) ?></td></tr>
        <?php if ($pm->catatan): ?>
        <tr><td class="text-muted text-sm">Catatan</td>
            <td class="text-sm"><?= htmlspecialchars($pm->catatan) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($pm->catatan_tolak)): ?>
        <tr><td class="text-muted text-sm">Catatan Penolakan</td>
            <td class="text-sm text-merah"><?= htmlspecialchars($pm->catatan_tolak) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- Status banner -->
    <?php if ($pm->status === 'diajukan'): ?>
    <div style="padding:12px 14px;background:var(--hijau-light);border-radius:var(--radius);
                border:1px solid var(--hijau-mid);font-size:13px">
      <i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i>
      <strong style="color:var(--hijau-mid)"> Permohonan telah diajukan kepada BKAD Provinsi.</strong>
      <div class="text-xs text-muted mt-1">Notifikasi telah dikirim ke Admin Provinsi.</div>
    </div>
    <?php elseif ($pm->status === 'draft'): ?>
    <div style="padding:12px 14px;background:var(--kuning-light);border-radius:var(--radius);
                border:1px solid var(--kuning-mid);font-size:13px">
      <i class="ti ti-info-circle" style="color:var(--kuning-mid)"></i>
      <strong style="color:var(--kuning-mid)"> Permohonan masih berstatus Draft.</strong>
      <div class="text-xs text-muted mt-1">Lengkapi dokumen di bawah, lalu klik "Ajukan ke Provinsi".</div>
    </div>
    <?php elseif ($pm->status === 'selesai'): ?>
    <div style="padding:12px 14px;background:var(--hijau-light);border-radius:var(--radius);
                border:1px solid var(--hijau-mid);font-size:13px">
      <i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i>
      <strong style="color:var(--hijau-mid)"> Permohonan selesai — dana telah dikonfirmasi diterima di RKUD.</strong>
      <div class="text-xs text-muted mt-1">Permohonan ini tidak dapat dibatalkan lagi.</div>
    </div>
    <?php elseif ($pm->status === 'batal'): ?>
    <div style="padding:12px 14px;background:var(--abu-light);border-radius:var(--radius);
                border:1px solid var(--abu);font-size:13px">
      <i class="ti ti-x" style="color:var(--abu)"></i>
      <strong style="color:var(--abu)"> Permohonan ini telah dibatalkan.</strong>
      <div class="text-xs text-muted mt-1">Kegiatan di dalamnya dapat diajukan kembali melalui permohonan baru.</div>
    </div>
    <?php elseif ($pm->status === 'ditolak'): ?>
    <div style="padding:12px 14px;background:var(--merah-light);border-radius:var(--radius);
                border:1px solid var(--merah-mid);font-size:13px">
      <i class="ti ti-circle-x" style="color:var(--merah-mid)"></i>
      <strong style="color:var(--merah-mid)"> Permohonan ini ditolak oleh BKAD Provinsi.</strong>
      <div class="text-xs text-muted mt-1">Kegiatan di dalamnya dapat diajukan kembali melalui permohonan baru.</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Kolom Kanan: Ringkasan Nilai -->
  <div>
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-chart-bar"></i> Ringkasan Nilai</div>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div class="stat-card" style="flex:1;min-width:120px">
          <div class="stat-val" style="color:var(--biru)"><?= count($items) ?></div>
          <div class="stat-label">Kegiatan</div>
        </div>
        <div class="stat-card" style="flex:1;min-width:120px">
          <div class="stat-val" style="font-size:15px;color:var(--teal-mid)"><?= rupiah_juta($total_nilai) ?></div>
          <div class="stat-label">Total Diajukan</div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Dokumen Permohonan (hanya tampil saat Draft) -->
<?php if ($pm->status === 'draft' && $this->rbac->can('permohonan.create')): ?>
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-paperclip"></i> Dokumen Permohonan</div>
  <p class="text-sm text-muted mb-2">Upload dokumen kelengkapan sebelum mengajukan ke BKAD Provinsi.</p>
  <?php foreach ($dok_types as $jenis => $info): ?>
  <?php $kolom = 'file_'.$jenis.'_path'; $file = $pm->$kolom ?? NULL; ?>
  <div style="display:flex;align-items:center;justify-content:space-between;
              padding:10px 12px;border:1px solid <?= $file ? 'var(--hijau-mid)' : 'var(--border)' ?>;
              border-radius:var(--radius);margin-bottom:8px;background:<?= $file ? 'var(--hijau-light)' : '#fff' ?>">
    <div style="display:flex;align-items:center;gap:10px">
      <i class="ti <?= $info['icon'] ?>" style="font-size:20px;color:<?= $file ? 'var(--hijau-mid)' : 'var(--text-muted)' ?>"></i>
      <div>
        <div class="fw-500 text-sm"><?= $info['label'] ?></div>
        <?php if ($file): ?>
        <div class="text-xs" style="color:var(--hijau-mid)"><i class="ti ti-check"></i> Dokumen tersedia</div>
        <?php else: ?>
        <div class="text-xs text-muted">Belum diupload</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="aksi-row">
      <?php if ($file): ?>
      <a href="<?= site_url('berkas/unduh/pm/'.$pm->id.'/'.$jenis) ?>" target="_blank" class="btn btn-outline btn-xs">
        <i class="ti ti-eye"></i> Lihat
      </a>
      <a href="<?= site_url('berkas/unduh/pm/'.$pm->id.'/'.$jenis) ?>" class="btn-icon" title="Unduh">
        <i class="ti ti-download"></i>
      </a>
      <?= form_open(site_url('permohonan/hapus-dok/'.$pm->id.'/'.$jenis)) ?>
      <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
      <button type="submit" class="btn-icon del" title="Hapus"
        onclick="return confirm('Hapus dokumen ini?')">
        <i class="ti ti-trash"></i>
      </button>
      <?= form_close() ?>
      <?php else: ?>
      <button class="btn btn-outline btn-xs" onclick="openUpload('<?= $jenis ?>')">
        <i class="ti ti-upload"></i> Upload
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Modal Upload per jenis -->
<?php foreach ($dok_types as $jenis => $info): ?>
<div id="modal-upload-<?= $jenis ?>" class="modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:440px;max-width:95vw">
    <div class="card-title"><i class="ti ti-upload"></i> Upload — <?= $info['label'] ?></div>
    <?= form_open_multipart(site_url('permohonan/upload-dok/'.$pm->id.'/'.$jenis)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group mb-2">
      <label>File <span class="req">*</span></label>
      <input type="file" name="file_dok" class="form-control"
             accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
      <div class="form-hint">Format: PDF, DOC, JPG, PNG. Maks. 10 MB.</div>
    </div>
    <div class="form-actions">
      <button type="button" onclick="closeUpload('<?= $jenis ?>')" class="btn btn-outline">Batal</button>
      <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Upload</button>
    </div>
    <?= form_close() ?>
  </div>
</div>
<?php endforeach; ?>

<script>
function openUpload(jenis) {
    var m = document.getElementById('modal-upload-' + jenis);
    if (m) { m.style.display = 'flex'; }
}
function closeUpload(jenis) {
    var m = document.getElementById('modal-upload-' + jenis);
    if (m) { m.style.display = 'none'; }
}
</script>
<?php endif; ?>

<!-- Tabel Pekerjaan -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <div class="card-title" style="margin:0"><i class="ti ti-list"></i> Daftar Pekerjaan</div>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:36px;text-align:center">No</th>
          <th>Kode BKP</th>
          <th>Nama Kegiatan</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <?php if ($is_tahap1): ?>
          <th style="text-align:right">Nilai Tahap I (50%)</th>
          <?php endif; ?>
          <th style="text-align:right">Nilai Pendukung</th>
          <th style="text-align:right"><?= $is_tahap1 ? 'Nilai Pengajuan' : 'Nilai Diajukan' ?></th>
          <th>No. Kontrak</th>
          <th>Penyedia</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($items)): $no = 1; foreach ($items as $item):
            $nilai_diajukan_item = ($item->nilai_diajukan ?? 0) + ($item->nilai_belanja_pendukung ?? 0);
      ?>
      <tr>
        <td class="center text-muted text-sm"><?= $no++ ?></td>
        <td>
          <div class="mono fw-500 text-sm"><?= htmlspecialchars($item->kode_bkp) ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($item->kode_tahap) ?></div>
        </td>
        <td class="text-sm"><?= htmlspecialchars($item->nama_kegiatan_dok ?: $item->uraian_bkp) ?></td>
        <td class="fw-500 text-sm" style="text-align:right"><?= rupiah($item->nilai_kontrak) ?></td>
        <?php if ($is_tahap1): ?>
        <td class="fw-500 text-sm" style="text-align:right;color:var(--teal-mid)"><?= rupiah($item->nilai_diajukan ?? 0) ?></td>
        <?php endif; ?>
        <td class="text-sm" style="text-align:right;color:var(--text-muted)">
          <?= ($item->nilai_belanja_pendukung ?? 0) > 0 ? rupiah($item->nilai_belanja_pendukung) : '—' ?>
        </td>
        <td class="fw-500 text-sm" style="text-align:right;color:var(--biru)">
          <?= rupiah($nilai_diajukan_item) ?>
          <?php if (!$is_tahap1): ?><div class="text-xs text-muted"><?= $item->persen_nilai ?>%</div><?php endif; ?>
        </td>
        <td class="mono text-xs"><?= htmlspecialchars($item->no_dok_pekerjaan ?: '—') ?></td>
        <td class="text-xs"><?= htmlspecialchars($item->nama_penyedia ?: '—') ?></td>
        <td><?= badge_status($item->pekerjaan_status) ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="<?= $is_tahap1 ? 10 : 9 ?>" class="text-center text-muted" style="padding:30px">Tidak ada data.</td></tr>
      <?php endif; ?>
      </tbody>
      <?php if (!empty($items) && count($items) > 1): ?>
      <tfoot>
        <tr style="background:var(--bg)">
          <td colspan="3" class="fw-500 text-sm text-right" style="padding:8px 7px">Total</td>
          <td class="fw-500 text-sm text-right"><?= rupiah($total_kontrak) ?></td>
          <?php if ($is_tahap1): ?>
          <td class="fw-500 text-sm text-right" style="color:var(--teal-mid)"><?= rupiah($total_tahap1) ?></td>
          <?php endif; ?>
          <td class="fw-500 text-sm text-right text-muted"><?= rupiah($total_pendukung) ?></td>
          <td class="fw-500 text-sm text-right text-biru"><?= rupiah($total_nilai) ?></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
