-- ============================================================
-- YUMMYSPOT — Reset & Fresh Seed
-- Jalankan di phpMyAdmin atau: mysql -u root -p yummyspot < reset.sql
-- ============================================================

USE yummyspot;

-- Matikan foreign key check sementara
SET FOREIGN_KEY_CHECKS = 0;

-- Kosongkan semua tabel (urutan dari child ke parent)
TRUNCATE TABLE moderation_logs;
TRUNCATE TABLE catalog_verifications;
TRUNCATE TABLE reports;
TRUNCATE TABLE notifications;
TRUNCATE TABLE wishlists;
TRUNCATE TABLE ratings;
TRUNCATE TABLE comments;
TRUNCATE TABLE likes;
TRUNCATE TABLE posts;
TRUNCATE TABLE catalog_images;
TRUNCATE TABLE catalog_tags;
TRUNCATE TABLE tags;
TRUNCATE TABLE catalogs;
TRUNCATE TABLE follows;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FRESH USERS
-- Semua password: password123
-- Hash bcrypt cost 12 untuk "password123"
-- ============================================================

INSERT INTO users (fullname, username, email, password, role, status, bio) VALUES
(
  'Super Admin',
  'superadmin',
  'superadmin@yummyspot.com',
  '$2y$12$KIXBNwEd3e0RiVYs1RxFTucNRfzlI.VU2VNHjolVaWN.2UiMPLTIu',
  'admin',
  'active',
  'Super Administrator YummySpot'
),
(
  'CS Team',
  'cs_yummyspot',
  'cs@yummyspot.com',
  '$2y$12$KIXBNwEd3e0RiVYs1RxFTucNRfzlI.VU2VNHjolVaWN.2UiMPLTIu',
  'cs',
  'active',
  'Tim Customer Service YummySpot'
);

-- ============================================================
-- CATEGORIES (tetap sama)
-- ============================================================
INSERT IGNORE INTO categories (name, icon) VALUES
('Kuliner',     'fa-utensils'),
('Wisata Alam', 'fa-mountain'),
('Kafe',        'fa-mug-hot'),
('Pantai',      'fa-umbrella-beach'),
('Budaya',      'fa-landmark'),
('Hiburan',     'fa-masks-theater'),
('Hotel',       'fa-hotel'),
('Belanja',     'fa-bag-shopping');

SELECT 'Database berhasil direset!' AS status;
SELECT id, fullname, email, role FROM users;

-- Tambah kolom deleted_at untuk soft delete katalog
-- Jalankan ini jika database sudah ada (tidak perlu reset ulang)
ALTER TABLE catalogs ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE posts    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;
