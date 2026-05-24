-- ============================================================
-- YUMMYSPOT — Migration: Tambah Soft Delete
-- Jalankan di phpMyAdmin Query tab atau terminal MySQL
-- Aman dijalankan berkali-kali (IF NOT EXISTS / IF EXISTS)
-- ============================================================

USE yummyspot;

-- Tambah kolom deleted_at pada tabel catalogs
ALTER TABLE catalogs
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Tambah index agar query filter deleted_at cepat
ALTER TABLE catalogs
    ADD INDEX IF NOT EXISTS idx_deleted_at (deleted_at);

SELECT 'Migration selesai!' AS status;
SELECT
    COUNT(*) AS total_catalogs,
    SUM(deleted_at IS NOT NULL) AS total_deleted,
    SUM(deleted_at IS NULL)     AS total_active
FROM catalogs;
