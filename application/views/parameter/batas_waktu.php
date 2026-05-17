<div class="page-header">
  <div class="page-title"><i class="ti ti-calendar-time"></i> Batas Waktu Pengajuan TA <?= $tahun ?></div>
  <div class="aksi-row">
    <select class="fc fc-sm" onchange="location='<?= site_url('parameter/batas-waktu?tahun=') ?>'+this.value">
      <?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun)?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?>
    </select>
    <a href="<?= site_url('parameter/batas-waktu/log') ?>" class="btn btn-outline btn-sm"><i class="ti ti-history"></i> Log Perubahan</a>
  </div>
</div>

<div class="alert alert-info mb-2"><i class="ti ti-info-circle"></i>
<div>Batas waktu di bawah digunakan sistem untuk <strong>memblokir pengajuan</strong> yang melewati tenggat. Setiap perubahan tercatat dalam log audit. Dasar: SE Gubernur No. 900.1.1.3689.</div></div>

<div class="card">
  <table class="tbl">
    <thead><tr><th>Jenis Penyaluran</th><th>Tahapan</th><th>Batas Pengajuan (OPD)</th><th>Batas Penyaluran (Prov)</th><th>Status Hari Ini</th><?php if ($this->rbac->can('parameter.batas_waktu.manage')): ?><th>Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!empty($list)): foreach ($list as $bw):
      $lewat = date('Y-m-d') > $bw->batas_pengajuan;
      $sisa  = (strtotime($bw->batas_pengajuan) - time()) / 86400;
    ?>
    <tr <?= $lewat ? 'style="background:#fff5f5"' : '' ?>>
      <td><?= badge_jenis($bw->jenis_penyaluran) ?></td>
      <td class="fw-500"><?= htmlspecialchars($bw->label) ?></td>
      <td class="<?= $lewat?'text-danger':($sisa<=7?'text-warning':'') ?>"><?= tgl_indo($bw->batas_pengajuan) ?><?= ($lewat)?' <span class="badge badge-merah text-xs">Lewat</span>':($sisa<=7?' <span class="badge badge-kuning text-xs">'.((int)$sisa).' hari</span>':'') ?></td>
      <td><?= tgl_indo($bw->batas_penyaluran) ?></td>
      <td><?php if ($lewat): ?><span class="badge badge-merah"><i class="ti ti-lock"></i> Ditutup</span><?php else: ?><span class="badge badge-hijau"><i class="ti ti-lock-open"></i> Terbuka</span><?php endif; ?></td>
      <?php if ($this->rbac->can('parameter.batas_waktu.manage')): ?>
      <td><button class="btn-icon" onclick="editBW(<?= $bw->id ?>,'<?= $bw->label ?>','<?= $bw->batas_pengajuan ?>','<?= $bw->batas_penyaluran ?>','<?= htmlspecialchars($bw->keterangan??'',ENT_QUOTES) ?>')" title="Edit"><i class="ti ti-edit"></i></button></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Belum ada data batas waktu untuk TA <?= $tahun ?>. Tambahkan di bawah.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($this->rbac->can('parameter.batas_waktu.manage')): ?>
<div class="card">
  <div class="card-title"><i class="ti ti-plus-circle"></i> Tambah Batas Waktu</div>
  <?= form_open(site_url('parameter/batas-waktu/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <div class="form-grid">
    <div class="form-group"><label>Tahun <span class="req">*</span></label><select name="tahun" class="form-control"><?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun)?'selected':'' ?>><?= $t->tahun ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Jenis Penyaluran <span class="req">*</span></label>
      <select name="jenis_penyaluran" class="form-control" onchange="updateKodeTahap(this.value)">
        <option value="bertahap">Bertahap</option><option value="sekaligus">Sekaligus</option>
        <option value="khusus_mendesak">Kondisi Mendesak</option><option value="khusus_bencana">Darurat Bencana</option>
      </select>
    </div>
    <div class="form-group"><label>Kode Tahap <span class="req">*</span></label>
      <select name="kode_tahap" id="kode_tahap" class="form-control">
        <option value="tahap_1">tahap_1</option><option value="tahap_2">tahap_2</option>
      </select>
    </div>
    <div class="form-group"><label>Label <span class="req">*</span></label><input type="text" name="label" class="form-control" placeholder="Tahap I (50%)" required></div>
    <div class="form-group"><label>Batas Pengajuan <span class="req">*</span></label><input type="date" name="batas_pengajuan" class="form-control" required></div>
    <div class="form-group"><label>Batas Penyaluran <span class="req">*</span></label><input type="date" name="batas_penyaluran" class="form-control" required></div>
  </div>
  <div class="form-group mt-1"><label>Keterangan</label><input type="text" name="keterangan" class="form-control" placeholder="Opsional"></div>
  <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Simpan</button></div>
  <?= form_close() ?>
</div>
<?php endif; ?>

<!-- Modal Edit -->
<div id="modalEdit" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:95vw">
    <div class="card-title" id="modalTitle"><i class="ti ti-edit"></i> Edit Batas Waktu</div>
    <?= form_open('', ['id'=>'formEdit']) ?>
    <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
    <div class="form-grid-2">
      <div class="form-group"><label>Batas Pengajuan <span class="req">*</span></label><input type="date" name="batas_pengajuan" id="editBP" class="form-control" required></div>
      <div class="form-group"><label>Batas Penyaluran <span class="req">*</span></label><input type="date" name="batas_penyaluran" id="editBS" class="form-control" required></div>
    </div>
    <div class="form-group mt-1"><label>Keterangan</label><input type="text" name="keterangan" id="editKet" class="form-control"></div>
    <div class="form-group mt-1"><label>Alasan Perubahan <span class="req">*</span></label><input type="text" name="alasan" class="form-control" placeholder="Wajib diisi — dicatat dalam log audit" required></div>
    <div class="form-actions"><button type="button" onclick="closeModal('modalEdit')" class="btn btn-outline">Batal</button><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan Perubahan</button></div>
    <?= form_close() ?>
  </div>
</div>

<script>
function editBW(id, label, bp, bs, ket) {
  document.getElementById('modalTitle').innerHTML = '<i class="ti ti-edit"></i> Edit: ' + label;
  document.getElementById('formEdit').action = '<?= site_url('parameter/batas-waktu/update/') ?>' + id;
  document.getElementById('editBP').value  = bp;
  document.getElementById('editBS').value  = bs;
  document.getElementById('editKet').value = ket;
  openModal('modalEdit');
}
function updateKodeTahap(jenis) {
  const opts = {
    bertahap: ['tahap_1|tahap_1','tahap_2|tahap_2'],
    sekaligus: ['sekaligus|sekaligus'],
    khusus_mendesak: ['khusus|khusus'],
    khusus_bencana: ['khusus|khusus'],
  };
  const sel = document.getElementById('kode_tahap');
  sel.innerHTML = (opts[jenis]||['khusus|khusus']).map(o=>{const[v,l]=o.split('|');return`<option value="${v}">${l}</option>`;}).join('');
}
</script>
