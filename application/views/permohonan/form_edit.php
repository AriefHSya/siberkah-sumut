<?php
$pm = $permohonan;
function label_kel_edit($jenis, $kode) {
    if ($jenis === 'bertahap') return 'Bertahap — ' . ($kode === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}
?>

<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-edit"></i> Edit Permohonan</div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <span class="badge badge-biru"><?= label_kel_edit($pm->jenis_penyaluran, $pm->kode_tahap) ?></span>
      <?= badge_status($pm->status) ?>
      <span class="text-xs text-muted"><?= htmlspecialchars($pm->nama_kabkota) ?> · TA <?= $pm->tahun ?></span>
    </div>
  </div>
  <div class="aksi-row">
    <a href="<?= site_url('permohonan/detail/'.$pm->id) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div style="max-width:560px">
  <div class="card">
    <div class="card-title"><i class="ti ti-file-description"></i> Informasi Permohonan</div>

    <?= form_open(site_url('permohonan/update/'.$pm->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group">
      <label class="form-label">Nomor Permohonan <span class="text-merah">*</span></label>
      <input type="text" name="no_permohonan" class="form-control"
             value="<?= htmlspecialchars($pm->no_permohonan ?? '') ?>"
             placeholder="Contoh: 900/123/BKAD/2026" required>
    </div>

    <div class="form-group">
      <label class="form-label">Tanggal Permohonan <span class="text-merah">*</span></label>
      <input type="date" name="tgl_permohonan" class="form-control"
             value="<?= htmlspecialchars($pm->tgl_permohonan ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label">Keterangan <span class="text-muted text-xs">(opsional)</span></label>
      <textarea name="catatan" class="form-control" rows="3"
                placeholder="Catatan atau keterangan tambahan..."><?= htmlspecialchars($pm->catatan ?? '') ?></textarea>
    </div>

    <div style="display:flex;gap:8px;margin-top:8px">
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> Simpan Perubahan
      </button>
      <a href="<?= site_url('permohonan/detail/'.$pm->id) ?>" class="btn btn-outline">
        Batal
      </a>
    </div>

    <?= form_close() ?>
  </div>
</div>
