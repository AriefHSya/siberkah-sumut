<?php
function label_kel_pm($jenis, $kode_tahap) {
    if ($jenis === 'bertahap')
        return 'Bertahap — ' . ($kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}

$dok_types = [
    'surat_permohonan' => 'Surat Permohonan KDH',
    'surat_pernyataan' => 'Surat Pernyataan KDH',
    'rekap_kegiatan'   => 'Rekapitulasi Kegiatan',
];

// Status persetujuan per item
$total_items    = count($items);
$verified_items = count(array_filter((array)$items, function($it) {
    return $it->hasil_verif_prov === 'disetujui';
}));
$semua_verified = $total_items > 0 && $verified_items === $total_items;

// Status nota/ringkasan sudah digenerate
$nota_kabid_ok  = !empty($pm->nota_kabid_at);
$nota_kabadan_ok= !empty($pm->nota_kabadan_at);
$ringkasan_ok   = !empty($pm->ringkasan_at);
$semua_dok_ok   = $nota_kabid_ok && $nota_kabadan_ok && $ringkasan_ok;

// Status SP2D
$sp2d_ada = !empty($pm->no_sp2d);
?>

<div class="page-header">
  <div>
    <div class="page-title">
      <i class="ti ti-file-description"></i>
      <?= htmlspecialchars($pm->no_permohonan ?: 'Permohonan #'.$pm->id) ?>
    </div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <?= badge_jenis($pm->jenis_penyaluran) ?>
      <span class="badge badge-biru"><?= label_kel_pm($pm->jenis_penyaluran, $pm->kode_tahap) ?></span>
      <span class="badge badge-hijau">Diajukan</span>
      <span class="text-xs text-muted"><?= htmlspecialchars($pm->nama_kabkota) ?> · TA <?= $pm->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('verif_prov.approve') && $verified_items === 0): ?>
    <button type="button" class="btn btn-danger btn-sm" onclick="openTolakPermohonan()">
      <i class="ti ti-x"></i> Tolak Permohonan
    </button>
    <?php endif; ?>
    <a href="<?= site_url('verifikasi/prov') ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<?php if ($this->rbac->can('verif_prov.approve') && $verified_items === 0): ?>
<div id="modal-tolak-pm" class="modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:440px;max-width:95vw">
    <div class="card-title"><i class="ti ti-x"></i> Tolak Permohonan</div>
    <?= form_open(site_url('verifikasi/prov/permohonan/tolak/'.$pm->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group mb-2">
      <label>Catatan Alasan Penolakan <span class="req">*</span></label>
      <textarea name="catatan_tolak" class="form-control" rows="4" required
                placeholder="Jelaskan alasan penolakan permohonan ini..."></textarea>
      <div class="form-hint">Seluruh kegiatan dalam permohonan ini akan dapat diajukan kembali melalui permohonan baru oleh SKPKD Kab/Kota.</div>
    </div>
    <div class="form-actions">
      <button type="button" onclick="closeTolakPermohonan()" class="btn btn-outline">Batal</button>
      <button type="submit" class="btn btn-danger"
        onclick="return confirm('Tolak permohonan ini? Aksi ini tidak dapat dibatalkan.')">
        <i class="ti ti-x"></i> Tolak Permohonan
      </button>
    </div>
    <?= form_close() ?>
  </div>
</div>
<script>
function openTolakPermohonan() {
    var m = document.getElementById('modal-tolak-pm');
    if (m) { m.style.display = 'flex'; }
}
function closeTolakPermohonan() {
    var m = document.getElementById('modal-tolak-pm');
    if (m) { m.style.display = 'none'; }
}
</script>
<?php endif; ?>

<div class="g2 mb-2">

  <!-- Info Permohonan -->
  <div class="card">
    <div class="card-title"><i class="ti ti-info-circle"></i> Informasi Permohonan</div>
    <table class="tbl">
      <tr>
        <td class="text-muted text-sm" style="width:44%">No. Permohonan</td>
        <td class="mono fw-500"><?= htmlspecialchars($pm->no_permohonan ?: '—') ?></td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Tanggal Diajukan</td>
        <td><?= tgl_indo($pm->created_at) ?></td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Kelompok</td>
        <td><span class="badge badge-biru"><?= label_kel_pm($pm->jenis_penyaluran, $pm->kode_tahap) ?></span></td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Kab/Kota</td>
        <td class="fw-500"><?= htmlspecialchars($pm->nama_kabkota) ?></td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Dibuat Oleh</td>
        <td class="text-sm"><?= htmlspecialchars($pm->nama_pembuat ?? '—') ?></td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Jumlah Kegiatan</td>
        <td class="fw-500"><?= count($items) ?> kegiatan</td>
      </tr>
      <tr>
        <td class="text-muted text-sm">Total Nilai Kontrak</td>
        <td><?= rupiah($total_kontrak) ?></td>
      </tr>
      <?php if ($is_tahap1): ?>
      <tr>
        <td class="text-muted text-sm">Total Nilai Tahap I (50%)</td>
        <td style="color:var(--teal-mid)"><?= rupiah($total_tahap1) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="text-muted text-sm">Total Nilai Pendukung</td>
        <td><?= rupiah($total_pendukung) ?></td>
      </tr>
      <tr>
        <td class="text-muted text-sm fw-500">Total Nilai Pengajuan</td>
        <td class="fw-500 text-biru"><?= rupiah($total_nilai) ?></td>
      </tr>
      <?php if ($pm->catatan): ?>
      <tr>
        <td class="text-muted text-sm">Catatan</td>
        <td class="text-sm"><?= htmlspecialchars($pm->catatan) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- Dokumen Permohonan -->
  <div class="card">
    <div class="card-title"><i class="ti ti-paperclip"></i> Dokumen Permohonan</div>
    <?php foreach ($dok_types as $jenis => $label):
          $kolom = 'file_'.$jenis.'_path';
          $file  = $pm->$kolom ?? NULL;
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:10px 12px;border:1px solid <?= $file ? 'var(--hijau-mid)' : 'var(--border)' ?>;
                border-radius:var(--radius);margin-bottom:8px;
                background:<?= $file ? 'var(--hijau-light)' : 'var(--bg)' ?>">
      <div style="display:flex;align-items:center;gap:10px">
        <i class="ti ti-file-text" style="font-size:18px;color:<?= $file ? 'var(--hijau-mid)' : 'var(--text-muted)' ?>"></i>
        <div>
          <div class="fw-500 text-sm"><?= $label ?></div>
          <?php if ($file): ?>
          <div class="text-xs" style="color:var(--hijau-mid)"><i class="ti ti-check"></i> Tersedia</div>
          <?php else: ?>
          <div class="text-xs text-muted">Tidak tersedia</div>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($file): ?>
      <div class="aksi-row">
        <a href="<?= site_url('berkas/unduh/pm/'.$pm->id.'/'.$jenis) ?>" target="_blank" class="btn btn-outline btn-xs">
          <i class="ti ti-eye"></i> Lihat
        </a>
        <a href="<?= site_url('berkas/unduh/pm/'.$pm->id.'/'.$jenis) ?>" class="btn-icon" title="Unduh">
          <i class="ti ti-download"></i>
        </a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- Card Nota & Ringkasan — muncul jika semua item sudah diverifikasi -->
<?php if ($semua_verified && $this->rbac->can('verif_prov.approve')): ?>
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-file-download"></i> Dokumen Penyaluran</div>
  <?php if (!$semua_dok_ok): ?>
  <div class="alert alert-warning mb-2" style="font-size:13px">
    <i class="ti ti-info-circle"></i>
    <div>Download seluruh dokumen di bawah sebelum menginput SP2D. SP2D akan tersedia setelah semua dokumen digenerate.</div>
  </div>
  <?php endif; ?>

  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:4px">
    <!-- Nota 1: Kabid Anggaran → Kepala Badan -->
    <a href="<?= site_url('verifikasi/prov/permohonan/nota-kabid/'.$pm->id) ?>" target="_blank"
       style="flex:1;min-width:200px;display:flex;align-items:center;gap:10px;padding:12px 14px;
              border:1px solid <?= $nota_kabid_ok ? 'var(--hijau-mid)' : 'var(--border)' ?>;
              border-radius:var(--radius);background:<?= $nota_kabid_ok ? 'var(--hijau-light)' : '#fff' ?>;
              text-decoration:none;color:inherit">
      <i class="ti ti-file-text" style="font-size:22px;color:<?= $nota_kabid_ok ? 'var(--hijau-mid)' : 'var(--biru)' ?>"></i>
      <div style="flex:1">
        <div class="fw-500 text-sm">Nota Kabid Anggaran</div>
        <div class="text-xs text-muted">Kepada Kepala Badan · Ttd Kabid Anggaran</div>
        <?php if ($nota_kabid_ok): ?>
        <div class="text-xs" style="color:var(--hijau-mid)"><i class="ti ti-check"></i> <?= tgl_indo(substr($pm->nota_kabid_at,0,10)) ?></div>
        <?php endif; ?>
      </div>
      <i class="ti ti-download" style="color:var(--biru)"></i>
    </a>

    <!-- Nota 2: Kepala Badan → Bendahara -->
    <a href="<?= site_url('verifikasi/prov/permohonan/nota-kabadan/'.$pm->id) ?>" target="_blank"
       style="flex:1;min-width:200px;display:flex;align-items:center;gap:10px;padding:12px 14px;
              border:1px solid <?= $nota_kabadan_ok ? 'var(--hijau-mid)' : 'var(--border)' ?>;
              border-radius:var(--radius);background:<?= $nota_kabadan_ok ? 'var(--hijau-light)' : '#fff' ?>;
              text-decoration:none;color:inherit">
      <i class="ti ti-file-text" style="font-size:22px;color:<?= $nota_kabadan_ok ? 'var(--hijau-mid)' : 'var(--biru)' ?>"></i>
      <div style="flex:1">
        <div class="fw-500 text-sm">Nota Kepala Badan</div>
        <div class="text-xs text-muted">Kepada Bendahara Pengeluaran · Ttd Kepala Badan</div>
        <?php if ($nota_kabadan_ok): ?>
        <div class="text-xs" style="color:var(--hijau-mid)"><i class="ti ti-check"></i> <?= tgl_indo(substr($pm->nota_kabadan_at,0,10)) ?></div>
        <?php endif; ?>
      </div>
      <i class="ti ti-download" style="color:var(--biru)"></i>
    </a>

    <!-- Ringkasan Penyaluran -->
    <a href="<?= site_url('verifikasi/prov/permohonan/ringkasan/'.$pm->id) ?>" target="_blank"
       style="flex:1;min-width:200px;display:flex;align-items:center;gap:10px;padding:12px 14px;
              border:1px solid <?= $ringkasan_ok ? 'var(--hijau-mid)' : 'var(--border)' ?>;
              border-radius:var(--radius);background:<?= $ringkasan_ok ? 'var(--hijau-light)' : '#fff' ?>;
              text-decoration:none;color:inherit">
      <i class="ti ti-table" style="font-size:22px;color:<?= $ringkasan_ok ? 'var(--hijau-mid)' : 'var(--biru)' ?>"></i>
      <div style="flex:1">
        <div class="fw-500 text-sm">Ringkasan Penyaluran</div>
        <div class="text-xs text-muted">Ttd Kabid Anggaran</div>
        <?php if ($ringkasan_ok): ?>
        <div class="text-xs" style="color:var(--hijau-mid)"><i class="ti ti-check"></i> <?= tgl_indo(substr($pm->ringkasan_at,0,10)) ?></div>
        <?php endif; ?>
      </div>
      <i class="ti ti-download" style="color:var(--biru)"></i>
    </a>
  </div>
</div>

<!-- Card SP2D — hanya muncul setelah semua nota/ringkasan di-download -->
<?php if ($semua_dok_ok): ?>
<div class="card mb-2" style="border-color:var(--teal-mid)">
  <div class="card-title" style="color:var(--teal-mid)">
    <i class="ti ti-file-invoice"></i> SP2D Penyaluran
    <?php if ($sp2d_ada && $pm->status_sp2d === 'selesai'): ?>
    <span class="badge badge-hijau" style="margin-left:auto">Dana Disalurkan ✓</span>
    <?php endif; ?>
  </div>

  <?php if ($sp2d_ada): ?>
  <div style="padding:12px;background:var(--teal-light);border-radius:var(--radius);margin-bottom:14px">
    <div class="text-xs text-muted mb-1">SP2D Tersimpan</div>
    <div class="g4 text-sm">
      <div><span class="text-muted">No. SP2D</span><br><strong class="mono"><?= htmlspecialchars($pm->no_sp2d) ?></strong></div>
      <div><span class="text-muted">Tanggal</span><br><strong><?= tgl_indo($pm->tgl_sp2d) ?></strong></div>
      <div><span class="text-muted">Nilai Transfer</span><br><strong class="text-biru"><?= rupiah($pm->nilai_sp2d) ?></strong></div>
      <div><span class="text-muted">Status</span><br>
        <?php $stm=['proses'=>['biru','Dalam Proses'],'selesai'=>['hijau','Selesai'],'gagal'=>['merah','Gagal']];
              $s=$stm[$pm->status_sp2d]??['abu','—'];
              echo '<span class="badge badge-'.$s[0].'">'.$s[1].'</span>'; ?>
      </div>
    </div>
    <?php if ($pm->rek_asal || $pm->rek_tujuan): ?>
    <div class="text-xs text-muted mt-2">
      <?= htmlspecialchars($pm->nama_bank_asal ?? 'Bank Sumut') ?>
      (<?= htmlspecialchars($pm->rek_asal ?? '—') ?>) →
      <?= htmlspecialchars($pm->nama_bank_tujuan ?? '—') ?>
      (<?= htmlspecialchars($pm->rek_tujuan ?? '—') ?>)
    </div>
    <?php endif; ?>
  </div>
  <p class="text-xs text-muted mb-2">Update data SP2D jika ada perubahan:</p>
  <?php endif; ?>

  <?= form_open(site_url('verifikasi/prov/permohonan/sp2d/'.$pm->id)) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="form-grid mb-2">
    <div class="form-group">
      <label>No. SP2D <span class="req">*</span></label>
      <input type="text" name="no_sp2d" class="form-control mono"
             value="<?= htmlspecialchars($pm->no_sp2d ?? '') ?>"
             placeholder="Nomor SP2D" required>
    </div>
    <div class="form-group">
      <label>Tanggal SP2D <span class="req">*</span></label>
      <input type="date" name="tgl_sp2d" class="form-control"
             value="<?= htmlspecialchars($pm->tgl_sp2d ?? date('Y-m-d')) ?>" required>
    </div>
    <div class="form-group">
      <label>Nilai Transfer (Rp) <span class="req">*</span></label>
      <input type="text" name="nilai_sp2d" class="form-control rupiah-input"
             value="<?= $pm->nilai_sp2d ? number_format($pm->nilai_sp2d,0,',','.') : number_format($total_nilai,0,',','.') ?>"
             placeholder="<?= rupiah($total_nilai) ?>" required>
      <div class="form-hint">Total nilai pengajuan: <?= rupiah($total_nilai) ?></div>
    </div>
    <div class="form-group">
      <label>Status Transfer</label>
      <select name="status_sp2d" class="form-control">
        <option value="proses"  <?= (!$sp2d_ada || $pm->status_sp2d === 'proses')  ? 'selected' : '' ?>>Dalam Proses</option>
        <option value="selesai" <?= ($sp2d_ada && $pm->status_sp2d === 'selesai') ? 'selected' : '' ?>>Selesai ✓</option>
        <option value="gagal"   <?= ($sp2d_ada && $pm->status_sp2d === 'gagal')   ? 'selected' : '' ?>>Gagal ✗</option>
      </select>
    </div>
  </div>
  <div class="form-grid mb-3">
    <div class="form-group">
      <label>Rekening Asal (RKUD Provinsi)</label>
      <input type="text" name="rek_asal" class="form-control mono"
             value="<?= htmlspecialchars($pm->rek_asal ?? '') ?>"
             placeholder="No. rekening RKUD Provinsi">
    </div>
    <div class="form-group">
      <label>Bank Asal</label>
      <input type="text" name="nama_bank_asal" class="form-control"
             value="<?= htmlspecialchars($pm->nama_bank_asal ?? 'Bank Sumut') ?>">
    </div>
    <div class="form-group">
      <label>Rekening Tujuan (RKUD <?= htmlspecialchars($pm->nama_kabkota) ?>)</label>
      <input type="text" name="rek_tujuan" class="form-control mono"
             value="<?= htmlspecialchars($pm->rek_tujuan ?? '') ?>"
             placeholder="No. rekening RKUD Kab/Kota">
    </div>
    <div class="form-group">
      <label>Bank Tujuan</label>
      <input type="text" name="nama_bank_tujuan" class="form-control"
             value="<?= htmlspecialchars($pm->nama_bank_tujuan ?? '') ?>"
             placeholder="Bank Sumut / BRI / dll">
    </div>
  </div>
  <button type="submit" class="btn btn-primary"
    onclick="return confirm('Simpan data SP2D ini?')">
    <i class="ti ti-device-floppy"></i>
    <?= $sp2d_ada ? 'Update Data SP2D' : 'Simpan SP2D & Proses Penyaluran' ?>
  </button>
  <?= form_close() ?>
</div>
<?php endif; // semua_dok_ok ?>
<?php endif; // semua_verified ?>

<!-- Tabel Kegiatan -->
<div class="card">
  <div class="card-title"><i class="ti ti-list"></i> Daftar Kegiatan dalam Permohonan</div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:32px;text-align:center">No</th>
          <th style="width:120px">Kode BKP</th>
          <th>Nama Kegiatan</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <?php if ($is_tahap1): ?>
          <th style="text-align:right">Nilai Tahap I (50%)</th>
          <?php endif; ?>
          <th style="text-align:right">Nilai Pendukung</th>
          <th style="text-align:right">Nilai Pengajuan</th>
          <th style="width:110px">No. SP2D</th>
          <th style="width:120px">Status</th>
          <th style="width:90px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($items)): $no = 1; foreach ($items as $item):
            // Tahap II: 50% × NK = nilai_diajukan (pendukung tidak termasuk)
            $nilai_pengajuan = $is_tahap2
                ? ($item->nilai_diajukan ?? 0)
                : ($item->nilai_diajukan ?? 0) + ($item->nilai_belanja_pendukung ?? 0);

            $st = $item->tahapan_status;
            if ($st === 'dikonfirmasi')       { $badge = ['hijau','Dikonfirmasi']; $btn_label = 'Detail'; $btn_cls = 'btn-outline'; }
            elseif ($st === 'disalurkan')     { $badge = ['teal','Disalurkan'];    $btn_label = 'Detail'; $btn_cls = 'btn-outline'; }
            elseif ($st === 'skpkd_prov_verif') { $badge = ['kuning','Diverifikasi']; $btn_label = 'Lanjut'; $btn_cls = 'btn-primary'; }
            elseif ($st === 'skpkd_prov_revisi') { $badge = ['oranye','Dikembalikan']; $btn_label = 'Detail'; $btn_cls = 'btn-outline'; }
            else                              { $badge = ['biru','Menunggu'];      $btn_label = 'Verifikasi'; $btn_cls = 'btn-primary'; }
      ?>
      <tr>
        <td class="center text-muted text-sm"><?= $no++ ?></td>
        <td>
          <div class="mono fw-500 text-sm"><?= htmlspecialchars($item->kode_bkp) ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($item->label_tahap ?? $item->kode_tahap) ?></div>
        </td>
        <td class="text-sm">
          <?= htmlspecialchars($item->nama_kegiatan_dok ?: $item->uraian_bkp) ?>
          <?php if ($item->nama_penyedia): ?>
          <div class="text-xs text-muted"><?= htmlspecialchars($item->nama_penyedia) ?></div>
          <?php endif; ?>
        </td>
        <td class="text-sm fw-500" style="text-align:right"><?= rupiah($item->nilai_kontrak) ?></td>
        <?php if ($is_tahap1): ?>
        <td class="text-sm" style="text-align:right;color:var(--teal-mid)"><?= rupiah($item->nilai_diajukan ?? 0) ?></td>
        <?php endif; ?>
        <td class="text-sm" style="text-align:right;color:var(--text-muted)">
          <?= ($item->nilai_belanja_pendukung ?? 0) > 0 ? rupiah($item->nilai_belanja_pendukung) : '—' ?>
        </td>
        <td class="text-sm fw-500" style="text-align:right;color:var(--biru)"><?= rupiah($nilai_pengajuan) ?></td>
        <td class="text-xs mono">
          <?php if ($item->no_sp2d): ?>
          <div class="fw-500"><?= htmlspecialchars($item->no_sp2d) ?></div>
          <div class="text-muted"><?= $item->tgl_sp2d ? tgl_short($item->tgl_sp2d) : '' ?></div>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge badge-<?= $badge[0] ?>"><?= $badge[1] ?></span>
          <?php if ($item->hasil_verif_prov === 'disetujui' && !$item->no_sp2d): ?>
          <div class="text-xs text-kuning-mid mt-1">Menunggu SP2D</div>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?= site_url('verifikasi/prov/form/'.$item->tahapan_id) ?>"
             class="btn <?= $btn_cls ?> btn-xs">
            <i class="ti ti-<?= $btn_label === 'Verifikasi' ? 'shield-check' : ($btn_label === 'Lanjut' ? 'arrow-right' : 'eye') ?>"></i>
            <?= $btn_label ?>
          </a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="<?= $is_tahap1 ? 10 : 9 ?>" class="text-center text-muted" style="padding:30px">
          Tidak ada data kegiatan.
        </td>
      </tr>
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
