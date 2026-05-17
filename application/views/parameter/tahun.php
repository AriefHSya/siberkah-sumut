<div class="page-header">
  <div class="page-title"><i class="ti ti-calendar"></i> Data Tahun Anggaran</div>
  <?php if ($this->rbac->can('parameter.tahun.manage')): ?>
  <button class="btn btn-primary btn-sm" onclick="document.getElementById('formTambah').scrollIntoView({behavior:'smooth'})"><i class="ti ti-plus"></i> Tambah Tahun</button>
  <?php endif; ?>
</div>

<div class="alert alert-info mb-2"><i class="ti ti-info-circle"></i><div>Aplikasi mendukung multi-tahun. Satu tahun harus ditandai sebagai <strong>Aktif</strong> sebagai default dashboard seluruh user.</div></div>

<div class="g3 mb-3">
  <div class="stat-card"><div class="stat-icon" style="background:var(--biru-light)"><i class="ti ti-calendar-stats" style="color:var(--biru)"></i></div><div class="stat-val"><?= count($list) ?></div><div class="stat-label">Total tahun terdaftar</div></div>
  <div class="stat-card"><div class="stat-icon" style="background:var(--hijau-light)"><i class="ti ti-check" style="color:var(--hijau-mid)"></i></div><div class="stat-val" style="color:var(--hijau-mid)"><?= array_reduce($list,fn($c,$r)=>$r->is_aktif?$r->tahun:$c,'-') ?></div><div class="stat-label">Tahun aktif</div></div>
  <div class="stat-card"><div class="stat-icon" style="background:var(--kuning-light)"><i class="ti ti-calendar-time" style="color:var(--kuning-mid)"></i></div><div class="stat-val" style="color:var(--kuning-mid)"><?= date('Y') ?></div><div class="stat-label">Tahun berjalan</div></div>
</div>

<?php foreach ($list as $t): $jml = $jml_bkp[$t->tahun] ?? 0; ?>
<div class="card mb-1" style="<?= $t->is_aktif ? 'border-color:var(--biru)' : '' ?>">
  <div style="display:flex;align-items:center;justify-content:space-between">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="font-size:28px;font-weight:700;color:<?= $t->is_aktif ? 'var(--biru)' : 'var(--text-muted)' ?>"><?= $t->tahun ?></div>
      <div><div class="text-sm"><?= $jml ?> BKP terdaftar</div><div class="text-xs text-muted">Ditambah: <?= tgl_short($t->created_at) ?></div></div>
    </div>
    <div class="aksi-row">
      <?php if ($t->is_aktif): ?>
        <span class="badge badge-biru">Aktif</span>
      <?php else: ?>
        <span class="badge badge-abu">Tidak aktif</span>
        <?php if ($this->rbac->can('parameter.tahun.manage')): ?>
        <a href="<?= site_url('parameter/tahun/set-aktif/'.$t->id) ?>" class="btn btn-outline btn-xs" onclick="return confirm('Jadikan <?= $t->tahun ?> sebagai tahun aktif?')"><i class="ti ti-toggle-left"></i> Set Aktif</a>
        <a href="<?= site_url('parameter/tahun/hapus/'.$t->id) ?>" class="btn-icon del" title="Hapus" data-confirm="Hapus tahun <?= $t->tahun ?>?"><i class="ti ti-trash"></i></a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if ($this->rbac->can('parameter.tahun.manage')): ?>
<div class="card" id="formTambah">
  <div class="card-title"><i class="ti ti-plus-circle"></i> Tambah Tahun Baru</div>
  <?= form_open(site_url('parameter/tahun/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <div class="form-grid-2">
    <div class="form-group"><label>Tahun <span class="req">*</span></label><input type="number" name="tahun" class="form-control" min="2000" max="2099" value="<?= date('Y')+1 ?>" required></div>
    <div class="form-group"><label>Set sebagai tahun aktif?</label>
      <select name="set_aktif" class="form-control">
        <option value="0">Tidak — pertahankan tahun aktif saat ini</option>
        <option value="1">Ya — jadikan tahun baru ini aktif</option>
      </select>
    </div>
  </div>
  <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Tambah Tahun</button></div>
  <?= form_close() ?>
</div>
<?php endif; ?>
