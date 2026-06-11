<?php $p = $pekerjaan; ?>

<div class="page-header">
  <div>
    <div class="page-title">
      <i class="ti ti-cash"></i> Penyaluran — <?= htmlspecialchars($p->kode_bkp) ?>
    </div>
    <div style="margin-top:4px;display:flex;align-items:center;gap:8px">
      <?= badge_jenis($p->jenis_penyaluran) ?>
      <span class="badge badge-abu"><?= htmlspecialchars($tahapan->label_tahap) ?></span>
      <span class="text-xs text-muted">
        <?= htmlspecialchars($p->nama_kabkota) ?> · TA <?= $p->tahun ?>
      </span>
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($this->rbac->can('pekerjaan.view')): ?>
    <a href="<?= site_url('pekerjaan/detail/'.$p->id.'?back='.urlencode(site_url('verifikasi/prov/form/'.$tahapan->id))) ?>"
       class="btn btn-outline btn-sm">
      <i class="ti ti-external-link"></i> Detail Pekerjaan
    </a>
    <?php endif; ?>
    <?php if ($this->rbac->can('verif_kab.cetak_rekap')): ?>
    <a href="<?= site_url('verifikasi/kab/cetak-rekap/'.$tahapan->id) ?>"
       target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-printer"></i> Rekap Kegiatan Kab
    </a>
    <?php endif; ?>
    <a href="<?= site_url('verifikasi/prov') ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<!-- Status bar -->
<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;
            background:var(--bg);border-radius:var(--radius);border:1px solid var(--border);
            margin-bottom:14px;flex-wrap:wrap">
  <span class="text-sm text-muted">Status:</span>
  <?= badge_status($p->status) ?>
  <?php if ($penyaluran && $penyaluran->no_sp2d): ?>
  <span class="text-muted">·</span>
  <span class="text-sm">SP2D: <strong class="mono"><?= htmlspecialchars($penyaluran->no_sp2d) ?></strong></span>
  <?php endif; ?>
  <?php if ($penyaluran && $penyaluran->status_transfer === 'selesai'): ?>
  <span class="badge badge-hijau"><i class="ti ti-check"></i> Transfer Selesai</span>
  <?php endif; ?>
</div>

<div class="g2">

  <!-- ── Kolom Kiri: Ringkasan + Dokumen ── -->
  <div>

    <!-- Ringkasan Pekerjaan -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-file-description"></i> Data Pekerjaan</div>
      <table class="tbl">
        <tr><td class="text-muted text-sm" style="width:42%">Uraian BKP</td>
            <td class="text-sm"><?= htmlspecialchars($p->uraian_bkp) ?></td></tr>
        <tr><td class="text-muted text-sm">Nama Kegiatan</td>
            <td class="text-sm fw-500"><?= htmlspecialchars($p->nama_kegiatan_dok ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Penyedia</td>
            <td class="text-sm"><?= htmlspecialchars($p->nama_penyedia ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai Kontrak</td>
            <td class="fw-500 text-biru"><?= rupiah($p->nilai_kontrak) ?></td></tr>
        <tr><td class="text-muted text-sm">Nilai Diajukan</td>
            <td class="fw-500"><?= rupiah($tahapan->nilai_diajukan) ?>
              <span class="text-xs text-muted">(<?= $tahapan->persen_nilai ?>%)</span></td></tr>
        <?php if ($reviu): ?>
        <tr><td class="text-muted text-sm">No. LHR Inspektorat</td>
            <td class="mono text-sm"><?= htmlspecialchars($reviu->no_lhr ?: '—') ?></td></tr>
        <?php endif; ?>
        <?php if ($verif_kab): ?>
        <tr><td class="text-muted text-sm">No. Surat Verif. Kab</td>
            <td class="mono text-sm"><?= htmlspecialchars($verif_kab->no_surat_verif ?: '—') ?></td></tr>
        <tr><td class="text-muted text-sm">Hasil Verif. Kab</td>
            <td><?php $hm=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
                  $h=$hm[$verif_kab->hasil_verifikasi]??['abu','—'];
                  echo '<span class="badge badge-'.$h[0].'">'.$h[1].'</span>'; ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted text-sm">Batas Penyaluran</td>
            <td class="text-sm <?= is_deadline_lewat($tahapan->batas_pengajuan??'') ? 'text-warning' : '' ?>">
              <?= tgl_indo($tahapan->batas_pengajuan ?? '—') ?>
            </td></tr>
      </table>
    </div>

    <!-- Dokumen Persyaratan dari Kab -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-files"></i> Dokumen dari Kab/Kota</div>
      <?php if (!empty($dokumen)): ?>
        <?php foreach ($dokumen as $d): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);
                    margin-bottom:5px;font-size:12px">
          <div style="display:flex;align-items:center;gap:7px">
            <i class="ti <?= icon_file($d->file_path) ?>"
               style="color:var(--biru);font-size:17px"></i>
            <div>
              <div class="fw-500"><?= label_jenis_dok($d->jenis_dokumen) ?></div>
              <div class="text-muted"><?= $d->ukuran_kb ?> KB</div>
            </div>
          </div>
          <a href="<?= site_url('berkas/unduh/dok/'.$d->id) ?>" target="_blank"
             class="btn-icon" title="Download">
            <i class="ti ti-download"></i>
          </a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted text-sm">Belum ada dokumen dari Kab/Kota.</p>
      <?php endif; ?>
    </div>

    <!-- Timeline Status -->
    <div class="card">
      <div class="card-title"><i class="ti ti-timeline"></i> Riwayat Status</div>
      <?php if (!empty($history)): ?>
      <div style="padding-left:4px">
        <?php foreach ($history as $h): ?>
        <div style="margin-bottom:12px;padding-left:16px;
                    border-left:2px solid var(--border);position:relative">
          <div style="position:absolute;left:-5px;top:4px;width:8px;height:8px;
                      border-radius:50%;background:var(--biru)"></div>
          <div style="display:flex;flex-wrap:wrap;align-items:center;gap:4px;margin-bottom:2px">
            <?php if ($h->status_lama): ?>
            <?= badge_status($h->status_lama) ?>
            <i class="ti ti-arrow-right" style="font-size:11px;color:var(--text-muted)"></i>
            <?php endif; ?>
            <?= badge_status($h->status_baru) ?>
          </div>
          <div class="text-xs text-muted">
            <?= htmlspecialchars($h->nama_user ?? 'Sistem') ?>
            · <?= htmlspecialchars($h->nama_role ?? '') ?>
          </div>
          <?php if ($h->catatan): ?>
          <div class="text-xs" style="margin-top:2px"><?= htmlspecialchars($h->catatan) ?></div>
          <?php endif; ?>
          <div class="text-xs text-muted"><?= tgl_indo($h->created_at) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted text-sm">Belum ada riwayat.</p>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── Kolom Kanan: Verifikasi + SP2D ── -->
  <div>

    <!-- Panel Verifikasi Provinsi -->
    <?php if ($this->rbac->can('verif_prov.approve')
          && in_array($tahapan->status, ['skpkd_prov_verif','skpkd_kab_approved'])): ?>
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-gavel"></i> Verifikasi Provinsi</div>

      <?php if ($verif_prov && $verif_prov->hasil_verifikasi): ?>
      <?php $hm=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
            $h=$hm[$verif_prov->hasil_verifikasi]; ?>
      <div style="padding:10px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius);margin-bottom:12px">
        <div class="fw-500">Keputusan: <span class="badge badge-<?= $h[0] ?>"><?= $h[1] ?></span></div>
        <?php if ($verif_prov->catatan): ?>
        <div class="text-sm mt-1"><?= htmlspecialchars($verif_prov->catatan) ?></div>
        <?php endif; ?>
        <div class="text-xs text-muted mt-1"><?= tgl_indo($verif_prov->tgl_verifikasi) ?></div>
      </div>
      <?php if ($verif_prov->hasil_verifikasi !== 'disetujui'): ?>
      <p class="text-sm text-muted mb-2">Ubah keputusan:</p>
      <?php endif; ?>
      <?php endif; ?>

      <?= form_open(site_url('verifikasi/prov/putuskan/'.($verif_prov ? $verif_prov->id : 0))) ?>
      <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
      <div class="form-group mb-2">
        <label>Hasil Verifikasi <span class="req">*</span></label>
        <select name="hasil_verifikasi" class="form-control" required>
          <option value="">— Pilih keputusan —</option>
          <option value="disetujui"
            <?= ($verif_prov && $verif_prov->hasil_verifikasi==='disetujui')?'selected':'' ?>>
            ✅ Disetujui — Lanjutkan ke Input SP2D
          </option>
          <option value="perlu_perbaikan"
            <?= ($verif_prov && $verif_prov->hasil_verifikasi==='perlu_perbaikan')?'selected':'' ?>>
            ⚠️ Perlu Perbaikan — Kembalikan ke Kab/Kota
          </option>
          <option value="ditolak"
            <?= ($verif_prov && $verif_prov->hasil_verifikasi==='ditolak')?'selected':'' ?>>
            ❌ Ditolak
          </option>
        </select>
      </div>
      <div class="form-group mb-3">
        <label>Catatan <span class="text-xs text-muted">(wajib jika Perlu Perbaikan/Ditolak)</span></label>
        <textarea name="catatan" class="form-control" rows="3"
          placeholder="Temuan atau alasan keputusan..."><?= htmlspecialchars($verif_prov->catatan ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"
        onclick="return confirm('Simpan keputusan verifikasi ini?')">
        <i class="ti ti-gavel"></i> Simpan Keputusan
      </button>
      <?= form_close() ?>
    </div>
    <?php elseif ($verif_prov && $verif_prov->hasil_verifikasi): ?>
    <!-- Tampilkan keputusan final -->
    <div class="card mb-2">
      <div class="card-title"><i class="ti ti-gavel"></i> Keputusan Verifikasi Provinsi</div>
      <?php $hm=['disetujui'=>['hijau','Disetujui'],'perlu_perbaikan'=>['kuning','Perlu Perbaikan'],'ditolak'=>['merah','Ditolak']];
            $h=$hm[$verif_prov->hasil_verifikasi]; ?>
      <div style="padding:12px;background:var(--<?= $h[0] ?>-light);border-radius:var(--radius)">
        <div class="badge badge-<?= $h[0] ?> mb-1"><?= $h[1] ?></div>
        <?php if ($verif_prov->catatan): ?><div class="text-sm mt-1"><?= htmlspecialchars($verif_prov->catatan) ?></div><?php endif; ?>
        <div class="text-xs text-muted mt-1"><?= tgl_indo($verif_prov->tgl_verifikasi) ?></div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
