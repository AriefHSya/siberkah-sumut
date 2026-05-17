<div class="page-header">
  <div class="page-title"><i class="ti ti-database"></i> Data BKP TA <?= $filters['tahun'] ?></div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('parameter.bkp.manage')): ?>
    <a href="<?= site_url('parameter/bkp/import') ?>" class="btn btn-outline btn-sm"><i class="ti ti-file-import"></i> Import</a>
    <?php endif; ?>
    <a href="<?= site_url('parameter/bkp/cetak?tahun='.$filters['tahun'].'&kabkota_id='.$filters['kabkota_id']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="ti ti-printer"></i> Cetak</a>
    <?php if ($this->rbac->can('parameter.bkp.manage')): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('modalTambah')"><i class="ti ti-plus"></i> Tambah BKP</button>
    <?php endif; ?>
  </div>
</div>
<div class="g3 mb-2">
  <div class="stat-card"><div class="stat-val" style="color:var(--biru)"><?= $rekap->total??0 ?></div><div class="stat-label">Total BKP</div></div>
  <div class="stat-card"><div class="stat-val" style="font-size:16px;color:var(--hijau-mid)"><?= rupiah_juta($rekap->total_nilai??0) ?></div><div class="stat-label">Total Nilai</div></div>
  <div class="stat-card"><div class="stat-val" style="color:var(--kuning-mid)"><?= $rekap->total_kabkota??0 ?></div><div class="stat-label">Kab/Kota</div></div>
</div>
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Tahun</label><select name="tahun"><?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$filters['tahun'])?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label>Kab/Kota</label><select name="kabkota_id"><option value="">Semua</option><?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label>Cari Uraian</label><input type="text" name="q" value="<?= htmlspecialchars($filters['q']??'') ?>" placeholder="Uraian BKP..."></div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
    <a href="<?= site_url('parameter/bkp') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>
<div class="card">
  <div class="table-wrap"><table class="tbl">
    <thead><tr><th>Kode BKP</th><th>Kab/Kota</th><th>Uraian BKP</th><th>Bidang</th><th style="text-align:right">Nilai</th><?php if ($this->rbac->can('parameter.bkp.manage')): ?><th>Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!empty($list)): foreach ($list as $b): $ada_perubahan = ($b->nilai!=$b->nilai_awal); ?>
    <tr <?= $ada_perubahan?'style="background:#FFF8F0"':'' ?>>
      <td class="mono text-sm"><?= htmlspecialchars($b->kode_bkp) ?></td>
      <td class="text-sm"><?= htmlspecialchars($b->nama_kabkota) ?></td>
      <td class="text-sm"><?= htmlspecialchars($b->uraian_bkp) ?></td>
      <td><span class="badge badge-abu"><?= htmlspecialchars($b->nama_bidang) ?></span></td>
      <td style="text-align:right">
        <?php if ($ada_perubahan): ?><div class="text-xs" style="text-decoration:line-through;color:var(--text-muted)"><?= rupiah($b->nilai_awal) ?></div><div class="fw-500 text-hijau"><?= rupiah($b->nilai) ?></div><?php else: ?><?= rupiah($b->nilai) ?><?php endif; ?>
      </td>
      <?php if ($this->rbac->can('parameter.bkp.manage')): ?>
      <td><div class="aksi-row">
        <button class="btn-icon" onclick="editBkp(<?= $b->id ?>,'<?= htmlspecialchars($b->uraian_bkp,ENT_QUOTES) ?>',<?= $b->nilai ?>)" title="Edit"><i class="ti ti-edit"></i></button>
        <a href="<?= site_url('parameter/bkp/hapus/'.$b->id) ?>" class="btn-icon del" data-confirm="Hapus BKP <?= $b->kode_bkp ?>?"><i class="ti ti-trash"></i></a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; else: ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Belum ada data BKP.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>

<!-- Modal Tambah BKP -->
<div id="modalTambah" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:600px;max-width:95vw;max-height:90vh;overflow-y:auto">
    <div class="card-title"><i class="ti ti-plus-circle"></i> Tambah Data BKP</div>
    <?= form_open(site_url('parameter/bkp/simpan')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
    <div class="form-grid">
      <div class="form-group"><label>Kode BKP <span class="req">*</span></label><input type="text" name="kode_bkp" class="form-control mono" placeholder="BKP-2026-001" required></div>
      <div class="form-group"><label>Tahun <span class="req">*</span></label><select name="tahun" class="form-control"><?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$filters['tahun'])?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Kabupaten/Kota <span class="req">*</span></label><select name="kabkota_id" class="form-control" required><option value="">-- Pilih --</option><?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Bidang <span class="req">*</span></label><select name="bidang_id" class="form-control" required><option value="">-- Pilih --</option><?php foreach ($bidang_list as $b): ?><option value="<?= $b->id ?>"><?= $b->nama ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-group mt-1"><label>Uraian BKP <span class="req">*</span></label><input type="text" name="uraian_bkp" class="form-control" maxlength="500" required></div>
    <div class="form-group mt-1"><label>Nilai (Rp) <span class="req">*</span></label><input type="text" name="nilai" class="form-control rupiah-input" required></div>
    <div class="form-actions"><button type="button" onclick="closeModal('modalTambah')" class="btn btn-outline">Batal</button><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button></div>
    <?= form_close() ?>
  </div>
</div>

<!-- Modal Edit BKP -->
<div id="modalEdit" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:95vw">
    <div class="card-title"><i class="ti ti-edit"></i> Edit Data BKP</div>
    <?= form_open('',['id'=>'formEditBkp']) ?>
    <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
    <div class="form-group mb-2"><label>Uraian BKP <span class="req">*</span></label><input type="text" name="uraian_bkp" id="editUraian" class="form-control" required></div>
    <div class="form-group mb-2"><label>Nilai (Rp) <span class="req">*</span></label><input type="text" name="nilai" id="editNilai" class="form-control rupiah-input" required></div>
    <div class="form-actions"><button type="button" onclick="closeModal('modalEdit')" class="btn btn-outline">Batal</button><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button></div>
    <?= form_close() ?>
  </div>
</div>
<script>
function editBkp(id, uraian, nilai) {
  document.getElementById('editUraian').value = uraian;
  document.getElementById('editNilai').value  = parseInt(nilai).toLocaleString('id-ID');
  document.getElementById('formEditBkp').action = '<?= site_url('parameter/bkp/update/') ?>' + id;
  openModal('modalEdit');
}
</script>
