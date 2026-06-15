-- ============================================================
-- must_change_password_migration.sql
-- Kolom flag untuk memaksa user mengganti password setelah
-- direset oleh admin atau setelah akun baru dibuat.
--
-- JALANKAN SEKALI di database sebelum deploy fitur ini.
-- Jika muncul "Duplicate column name" berarti kolom sudah ada — skip.
-- ============================================================

ALTER TABLE users
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Wajib ganti password saat login berikutnya (1=ya)'
    AFTER password;
