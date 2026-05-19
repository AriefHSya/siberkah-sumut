-- ================================================================
-- ci_sessions.sql — Tabel Session Database untuk Production
-- ================================================================
-- Dibutuhkan saat APP_ENV=production (sess_driver='database')
-- Jalankan SEBELUM pertama kali deploy ke production.
--
-- CodeIgniter 3 Session Database Driver membutuhkan tabel ini.
-- Struktur WAJIB persis seperti ini — jangan ubah nama kolom.
-- ================================================================

CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id`         VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45)  NOT NULL,
    `timestamp`  INT(10) UNSIGNED DEFAULT 0 NOT NULL,
    `data`       BLOB NOT NULL,
    KEY `ci_sessions_timestamp` (`timestamp`)
);
