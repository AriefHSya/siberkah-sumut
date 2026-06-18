-- Tambah kolom data reviewer di trx_reviu_inspektorat
-- Jalankan satu per satu, skip jika sudah ada (Duplicate column name)
ALTER TABLE trx_reviu_inspektorat ADD COLUMN reviewer_nama    VARCHAR(150) NULL AFTER checklist_confirmed_at;
ALTER TABLE trx_reviu_inspektorat ADD COLUMN reviewer_nip     VARCHAR(30)  NULL AFTER reviewer_nama;
ALTER TABLE trx_reviu_inspektorat ADD COLUMN reviewer_jabatan VARCHAR(150) NULL AFTER reviewer_nip;
