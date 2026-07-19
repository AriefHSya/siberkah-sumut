<div class="page-header">
  <div class="page-title">
    <i class="ti ti-<?= $edit ? 'edit' : 'users-group' ?>"></i>
    <?= $edit ? 'Edit Tim Review' : 'Buat Tim Review Baru' ?>
  </div>
  <a href="<?= site_url($edit ? 'tim-reviu/'.$tim->id : 'tim-reviu') ?>" class="btn btn-outline btn-sm">
    <i class="ti ti-arrow-left"></i> Kembali
  </a>
</div>

<div class="card">
  <?= form_open_multipart(site_url($edit ? 'tim-reviu/update/'.$tim->id : 'tim-reviu/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

  <div class="form-grid form-grid-2">
    <div class="form-group">
      <label>No. SK / Surat Tugas <span class="req">*</span></label>
      <input type="text" name="no_sk" class="form-control"
             value="<?= htmlspecialchars($tim->no_sk ?? '') ?>"
             placeholder="Contoh: 050/ST/2026/123" required>
    </div>
    <div class="form-group">
      <label>Tanggal SK <span class="req">*</span></label>
      <input type="date" name="tgl_sk" class="form-control"
             value="<?= $tim->tgl_sk ?? '' ?>" required>
    </div>
    <div class="form-group">
      <label>File Surat Tugas <span class="text-xs text-muted">(PDF/DOC, maks 10 MB<?= $edit && $tim->file_sk ? ' — sudah ada, isi jika ingin ganti' : '' ?>)</span></label>
      <input type="file" name="file_sk" class="form-control" accept=".pdf,.doc,.docx">
      <?php if ($edit && $tim->file_sk): ?>
      <div class="form-hint">
        <a href="<?= site_url('berkas/unduh/sk-tim/'.$tim->id) ?>" target="_blank">
          <i class="ti ti-download"></i> Unduh SK saat ini (<?= htmlspecialchars($tim->nama_asli_sk ?? basename($tim->file_sk)) ?>)
        </a>
      </div>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label>Keterangan <span class="text-xs text-muted">(opsional)</span></label>
      <input type="text" name="keterangan" class="form-control"
             value="<?= htmlspecialchars($tim->keterangan ?? '') ?>"
             placeholder="Misal: Tim review batch Juli 2026">
    </div>
  </div>

  <!-- Anggota Tim -->
  <div class="card-title" style="margin-top:8px">
    <i class="ti ti-users"></i> Anggota Tim <span class="text-xs text-muted fw-400">(Maks. 7 orang)</span>
  </div>
  <div id="anggotaWrap">
    <?php
    $rows = !empty($anggota) ? $anggota : [null];
    foreach ($rows as $idx => $a):
    ?>
    <div class="anggota-row form-grid" style="grid-template-columns:1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end" data-row="<?= $idx ?>">
      <div class="form-group" style="margin:0">
        <?php if ($idx === 0): ?><label>Nama <span class="req">*</span></label><?php endif; ?>
        <input type="text" name="nama[]" class="form-control fc"
               value="<?= htmlspecialchars($a->nama ?? '') ?>"
               placeholder="Nama lengkap" required>
      </div>
      <div class="form-group" style="margin:0">
        <?php if ($idx === 0): ?><label>NIP <span class="text-xs text-muted">(opsional)</span></label><?php endif; ?>
        <input type="text" name="nip[]" class="form-control fc mono"
               value="<?= htmlspecialchars($a->nip ?? '') ?>"
               placeholder="NIP (opsional)" maxlength="18"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')">
      </div>
      <div class="form-group" style="margin:0">
        <?php if ($idx === 0): ?><label>Jabatan <span class="text-xs text-muted">(opsional)</span></label><?php endif; ?>
        <input type="text" name="jabatan[]" class="form-control fc"
               value="<?= htmlspecialchars($a->jabatan ?? '') ?>"
               placeholder="Jabatan dalam tim">
      </div>
      <div style="padding-bottom:2px">
        <?php if ($idx === 0): ?><label style="display:block;opacity:0;font-size:11px">.</label><?php endif; ?>
        <?php if ($idx > 0): ?>
        <button type="button" class="btn btn-danger btn-xs btn-icon" onclick="hapusAnggota(this)" title="Hapus baris">
          <i class="ti ti-trash"></i>
        </button>
        <?php else: ?>
        <span style="display:inline-block;width:30px"></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline btn-sm" id="btnTambahAnggota" onclick="tambahAnggota()">
    <i class="ti ti-plus"></i> Tambah Anggota
  </button>

  <div class="form-actions">
    <a href="<?= site_url($edit ? 'tim-reviu/'.$tim->id : 'tim-reviu') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">
      <i class="ti ti-device-floppy"></i> <?= $edit ? 'Simpan Perubahan' : 'Simpan Tim' ?>
    </button>
  </div>
  <?= form_close() ?>
</div>

<script>
var rowCount = <?= count($rows) ?>;
var MAX_ROWS = 7;

function tambahAnggota() {
  if (rowCount >= MAX_ROWS) {
    alert('Maksimal 7 anggota tim.');
    return;
  }
  var wrap = document.getElementById('anggotaWrap');
  var div  = document.createElement('div');
  div.className = 'anggota-row form-grid';
  div.style.cssText = 'grid-template-columns:1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end';
  div.innerHTML =
    '<div class="form-group" style="margin:0"><input type="text" name="nama[]" class="form-control fc" placeholder="Nama lengkap" required></div>' +
    '<div class="form-group" style="margin:0"><input type="text" name="nip[]" class="form-control fc mono" placeholder="NIP (opsional)" maxlength="18" oninput="this.value=this.value.replace(/[^0-9]/g,\'\')"></div>' +
    '<div class="form-group" style="margin:0"><input type="text" name="jabatan[]" class="form-control fc" placeholder="Jabatan dalam tim"></div>' +
    '<div style="padding-bottom:2px"><button type="button" class="btn btn-danger btn-xs btn-icon" onclick="hapusAnggota(this)" title="Hapus baris"><i class="ti ti-trash"></i></button></div>';
  wrap.appendChild(div);
  rowCount++;
  updateTambahBtn();
}

function hapusAnggota(btn) {
  var row = btn.closest('.anggota-row');
  if (row) { row.remove(); rowCount--; updateTambahBtn(); }
}

function updateTambahBtn() {
  var btn = document.getElementById('btnTambahAnggota');
  if (btn) btn.disabled = (rowCount >= MAX_ROWS);
}
</script>
