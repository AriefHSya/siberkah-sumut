<?php
function label_kelompok_prov($jenis, $kode_tahap) {
    if ($jenis === 'bertahap')
        return 'Bertahap — ' . ($kode_tahap === 'tahap_2' ? 'Tahap II' : 'Tahap I');
    $m = ['sekaligus'=>'Sekaligus','khusus_mendesak'=>'Khusus Mendesak','khusus_bencana'=>'Khusus Bencana'];
    return $m[$jenis] ?? ucfirst($jenis);
}
?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-inbox"></i> Permohonan Pencairan BKP TA <?= $tahun ?></div>
  <div class="aksi-row">
    <a href="<?= site_url('verifikasi/prov/cetak-rekap?tahun='.$tahun) ?>"
       target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Cetak Rekap SP2D
    </a>
  </div>
</div>

<!-- Stat Cards -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)">
      <i class="ti ti-inbox" style="color:var(--biru)"></i>
    </div>
    <div class="stat-val" style="color:var(--biru)"><?= $total ?></div>
    <div class="stat-label">Permohonan Masuk</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--kuning-light)">
      <i class="ti ti-file-invoice" style="color:var(--kuning-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--kuning-mid)"><?= $rekap->total_sp2d ?? 0 ?></div>
    <div class="stat-label">SP2D Diterbitkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--teal-light)">
      <i class="ti ti-cash" style="color:var(--teal-mid)"></i>
    </div>
    <div class="stat-val" style="font-size:14px;color:var(--teal-mid)">
      <?= rupiah_juta($rekap->total_disalurkan ?? 0) ?>
    </div>
    <div class="stat-label">Total Disalurkan</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)">
      <i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= $rekap->total_dikonfirmasi ?? 0 ?></div>
    <div class="stat-label">Dikonfirmasi Kab</div>
  </div>
</div>

<!-- Filter -->
<div class="card mb-2">
  <form method="get" class="filter-row">
    <div class="filter-group"><label>Cari</label>
      <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>"
             placeholder="No. permohonan / kab/kota...">
    </div>
    <div class="filter-group"><label>Kab/Kota</label>
      <select name="kabkota_id">
        <option value="">Semua</option>
        <?php foreach ($kabkota_list as $k): ?>
        <option value="<?= $k->id ?>"
          <?= ($k->id == ($filters['kabkota_id'] ?? '')) ? 'selected' : '' ?>>
          <?= htmlspecialchars($k->nama) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group"><label>Kelompok</label>
      <select name="jenis">
        <option value="">Semua</option>
        <option value="bertahap"        <?= (($filters['jenis']??'')==='bertahap')?'selected':'' ?>>Bertahap</option>
        <option value="sekaligus"       <?= (($filters['jenis']??'')==='sekaligus')?'selected':'' ?>>Sekaligus</option>
        <option value="khusus_mendesak" <?= (($filters['jenis']??'')==='khusus_mendesak')?'selected':'' ?>>Mendesak</option>
        <option value="khusus_bencana"  <?= (($filters['jenis']??'')==='khusus_bencana')?'selected':'' ?>>Bencana</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="ti ti-search"></i> Filter
    </button>
    <a href="<?= site_url('verifikasi/prov') ?>" class="btn btn-outline btn-sm">Reset</a>
  </form>
</div>

<!-- Tabel Permohonan -->
<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No. Permohonan</th>
          <th>Kab/Kota</th>
          <th>Kelompok</th>
          <th style="text-align:center">Kegiatan</th>
          <th style="text-align:right">Total Nilai Pengajuan</th>
          <th style="text-align:center">Progress</th>
          <th>Tanggal Masuk</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($list)): foreach ($list as $row):
            $jml     = (int)($row->jumlah_item ?? 0);
            $selesai = (int)($row->item_disalurkan ?? 0);
            $pct     = $jml > 0 ? round($selesai / $jml * 100) : 0;
      ?>
      <tr>
        <td>
          <div class="mono fw-500"><?= htmlspecialchars($row->no_permohonan ?: '—') ?></div>
          <div class="text-xs text-muted">ID #<?= $row->id ?></div>
        </td>
        <td class="fw-500 text-sm"><?= htmlspecialchars($row->nama_kabkota) ?></td>
        <td><?= badge_jenis($row->jenis_penyaluran) ?>
          <div class="text-xs text-muted mt-1">
            <?= label_kelompok_prov($row->jenis_penyaluran, $row->kode_tahap) ?>
          </div>
          <?php if ($row->status === 'selesai'): ?>
          <div class="mt-1"><span class="badge badge-hijau">Selesai</span></div>
          <?php endif; ?>
        </td>
        <td class="text-center fw-500"><?= $jml ?> kegiatan</td>
        <td class="fw-500 text-sm" style="text-align:right;color:var(--biru)">
          <?= rupiah($row->total_nilai ?? 0) ?>
        </td>
        <td style="text-align:center;min-width:110px">
          <?php if ($jml > 0): ?>
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">
            <?= $selesai ?>/<?= $jml ?> disalurkan
          </div>
          <div style="background:var(--border);border-radius:4px;height:6px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:var(--teal-mid);transition:.3s"></div>
          </div>
          <?php else: ?>
          <span class="text-muted text-xs">—</span>
          <?php endif; ?>
        </td>
        <td class="text-sm"><?= tgl_indo($row->created_at) ?></td>
        <td>
          <div class="aksi-row">
            <?php if ($row->status === 'selesai'): ?>
            <a href="<?= site_url('verifikasi/prov/permohonan/'.$row->id) ?>"
               class="btn btn-outline btn-xs">
              <i class="ti ti-eye"></i> Lihat
            </a>
            <?php else: ?>
            <a href="<?= site_url('verifikasi/prov/permohonan/'.$row->id) ?>"
               class="btn btn-primary btn-xs">
              <i class="ti ti-shield-check"></i> Proses
            </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="8" style="text-align:center;padding:50px;color:var(--text-muted)">
          <div style="font-size:36px;margin-bottom:8px"><i class="ti ti-inbox"></i></div>
          <div class="fw-500">Tidak ada permohonan pencairan masuk.</div>
          <div class="text-xs mt-1">Permohonan akan muncul di sini setelah SKPKD Kab/Kota mengajukan ke Provinsi.</div>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters]); ?>
</div>
