-- ================================================================
-- dokumen_draft_migration.sql
-- Tambah kolom path file dokumen wajib pre-submit OPD
-- ================================================================
-- Jalankan di DB Railway dan lokal sebelum deploy.
-- ================================================================

-- Jalankan satu per satu. Jika muncul "Duplicate column name" berarti kolom sudah ada — skip.
ALTER TABLE trx_pekerjaan ADD COLUMN dok_spk_path  VARCHAR(500) NULL COMMENT 'File SPK';
ALTER TABLE trx_pekerjaan ADD COLUMN dok_spmk_path VARCHAR(500) NULL COMMENT 'File SPMK';
ALTER TABLE trx_pekerjaan ADD COLUMN dok_bast_path VARCHAR(500) NULL COMMENT 'File BAST (sekaligus)';
ALTER TABLE trx_pekerjaan ADD COLUMN belanja_pendukung_json TEXT NULL COMMENT 'JSON rincian belanja pendukung';
