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
-- JALANKAN SEKALI, satu per satu. Jika muncul "Duplicate key name"
-- berarti constraint sudah ada — skip.
-- ============================================================

ALTER TABLE trx_reviu_inspektorat
  ADD UNIQUE KEY uq_reviu_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_kab
  ADD UNIQUE KEY uq_verif_kab_tahapan (tahapan_id);

ALTER TABLE trx_verifikasi_skpkd_prov
  ADD UNIQUE KEY uq_verif_prov_tahapan (tahapan_id);

ALTER TABLE trx_penyaluran_dana
  ADD UNIQUE KEY uq_penyaluran_tahapan (tahapan_id);

-- trx_capaian_output juga menggunakan pola upsert 1:1 per tahapan
-- (Capaian_model::simpan) tapi belum punya UNIQUE KEY — tambahkan
-- di sini agar konsisten.
ALTER TABLE trx_capaian_output
  ADD UNIQUE KEY uq_capaian_tahapan (tahapan_id);
