<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
  <div>
    <div class="page-title">Penyaluran Dana BKP</div>
    <div class="page-sub">Tahun Anggaran <?= $tahun ?> · <?= htmlspecialchars($this->session->userdata('kabkota_nama') ?? '') ?></div>
  </div>
</div>

<!-- Ringkasan -->
<div class="g4" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light);color:var(--biru)">
      <i class="ti ti-send"></i>
    </div>
    <div>
      <div class="stat-val"><?= $rekap->total_permohonan ?? 0 ?></div>
      <div class="stat-label">Total Permohonan</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--kuning-light);color:var(--kuning)">
      <i class="ti ti-file-invoice"></i>
    </div>
    <div>
      <div class="stat-val"><?= $rekap->ada_sp2d ?? 0 ?></div>
      <div class="stat-label">Ada SP2D Provinsi</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light);color:var(--hijau)">
      <i class="ti ti-circle-check"></i>
    </div>
    <div>
      <div class="stat-val"><?= $rekap->dikonfirmasi ?? 0 ?></div>
      <div class="stat-label">Sudah Dikonfirmasi</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--teal-light);color:var(--teal)">
      <i class="ti ti-cash"></i>
    </div>
    <div>
      <div class="stat-val"><?= rupiah_juta($rekap->total_rkud ?? 0) ?></div>
      <div class="stat-label">Total Dana Dikonfirmasi</div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:16px;padding:12px 16px">
  <form method="get" class="filter-row">
    <div class="filter-group">
      <input type="text" name="q" class="fc fc-sm" placeholder="Cari no. permohonan / SP2D…"
             value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
    </div>
    <div class="filter-group">
      <select name="status_rkud" class="fc fc-sm">
        <option value="">Semua Status</option>
        <option value="belum_sp2d"  <?= ($filters['status_rkud'] ?? '') === 'belum_sp2d'  ? 'selected':'' ?>>Belum Ada SP2D</option>
        <option value="menunggu"    <?= ($filters['status_rkud'] ?? '') === 'menunggu'    ? 'selected':'' ?>>Menunggu Konfirmasi</option>
        <option value="dikonfirmasi"<?= ($filters['status_rkud'] ?? '') === 'dikonfirmasi'? 'selected':'' ?>>Sudah Dikonfirmasi</option>
      </select>
    </div>
    <button type="submit" class="btn btn-outline btn-sm">
      <i class="ti ti-search"></i> Filter
    </button>
    <a href="<?= site_url('penyaluran-kab') ?>" class="btn btn-sm" style="color:var(--abu)">Reset</a>
  </form>
</div>

<!-- Tabel -->
<div class="card">
  <div class="card-title">
    <span>Daftar Permohonan Penyaluran</span>
    <span class="badge badge-abu"><?= $paging['total'] ?> permohonan</span>
  </div>

  <?php if (empty($list)): ?>
    <div style="text-align:center;padding:40px;color:var(--abu)">
      <i class="ti ti-inbox" style="font-size:32px;display:block;margin-bottom:8px"></i>
      Belum ada permohonan penyaluran.
    </div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:32px">No</th>
          <th>No. Permohonan</th>
          <th>Jenis</th>
          <th class="center">Jml Keg.</th>
          <th class="right">Total Nilai (Rp)</th>
          <th>SP2D Provinsi</th>
          <th>Status Konfirmasi</th>
          <th class="center">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($list as $i => $row):
        $sudah_konfirmasi = !empty($row->kode_transaksi_rkud);
        $ada_sp2d         = !empty($row->no_sp2d);

        // Label jenis
        $label_jenis = [
          'bertahap'       => 'Bertahap',
          'sekaligus'      => 'Sekaligus',
          'khusus_mendesak'=> 'Khusus Mendesak',
          'khusus_bencana' => 'Khusus Bencana',
        ];
        $jenis_disp = ($label_jenis[$row->jenis_penyaluran] ?? ucfirst($row->jenis_penyaluran));
        if ($row->jenis_penyaluran === 'bertahap') {
          $jenis_disp .= ' ' . ($row->kode_tahap === 'tahap_1' ? 'Tahap I' : 'Tahap II');
        }
      ?>
      <tr>
        <td class="center"><?= ($paging['page'] - 1) * $paging['per_page'] + $i + 1 ?></td>
        <td>
          <strong><?= htmlspecialchars($row->no_permohonan) ?></strong>
          <div style="font-size:11px;color:var(--abu);margin-top:2px">
            Diajukan: <?= tgl_short($row->created_at) ?>
          </div>
        </td>
        <td><?= badge_jenis($row->jenis_penyaluran) ?><br>
          <span style="font-size:11px;color:var(--abu)"><?= htmlspecialchars($jenis_disp) ?></span>
        </td>
        <td class="center"><?= (int)$row->jumlah_kegiatan ?></td>
        <td class="right"><?= rupiah($row->total_nilai ?? 0) ?></td>
        <td>
          <?php if ($ada_sp2d): ?>
            <div style="font-size:12px">
              <div><i class="ti ti-file-invoice" style="color:var(--biru)"></i>
                <strong><?= htmlspecialchars($row->no_sp2d) ?></strong></div>
              <div style="color:var(--abu)"><?= tgl_short($row->tgl_sp2d) ?></div>
              <div><?= rupiah($row->nilai_sp2d ?? 0) ?></div>
              <div>
                <?php if ($row->status_sp2d === 'selesai'): ?>
                  <span class="badge badge-hijau" style="font-size:10px">Selesai</span>
                <?php else: ?>
                  <span class="badge badge-kuning" style="font-size:10px">Proses</span>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <span style="color:var(--abu);font-size:12px">Belum ada SP2D</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($sudah_konfirmasi): ?>
            <span class="badge badge-hijau"><i class="ti ti-check"></i> Dikonfirmasi</span>
            <div style="font-size:11px;color:var(--abu);margin-top:4px">
              <?= htmlspecialchars($row->kode_transaksi_rkud) ?><br>
              <?= rupiah($row->nilai_rkud ?? 0) ?><br>
              <?= tgl_short($row->tgl_rkud) ?>
            </div>
          <?php elseif ($ada_sp2d): ?>
            <span class="badge badge-kuning"><i class="ti ti-clock"></i> Menunggu Konfirmasi</span>
          <?php else: ?>
            <span class="badge badge-abu">Menunggu SP2D</span>
          <?php endif; ?>
        </td>
        <td class="center">
          <?php if ($ada_sp2d && !$sudah_konfirmasi && $this->rbac->can('penyaluran_kab.konfirmasi')): ?>
            <button class="btn btn-success btn-sm"
              onclick="bukaModalKonfirmasi(<?= $row->id ?>, '<?= htmlspecialchars($row->no_permohonan) ?>', '<?= htmlspecialchars($row->no_sp2d) ?>', <?= $row->nilai_sp2d ?? 0 ?>)">
              <i class="ti ti-circle-check"></i> Konfirmasi
            </button>
          <?php elseif ($sudah_konfirmasi): ?>
            <span style="color:var(--hijau);font-size:12px">
              <i class="ti ti-check"></i> Selesai
            </span>
          <?php else: ?>
            <span style="color:var(--abu);font-size:11px">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginasi -->
  <?php if ($paging['total'] > $paging['per_page']): ?>
  <div style="padding:12px 0 4px;display:flex;justify-content:center;gap:6px">
    <?php
    $total_pages = ceil($paging['total'] / $paging['per_page']);
    for ($p = 1; $p <= $total_pages; $p++):
      $q   = $filters['q'] ?? '';
      $stt = $filters['status_rkud'] ?? '';
      $url = site_url("penyaluran-kab?page=$p&q=".urlencode($q)."&status_rkud=".urlencode($stt));
    ?>
    <a href="<?= $url ?>" class="btn btn-sm <?= $p === $paging['page'] ? 'btn-primary' : 'btn-outline' ?>">
      <?= $p ?>
    </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal Konfirmasi Penyaluran -->
<div id="modalKonfirmasi" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:10px;padding:28px 32px;max-width:480px;width:94%;box-shadow:0 8px 32px rgba(0,0,0,0.18)">
    <div style="font-size:15pt;font-weight:bold;margin-bottom:4px">Konfirmasi Penerimaan Dana</div>
    <div id="modalSubjudul" style="color:var(--abu);font-size:12px;margin-bottom:20px"></div>

    <?= form_open(site_url('penyaluran-kab/konfirmasi/0'), ['id'=>'formKonfirmasi']) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

    <div class="form-group">
      <label class="form-label">Kode Transaksi RKUD <span style="color:red">*</span></label>
      <input type="text" name="kode_transaksi_rkud" class="fc" required
             placeholder="Contoh: TRF-2026-XXXXXX">
      <small style="color:var(--abu)">Nomor transaksi atau referensi transfer masuk di rekening RKUD</small>
    </div>

    <div class="form-grid-2" style="gap:12px">
      <div class="form-group">
        <label class="form-label">Tanggal Masuk RKUD <span style="color:red">*</span></label>
        <input type="date" name="tgl_rkud" id="inputTglRkud" class="fc" required>
      </div>
      <div class="form-group">
        <label class="form-label">Nilai Diterima (Rp) <span style="color:red">*</span></label>
        <input type="text" name="nilai_rkud" id="inputNilaiRkud" class="fc" required
               placeholder="0" oninput="formatRupiah(this)">
        <small id="nilaiSp2dHint" style="color:var(--biru)"></small>
      </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-outline" onclick="tutupModal()">Batal</button>
      <button type="submit" class="btn btn-success">
        <i class="ti ti-circle-check"></i> Konfirmasi Penerimaan
      </button>
    </div>
    <?= form_close() ?>
  </div>
</div>

<script>
function bukaModalKonfirmasi(pmId, noPermohonan, noSp2d, nilaiSp2d) {
  var form = document.getElementById('formKonfirmasi');
  var action = form.getAttribute('action').replace(/\/\d+$/, '/' + pmId);
  form.setAttribute('action', action);

  document.getElementById('modalSubjudul').textContent =
    'Permohonan: ' + noPermohonan + ' | SP2D: ' + noSp2d;

  // Pre-fill nilai dari SP2D
  if (nilaiSp2d > 0) {
    var inputNilai = document.getElementById('inputNilaiRkud');
    inputNilai.value = nilaiSp2d.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    document.getElementById('nilaiSp2dHint').textContent =
      'Nilai SP2D Provinsi: Rp ' + nilaiSp2d.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Default tanggal hari ini
  var today = new Date().toISOString().split('T')[0];
  document.getElementById('inputTglRkud').value = today;

  document.getElementById('modalKonfirmasi').style.display = 'flex';
}

function tutupModal() {
  document.getElementById('modalKonfirmasi').style.display = 'none';
  document.getElementById('formKonfirmasi').reset();
}

function formatRupiah(el) {
  var raw = el.value.replace(/\D/g, '');
  el.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
}

// Tutup modal klik luar
document.getElementById('modalKonfirmasi').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});
</script>
