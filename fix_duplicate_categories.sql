-- ============================================================
-- YUMMYSPOT — Fix Duplikat Kategori
-- Jalankan sekali di phpMyAdmin Query tab
-- ============================================================

USE yummyspot;

-- Langkah 1: Hapus duplikat, pertahankan ID terkecil per nama
DELETE c1 FROM categories c1
INNER JOIN categories c2
WHERE c1.id > c2.id AND c1.name = c2.name;

-- Langkah 2: Tambah UNIQUE constraint agar tidak bisa duplikat lagi
ALTER TABLE categories
    ADD UNIQUE INDEX IF NOT EXISTS uq_category_name (name);

-- Verifikasi
SELECT id, name, icon FROM categories ORDER BY id;
