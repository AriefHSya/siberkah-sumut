<?php
$label_jenis = [
    'sekaligus'       => 'Sekaligus',
    'bertahap'        => 'Bertahap',
    'khusus_mendesak' => 'Khusus Mendesak',
    'khusus_bencana'  => 'Khusus Bencana',
];
function label_kelompok_pm($jenis, $kode_tahap) {
    if ($jenis === 'bertahap') {
        return 'Bertahap — '.($kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    }
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}
?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-send"></i> Permohonan Pencairan TA <?= $tahun ?></div>
  <?php if ($this->rbac->can('permohonan.create') && $this->rbac->isKabkota()): ?>
  <a href="<?= site_url('permohonan/buat') ?>" class="btn btn-primary btn-sm">
    <i class="ti ti-plus"></i> Buat Permohonan
  </a>
  <?php endif; ?>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <?php if (!empty($kabkota_list)): ?>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>" <?= ($k->id==$filters['kabkota_id'])?'selected':'' ?>><?= $k->nama ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="filter-group"><label>Jenis</label>
      <select name="jenis">
        <option value="">Semua</option>
        <?php foreach ($label_jenis as $kode => $lab): ?>
        <option value="<?= $kode ?>" <?= ($filters['jenis']===$kode)?'selected':'' ?>><?= $lab ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group"><label>Status</label>
      <select name="status">
        <option value="">Semua</option>
        <option value="diajukan" <?= ($filters['status']==='diajukan')?'selected':'' ?>>Diajukan</option>
        <option value="draft"    <?= ($filters['status']==='draft')?'selected':'' ?>>Draft</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Filter</button>
    <a href="<?= site_url('permohonan') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No. Permohonan</th>
          <th>Kelompok</th>
          <?php if ($this->rbac->isProvinsi()): ?>
          <th>Kab/Kota</th>
          <?php endif; ?>
          <th style="text-align:center">Jml. Pekerjaan</th>
          <th style="text-align:right">Total Nilai Diajukan</th>
          <th>Tgl. Permohonan</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $row): ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($row->no_permohonan ?: '—') ?></div>
          <div class="text-xs text-muted">TA <?= $row->tahun ?></div>
        </td>
        <td>
          <span class="badge badge-biru"><?= label_kelompok_pm($row->jenis_penyaluran, $row->kode_tahap) ?></span>
        </td>
        <?php if ($this->rbac->isProvinsi()): ?>
        <td class="text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <?php endif; ?>
        <td class="text-center fw-500"><?= $row->jumlah_item ?></td>
        <td class="fw-500 text-sm" style="text-align:right"><?= rupiah($row->total_nilai ?? 0) ?></td>
        <td class="text-sm"><?= $row->tgl_permohonan ? tgl_indo($row->tgl_permohonan) : '—' ?></td>
        <td>
          <?php if ($row->status === 'diajukan'): ?>
          <span class="badge badge-hijau">Diajukan</span>
          <?php else: ?>
          <span class="badge badge-abu">Draft</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?= site_url('permohonan/detail/'.$row->id) ?>" class="btn btn-outline btn-xs">
            <i class="ti ti-eye"></i> Lihat
          </a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="<?= $this->rbac->isProvinsi() ? 8 : 7 ?>"
            style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:32px;margin-bottom:8px"><i class="ti ti-send"></i></div>
          Belum ada permohonan pencairan.
          <?php if ($this->rbac->isKabkota() && $this->rbac->can('permohonan.create')): ?>
          <div style="margin-top:10px">
            <a href="<?= site_url('permohonan/buat') ?>" class="btn btn-primary btn-sm">
              <i class="ti ti-plus"></i> Buat Permohonan Pertama
            </a>
          </div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
