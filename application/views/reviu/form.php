<?php
$p  = $pekerjaan;
$r  = $reviu;
$confirmed   = $r && !empty($r->checklist_confirmed_at);
$lhr_done    = $r && !empty($r->no_lhr) && !empty($r->file_lhr_path);
$keputusan   = $r ? $r->hasil_reviu : NULL;
$can_input   = $this->rbac->can('reviu.input');
$can_approve = $this->rbac->can('reviu.approve');

// Hitung stats
$total_item      = count($items);
$filled          = $r ? ($stat['sesuai'] + $stat['tidak_sesuai'] + $stat['tidak_berlaku']) : 0;
$pct_filled      = $total_item > 0 ? round($filled / $total_item * 100) : 0;
$semua_terisi    = ($filled >= $total_item && $total_item > 0);
$ada_tidak_sesuai= $r && ($stat['tidak_sesuai'] ?? 0) > 0;
?>

<!-- ── HEADER ───────────────────────────────────────────── -->
<div class="page-header">
  <div>
    <div class="page-title">
      <i class="ti ti-clipboard-check"></i> Reviu — <?= htmlspecialchars($p->kode_bkp) ?>
    </div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <?= badge_jenis($p->jenis_penyaluran) ?>
      <span class="badge badge-abu"><?= htmlspecialchars($tahapan->label_tahap) ?></span>
      <span class="text-xs text-muted"><?= htmlspecialchars($p->nama_kabkota) ?> · TA <?= $p->tahun ?></span>
      <!-- Status tahap reviu -->
      <?php if ($keputusan): ?>
        <?php $hmap=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Dikembalikan ke OPD'],'ditolak'=>['merah','Ditolak']]; $h=$hmap[$keputusan]??['abu','?']; ?>
        <span class="badge badge-<?= $h[0] ?>"><i class="ti ti-gavel" style="font-size:10px"></i> <?= $h[1] ?></span>
      <?php elseif ($lhr_done): ?>
        <span class="badge badge-biru">LHR Terupload — Siap Putuskan</span>
      <?php elseif ($confirmed): ?>
        <span class="badge badge-kuning">Checklist Terkunci — Menunggu LHR</span>
      <?php else: ?>
        <span class="badge badge-abu">Checklist Belum Selesai</span>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= site_url('reviu') ?>" class="btn btn-outline btn-sm">
    <i class="ti ti-arrow-left"></i> Kembali
  </a>
</div>

<!-- ══════════════════════════════════════════════════════════
     BLOK 1: DATA PEKERJAAN OPD (collapsible reference)
     ══════════════════════════════════════════════════════════ -->
<div class="card mb-2">
  <div class="card-title" style="cursor:pointer" onclick="toggleDataPekerjaan()">
    <i class="ti ti-file-text"></i> Data Pekerjaan OPD Teknis
    <span class="text-xs text-muted fw-500" style="margin-left:8px" id="dp-hint"></span>
    <i class="ti ti-chevron-up" id="dp-chevron" style="margin-left:auto"></i>
  </div>

  <div id="dataPekerjaan">
    <!-- Ringkasan cepat -->
    <div class="g3 mb-2">
      <div>
        <div class="text-xs text-muted">Uraian BKP</div>
        <div class="text-sm fw-500"><?= htmlspecialchars($p->uraian_bkp) ?></div>
      </div>
      <div>
        <div class="text-xs text-muted">Nama Kegiatan</div>
        <div class="text-sm"><?= htmlspecialchars($p->nama_kegiatan_dok ?: '—') ?></div>
      </div>
      <div>
        <div class="text-xs text-muted">Nilai Kontrak</div>
        <div class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></div>
      </div>
    </div>

    <!-- 17 Field lengkap -->
    <table class="tbl mb-2" style="font-size:12px">
      <thead>
        <tr><th style="width:35%">Field</th><th>Nilai</th></tr>
      </thead>
      <tbody>
        <tr><td class="text-muted">1. Nama Kegiatan</td><td><?= htmlspecialchars($p->nama_kegiatan_dok ?: '—') ?></td></tr>
        <tr><td class="text-muted">2. Volume & Satuan</td><td><?= htmlspecialchars($p->volume_satuan ?: '—') ?></td></tr>
        <tr><td class="text-muted">3. Metode Pelaksanaan</td><td><?= htmlspecialchars($p->metode_pelaksanaan ?: '—') ?></td></tr>
        <tr><td class="text-muted">4. Jenis Pekerjaan</td><td><?= htmlspecialchars($p->jenis_pekerjaan ?: '—') ?></td></tr>
        <tr><td class="text-muted">5. Jangka Waktu</td><td><?= $p->jangka_waktu_hari ? $p->jangka_waktu_hari.' hari' : '—' ?></td></tr>
        <tr><td class="text-muted">6. Nama Penyedia</td><td class="fw-500"><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></td></tr>
        <tr><td class="text-muted">7. Alamat Penyedia</td><td><?= htmlspecialchars($p->alamat_penyedia ?: '—') ?></td></tr>
        <tr><td class="text-muted">8. No. Dok. Pekerjaan</td><td class="mono"><?= htmlspecialchars($p->no_dok_pekerjaan ?: '—') ?></td></tr>
        <tr><td class="text-muted">9. Tgl. Dokumen</td><td><?= tgl_indo($p->tgl_dok_pekerjaan) ?></td></tr>
        <tr><td class="text-muted">10. No. SPMK</td><td class="mono"><?= htmlspecialchars($p->no_spmk ?: '—') ?></td></tr>
        <tr><td class="text-muted">11. Tgl. SPMK</td><td><?= tgl_indo($p->tgl_spmk) ?></td></tr>
        <?php if ($p->jenis_penyaluran === 'sekaligus'): ?>
        <tr><td class="text-muted">12. No. BAST</td><td class="mono"><?= htmlspecialchars($p->no_bast ?: '—') ?></td></tr>
        <tr><td class="text-muted">13. Tgl. BAST</td><td><?= tgl_indo($p->tgl_bast) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted">14. Nilai Kontrak</td><td class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></td></tr>
        <tr><td class="text-muted">15. Belanja Pendukung</td><td><?= rupiah($p->nilai_belanja_pendukung) ?></td></tr>
        <tr><td class="text-muted">16. Perda APBD</td><td><?= $p->no_perda ? 'No. '.htmlspecialchars($p->no_perda).' / '.tgl_indo($p->tgl_perda) : '—' ?></td></tr>
        <tr><td class="text-muted">17. Perkada/Pergub BKP</td><td><?= $p->no_perkada ? 'No. '.htmlspecialchars($p->no_perkada).' / '.tgl_indo($p->tgl_perkada) : '—' ?></td></tr>
      </tbody>
    </table>

    <!-- Peta Lokasi Pekerjaan -->
    <?php if ($p->latitude && $p->longitude): ?>
    <div class="mb-2">
      <div class="text-xs text-muted fw-600 mb-1" style="text-transform:uppercase;letter-spacing:0.5px">
        <i class="ti ti-map-pin"></i> Lokasi Pekerjaan
      </div>
      <div class="text-xs text-muted mb-1">
        <?= htmlspecialchars($p->lokasi_deskripsi ?: '') ?>
        &nbsp;📍 <?= $p->latitude ?>, <?= $p->longitude ?>
      </div>
      <div id="mapReviu" style="height:220px;border-radius:var(--radius);border:1px solid var(--border)"></div>
    </div>
    <?php else: ?>
    <div class="mb-2 text-sm text-muted">
      <i class="ti ti-map-off"></i> Lokasi belum ditentukan oleh OPD.
    </div>
    <?php endif; ?>

    <!-- Dokumen yang diupload OPD -->
    <div>
      <div class="text-xs text-muted fw-600 mb-1" style="text-transform:uppercase;letter-spacing:0.5px">Dokumen yang Diupload OPD</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <?php
        $dok_draft = [
            'SPK'  => $p->dok_spk_path  ?? NULL,
            'SPMK' => $p->dok_spmk_path ?? NULL,
            'BAST' => $p->dok_bast_path  ?? NULL,
        ];
        foreach ($dok_draft as $label => $path):
          if (!$path) continue;
        ?>
        <a href="<?= base_url($path) ?>" target="_blank"
           style="display:flex;align-items:center;gap:6px;padding:7px 12px;
                  border:1px solid var(--hijau-mid);border-radius:var(--radius);
                  font-size:12px;text-decoration:none;color:var(--hijau-mid);
                  background:var(--hijau-light)">
          <i class="ti ti-file-check" style="font-size:15px"></i>
          <strong><?= $label ?></strong>
        </a>
        <?php endforeach; ?>
        <?php if (!empty($dokumen)): foreach ($dokumen as $d): ?>
        <a href="<?= base_url($d->file_path) ?>" target="_blank"
           style="display:flex;align-items:center;gap:6px;padding:7px 12px;
                  border:1px solid var(--border);border-radius:var(--radius);
                  font-size:12px;text-decoration:none;color:var(--text)">
          <i class="ti <?= icon_file($d->file_path) ?>" style="color:var(--biru);font-size:15px"></i>
          <div><div class="fw-500"><?= label_jenis_dok($d->jenis_dokumen) ?></div>
          <div class="text-xs text-muted"><?= $d->ukuran_kb ?> KB</div></div>
        </a>
        <?php endforeach; endif; ?>
        <?php if (empty($dokumen) && !array_filter(array_values($dok_draft))): ?>
        <span class="text-sm text-muted">Belum ada dokumen yang diupload OPD.</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="mt-2">
      <a href="<?= site_url('pekerjaan/detail/'.$p->id) ?>" class="btn btn-outline btn-xs" target="_blank">
        <i class="ti ti-external-link"></i> Lihat Detail Pekerjaan Lengkap
      </a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     BLOK 2: STATISTIK + PROGRESS CHECKLIST
     ══════════════════════════════════════════════════════════ -->
<?php if ($r): ?>
<div class="g4 mb-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--biru-light)"><i class="ti ti-list-check" style="color:var(--biru)"></i></div>
    <div class="stat-val"><?= $total_item ?></div>
    <div class="stat-label">Total Item</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--hijau-light)"><i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i></div>
    <div class="stat-val" style="color:var(--hijau-mid)"><?= $stat['sesuai'] ?></div>
    <div class="stat-label">Sesuai</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--merah-light)"><i class="ti ti-circle-x" style="color:var(--merah-mid)"></i></div>
    <div class="stat-val" style="color:var(--merah-mid)"><?= $stat['tidak_sesuai'] ?></div>
    <div class="stat-label">Tidak Sesuai</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--abu-light)"><i class="ti ti-minus-circle" style="color:var(--abu)"></i></div>
    <div class="stat-val"><?= $stat['tidak_berlaku'] ?></div>
    <div class="stat-label">Tidak Berlaku / N/A</div>
  </div>
</div>
<div style="margin-bottom:16px">
  <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
    <span>Progress pengisian checklist</span>
    <span class="fw-500"><?= $filled ?> / <?= $total_item ?> diisi · <?= $pct_filled ?>%</span>
  </div>
  <div style="height:8px;background:var(--bg);border-radius:4px;overflow:hidden">
    <div style="height:100%;background:<?= $pct_filled >= 100 ? 'var(--hijau-mid)' : 'var(--biru)' ?>;width:<?= $pct_filled ?>%;transition:width 0.3s"></div>
  </div>
  <?php if ($confirmed): ?>
  <div class="text-xs mt-1" style="color:var(--hijau-mid)">
    <i class="ti ti-lock"></i> Checklist dikunci pada <?= tgl_short($r->checklist_confirmed_at) ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     BLOK 3: FORM CHECKLIST
     ══════════════════════════════════════════════════════════ -->
<?php if ($can_input && $r && !$confirmed): ?>
<?= form_open(site_url('reviu/simpan-checklist/'.$r->id), ['id'=>'formChecklist']) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
<?= form_hidden('action', 'save', ['id'=>'hiddenAction']) ?>
<?php endif; ?>

<div class="card mb-2">
  <div class="card-title">
    <i class="ti ti-list-check"></i> Checklist Reviu
    <span class="badge badge-biru" style="font-size:10px;margin-left:6px">
      <?= $total_item ?> item · <?= ucfirst(str_replace('_',' ',$p->jenis_penyaluran)) ?> · <?= $tahapan->kode_tahap ?>
    </span>
    <?php if ($confirmed): ?>
    <span class="badge badge-hijau" style="margin-left:auto;font-size:10px">
      <i class="ti ti-lock" style="font-size:10px"></i> Terkunci
    </span>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
  <div class="alert alert-info"><i class="ti ti-info-circle"></i>
    <div>Tidak ada item checklist yang berlaku untuk kombinasi jenis/tahapan ini.</div>
  </div>
  <?php else: ?>

  <div class="table-wrap">
  <table class="tbl" id="tblChecklist">
    <thead>
      <tr>
        <th style="width:50px">Kode</th>
        <th>Uraian Item Pemeriksaan</th>
        <th style="width:200px;text-align:center">Hasil</th>
        <th style="width:230px">Catatan Temuan</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item):
      $is_val = $isian[$item->id] ?? NULL;
      $nilai  = $is_val ? $is_val->nilai : 'tidak_berlaku';
    ?>
    <tr id="row-<?= $item->id ?>" class="<?= $nilai === 'tidak_sesuai' ? 'ck-row-masalah' : '' ?>">
      <td class="text-muted text-xs mono fw-500" style="text-align:center"><?= $item->kode ?></td>
      <td class="text-sm">
        <?= htmlspecialchars($item->uraian_item) ?>
        <?php if ($item->jenis_penyaluran): ?>
        <span class="badge badge-abu" style="font-size:9px;margin-left:4px"><?= $item->jenis_penyaluran ?></span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($can_input && $r && !$confirmed): ?>
        <div style="display:flex;flex-direction:column;gap:4px">
          <?php foreach (['sesuai'=>['hijau','✓ Sesuai'],'tidak_sesuai'=>['merah','✗ Tidak Sesuai'],'tidak_berlaku'=>['abu','— N/A']] as $val=>[$cl,$lab]): ?>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:3px 6px;border-radius:4px;border:1px solid <?= $nilai===$val ? 'var(--'.$cl.'-mid,var(--'.$cl.'))' : 'transparent' ?>;background:<?= $nilai===$val ? 'var(--'.$cl.'-light)' : 'transparent' ?>">
            <input type="radio" name="nilai[<?= $item->id ?>]" value="<?= $val ?>"
              <?= $nilai===$val ? 'checked' : '' ?>
              onchange="onNilaiChange(<?= $item->id ?>, this)">
            <span><?= $lab ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <?php $cl_map=['sesuai'=>'hijau','tidak_sesuai'=>'merah','tidak_berlaku'=>'abu'];
                $lb_map=['sesuai'=>'✓ Sesuai','tidak_sesuai'=>'✗ Tidak Sesuai','tidak_berlaku'=>'— N/A']; ?>
          <span class="badge badge-<?= $cl_map[$nilai] ?? 'abu' ?>" style="font-size:11px">
            <?= $lb_map[$nilai] ?? '—' ?>
          </span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($can_input && $r && !$confirmed): ?>
        <input type="text" name="catatan[<?= $item->id ?>]"
          id="catatan_<?= $item->id ?>"
          class="form-control fc-sm"
          value="<?= htmlspecialchars($is_val->catatan ?? '') ?>"
          placeholder="Catatan temuan..." maxlength="500">
        <?php else: ?>
        <span class="text-sm <?= empty($is_val->catatan) ? 'text-muted' : '' ?>">
          <?= $is_val && $is_val->catatan ? htmlspecialchars($is_val->catatan) : '—' ?>
        </span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <!-- Tombol aksi checklist (hanya saat belum terkunci) -->
  <?php if ($can_input && $r && !$confirmed): ?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:8px">
    <div style="display:flex;gap:8px">
      <button type="button" class="btn btn-outline btn-sm" onclick="isiSemua('sesuai')">
        <i class="ti ti-checks"></i> Semua Sesuai
      </button>
      <button type="button" class="btn btn-outline btn-sm" onclick="simpanDraft()">
        <i class="ti ti-device-floppy"></i> Simpan Draft
      </button>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="konfirmasiKunci()"
            <?= $semua_terisi ? '' : 'disabled title="Isi semua item checklist terlebih dahulu"' ?>>
      <i class="ti ti-lock"></i> Selesai & Kunci Checklist
    </button>
  </div>
  <?php if (!$semua_terisi): ?>
  <div class="text-xs text-muted mt-1" style="text-align:right">
    Isi semua <?= $total_item - $filled ?> item yang belum terisi untuk mengunci checklist.
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php endif; // end empty items ?>
</div>

<?php if ($can_input && $r && !$confirmed): ?>
<?= form_close() ?>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     BLOK 4: CETAK + LHR (muncul setelah checklist terkunci)
     ══════════════════════════════════════════════════════════ -->
<?php if ($r && $confirmed && !$keputusan): ?>
<div class="g2 mb-2">

  <!-- Panel Print Kertas Kerja -->
  <div class="card" style="border-left:4px solid var(--biru)">
    <div class="card-title"><i class="ti ti-printer"></i> Kertas Kerja Reviu</div>
    <p class="text-sm text-muted mb-3">
      Cetak kertas kerja checklist untuk ditandatangani oleh reviewer.
      Masukkan data reviewer sebelum mencetak.
    </p>
    <div class="form-group mb-2">
      <label>Nama Reviewer</label>
      <input type="text" id="rvNama" class="form-control" placeholder="Nama lengkap reviewer"
             value="<?= htmlspecialchars($pejabat->nama ?? '') ?>">
    </div>
    <div class="form-group mb-2">
      <label>NIP</label>
      <input type="text" id="rvNip" class="form-control mono" placeholder="NIP reviewer"
             value="<?= htmlspecialchars($pejabat->nip ?? '') ?>">
    </div>
    <div class="form-group mb-3">
      <label>Jabatan</label>
      <input type="text" id="rvJabatan" class="form-control" placeholder="Jabatan reviewer"
             value="Inspektur">
    </div>
    <button type="button" class="btn btn-primary" onclick="cetakKertasKerja()">
      <i class="ti ti-printer"></i> Cetak Kertas Kerja
    </button>
    <div class="text-xs text-muted mt-2">
      <i class="ti ti-info-circle"></i>
      Setelah dicetak dan ditandatangani, scan dan upload sebagai bagian dari LHR.
    </div>
  </div>

  <!-- Panel Upload LHR -->
  <div class="card" style="border-left:4px solid <?= $lhr_done ? 'var(--hijau-mid)' : 'var(--kuning-mid)' ?>">
    <div class="card-title">
      <i class="ti ti-file-certificate"></i> Laporan Hasil Reviu (LHR)
      <?php if ($lhr_done): ?>
      <span class="badge badge-hijau" style="margin-left:auto">Terupload</span>
      <?php else: ?>
      <span class="badge badge-kuning" style="margin-left:auto">Belum Upload</span>
      <?php endif; ?>
    </div>

    <?php if ($lhr_done): ?>
    <div style="padding:10px;background:var(--hijau-light);border-radius:var(--radius);margin-bottom:12px">
      <div class="text-sm fw-500"><i class="ti ti-circle-check" style="color:var(--hijau-mid)"></i> LHR Sudah Terupload</div>
      <div class="text-sm mt-1">No. LHR: <strong><?= htmlspecialchars($r->no_lhr) ?></strong></div>
      <div class="text-sm">Tanggal: <?= tgl_indo($r->tgl_lhr) ?></div>
      <div style="margin-top:8px;display:flex;gap:8px">
        <a href="<?= base_url($r->file_lhr_path) ?>" target="_blank" class="btn btn-outline btn-xs">
          <i class="ti ti-eye"></i> Lihat File LHR
        </a>
      </div>
    </div>
    <p class="text-xs text-muted mb-2">Upload ulang jika ada koreksi:</p>
    <?php endif; ?>

    <?= form_open_multipart(site_url('reviu/upload-lhr/'.$r->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="form-group mb-2">
      <label>No. LHR <span class="req">*</span></label>
      <input type="text" name="no_lhr" class="form-control"
        value="<?= htmlspecialchars($r->no_lhr ?? '') ?>"
        placeholder="Contoh: 700/LHR-BKP/2026/001" required>
    </div>
    <div class="form-group mb-2">
      <label>Tanggal LHR</label>
      <input type="date" name="tgl_lhr" class="form-control"
        value="<?= $r->tgl_lhr ?? date('Y-m-d') ?>">
    </div>
    <div class="form-group mb-3">
      <label>File LHR (PDF) <span class="req">*</span></label>
      <input type="file" name="file_lhr" class="form-control"
             accept=".pdf,.doc,.docx" <?= $lhr_done ? '' : 'required' ?>>
      <div class="form-hint">Format PDF/DOC, maks 10 MB.<?= $lhr_done ? ' Kosongkan jika tidak ingin mengganti file.' : '' ?></div>
    </div>
    <button type="submit" class="btn btn-primary">
      <i class="ti ti-upload"></i> <?= $lhr_done ? 'Update LHR' : 'Upload LHR' ?>
    </button>
    <?= form_close() ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     BLOK 5: KEPUTUSAN (muncul setelah LHR diupload)
     ══════════════════════════════════════════════════════════ -->
<?php if ($r && $lhr_done && !$keputusan && $can_approve): ?>
<div class="g2 mb-2">

  <!-- Setujui → Kirim ke SKPKD -->
  <div class="card" style="border-left:4px solid var(--hijau-mid)">
    <div class="card-title"><i class="ti ti-send"></i> Setujui & Kirim ke SKPKD Kab/Kota</div>
    <?php if ($ada_tidak_sesuai): ?>
    <div class="alert alert-warning mb-2" style="font-size:12px">
      <i class="ti ti-alert-triangle"></i>
      <div>Ada <strong><?= $stat['tidak_sesuai'] ?></strong> item checklist ditandai <strong>Tidak Sesuai</strong>.
      Pastikan sudah didiskusikan sebelum menyetujui.</div>
    </div>
    <?php endif; ?>
    <p class="text-sm text-muted mb-3">
      Reviu dinyatakan selesai dan data pekerjaan diteruskan ke SKPKD Kab/Kota
      untuk verifikasi dan permohonan pencairan dana.
    </p>
    <?= form_open(site_url('reviu/putuskan/'.$r->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <?= form_hidden('hasil_reviu', 'disetujui') ?>
    <div class="form-group mb-3">
      <label>Catatan Penutup <span class="text-muted text-xs">(opsional)</span></label>
      <textarea name="catatan" class="form-control" rows="2"
        placeholder="Catatan singkat hasil reviu..."></textarea>
    </div>
    <button type="submit" class="btn btn-success"
            onclick="return confirm('Setujui reviu dan kirim ke SKPKD Kab/Kota?\n\nTindakan ini tidak dapat dibatalkan.')">
      <i class="ti ti-circle-check"></i> Setujui — Kirim ke SKPKD
    </button>
    <?= form_close() ?>
  </div>

  <!-- Kembalikan ke OPD -->
  <div class="card" style="border-left:4px solid var(--kuning-mid)">
    <div class="card-title"><i class="ti ti-arrow-back-up"></i> Kembalikan ke OPD Teknis</div>
    <p class="text-sm text-muted mb-3">
      Terdapat temuan yang perlu diperbaiki OPD. Isi catatan dengan jelas
      agar OPD memahami bagian mana yang perlu diperbaiki.
    </p>
    <?= form_open(site_url('reviu/putuskan/'.$r->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <?= form_hidden('hasil_reviu', 'perlu_perbaikan') ?>
    <div class="form-group mb-3">
      <label>Catatan untuk OPD Teknis <span class="req">*</span></label>
      <textarea name="catatan" class="form-control" rows="4" required
        placeholder="Uraikan temuan dan bagian yang perlu diperbaiki OPD secara spesifik...
Contoh:
- Item CK-05: Nomor SPMK tidak sesuai dengan dokumen kontrak
- Item CK-12: Foto dokumentasi belum dilampirkan
- Nilai kontrak melebihi nilai BKP yang disepakati"></textarea>
      <div class="form-hint">Catatan ini akan terlihat oleh OPD Teknis saat memperbaiki data.</div>
    </div>
    <button type="submit" class="btn btn-outline"
            style="border-color:var(--kuning-mid);color:var(--kuning-mid)"
            onclick="return confirm('Kembalikan pekerjaan ini ke OPD Teknis?\n\nOPD akan menerima notifikasi dan bisa memperbaiki data.')">
      <i class="ti ti-arrow-back-up"></i> Kembalikan ke OPD
    </button>
    <?= form_close() ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     BLOK 6: HASIL KEPUTUSAN (setelah putuskan)
     ══════════════════════════════════════════════════════════ -->
<?php if ($keputusan): ?>
<?php
$hmap = [
    'disetujui'       => ['hijau', 'Disetujui', 'ti-circle-check',    'Data diteruskan ke SKPKD Kab/Kota untuk verifikasi.'],
    'perlu_perbaikan' => ['kuning','Perlu Perbaikan','ti-arrow-back-up','Data dikembalikan ke OPD Teknis untuk diperbaiki.'],
    'ditolak'         => ['merah', 'Ditolak',    'ti-circle-x',        'Pekerjaan ini ditolak oleh Inspektorat.'],
];
$h = $hmap[$keputusan] ?? ['abu','—','ti-question-mark',''];
?>
<div class="card mb-2" style="border-left:4px solid var(--<?= $h[0] ?>-mid,var(--<?= $h[0] ?>))">
  <div class="card-title">
    <i class="ti <?= $h[2] ?>" style="color:var(--<?= $h[0] ?>-mid,var(--<?= $h[0] ?>))"></i>
    Keputusan Reviu: <?= $h[1] ?>
  </div>
  <div class="g3">
    <div>
      <div class="text-xs text-muted">Hasil</div>
      <span class="badge badge-<?= $h[0] ?>" style="font-size:12px;padding:4px 12px"><?= $h[1] ?></span>
    </div>
    <div>
      <div class="text-xs text-muted">Tanggal Keputusan</div>
      <div class="fw-500"><?= tgl_indo($r->tgl_reviu_selesai) ?></div>
    </div>
    <div>
      <div class="text-xs text-muted">No. LHR</div>
      <div class="mono fw-500"><?= htmlspecialchars($r->no_lhr ?: '—') ?></div>
    </div>
  </div>
  <?php if ($r->catatan): ?>
  <div style="margin-top:12px;padding:12px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius)">
    <div class="text-xs text-muted fw-600 mb-1">Catatan Inspektorat:</div>
    <div class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($r->catatan) ?></div>
  </div>
  <?php endif; ?>
  <div class="mt-2" style="display:flex;gap:8px">
    <?php if ($r->file_lhr_path): ?>
    <a href="<?= base_url($r->file_lhr_path) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-download"></i> Download LHR
    </a>
    <?php endif; ?>
    <a href="<?= site_url('reviu/cetak-rekap/'.$r->id) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-report"></i> Cetak Rekap Reviu
    </a>
  </div>
  <div class="text-xs text-muted mt-1"><?= $h[3] ?></div>
</div>
<?php endif; ?>

<!-- ══ MODAL KONFIRMASI KUNCI CHECKLIST ══════════════════════ -->
<div id="modalKunci" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
     z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:28px;width:480px;max-width:95vw;text-align:center">
    <div style="font-size:48px;margin-bottom:8px">🔒</div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Kunci Checklist?</div>
    <p class="text-muted text-sm" style="margin-bottom:20px">
      Setelah dikunci, <strong>isian checklist tidak dapat diubah lagi</strong>.<br>
      Pastikan semua item sudah diperiksa dan diisi dengan benar.
    </p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button type="button" onclick="document.getElementById('modalKunci').style.display='none'"
              class="btn btn-outline">Batal — Periksa Lagi</button>
      <button type="button" onclick="eksekusiKunci()" class="btn btn-primary">
        <i class="ti ti-lock"></i> Ya, Kunci Checklist
      </button>
    </div>
  </div>
</div>

<script>
/* ── Toggle Data Pekerjaan ── */
function toggleDataPekerjaan() {
  var el   = document.getElementById('dataPekerjaan');
  var chv  = document.getElementById('dp-chevron');
  var hint = document.getElementById('dp-hint');
  var open = el.style.display !== 'none';
  el.style.display = open ? 'none' : 'block';
  chv.className    = open ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
  chv.style.marginLeft = 'auto';
  hint.textContent = open ? 'Klik untuk lihat detail' : '';
}

/* ── Cetak Kertas Kerja (buka tab baru dengan param reviewer) ── */
function cetakKertasKerja() {
  var nama    = encodeURIComponent(document.getElementById('rvNama').value.trim());
  var nip     = encodeURIComponent(document.getElementById('rvNip').value.trim());
  var jabatan = encodeURIComponent(document.getElementById('rvJabatan').value.trim());
  if (!nama) { alert('Isi Nama Reviewer terlebih dahulu.'); return; }
  var url = '<?= site_url('reviu/cetak-kertas-kerja/'.$r->id) ?>?nama='+nama+'&nip='+nip+'&jabatan='+jabatan;
  window.open(url, '_blank');
}

/* ── Checklist interactions ── */
function onNilaiChange(itemId, radioEl) {
  var row   = document.getElementById('row-' + itemId);
  var nilai = radioEl.value;
  row.classList.toggle('ck-row-masalah', nilai === 'tidak_sesuai');

  // Highlight label terpilih
  var labels = row.querySelectorAll('label');
  labels.forEach(function(lbl) {
    var inp = lbl.querySelector('input[type="radio"]');
    var cls = inp.value === 'sesuai' ? 'var(--hijau-mid)' : inp.value === 'tidak_sesuai' ? 'var(--merah-mid)' : 'var(--abu)';
    var bg  = inp.value === 'sesuai' ? 'var(--hijau-light)' : inp.value === 'tidak_sesuai' ? 'var(--merah-light)' : 'var(--abu-light)';
    if (inp.checked) {
      lbl.style.borderColor  = cls;
      lbl.style.background   = bg;
    } else {
      lbl.style.borderColor  = 'transparent';
      lbl.style.background   = 'transparent';
    }
  });

  // Tandai catatan wajib jika tidak_sesuai
  var catInput = document.getElementById('catatan_' + itemId);
  if (catInput) {
    catInput.style.borderColor = (nilai === 'tidak_sesuai') ? 'var(--kuning-mid)' : '';
    catInput.placeholder = (nilai === 'tidak_sesuai')
      ? 'Wajib isi catatan temuan...' : 'Catatan temuan...';
  }

  updateProgressBar();
}

function isiSemua(nilai) {
  document.querySelectorAll('input[type="radio"][value="' + nilai + '"]').forEach(function(r) {
    r.checked = true;
    onNilaiChange(r.name.match(/\d+/)[0], r);
  });
}

function updateProgressBar() {
  var total   = <?= $total_item ?>;
  if (!total) return;
  var radios  = document.querySelectorAll('#tblChecklist tbody tr');
  var filled  = 0;
  radios.forEach(function(row) {
    var checked = row.querySelector('input[type="radio"]:checked');
    if (checked) filled++;
  });
  // Update stat cards jika ada
}

function simpanDraft() {
  document.getElementById('hiddenAction').value = 'save';
  document.getElementById('formChecklist').submit();
}

function konfirmasiKunci() {
  document.getElementById('modalKunci').style.display = 'flex';
}

function eksekusiKunci() {
  document.getElementById('hiddenAction').value = 'confirm';
  document.getElementById('formChecklist').dataset.dirty = 'false';
  document.getElementById('formChecklist').submit();
}

/* ── Dirty state guard ── */
window.addEventListener('beforeunload', function(e) {
  var form = document.getElementById('formChecklist');
  if (form && form.dataset.dirty === 'true') {
    e.preventDefault(); e.returnValue = '';
  }
});
<?php if (document.getElementById('formChecklist')): // always false in PHP, JS runs later ?>
<?php endif; ?>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('formChecklist');
  if (!form) return;
  form.querySelectorAll('input, textarea').forEach(function(el) {
    el.addEventListener('change', function() { form.dataset.dirty = 'true'; });
  });
  form.addEventListener('submit', function() { form.dataset.dirty = 'false'; });

  // Init warna label yang sudah terisi
  form.querySelectorAll('input[type="radio"]:checked').forEach(function(r) {
    onNilaiChange(r.name.match(/\d+/)[0], r);
  });
});
</script>

<?php if ($p->latitude && $p->longitude): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
setTimeout(function() {
  var lat  = <?= (float)$p->latitude ?>;
  var lng  = <?= (float)$p->longitude ?>;
  var mapR = L.map('mapReviu', { scrollWheelZoom: false }).setView([lat, lng], 15);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(mapR);
  L.marker([lat, lng])
    .addTo(mapR)
    .bindPopup('<strong><?= htmlspecialchars($p->kode_bkp, ENT_QUOTES) ?></strong><br><?= htmlspecialchars($p->nama_kegiatan_dok ?: $p->uraian_bkp, ENT_QUOTES) ?>')
    .openPopup();
  // Paksa recalculate ukuran setelah container benar-benar terrender
  mapR.invalidateSize();
}, 300);
</script>
<?php endif; ?>
