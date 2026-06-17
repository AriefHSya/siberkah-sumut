
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;
SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '448bbbb8-00cd-11f1-9d50-42a88396694b:1-250';
DROP TABLE IF EXISTS `permission_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int DEFAULT NULL,
  `role_nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aksi` enum('grant','revoke') COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_kode` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `modul` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('menu','aksi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menu',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_app_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_app_setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` text COLLATE utf8mb4_unicode_ci,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_batas_waktu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_batas_waktu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` year NOT NULL,
  `jenis_penyaluran` enum('bertahap','sekaligus','khusus_mendesak','khusus_bencana') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_tahap` enum('tahap_1','tahap_2','sekaligus','khusus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tahap I (50%), Sekaligus (100%), dst',
  `batas_pengajuan` date NOT NULL COMMENT 'Batas submit dari OPD ke Inspektorat',
  `batas_penyaluran` date NOT NULL COMMENT 'Batas transfer dari Provinsi ke Kab/Kota',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bw` (`tahun`,`jenis_penyaluran`,`kode_tahap`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_bw_tahun_jenis` (`tahun`,`jenis_penyaluran`),
  CONSTRAINT `ref_batas_waktu_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ref_batas_waktu_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_batas_waktu_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_batas_waktu_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `batas_waktu_id` int NOT NULL,
  `field_ubah` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'batas_pengajuan atau batas_penyaluran',
  `nilai_lama` date DEFAULT NULL,
  `nilai_baru` date DEFAULT NULL,
  `alasan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `batas_waktu_id` (`batas_waktu_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ref_batas_waktu_log_ibfk_1` FOREIGN KEY (`batas_waktu_id`) REFERENCES `ref_batas_waktu` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ref_batas_waktu_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_bidang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_bidang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_bkp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_bkp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_bkp` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` year NOT NULL,
  `kabkota_id` int NOT NULL,
  `bidang_id` int NOT NULL,
  `uraian_bkp` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` bigint NOT NULL DEFAULT '0',
  `nilai_awal` bigint NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bkp` (`kode_bkp`,`tahun`),
  KEY `bidang_id` (`bidang_id`),
  KEY `idx_refbkp_tahun` (`tahun`),
  KEY `idx_refbkp_kab` (`kabkota_id`),
  CONSTRAINT `ref_bkp_ibfk_1` FOREIGN KEY (`kabkota_id`) REFERENCES `ref_kabkota` (`id`),
  CONSTRAINT `ref_bkp_ibfk_2` FOREIGN KEY (`bidang_id`) REFERENCES `ref_bidang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_bkp_import_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_bkp_import_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` year NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_baris` int DEFAULT '0',
  `total_baru` int DEFAULT '0',
  `total_update` int DEFAULT '0',
  `total_skip` int DEFAULT '0',
  `total_error` int DEFAULT '0',
  `aksi_duplikat` enum('skip','update') COLLATE utf8mb4_unicode_ci DEFAULT 'skip',
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_bkp_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_bkp_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref_bkp_id` int NOT NULL,
  `kode_bkp` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` year NOT NULL,
  `field_ubah` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai_lama` text COLLATE utf8mb4_unicode_ci,
  `nilai_baru` text COLLATE utf8mb4_unicode_ci,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ref_bkp_id` (`ref_bkp_id`),
  CONSTRAINT `ref_bkp_log_ibfk_1` FOREIGN KEY (`ref_bkp_id`) REFERENCES `ref_bkp` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_checklist_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_checklist_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uraian_item` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_penyaluran` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL = berlaku semua jenis',
  `urutan` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_kabkota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_kabkota` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ibukota` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis` enum('kabupaten','kota') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kabupaten',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_landing_pejabat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_landing_pejabat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis` enum('gubernur','wakil_gubernur','sekda','kepala_bkad') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jenis` (`jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_landing_slideshow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_landing_slideshow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `urutan` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_pemda_dokumen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_pemda_dokumen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` year NOT NULL,
  `jenis` enum('perda_apbd','perkada_apbd','perkada_pergeseran','perda_p_apbd','perkada_p_apbd') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kabkota_id` (`kabkota_id`),
  CONSTRAINT `ref_pemda_dokumen_ibfk_1` FOREIGN KEY (`kabkota_id`) REFERENCES `ref_kabkota` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_pemda_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_pemda_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` year NOT NULL,
  `tabel` enum('pejabat','dokumen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `field_ubah` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nilai_lama` text COLLATE utf8mb4_unicode_ci,
  `nilai_baru` text COLLATE utf8mb4_unicode_ci,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kabkota_id` (`kabkota_id`),
  CONSTRAINT `ref_pemda_log_ibfk_1` FOREIGN KEY (`kabkota_id`) REFERENCES `ref_kabkota` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_pemda_pejabat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_pemda_pejabat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kabkota_id` int NOT NULL,
  `tahun` year NOT NULL,
  `jenis` enum('kepala_daerah','kepala_bkad','inspektur') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pangkat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pejabat` (`kabkota_id`,`tahun`,`jenis`),
  CONSTRAINT `ref_pemda_pejabat_ibfk_1` FOREIGN KEY (`kabkota_id`) REFERENCES `ref_kabkota` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_tahun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_tahun` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` year NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahun` (`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `granted_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rp` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  KEY `idx_rp_role` (`role_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `level` int NOT NULL DEFAULT '10',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_bukti_transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_bukti_transfer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `penyaluran_id` int NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_upload` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `penyaluran_id` (`penyaluran_id`),
  KEY `user_upload` (`user_upload`),
  CONSTRAINT `trx_bukti_transfer_ibfk_1` FOREIGN KEY (`penyaluran_id`) REFERENCES `trx_penyaluran_dana` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_bukti_transfer_ibfk_2` FOREIGN KEY (`user_upload`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_capaian_output`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_capaian_output` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `tgl_realisasi` date NOT NULL,
  `persen_fisik` decimal(5,2) NOT NULL,
  `no_ba_kemajuan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_ba_kemajuan` date DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `foto_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ba_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ba_asli` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_foto_asli` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_input` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahapan_id` (`tahapan_id`),
  KEY `user_input` (`user_input`),
  UNIQUE KEY `uq_capaian_tahapan` (`tahapan_id`),
  CONSTRAINT `trx_capaian_output_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_capaian_output_ibfk_2` FOREIGN KEY (`user_input`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_checklist_reviu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_checklist_reviu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reviu_id` int NOT NULL,
  `checklist_item_id` int NOT NULL,
  `nilai` enum('sesuai','tidak_sesuai','tidak_berlaku') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tidak_berlaku',
  `catatan` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ck` (`reviu_id`,`checklist_item_id`),
  KEY `checklist_item_id` (`checklist_item_id`),
  CONSTRAINT `trx_checklist_reviu_ibfk_1` FOREIGN KEY (`reviu_id`) REFERENCES `trx_reviu_inspektorat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_checklist_reviu_ibfk_2` FOREIGN KEY (`checklist_item_id`) REFERENCES `ref_checklist_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_dokumen_persyaratan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_dokumen_persyaratan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `jenis_dokumen` enum('surat_permohonan_pencairan','surat_pernyataan_bupati','dokumen_pekerjaan_kontrak','daftar_pekerjaan','laporan_reviu_inspektorat','ba_kemajuan_pekerjaan','rekapitulasi_kegiatan','bast','kwitansi_sts','foto_dokumentasi','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ukuran_kb` int DEFAULT '0',
  `is_required` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_upload` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahapan_id` (`tahapan_id`),
  KEY `user_upload` (`user_upload`),
  CONSTRAINT `trx_dokumen_persyaratan_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_dokumen_persyaratan_ibfk_2` FOREIGN KEY (`user_upload`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_notifikasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tahapan_id` int DEFAULT NULL,
  `pekerjaan_id` int DEFAULT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('info','sukses','peringatan','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahapan_id` (`tahapan_id`),
  KEY `pekerjaan_id` (`pekerjaan_id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `trx_notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_notifikasi_ibfk_2` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_notifikasi_ibfk_3` FOREIGN KEY (`pekerjaan_id`) REFERENCES `trx_pekerjaan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_pekerjaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_pekerjaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bkp_id` int NOT NULL,
  `jenis_penyaluran` enum('bertahap','sekaligus','khusus_mendesak','khusus_bencana') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kegiatan_dok` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volume_satuan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metode_pelaksanaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pekerjaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_penyedia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_penyedia` text COLLATE utf8mb4_unicode_ci,
  `no_dok_pekerjaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_dok_pekerjaan` date DEFAULT NULL,
  `no_spmk` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_spmk` date DEFAULT NULL,
  `no_bast` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_bast` date DEFAULT NULL,
  `jangka_waktu_hari` int DEFAULT NULL,
  `nilai_kontrak` bigint NOT NULL DEFAULT '0',
  `nilai_belanja_pendukung` bigint NOT NULL DEFAULT '0',
  `lokasi_deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'WGS84 — siap untuk Google Maps API',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'WGS84 — siap untuk Google Maps API',
  `peta_lokasi_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_perda_id` int DEFAULT NULL,
  `ref_perkada_id` int DEFAULT NULL,
  `status` enum('draft','opd_submitted','inspektorat_reviu','inspektorat_revisi','inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi','skpkd_kab_approved','skpkd_prov_verif','skpkd_prov_revisi','disalurkan_tahap1','dikonfirmasi_tahap1','opd_capaian_tahap1','disalurkan_sekaligus','disalurkan_tahap2','selesai','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` int NOT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bkp_id` (`bkp_id`),
  KEY `ref_perda_id` (`ref_perda_id`),
  KEY `ref_perkada_id` (`ref_perkada_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_pekerjaan_bkp` (`bkp_id`),
  KEY `idx_pekerjaan_status` (`status`),
  CONSTRAINT `trx_pekerjaan_ibfk_1` FOREIGN KEY (`bkp_id`) REFERENCES `ref_bkp` (`id`),
  CONSTRAINT `trx_pekerjaan_ibfk_2` FOREIGN KEY (`ref_perda_id`) REFERENCES `ref_pemda_dokumen` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_pekerjaan_ibfk_3` FOREIGN KEY (`ref_perkada_id`) REFERENCES `ref_pemda_dokumen` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_pekerjaan_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `trx_pekerjaan_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_pekerjaan_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_pekerjaan_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pekerjaan_id` int NOT NULL,
  `field_ubah` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai_lama` text COLLATE utf8mb4_unicode_ci,
  `nilai_baru` text COLLATE utf8mb4_unicode_ci,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pekerjaan_id` (`pekerjaan_id`),
  CONSTRAINT `trx_pekerjaan_log_ibfk_1` FOREIGN KEY (`pekerjaan_id`) REFERENCES `trx_pekerjaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_penyaluran_dana`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_penyaluran_dana` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `no_sp2d` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_sp2d` date NOT NULL,
  `nilai_transfer` bigint NOT NULL,
  `rek_asal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RKUD Provinsi',
  `nama_bank_asal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Bank Sumut',
  `rek_tujuan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RKUD Kab/Kota',
  `nama_bank_tujuan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_transfer` enum('proses','selesai','gagal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proses',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_input` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahapan_id` (`tahapan_id`),
  KEY `user_input` (`user_input`),
  CONSTRAINT `trx_penyaluran_dana_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_penyaluran_dana_ibfk_2` FOREIGN KEY (`user_input`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_reviu_inspektorat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_reviu_inspektorat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `tgl_reviu_mulai` date DEFAULT NULL,
  `tgl_reviu_selesai` date DEFAULT NULL,
  `hasil_reviu` enum('disetujui','ditolak','perlu_perbaikan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `checklist_confirmed_at` datetime DEFAULT NULL,
  `reviewer_nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewer_nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewer_jabatan` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_lhr` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_lhr` date DEFAULT NULL,
  `file_lhr_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_inspektur_id` int DEFAULT NULL,
  `user_inspektorat` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahapan_id` (`tahapan_id`),
  KEY `ref_inspektur_id` (`ref_inspektur_id`),
  KEY `user_inspektorat` (`user_inspektorat`),
  CONSTRAINT `trx_reviu_inspektorat_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_reviu_inspektorat_ibfk_2` FOREIGN KEY (`ref_inspektur_id`) REFERENCES `ref_pemda_pejabat` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_reviu_inspektorat_ibfk_3` FOREIGN KEY (`user_inspektorat`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pekerjaan_id` int NOT NULL,
  `tahapan_id` int DEFAULT NULL,
  `status_lama` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_baru` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pekerjaan_id` (`pekerjaan_id`),
  KEY `tahapan_id` (`tahapan_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trx_status_history_ibfk_1` FOREIGN KEY (`pekerjaan_id`) REFERENCES `trx_pekerjaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_status_history_ibfk_2` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_status_history_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_tahapan_penyaluran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_tahapan_penyaluran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pekerjaan_id` int NOT NULL,
  `batas_waktu_id` int DEFAULT NULL COMMENT 'FK → ref_batas_waktu (snapshot batas waktu saat submit)',
  `kode_tahap` enum('tahap_1','tahap_2','sekaligus','khusus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` tinyint NOT NULL DEFAULT '1',
  `label_tahap` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `persen_nilai` decimal(5,2) NOT NULL DEFAULT '50.00',
  `nilai_diajukan` bigint NOT NULL DEFAULT '0',
  `nilai_disetujui` bigint DEFAULT NULL,
  `persen_fisik_syarat` decimal(5,2) DEFAULT NULL,
  `persen_fisik_capaian` decimal(5,2) DEFAULT NULL,
  `batas_tgl_pengajuan` date NOT NULL COMMENT 'Disalin dari ref_batas_waktu.batas_pengajuan saat submit',
  `tgl_pengajuan` date DEFAULT NULL,
  `tgl_disalurkan` date DEFAULT NULL,
  `status` enum('belum','opd_input','inspektorat_reviu','inspektorat_revisi','inspektorat_approved','skpkd_kab_verif','skpkd_kab_revisi','skpkd_kab_approved','skpkd_prov_verif','skpkd_prov_revisi','disalurkan','dikonfirmasi','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `user_input` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tahapan` (`pekerjaan_id`,`kode_tahap`),
  KEY `batas_waktu_id` (`batas_waktu_id`),
  KEY `user_input` (`user_input`),
  KEY `idx_tahapan_pekerjaan` (`pekerjaan_id`),
  KEY `idx_tahapan_status` (`status`),
  CONSTRAINT `trx_tahapan_penyaluran_ibfk_1` FOREIGN KEY (`pekerjaan_id`) REFERENCES `trx_pekerjaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_tahapan_penyaluran_ibfk_2` FOREIGN KEY (`batas_waktu_id`) REFERENCES `ref_batas_waktu` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_tahapan_penyaluran_ibfk_3` FOREIGN KEY (`user_input`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_verifikasi_skpkd_kab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_verifikasi_skpkd_kab` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `hasil_verifikasi` enum('disetujui','ditolak','perlu_perbaikan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tgl_verifikasi` date DEFAULT NULL,
  `no_surat_verif` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_ppkd_id` int DEFAULT NULL,
  `user_skpkd` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahapan_id` (`tahapan_id`),
  KEY `ref_ppkd_id` (`ref_ppkd_id`),
  KEY `user_skpkd` (`user_skpkd`),
  CONSTRAINT `trx_verifikasi_skpkd_kab_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_verifikasi_skpkd_kab_ibfk_2` FOREIGN KEY (`ref_ppkd_id`) REFERENCES `ref_pemda_pejabat` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trx_verifikasi_skpkd_kab_ibfk_3` FOREIGN KEY (`user_skpkd`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trx_verifikasi_skpkd_prov`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trx_verifikasi_skpkd_prov` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahapan_id` int NOT NULL,
  `hasil_verifikasi` enum('disetujui','ditolak','perlu_perbaikan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tgl_verifikasi` date DEFAULT NULL,
  `user_admin_prov` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahapan_id` (`tahapan_id`),
  KEY `user_admin_prov` (`user_admin_prov`),
  CONSTRAINT `trx_verifikasi_skpkd_prov_ibfk_1` FOREIGN KEY (`tahapan_id`) REFERENCES `trx_tahapan_penyaluran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trx_verifikasi_skpkd_prov_ibfk_2` FOREIGN KEY (`user_admin_prov`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `aksi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Jumlah percobaan login gagal berturut-turut',
  `locked_at` datetime DEFAULT NULL COMMENT 'Waktu akun dikunci otomatis (5x gagal login)',
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'NIP 18 digit — wajib unik',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int NOT NULL,
  `kabkota_id` int DEFAULT NULL,
  `instansi_jenis` enum('bkad_provinsi','skpkd_kabkota','inspektorat','opd_teknis','lainnya') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opd_nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama OPD/Dinas (jika opd_teknis)',
  `telegram_chat_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uniq_users_nip` (`nip`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_kabkota` (`kabkota_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`kabkota_id`) REFERENCES `ref_kabkota` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
-- MySQL dump 10.13  Distrib 9.6.0, for macos26.2 (arm64)
--
-- Host: localhost    Database: siberkah_sumut
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '448bbbb8-00cd-11f1-9d50-42a88396694b:1-250';

-- Setting default (nilai dikonfigurasi lewat UI atau env var)
INSERT IGNORE INTO ref_app_setting (kode, nilai, keterangan) VALUES
('telegram_bot_token', '', 'Token Bot Telegram — isi via menu Pengaturan > Notif Telegram'),
('logo_provinsi', '', 'Path file logo Pemerintah Provinsi — upload via Parameter > Logo Provinsi');
