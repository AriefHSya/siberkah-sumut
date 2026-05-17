<?php
$pek = $stats['pek_status'] ?? [];
$total_pekerjaan = array_sum($pek);
$bkp_obj  = $stats['bkp']  ?? (object)['total'=>0,'total_nilai'=>0];
$sp2d_obj = $stats['sp2d'] ?? (object)['total_sp2d'=>0,'total_transfer'=>0,'selesai'=>0];
?>

<div class="page-header">
  <div class="page-title">
    <i class="ti ti-layout-dashboard"></i> Dashboard TA <?= $tahun ?>
  </div>
</div>

<!-- ── ALERT DEADLINE ── -->
<?php if (!empty($alert_deadlines)): ?>
<div class="deadline-block warning mb-2">
  <div style="font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:6px">
    <i class="ti ti-alert-triangle"></i> Perhatian — Batas Waktu Pengajuan
  </div>
  <?php foreach ($alert_deadlines as $d):
    $lewat = date('Y-m-d') > $d->batas_pengajuan;
    $sisa  = (int)((strtotime($d->batas_pengajuan) - time()) / 86400);
  ?>
  <div style="font-size:13px;margin-bottom:3px;display:flex;align-items:center;gap:8px">
    <?= badge_jenis($d->jenis_penyaluran) ?>
    <strong><?= htmlspecialchars($d->label) ?></strong> →
    <?= tgl_indo($d->batas_pengajuan) ?>
    <?php if ($lewat): ?>
      <span class="badge badge-merah">Sudah Lewat</span>
    <?php else: ?>
      <span class="badge badge-kuning">Sisa <?= $sisa ?> hari</span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── ANTRIAN AKSI ── -->
<?php if (!empty($antrian_aksi)): ?>
<div class="card mb-2" style="border-left:4px solid var(--biru)">
  <div class="card-title"><i class="ti ti-bell-ringing"></i> Perlu Tindakan Anda</div>
  <?php foreach ($antrian_aksi as $a): ?>
  <a href="<?= site_url($a['url']) ?>"
     style="display:flex;align-items:center;gap:10px;padding:8px 10px;
            border:1px solid var(--border);border-radius:var(--radius);
            margin-bottom:6px;text-decoration:none;color:var(--text);
            background:var(--<?= $a['warna'] ?>-light)">
    <i class="ti ti-<?= $a['icon'] ?>" style="font-size:20px;color:var(--<?= $a['warna'] ?>-mid,var(--biru));flex-shrink:0"></i>
    <span class="text-sm fw-500"><?= htmlspecialchars($a['label']) ?></span>
    <i class="ti ti-chevron-right" style="margin-left:auto;color:var(--text-muted)"></i>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── STATISTIK UTAMA ── -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)">
      <i class="ti ti-database" style="color:var(--biru)"></i>
    </div>
    <div class="stat-val" style="color:var(--biru)"><?= number_format($bkp_obj->total ?? 0) ?></div>
    <div class="stat-label">Total BKP TA <?= $tahun ?></div>
    <div class="text-xs text-muted" style="margin-top:2px"><?= rupiah_juta($bkp_obj->total_nilai ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)">
      <i class="ti ti-file-text" style="color:var(--hijau-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= number_format($total_pekerjaan) ?></div>
    <div class="stat-label">Data Pekerjaan</div>
    <div class="text-xs text-muted" style="margin-top:2px">
      <?= $pek['selesai'] ?? 0 ?> selesai · <?= ($pek['ditolak'] ?? 0) ?> ditolak
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--kuning-light)">
      <i class="ti ti-file-invoice" style="color:var(--kuning-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--kuning-mid)"><?= number_format($sp2d_obj->total_sp2d ?? 0) ?></div>
    <div class="stat-label">SP2D Diterbitkan</div>
    <div class="text-xs text-muted" style="margin-top:2px">
      <?= $sp2d_obj->selesai ?? 0 ?> transfer selesai
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--teal-light)">
      <i class="ti ti-cash" style="color:var(--teal-mid)"></i>
    </div>
    <div class="stat-val" style="font-size:16px;color:var(--teal-mid)">
      <?= rupiah_juta($sp2d_obj->total_transfer ?? 0) ?>
    </div>
    <div class="stat-label">Total Disalurkan</div>
    <?php if (($bkp_obj->total_nilai ?? 0) > 0): ?>
    <div class="text-xs text-muted" style="margin-top:2px">
      <?= round(($sp2d_obj->total_transfer ?? 0) / $bkp_obj->total_nilai * 100, 1) ?>% dari nilai BKP
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── FUNNEL PROGRESS + BATAS WAKTU ── -->
<div class="g2 mb-2">
  <!-- Funnel Alur Pekerjaan -->
  <div class="card">
    <div class="card-title"><i class="ti ti-filter"></i> Progress Alur Pekerjaan</div>
    <?php
    $funnel_colors = ['var(--biru)','var(--biru)','var(--kuning-mid)','var(--kuning-mid)','var(--teal-mid)','var(--hijau-mid)'];
    $funnel_max = max(array_values($funnel) ?: [1]);
    $fi = 0;
    foreach ($funnel as $label => $val): $pct = $funnel_max > 0 ? round($val/$funnel_max*100) : 0;
    ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
        <span><?= htmlspecialchars($label) ?></span>
        <strong><?= number_format($val) ?></strong>
      </div>
      <div style="height:8px;background:var(--bg);border-radius:4px;overflow:hidden">
        <div style="height:100%;background:<?= $funnel_colors[$fi] ?? 'var(--biru)' ?>;
                    width:<?= $pct ?>%;border-radius:4px;transition:width 0.3s"></div>
      </div>
    </div>
    <?php $fi++; endforeach; ?>
  </div>

  <!-- Batas Waktu -->
  <div class="card">
    <div class="card-title"><i class="ti ti-calendar-time"></i> Batas Waktu Pengajuan TA <?= $tahun ?></div>
    <?php if (!empty($bw_list)): foreach ($bw_list as $bw):
      $lewat = date('Y-m-d') > $bw->batas_pengajuan;
      $sisa  = (int)((strtotime($bw->batas_pengajuan) - time()) / 86400);
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:8px 0;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:7px">
        <?= badge_jenis($bw->jenis_penyaluran) ?>
        <span class="text-sm fw-500"><?= htmlspecialchars($bw->label) ?></span>
      </div>
      <div style="text-align:right">
        <div class="text-sm fw-500 <?= $lewat ? 'text-danger' : ($sisa <= 7 ? 'text-warning' : '') ?>">
          <?= tgl_indo($bw->batas_pengajuan) ?>
        </div>
        <?php if ($lewat): ?>
          <div class="text-xs"><span class="badge badge-merah">Ditutup</span></div>
        <?php elseif ($sisa <= 7): ?>
          <div class="text-xs text-warning"><i class="ti ti-clock"></i> Sisa <?= $sisa ?> hari</div>
        <?php else: ?>
          <div class="text-xs text-muted"><i class="ti ti-lock-open"></i> Terbuka</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; else: ?>
    <p class="text-sm text-muted">Belum ada batas waktu TA <?= $tahun ?>.
      <?php if ($this->rbac->can('parameter.batas_waktu.manage')): ?>
      <a href="<?= site_url('parameter/batas-waktu') ?>">Atur →</a>
      <?php endif; ?>
    </p>
    <?php endif; ?>
    <?php if ($this->rbac->can('parameter.batas_waktu.view')): ?>
    <div class="mt-2">
      <a href="<?= site_url('parameter/batas-waktu?tahun='.$tahun) ?>"
         class="btn btn-outline btn-sm">
        <i class="ti ti-adjustments-horizontal"></i> Kelola Batas Waktu
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── DISTRIBUSI PER BIDANG ── -->
<?php if (!empty($per_bidang)): ?>
<div class="g2 mb-2">
  <div class="card">
    <div class="card-title"><i class="ti ti-chart-bar"></i> Distribusi per Bidang</div>
    <?php
    $max_nilai = max(array_column((array)$per_bidang, 'total_nilai') ?: [1]);
    foreach ($per_bidang as $b):
      $pct = $max_nilai > 0 ? round($b->total_nilai / $max_nilai * 100) : 0;
    ?>
    <div style="margin-bottom:9px">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
        <span><?= htmlspecialchars($b->nama) ?></span>
        <span class="text-muted"><?= $b->total ?> pkj · <?= rupiah_juta($b->total_nilai) ?></span>
      </div>
      <div style="height:7px;background:var(--bg);border-radius:4px;overflow:hidden">
        <div style="height:100%;background:var(--biru);width:<?= $pct ?>%;border-radius:4px"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Notifikasi -->
  <div class="card">
    <div class="card-title"><i class="ti ti-bell"></i> Notifikasi Terbaru</div>
    <?php if (!empty($notif_recent)): foreach ($notif_recent as $n):
      $icon_map = ['info'=>'info-circle','sukses'=>'circle-check','peringatan'=>'alert-triangle','error'=>'alert-circle'];
      $color_map = ['info'=>'var(--biru)','sukses'=>'var(--hijau-mid)','peringatan'=>'var(--kuning-mid)','error'=>'var(--merah-mid)'];
    ?>
    <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);align-items:flex-start">
      <i class="ti ti-<?= $icon_map[$n->jenis] ?? 'info-circle' ?>"
         style="color:<?= $color_map[$n->jenis] ?? 'var(--biru)' ?>;font-size:16px;flex-shrink:0;margin-top:2px"></i>
      <div style="flex:1;min-width:0">
        <div class="text-sm fw-500" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($n->judul) ?>
        </div>
        <div class="text-xs text-muted"><?= tgl_short($n->created_at) ?></div>
      </div>
      <?php if (!$n->is_read): ?>
      <span class="badge badge-biru" style="font-size:9px;flex-shrink:0">Baru</span>
      <?php endif; ?>
    </div>
    <?php endforeach; else: ?>
    <p class="text-sm text-muted" style="padding:12px 0">Tidak ada notifikasi.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── TABEL PER KAB/KOTA (PROVINSI) ── -->
<?php if ($is_provinsi && !empty($per_kabkota)): ?>
<?php $total_kab = count($per_kabkota); $per_kab_page = 15; ?>
<div class="card">
  <div class="card-title">
    <i class="ti ti-map"></i> Realisasi per Kab/Kota TA <?= $tahun ?>
    <a href="<?= site_url('laporan/rekap-bkp?tahun='.$tahun) ?>"
       class="btn btn-outline btn-xs" style="margin-left:auto">Lihat Laporan Lengkap</a>
  </div>
  <div class="table-wrap">
    <table class="tbl" id="tbl-kab">
      <thead>
        <tr>
          <th>Kabupaten/Kota</th>
          <th style="text-align:center">BKP</th>
          <th style="text-align:center">Pekerjaan</th>
          <th style="text-align:right">Nilai Kontrak</th>
          <th style="text-align:right">Disalurkan</th>
          <th style="text-align:center">SP2D</th>
          <th>Progress</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($per_kabkota as $idx => $kab):
        $pct_sal = ($kab->total_kontrak > 0 && $kab->total_disalurkan > 0)
          ? min(100, round($kab->total_disalurkan / $kab->total_kontrak * 100)) : 0;
      ?>
      <tr class="kab-row" data-idx="<?= $idx ?>" <?= $idx >= $per_kab_page ? 'style="display:none"' : '' ?>>
        <td class="text-sm fw-500"><?= htmlspecialchars($kab->nama) ?></td>
        <td class="center text-sm"><?= $kab->total_bkp > 0 ? $kab->total_bkp . ' BKP' : '<span class="text-muted">—</span>' ?></td>
        <td class="center text-sm"><?= $kab->total_pekerjaan ?></td>
        <td class="text-sm" style="text-align:right"><?= $kab->total_kontrak ? rupiah_juta($kab->total_kontrak) : '—' ?></td>
        <td class="text-sm fw-500" style="text-align:right;color:var(--teal-mid)"><?= $kab->total_disalurkan ? rupiah_juta($kab->total_disalurkan) : '—' ?></td>
        <td class="center text-sm"><?= $kab->total_sp2d ?: '—' ?></td>
        <td style="min-width:80px">
          <?php if ($pct_sal > 0): ?>
          <div style="height:6px;background:var(--bg);border-radius:3px;overflow:hidden">
            <div style="height:100%;background:<?= $pct_sal >= 100 ? 'var(--hijau-mid)' : 'var(--teal-mid)' ?>;width:<?= $pct_sal ?>%"></div>
          </div>
          <div class="text-xs text-muted" style="margin-top:1px"><?= $pct_sal ?>%</div>
          <?php else: ?>
          <span class="text-muted text-xs">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_kab > $per_kab_page): ?>
  <!-- Pagination client-side -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0 2px;flex-wrap:wrap;gap:8px">
    <div style="font-size:13px;color:var(--abu)" id="kab-info">
      Menampilkan <strong>1–<?= min($per_kab_page, $total_kab) ?></strong> dari <strong><?= $total_kab ?></strong> kab/kota
    </div>
    <div style="display:flex;gap:4px;align-items:center">
      <button onclick="kabPage(-1)" id="kab-prev" class="btn btn-outline btn-sm" disabled>
        <i class="ti ti-chevron-left"></i>
      </button>
      <span id="kab-page-label" style="font-size:13px;padding:0 8px;color:var(--abu)">Hal 1</span>
      <button onclick="kabPage(1)" id="kab-next" class="btn btn-outline btn-sm"
              <?= $total_kab <= $per_kab_page ? 'disabled' : '' ?>>
        <i class="ti ti-chevron-right"></i>
      </button>
    </div>
  </div>
  <script>
  (function(){
    var page    = 0;
    var perPage = <?= $per_kab_page ?>;
    var total   = <?= $total_kab ?>;
    var rows    = document.querySelectorAll('#tbl-kab .kab-row');

    function render() {
      var start = page * perPage;
      var end   = Math.min(start + perPage, total);
      rows.forEach(function(r) {
        var idx = parseInt(r.getAttribute('data-idx'));
        r.style.display = (idx >= start && idx < end) ? '' : 'none';
      });
      document.getElementById('kab-info').innerHTML =
        'Menampilkan <strong>' + (start+1) + '–' + end + '</strong> dari <strong>' + total + '</strong> kab/kota';
      document.getElementById('kab-page-label').textContent = 'Hal ' + (page+1);
      document.getElementById('kab-prev').disabled = (page === 0);
      document.getElementById('kab-next').disabled = (end >= total);
    }

    window.kabPage = function(dir) {
      var maxPage = Math.ceil(total / perPage) - 1;
      page = Math.max(0, Math.min(maxPage, page + dir));
      render();
    };
  })();
  </script>
  <?php endif; ?>

</div>
<?php endif; ?>

<!-- Quick Links -->
<div class="card mt-2">
  <div class="card-title"><i class="ti ti-rocket"></i> Akses Cepat</div>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php
    $links = [
      ['perm'=>'parameter.bkp.view',    'url'=>'parameter/bkp?tahun='.$tahun,  'icon'=>'database',        'label'=>'Data BKP'],
      ['perm'=>'parameter.batas_waktu.view','url'=>'parameter/batas-waktu?tahun='.$tahun,'icon'=>'calendar-time','label'=>'Batas Waktu'],
      ['perm'=>'pekerjaan.view',         'url'=>'pekerjaan',                    'icon'=>'file-text',       'label'=>'Pekerjaan'],
      ['perm'=>'reviu.view',             'url'=>'reviu',                        'icon'=>'clipboard-check', 'label'=>'Reviu'],
      ['perm'=>'verif_kab.view',         'url'=>'verifikasi/kab',               'icon'=>'shield-check',    'label'=>'Verif. Kab'],
      ['perm'=>'verif_prov.view',        'url'=>'verifikasi/prov',              'icon'=>'cash',            'label'=>'Penyaluran'],
      ['perm'=>'laporan.view',           'url'=>'laporan/rekap-bkp',            'icon'=>'report',          'label'=>'Laporan'],
      ['perm'=>'admin.view',             'url'=>'admin/users',                  'icon'=>'users',           'label'=>'Pengguna'],
    ];
    foreach ($links as $lnk): if (!$this->rbac->can($lnk['perm'])) continue; ?>
    <a href="<?= site_url($lnk['url']) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-<?= $lnk['icon'] ?>"></i> <?= $lnk['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
