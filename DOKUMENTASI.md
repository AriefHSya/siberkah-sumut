# DOKUMENTASI TEKNIS — SIBERKAH SUMUT v4.1.0
> Sistem Informasi Bantuan Keuangan Daerah Provinsi Sumatera Utara  
> Terakhir diperbarui: Mei 2026

---

## DAFTAR ISI

1. [Arsitektur & Hierarki Controller](#1-arsitektur--hierarki-controller)
2. [Core Infrastructure](#2-core-infrastructure)
3. [Controllers](#3-controllers)
4. [Models](#4-models)
5. [Views](#5-views)
6. [Library & Helper](#6-library--helper)
7. [Alur Workflow Bisnis](#7-alur-workflow-bisnis)
8. [Struktur Tabel Database](#8-struktur-tabel-database)
9. [Sistem Permission](#9-sistem-permission)

---

## 1. ARSITEKTUR & HIERARKI CONTROLLER

```
CI_Controller
└── MY_Controller          → base semua controller; render(), json(), log_aktivitas()
    ├── Auth_Controller    → requirePerm(), properti session shortcut
    │   ├── Dashboard
    │   ├── Pekerjaan
    │   ├── Reviu
    │   ├── Verif_kab
    │   ├── Verif_prov
    │   ├── Laporan
    │   ├── Parameter
    │   ├── Admin_users
    │   └── Admin_roles
    └── Guest_Controller   → redirect ke dashboard jika sudah login
        ├── Auth
        └── Welcome
```

**Pola request flow:**
```
Request → routes.php → Controller::method()
    → requirePerm() [guard di constructor/method]
    → Model::query()
    → $this->render('view/path', $data)
    → layouts/main.php (wrap view dengan layout)
```

---

## 2. CORE INFRASTRUCTURE

### `application/core/MY_Controller.php`

Base class untuk seluruh controller aplikasi.

#### MY_Controller (base)

| Method | Signature | Fungsi |
|--------|-----------|--------|
| `render` | `render($view, $extra=[])` | Render view dengan layout `layouts/main.php` |
| `render_plain` | `render_plain($view, $extra=[])` | Render view tanpa layout (untuk cetak/PDF) |
| `json` | `json($data, $code=200)` | Response JSON + `exit` |
| `log_aktivitas` | `log_aktivitas($aksi, $keterangan='')` | Insert ke `user_logs` |

**`$this->data` (array shared ke semua view):**  
`app_name`, `tagline`, `app_version`, `base_url`

#### Auth_Controller (extends MY_Controller)

Digunakan oleh semua controller yang butuh autentikasi dan RBAC.

| Method | Signature | Fungsi |
|--------|-----------|--------|
| `__construct` | `__construct()` | Load session, library Rbac, Notifikasi_model, set shortcut properti |
| `requirePerm` | `requirePerm($kode, $redirect='dashboard')` | Cek permission; redirect jika tidak punya akses |

**Properti shortcut dari session:**

| Properti | Session Key | Tipe |
|----------|-------------|------|
| `$this->user_id` | `user_id` | int |
| `$this->role_kode` | `role_kode` | string |
| `$this->role_level` | `role_level` | int |
| `$this->kabkota_id` | `kabkota_id` | int\|NULL |
| `$this->tahun` | `tahun_anggaran` | string |

#### Guest_Controller (extends MY_Controller)

Untuk halaman yang tidak butuh login (login page, landing). Otomatis redirect ke dashboard jika sudah login.

---

## 3. CONTROLLERS

### `Auth.php` — Autentikasi

**Class:** `Auth extends Guest_Controller`  
**Routes:** `auth/login`, `auth/proses`, `auth/logout`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `login()` | GET | — | Tampil form login → `auth/login` |
| `proses()` | POST | — | Validasi credentials, set session lengkap, update `last_login`, log |
| `logout()` | GET | — | Destroy session, log, redirect ke login |

**Session yang di-set saat login:**  
`logged_in`, `user_id`, `username`, `nama`, `email`, `role_id`, `role_kode`, `role_nama`, `role_level`, `kabkota_id`, `kabkota_nama`, `instansi_jenis`, `opd_nama`, `tahun_anggaran`

---

### `Welcome.php` — Landing Page

**Class:** `Welcome extends Guest_Controller`

| Method | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Render `landing/index` |

---

### `Dashboard.php` — Beranda Aplikasi

**Class:** `Dashboard extends Auth_Controller`  
**Guard constructor:** `dashboard.view`  
**Models:** `Laporan_model`, `Parameter_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `pilih_tahun()` | GET | `dashboard.view` | Form pilih tahun anggaran |
| `set_tahun()` | POST | `dashboard.view` | Set session `tahun_anggaran`, redirect dashboard |
| `index()` | GET | `dashboard.view` | Stats, funnel, per_bidang, per_kabkota, batas_waktu, antrian_aksi |
| `_get_antrian_aksi()` | — | private | Build action queue berbeda per role |

**Antrian Aksi per Role:**
- `opd_teknis` → pekerjaan yang bisa di-edit/submit
- `inspektorat` → tahapan status `opd_submitted` / `inspektorat_reviu`
- `skpkd_kabkota` → tahapan status `inspektorat_approved` / `skpkd_kab_verif`
- `admin_provinsi` → tahapan status `skpkd_kab_approved` / `skpkd_prov_verif`

---

### `Pekerjaan.php` — Manajemen Pekerjaan BKP

**Class:** `Pekerjaan extends Auth_Controller`  
**Guard constructor:** `pekerjaan.view`  
**Models:** `Pekerjaan_model`, `Parameter_model`, `Notifikasi_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `pekerjaan.view` | Daftar pekerjaan; filter tahun/kabkota/status/jenis |
| `input()` | GET | `pekerjaan.input` | Form input pekerjaan baru + daftar BKP tersedia |
| `simpan()` | POST | `pekerjaan.input` | Insert pekerjaan, buat tahapan, set status `draft` |
| `edit($id)` | GET | `pekerjaan.edit` | Form edit (hanya jika status `draft`/`revisi`) |
| `update($id)` | POST | `pekerjaan.edit` | Update pekerjaan + log perubahan field |
| `detail($id)` | GET | `pekerjaan.view` | Detail lengkap: tahapan, dokumen, history |
| `submit($id)` | POST | `pekerjaan.submit` | Submit ke inspektorat (validasi deadline & kelengkapan) |
| `upload_dok($tahapan_id)` | POST | `pekerjaan.upload_dok` | Upload dokumen persyaratan per tahapan |
| `hapus_dok($dok_id)` | POST | `pekerjaan.upload_dok` | Hapus dokumen |
| `cetak_permohonan($id)` | GET | `pekerjaan.cetak_permohonan` | Cetak surat permohonan reviu (tanpa layout) |

**Validasi bisnis di `simpan()` & `update()`:**
- Jenis `bertahap`: `nilai_kontrak > 200.000.000`
- Jenis `bertahap`: `belanja_pendukung ≤ 5%` dari nilai BKP
- Tidak boleh pakai BKP yang sudah punya pekerjaan aktif

**Validasi bisnis di `submit()`:**
- Cek batas waktu via `Parameter_model::cek_deadline()` → **HARD BLOCK**, tidak ada override
- Cek kelengkapan field: `nama_kegiatan`, `no_dok`, `nama_penyedia`, `nilai_kontrak`, `no_spmk`
- Kirim notifikasi ke user `inspektorat` di kabkota yang sama

---

### `Reviu.php` — Reviu Inspektorat

**Class:** `Reviu extends Auth_Controller`  
**Guard constructor:** `reviu.view`  
**Models:** `Reviu_model`, `Pekerjaan_model`, `Parameter_model`, `Notifikasi_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `reviu.view` | Daftar antrian reviu; filter status/kabkota |
| `form($tahapan_id)` | GET | `reviu.input` | Form reviu + checklist + dokumen existing |
| `simpan_checklist($reviu_id)` | POST | `reviu.input` | Simpan isian checklist (bisa AJAX auto-save) |
| `upload_lhr($reviu_id)` | POST | `reviu.input` | Upload LHR (Laporan Hasil Reviu) |
| `putuskan($reviu_id)` | POST | `reviu.approve` | Keputusan: disetujui / perlu_perbaikan / ditolak |
| `cetak_kertas_kerja($reviu_id)` | GET | `reviu.cetak_kertas_kerja` | Cetak kertas kerja reviu (tanpa layout) |
| `cetak_rekap($reviu_id)` | GET | `reviu.download_rekap` | Cetak rekap hasil reviu (tanpa layout) |

**Flow di `form()`:**
1. `Reviu_model::buat_atau_ambil()` → buat record jika belum ada
2. Set `tahapan.status = inspektorat_reviu`

**Validasi di `putuskan()`:**
- LHR wajib diupload sebelum dapat approve
- Catatan wajib jika hasil = `perlu_perbaikan` atau `ditolak`
- `disetujui` → notif ke SKPKD Kab
- `perlu_perbaikan` → notif kembali ke OPD

---

### `Verif_kab.php` — Verifikasi SKPKD Kab/Kota

**Class:** `Verif_kab extends Auth_Controller`  
**Guard constructor:** `verif_kab.view`  
**Models:** `Verifikasi_kab_model`, `Pekerjaan_model`, `Parameter_model`, `Notifikasi_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `verif_kab.view` | Daftar antrian + rekap nilai penyaluran |
| `form($tahapan_id)` | GET | `verif_kab.input` | Form verifikasi + dokumen + history |
| `upload_dok($tahapan_id)` | POST | `pekerjaan.upload_dok` | Upload dokumen permohonan |
| `hapus_dok($dok_id)` | POST | `pekerjaan.upload_dok` | Hapus dokumen |
| `putuskan($verif_id)` | POST | `verif_kab.approve` | Keputusan: disetujui / perlu_perbaikan / ditolak |
| `konfirmasi($tahapan_id)` | POST | `verif_kab.konfirmasi` | Konfirmasi penerimaan dana + upload bukti RKUD |
| `cetak_rekap($tahapan_id)` | GET | `verif_kab.cetak_rekap` | Cetak rekap kegiatan (tanpa layout) |

**Flow di `konfirmasi()`:**
- Upload file bukti transfer → `trx_bukti_transfer`
- Set `tahapan.status = dikonfirmasi`
- Jika semua tahapan selesai → set `pekerjaan.status = selesai`

---

### `Verif_prov.php` — Verifikasi Provinsi & Input SP2D

**Class:** `Verif_prov extends Auth_Controller`  
**Guard constructor:** `verif_prov.view`  
**Models:** `Verifikasi_prov_model`, `Pekerjaan_model`, `Notifikasi_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `verif_prov.view` | Daftar antrian + rekap penyaluran |
| `form($tahapan_id)` | GET | `verif_prov.view` | Form verifikasi + input SP2D |
| `putuskan($verif_id)` | POST | `verif_prov.approve` | Keputusan verifikasi prov |
| `simpan_sp2d($tahapan_id)` | POST | `penyaluran.input_sp2d` | Input SP2D; jika `status_transfer=selesai` → langsung disalurkan |
| `konfirmasi_transfer($penyaluran_id)` | POST | `penyaluran.input_sp2d` | Konfirmasi transfer selesai → set status `disalurkan` |
| `_set_disalurkan()` | — | private | Helper: update status, log, kirim notif ke SKPKD Kab + OPD |
| `cetak_rekap()` | GET | `laporan.cetak_rekap_penyaluran` | Cetak rekap SP2D (tanpa layout) |

**Catatan penting:**  
- Guard `penyaluran.input_sp2d` di method SP2D (bukan di constructor)
- Auto-create record verif prov saat pertama akses `form()`

---

### `Laporan.php` — Laporan & Export

**Class:** `Laporan extends Auth_Controller`  
**Guard constructor:** `laporan.view`  
**Models:** `Laporan_model`, `Parameter_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `laporan.view` | Redirect ke `laporan/rekap-bkp` |
| `rekap_bkp()` | GET | `laporan.cetak_rekap_bkp` | View rekap BKP; filter tahun/kabkota/bidang |
| `cetak_rekap_bkp()` | GET | `laporan.cetak_rekap_bkp` | Cetak rekap BKP (tanpa layout, HTML to PDF) |
| `rekap_penyaluran()` | GET | `laporan.cetak_rekap_penyaluran` | View rekap SP2D/penyaluran |
| `export_bkp()` | GET | `laporan.export` | Download CSV daftar BKP (UTF-8 BOM) |
| `export_penyaluran()` | GET | `laporan.export` | Download CSV daftar SP2D (UTF-8 BOM) |

---

### `Parameter.php` — Master Data

**Class:** `Parameter extends Auth_Controller`  
**Guard constructor:** `parameter.view`  
**Models:** `Parameter_model`

#### Sub-modul: Tahun Anggaran

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `tahun()` | GET | `parameter.tahun.view` | Daftar tahun + count BKP per tahun |
| `tahun_simpan()` | POST | `parameter.tahun.manage` | Tambah tahun baru |
| `tahun_set_aktif($id)` | GET | `parameter.tahun.manage` | Set tahun aktif (unset yang lain) |
| `tahun_hapus($id)` | GET | `parameter.tahun.manage` | Hapus tahun (validasi: tidak aktif, tidak ada BKP) |

#### Sub-modul: Batas Waktu Pengajuan

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `batas_waktu()` | GET | `parameter.batas_waktu.view` | Daftar batas waktu per tahun |
| `batas_waktu_simpan()` | POST | `parameter.batas_waktu.manage` | Tambah batas waktu |
| `batas_waktu_update($id)` | POST | `parameter.batas_waktu.manage` | Edit + log perubahan per field |
| `batas_waktu_log()` | GET | `parameter.batas_waktu.view` | View log perubahan batas waktu |

#### Sub-modul: Data BKP

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `bkp()` | GET | `parameter.bkp.view` | Daftar BKP; filter tahun/kabkota/bidang |
| `bkp_simpan()` | POST | `parameter.bkp.manage` | Tambah BKP baru |
| `bkp_update($id)` | POST | `parameter.bkp.manage` | Edit BKP + log perubahan |
| `bkp_hapus($id)` | GET | `parameter.bkp.manage` | Hapus BKP (validasi: tidak ada pekerjaan terkait) |
| `bkp_import()` | GET | `parameter.bkp.manage` | Form import BKP (stub — belum diimplementasi) |
| `bkp_cetak()` | GET | `parameter.bkp.view` | Cetak daftar BKP (tanpa layout) |

#### Sub-modul: Data Pemda (Pejabat & Dokumen)

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `pemda()` | GET | `parameter.pemda.view` | View pejabat + dokumen per kabkota/tahun |
| `pemda_simpan_pejabat()` | POST | `parameter.pemda.manage` | Upsert data pejabat (KDH, Inspektur, Kepala BKAD, PPKD) |
| `pemda_simpan_dokumen()` | POST | `parameter.pemda.manage` | Insert/update dokumen (Perda, Perkada, Pergub) |
| `pemda_hapus_dokumen($id)` | GET | `parameter.pemda.manage` | Hapus dokumen |

#### Sub-modul: Log Perubahan

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `log()` | GET | `parameter.*.view` | View log gabungan: BKP, Pemda, Batas Waktu |

---

### `Admin_users.php` — Manajemen User

**Class:** `Admin_users extends Auth_Controller`  
**Guard constructor:** `admin.user.view`  
**Models:** `User_model`, `Role_model`, `Parameter_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `admin.user.view` | Daftar user; filter role/kabkota/status |
| `tambah()` | GET | `admin.user.create` | Form tambah user |
| `simpan()` | POST | `admin.user.create` | Insert user; validasi username unik + `canManageUser()` |
| `edit($id)` | GET | `admin.user.edit` | Form edit user |
| `update($id)` | POST | `admin.user.edit` | Update data user; hash password jika diisi |
| `toggle($id)` | GET | `admin.user.toggle` | Toggle `is_active` (tidak bisa self-toggle) |
| `hapus($id)` | GET | `admin.user.delete` | Hapus user (tidak bisa hapus diri sendiri) |
| `reset_pw($id)` | GET | `admin.user.reset_pw` | Reset password ke `password123` (stub) |

**Guard bisnis:**  
Tidak dapat membuat/mengelola user dengan `role_level` lebih rendah atau sama dengan level user saat ini (`canManageUser()`).

---

### `Admin_roles.php` — Manajemen Role & Permission

**Class:** `Admin_roles extends Auth_Controller`  
**Guard constructor:** `admin.role.view`  
**Models:** `Role_model`, `User_model`

| Method | HTTP | Permission | Fungsi |
|--------|------|-----------|--------|
| `index()` | GET | `admin.role.view` | Daftar role + count permission + count user |
| `tambah()` | GET | `admin.role.create` | Form tambah role |
| `simpan()` | POST | `admin.role.create` | Insert role; level min=3, max=99 |
| `edit($id)` | GET | `admin.role.edit` | Form edit role |
| `update($id)` | POST | `admin.role.edit` | Update role; non-system dapat ubah level |
| `hapus($id)` | GET | `admin.role.delete` | Hapus role (validasi: non-system, tidak ada user) |
| `permissions($id)` | GET | `admin.role.permission` | Form assign permission; grouped by modul |
| `save_permissions($id)` | POST | `admin.role.permission` | Truncate + insert batch permission; log grant/revoke |
| `logs()` | GET | `admin.role.view` | View log perubahan permission |

---

## 4. MODELS

### `User_model.php`

**Tabel utama:** `users` (join: `roles`, `ref_kabkota`)

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_all` | `get_all($filters=[])` | array | Daftar user; filter: `role_id`, `kabkota_id`, `is_active`, `q` |
| `get_by_id` | `get_by_id($id)` | object | Detail user by ID |
| `get_by_username` | `get_by_username($username)` | object | User aktif by username (untuk login) |
| `username_exists` | `username_exists($username, $exclude_id=NULL)` | bool | Cek duplikat username |
| `insert` | `insert($data)` | int | Insert user; return `insert_id` |
| `update` | `update($id, $data)` | bool | Update + set `updated_at` |
| `toggle` | `toggle($id)` | bool | Toggle `is_active` |
| `hapus` | `hapus($id)` | bool | Delete hard |
| `update_last_login` | `update_last_login($id)` | void | Set `last_login = NOW()` |
| `count_per_role` | `count_per_role()` | array | Count user aktif per `role_kode` |

---

### `Role_model.php`

**Tabel:** `roles`, `permissions`, `role_permissions`, `permission_logs`

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_all` | `get_all($only_active=FALSE)` | array | Daftar role; optional filter aktif |
| `get_by_id` | `get_by_id($id)` | object | Detail role |
| `get_all_permissions` | `get_all_permissions()` | array | Semua permission di sistem |
| `get_permissions_by_role` | `get_permissions_by_role($role_id)` | array | Kode permission yang dimiliki role |
| `insert` | `insert($data)` | int | Insert role |
| `update` | `update($id, $data)` | bool | Update role |
| `hapus` | `hapus($id)` | bool | Delete (hanya non-system) |
| `save_permissions` | `save_permissions($role_id, $perm_kodes, $granted_by)` | void | Truncate + insert batch permission |
| `get_modul_meta` | `get_modul_meta()` | array | Metadata modul dari `Rbac::getModulMeta()` |
| `log_permission` | `log_permission($role_id, $role_nama, $aksi, $kode, $user_id)` | void | Log grant/revoke ke `permission_logs` |

---

### `Parameter_model.php`

Mengelola seluruh master data referensi.  
**Tabel:** `ref_tahun`, `ref_batas_waktu`, `ref_batas_waktu_log`, `ref_bkp`, `ref_bkp_log`, `ref_pemda_pejabat`, `ref_pemda_dokumen`, `ref_pemda_log`, `ref_kabkota`, `ref_bidang`

#### Tahun Anggaran

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_all_tahun` | `get_all_tahun()` | array | Daftar tahun order DESC |
| `get_tahun_aktif` | `get_tahun_aktif()` | string | Tahun `is_aktif=1`; fallback ke tahun sekarang |
| `tahun_exists` | `tahun_exists($tahun)` | bool | Cek duplikat |
| `insert_tahun` | `insert_tahun($data)` | int | Insert |
| `set_tahun_aktif` | `set_tahun_aktif($tahun)` | void | Set semua `is_aktif=0`, lalu 1 untuk tahun ini |
| `hapus_tahun` | `hapus_tahun($id)` | void | Delete |

#### Batas Waktu

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_batas_waktu` | `get_batas_waktu($tahun=NULL)` | array | Daftar batas waktu per tahun |
| `get_batas_waktu_by_id` | `get_batas_waktu_by_id($id)` | object | Get by ID |
| `cek_deadline` | `cek_deadline($tahun, $jenis, $kode_tahap)` | array | Return `['ok'=>bool, 'pesan'=>string, 'bw'=>object]` |
| `insert_batas_waktu` | `insert_batas_waktu($data)` | int | Insert |
| `update_batas_waktu` | `update_batas_waktu($id, $data, $user_id)` | void | Update + log setiap field berubah |
| `get_log_batas_waktu` | `get_log_batas_waktu($limit=50)` | array | Log perubahan |

#### Data BKP

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_bkp` | `get_bkp($filters=[])` | array | Daftar BKP; filter: `tahun`, `kabkota_id`, `bidang_id`, `q` |
| `get_bkp_by_id` | `get_bkp_by_id($id)` | object | Detail BKP |
| `bkp_exists` | `bkp_exists($kode, $tahun, $exclude=NULL)` | bool | Cek duplikat kode+tahun |
| `insert_bkp` | `insert_bkp($data)` | int | Insert |
| `update_bkp` | `update_bkp($id, $data, $user_id)` | void | Update + log field berubah |
| `hapus_bkp` | `hapus_bkp($id)` | void | Delete |
| `rekap_bkp` | `rekap_bkp($tahun, $kabkota_id=NULL)` | object | Count + sum nilai BKP aktif |
| `get_log_bkp` | `get_log_bkp($tahun=NULL, $limit=100)` | array | Log perubahan BKP |

#### Data Pemda

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_pejabat` | `get_pejabat($kabkota_id, $tahun)` | array | Daftar pejabat per jenis |
| `get_dokumen_pemda` | `get_dokumen_pemda($kabkota_id, $tahun)` | array | Daftar dokumen Perda/Perkada |
| `get_dokumen_by_id` | `get_dokumen_by_id($id)` | object | Get by ID |
| `simpan_pejabat` | `simpan_pejabat($data, $user_id)` | void | Upsert pejabat per jenis + log |
| `simpan_dokumen` | `simpan_dokumen($data, $id_edit, $user_id)` | void | Insert atau update dokumen |
| `hapus_dokumen` | `hapus_dokumen($id)` | void | Delete |
| `get_log_pemda` | `get_log_pemda($tahun=NULL, $limit=50)` | array | Log perubahan |

#### Dropdown Helpers

| Method | Return | Fungsi |
|--------|--------|--------|
| `get_kabkota()` | array | Daftar kabkota aktif order nama |
| `get_bidang()` | array | Daftar bidang aktif |

---

### `Pekerjaan_model.php`

Model utama untuk transaksi pekerjaan.  
**Tabel:** `trx_pekerjaan`, `trx_tahapan_penyaluran`, `trx_dokumen_persyaratan`, `trx_pekerjaan_log`, `trx_status_history`

#### Read

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_all` | `get_all($filters=[])` | array | Daftar pekerjaan + info BKP/kabkota/bidang; filter: `tahun`, `kabkota_id`, `status`, `jenis_penyaluran`, `opd_nama`, `q` |
| `get_by_id` | `get_by_id($id)` | object | Detail pekerjaan + join referensi |
| `get_by_bkp` | `get_by_bkp($bkp_id)` | object | Pekerjaan berdasarkan BKP ID |
| `bkp_sudah_ada` | `bkp_sudah_ada($bkp_id, $exclude_id=NULL)` | bool | Cek duplikat BKP |

#### Write

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `insert` | `insert($data)` | int | Insert pekerjaan; return ID |
| `update` | `update($id, $data, $user_id=NULL)` | bool | Update + log field yang berubah |
| `set_status` | `set_status($id, $status_baru, $user_id, $catatan='')` | bool | Update status + insert `trx_status_history` |

#### Tahapan Penyaluran

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_tahapan` | `get_tahapan($pekerjaan_id)` | array | Daftar tahapan order urutan ASC |
| `get_tahapan_by_id` | `get_tahapan_by_id($id)` | object | Detail tahapan + info batas_waktu |
| `buat_tahapan` | `buat_tahapan($pekerjaan_id, $jenis, $nilai_kontrak, $tahun, $user_id)` | void | Buat 1 atau 2 tahapan sesuai jenis penyaluran |
| `get_count_tahapan` | `get_count_tahapan($pekerjaan_id)` | int | Count tahapan |

**Logika `buat_tahapan()`:**
- `bertahap` → 2 tahapan (Tahap I: 70%, Tahap II: 30%)
- `sekaligus` / `khusus_mendesak` / `khusus_bencana` → 1 tahapan (100%)

#### Dokumen Persyaratan

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_dokumen` | `get_dokumen($tahapan_id)` | array | Dokumen per tahapan |
| `get_dokumen_by_id` | `get_dokumen_by_id($dok_id)` | object | Get by ID |
| `get_semua_dokumen_pekerjaan` | `get_semua_dokumen_pekerjaan($pekerjaan_id)` | array | Semua dokumen lintas tahapan |
| `insert_dokumen` | `insert_dokumen($data)` | int | Insert |
| `hapus_dokumen` | `hapus_dokumen($dok_id)` | void | Delete |

#### Status History

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_status_history` | `get_status_history($pekerjaan_id)` | array | Daftar perubahan status + user + timestamp |

---

### `Reviu_model.php`

**Tabel:** `trx_reviu_inspektorat`, `trx_checklist_reviu`, `ref_checklist_items`

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_antrian` | `get_antrian($filters=[])` | array | Tahapan siap reviu; join reviu, pekerjaan, BKP |
| `get_by_tahapan` | `get_by_tahapan($tahapan_id)` | object | Reviu record + info inspektur |
| `get_by_id` | `get_by_id($id)` | object | Get by ID |
| `buat_atau_ambil` | `buat_atau_ambil($tahapan_id, $user_id)` | int | Insert jika belum ada; return `reviu_id` |
| `update` | `update($id, $data)` | bool | Update + `updated_at` |
| `get_checklist_items` | `get_checklist_items($jenis_penyaluran, $kode_tahap)` | array | Item checklist (CK-01 s/d CK-21) per jenis/tahap |
| `get_isian` | `get_isian($reviu_id)` | array | Isian checklist per reviu, keyed by `item_id` |
| `simpan_checklist` | `simpan_checklist($reviu_id, $isian)` | void | Upsert isian checklist |
| `hitung_checklist` | `hitung_checklist($reviu_id)` | object | `total`, `terisi`, `percentage` |
| `update_lhr` | `update_lhr($reviu_id, $no_lhr, $tgl_lhr, $file_path, $ref_inspektur_id)` | void | Simpan data LHR |
| `count_by_status` | `count_by_status($tahun, $kabkota_id=NULL)` | array | Count tahapan per status (untuk dashboard) |

---

### `Verifikasi_kab_model.php`

**Tabel:** `trx_verifikasi_skpkd_kab`, `trx_penyaluran_dana`, `trx_bukti_transfer`

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_antrian` | `get_antrian($filters=[])` | array | Antrian verif kab; filter status/kabkota |
| `get_by_tahapan` | `get_by_tahapan($tahapan_id)` | object | Verifikasi record + info PPKD |
| `get_by_id` | `get_by_id($id)` | object | Get by ID |
| `buat_atau_ambil` | `buat_atau_ambil($tahapan_id, $user_id)` | int | Insert jika belum ada; return ID |
| `update` | `update($id, $data)` | bool | Update |
| `get_dokumen` | `get_dokumen($tahapan_id)` | array | Dokumen permohonan per tahapan |
| `get_penyaluran` | `get_penyaluran($tahapan_id)` | object | Data SP2D / penyaluran |
| `simpan_bukti_transfer` | `simpan_bukti_transfer($penyaluran_id, $file_path, $nama_file, $keterangan, $user_id)` | void | Insert bukti transfer |
| `rekap_nilai` | `rekap_nilai($tahun, $kabkota_id=NULL)` | object | Sum nilai kontrak + transfer |
| `count_by_status` | `count_by_status($tahun, $kabkota_id=NULL)` | array | Count per status |

---

### `Verifikasi_prov_model.php`

**Tabel:** `trx_verifikasi_skpkd_prov`, `trx_penyaluran_dana`

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_antrian` | `get_antrian($filters=[])` | array | Antrian verif prov |
| `get_verif_by_tahapan` | `get_verif_by_tahapan($tahapan_id)` | object | Verif prov record |
| `get_verif_by_id` | `get_verif_by_id($id)` | object | Get by ID |
| `buat_atau_ambil_verif` | `buat_atau_ambil_verif($tahapan_id, $user_id)` | int | Insert jika belum ada; return ID |
| `update_verif` | `update_verif($id, $data)` | bool | Update |
| `get_penyaluran` | `get_penyaluran($tahapan_id)` | object | SP2D data |
| `simpan_sp2d` | `simpan_sp2d($tahapan_id, $data, $user_id)` | int | Insert SP2D; return `penyaluran_id` |
| `update_status_transfer` | `update_status_transfer($penyaluran_id, $status)` | void | Update kolom `status_transfer` |
| `get_daftar_sp2d` | `get_daftar_sp2d($tahun, $kabkota_id=NULL)` | array | Daftar SP2D + join pekerjaan/kabkota |
| `rekap_penyaluran` | `rekap_penyaluran($tahun)` | object | Sum total transfer per tahun |
| `count_by_status` | `count_by_status($tahun)` | array | Count per status (dashboard) |

---

### `Notifikasi_model.php`

**Tabel:** `trx_notifikasi`

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `count_unread` | `count_unread($user_id)` | int | Count notif belum dibaca |
| `get_recent` | `get_recent($user_id, $limit=5)` | array | Notif terbaru untuk badge/dropdown |
| `mark_read` | `mark_read($id, $user_id)` | void | Set `is_read=1` |
| `mark_all_read` | `mark_all_read($user_id)` | void | Set semua `is_read=1` untuk user |
| `kirim` | `kirim($user_id, $judul, $pesan, $jenis='info', $url=NULL, $pekerjaan_id=NULL, $tahapan_id=NULL)` | void | Insert notif baru |

**Jenis notifikasi:** `info`, `sukses`, `peringatan`, `error`

---

### `Laporan_model.php`

**Tabel:** `ref_bkp`, `trx_pekerjaan`, `trx_tahapan_penyaluran`, `trx_penyaluran_dana`

#### Statistik Dashboard

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_stats_provinsi` | `get_stats_provinsi($tahun)` | array | Total BKP, pekerjaan per status, SP2D, kab aktif |
| `get_stats_kabkota` | `get_stats_kabkota($tahun, $kabkota_id)` | array | Stats untuk view kabkota |
| `get_per_bidang` | `get_per_bidang($tahun, $kabkota_id=NULL)` | array | Distribusi pekerjaan per bidang |
| `get_per_kabkota` | `get_per_kabkota($tahun)` | array | Distribusi per kabkota (hanya provinsi) |

#### Funnel & Rekap

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `get_funnel` | `get_funnel($tahun, $kabkota_id=NULL)` | array | Pekerjaan per tahapan status (pipeline view) |
| `get_rekap_bkp` | `get_rekap_bkp($tahun, $kabkota_id=NULL, $bidang_id=NULL)` | array | Rekap BKP + status pekerjaan + nilai |
| `get_rekap_summary` | `get_rekap_summary($tahun, $kabkota_id=NULL)` | object | Summary: total BKP, nilai, pekerjaan aktif |

---

## 5. VIEWS

### Struktur Direktori Views

```
application/views/
├── layouts/
│   └── main.php              ← layout utama (sidebar RBAC, topbar, flash)
├── auth/
│   └── login.php
├── landing/
│   └── index.php
├── dashboard/
│   ├── index.php             ← stats, funnel, per bidang/kabkota, antrian aksi
│   └── pilih_tahun.php
├── parameter/
│   ├── tahun.php
│   ├── batas_waktu.php
│   ├── batas_waktu_log.php
│   ├── bkp.php
│   ├── bkp_import.php        ← STUB, belum diimplementasi
│   ├── bkp_cetak.php
│   ├── pemda.php
│   └── log.php
├── pekerjaan/
│   ├── index.php
│   ├── form.php              ← 17 field + Leaflet map pin lokasi
│   ├── detail.php            ← timeline, dokumen, history status
│   └── cetak_permohonan.php  ← tanpa layout
├── reviu/
│   ├── index.php
│   ├── form.php              ← form checklist CK-01 s/d CK-21
│   ├── cetak_kertas_kerja.php
│   └── cetak_rekap.php
├── verif_kab/
│   ├── index.php
│   ├── form.php
│   └── cetak_rekap.php
├── verif_prov/
│   ├── index.php
│   ├── form.php              ← form verifikasi + input SP2D
│   └── cetak_rekap_penyaluran.php
├── laporan/
│   ├── rekap_bkp.php
│   ├── rekap_penyaluran.php
│   └── cetak_rekap_bkp.php
└── admin/
    ├── users/
    │   ├── index.php
    │   └── form.php
    └── roles/
        ├── index.php
        ├── form.php
        ├── permissions.php   ← matrix permission per modul
        └── logs.php
```

### Catatan Views

- Semua view dalam flow utama di-render via `$this->render()` → di-wrap `layouts/main.php`
- Views `cetak_*` di-render via `$this->render_plain()` → tanpa layout, untuk browser print / save PDF
- `layouts/main.php` membangun sidebar menggunakan `$this->rbac->getMenus()` — otomatis menyesuaikan per role
- Flash message (`success`, `error`, `warning`) di-handle oleh `layouts/main.php`; `success` dan `warning` auto-close 4 detik via JS

---

## 6. LIBRARY & HELPER

### `application/libraries/Rbac.php`

RBAC dinamis dari database. Di-load otomatis oleh `Auth_Controller`.  
**Tabel:** `role_permissions` ← join `permissions`

#### Cek Akses

| Method | Signature | Return | Fungsi |
|--------|-----------|--------|--------|
| `can` | `can($kode)` | bool | Cek 1 permission; `superadmin` selalu `true` |
| `canAny` | `canAny(array $kodes)` | bool | Cek apakah punya minimal 1 dari daftar permission |
| `requirePermission` | `requirePermission($kode, $redirect='dashboard')` | void | Guard; redirect jika tidak punya akses |
| `requireLogin` | `requireLogin()` | void | Guard login |

#### Cek Role

| Method | Return | Fungsi |
|--------|--------|--------|
| `isProvinsi()` | bool | `superadmin` atau `admin_provinsi` |
| `isKabkota()` | bool | `skpkd_kabkota`, `inspektorat`, atau `opd_teknis` |
| `isSuperadmin()` | bool | Hanya `superadmin` |
| `getLevel()` | int | `role_level`; default 99 jika tidak ada session |
| `canManageUser($target_level)` | bool | `true` jika `current_level < target_level` |

#### Menu & Metadata

| Method | Return | Fungsi |
|--------|--------|--------|
| `getMenus()` | array | Menu navigasi berdasarkan permission aktif |
| `getSubParameter()` | array | Sub-menu modul Parameter |
| `getSubPengaturan()` | array | Sub-menu modul Pengaturan (admin) |
| `getModulMeta()` | array | Metadata modul untuk form matrix permission |
| `getPermsByRole($role_id)` | array | Kode permission untuk role ID tertentu |
| `resetCache()` | void | Clear cache permission per request |

**Caching:** Permission di-cache dalam properti instance per request (tidak ke session/file).

---

### `application/helpers/siberkah_helper.php`

Di-autoload via `autoload.php`. Tersedia global di semua controller dan view.

#### Format Angka

| Function | Signature | Output Contoh |
|----------|-----------|---------------|
| `rupiah` | `rupiah($angka)` | `Rp 1.500.000` |
| `rupiah_juta` | `rupiah_juta($angka)` | `Rp 1,50 Jt` |

#### Format Tanggal

| Function | Signature | Output Contoh |
|----------|-----------|---------------|
| `tgl_indo` | `tgl_indo($tgl)` | `15 Januari 2026` / `—` jika kosong |
| `tgl_short` | `tgl_short($tgl)` | `15/01/2026` atau `15/01/2026 08:30` |

#### Badge & Status

| Function | Signature | Output |
|----------|-----------|--------|
| `badge_status` | `badge_status($status)` | HTML `<span class="badge badge-{warna}">Label</span>` |
| `badge_jenis` | `badge_jenis($jenis)` | Badge jenis penyaluran |
| `badge_role` | `badge_role($kode)` | Badge role user |

#### Deadline

| Function | Signature | Output |
|----------|-----------|--------|
| `deadline_info` | `deadline_info($tgl_batas)` | HTML info sisa hari (merah/kuning/abu) |
| `is_deadline_lewat` | `is_deadline_lewat($tgl_batas)` | `bool` |

#### Label & Icon

| Function | Signature | Output |
|----------|-----------|--------|
| `label_instansi` | `label_instansi($jenis)` | Label teks jenis instansi |
| `label_jenis_dok` | `label_jenis_dok($jenis)` | Label teks jenis dokumen |
| `icon_file` | `icon_file($path)` | Class icon Tabler (`ti-file-pdf`, dll.) |

#### Sub-Nav Builder

| Function | Signature | Output |
|----------|-----------|--------|
| `sub_nav_parameter` | `sub_nav_parameter($active_sub)` | HTML sub-nav modul Parameter |
| `sub_nav_pengaturan` | `sub_nav_pengaturan($active_sub)` | HTML sub-nav modul Pengaturan |

---

## 7. ALUR WORKFLOW BISNIS

```
OPD Teknis
  1. input() → pilih BKP → form 17 field + pin lokasi
  2. simpan() → status: draft, tahapan dibuat otomatis
  3. detail() → upload dokumen persyaratan per tahapan
  4. submit() → cek deadline (HARD BLOCK) + kelengkapan
              → status: opd_submitted
              → notif: inspektorat

Inspektorat
  5. reviu/form() → buat record reviu otomatis
                  → status tahapan: inspektorat_reviu
  6. simpan_checklist() → isi 21 item CK-01 s/d CK-21
  7. upload_lhr() → upload LHR
  8. putuskan() →
       disetujui       → status: inspektorat_approved → notif: SKPKD Kab
       perlu_perbaikan → status: inspektorat_revisi   → notif: OPD

SKPKD Kab/Kota
  9. verif_kab/form() → buat record verifikasi
                      → status tahapan: skpkd_kab_verif
 10. upload_dok() → dokumen permohonan pencairan
 11. putuskan() →
       disetujui       → status: skpkd_kab_approved → notif: Admin Provinsi
       perlu_perbaikan → status: skpkd_kab_revisi   → notif: OPD

Admin Provinsi / BKAD
 12. verif_prov/form() → buat record verif prov
                       → status tahapan: skpkd_prov_verif
 13. putuskan() →
       disetujui       → status tetap skpkd_prov_verif (menunggu SP2D)
       perlu_perbaikan → status: skpkd_prov_revisi
 14. simpan_sp2d() → input No. SP2D, nilai, rekening
     konfirmasi_transfer() → jika status_transfer=selesai
       → status tahapan: disalurkan
       → notif: SKPKD Kab + OPD

SKPKD Kab/Kota (konfirmasi penerimaan)
 15. konfirmasi() → upload bukti RKUD
                 → status: dikonfirmasi
                 → jika semua tahapan selesai → pekerjaan.status: selesai
```

**Status pekerjaan (enum lengkap):**
```
draft → opd_submitted → inspektorat_reviu → inspektorat_revisi
→ inspektorat_approved → skpkd_kab_verif → skpkd_kab_revisi
→ skpkd_kab_approved → skpkd_prov_verif → skpkd_prov_revisi
→ disalurkan_tahap1 → dikonfirmasi_tahap1 → opd_capaian_tahap1
→ [alur Tahap II untuk bertahap]
→ disalurkan_sekaligus / disalurkan_tahap2 → selesai / ditolak
```

---

## 8. STRUKTUR TABEL DATABASE

### Tabel Referensi (`ref_`)

| Tabel | Kolom Kunci | Fungsi |
|-------|-------------|--------|
| `ref_tahun` | `tahun`, `is_aktif` | Tahun anggaran multi-year |
| `ref_kabkota` | `id`, `nama`, `is_aktif` | 33 Kab/Kota Sumatera Utara |
| `ref_bidang` | `id`, `kode`, `nama` | 12 bidang kegiatan |
| `ref_bkp` | `id`, `kode_bkp`, `tahun`, `kabkota_id`, `bidang_id`, `nilai` | Master BKP per tahun/kab/bidang |
| `ref_batas_waktu` | `id`, `tahun`, `jenis_penyaluran`, `kode_tahap`, `tgl_batas` | Deadline submit per jenis/tahap |
| `ref_checklist_items` | `id`, `kode_item`, `uraian`, `jenis_penyaluran`, `kode_tahap` | 21 item checklist statis |
| `ref_pemda_pejabat` | `id`, `kabkota_id`, `tahun`, `jenis_jabatan`, `nama`, `nip` | KDH, Inspektur, PPKD, Kepala BKAD |
| `ref_pemda_dokumen` | `id`, `kabkota_id`, `tahun`, `jenis_dok`, `nomor`, `tanggal` | Perda/Perkada/Pergub per kab |

### Tabel RBAC & User

| Tabel | Kolom Kunci | Fungsi |
|-------|-------------|--------|
| `users` | `id`, `username`, `password`, `role_id`, `kabkota_id`, `is_active` | Semua akun user |
| `roles` | `id`, `kode`, `nama`, `level`, `is_system` | 6 role bawaan + custom |
| `permissions` | `id`, `kode`, `nama`, `modul`, `jenis` | ~50 permission kode |
| `role_permissions` | `role_id`, `permission_kode` | Relasi M:N |
| `user_logs` | `user_id`, `aksi`, `keterangan`, `ip`, `created_at` | Audit trail aktivitas |
| `permission_logs` | `role_id`, `aksi`, `permission_kode`, `user_id`, `created_at` | Log grant/revoke |

### Tabel Transaksi (`trx_`)

| Tabel | Kolom Kunci | Fungsi |
|-------|-------------|--------|
| `trx_pekerjaan` | `id`, `bkp_id`, `status`, `jenis_penyaluran`, `nilai_kontrak` | Data pekerjaan utama (1:1 dengan ref_bkp) |
| `trx_tahapan_penyaluran` | `id`, `pekerjaan_id`, `urutan`, `kode_tahap`, `status`, `porsi_persen` | 1 atau 2 tahapan per pekerjaan |
| `trx_dokumen_persyaratan` | `id`, `tahapan_id`, `jenis_dok`, `nama_file`, `file_path` | Upload dokumen per tahapan |
| `trx_reviu_inspektorat` | `id`, `tahapan_id`, `user_id`, `hasil_reviu`, `no_lhr`, `file_lhr` | Record reviu (UNIQUE per tahapan) |
| `trx_checklist_reviu` | `id`, `reviu_id`, `item_id`, `nilai`, `catatan` | Isian checklist per item |
| `trx_verifikasi_skpkd_kab` | `id`, `tahapan_id`, `user_id`, `hasil_verifikasi`, `catatan` | Verifikasi kab (UNIQUE per tahapan) |
| `trx_verifikasi_skpkd_prov` | `id`, `tahapan_id`, `user_id`, `hasil_verifikasi`, `catatan` | Verifikasi prov (UNIQUE per tahapan) |
| `trx_penyaluran_dana` | `id`, `tahapan_id`, `no_sp2d`, `tgl_sp2d`, `nilai_transfer`, `status_transfer` | Data SP2D (UNIQUE per tahapan) |
| `trx_bukti_transfer` | `id`, `penyaluran_id`, `file_path`, `keterangan` | Bukti RKUD dari kab |
| `trx_status_history` | `id`, `pekerjaan_id`, `status_lama`, `status_baru`, `user_id`, `catatan` | Audit trail status |
| `trx_notifikasi` | `id`, `user_id`, `judul`, `pesan`, `jenis`, `url`, `is_read` | In-app notification |
| `trx_capaian_output` | `id`, `tahapan_id`, `deskripsi`, `foto_path` | Capaian output fisik (tabel ada, UI belum) |

### Tabel Log (`_log`)

| Tabel | Fungsi |
|-------|--------|
| `ref_bkp_log` | Log perubahan field BKP |
| `ref_batas_waktu_log` | Log perubahan batas waktu |
| `ref_pemda_log` | Log perubahan data pejabat/dokumen pemda |
| `trx_pekerjaan_log` | Log perubahan field pekerjaan |

---

## 9. SISTEM PERMISSION

### 6 Role Bawaan

| Kode | Level | Scope | Akses |
|------|-------|-------|-------|
| `superadmin` | 1 | Provinsi | Semua akses; bypass RBAC |
| `admin_provinsi` | 2 | Provinsi | Kelola user, parameter, verif final, SP2D |
| `skpkd_kabkota` | 3 | Kab/Kota | Verifikasi, permohonan, konfirmasi RKUD |
| `inspektorat` | 4 | Kab/Kota | Reviu dokumen, checklist, upload LHR |
| `opd_teknis` | 5 | Kab/Kota | Input pekerjaan, upload dokumen |
| `pengawas` | 8 | Fleksibel | View-only seluruh data |

**Aturan hierarki:** user hanya dapat mengelola user dengan `role_level` lebih tinggi (nilai lebih besar).

### Daftar Permission Lengkap

| Modul | Permission Kode |
|-------|-----------------|
| Dashboard | `dashboard.view`, `dashboard.provinsi` |
| Parameter | `parameter.view`, `parameter.tahun.view`, `parameter.tahun.manage`, `parameter.bkp.view`, `parameter.bkp.manage`, `parameter.pemda.view`, `parameter.pemda.manage`, `parameter.batas_waktu.view`, `parameter.batas_waktu.manage` |
| Pekerjaan | `pekerjaan.view`, `pekerjaan.view_all`, `pekerjaan.input`, `pekerjaan.edit`, `pekerjaan.upload_dok`, `pekerjaan.download_dok`, `pekerjaan.submit`, `pekerjaan.cetak_permohonan` |
| Reviu | `reviu.view`, `reviu.input`, `reviu.approve`, `reviu.cetak_kertas_kerja`, `reviu.download_rekap` |
| Verif Kab | `verif_kab.view`, `verif_kab.input`, `verif_kab.approve`, `verif_kab.konfirmasi`, `verif_kab.cetak_rekap` |
| Verif Prov | `verif_prov.view`, `verif_prov.approve` |
| Penyaluran | `penyaluran.view`, `penyaluran.input_sp2d` |
| Capaian | `capaian.view`, `capaian.input` |
| Laporan | `laporan.view`, `laporan.cetak_rekap_bkp`, `laporan.cetak_rekap_penyaluran`, `laporan.export` |
| Admin User | `admin.view`, `admin.user.view`, `admin.user.create`, `admin.user.edit`, `admin.user.delete`, `admin.user.toggle`, `admin.user.reset_pw` |
| Admin Role | `admin.role.view`, `admin.role.create`, `admin.role.edit`, `admin.role.delete`, `admin.role.permission` |

---

*Dokumen ini mencakup 11 controller, 9 model, 1 library (Rbac), 1 helper (siberkah_helper), dan 1 base controller (MY_Controller).*  
*Update dokumen ini setiap kali ada penambahan controller, model, atau perubahan API method yang signifikan.*
