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

 Date: 25/05/2026 15:48:46
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for trx_permohonan_item
-- ----------------------------
CREATE TABLE IF NOT EXISTS `trx_permohonan_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permohonan_id` int NOT NULL,
  `tahapan_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_item` (`permohonan_id`,`tahapan_id`),
  KEY `idx_tahapan` (`tahapan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
