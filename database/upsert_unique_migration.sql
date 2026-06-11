-- ============================================================
-- upsert_unique_migration.sql
-- Defensif: pastikan UNIQUE KEY tahapan_id ada pada tabel-tabel
-- yang menggunakan pola buat_atau_ambil() (cek-lalu-insert),
-- agar race condition antar request tidak menghasilkan duplikat
-- record 1:1 per tahapan.
--
-- schema.sql versi terbaru SUDAH mendefinisikan UNIQUE KEY ini.
-- Migration ini hanya jaring pengaman untuk database lama yang
-- dibuat sebelum constraint tersebut ditambahkan.
--
-- JALANKAN SEKALI. Menggunakan ADD UNIQUE IF NOT EXISTS — aman
-- jika dijalankan ulang (MariaDB 10.0.2+ / MySQL 8.0.29+).
-- ============================================================

ALTER TABLE trx_reviu_inspektorat
  ADD UNIQUE IF NOT EXISTS uq_reviu_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_kab
  ADD UNIQUE IF NOT EXISTS uq_verif_kab_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_prov
  ADD UNIQUE IF NOT EXISTS uq_verif_prov_tahapan (tahapan_id);

ALTER TABLE trx_penyaluran_dana
  ADD UNIQUE IF NOT EXISTS uq_penyaluran_tahapan (tahapan_id);
