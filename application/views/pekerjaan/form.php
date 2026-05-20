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

    <!-- Field 12-13: BAST — hanya tampil jika jenis penyaluran = sekaligus -->
    <div id="rowBastNo" class="form-group" style="display:none">
      <label>12. No. BAST <span class="text-xs text-muted">(jika sudah ada)</span></label>
      <input type="text" name="no_bast" id="no_bast" class="form-control"
        value="<?= $val('no_bast') ?>"
        placeholder="Nomor BAST">
    </div>
    <div id="rowBastTgl" class="form-group" style="display:none">
      <label>13. Tanggal BAST</label>
      <input type="date" name="tgl_bast" id="tgl_bast" class="form-control"
        value="<?= $val('tgl_bast') ?>">
    </div>
    <div id="rowBastSpacer" class="form-group" style="display:none"><!-- spacer --></div>
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
      <div style="display:flex;gap:8px;align-items:stretch">
        <input type="text" name="nilai_belanja_pendukung" id="nilai_belanja_pendukung"
          class="form-control mono"
          value="<?= $v_rupiah('nilai_belanja_pendukung') ?>"
          placeholder="0" readonly
          style="background:var(--biru-light);cursor:default;flex:1;font-weight:600">
        <button type="button" onclick="openModalPendukung()"
                class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap">
          <i class="ti ti-list-details"></i> Isi Rincian
        </button>
      </div>
      <div class="form-hint" id="hint_pendukung">Maks. 5% dari nilai BKP — klik <strong>Isi Rincian</strong> untuk input detail</div>
      <!-- Menyimpan rincian pendukung sebagai JSON agar bisa di-restore saat edit -->
      <input type="hidden" name="belanja_pendukung_json" id="belanja_pendukung_json"
             value="<?= htmlspecialchars($p->belanja_pendukung_json ?? '[]') ?>">
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

<!-- ══ MODAL RINCIAN BELANJA PENDUKUNG ═══════════════════════ -->
<div id="modalPendukung" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);
     z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:680px;max-width:96vw;
              max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3)">

    <div class="card-title" style="margin-bottom:16px">
      <i class="ti ti-list-details"></i> Rincian Belanja Pendukung
      <button type="button" onclick="closeModalPendukung()"
              style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted)">
        <i class="ti ti-x"></i>
      </button>
    </div>

    <!-- Tabel rincian -->
    <div class="table-wrap mb-2">
      <table class="tbl" id="tblPendukung">
        <thead>
          <tr>
            <th style="width:40px">No.</th>
            <th>Uraian Belanja Pendukung</th>
            <th style="text-align:right;width:180px">Nilai (Rp)</th>
            <th style="width:40px"></th>
          </tr>
        </thead>
        <tbody id="tbodyPendukung">
          <!-- baris diisi JS -->
        </tbody>
      </table>
    </div>

    <button type="button" onclick="tambahBarisPendukung()"
            class="btn btn-outline btn-sm mb-2">
      <i class="ti ti-plus"></i> Tambah Baris
    </button>

    <!-- Summary total + persentase -->
    <div id="summaryPendukung" style="border-radius:var(--radius);padding:14px 16px;
         background:var(--bg);border:1px solid var(--border);margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <div>
          <div class="text-xs text-muted">Total Belanja Pendukung</div>
          <div style="font-size:20px;font-weight:700;color:var(--biru)" id="summTotal">Rp 0</div>
        </div>
        <div style="text-align:right">
          <div class="text-xs text-muted">% terhadap Nilai BKP</div>
          <div style="font-size:20px;font-weight:700" id="summPct">—</div>
        </div>
      </div>
      <div id="warnPendukung" style="display:none;margin-top:10px;padding:8px 12px;
           background:var(--merah-light);border-radius:var(--radius);
           color:var(--merah-mid);font-size:12px;font-weight:600">
        <i class="ti ti-alert-triangle"></i>
        Belanja pendukung melebihi batas <strong>5%</strong> dari nilai BKP.
        Kurangi nilai agar bisa disimpan.
      </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button type="button" onclick="closeModalPendukung()" class="btn btn-outline">Batal</button>
      <button type="button" onclick="simpanPendukung()" id="btnSimpanPendukung" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> Simpan ke Form
      </button>
    </div>
  </div>
</div>

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

// ─── Nilai BKP aktif (untuk kalkulasi % belanja pendukung) ───
var nilaiBkpAktif = <?= ($edit && $p) ? (int)($p->nilai_bkp ?? 0) : 0 ?>;

// ─── Info BKP dinamis (mode tambah) ─────────────────────────
function onBkpChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) { document.getElementById('infoBkp').style.display='none'; nilaiBkpAktif = 0; return; }
    nilaiBkpAktif = parseInt(opt.dataset.nilai) || 0;
    document.getElementById('infoBkpKode').textContent   = opt.dataset.kode;
    document.getElementById('infoBkpUraian').textContent = opt.dataset.uraian;
    document.getElementById('infoBkpNilai').textContent  = 'Rp ' + nilaiBkpAktif.toLocaleString('id-ID');
    document.getElementById('infoBkpBidang').textContent = opt.dataset.bidang;
    document.getElementById('infoBkp').style.display = 'block';
    hitungSummaryPendukung(); // recalc jika modal sudah diisi
}

// ─── Info batas waktu + tampil/sembunyikan field BAST ────────
function onJenisChange(jenis) {
    // Info batas waktu
    const el = document.getElementById('bwContent');
    if (!jenis) { el.innerHTML = '<span class="text-muted">Pilih jenis penyaluran...</span>'; }
    else {
        const items = batasWaktuData.filter(b => b.jenis === jenis);
        if (!items.length) {
            el.innerHTML = '<span class="text-warning"><i class="ti ti-alert-triangle"></i> Belum ada batas waktu untuk jenis ini di TA <?= $tahun ?>. Hubungi Admin Provinsi.</span>';
        } else {
            el.innerHTML = items.map(function(b) {
                const today = new Date().toISOString().slice(0,10);
                const lewat = today > b.batas_pengajuan;
                const cls   = lewat ? 'text-danger' : '';
                const icon  = lewat ? '🔴' : '🟢';
                return `<div class="${cls}">${icon} <strong>${b.label}</strong>: batas pengajuan <strong>${b.batas_pengajuan}</strong>${lewat?' ⚠️ SUDAH LEWAT':''}</div>`;
            }).join('');
        }
    }

    // Tampilkan field BAST hanya jika jenis = sekaligus
    var showBast = (jenis === 'sekaligus');
    var disp     = showBast ? '' : 'none';
    document.getElementById('rowBastNo').style.display     = disp;
    document.getElementById('rowBastTgl').style.display    = disp;
    document.getElementById('rowBastSpacer').style.display = disp;

    // Kosongkan nilai BAST jika disembunyikan agar tidak ikut terkirim
    if (!showBast) {
        document.getElementById('no_bast').value  = '';
        document.getElementById('tgl_bast').value = '';
    }
}

// ─── Modal Rincian Belanja Pendukung ─────────────────────────
var uraianOptions = [
    'Konsultan Perencana',
    'Konsultan Pengawas',
    'Perjalanan Dinas Monitoring dan Reviu Pelaksanaan Kegiatan'
];

var pendukungRows = []; // [{uraian:'...', nilai:0}, ...]

function openModalPendukung() {
    // Jika belum ada baris, tambah 1 baris kosong
    if (pendukungRows.length === 0) tambahBarisPendukung();
    renderTabelPendukung();
    hitungSummaryPendukung();
    document.getElementById('modalPendukung').style.display = 'flex';
}

function closeModalPendukung() {
    document.getElementById('modalPendukung').style.display = 'none';
}

function tambahBarisPendukung() {
    pendukungRows.push({ uraian: '', nilai: 0 });
    renderTabelPendukung();
    hitungSummaryPendukung();
}

function hapusBarisPendukung(idx) {
    pendukungRows.splice(idx, 1);
    renderTabelPendukung();
    hitungSummaryPendukung();
}

function renderTabelPendukung() {
    var tbody = document.getElementById('tbodyPendukung');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (pendukungRows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:16px;color:var(--text-muted)">Belum ada rincian. Klik "+ Tambah Baris".</td></tr>';
        return;
    }
    pendukungRows.forEach(function(row, idx) {
        var opts = uraianOptions.map(function(u) {
            return '<option value="' + u + '"' + (row.uraian === u ? ' selected' : '') + '>' + u + '</option>';
        }).join('');
        var nilaiStr = row.nilai > 0 ? row.nilai.toLocaleString('id-ID') : '';
        tbody.innerHTML += '<tr>' +
            '<td class="text-muted" style="font-size:12px;text-align:center">' + (idx+1) + '</td>' +
            '<td>' +
              '<select class="form-control fc fc-sm" onchange="updatePendukung(' + idx + ',\'uraian\',this.value)">' +
                '<option value="">-- Pilih --</option>' + opts +
              '</select>' +
            '</td>' +
            '<td>' +
              '<input type="text" class="form-control fc fc-sm mono" ' +
                     'style="text-align:right" ' +
                     'value="' + nilaiStr + '" ' +
                     'placeholder="0" ' +
                     'oninput="updatePendukungNilai(' + idx + ',this.value)">' +
            '</td>' +
            '<td style="text-align:center">' +
              '<button type="button" class="btn-icon del" onclick="hapusBarisPendukung(' + idx + ')" title="Hapus">' +
                '<i class="ti ti-trash"></i>' +
              '</button>' +
            '</td>' +
            '</tr>';
    });
}

function updatePendukung(idx, field, value) {
    if (pendukungRows[idx]) pendukungRows[idx][field] = value;
    hitungSummaryPendukung();
}

function updatePendukungNilai(idx, rawValue) {
    // Bersihkan format ribuan, ambil angka saja
    var clean = rawValue.replace(/\./g, '').replace(/,/g, '').replace(/[^0-9]/g, '');
    var num   = parseInt(clean) || 0;
    if (pendukungRows[idx]) pendukungRows[idx].nilai = num;
    hitungSummaryPendukung();
}

function hitungSummaryPendukung() {
    var total    = pendukungRows.reduce(function(s, r) { return s + (r.nilai || 0); }, 0);
    var totalEl  = document.getElementById('summTotal');
    var pctEl    = document.getElementById('summPct');
    var warnEl   = document.getElementById('warnPendukung');
    var btnSimpan= document.getElementById('btnSimpanPendukung');

    totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');

    var melebihi = false;
    if (nilaiBkpAktif > 0) {
        var pct  = (total / nilaiBkpAktif * 100).toFixed(2);
        melebihi = parseFloat(pct) > 5;
        pctEl.textContent = pct + '%';
        pctEl.style.color = melebihi ? 'var(--merah-mid)' : 'var(--hijau-mid)';
        warnEl.style.display = melebihi ? 'block' : 'none';
    } else {
        pctEl.textContent    = '— (pilih BKP dulu)';
        pctEl.style.color    = 'var(--text-muted)';
        warnEl.style.display = 'none';
    }

    // Disable tombol simpan jika melebihi 5%
    if (btnSimpan) {
        btnSimpan.disabled = melebihi;
        btnSimpan.style.opacity = melebihi ? '0.5' : '1';
        btnSimpan.style.cursor  = melebihi ? 'not-allowed' : '';
        btnSimpan.title = melebihi ? 'Kurangi nilai — total melebihi 5% dari nilai BKP' : '';
    }
}

function simpanPendukung() {
    // Cek lagi sebelum simpan
    var total    = pendukungRows.reduce(function(s, r) { return s + (r.nilai || 0); }, 0);
    if (nilaiBkpAktif > 0 && (total / nilaiBkpAktif * 100) > 5) {
        alert('Total belanja pendukung melebihi 5% dari nilai BKP. Kurangi nilai terlebih dahulu.');
        return;
    }

    // Simpan total ke field
    document.getElementById('nilai_belanja_pendukung').value =
        total > 0 ? total.toLocaleString('id-ID') : '0';

    // Simpan rincian sebagai JSON ke hidden field agar bisa di-restore saat edit
    var rincianBersih = pendukungRows.filter(function(r) { return r.uraian && r.nilai > 0; });
    document.getElementById('belanja_pendukung_json').value =
        JSON.stringify(rincianBersih);

    closeModalPendukung();
}

// Restore rincian dari JSON (mode edit) atau nilai existing
(function initPendukungRows() {
    <?php if ($edit && $p): ?>
    var jsonStr = <?= json_encode($p->belanja_pendukung_json ?? '[]') ?>;
    try {
        var parsed = JSON.parse(jsonStr);
        if (Array.isArray(parsed) && parsed.length > 0) {
            pendukungRows = parsed;
            return; // restored dari JSON, selesai
        }
    } catch(e) {}
    // Fallback: jika JSON kosong/invalid tapi ada nilai, buat 1 baris
    <?php if (($p->nilai_belanja_pendukung ?? 0) > 0): ?>
    pendukungRows = [{ uraian: 'Konsultan Perencana', nilai: <?= (int)($p->nilai_belanja_pendukung ?? 0) ?> }];
    <?php endif; ?>
    <?php endif; ?>
})();

// Init tampilan awal — penting untuk mode edit dan mode tambah yang sudah pilih jenis
<?php if ($edit && $p): ?>
onJenisChange('<?= $p->jenis_penyaluran ?>');
<?php else: ?>
// Mode tambah: jika ada nilai default di select, trigger juga
(function() {
    var sel = document.getElementById('jenis_penyaluran');
    if (sel && sel.value) onJenisChange(sel.value);
})();
<?php endif; ?>
</script>
