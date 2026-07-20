# Spesifikasi Teknis — SIBERKAH SUMUT
## Sistem Informasi Bantuan Keuangan Daerah Provinsi Sumatera Utara

**Versi Aplikasi:** 4.1.0  
**Pengelola:** BKAD Provinsi Sumatera Utara  
**Dasar Hukum:** SE Gubernur Sumatera Utara No. 900.1.1.3689 Tanggal 8 Mei 2026  

---

## 1. Ringkasan Sistem

SIBERKAH adalah platform kolaborasi multi-peran (*multi-role*) berbasis web untuk mengelola penyaluran dana Bantuan Keuangan Provinsi (BKP) kepada 33 Kabupaten/Kota di Sumatera Utara. Sistem mencakup alur kerja dari input pekerjaan oleh OPD Teknis, reviu oleh Inspektorat, verifikasi berjenjang (Kabupaten/Kota dan Provinsi), penerbitan SP2D, hingga konfirmasi RKUD dan pelaporan capaian output.

---

## 2. Tech Stack

### 2.1 Backend

| Komponen | Teknologi | Versi |
|---|---|---|
| Bahasa Pemrograman | PHP | **8.2** (runtime semua environment) |
| Framework | CodeIgniter | 3.1.13 |
| Web Server | Apache HTTP Server | 2.4.x |
| Modul Apache | `mod_rewrite`, `mod_php` | — |
| Database | MySQL / MariaDB | 5.7+ / 10.3+ |
| Database Driver PHP | `php-mysqli` | — |
| Ekstensi PHP (wajib) | `php-mysqli`, `php-zip`, `php-gd`, `php-xml`, `php-mbstring` | — |

### 2.2 Frontend

| Komponen | Teknologi | Versi | Metode |
|---|---|---|---|
| Peta Interaktif | Leaflet.js | 1.9.4 | CDN |
| Clustering Marker Peta | Leaflet.MarkerCluster | 1.5.3 | CDN |
| Chart / Grafik | Chart.js | 4.4.0 | CDN |
| Tile Map | OpenStreetMap | — | CDN |
| Icon Set | Tabler Icons | 2.44.0 | CDN |
| CSS Framework | *Custom Design System* (`siberkah.css`) | — | Lokal |
| JavaScript Utilities | `siberkah.js` | — | Lokal |
| Markup | HTML5 / PHP Template | — | Server-side render |

### 2.3 Library PHP (Lokal, Tanpa Composer)

| File | Fungsi |
|---|---|
| `application/libraries/Rbac.php` | Role-Based Access Control dinamis dari database |
| `application/libraries/XlsxWriter.php` | Generate file Excel (.xlsx) untuk export laporan |
| `application/libraries/XlsxReader.php` | Parsing file Excel (.xlsx) untuk import data BKP |

### 2.4 Infrastruktur & Deployment

#### Opsi A — Railway (Uji Coba Live)

| Komponen | Detail |
|---|---|
| Container | Docker (`debian:bookworm-slim` + Apache + PHP 8.2) |
| Platform | Railway.app |
| File sistem | Ephemeral (file upload hilang saat redeploy) |
| Solusi file upload | Railway Volume di-mount ke `/var/www/html/uploads` |
| Database | MySQL di Railway |
| Session storage | Database (`ci_sessions` table) |

#### Opsi B — VPS Ubuntu (Produksi)

| Komponen | Detail |
|---|---|
| OS | Ubuntu Server 22.04 LTS |
| Web Server | Apache 2.4 dengan `mod_rewrite` |
| PHP | 8.2 via PPA Ondřej |
| Database | MySQL 8.0 / MariaDB |
| Session storage | Database (`ci_sessions` table) |
| Direktori Instalasi | `/home/ard/siberkah` |
| File Permission | Owner `ard:www-data`, runtime folder `770` |

---

## 3. Arsitektur Aplikasi

### 3.1 Pola Arsitektur

```
MVC (Model-View-Controller) — CodeIgniter 3
├── MY_Controller (base)
│   ├── Auth_Controller   — semua halaman terproteksi
│   └── Guest_Controller  — login, landing
```

### 3.2 Statistik Kode

| Metrik | Jumlah |
|---|---|
| Controller | 18 file |
| Model | 13 file |
| View | 67 file |
| Total file PHP (application/) | 121 file |
| Routing URL | 143 route |
| Tabel Database | 37 tabel |
| Total baris schema.sql | 851 baris |

### 3.3 Struktur URL & Routing

Semua URL bersih (*clean URL*) tanpa `index.php` menggunakan `mod_rewrite`. Contoh pola:

```
/pekerjaan                  → Pekerjaan::index()
/pekerjaan/tambah           → Pekerjaan::tambah()
/pekerjaan/detail/{id}      → Pekerjaan::detail($id)
/reviu/form/{tahapan_id}    → Reviu::form($tahapan_id)
/laporan/rekap-bkp          → Laporan::rekap_bkp()
```

---

## 4. Database

### 4.1 Spesifikasi Koneksi

| Parameter | Nilai |
|---|---|
| Driver | `mysqli` |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |
| Query Builder | Aktif (CodeIgniter Active Record) |
| Debug Mode | Aktif di development, nonaktif di production |
| Persistent Connection | Tidak (pconnect = FALSE) |

### 4.2 Daftar Tabel (37 tabel)

**Tabel RBAC:**

| Tabel | Fungsi |
|---|---|
| `roles` | Definisi peran (6 bawaan + custom) |
| `permissions` | ~50 kode permission |
| `role_permissions` | Relasi M:N role ↔ permission |
| `users` | Semua pengguna semua instansi |
| `permission_logs` | Audit trail perubahan permission |

**Tabel Referensi (`ref_`):**

| Tabel | Fungsi |
|---|---|
| `ref_tahun` | Tahun anggaran (multi-year) |
| `ref_kabkota` | 33 Kabupaten/Kota Sumatera Utara |
| `ref_bidang` | 12 bidang kegiatan |
| `ref_bkp` | Data BKP per tahun per kab/kota |
| `ref_bkp_log` | Log perubahan data BKP |
| `ref_bkp_import_log` | Log import Excel BKP |
| `ref_batas_waktu` | Batas waktu pengajuan per jenis per tahun |
| `ref_batas_waktu_log` | Log perubahan batas waktu |
| `ref_checklist_item` | 22 item checklist reviu statis |
| `ref_pemda_pejabat` | KDH, Kepala BKAD, Inspektur per kab per tahun |
| `ref_pemda_dokumen` | Perda/Pergub/Perkada per kab per tahun |
| `ref_pemda_log` | Log perubahan data pemda |
| `ref_pejabat_bkad_prov` | Pejabat BKAD Provinsi (TTD nota dinas) |
| `ref_app_setting` | Konfigurasi aplikasi (logo, SMTP, dll) |
| `ref_landing_pejabat` | Foto pejabat Provinsi untuk landing page |
| `ref_landing_slideshow` | Foto slideshow landing page |

**Tabel Transaksi (`trx_`):**

| Tabel | Fungsi |
|---|---|
| `trx_pekerjaan` | Data pekerjaan BKP |
| `trx_pekerjaan_log` | Audit trail perubahan data pekerjaan |
| `trx_tahapan_penyaluran` | Tahapan penyaluran (1 atau 2 tahap) |
| `trx_dokumen_persyaratan` | Dokumen upload per tahapan |
| `trx_reviu_inspektorat` | Record reviu Inspektorat |
| `trx_checklist_reviu` | Isian checklist per reviu |
| `trx_verifikasi_skpkd_kab` | Verifikasi Kabupaten/Kota |
| `trx_verifikasi_skpkd_prov` | Verifikasi Provinsi |
| `trx_permohonan` | Bundel permohonan penyaluran |
| `trx_permohonan_item` | Item kegiatan dalam bundel permohonan |
| `trx_penyaluran_dana` | Data SP2D per tahapan |
| `trx_bukti_transfer` | Bukti transfer RKUD dari kab/kota |
| `trx_capaian_output` | Capaian output fisik OPD |
| `trx_status_history` | Audit trail semua perubahan status |
| `trx_notifikasi` | Notifikasi antar pengguna |
| `user_logs` | Log aktivitas pengguna |

---

## 5. Keamanan (Security)

### 5.1 Autentikasi & Sesi

| Fitur | Implementasi |
|---|---|
| Autentikasi | Username + password (bcrypt hash) |
| Manajemen sesi | CodeIgniter Session Library |
| Session driver | `database` (production), `files` (development) |
| Session expiry | 7.200 detik (2 jam) |
| Session regenerasi | Setiap 300 detik |
| Cookie | `HttpOnly` + `Secure` (aktif di production) |
| Cookie prefix | `siberkah_` |

### 5.2 Proteksi Serangan

| Jenis Serangan | Mitigasi |
|---|---|
| CSRF | Token CSRF di semua form POST (`csrf_protection = TRUE`) |
| XSS | `htmlspecialchars()` wajib di semua output user input |
| SQL Injection | CodeIgniter Query Builder (parameterized query) |
| Directory Traversal | Akses `application/`, `system/`, folder upload privat diblokir via `.htaccess` |
| IDOR (Insecure Direct Object Reference) | Scope check `kabkota_id` di setiap method keputusan |
| Akses tidak sah | RBAC check di constructor setiap controller |

### 5.3 Manajemen File Upload

| Parameter | Nilai |
|---|---|
| Ukuran maksimum | 10 MB |
| Tipe file diizinkan | `pdf`, `doc`, `docx`, `xls`, `xlsx`, `jpg`, `jpeg`, `png` |
| Validasi MIME | `finfo` (bukan hanya ekstensi) |
| Penamaan file | `bin2hex(random_bytes(16)).<ext>` (nama tidak dapat ditebak) |
| Nama file asli | Disimpan di kolom `nama_asli` / `nama_lhr_asli` / dll |
| Akses file privat | Hanya via controller `Berkas.php` + RBAC check + scope check |
| Folder upload publik | `uploads/logo/`, `uploads/landing/` |
| Folder upload privat | `uploads/dokumen/`, `uploads/lhr/`, `uploads/permohonan/`, `uploads/capaian/` |

### 5.4 Enkripsi

| Komponen | Detail |
|---|---|
| Encryption key | 64-karakter hex, wajib via environment variable `ENCRYPTION_KEY` |
| Password storage | bcrypt (CodeIgniter built-in) |
| Transport security | HTTPS (wajib di production, dikelola di level server/proxy) |

---

## 6. Kontrol Akses (RBAC)

### 6.1 Peran Pengguna

| Kode | Level | Scope | Fungsi |
|---|---|---|---|
| `superadmin` | 1 | Provinsi | Akses penuh, bypass semua RBAC |
| `admin_provinsi` | 2 | Provinsi | Kelola user, parameter, verifikasi final, SP2D |
| `skpkd_kabkota` | 3 | Kab/Kota | Verifikasi, permohonan, konfirmasi RKUD |
| `inspektorat` | 4 | Kab/Kota | Reviu dokumen, checklist, upload LHR |
| `opd_teknis` | 5 | Kab/Kota | Input pekerjaan, upload dokumen |
| `pengawas` | 8 | Fleksibel | View-only seluruh data |

Role tambahan dapat dibuat melalui UI Admin tanpa modifikasi kode.

### 6.2 Mekanisme RBAC

- Permission disimpan di database (dinamis, dapat dikonfigurasi via UI)
- Setiap aksi dikontrol oleh kode permission (format: `modul.aksi`)
- Guard di constructor controller: `$this->requirePerm('modul.aksi')`
- Sekitar 50 kode permission tercatat di sistem
- Menu sidebar di-render berdasarkan permission yang dimiliki pengguna

---

## 7. Konfigurasi Environment

### 7.1 Variabel Environment (Production)

| Variabel | Keterangan | Contoh |
|---|---|---|
| `APP_ENV` | Mode aplikasi | `production` |
| `APP_URL` | URL dasar aplikasi | `https://siberkah.example.id/` |
| `ENCRYPTION_KEY` | Kunci enkripsi 64 hex char | `8ecb4be9...` |
| `DB_HOST` | Host database | `127.0.0.1` |
| `DB_PORT` | Port database | `3306` |
| `DB_USER` | Username database | `siberkah_user` |
| `DB_PASS` | Password database | — |
| `DB_NAME` | Nama database | `siberkah_sumut` |

### 7.2 Perbedaan Development vs Production

| Konfigurasi | Development | Production |
|---|---|---|
| Session driver | `files` | `database` |
| Cookie Secure | FALSE | TRUE |
| Cookie HttpOnly | FALSE | TRUE |
| DB debug mode | TRUE | FALSE |
| Log threshold | Level 1 (semua) | Level 3 (error saja) |
| Encryption key | Fallback hardcoded | Wajib via env var |

---

## 8. Alur Bisnis Utama

### 8.1 Status Pekerjaan (State Machine)

```
draft
  → opd_submitted
  → inspektorat_reviu  ↔ inspektorat_revisi (kembali ke OPD)
  → inspektorat_approved
  → skpkd_kab_verif    ↔ skpkd_kab_revisi  (kembali ke OPD)
  → skpkd_kab_approved
  → skpkd_prov_verif   ↔ skpkd_prov_revisi (kembali ke Kab)
  → disalurkan_tahap1  → dikonfirmasi_tahap1 → opd_capaian_tahap1
      → [alur Tahap II untuk jenis bertahap]
  → disalurkan_sekaligus / disalurkan_tahap2
  → selesai
  → ditolak
```

### 8.2 Jenis Penyaluran

| Jenis | Keterangan | Tahap |
|---|---|---|
| `bertahap` | Nilai kontrak > Rp 400 juta | 2 tahap |
| `sekaligus` | Penyaluran penuh satu kali | 1 tahap |
| `khusus_mendesak` | Kebutuhan mendesak | 1 tahap |
| `khusus_bencana` | Kondisi bencana | 1 tahap |

---

## 9. Fitur Utama

| No | Fitur | Status |
|---|---|---|
| 1 | Input pekerjaan BKP (17 field + pin lokasi Leaflet + upload dokumen) | ✅ Selesai |
| 2 | Reviu Inspektorat (22 item checklist + upload LHR) | ✅ Selesai |
| 3 | Verifikasi berjenjang (Kab/Kota + Provinsi) | ✅ Selesai |
| 4 | Bundel permohonan penyaluran oleh SKPKD Kab/Kota | ✅ Selesai |
| 5 | Cetak Nota Dinas (Kabid, Kabadan, Ringkasan) | ✅ Selesai |
| 6 | Input SP2D oleh Admin Provinsi | ✅ Selesai |
| 7 | Konfirmasi RKUD oleh SKPKD Kab/Kota | ✅ Selesai |
| 8 | Input Capaian Output Fisik + upload Berita Acara | ✅ Selesai |
| 9 | Dashboard peta cluster Leaflet (khusus Provinsi/Pengawas) | ✅ Selesai |
| 10 | Dashboard chart distribusi per bidang (Chart.js) | ✅ Selesai |
| 11 | Laporan rekap BKP + rekap penyaluran + rekap Tahap II | ✅ Selesai |
| 12 | Export laporan ke XLSX (tanpa Composer) | ✅ Selesai |
| 13 | Import data BKP dari Excel/CSV (dengan preview & validasi) | ✅ Selesai |
| 14 | Checklist reviu khusus Tahap II (CK-21, CK-22) | ✅ Selesai |
| 15 | Manajemen pengguna & role via UI | ✅ Selesai |
| 16 | Log aktivitas & riwayat perubahan status | ✅ Selesai |
| 17 | Landing page (slideshow + foto pejabat Provinsi) | ✅ Selesai |
| 18 | Batas waktu pengajuan (hard block otomatis) | ✅ Selesai |
| 19 | Wajib ganti password saat pertama login | ✅ Selesai |
| 20 | Notifikasi in-app antar pengguna | ✅ Selesai |

---

## 10. Ketergantungan Eksternal

| Layanan | Fungsi |
|---|---|
| CDN Leaflet.js | Peta interaktif |
| CDN Chart.js | Grafik dashboard |
| CDN Tabler Icons | Icon set UI |
| OpenStreetMap Tile Server | Tile peta |

---

## 11. Persyaratan Server Minimum

### VPS / On-Premise

| Komponen | Minimum | Rekomendasi |
|---|---|---|
| OS | Ubuntu 20.04 LTS | Ubuntu 22.04 LTS |
| CPU | 1 vCore | 2 vCore |
| RAM | 1 GB | 2 GB |
| Storage | 50 GB | 150 GB+ (untuk file upload) |
| PHP | 8.2 | 8.2 |
| MySQL | 5.7 | 8.0 |
| Apache | 2.4 | 2.4 |

### Docker / Railway

| Komponen | Nilai |
|---|---|
| Base image | `debian:bookworm-slim` |
| PHP | 8.2 |
| Volume mount (wajib) | `/var/www/html/uploads` |
| Port | 80 (dikonfigurasi otomatis via `PORT` env Railway) |

---
