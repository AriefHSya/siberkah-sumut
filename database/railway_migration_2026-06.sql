-- ============================================================
-- railway_migration_2026-06.sql
-- Konsolidasi SEMUA migrasi database periode 15 Mei – 15 Juni 2026
-- (Sprint 7: Log Aktivitas, hardening upload, Permohonan bundel,
--  SP2D/RKUD, Pejabat BKAD Prov, wajib ganti password, dll)
--
-- Sumber file (lihat juga CLAUDE.md bagian "MIGRASI DATABASE"):
--   - admin_logs_migration.sql
--   - must_change_password_migration.sql
--   - secure_uploads_migration.sql
--   - ref_pejabat_bkad_prov.sql
--   - trx_permohonan.sql + trx_permohonan_item.sql
--   - upsert_unique_migration.sql
--   - permohonan_status_migration.sql
--   - dokumen_draft_migration.sql (defensif, untuk dependency #6)
--
-- CARA PAKAI:
--   Jalankan SATU PER SATU (statement demi statement), bukan
--   sekaligus sebagai satu blok. Server Railway tidak mendukung
--   "ADD COLUMN/KEY IF NOT EXISTS", jadi setiap ALTER TABLE
--   ADD COLUMN/ADD KEY di bawah ini polos (tanpa IF NOT EXISTS).
--
--   Jika sebuah statement menghasilkan error:
--     - "Duplicate column name '...'"  -> kolom sudah ada, SKIP
--     - "Duplicate key name '...'"     -> key/index sudah ada, SKIP
--     - "Table '...' already exists"   -> tidak terjadi (pakai IF NOT EXISTS)
--   lanjut ke statement berikutnya seperti biasa.
-- ============================================================

SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 1. Permission "Log Aktivitas" (admin_logs_migration.sql)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('admin.logs.view', 'Lihat Log Aktivitas', 'admin', 'menu');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode IN ('superadmin','admin_provinsi')
  AND p.kode = 'admin.logs.view';

-- ─────────────────────────────────────────────────────────────
-- 2. Wajib ganti password (must_change_password_migration.sql)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE users
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Wajib ganti password saat login berikutnya (1=ya)'
    AFTER password;

-- ─────────────────────────────────────────────────────────────
-- 3. Dependency defensif: kolom dokumen draft pekerjaan
--    (dokumen_draft_migration.sql — diperlukan oleh bagian 4.5)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE trx_pekerjaan ADD COLUMN dok_spk_path  VARCHAR(500) NULL COMMENT 'File SPK';
ALTER TABLE trx_pekerjaan ADD COLUMN dok_spmk_path VARCHAR(500) NULL COMMENT 'File SPMK';
ALTER TABLE trx_pekerjaan ADD COLUMN dok_bast_path VARCHAR(500) NULL COMMENT 'File BAST (sekaligus)';
ALTER TABLE trx_pekerjaan ADD COLUMN belanja_pendukung_json TEXT NULL COMMENT 'JSON rincian belanja pendukung';

-- ─────────────────────────────────────────────────────────────
-- 4. Secure uploads — kolom nama file asli (secure_uploads_migration.sql)
-- ─────────────────────────────────────────────────────────────
-- 4.1 Dokumen persyaratan tahapan
ALTER TABLE trx_dokumen_persyaratan
  ADD COLUMN nama_asli VARCHAR(255) NULL
    COMMENT 'Nama file asli sebelum direname ke nama acak'
    AFTER nama_file;

-- 4.2 LHR Inspektorat
ALTER TABLE trx_reviu_inspektorat
  ADD COLUMN nama_lhr_asli VARCHAR(255) NULL
    COMMENT 'Nama file LHR asli sebelum direname ke nama acak'
    AFTER file_lhr_path;

-- 4.3 Foto/dokumen capaian output
ALTER TABLE trx_capaian_output
  ADD COLUMN nama_foto_asli VARCHAR(255) NULL
    COMMENT 'Nama file foto/dokumen asli sebelum direname ke nama acak'
    AFTER foto_path;

-- 4.4 File permohonan -> lihat bagian 6 (setelah tabel trx_permohonan dibuat)

-- 4.5 Dokumen draft pekerjaan (SPK, SPMK, BAST)
ALTER TABLE trx_pekerjaan
  ADD COLUMN nama_dok_spk VARCHAR(255) NULL
    COMMENT 'Nama file SPK asli sebelum direname ke nama acak'
    AFTER dok_spk_path;
ALTER TABLE trx_pekerjaan
  ADD COLUMN nama_dok_spmk VARCHAR(255) NULL
    COMMENT 'Nama file SPMK asli sebelum direname ke nama acak'
    AFTER dok_spmk_path;
ALTER TABLE trx_pekerjaan
  ADD COLUMN nama_dok_bast VARCHAR(255) NULL
    COMMENT 'Nama file BAST asli sebelum direname ke nama acak'
    AFTER dok_bast_path;

-- ─────────────────────────────────────────────────────────────
-- 5. Tabel ref_pejabat_bkad_prov (ref_pejabat_bkad_prov.sql)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ref_pejabat_bkad_prov` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` year NOT NULL,
  `jenis` enum('kepala_badan','kabid_anggaran','bendahara_pengeluaran') NOT NULL,
  `nama` varchar(200) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `jabatan` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pejabat_bkad` (`tahun`,`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 6. Tabel trx_permohonan + trx_permohonan_item
--    (trx_permohonan.sql, trx_permohonan_item.sql,
--     permohonan_status_migration.sql)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `trx_permohonan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` varchar(4) NOT NULL,
  `jenis_penyaluran` varchar(30) NOT NULL,
  `kode_tahap` varchar(20) NOT NULL,
  `no_permohonan` varchar(100) DEFAULT NULL,
  `tgl_permohonan` date DEFAULT NULL,
  `status` enum('draft','diajukan','batal','ditolak') NOT NULL DEFAULT 'draft',
  `catatan` text,
  `catatan_tolak` text,
  `file_surat_permohonan_path` varchar(255) DEFAULT NULL,
  `file_surat_pernyataan_path` varchar(255) DEFAULT NULL,
  `file_rekap_kegiatan_path` varchar(255) DEFAULT NULL,
  `nota_kabid_at` datetime DEFAULT NULL,
  `nota_kabadan_at` datetime DEFAULT NULL,
  `ringkasan_at` datetime DEFAULT NULL,
  `no_sp2d` varchar(100) DEFAULT NULL,
  `tgl_sp2d` date DEFAULT NULL,
  `nilai_sp2d` bigint unsigned DEFAULT NULL,
  `rek_asal` varchar(100) DEFAULT NULL,
  `nama_bank_asal` varchar(100) DEFAULT NULL,
  `rek_tujuan` varchar(100) DEFAULT NULL,
  `nama_bank_tujuan` varchar(100) DEFAULT NULL,
  `status_sp2d` enum('proses','selesai','gagal') DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kabkota_tahun` (`kabkota_id`,`tahun`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trx_permohonan_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permohonan_id` int NOT NULL,
  `tahapan_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_item` (`permohonan_id`,`tahapan_id`),
  KEY `idx_tahapan` (`tahapan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika error "Duplicate column name 'catatan_tolak'" -> SKIP
ALTER TABLE trx_permohonan
  ADD COLUMN catatan_tolak text AFTER catatan;

-- 4.4 (lanjutan bagian 4) — nama file asli dokumen permohonan
-- Jika error "Duplicate column name '...'" -> SKIP per baris
ALTER TABLE trx_permohonan
  ADD COLUMN nama_surat_permohonan VARCHAR(255) NULL
    COMMENT 'Nama asli surat_permohonan sebelum direname'
    AFTER file_surat_permohonan_path;
ALTER TABLE trx_permohonan
  ADD COLUMN nama_surat_pernyataan VARCHAR(255) NULL
    COMMENT 'Nama asli surat_pernyataan sebelum direname'
    AFTER file_surat_pernyataan_path;
ALTER TABLE trx_permohonan
  ADD COLUMN nama_rekap_kegiatan VARCHAR(255) NULL
    COMMENT 'Nama asli rekap_kegiatan sebelum direname'
    AFTER file_rekap_kegiatan_path;

-- 6.1 Kolom konfirmasi RKUD oleh SKPKD Kab/Kota (Penyaluran_kab)
-- Jika error "Duplicate column name '...'" -> SKIP per baris
ALTER TABLE trx_permohonan
  ADD COLUMN kode_transaksi_rkud VARCHAR(100) NULL;
ALTER TABLE trx_permohonan
  ADD COLUMN nilai_rkud BIGINT UNSIGNED NULL;
ALTER TABLE trx_permohonan
  ADD COLUMN tgl_rkud DATE NULL;
ALTER TABLE trx_permohonan
  ADD COLUMN tgl_konfirmasi_rkud DATETIME NULL;

-- 6.2 Status 'selesai' untuk permohonan yang sudah dikonfirmasi RKUD
-- (set otomatis oleh Penyaluran_kab::konfirmasi()). MODIFY aman diulang.
ALTER TABLE trx_permohonan
  MODIFY COLUMN status enum('draft','diajukan','batal','ditolak','selesai') NOT NULL DEFAULT 'draft';

-- Backfill: permohonan yang sudah dikonfirmasi RKUD sebelum kolom status
-- 'selesai' ditambahkan -> tandai selesai (sekali jalan, aman diulang)
UPDATE trx_permohonan
  SET status = 'selesai'
  WHERE kode_transaksi_rkud IS NOT NULL AND status = 'diajukan';

-- ─────────────────────────────────────────────────────────────
-- 7. UNIQUE KEY defensif untuk tabel upsert 1:1 per tahapan
--    (upsert_unique_migration.sql)
--    Jika error "Duplicate key name '...'" -> SKIP
-- ─────────────────────────────────────────────────────────────
ALTER TABLE trx_reviu_inspektorat
  ADD UNIQUE KEY uq_reviu_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_kab
  ADD UNIQUE KEY uq_verif_kab_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_prov
  ADD UNIQUE KEY uq_verif_prov_tahapan (tahapan_id);

ALTER TABLE trx_penyaluran_dana
  ADD UNIQUE KEY uq_penyaluran_tahapan (tahapan_id);

ALTER TABLE trx_capaian_output
  ADD UNIQUE KEY uq_capaian_tahapan (tahapan_id);

-- ─────────────────────────────────────────────────────────────
-- 8. Permission modul Penyaluran Kab/Kota (konfirmasi RKUD)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('penyaluran_kab.view',       'Lihat Penyaluran Kab', 'penyaluran_kab', 'menu'),
('penyaluran_kab.konfirmasi', 'Konfirmasi RKUD',      'penyaluran_kab', 'aksi');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode = 'skpkd_kabkota'
  AND p.kode IN ('penyaluran_kab.view','penyaluran_kab.konfirmasi');

-- ─────────────────────────────────────────────────────────────
-- 9. Lockout login 5x gagal + permission buka akun terkunci
--    Jika error "Duplicate column name '...'" -> SKIP per baris
-- ─────────────────────────────────────────────────────────────
ALTER TABLE users
  ADD COLUMN failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Jumlah percobaan login gagal berturut-turut'
    AFTER password;
ALTER TABLE users
  ADD COLUMN locked_at DATETIME NULL
    COMMENT 'Waktu akun dikunci otomatis (5x gagal login). NULL = tidak terkunci'
    AFTER failed_login_attempts;

INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('admin.user.unlock', 'Buka Akun Terkunci', 'admin', 'aksi');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode IN ('superadmin','admin_provinsi','skpkd_kabkota')
  AND p.kode = 'admin.user.unlock';

-- ─────────────────────────────────────────────────────────────
-- 10. Fix enum kode_tahap trx_tahapan_penyaluran + backfill data khusus
--     trx_tahapan_penyaluran.kode_tahap harus punya nilai 'khusus'
--     (bukan 'khusus_mendesak'/'khusus_bencana') agar konsisten
--     dengan ref_batas_waktu.kode_tahap dan _definisi_tahapan().
-- ─────────────────────────────────────────────────────────────
ALTER TABLE trx_tahapan_penyaluran
  MODIFY COLUMN kode_tahap enum('tahap_1','tahap_2','sekaligus','khusus') NOT NULL;

-- Backfill: tahapan khusus yang kode_tahap-nya kosong akibat enum mismatch
UPDATE trx_tahapan_penyaluran t
JOIN trx_pekerjaan p ON p.id = t.pekerjaan_id
SET t.kode_tahap = 'khusus'
WHERE (t.kode_tahap = '' OR t.kode_tahap IS NULL)
  AND p.jenis_penyaluran IN ('khusus_mendesak','khusus_bencana');

-- ─────────────────────────────────────────────────────────────
-- 11. Berita Acara Kemajuan Pekerjaan — kolom ba_path & nama_ba_asli
--     di trx_capaian_output (upload opsional dari form capaian OPD)
--     Jika error "Duplicate column name '...'" -> SKIP
-- ─────────────────────────────────────────────────────────────
ALTER TABLE trx_capaian_output
  ADD COLUMN ba_path VARCHAR(255) NULL
    COMMENT 'Path file Berita Acara Kemajuan Pekerjaan'
    AFTER foto_path;
ALTER TABLE trx_capaian_output
  ADD COLUMN nama_ba_asli VARCHAR(255) NULL
    COMMENT 'Nama file BA asli sebelum direname ke nama acak'
    AFTER ba_path;

-- ============================================================
-- SELESAI — verifikasi cepat
-- ============================================================
SELECT 'Migration railway_migration_2026-06.sql selesai dijalankan.' AS info;
