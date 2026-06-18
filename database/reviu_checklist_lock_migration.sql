-- ================================================================
-- reviu_checklist_lock_migration.sql
-- Tambah kolom untuk mengunci checklist setelah dikonfirmasi
-- ================================================================
-- Jika muncul "Duplicate column name" berarti kolom sudah ada — skip.
ALTER TABLE trx_reviu_inspektorat
  ADD COLUMN checklist_confirmed_at DATETIME NULL
  COMMENT 'Waktu checklist dikunci/dikonfirmasi Inspektorat';
