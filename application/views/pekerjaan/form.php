<?php
// Ambil data pekerjaan jika edit
$p = $pekerjaan ?? NULL;
$val = function($field, $default = '') use ($p) {
    return htmlspecialchars($p ? ($p->$field ?? $default) : $default);
};
$v_rupiah = function($field) use ($p) {
    $angka = $p ? ($p->$field ?? 0) : 0;
    return $angka > 0 ? number_format($angka, 0, ',', '.') : '';
};
?>
<div class="page-header">
  <div class="page-title">
    <i class="ti ti-<?= $edit ? 'edit' : 'file-plus' ?>"></i>
    <?= $edit ? 'Edit Pekerjaan — ' . htmlspecialchars($p->kode_bkp ?? '') : 'Input Pekerjaan Baru' ?>
  </div>
  <a href="<?= site_url($edit ? 'pekerjaan/detail/'.$p->id : 'pekerjaan') ?>" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Kembali</a>
</div>

<?= form_open(site_url($edit ? 'pekerjaan/update/'.$p->id : 'pekerjaan/simpan'), ['id'=>'formPekerjaan']) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

<!-- ── BLOK A: IDENTITAS BKP ─────────────────────────────── -->
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-database"></i> Identitas BKP</div>

  <?php if (!$edit): ?>
  <div class="form-group mb-3">
    <label>Pilih BKP <span class="req">*</span></label>
    <select name="bkp_id" id="bkp_id" class="form-control" required onchange="onBkpChange(this)">
      <option value="">-- Pilih Kode BKP --</option>
      <?php foreach ($bkp_tersedia as $b): ?>
      <option value="<?= $b->id ?>"
        data-nilai="<?= $b->nilai ?>"
        data-uraian="<?= htmlspecialchars($b->uraian_bkp, ENT_QUOTES) ?>"
        data-kode="<?= $b->kode_bkp ?>"
        data-bidang="<?= $b->nama_bidang ?>">
        <?= htmlspecialchars($b->kode_bkp . ' — ' . $b->uraian_bkp) ?> (<?= rupiah($b->nilai) ?>)
      </option>
      <?php endforeach; ?>
    </select>
    <?php if (empty($bkp_tersedia)): ?>
    <div class="alert alert-warning mt-1"><i class="ti ti-alert-triangle"></i>
      Semua BKP untuk kabupaten/kota Anda TA <?= $tahun ?> sudah memiliki data pekerjaan,
      atau belum ada BKP yang terdaftar. Hubungi Admin Provinsi.
    </div>
    <?php endif; ?>
  </div>
  <!-- Info BKP yang dipilih -->
  <div id="infoBkp" style="display:none;padding:12px;background:var(--biru-light);border-radius:var(--radius);margin-bottom:14px">
    <div class="text-sm"><strong>Kode BKP:</strong> <span id="infoBkpKode" class="mono"></span></div>
    <div class="text-sm"><strong>Uraian:</strong> <span id="infoBkpUraian"></span></div>
    <div class="text-sm"><strong>Nilai BKP:</strong> <span id="infoBkpNilai" class="fw-500"></span></div>
    <div class="text-sm"><strong>Bidang:</strong> <span id="infoBkpBidang"></span></div>
  </div>
  <?php else: ?>
  <!-- Edit mode: tampilkan info BKP saja -->
  <div style="padding:12px;background:var(--biru-light);border-radius:var(--radius);margin-bottom:14px">
    <div class="g2">
      <div><div class="text-xs text-muted">Kode BKP</div><div class="mono fw-500"><?= $val('kode_bkp') ?></div></div>
      <div><div class="text-xs text-muted">Nilai BKP</div><div class="fw-500"><?= rupiah($p->nilai_bkp??0) ?></div></div>
    </div>
    <div class="mt-1"><div class="text-xs text-muted">Uraian BKP</div><div class="text-sm"><?= $val('uraian_bkp') ?></div></div>
  </div>
  <?php endif; ?>

  <div class="form-grid">
    <div class="form-group">
      <label>Jenis Penyaluran <span class="req">*</span></label>
      <select name="jenis_penyaluran" id="jenis_penyaluran" class="form-control" required onchange="onJenisChange(this.value)">
        <option value="">-- Pilih --</option>
        <option value="bertahap"        <?= ($val('jenis_penyaluran')==='bertahap')?'selected':'' ?>>Bertahap (nilai kontrak > Rp 200 jt)</option>
        <option value="sekaligus"       <?= ($val('jenis_penyaluran')==='sekaligus')?'selected':'' ?>>Sekaligus (100%)</option>
        <option value="khusus_mendesak" <?= ($val('jenis_penyaluran')==='khusus_mendesak')?'selected':'' ?>>Kondisi Mendesak (100%)</option>
        <option value="khusus_bencana"  <?= ($val('jenis_penyaluran')==='khusus_bencana')?'selected':'' ?>>Darurat Bencana (100%)</option>
      </select>
    </div>

    <!-- Info batas waktu dinamis -->
    <div id="infoBatasWaktu" style="padding:10px;background:var(--bg);border-radius:var(--radius);font-size:12px;border:1px solid var(--border)">
      <div style="font-weight:600;margin-bottom:4px"><i class="ti ti-calendar-time"></i> Batas Waktu TA <?= $tahun ?></div>
      <div id="bwContent" class="text-muted">Pilih jenis penyaluran untuk melihat batas waktu.</div>
    </div>
  </div>
</div>

<!-- ── BLOK B: DATA KEGIATAN (17 FIELD) ─────────────────── -->
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-clipboard-list"></i> Data Kegiatan / Kontrak
    <span class="badge badge-biru" style="font-size:10px;margin-left:auto">17 Field — SE Gubernur No. 900.1.1.3689</span>
  </div>

  <!-- Field 1-4 -->
  <div class="form-grid mb-2">
    <div class="form-group" style="grid-column:1/-1">
      <label>1. Nama Kegiatan (sesuai dokumen kontrak) <span class="req">*</span></label>
      <input type="text" name="nama_kegiatan_dok" class="form-control"
        value="<?= $val('nama_kegiatan_dok') ?>"
        placeholder="Nama lengkap kegiatan sesuai dokumen" required>
    </div>
    <div class="form-group">
      <label>2. Volume & Satuan</label>
      <input type="text" name="volume_satuan" class="form-control"
        value="<?= $val('volume_satuan') ?>"
        placeholder="Contoh: 500 m², 10 unit">
    </div>
    <div class="form-group">
      <label>3. Metode Pelaksanaan</label>
      <input type="text" name="metode_pelaksanaan" class="form-control"
        value="<?= $val('metode_pelaksanaan') ?>"
        placeholder="Swakelola / Kontraktual">
    </div>
    <div class="form-group">
      <label>4. Jenis Pekerjaan</label>
      <input type="text" name="jenis_pekerjaan" class="form-control"
        value="<?= $val('jenis_pekerjaan') ?>"
        placeholder="Konstruksi / Pengadaan / Jasa">
    </div>
    <div class="form-group">
      <label>5. Jangka Waktu Pelaksanaan (hari)</label>
      <input type="number" name="jangka_waktu_hari" class="form-control"
        value="<?= $val('jangka_waktu_hari') ?>"
        placeholder="Contoh: 90" min="1">
    </div>
  </div>

  <!-- Field 6-8: Penyedia -->
  <div class="form-grid mb-2">
    <div class="form-group" style="grid-column:1/-1">
      <label>6. Nama Penyedia / Rekanan <span class="req">*</span></label>
      <input type="text" name="nama_penyedia" class="form-control"
        value="<?= $val('nama_penyedia') ?>"
        placeholder="PT / CV / Perorangan" required>
    </div>
    <div class="form-group" style="grid-column:1/-1">
      <label>7. Alamat Penyedia</label>
      <input type="text" name="alamat_penyedia" class="form-control"
        value="<?= $val('alamat_penyedia') ?>"
        placeholder="Alamat lengkap penyedia">
    </div>
  </div>

  <!-- Field 9-14: Dokumen -->
  <div class="form-grid-3 mb-2">
    <div class="form-group">
      <label>8. No. Dokumen Pekerjaan / Kontrak <span class="req">*</span></label>
      <input type="text" name="no_dok_pekerjaan" class="form-control"
        value="<?= $val('no_dok_pekerjaan') ?>"
        placeholder="No. kontrak/MoU/SP" required>
    </div>
    <div class="form-group">
      <label>9. Tanggal Dokumen Pekerjaan</label>
      <input type="date" name="tgl_dok_pekerjaan" class="form-control"
        value="<?= $val('tgl_dok_pekerjaan') ?>">
    </div>
    <div class="form-group"><!-- spacer --></div>

    <div class="form-group">
      <label>10. No. SPMK <span class="req">*</span></label>
      <input type="text" name="no_spmk" class="form-control"
        value="<?= $val('no_spmk') ?>"
        placeholder="Nomor SPMK" required>
    </div>
    <div class="form-group">
      <label>11. Tanggal SPMK</label>
      <input type="date" name="tgl_spmk" class="form-control"
        value="<?= $val('tgl_spmk') ?>">
    </div>
    <div class="form-group"><!-- spacer --></div>

    <div class="form-group">
      <label>12. No. BAST <span class="text-xs text-muted">(jika sudah ada)</span></label>
      <input type="text" name="no_bast" class="form-control"
        value="<?= $val('no_bast') ?>"
        placeholder="Nomor BAST">
    </div>
    <div class="form-group">
      <label>13. Tanggal BAST</label>
      <input type="date" name="tgl_bast" class="form-control"
        value="<?= $val('tgl_bast') ?>">
    </div>
    <div class="form-group"><!-- spacer --></div>
  </div>

  <!-- Field 15-17: Nilai -->
  <div class="form-grid mb-2">
    <div class="form-group">
      <label>14. Nilai Kontrak (Rp) <span class="req">*</span></label>
      <input type="text" name="nilai_kontrak" id="nilai_kontrak"
        class="form-control rupiah-input"
        value="<?= $v_rupiah('nilai_kontrak') ?>"
        placeholder="0" required>
      <div class="form-hint" id="hint_nilai_kontrak">Untuk jenis Bertahap, minimal Rp 200.000.001</div>
    </div>
    <div class="form-group">
      <label>15. Nilai Belanja Pendukung (Rp)</label>
      <input type="text" name="nilai_belanja_pendukung" id="nilai_belanja_pendukung"
        class="form-control rupiah-input"
        value="<?= $v_rupiah('nilai_belanja_pendukung') ?>"
        placeholder="0">
      <div class="form-hint" id="hint_pendukung">Maks. 5% dari nilai BKP (untuk jenis Bertahap)</div>
    </div>
  </div>

  <!-- Perda / Perkada -->
  <div class="form-grid mb-0">
    <div class="form-group">
      <label>16. Perda APBD / APBD-P</label>
      <select name="ref_perda_id" class="form-control">
        <option value="">-- Belum dipilih --</option>
        <?php foreach ($dokumen_perda as $d): ?>
        <option value="<?= $d->id ?>" <?= ($val('ref_perda_id')==$d->id)?'selected':'' ?>>
          <?= htmlspecialchars($d->jenis . ': No. ' . $d->nomor . ' / ' . date('d-m-Y', strtotime($d->tanggal))) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($dokumen_perda)): ?>
      <div class="form-hint text-warning">Belum ada data Perda. Isi di <a href="<?= site_url('parameter/pemda') ?>">Parameter → Data Pemda</a>.</div>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label>17. Perkada / Pergub BKP</label>
      <select name="ref_perkada_id" class="form-control">
        <option value="">-- Belum dipilih --</option>
        <?php foreach ($dokumen_perkada as $d): ?>
        <option value="<?= $d->id ?>" <?= ($val('ref_perkada_id')==$d->id)?'selected':'' ?>>
          <?= htmlspecialchars($d->jenis . ': No. ' . $d->nomor . ' / ' . date('d-m-Y', strtotime($d->tanggal))) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<!-- ── BLOK C: LOKASI (LEAFLET) ──────────────────────────── -->
<div class="card mb-2">
  <div class="card-title"><i class="ti ti-map-pin"></i> Lokasi Kegiatan
    <span class="badge badge-abu" style="font-size:10px;margin-left:auto">OpenStreetMap — siap swap Google Maps API</span>
  </div>

  <div class="form-group mb-2">
    <label>Deskripsi Lokasi</label>
    <input type="text" name="lokasi_deskripsi" class="form-control"
      value="<?= $val('lokasi_deskripsi') ?>"
      placeholder="Desa/Kel., Kecamatan — deskripsi singkat lokasi pekerjaan">
  </div>

  <div class="alert alert-info mb-2" style="font-size:12px">
    <i class="ti ti-info-circle"></i>
    <div>Klik pada peta untuk menentukan titik lokasi pekerjaan, atau isi koordinat secara manual di bawah.</div>
  </div>

  <!-- Peta Leaflet -->
  <div id="map" style="height:350px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:12px"></div>

  <div class="form-grid">
    <div class="form-group">
      <label>Latitude</label>
      <input type="text" name="latitude" id="lat_input" class="form-control mono"
        value="<?= $val('latitude') ?>"
        placeholder="Contoh: 2.96167">
    </div>
    <div class="form-group">
      <label>Longitude</label>
      <input type="text" name="longitude" id="lng_input" class="form-control mono"
        value="<?= $val('longitude') ?>"
        placeholder="Contoh: 98.89694">
    </div>
    <div class="form-group" style="align-self:flex-end">
      <button type="button" class="btn btn-outline btn-sm" onclick="jumpToCoords()">
        <i class="ti ti-crosshair"></i> Tampilkan di Peta
      </button>
    </div>
  </div>
</div>

<!-- ── ACTIONS ────────────────────────────────────────────── -->
<div style="display:flex;gap:10px;justify-content:flex-end;margin-bottom:30px">
  <a href="<?= site_url($edit ? 'pekerjaan/detail/'.$p->id : 'pekerjaan') ?>" class="btn btn-outline">Batal</a>
  <button type="submit" class="btn btn-primary">
    <i class="ti ti-device-floppy"></i> <?= $edit ? 'Simpan Perubahan' : 'Simpan sebagai Draft' ?>
  </button>
</div>

<?= form_close() ?>

<!-- ── LEAFLET + OpenStreetMap ────────────────────────────── -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Data batas waktu dari server (untuk info di form)
const batasWaktuData = <?= json_encode(array_map(function($bw){
    return [
        'jenis'           => $bw->jenis_penyaluran,
        'kode_tahap'      => $bw->kode_tahap,
        'label'           => $bw->label,
        'batas_pengajuan' => $bw->batas_pengajuan,
        'batas_penyaluran'=> $bw->batas_penyaluran,
    ];
}, $batas_waktu)) ?>;

// ─── Inisialisasi Peta Leaflet ───────────────────────────────
const defaultLat = <?= ($p && $p->latitude)  ? $p->latitude  : '2.9671' ?>;
const defaultLng = <?= ($p && $p->longitude) ? $p->longitude : '98.6762' ?>;
const map = L.map('map').setView([defaultLat, defaultLng], <?= ($p && $p->latitude) ? 15 : 9 ?>);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
    maxZoom: 19,
}).addTo(map);

let marker = null;

// Jika sudah ada koordinat (mode edit), tampilkan marker
<?php if ($p && $p->latitude && $p->longitude): ?>
marker = L.marker([<?= $p->latitude ?>, <?= $p->longitude ?>]).addTo(map);
<?php endif; ?>

// Klik peta untuk set marker
map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(8);
    const lng = e.latlng.lng.toFixed(8);
    document.getElementById('lat_input').value = lat;
    document.getElementById('lng_input').value = lng;
    if (marker) marker.remove();
    marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup('<b>Lokasi dipilih</b><br>'+lat+', '+lng).openPopup();
});

function jumpToCoords() {
    const lat = parseFloat(document.getElementById('lat_input').value);
    const lng = parseFloat(document.getElementById('lng_input').value);
    if (isNaN(lat) || isNaN(lng)) { alert('Koordinat tidak valid.'); return; }
    map.setView([lat, lng], 15);
    if (marker) marker.remove();
    marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(lat+', '+lng).openPopup();
}

// ─── Info BKP dinamis (mode tambah) ─────────────────────────
function onBkpChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) { document.getElementById('infoBkp').style.display='none'; return; }
    document.getElementById('infoBkpKode').textContent  = opt.dataset.kode;
    document.getElementById('infoBkpUraian').textContent = opt.dataset.uraian;
    document.getElementById('infoBkpNilai').textContent  = 'Rp ' + parseInt(opt.dataset.nilai).toLocaleString('id-ID');
    document.getElementById('infoBkpBidang').textContent = opt.dataset.bidang;
    document.getElementById('infoBkp').style.display = 'block';
}

// ─── Info batas waktu per jenis ──────────────────────────────
function onJenisChange(jenis) {
    const el = document.getElementById('bwContent');
    if (!jenis) { el.innerHTML = '<span class="text-muted">Pilih jenis penyaluran...</span>'; return; }
    const items = batasWaktuData.filter(b => b.jenis === jenis);
    if (!items.length) {
        el.innerHTML = '<span class="text-warning"><i class="ti ti-alert-triangle"></i> Belum ada batas waktu untuk jenis ini di TA <?= $tahun ?>. Hubungi Admin Provinsi.</span>';
        return;
    }
    let html = items.map(function(b) {
        const today = new Date().toISOString().slice(0,10);
        const lewat = today > b.batas_pengajuan;
        const cls   = lewat ? 'text-danger' : '';
        const icon  = lewat ? '🔴' : '🟢';
        return `<div class="${cls}">${icon} <strong>${b.label}</strong>: batas pengajuan <strong>${b.batas_pengajuan}</strong>${lewat?' ⚠️ SUDAH LEWAT':''}</div>`;
    }).join('');
    el.innerHTML = html;
}

// Init jika mode edit
<?php if ($edit && $p): ?>
onJenisChange('<?= $p->jenis_penyaluran ?>');
<?php endif; ?>
</script>
