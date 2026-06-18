<?php
$jenis_map = [
    'kepala_badan'          => 'Kepala BKAD Provinsi',
    'kabid_anggaran'        => 'Kabid Anggaran BKAD',
    'bendahara_pengeluaran' => 'Bendahara Pengeluaran',
];
?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-users"></i> Pejabat BKAD Provinsi Sumatera Utara</div>
</div>

<!-- Filter Tahun -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Tahun Anggaran</label>
      <select name="tahun" onchange="this.form.submit()">
        <?php foreach ($tahun_list as $t): ?>
        <option value="<?= $t->tahun ?>" <?= ($t->tahun == $tahun_sel) ? 'selected' : '' ?>>
          <?= $t->tahun ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success mb-2"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-title"><i class="ti ti-id-badge"></i> Data Pejabat TA <?= $tahun_sel ?></div>
  <p class="text-sm text-muted mb-2">
    Data pejabat ini digunakan untuk tanda tangan pada Nota Dinas dan Ringkasan Penyaluran BKP.
  </p>

  <?= form_open(site_url('parameter/pejabat-provinsi/simpan')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <?= form_hidden('tahun', $tahun_sel) ?>

  <?php foreach ($jenis_map as $jenis => $label):
        $p = $pejabat[$jenis] ?? NULL;
  ?>
  <div style="padding:16px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:14px">
    <div class="fw-500 mb-2" style="color:var(--biru)"><i class="ti ti-user-check"></i> <?= $label ?></div>
    <div class="form-grid-3">
      <div class="form-group">
        <label>Nama Lengkap <span class="req">*</span></label>
        <input type="text" name="nama_<?= $jenis ?>" class="form-control"
               value="<?= htmlspecialchars($p->nama ?? '') ?>"
               placeholder="Nama lengkap beserta gelar">
      </div>
      <div class="form-group">
        <label>NIP</label>
        <input type="text" name="nip_<?= $jenis ?>" class="form-control mono"
               value="<?= htmlspecialchars($p->nip ?? '') ?>"
               placeholder="NIP 18 digit">
      </div>
      <div class="form-group">
        <label>Jabatan (opsional)</label>
        <input type="text" name="jabatan_<?= $jenis ?>" class="form-control"
               value="<?= htmlspecialchars($p->jabatan ?? '') ?>"
               placeholder="Misal: Pembina Tk. I / IV.b">
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <button type="submit" class="btn btn-primary">
    <i class="ti ti-device-floppy"></i> Simpan Data Pejabat
  </button>
  <?= form_close() ?>
</div>
