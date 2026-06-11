-- ================================================================
-- logo_base64_migration.sql
-- Ubah kolom ref_app_setting.nilai ke MEDIUMTEXT
-- ================================================================
-- Diperlukan karena logo sekarang disimpan sebagai base64 data URL
-- langsung di DB (bukan path file), agar tidak bergantung filesystem.
-- TEXT (64KB) tidak cukup — MEDIUMTEXT mendukung hingga 16MB.
-- ================================================================
-- Jalankan di semua environment (Railway, production, lokal).
-- Aman dijalankan berulang kali (MODIFY tidak gagal jika sudah MEDIUMTEXT).
-- ================================================================

ALTER TABLE ref_app_setting
  MODIFY COLUMN nilai MEDIUMTEXT COLLATE utf8mb4_unicode_ci;

-- Bersihkan nilai lama yang berupa path file (bukan data URL)
-- Nilai path file dimulai dengan 'uploads/', bukan 'data:'
UPDATE ref_app_setting
  SET nilai = '', updated_at = NOW()
  WHERE kode = 'logo_provinsi'
    AND nilai != ''
    AND nilai NOT LIKE 'data:%';
