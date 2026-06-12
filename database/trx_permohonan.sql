/*
 Navicat Premium Dump SQL

 Source Server         : keuangan
 Source Server Type    : MySQL
 Source Server Version : 90600 (9.6.0)
 Source Host           : localhost:3306
 Source Schema         : siberkah_sumut

 Target Server Type    : MySQL
 Target Server Version : 90600 (9.6.0)
 File Encoding         : 65001

 Date: 25/05/2026 15:47:10
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for trx_permohonan
-- ----------------------------
DROP TABLE IF EXISTS `trx_permohonan`;
CREATE TABLE `trx_permohonan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` varchar(4) NOT NULL,
  `jenis_penyaluran` varchar(30) NOT NULL,
  `kode_tahap` varchar(20) NOT NULL,
  `no_permohonan` varchar(100) DEFAULT NULL,
  `tgl_permohonan` date DEFAULT NULL,
  `status` enum('draft','diajukan') NOT NULL DEFAULT 'draft',
  `catatan` text,
  `file_surat_permohonan_path` varchar(255) DEFAULT NULL,
  `file_surat_pernyataan_path` varchar(255) DEFAULT NULL,
  `file_rekap_kegiatan_path` varchar(255) DEFAULT NULL,
  `nota_kabid_at` datetime DEFAULT NULL,
  `nota_kabadan_at` datetime DEFAULT NULL,
  `ringkasan_at` datetime DEFAULT NULL,
  `no_sp2d` varchar(100) DEFAULT NULL,
  `tgl_sp2d` date DEFAULT NULL,
  `nilai_sp2d` bigint unsigned DEFAULT NULL,
  `rek_asal` varchar(100) DEFAULT NULL,
  `nama_bank_asal` varchar(100) DEFAULT NULL,
  `rek_tujuan` varchar(100) DEFAULT NULL,
  `nama_bank_tujuan` varchar(100) DEFAULT NULL,
  `status_sp2d` enum('proses','selesai','gagal') DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kabkota_tahun` (`kabkota_id`,`tahun`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
