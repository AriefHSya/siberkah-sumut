<div class="page-header"><div class="page-title"><i class="ti ti-building-community"></i> Data Umum Pemda</div></div>
<div class="alert alert-info mb-2"><i class="ti ti-info-circle"></i><div>Pilih Kabupaten/Kota dan Tahun Anggaran untuk melihat dan mengisi data pejabat serta dokumen perda/pergub.</div></div>
<div class="card mb-2">
  <div class="filter-row">
    <div class="filter-group"><label>Tahun</label><select class="fc fc-sm" onchange="location='<?= site_url('parameter/pemda?') ?>kabkota_id=<?= $kabkota_sel ?>&tahun='+this.value"><?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun_sel)?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?></select></div>
    <div class="filter-group"><label>Kabupaten/Kota</label><select class="fc fc-sm" onchange="location='<?= site_url('parameter/pemda?tahun='.$tahun_sel.'&kabkota_id=') ?>'+this.value"><option value="">-- Pilih --</option><?php foreach ($kabkota_list as $k): ?><option value="<?= $k->id ?>" <?= ($k->id==$kabkota_sel)?'selected':'' ?>><?= $k->nama ?></option><?php endforeach; ?></select></div>
  </div>
</div>
<?php if (!$kabkota_sel): ?>
<div class="card" style="text-align:center;padding:40px"><p class="text-muted">Pilih Kabupaten/Kota untuk menampilkan data.</p></div>
<?php else: ?>
<div class="g2">
  <div class="card"><div class="card-title"><i class="ti ti-users"></i> Data Pejabat TA <?= $tahun_sel ?></div>
    <?php $jenis_map=['kepala_daerah'=>'Kepala Daerah','kepala_bkad'=>'Kepala BKAD','inspektur'=>'Inspektur']; ?>
    <?php foreach (['kepala_daerah','kepala_bkad','inspektur'] as $j): $pj=$pejabat[$j]??NULL; ?>
    <div style="padding:12px;background:var(--bg);border-radius:var(--radius);margin-bottom:8px">
      <div class="text-xs text-muted fw-500"><?= $jenis_map[$j] ?></div>
      <div class="fw-500 text-sm"><?= $pj ? htmlspecialchars($pj->nama) : '<span class="text-muted">Belum diisi</span>' ?></div>
      <?php if ($pj&&$pj->nip): ?><div class="text-xs text-muted mono"><?= $pj->nip ?></div><?php endif; ?>
      <?php if ($this->rbac->can('parameter.pemda.manage')): ?>
      <button class="btn btn-outline btn-xs mt-1" onclick="editPej('<?= $j ?>','<?= $jenis_map[$j] ?>',<?= $kabkota_sel ?>,<?= $tahun_sel ?>,'<?= htmlspecialchars($pj->nama??'',ENT_QUOTES) ?>','<?= htmlspecialchars($pj->nip??'',ENT_QUOTES) ?>','<?= htmlspecialchars($pj->jabatan??'',ENT_QUOTES) ?>','<?= htmlspecialchars($pj->pangkat??'',ENT_QUOTES) ?>')"><i class="ti ti-edit"></i> <?= $pj ? 'Edit' : 'Isi Data' ?></button>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card"><div class="card-title"><i class="ti ti-file-certificate"></i> Perda/Pergub TA <?= $tahun_sel ?></div>
    <?php $dk_map=['perda_apbd'=>'Perda APBD','perda_apbd_p'=>'Perda APBD-P','perkada_bkp'=>'Perkada BKP','pergub_bkp'=>'Pergub BKP','lainnya'=>'Lainnya']; ?>
    <?php if (!empty($dokumen)): foreach ($dokumen as $d): ?>
    <div style="padding:10px;background:var(--bg);border-radius:var(--radius);margin-bottom:6px">
      <div class="text-xs text-muted"><?= $dk_map[$d->jenis]??$d->jenis ?></div>
      <div class="fw-500 text-sm"><?= htmlspecialchars($d->nomor) ?></div>
      <div class="text-xs text-muted"><?= tgl_indo($d->tanggal) ?></div>
      <?php if ($this->rbac->can('parameter.pemda.manage')): ?>
      <a href="<?= site_url('parameter/pemda/hapus-dokumen/'.$d->id) ?>" class="btn btn-outline btn-xs mt-1 del" data-confirm="Hapus dokumen ini?"><i class="ti ti-trash"></i></a>
      <?php endif; ?>
    </div>
    <?php endforeach; else: ?><p class="text-muted text-sm">Belum ada dokumen.</p><?php endif; ?>
    <?php if ($this->rbac->can('parameter.pemda.manage')): ?>
    <button class="btn btn-primary btn-xs mt-1" onclick="openModal('modalDok')"><i class="ti ti-plus"></i> Tambah Dokumen</button>
    <?php endif; ?>
  </div>
</div>
<!-- Modal Pejabat -->
<div id="modalPej" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw">
    <div class="card-title" id="pejTitle"><i class="ti ti-edit"></i></div>
    <?= form_open(site_url('parameter/pemda/simpan-pejabat')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
    <input type="hidden" name="kabkota_id" value="<?= $kabkota_sel ?>"><input type="hidden" name="tahun" value="<?= $tahun_sel ?>"><input type="hidden" name="jenis" id="pejJenis">
    <div class="form-group mb-2"><label>Nama <span class="req">*</span></label><input type="text" name="nama" id="pejNama" class="form-control" required></div>
    <div class="form-group mb-2"><label>NIP</label><input type="text" name="nip" id="pejNip" class="form-control"></div>
    <div class="form-group mb-2"><label>Jabatan</label><input type="text" name="jabatan" id="pejJabatan" class="form-control"></div>
    <div class="form-group mb-2"><label>Pangkat / Golongan</label><input type="text" name="pangkat" id="pejPangkat" class="form-control"></div>
    <div class="form-actions"><button type="button" onclick="closeModal('modalPej')" class="btn btn-outline">Batal</button><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button></div>
    <?= form_close() ?>
  </div>
</div>
<!-- Modal Dokumen -->
<div id="modalDok" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw">
    <div class="card-title"><i class="ti ti-plus-circle"></i> Tambah Dokumen Perda/Pergub</div>
    <?= form_open(site_url('parameter/pemda/simpan-dokumen')) ?>
    <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
    <input type="hidden" name="kabkota_id" value="<?= $kabkota_sel ?>"><input type="hidden" name="tahun" value="<?= $tahun_sel ?>">
    <div class="form-group mb-2"><label>Jenis</label><select name="jenis" class="form-control"><?php foreach ($dk_map as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="form-group mb-2"><label>Nomor <span class="req">*</span></label><input type="text" name="nomor" class="form-control" required placeholder="No. X Tahun YYYY"></div>
    <div class="form-group mb-2"><label>Tanggal <span class="req">*</span></label><input type="date" name="tanggal" class="form-control" required></div>
    <div class="form-group mb-2"><label>Keterangan</label><input type="text" name="keterangan" class="form-control"></div>
    <div class="form-actions"><button type="button" onclick="closeModal('modalDok')" class="btn btn-outline">Batal</button><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button></div>
    <?= form_close() ?>
  </div>
</div>
<script>
function editPej(j,label,kab,thn,nama,nip,jabatan,pangkat){
  document.getElementById('pejTitle').innerHTML='<i class="ti ti-edit"></i> '+label;
  document.getElementById('pejJenis').value=j;
  document.getElementById('pejNama').value=nama;
  document.getElementById('pejNip').value=nip;
  document.getElementById('pejJabatan').value=jabatan;
  document.getElementById('pejPangkat').value=pangkat;
  openModal('modalPej');
}
</script>
<?php endif; ?>
