-- ================================================================
-- reviu_checklist_lock_migration.sql
-- Tambah kolom untuk mengunci checklist setelah dikonfirmasi
-- ================================================================
ALTER TABLE trx_reviu_inspektorat
  ADD COLUMN IF NOT EXISTS checklist_confirmed_at DATETIME NULL
    COMMENT 'Waktu checklist dikunci/dikonfirmasi Inspektorat — NULL = masih bisa diedit'
  AFTER catatan;
