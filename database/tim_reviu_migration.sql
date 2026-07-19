-- Migrasi: Fitur Tim Review (Poin 7)
-- Jalankan pada database existing (fresh install dari schema.sql sudah mencakup ini)

CREATE TABLE IF NOT EXISTS `trx_tim_reviu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` year NOT NULL,
  `no_sk` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_sk` date NOT NULL,
  `file_sk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_asli_sk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_no_sk` (`kabkota_id`,`tahun`,`no_sk`),
  KEY `idx_tim_kabkota` (`kabkota_id`,`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trx_tim_reviu_anggota` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tim_id` int NOT NULL,
  `urutan` tinyint unsigned NOT NULL DEFAULT '1',
  `nama` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_anggota_tim` (`tim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambah kolom tim_id ke trx_reviu_inspektorat
ALTER TABLE `trx_reviu_inspektorat`
  ADD COLUMN `tim_id` int DEFAULT NULL
    COMMENT 'FK → trx_tim_reviu'
  AFTER `tahapan_id`;

-- Permission baru
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('tim_reviu.view',   'Lihat Tim Review',   'tim_reviu', 'menu'),
('tim_reviu.manage', 'Kelola Tim Review',  'tim_reviu', 'aksi');

-- Assign ke inspektorat
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.kode = 'inspektorat'
  AND p.kode IN ('tim_reviu.view', 'tim_reviu.manage');
