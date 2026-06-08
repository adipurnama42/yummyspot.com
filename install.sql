-- ============================================================
-- YUMMYSPOT — Fresh Install SQL (Server)
-- Database: yummyspot
-- Jalankan di phpMyAdmin → tab SQL
-- ⚠ HAPUS SEMUA DATA LAMA — tidak bisa dikembalikan!
-- ============================================================

-- Gunakan database yang sudah ada di server
USE yummyspot;

-- ── Hapus semua tabel (urutan foreign key) ────────────────
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS catalog_verifications;
DROP TABLE IF EXISTS catalog_images;
DROP TABLE IF EXISTS catalogs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── USERS ─────────────────────────────────────────────────
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    fullname        VARCHAR(100) NOT NULL,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    bio             TEXT         DEFAULT NULL,
    role            ENUM('user','owner','cs','admin') NOT NULL DEFAULT 'user',
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role   (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATEGORIES ────────────────────────────────────────────
CREATE TABLE categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    icon       VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATALOGS ──────────────────────────────────────────────
CREATE TABLE catalogs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    owner_id            INT NOT NULL,
    category_id         INT NOT NULL,
    name                VARCHAR(100) NOT NULL,
    slug                VARCHAR(150) NOT NULL UNIQUE,
    description         TEXT,
    address             TEXT NOT NULL,
    city                VARCHAR(100) NOT NULL,
    contact             VARCHAR(50)  DEFAULT NULL,
    thumbnail           VARCHAR(255) DEFAULT NULL,
    open_time           TIME         DEFAULT NULL,
    close_time          TIME         DEFAULT NULL,
    latitude            DECIMAL(10,8) DEFAULT NULL,
    longitude           DECIMAL(11,8) DEFAULT NULL,
    verification_status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
    avg_rating          DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_reviews       INT UNSIGNED NOT NULL DEFAULT 0,
    total_likes         INT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at          TIMESTAMP NULL DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id)    REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_status     (verification_status),
    INDEX idx_city       (city),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATALOG IMAGES ────────────────────────────────────────
CREATE TABLE catalog_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id INT NOT NULL,
    image      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATALOG VERIFICATIONS ─────────────────────────────────
CREATE TABLE catalog_verifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id  INT NOT NULL,
    cs_id       INT NOT NULL,
    status      ENUM('approved','rejected') NOT NULL,
    note        TEXT DEFAULT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
    FOREIGN KEY (cs_id)      REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── WISHLISTS ─────────────────────────────────────────────
CREATE TABLE wishlists (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    catalog_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (user_id, catalog_id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── FOLLOWS ───────────────────────────────────────────────
CREATE TABLE follows (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    follower_id  INT NOT NULL,
    following_id INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_follow (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── POSTS ─────────────────────────────────────────────────
CREATE TABLE posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    catalog_id INT DEFAULT NULL,
    caption    TEXT,
    image      VARCHAR(255) DEFAULT NULL,
    post_type  ENUM('user','business') NOT NULL DEFAULT 'user',
    status     ENUM('published','removed') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE SET NULL,
    INDEX idx_status  (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── LIKES ─────────────────────────────────────────────────
CREATE TABLE likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_like (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── COMMENTS ──────────────────────────────────────────────
CREATE TABLE comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── RATINGS ───────────────────────────────────────────────
CREATE TABLE ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    catalog_id INT NOT NULL,
    rating     INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review     TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating (user_id, catalog_id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── REPORTS ───────────────────────────────────────────────
CREATE TABLE reports (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id         INT NULL,
    reported_post_id    INT DEFAULT NULL,
    reported_catalog_id INT DEFAULT NULL,
    report_type         ENUM('bug','spam','fake','inappropriate','saran','konten','akun','lainnya') NOT NULL,
    description         TEXT,
    status              ENUM('pending','process','done') NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id)         REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_post_id)    REFERENCES posts(id)    ON DELETE SET NULL,
    FOREIGN KEY (reported_catalog_id) REFERENCES catalogs(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── NOTIFICATIONS ─────────────────────────────────────────
CREATE TABLE notifications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    type         ENUM('like','comment','follow','review','verified','rejected','report') NOT NULL,
    target_id    INT DEFAULT NULL,
    message      VARCHAR(255) NOT NULL,
    is_read      TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Kategori (8 kategori, tidak duplikat karena ada UNIQUE)
INSERT INTO categories (name, icon) VALUES
('Kuliner',     'fa-utensils'),
('Wisata Alam', 'fa-mountain'),
('Kafe',        'fa-mug-saucer'),
('Pantai',      'fa-water'),
('Budaya',      'fa-landmark'),
('Hiburan',     'fa-star'),
('Hotel',       'fa-hotel'),
('Belanja',     'fa-cart-shopping');

-- Akun sistem (password: password123)
-- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (fullname, username, email, password, role, status, bio) VALUES
('Super Admin', 'superadmin', 'superadmin@yummyspot.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'Administrator YummySpot'),
('CS Team',     'cs_team',   'cs@yummyspot.com',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs',    'active', 'Tim Customer Service');

-- Verifikasi
SELECT 'Tabel berhasil dibuat!' AS status;
SELECT 'Categories:' AS info; SELECT id, name, icon FROM categories;
SELECT 'Users:' AS info; SELECT id, fullname, email, role FROM users;