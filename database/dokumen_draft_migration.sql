-- ================================================================
-- dokumen_draft_migration.sql
-- Tambah kolom path file dokumen wajib pre-submit OPD
-- ================================================================
-- Jalankan di DB Railway dan lokal sebelum deploy.
-- ================================================================

ALTER TABLE trx_pekerjaan
  ADD COLUMN IF NOT EXISTS dok_spk_path  VARCHAR(500) NULL COMMENT 'File SPK (Surat Perintah Kerja)'   AFTER nilai_belanja_pendukung,
  ADD COLUMN IF NOT EXISTS dok_spmk_path VARCHAR(500) NULL COMMENT 'File SPMK (Surat Perintah Mulai Kerja)' AFTER dok_spk_path,
  ADD COLUMN IF NOT EXISTS dok_bast_path VARCHAR(500) NULL COMMENT 'File BAST — hanya untuk jenis sekaligus' AFTER dok_spmk_path;

-- Tambah kolom JSON rincian belanja pendukung
ALTER TABLE trx_pekerjaan
  ADD COLUMN IF NOT EXISTS belanja_pendukung_json TEXT NULL
    COMMENT 'JSON array rincian belanja pendukung [{uraian,nilai},...]'
  AFTER dok_bast_path;
