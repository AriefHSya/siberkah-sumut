# CLAUDE.md — SIBERKAH SUMUT v4
## Project Context untuk Claude Code

---

## IDENTITAS PROJECT

**Nama**: SIBERKAH SUMUT — Sistem Informasi Bantuan Keuangan Daerah Provinsi Sumatera Utara  
**Versi**: 4.1.0  
**Dasar Hukum**: SE Gubernur Sumatera Utara No. 900.1.1.3689 Tanggal 8 Mei 2026  
**Stack**: CodeIgniter 3.1.13 · PHP 7.4+ · MySQL 5.7+ / MariaDB 10.3+ · Leaflet.js  
**Repository**: https://github.com/AriefHSya/siberkah-sumut  
**Owner**: BKAD Provinsi Sumatera Utara

---

## APA YANG DILAKUKAN APLIKASI INI

SIBERKAH adalah platform kolaborasi multi-role untuk mengelola penyaluran dana Bantuan Keuangan Provinsi (BKP) kepada 33 Kabupaten/Kota di Sumatera Utara. Alur bisnis utama:

```
OPD Teknis → input form 17 field + pin lokasi + upload dokumen
    ↓ (cek batas waktu dari ref_batas_waktu)
Inspektorat → reviu 21-item checklist otomatis + upload LHR
    ↓
SKPKD Kab/Kota → verifikasi kegiatan individual (menu: Verifikasi)
    ↓
SKPKD Kab/Kota → buat Permohonan bundel kegiatan + ajukan (menu: Permohonan)
    ↓
Admin Provinsi → verifikasi per-tahapan + cetak Nota Kabid/Kabadan/Ringkasan
    ↓
Admin Provinsi → input SP2D per-permohonan (menu: Penyaluran)
    ↓
SKPKD Kab/Kota → konfirmasi RKUD: kode transaksi + nilai + tanggal (menu: Penyaluran)
    ↓ (jika Tahap I bertahap)
OPD Teknis → input Capaian Output Fisik (menu: Capaian)
```

Satu BKP = satu pekerjaan. Jenis penyaluran: `bertahap` (2 tahap), `sekaligus`, `khusus_mendesak`, `khusus_bencana`.

---

## STRUKTUR DIREKTORI

```
application/
├── config/
│   ├── config.php          # base_url, encryption_key, app metadata
│   ├── database.php        # koneksi MySQL
│   ├── autoload.php        # libraries: database, session, form_validation
│   └── routes.php          # 83 routes — SELALU update di sini jika tambah controller
│
├── core/
│   └── MY_Controller.php   # 3 class: MY_Controller, Auth_Controller, Guest_Controller
│
├── libraries/
│   └── Rbac.php            # RBAC dinamis dari DB — can(), canAny(), getMenus()
│
├── helpers/
│   └── siberkah_helper.php # rupiah(), tgl_indo(), badge_status(), deadline_info()
│
├── models/
│   ├── User_model.php
│   ├── Role_model.php
│   ├── Parameter_model.php     # ref_tahun, ref_bkp, ref_batas_waktu, ref_pemda, ref_pejabat_bkad_prov
│   ├── Notifikasi_model.php
│   ├── Pekerjaan_model.php     # trx_pekerjaan, trx_tahapan, trx_dokumen
│   ├── Reviu_model.php         # trx_reviu_inspektorat, trx_checklist_reviu
│   ├── Verifikasi_kab_model.php
│   ├── Verifikasi_prov_model.php  # trx_verifikasi_skpkd_prov, trx_penyaluran_dana
│   ├── Permohonan_model.php    # trx_permohonan, trx_permohonan_item
│   ├── Penyaluran_kab_model.php   # konfirmasi RKUD oleh SKPKD Kab/Kota
│   ├── Capaian_model.php       # trx_capaian_output
│   └── Laporan_model.php       # statistik, funnel, rekap per bidang/kab
│
├── controllers/
│   ├── Auth.php            # login, logout — extends Guest_Controller
│   ├── Dashboard.php       # stats, funnel, antrian aksi per role
│   ├── Parameter.php       # tahun, batas_waktu, bkp, pemda, pejabat_prov, log
│   ├── Pekerjaan.php       # input, edit, detail, submit, upload_dok, cetak
│   ├── Reviu.php           # form reviu, simpan_checklist, upload_lhr, putuskan
│   ├── Verif_kab.php       # verifikasi kab individual, upload_dok, putuskan
│   ├── Permohonan.php      # buat bundel permohonan, ajukan ke provinsi
│   ├── Verif_prov.php      # verifikasi prov, cetak nota, simpan_sp2d
│   ├── Penyaluran_kab.php  # konfirmasi RKUD oleh SKPKD Kab/Kota
│   ├── Capaian.php         # input capaian output fisik (setelah Tahap I dikonfirmasi)
│   ├── Laporan.php         # rekap_bkp, rekap_penyaluran, export CSV/XLSX
│   ├── Admin_users.php     # CRUD user dengan role-level guard
│   ├── Admin_roles.php     # CRUD role, save_permissions, logs
│   └── Welcome.php         # landing page
│
└── views/
    ├── layouts/main.php    # layout utama: sidebar RBAC-driven, topbar, flash
    ├── auth/               # login.php
    ├── dashboard/          # index.php, pilih_tahun.php
    ├── parameter/          # tahun, batas_waktu, bkp, pemda, pejabat_provinsi, log
    ├── pekerjaan/          # index, form (17 field + Leaflet), detail, cetak
    ├── reviu/              # index, form (checklist), cetak_kertas_kerja, cetak_rekap
    ├── verif_kab/          # index, form, cetak_rekap
    ├── permohonan/         # index, detail (bundel kegiatan)
    ├── verif_prov/         # index, detail_permohonan, form, cetak_nota_*, cetak_rekap_penyaluran
    ├── penyaluran_kab/     # index (daftar permohonan + konfirmasi RKUD)
    ├── capaian/            # index, form (capaian output fisik)
    ├── laporan/            # rekap_bkp, rekap_penyaluran, cetak_rekap_bkp
    └── admin/              # users/ & roles/

assets/
├── css/siberkah.css        # design system lengkap (CSS variables, komponen)
└── js/siberkah.js          # rupiah formatter, modal, flash auto-close

uploads/                    # file upload user — jangan commit ke git
├── dokumen/
├── lhr/
├── permohonan/
└── temp/
```

---

## DATABASE SCHEMA RINGKAS

### Tabel Referensi (prefix: ref_)
| Tabel | Isi |
|---|---|
| `ref_tahun` | Tahun anggaran multi-year |
| `ref_kabkota` | 33 Kab/Kota Sumatera Utara |
| `ref_bidang` | 12 bidang kegiatan |
| `ref_bkp` | Data BKP per tahun per kab/kota |
| `ref_batas_waktu` | **Kunci**: batas pengajuan per jenis per tahun — mengontrol submit OPD |
| `ref_checklist_item` | 21 item checklist statis (CK-01 s/d CK-21) |
| `ref_pemda_pejabat` | KDH, Kepala BKAD, Inspektur per kab per tahun |
| `ref_pemda_dokumen` | Perda/Pergub/Perkada per kab per tahun |

### Tabel RBAC
| Tabel | Isi |
|---|---|
| `roles` | 6 role bawaan + custom |
| `permissions` | ~50 permission kode |
| `role_permissions` | Relasi M:N role ↔ permission |
| `users` | Semua user semua instansi |

### Tabel Transaksi (prefix: trx_)
| Tabel | Isi |
|---|---|
| `trx_pekerjaan` | Data pekerjaan (1:1 dengan ref_bkp) |
| `trx_tahapan_penyaluran` | Tahapan per pekerjaan (1 atau 2 tahap) |
| `trx_dokumen_persyaratan` | Dokumen upload per tahapan |
| `trx_reviu_inspektorat` | Record reviu (UNIQUE per tahapan) |
| `trx_checklist_reviu` | Isian checklist per reviu |
| `trx_verifikasi_skpkd_kab` | Verifikasi kab (UNIQUE per tahapan) |
| `trx_verifikasi_skpkd_prov` | Verifikasi prov (UNIQUE per tahapan) |
| `trx_penyaluran_dana` | Data SP2D (UNIQUE per tahapan) |
| `trx_bukti_transfer` | Bukti transfer RKUD dari kab |
| `trx_status_history` | Audit trail semua perubahan status |
| `trx_notifikasi` | Notifikasi antar user |

---

## ALUR STATUS PEKERJAAN (ENUM lengkap)

```
draft
  → opd_submitted
  → inspektorat_reviu  → inspektorat_revisi (kembali ke OPD)
  → inspektorat_approved
  → skpkd_kab_verif    → skpkd_kab_revisi (kembali ke OPD)
  → skpkd_kab_approved
  → skpkd_prov_verif   → skpkd_prov_revisi (kembali ke Kab)
  → disalurkan_tahap1  → dikonfirmasi_tahap1 → opd_capaian_tahap1
      → [alur Tahap II untuk bertahap]
  → disalurkan_sekaligus / disalurkan_tahap2
  → selesai
  → ditolak
```

Status tahapan (`trx_tahapan_penyaluran.status`):
```
belum → opd_input → inspektorat_reviu → inspektorat_revisi
→ inspektorat_approved → skpkd_kab_verif → skpkd_kab_revisi
→ skpkd_kab_approved → skpkd_prov_verif → skpkd_prov_revisi
→ disalurkan → dikonfirmasi → ditolak
```

---

## ROLE & PERMISSION SYSTEM

### 6 Role Bawaan (+ custom via UI)

| Kode | Level | Scope | Fungsi |
|---|---|---|---|
| `superadmin` | 1 | Provinsi | Akses penuh, bypass semua RBAC check |
| `admin_provinsi` | 2 | Provinsi | Kelola user, parameter, verifikasi final, SP2D |
| `skpkd_kabkota` | 3 | Kab/Kota | Verifikasi + permohonan + konfirmasi RKUD |
| `inspektorat` | 4 | Kab/Kota | Reviu dokumen + checklist + upload LHR |
| `opd_teknis` | 5 | Kab/Kota | Input pekerjaan + upload dokumen |
| `pengawas` | 8 | Fleksibel | View-only seluruh data |

### Pola Permission Check di Controller

```php
// Cek 1 permission — redirect jika gagal
$this->requirePerm('pekerjaan.input');

// Cek di view
<?php if ($this->rbac->can('pekerjaan.submit')): ?>

// Cek role
$this->rbac->isProvinsi()   // superadmin atau admin_provinsi
$this->rbac->isKabkota()    // skpkd, inspektorat, opd_teknis
$this->rbac->isSuperadmin()

// Cek apakah boleh manage user lain (level numerik lebih kecil = lebih tinggi)
$this->rbac->canManageUser($target_role_level)
```

### Kode Permission Lengkap

```
dashboard.view, dashboard.provinsi
parameter.view, parameter.tahun.view, parameter.tahun.manage
parameter.bkp.view, parameter.bkp.manage
parameter.pemda.view, parameter.pemda.manage
parameter.batas_waktu.view, parameter.batas_waktu.manage
pekerjaan.view, pekerjaan.view_all, pekerjaan.input, pekerjaan.edit
pekerjaan.upload_dok, pekerjaan.download_dok, pekerjaan.submit
pekerjaan.cetak_permohonan
reviu.view, reviu.input, reviu.approve
reviu.cetak_kertas_kerja, reviu.download_rekap
verif_kab.view, verif_kab.input, verif_kab.approve
verif_kab.konfirmasi, verif_kab.cetak_rekap
verif_prov.view, verif_prov.approve
penyaluran.view, penyaluran.input_sp2d
penyaluran_kab.view, penyaluran_kab.konfirmasi
capaian.view, capaian.input
laporan.view, laporan.cetak_rekap_bkp, laporan.cetak_rekap_penyaluran, laporan.export
admin.view, admin.user.view, admin.user.create, admin.user.edit
admin.user.delete, admin.user.toggle, admin.user.reset_pw
admin.role.view, admin.role.create, admin.role.edit
admin.role.delete, admin.role.permission
```

---

## SESSION KEYS

```php
$this->session->userdata('logged_in')       // bool
$this->session->userdata('user_id')         // int
$this->session->userdata('username')        // string
$this->session->userdata('nama')            // string
$this->session->userdata('email')           // string
$this->session->userdata('role_id')         // int
$this->session->userdata('role_kode')       // string: 'superadmin', 'admin_provinsi', dll
$this->session->userdata('role_nama')       // string
$this->session->userdata('role_level')      // int: 1-9
$this->session->userdata('kabkota_id')      // int|NULL (NULL untuk provinsi)
$this->session->userdata('kabkota_nama')    // string|NULL
$this->session->userdata('instansi_jenis')  // enum: bkad_provinsi|skpkd_kabkota|inspektorat|opd_teknis|lainnya
$this->session->userdata('opd_nama')        // string|NULL
$this->session->userdata('tahun_anggaran')  // year string: '2026'
```

Di `Auth_Controller`, shortcut:
```php
$this->user_id     // dari session
$this->role_kode   // dari session
$this->role_level  // dari session
$this->kabkota_id  // dari session
$this->tahun       // dari session
```

---

## KONVENSI KODING

### Controller
```php
// Selalu extends Auth_Controller (halaman terproteksi)
class Pekerjaan extends Auth_Controller
{
    public function __construct() {
        parent::__construct();
        $this->requirePerm('pekerjaan.view');    // guard di constructor
        $this->load->model(['Pekerjaan_model']);
        $this->data['active_menu'] = 'pekerjaan'; // untuk highlight sidebar
    }

    public function index() {
        // Kumpulkan data
        $data = $this->data;
        $data['list'] = $this->Pekerjaan_model->get_all([...]);

        // Render dengan layout
        $this->render('pekerjaan/index', $data);
    }

    public function cetak_sesuatu() {
        // Render TANPA layout (untuk cetak/PDF)
        $this->render_plain('pekerjaan/cetak_xxx', $data);
    }
}
```

### Model — Query Pattern
```php
// JOIN query — selalu explicit alias
$this->db->select('p.*, b.kode_bkp, k.nama as nama_kabkota')
    ->from('trx_pekerjaan p')
    ->join('ref_bkp b',     'b.id = p.bkp_id')
    ->join('ref_kabkota k', 'k.id = b.kabkota_id')
    ->where('b.tahun', $tahun)
    ->get()->result();

// Upsert pattern (cek ada/tidak sebelum insert/update)
$ada = $this->db->get_where('tabel', ['kolom' => $val])->row();
if ($ada) { $this->db->where('id', $ada->id)->update(...); }
else      { $this->db->insert(...); }

// Counting dengan filter kabkota
if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
```

### View — Pola HTML
```php
// Selalu htmlspecialchars() untuk output user input
<?= htmlspecialchars($row->nama) ?>

// Helper functions tersedia global
<?= rupiah($angka) ?>           // Rp 1.000.000
<?= rupiah_juta($angka) ?>      // Rp 1,00 Jt
<?= tgl_indo($tgl) ?>           // 15 Oktober 2026
<?= tgl_short($tgl) ?>          // 15/10/2026
<?= badge_status($status) ?>    // <span class="badge badge-biru">Diajukan</span>
<?= badge_jenis($jenis) ?>      // <span class="badge badge-teal">Bertahap</span>
<?= badge_role($kode) ?>
<?= deadline_info($tgl) ?>      // info sisa hari / sudah lewat
<?= is_deadline_lewat($tgl) ?>  // bool
<?= label_jenis_dok($jenis) ?>
<?= icon_file($path) ?>         // class tabler icon

// RBAC di view
<?php if ($this->rbac->can('permission.kode')): ?>
```

### Form — CSRF selalu wajib
```php
<?= form_open(site_url('pekerjaan/simpan')) ?>
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
// ... fields ...
<?= form_close() ?>

// Upload file
<?= form_open_multipart(site_url('pekerjaan/upload-dok/'.$tahapan_id)) ?>
```

### Flash Message
```php
// Di controller
$this->session->set_flashdata('success', 'Berhasil disimpan.');
$this->session->set_flashdata('error',   'Terjadi kesalahan.');
$this->session->set_flashdata('warning', 'Perhatian...');
// 'success' dan 'warning' auto-close setelah 4 detik (JS)
// 'error' tidak auto-close

// Khusus deadline error (tampil lebih menonjol di detail pekerjaan)
$this->session->set_flashdata('error_deadline', $pesan_html);
```

### Log Aktivitas
```php
// Selalu panggil setelah aksi penting
$this->log_aktivitas('modul.aksi', 'Deskripsi singkat id='.$id);
// Tersimpan ke tabel user_logs
```

### Notifikasi Antar User
```php
$this->Notifikasi_model->kirim(
    $user_id,     // int — penerima
    'Judul',      // string
    'Pesan...',   // string
    'info',       // enum: info|sukses|peringatan|error
    site_url('url/tujuan'), // string|NULL
    $pekerjaan_id, // int|NULL
    $tahapan_id    // int|NULL
);
```

---

## VALIDASI BISNIS KRITIS

### 1. Batas Waktu — HARD BLOCK
```php
// Di Pekerjaan.php submit()
$cek = $this->Parameter_model->cek_deadline($tahun, $jenis, $kode_tahap);
if (!$cek['ok']) {
    $this->session->set_flashdata('error_deadline', $cek['pesan']);
    redirect('pekerjaan/detail/'.$id);
    return; // STOP — tidak ada override
}
```

### 2. Nilai Kontrak — Bertahap wajib > 200 juta
```php
if ($jenis === 'bertahap' && $nilai_kontrak <= 200000000) {
    // Tolak dengan error
}
```

### 3. Belanja Pendukung — Maks 5% dari nilai BKP
```php
if ($jenis === 'bertahap' && $nilai_pendukung > ($bkp->nilai * 0.05)) {
    // Tolak dengan error
}
```

### 4. SP2D — Verifikasi prov harus disetujui dulu
```php
if (!$verif_prov || $verif_prov->hasil_verifikasi !== 'disetujui') {
    // Blokir input SP2D
}
```

### 5. Keputusan — Catatan wajib jika tolak/perlu perbaikan
```php
if (in_array($hasil, ['ditolak','perlu_perbaikan']) && empty($catatan)) {
    // Error: catatan wajib
}
```

---

## FITUR YANG BELUM SELESAI (STUB)

Fitur-fitur ini ada dalam blueprint tapi belum diimplementasi — **prioritas untuk pengembangan**:

| Fitur | File Terkait | Keterangan |
|---|---|---|
| **Import Excel BKP** | `views/parameter/bkp_import.php` | View ada tapi stub. Butuh: PhpSpreadsheet, controller `bkp_proses_import`, `bkp_preview_import`, validasi duplikat |
| **Notifikasi Email** | `Notifikasi_model.php` | Hanya in-app. Perlu tambah SMTP / PHPMailer |
| **Reset Password via Email** | `Admin_users.php` | Reset saat ini set ke 'password123', belum kirim email |
| **Peta Overview Provinsi** | `Dashboard.php` | Tabel per kab ada, peta Leaflet cluster belum dibuat |
| **API JSON** | — | Tidak ada endpoint API. Perlu jika integrasi dengan sistem e-budgeting daerah |
| **Notif Realtime** | — | Notifikasi hanya muncul saat page refresh |

**Fitur SELESAI** (sebelumnya tercatat sebagai stub, kini sudah diimplementasi):
- ✅ **Capaian Output** — `Capaian.php` + `Capaian_model.php` + `capaian/index.php` + `capaian/form.php`
- ✅ **Export XLSX** — `Laporan.php` menggunakan XlsxWriter library
- ✅ **Pagination** — `Admin_users`, `Parameter BKP` sudah ada pagination
- ✅ **Penyaluran Kab** — `Penyaluran_kab.php` + konfirmasi RKUD (kode transaksi, nilai, tanggal)
- ✅ **Permohonan bundel** — SKPKD Kab buat bundel kegiatan dalam satu permohonan
- ✅ **Nota Dinas cetak** — `cetak_nota_kabid.php`, `cetak_nota_kabadan.php`, `cetak_ringkasan.php`
- ✅ **Pejabat BKAD Provinsi** — `ref_pejabat_bkad_prov` + parameter UI

---

## CARA MENAMBAH FITUR BARU

### Pola standar: tambah modul baru

**1. Buat Model** di `application/models/NamaFitur_model.php`
```php
class NamaFitur_model extends CI_Model {
    public function get_all($filters = []) { ... }
    public function get_by_id($id) { ... }
    public function insert($data) { ... }
    public function update($id, $data) { ... }
}
```

**2. Buat Controller** di `application/controllers/NamaFitur.php`
```php
class NamaFitur extends Auth_Controller {
    public function __construct() {
        parent::__construct();
        $this->requirePerm('nama_fitur.view');
        $this->load->model('NamaFitur_model');
        $this->data['active_menu'] = 'nama_fitur';
    }
}
```

**3. Tambah Routes** di `application/config/routes.php`
```php
$route['nama-fitur']            = 'nama_fitur/index';
$route['nama-fitur/tambah']     = 'nama_fitur/tambah';
$route['nama-fitur/simpan']     = 'nama_fitur/simpan';
$route['nama-fitur/(:num)']     = 'nama_fitur/detail/$1';
```

**4. Tambah Permission** di SQL
```sql
INSERT INTO permissions (kode, nama, modul, jenis) VALUES
('nama_fitur.view',   'Lihat Nama Fitur', 'nama_fitur', 'menu'),
('nama_fitur.create', 'Tambah Nama Fitur','nama_fitur', 'aksi');
```

**5. Tambah ke Sidebar** di `Rbac.php` → method `getMenus()`
```php
['key'=>'nama_fitur', 'url'=>'nama-fitur', 'label'=>'Nama Fitur',
 'icon'=>'icon-tabler', 'perm'=>'nama_fitur.view'],
```

**6. Buat Views** di `application/views/nama_fitur/`
- `index.php` — daftar dengan filter
- `form.php` — form tambah/edit
- `detail.php` — detail (jika perlu)

---

## ATURAN ANTI-PATTERN

Hal-hal yang **tidak boleh** dilakukan:

```php
// ❌ JANGAN akses $this->db langsung dari view
<?php $jml = $this->db->count_all_results('tabel'); ?>
// ✅ Hitung di controller, inject ke view sebagai variabel

// ❌ JANGAN instantiate model dari view
<?php $data = (new SomeModel)->get_all(); ?>
// ✅ Load model di controller

// ❌ JANGAN echo langsung tanpa escape
<?= $row->input_user ?>
// ✅ Selalu htmlspecialchars()
<?= htmlspecialchars($row->input_user) ?>

// ❌ JANGAN hardcode tahun
WHERE tahun = 2026
// ✅ Gunakan $this->tahun dari session
->where('b.tahun', $this->tahun)

// ❌ JANGAN gunakan PHP 8-only syntax (match, named args, dll)
// Project target PHP 7.4+ untuk kompatibilitas XAMPP lama
$x = match($val) { 'a' => 1 };
// ✅ Gunakan if/switch
if ($val === 'a') $x = 1;

// ❌ JANGAN lupa CSRF di semua form POST
// ✅ Selalu:
<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

// ❌ JANGAN buat route duplikat di routes.php (sudah terjadi sebelumnya)
// ✅ routes.php sudah bersih — satu route = satu entry
```

---

## DESIGN SYSTEM (CSS Variables)

```css
/* Warna utama — gunakan CSS variables, bukan hex langsung */
var(--biru)         /* #1A5EA8 */
var(--biru-dark)    /* #134a8a */
var(--biru-light)   /* #E6F1FB */
var(--hijau)        /* #27500A */
var(--hijau-mid)    /* #3B6D11 */
var(--hijau-light)  /* #EAF3DE */
var(--merah)        /* #791F1F */
var(--merah-mid)    /* #A32D2D */
var(--merah-light)  /* #FCEBEB */
var(--kuning)       /* #633806 */
var(--kuning-mid)   /* #854F0B */
var(--kuning-light) /* #FAEEDA */
var(--teal)         /* #085041 */
var(--teal-mid)     /* #0F6E56 */
var(--teal-light)   /* #E1F5EE */
var(--ungu)         /* #3C3489 */
var(--ungu-light)   /* #EEEDFE */
var(--abu)          /* #5F5E5A */
var(--abu-light)    /* #F1EFE8 */

/* Komponen yang tersedia di siberkah.css */
.card               /* container putih dengan border */
.card-title         /* header section dalam card */
.tbl                /* table styling */
.btn .btn-primary .btn-outline .btn-danger .btn-success
.btn-sm .btn-xs .btn-icon
.badge .badge-{warna}   /* biru|hijau|merah|kuning|teal|ungu|abu|oranye */
.form-group .form-control .fc .fc-sm
.form-grid .form-grid-2 .form-grid-3
.g2 .g3 .g4         /* CSS grid: 2/3/4 kolom */
.alert .alert-success .alert-error .alert-warning .alert-info
.stat-card .stat-val .stat-label .stat-icon
.page-header .page-title
.filter-row .filter-group
.deadline-block     /* warning block merah untuk deadline */
.sub-nav .sub-nav-item
.aksi-row           /* flex row untuk tombol aksi di tabel */

/* Icon: Tabler Icons v2.44 */
<i class="ti ti-{nama-icon}"></i>
/* Referensi: https://tabler-icons.io */
```

---

## ENVIRONMENT & DEPENDENCIES

```
PHP          : 7.4+ (target), 8.x (direkomendasikan)
CodeIgniter  : 3.1.13
MySQL        : 5.7+ / MariaDB 10.3+
Leaflet.js   : 1.9.4 (CDN)
Tabler Icons : 2.44.0 (CDN)
OpenStreetMap: tile server (CDN)

# Tidak ada Composer — semua dependency via CDN atau manual
# Jika ingin tambah library PHP (misal PhpSpreadsheet):
#   → Simpan di application/libraries/ atau application/third_party/
#   → Load manual di controller/autoload
```

---

## FILE KONFIGURASI YANG PERLU DIPERHATIKAN

### `application/config/config.php`
```php
$config['base_url']       // WAJIB disesuaikan per environment
$config['encryption_key'] // WAJIB diganti di production
$config['sess_driver']    // 'files' (default) atau 'database' untuk production
$config['csrf_protection']// TRUE — jangan dinonaktifkan
$config['app_name']       // 'SIBERKAH SUMUT'
$config['app_version']    // '4.1.0'
```

### `application/config/database.php`
```php
// Untuk production, gunakan environment variable atau config terpisah
$db['default']['hostname'] // 'localhost'
$db['default']['username'] // sesuaikan
$db['default']['password'] // sesuaikan — jangan kosong di production
$db['default']['database'] // 'siberkah_sumut'
```

---

## CHECKLIST SEBELUM COMMIT

- [ ] Tidak ada `var_dump()` / `print_r()` / `die()` debug tersisa
- [ ] Semua form POST punya CSRF token
- [ ] Semua output user input di-escape dengan `htmlspecialchars()`
- [ ] Controller guard `requirePerm()` ada di method sensitif
- [ ] Log aktivitas dipanggil setelah aksi penting
- [ ] Tidak ada akses `$this->db` langsung dari view
- [ ] Route baru sudah ditambahkan di `routes.php`
- [ ] Tidak ada duplikat route
- [ ] Tidak menggunakan PHP 8-only syntax (match, named args)
- [ ] Upload folder ada dan writable

---

## MIGRASI DATABASE (wajib dijalankan manual)

Jalankan SQL berikut jika belum ada di database:

```sql
-- Tabel pejabat BKAD Provinsi (untuk TTD Nota Dinas)
CREATE TABLE IF NOT EXISTS ref_pejabat_bkad_prov (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tahun YEAR NOT NULL,
  jenis ENUM('kepala_badan','kabid_anggaran','bendahara_pengeluaran') NOT NULL,
  nama VARCHAR(200) NOT NULL,
  nip VARCHAR(50) NULL,
  jabatan VARCHAR(200) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pejabat_bkad (tahun, jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom bundel permohonan + SP2D + RKUD di trx_permohonan
ALTER TABLE trx_permohonan
  ADD COLUMN IF NOT EXISTS nota_kabid_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS nota_kabadan_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS ringkasan_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS no_sp2d VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS tgl_sp2d DATE NULL,
  ADD COLUMN IF NOT EXISTS nilai_sp2d BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS rek_asal VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS nama_bank_asal VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS rek_tujuan VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS nama_bank_tujuan VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS status_sp2d ENUM('proses','selesai','gagal') NULL,
  ADD COLUMN IF NOT EXISTS kode_transaksi_rkud VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS nilai_rkud BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS tgl_rkud DATE NULL,
  ADD COLUMN IF NOT EXISTS tgl_konfirmasi_rkud DATETIME NULL;

-- Permission baru untuk Penyaluran Kab/Kota
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('penyaluran_kab.view',       'Lihat Penyaluran Kab',       'penyaluran_kab', 'menu'),
('penyaluran_kab.konfirmasi', 'Konfirmasi RKUD',            'penyaluran_kab', 'aksi');

-- Assign ke role skpkd_kabkota (sesuaikan role_id)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode = 'skpkd_kabkota'
  AND p.kode IN ('penyaluran_kab.view','penyaluran_kab.konfirmasi');
```

---

## ROADMAP PENGEMBANGAN

### Prioritas Tinggi
1. **Import Excel BKP** — paling sering dibutuhkan operator, data bisa ratusan baris

### Prioritas Menengah
2. **Peta cluster Leaflet** — visualisasi lokasi semua pekerjaan di satu peta provinsi
3. **Notifikasi email** — SMTP untuk notif penting (batas waktu mendekati, dll)

### Prioritas Rendah
4. **Dashboard chart interaktif** — Chart.js/ApexCharts untuk grafik realisasi
5. **API JSON endpoint** — untuk integrasi dengan sistem e-budgeting daerah
6. **Audit log UI** — tampilkan `user_logs` lengkap dengan filter di menu admin
12. **Multi-bahasa** — saat ini hanya Bahasa Indonesia
13. **Dark mode** — CSS variables sudah siap, tinggal tambah theme toggle

---

*File ini adalah konteks utama untuk Claude Code.*
*Update file ini setiap kali ada perubahan arsitektur, konvensi, atau fitur baru.*
*Terakhir diupdate: Mei 2026 — Sprint 1-6 selesai*
