-- ============================================================
-- secure_uploads_migration.sql
-- Kolom baru untuk menyimpan nama file asli (sebelum diubah
-- menjadi nama acak saat upload). Diperlukan oleh controller
-- Berkas.php agar nama asli dapat ditampilkan saat unduhan.
--
-- JALANKAN SEKALI di database sebelum deploy fitur ini.
-- Semua ALTER menggunakan ADD COLUMN IF NOT EXISTS — aman
-- jika dijalankan ulang.
-- ============================================================

-- 1. Nama asli dokumen persyaratan tahapan
ALTER TABLE trx_dokumen_persyaratan
  ADD COLUMN IF NOT EXISTS nama_asli VARCHAR(255) NULL
    COMMENT 'Nama file asli sebelum direname ke nama acak'
    AFTER nama_file;

-- 2. Nama asli LHR Inspektorat
ALTER TABLE trx_reviu_inspektorat
  ADD COLUMN IF NOT EXISTS nama_lhr_asli VARCHAR(255) NULL
    COMMENT 'Nama file LHR asli sebelum direname ke nama acak'
    AFTER file_lhr_path;

-- 3. Nama asli foto/dokumen capaian output
ALTER TABLE trx_capaian_output
  ADD COLUMN IF NOT EXISTS nama_foto_asli VARCHAR(255) NULL
    COMMENT 'Nama file foto/dokumen asli sebelum direname ke nama acak'
    AFTER foto_path;

-- 4. Nama asli file permohonan (3 jenis dokumen)
ALTER TABLE trx_permohonan
  ADD COLUMN IF NOT EXISTS nama_surat_permohonan VARCHAR(255) NULL
    COMMENT 'Nama asli surat_permohonan sebelum direname'
    AFTER file_surat_permohonan_path,
  ADD COLUMN IF NOT EXISTS nama_surat_pernyataan VARCHAR(255) NULL
    COMMENT 'Nama asli surat_pernyataan sebelum direname'
    AFTER file_surat_pernyataan_path,
  ADD COLUMN IF NOT EXISTS nama_rekap_kegiatan VARCHAR(255) NULL
    COMMENT 'Nama asli rekap_kegiatan sebelum direname'
    AFTER file_rekap_kegiatan_path;

-- 5. Nama asli dokumen draft pekerjaan (SPK, SPMK, BAST)
ALTER TABLE trx_pekerjaan
  ADD COLUMN IF NOT EXISTS nama_dok_spk VARCHAR(255) NULL
    COMMENT 'Nama file SPK asli sebelum direname ke nama acak'
    AFTER dok_spk_path,
  ADD COLUMN IF NOT EXISTS nama_dok_spmk VARCHAR(255) NULL
    COMMENT 'Nama file SPMK asli sebelum direname ke nama acak'
    AFTER dok_spmk_path,
  ADD COLUMN IF NOT EXISTS nama_dok_bast VARCHAR(255) NULL
    COMMENT 'Nama file BAST asli sebelum direname ke nama acak'
    AFTER dok_bast_path;

-- Catatan: trx_bukti_transfer.nama_file sudah diubah di kode
-- untuk menyimpan nama asli (bukan nama tersimpan). Tidak perlu
-- migrasi skema untuk tabel ini.
