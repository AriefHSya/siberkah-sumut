-- ============================================================
-- SIBERKAH SUMUT — Migration: Landing Page Foto & Slideshow
-- Jalankan di Navicat: Query → Run SQL File
-- ============================================================

CREATE TABLE IF NOT EXISTS ref_landing_pejabat (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  jenis      ENUM('gubernur','wakil_gubernur','sekda','kepala_bkad') NOT NULL UNIQUE,
  nama       VARCHAR(150) DEFAULT NULL,
  jabatan    VARCHAR(200) DEFAULT NULL,
  foto_path  VARCHAR(500) DEFAULT NULL,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  updated_by INT          DEFAULT NULL,
  updated_at DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO ref_landing_pejabat (jenis, jabatan) VALUES
('gubernur',      'Gubernur Sumatera Utara'),
('wakil_gubernur','Wakil Gubernur Sumatera Utara'),
('sekda',         'Sekretaris Daerah Provinsi Sumatera Utara'),
('kepala_bkad',   'Kepala BKAD Provinsi Sumatera Utara');

CREATE TABLE IF NOT EXISTS ref_landing_slideshow (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  judul      VARCHAR(200)  DEFAULT NULL,
  foto_path  VARCHAR(500)  NOT NULL,
  caption    TEXT          DEFAULT NULL,
  urutan     INT           NOT NULL DEFAULT 0,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  created_by INT           DEFAULT NULL,
  created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission baru
INSERT IGNORE INTO permissions (kode, nama, modul, jenis) VALUES
('parameter.landing.view',   'Lihat Tampilan Landing', 'parameter', 'menu'),
('parameter.landing.manage', 'Kelola Tampilan Landing','parameter', 'aksi');

-- Berikan permission ke superadmin dan admin_provinsi
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.kode IN ('parameter.landing.view','parameter.landing.manage')
WHERE r.kode IN ('superadmin','admin_provinsi');
