<?php
/* ── Data prep ─────────────────────────────────────────────── */
$pek        = $stats['pek_status'] ?? [];
$bkp_obj    = $stats['bkp']  ?? (object)['total'=>0,'total_nilai'=>0];
$sp2d_obj   = $stats['sp2d'] ?? (object)['total_sp2d'=>0,'total_transfer'=>0,'selesai'=>0];
$kab_aktif  = $stats['kab_aktif'] ?? (object)['total'=>0];
$total_bkp_nilai  = (float)($bkp_obj->total_nilai ?? 0);
$total_disalurkan = (float)($sp2d_obj->total_transfer ?? 0);
$pct_realisasi    = $total_bkp_nilai > 0 ? round($total_disalurkan / $total_bkp_nilai * 100, 1) : 0;

/* Status pekerjaan — dikelompokkan untuk donut chart */
$grp_belum  = ($pek['draft'] ?? 0);
$grp_proses = ($pek['opd_submitted'] ?? 0) + ($pek['inspektorat_reviu'] ?? 0)
            + ($pek['inspektorat_revisi'] ?? 0) + ($pek['inspektorat_approved'] ?? 0)
            + ($pek['skpkd_kab_verif'] ?? 0) + ($pek['skpkd_kab_revisi'] ?? 0)
            + ($pek['skpkd_kab_approved'] ?? 0) + ($pek['skpkd_prov_verif'] ?? 0)
            + ($pek['disalurkan'] ?? 0);
$grp_selesai = ($pek['dikonfirmasi_tahap1'] ?? 0) + ($pek['selesai'] ?? 0)
             + ($pek['dikonfirmasi'] ?? 0);
$grp_tolak   = ($pek['ditolak'] ?? 0);
$total_pekerjaan = $grp_belum + $grp_proses + $grp_selesai + $grp_tolak;

/* Funnel data */
$funnel_vals   = array_values($funnel);
$funnel_labels = array_keys($funnel);
$funnel_max    = max($funnel_vals ?: [1]);

/* Per bidang */
$bidang_labels = [];
$bidang_vals   = [];
$bidang_nilai  = [];
foreach ($per_bidang as $b) {
    $bidang_labels[] = $b->nama;
    $bidang_vals[]   = (int)$b->total;
    $bidang_nilai[]  = (float)$b->total_nilai;
}

/* Top 10 kab/kota untuk chart provinsi */
$top_kab = $is_provinsi ? array_slice((array)$per_kabkota, 0, 10) : [];
$kab_labels = array_map(fn($k) => $k->nama, $top_kab);
$kab_disalurkan = array_map(fn($k) => (float)($k->total_disalurkan ?? 0) / 1000000, $top_kab);
$kab_kontrak    = array_map(fn($k) => (float)($k->total_kontrak ?? 0) / 1000000, $top_kab);
?>

<!-- ══ LOAD CHART.JS ══════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="page-header">
  <div class="page-title">
    <i class="ti ti-layout-dashboard"></i> Dashboard TA <?= $tahun ?>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="text-sm text-muted">Data per <?= date('d/m/Y H:i') ?></span>
    <?php if ($this->rbac->can('laporan.view')): ?>
    <a href="<?= site_url('laporan/rekap-bkp?tahun='.$tahun) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-report"></i> Laporan Lengkap
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- ── ALERT DEADLINE ────────────────────────────────────────── -->
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

<!-- ── ANTRIAN AKSI ──────────────────────────────────────────── -->
<?php if (!empty($antrian_aksi)): ?>
<div class="card mb-2" style="border-left:4px solid var(--biru)">
  <div class="card-title"><i class="ti ti-bell-ringing"></i> Perlu Tindakan Anda</div>
  <?php foreach ($antrian_aksi as $a): ?>
  <a href="<?= site_url($a['url']) ?>" class="antrian-item antrian-<?= $a['warna'] ?>">
    <i class="ti ti-<?= $a['icon'] ?>" style="font-size:20px;flex-shrink:0"></i>
    <span class="text-sm fw-500"><?= htmlspecialchars($a['label']) ?></span>
    <i class="ti ti-chevron-right" style="margin-left:auto;color:var(--text-muted)"></i>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── STATISTIK UTAMA ───────────────────────────────────────── -->
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)">
      <i class="ti ti-database" style="color:var(--biru)"></i>
    </div>
    <div class="stat-val" style="color:var(--biru)"><?= number_format($bkp_obj->total ?? 0) ?></div>
    <div class="stat-label">Total BKP TA <?= $tahun ?></div>
    <div class="text-xs text-muted" style="margin-top:2px"><?= rupiah($bkp_obj->total_nilai ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)">
      <i class="ti ti-file-text" style="color:var(--hijau-mid)"></i>
    </div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= number_format($total_pekerjaan) ?></div>
    <div class="stat-label">Data Pekerjaan</div>
    <div class="text-xs text-muted" style="margin-top:2px">
      <?= $grp_selesai ?> selesai &nbsp;·&nbsp; <?= $grp_tolak ?> ditolak
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
    <div class="stat-val" style="font-size:14px;color:var(--teal-mid)"><?= rupiah($total_disalurkan) ?></div>
    <div class="stat-label">Total Disalurkan</div>
    <div class="text-xs text-muted" style="margin-top:2px"><?= $pct_realisasi ?>% dari nilai BKP</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     BARIS GRAFIK 1: Realisasi Dana + Status Pekerjaan + Batas Waktu
     ══════════════════════════════════════════════════════════════ -->
<div class="g3 mb-2">

  <!-- Grafik Donut — Realisasi Keuangan -->
  <div class="card" style="display:flex;flex-direction:column;align-items:center">
    <div class="card-title" style="width:100%">
      <i class="ti ti-circle-half-2"></i> Realisasi Penyaluran Dana
    </div>
    <div style="position:relative;width:160px;height:160px;margin:8px auto;flex-shrink:0">
      <canvas id="chartRealisasi" width="160" height="160" style="display:block"></canvas>
      <div style="position:absolute;inset:0;display:flex;flex-direction:column;
                  align-items:center;justify-content:center;pointer-events:none">
        <div style="font-size:22px;font-weight:700;color:<?= $pct_realisasi > 0 ? 'var(--teal-mid)' : 'var(--text-muted)' ?>;line-height:1"><?= $pct_realisasi ?>%</div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:2px">Tersalurkan</div>
      </div>
    </div>
    <div style="display:flex;gap:16px;font-size:11px;margin-top:6px">
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#0F6E56;flex-shrink:0"></span>
        <span class="text-muted">Disalurkan</span>
      </div>
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#e5e7eb;flex-shrink:0"></span>
        <span class="text-muted">Sisa</span>
      </div>
    </div>
    <div style="text-align:center;margin-top:10px;font-size:11px;color:var(--text-muted)">
      <div><strong style="color:var(--teal-mid)"><?= rupiah($total_disalurkan) ?></strong></div>
      <div>dari <?= rupiah($total_bkp_nilai) ?></div>
    </div>
  </div>

  <!-- Grafik Donut — Status Pekerjaan -->
  <div class="card" style="display:flex;flex-direction:column;align-items:center">
    <div class="card-title" style="width:100%">
      <i class="ti ti-chart-pie"></i> Status Pekerjaan
    </div>
    <div style="width:160px;height:160px;margin:8px auto;flex-shrink:0">
      <canvas id="chartStatus" width="160" height="160" style="display:block"></canvas>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px 14px;font-size:11px;margin-top:6px;justify-content:center">
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#5F5E5A;flex-shrink:0"></span>
        <span class="text-muted">Draft (<?= $grp_belum ?>)</span>
      </div>
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#1A5EA8;flex-shrink:0"></span>
        <span class="text-muted">Proses (<?= $grp_proses ?>)</span>
      </div>
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#3B6D11;flex-shrink:0"></span>
        <span class="text-muted">Selesai (<?= $grp_selesai ?>)</span>
      </div>
      <?php if ($grp_tolak > 0): ?>
      <div style="display:flex;align-items:center;gap:4px">
        <span style="width:10px;height:10px;border-radius:50%;background:#A32D2D;flex-shrink:0"></span>
        <span class="text-muted">Ditolak (<?= $grp_tolak ?>)</span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Batas Waktu (existing) -->
  <div class="card">
    <div class="card-title"><i class="ti ti-calendar-time"></i> Batas Waktu TA <?= $tahun ?></div>
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
        <i class="ti ti-adjustments-horizontal"></i> Kelola
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     PIPELINE ALUR PROSES BKP — Diagram alur 6 tahapan
     Menunjukkan di mana setiap BKP berada dalam proses pengajuan
     ══════════════════════════════════════════════════════════════ -->
<div class="card mb-2">
  <div class="card-title">
    <i class="ti ti-route"></i> Pipeline Alur Proses BKP TA <?= $tahun ?>
    <span class="text-xs text-muted fw-500" style="margin-left:auto">
      Jumlah tahapan per fase — angka kumulatif
    </span>
  </div>

  <div class="pipeline-wrap">
    <?php
    $pipe_colors = ['#1A5EA8','#854F0B','#0F6E56','#3B6D11','#3C3489','#27500A'];
    $pipe_icons  = ['database','file-text','clipboard-check','shield-check','file-invoice','circle-check'];
    $fi = 0;
    foreach ($funnel as $label => $val):
      $pct = $funnel_max > 0 ? max(15, round($val / $funnel_max * 100)) : 15;
      $conv = ($fi > 0 && $funnel_vals[$fi-1] > 0)
              ? round($val / $funnel_vals[$fi-1] * 100) : 100;
    ?>
    <div class="pipe-stage">
      <div class="pipe-bar" style="background:<?= $pipe_colors[$fi] ?>;height:<?= $pct ?>px;min-height:32px">
        <span class="pipe-count"><?= number_format($val) ?></span>
      </div>
      <div class="pipe-arrow"><i class="ti ti-chevron-right"></i></div>
      <div class="pipe-icon" style="color:<?= $pipe_colors[$fi] ?>">
        <i class="ti ti-<?= $pipe_icons[$fi] ?>"></i>
      </div>
      <div class="pipe-label"><?= htmlspecialchars($label) ?></div>
      <?php if ($fi > 0): ?>
      <div class="pipe-conv <?= $conv < 60 ? 'pipe-conv-warn' : '' ?>">
        <?= $conv ?>% konversi
      </div>
      <?php else: ?>
      <div class="pipe-conv">Total</div>
      <?php endif; ?>
    </div>
    <?php $fi++; endforeach; ?>
  </div>

  <!-- Mini penjelasan -->
  <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;font-size:11px;color:var(--text-muted)">
    <span><i class="ti ti-info-circle"></i> Pipeline menampilkan jumlah kumulatif — setiap tahap mencakup tahap-tahap setelahnya.</span>
    <span>Konversi &lt;60% <span style="color:var(--kuning-mid);font-weight:600">⚠</span> = kemungkinan ada hambatan di tahap ini.</span>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     BARIS GRAFIK 2: Distribusi per Bidang + Grafik Bar Kab/Kota
     ══════════════════════════════════════════════════════════════ -->
<div class="<?= $is_provinsi && !empty($top_kab) ? 'g2' : '' ?> mb-2">

  <!-- Grafik Bar Horizontal — Per Bidang -->
  <div class="card">
    <div class="card-title"><i class="ti ti-chart-bar"></i> Distribusi Pekerjaan per Bidang</div>
    <?php if (!empty($per_bidang)): ?>
    <div style="position:relative;height:<?= min(400, max(180, count($per_bidang) * 38)) ?>px">
      <canvas id="chartBidang"></canvas>
    </div>
    <?php else: ?>
    <div style="padding:32px;text-align:center;color:var(--text-muted)">
      <i class="ti ti-chart-bar" style="font-size:36px;opacity:0.3;display:block;margin-bottom:8px"></i>
      <div class="text-sm">Belum ada data pekerjaan TA <?= $tahun ?></div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($is_provinsi && !empty($top_kab)): ?>
  <!-- Grafik Bar — Top Kab/Kota Realisasi (hanya provinsi) -->
  <div class="card">
    <div class="card-title"><i class="ti ti-map-pin"></i> Realisasi Penyaluran per Kab/Kota (Top 10)</div>
    <div style="position:relative;height:<?= min(400, max(180, count($top_kab) * 38)) ?>px">
      <canvas id="chartKabkota"></canvas>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- ══════════════════════════════════════════════════════════════
     BARIS GRAFIK 3: Notifikasi + Akses Cepat
     ══════════════════════════════════════════════════════════════ -->
<div class="g2 mb-2">
  <div class="card">
    <div class="card-title"><i class="ti ti-bell"></i> Notifikasi Terbaru</div>
    <?php if (!empty($notif_recent)): foreach ($notif_recent as $n):
      $icon_map  = ['info'=>'info-circle','sukses'=>'circle-check','peringatan'=>'alert-triangle','error'=>'alert-circle'];
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

  <div class="card">
    <div class="card-title"><i class="ti ti-rocket"></i> Akses Cepat</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php
    $links = [
      ['perm'=>'parameter.bkp.view',       'url'=>'parameter/bkp?tahun='.$tahun,      'icon'=>'database',        'label'=>'Data BKP'],
      ['perm'=>'parameter.batas_waktu.view','url'=>'parameter/batas-waktu?tahun='.$tahun,'icon'=>'calendar-time', 'label'=>'Batas Waktu'],
      ['perm'=>'pekerjaan.view',            'url'=>'pekerjaan',                         'icon'=>'file-text',       'label'=>'Pekerjaan'],
      ['perm'=>'reviu.view',                'url'=>'reviu',                             'icon'=>'clipboard-check', 'label'=>'Reviu'],
      ['perm'=>'verif_kab.view',            'url'=>'verifikasi/kab',                    'icon'=>'shield-check',    'label'=>'Verif. Kab'],
      ['perm'=>'verif_prov.view',           'url'=>'verifikasi/prov',                   'icon'=>'cash',            'label'=>'Penyaluran'],
      ['perm'=>'laporan.view',              'url'=>'laporan/rekap-bkp',                 'icon'=>'report',          'label'=>'Laporan'],
      ['perm'=>'admin.view',               'url'=>'admin/users',                        'icon'=>'users',           'label'=>'Pengguna'],
    ];
    foreach ($links as $lnk): if (!$this->rbac->can($lnk['perm'])) continue; ?>
    <a href="<?= site_url($lnk['url']) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-<?= $lnk['icon'] ?>"></i> <?= $lnk['label'] ?>
    </a>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── TABEL PER KAB/KOTA (PROVINSI) ────────────────────────── -->
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
          <th class="tbl-sort" data-col="0" data-type="str" style="cursor:pointer;user-select:none">
            Kabupaten/Kota <i class="ti ti-selector" id="sort-icon-0" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="1" data-type="num" style="text-align:center;cursor:pointer;user-select:none">
            BKP <i class="ti ti-selector" id="sort-icon-1" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="2" data-type="num" style="text-align:right;cursor:pointer;user-select:none">
            Total Nilai BKP <i class="ti ti-selector" id="sort-icon-2" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="3" data-type="num" style="text-align:center;cursor:pointer;user-select:none">
            Pekerjaan <i class="ti ti-selector" id="sort-icon-3" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="4" data-type="num" style="text-align:right;cursor:pointer;user-select:none">
            Nilai Kontrak <i class="ti ti-selector" id="sort-icon-4" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="5" data-type="num" style="text-align:right;cursor:pointer;user-select:none">
            Disalurkan <i class="ti ti-selector" id="sort-icon-5" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th class="tbl-sort" data-col="6" data-type="num" style="text-align:center;cursor:pointer;user-select:none">
            SP2D <i class="ti ti-selector" id="sort-icon-6" style="font-size:10px;opacity:0.5"></i>
          </th>
          <th style="min-width:90px">Progress</th>
        </tr>
      </thead>
      <tbody id="tbody-kab">
      <?php foreach ($per_kabkota as $idx => $kab):
        $pct_sal        = ($kab->total_kontrak > 0 && $kab->total_disalurkan > 0)
          ? min(100, round($kab->total_disalurkan / $kab->total_kontrak * 100)) : 0;
        $nilai_bkp_raw  = (float)($kab->total_nilai_bkp ?? 0);
        $kontrak_raw    = (float)($kab->total_kontrak ?? 0);
        $disalurkan_raw = (float)($kab->total_disalurkan ?? 0);
      ?>
      <tr class="kab-row"
          data-idx="<?= $idx ?>"
          data-d0="<?= htmlspecialchars($kab->nama) ?>"
          data-d1="<?= (int)($kab->total_bkp ?? 0) ?>"
          data-d2="<?= $nilai_bkp_raw ?>"
          data-d3="<?= (int)($kab->total_pekerjaan ?? 0) ?>"
          data-d4="<?= $kontrak_raw ?>"
          data-d5="<?= $disalurkan_raw ?>"
          data-d6="<?= (int)($kab->total_sp2d ?? 0) ?>"
          <?= $idx >= $per_kab_page ? 'style="display:none"' : '' ?>>
        <td class="text-sm fw-500"><?= htmlspecialchars($kab->nama) ?></td>
        <td class="center text-sm"><?= $kab->total_bkp > 0 ? $kab->total_bkp : '<span class="text-muted">—</span>' ?></td>
        <td class="text-sm" style="text-align:right"><?= $nilai_bkp_raw > 0 ? rupiah($nilai_bkp_raw) : '—' ?></td>
        <td class="center text-sm"><?= $kab->total_pekerjaan ?: '—' ?></td>
        <td class="text-sm" style="text-align:right"><?= $kontrak_raw > 0 ? rupiah($kontrak_raw) : '—' ?></td>
        <td class="text-sm fw-500" style="text-align:right;color:var(--teal-mid)"><?= $disalurkan_raw > 0 ? rupiah($disalurkan_raw) : '—' ?></td>
        <td class="center text-sm"><?= $kab->total_sp2d ?: '—' ?></td>
        <td style="min-width:90px">
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
  <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0 2px;flex-wrap:wrap;gap:8px">
    <div style="font-size:13px;color:var(--abu)" id="kab-info">
      Menampilkan <strong>1–<?= min($per_kab_page, $total_kab) ?></strong> dari <strong><?= $total_kab ?></strong>
    </div>
    <div style="display:flex;gap:4px;align-items:center">
      <button onclick="kabPage(-1)" id="kab-prev" class="btn btn-outline btn-sm" disabled>
        <i class="ti ti-chevron-left"></i>
      </button>
      <span id="kab-page-label" style="font-size:13px;padding:0 8px;color:var(--abu)">Hal 1</span>
      <button onclick="kabPage(1)" id="kab-next" class="btn btn-outline btn-sm">
        <i class="ti ti-chevron-right"></i>
      </button>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     CHART.JS INITIALIZATION
     Data di-pass dari PHP via json_encode — aman karena tidak ada
     user input di sini, semua data dari DB yang sudah trusted.
     ══════════════════════════════════════════════════════════════ -->
<script>
(function () {
  if (typeof Chart === 'undefined') {
    console.error('Chart.js gagal dimuat');
    return;
  }

  /* ── Default Chart.js options ── */
  Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#6b7280';
  Chart.defaults.plugins.legend.display = false;
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 6;

  /* ── Data dari PHP ── */
  var pctReal    = <?= $pct_realisasi ?>;
  var nilaiDis   = <?= $total_disalurkan ?>;
  var nilaiBkp   = <?= $total_bkp_nilai ?>;
  var grpBelum   = <?= $grp_belum ?>;
  var grpProses  = <?= $grp_proses ?>;
  var grpSelesai = <?= $grp_selesai ?>;
  var grpTolak   = <?= $grp_tolak ?>;

  var bidangLabels = <?= json_encode($bidang_labels) ?>;
  var bidangVals   = <?= json_encode($bidang_vals) ?>;
  var bidangNilai  = <?= json_encode($bidang_nilai) ?>;

  <?php if ($is_provinsi && !empty($top_kab)): ?>
  var kabLabels     = <?= json_encode(array_values($kab_labels)) ?>;
  var kabDisalurkan = <?= json_encode(array_values($kab_disalurkan)) ?>;
  var kabKontrak    = <?= json_encode(array_values($kab_kontrak)) ?>;
  <?php endif; ?>

  /* ── Helper: Gambar donut dengan native Canvas 2D ── */
  /* Tidak pakai Chart.js agar selalu render meski data = 0 */
  function drawDonut(canvasId, segments, cutoutPct) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) return;
    var ctx    = canvas.getContext('2d');
    var W = canvas.width, H = canvas.height;
    var cx = W / 2, cy = H / 2;
    var outerR = Math.min(W, H) / 2 - 4;
    var innerR = outerR * (cutoutPct / 100);
    var ringW  = outerR - innerR;

    ctx.clearRect(0, 0, W, H);

    var total = segments.reduce(function(s, seg) { return s + (seg.v || 0); }, 0);

    if (total <= 0) {
      /* Placeholder ring abu-abu */
      ctx.beginPath();
      ctx.arc(cx, cy, (outerR + innerR) / 2, 0, 2 * Math.PI);
      ctx.strokeStyle = '#e5e7eb';
      ctx.lineWidth   = ringW;
      ctx.stroke();
      return;
    }

    /* Gambar setiap segmen */
    var startAngle = -Math.PI / 2;
    segments.forEach(function(seg) {
      if (!seg.v) return;
      var sweep = (seg.v / total) * 2 * Math.PI;
      ctx.beginPath();
      ctx.arc(cx, cy, (outerR + innerR) / 2, startAngle, startAngle + sweep);
      ctx.strokeStyle = seg.c;
      ctx.lineWidth   = ringW;
      ctx.stroke();
      startAngle += sweep;
    });
  }

  /* ── Grafik 1: Realisasi Keuangan ── */
  drawDonut('chartRealisasi', [
    { v: nilaiDis,                         c: '#0F6E56' },
    { v: Math.max(0, nilaiBkp - nilaiDis), c: '#e5e7eb' }
  ], 72);

  /* ── Grafik 2: Status Pekerjaan ── */
  drawDonut('chartStatus', [
    { v: grpBelum,   c: '#5F5E5A' },
    { v: grpProses,  c: '#1A5EA8' },
    { v: grpSelesai, c: '#3B6D11' },
    { v: grpTolak,   c: '#A32D2D' }
  ], 60);

  /* ── Grafik 3: Distribusi per Bidang (Horizontal Bar) ── */
  var ctxBidang = document.getElementById('chartBidang');
  if (ctxBidang && bidangLabels.length > 0) {
    new Chart(ctxBidang, {
      type: 'bar',
      data: {
        labels: bidangLabels,
        datasets: [
          {
            label: 'Jumlah Pekerjaan',
            data: bidangVals,
            backgroundColor: 'rgba(26,94,168,0.75)',
            borderColor:     '#1A5EA8',
            borderWidth: 1,
            borderRadius: 4,
            yAxisID: 'yCount',
          },
          {
            label: 'Nilai (Juta Rp)',
            data: bidangNilai.map(function(v){ return v / 1000000; }),
            backgroundColor: 'rgba(15,110,86,0.6)',
            borderColor:     '#0F6E56',
            borderWidth: 1,
            borderRadius: 4,
            yAxisID: 'yNilai',
          }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: { boxWidth: 12, padding: 12 }
          },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                if (ctx.datasetIndex === 0) return 'Pekerjaan: ' + ctx.raw;
                return 'Nilai: Rp ' + ctx.raw.toFixed(2) + ' Jt';
              }
            }
          }
        },
        scales: {
          yCount: { position: 'left',  display: false, beginAtZero: true },
          yNilai: { position: 'right', display: false, beginAtZero: true },
          x: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  /* ── Grafik 4: Top Kab/Kota (Horizontal Bar — hanya provinsi) ── */
  <?php if ($is_provinsi && !empty($top_kab)): ?>
  var ctxKab = document.getElementById('chartKabkota');
  if (ctxKab && kabLabels.length > 0) {
    new Chart(ctxKab, {
      type: 'bar',
      data: {
        labels: kabLabels,
        datasets: [
          {
            label: 'Nilai Kontrak (Jt)',
            data: kabKontrak,
            backgroundColor: 'rgba(26,94,168,0.45)',
            borderColor:     '#1A5EA8',
            borderWidth: 1,
            borderRadius: 4,
          },
          {
            label: 'Disalurkan (Jt)',
            data: kabDisalurkan,
            backgroundColor: 'rgba(15,110,86,0.8)',
            borderColor:     '#0F6E56',
            borderWidth: 1,
            borderRadius: 4,
          }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: { boxWidth: 12, padding: 12 }
          },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                return ctx.dataset.label + ': Rp ' + ctx.raw.toFixed(2) + ' Jt';
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { callback: function(v){ return 'Rp '+v+' Jt'; } } }
        }
      }
    });
  }
  <?php endif; ?>

})();

/* ── Pagination tabel kab/kota — global agar onclick HTML bisa memanggil ── */
(function(){
  var page    = 0;
  var perPage = <?= $per_kab_page ?? 15 ?>;
  var total   = <?= $total_kab ?? 0 ?>;

  function render() {
    var rows  = document.querySelectorAll('#tbl-kab .kab-row');
    if (!rows.length) return;
    var start = page * perPage;
    var end   = Math.min(start + perPage, total);
    rows.forEach(function(r) {
      var idx = parseInt(r.getAttribute('data-idx'));
      r.style.display = (idx >= start && idx < end) ? '' : 'none';
    });
    var info = document.getElementById('kab-info');
    if (info) info.innerHTML = 'Menampilkan <strong>'+(start+1)+'–'+end+'</strong> dari <strong>'+total+'</strong> kab/kota';
    var lbl  = document.getElementById('kab-page-label');
    if (lbl)  lbl.textContent = 'Hal ' + (page+1);
    var prev = document.getElementById('kab-prev');
    var next = document.getElementById('kab-next');
    if (prev) prev.disabled = (page === 0);
    if (next) next.disabled = (end >= total);
  }

  window.kabPage = function(dir) {
    var maxPage = Math.ceil(total / perPage) - 1;
    page = Math.max(0, Math.min(maxPage, page + dir));
    render();
  };
})();

/* ── Sort tabel Realisasi per Kab/Kota ── */
(function() {
  var sortCol = -1, sortAsc = true;

  function sortTable(colIdx, colType) {
    var tbody = document.getElementById('tbody-kab');
    if (!tbody) return;

    // Toggle arah sort jika kolom sama
    if (sortCol === colIdx) {
      sortAsc = !sortAsc;
    } else {
      sortCol = colIdx;
      sortAsc = true;
    }

    // Update ikon header
    document.querySelectorAll('#tbl-kab .tbl-sort i').forEach(function(ic, i) {
      ic.className = 'ti ti-selector';
      ic.style.opacity = '0.4';
    });
    var activeIcon = document.getElementById('sort-icon-' + colIdx);
    if (activeIcon) {
      activeIcon.className = sortAsc ? 'ti ti-sort-ascending' : 'ti ti-sort-descending';
      activeIcon.style.opacity = '1';
    }

    // Ambil semua baris dan sort
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.kab-row'));
    rows.sort(function(a, b) {
      var va = a.getAttribute('data-d' + colIdx) || '';
      var vb = b.getAttribute('data-d' + colIdx) || '';
      var cmp;
      if (colType === 'num') {
        cmp = parseFloat(va || 0) - parseFloat(vb || 0);
      } else {
        cmp = va.localeCompare(vb, 'id');
      }
      return sortAsc ? cmp : -cmp;
    });

    // Re-insert baris sesuai urutan sort baru & update data-idx
    rows.forEach(function(row, i) {
      row.setAttribute('data-idx', i);
      tbody.appendChild(row);
    });

    // Reset pagination ke halaman 1
    if (typeof kabPage === 'function') {
      // Trigger re-render pagination
      window.kabSortReset && window.kabSortReset();
    }
  }

  // Bind click ke setiap header yang sortable
  document.querySelectorAll('#tbl-kab .tbl-sort').forEach(function(th) {
    th.addEventListener('click', function() {
      var col  = parseInt(th.getAttribute('data-col'));
      var type = th.getAttribute('data-type') || 'str';
      sortTable(col, type);
    });
    th.style.cursor = 'pointer';
  });

  // Expose reset fungsi untuk pagination setelah sort
  window.kabSortReset = function() {
    // Re-render pagination dari index 0
    var rows = document.querySelectorAll('#tbody-kab tr.kab-row');
    var perPage = <?= $per_kab_page ?? 15 ?>;
    rows.forEach(function(r, i) {
      r.style.display = i < perPage ? '' : 'none';
    });
    var info = document.getElementById('kab-info');
    if (info) {
      var shown = Math.min(perPage, rows.length);
      info.innerHTML = 'Menampilkan <strong>1–' + shown + '</strong> dari <strong>' + rows.length + '</strong> kab/kota';
    }
    var lbl = document.getElementById('kab-page-label');
    if (lbl) lbl.textContent = 'Hal 1';
  };
})();
</script>

