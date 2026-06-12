-- ============================================================
-- admin_logs_migration.sql
-- Permission baru untuk modul "Log Aktivitas" (Admin_logs.php).
-- Halaman read-only: menampilkan user_logs & trx_status_history.
--
-- JALANKAN SEKALI. Menggunakan INSERT IGNORE — aman jika
-- dijalankan ulang.
-- ============================================================

-- 1. Permission
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('admin.logs.view', 'Lihat Log Aktivitas', 'admin', 'menu');

-- 2. Assign default ke superadmin & admin_provinsi
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode IN ('superadmin','admin_provinsi')
  AND p.kode = 'admin.logs.view';
