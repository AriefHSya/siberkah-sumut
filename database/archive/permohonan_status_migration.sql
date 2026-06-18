-- ============================================================
-- permohonan_status_migration.sql
-- Tambah status 'batal' (dibatalkan oleh SKPKD Kab/Kota) dan
-- 'ditolak' (ditolak oleh BKAD Provinsi) pada trx_permohonan,
-- beserta kolom catatan_tolak untuk alasan penolakan.
--
-- Riwayat permohonan yang batal/ditolak TETAP disimpan
-- (beserta trx_permohonan_item-nya) sebagai log; tahapan yang
-- dikandungnya menjadi eligible kembali untuk permohonan baru
-- karena get_eligible()/get_kelompok_tersedia() hanya
-- mengecualikan tahapan yang masih terikat ke permohonan
-- berstatus 'draft' atau 'diajukan'.
--
-- JALANKAN SEKALI di database sebelum deploy fitur ini.
-- ============================================================

ALTER TABLE trx_permohonan
  MODIFY COLUMN status enum('draft','diajukan','batal','ditolak','selesai') NOT NULL DEFAULT 'draft';

ALTER TABLE trx_permohonan
  ADD COLUMN catatan_tolak text AFTER catatan;
