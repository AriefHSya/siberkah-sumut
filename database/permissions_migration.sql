-- ================================================================
-- permissions_migration.sql — Tambah permission baru ke DB existing
-- ================================================================
-- Jalankan file ini jika database sudah ada tapi permission
-- capaian dan landing belum ter-insert (Railway atau server existing).
-- Semua query pakai INSERT IGNORE — aman dijalankan berulang kali.
-- ================================================================

-- Permission Capaian Output
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('capaian.view',  'Lihat Data Capaian', 'capaian', 'menu'),
('capaian.input', 'Input Capaian Fisik','capaian', 'aksi');

-- Permission Tampilan Landing Page
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('parameter.landing.view',   'Lihat Tampilan Landing',  'parameter', 'menu'),
('parameter.landing.manage', 'Kelola Tampilan Landing', 'parameter', 'aksi');

-- Berikan capaian.view & capaian.input ke role yang relevan:
--   superadmin (1), admin_provinsi (2), skpkd_kabkota (3), inspektorat (4), opd_teknis (5)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode IN ('superadmin','admin_provinsi','skpkd_kabkota','inspektorat','opd_teknis')
  AND p.kode IN ('capaian.view','capaian.input');

-- Berikan parameter.landing.* ke superadmin dan admin_provinsi saja
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode IN ('superadmin','admin_provinsi')
  AND p.kode IN ('parameter.landing.view','parameter.landing.manage');

-- Konfirmasi
SELECT p.kode, r.kode as role FROM role_permissions rp
JOIN permissions p ON p.id = rp.permission_id
JOIN roles r ON r.id = rp.role_id
WHERE p.kode IN ('capaian.view','capaian.input','parameter.landing.view','parameter.landing.manage')
ORDER BY p.kode, r.level;
