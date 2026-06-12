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

 Date: 25/05/2026 17:21:26
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ref_pejabat_bkad_prov
-- ----------------------------
DROP TABLE IF EXISTS `ref_pejabat_bkad_prov`;
CREATE TABLE `ref_pejabat_bkad_prov` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` year NOT NULL,
  `jenis` enum('kepala_badan','kabid_anggaran','bendahara_pengeluaran') NOT NULL,
  `nama` varchar(200) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `jabatan` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pejabat_bkad` (`tahun`,`jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
