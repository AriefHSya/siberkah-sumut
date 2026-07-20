<div class="page-header">
  <div class="page-title"><i class="ti ti-users-group"></i> Tim Review & Surat Tugas</div>
  <?php if ($this->rbac->can('tim_reviu.manage')): ?>
  <a href="<?= site_url('tim-reviu/tambah') ?>" class="btn btn-primary btn-sm">
    <i class="ti ti-plus"></i> Buat Tim Baru
  </a>
  <?php endif; ?>
</div>

<?php if (empty($list)): ?>
<div class="card" style="text-align:center;padding:40px">
  <i class="ti ti-users-group" style="font-size:40px;color:var(--abu);display:block;margin-bottom:12px"></i>
  <div class="text-muted">Belum ada tim review untuk tahun <?= $tahun ?>.</div>
  <?php if ($this->rbac->can('tim_reviu.manage')): ?>
  <div style="margin-top:16px">
    <a href="<?= site_url('tim-reviu/tambah') ?>" class="btn btn-primary btn-sm">
      <i class="ti ti-plus"></i> Buat Tim Pertama
    </a>
  </div>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <?php if ($is_provinsi): ?><th>Kab/Kota</th><?php endif; ?>
        <th>No. SK / Surat Tugas</th>
        <th style="width:110px">Tanggal SK</th>
        <th style="width:80px;text-align:center">Anggota</th>
        <th style="width:80px;text-align:center">Digunakan</th>
        <th style="width:80px">File SK</th>
        <th style="width:120px">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $i => $t): ?>
    <tr>
      <td class="text-muted text-sm"><?= $i+1 ?></td>
      <?php if ($is_provinsi): ?>
      <td class="text-sm"><?= htmlspecialchars($t->nama_kabkota) ?></td>
      <?php endif; ?>
      <td>
        <div class="fw-500"><?= htmlspecialchars($t->no_sk) ?></div>
        <?php if ($t->keterangan): ?>
        <div class="text-xs text-muted"><?= htmlspecialchars($t->keterangan) ?></div>
        <?php endif; ?>
      </td>
      <td><?= tgl_short($t->tgl_sk) ?></td>
      <td class="text-center">
        <span class="badge badge-biru"><?= $t->jml_anggota ?> orang</span>
      </td>
      <td class="text-center">
        <?php if ($t->jml_reviu > 0): ?>
        <span class="badge badge-hijau"><?= $t->jml_reviu ?> reviu</span>
        <?php else: ?>
        <span class="text-muted text-xs">—</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($t->file_sk): ?>
        <a href="<?= site_url('berkas/unduh/sk-tim/'.$t->id) ?>" target="_blank" class="btn btn-outline btn-xs">
          <i class="ti ti-download"></i> SK
        </a>
        <?php else: ?>
        <span class="text-muted text-xs">—</span>
        <?php endif; ?>
      </td>
      <td class="aksi-row">
        <a href="<?= site_url('tim-reviu/'.$t->id) ?>" class="btn btn-outline btn-xs">
          <i class="ti ti-eye"></i>
        </a>
        <?php if ($this->rbac->can('tim_reviu.manage')): ?>
        <a href="<?= site_url('tim-reviu/edit/'.$t->id) ?>" class="btn btn-outline btn-xs">
          <i class="ti ti-edit"></i>
        </a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
