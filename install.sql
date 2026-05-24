-- ============================================================
-- YUMMYSPOT — Database Installer
-- Sesuai laporan: Institut Teknologi dan Informatika (INSTIKI)
-- ============================================================
CREATE DATABASE IF NOT EXISTS yummyspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yummyspot;

-- USERS
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CATEGORIES
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    icon       VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CATALOGS
CREATE TABLE IF NOT EXISTS catalogs (
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
    verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    avg_rating          DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_reviews       INT UNSIGNED NOT NULL DEFAULT 0,
    total_likes         INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id)    REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_status (verification_status),
    INDEX idx_city   (city),
    FULLTEXT INDEX ft_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CATALOG IMAGES
CREATE TABLE IF NOT EXISTS catalog_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id INT NOT NULL,
    image      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CATALOG VERIFICATIONS
CREATE TABLE IF NOT EXISTS catalog_verifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id INT NOT NULL,
    cs_id      INT NOT NULL,
    status     ENUM('approved','rejected') NOT NULL,
    note       TEXT DEFAULT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
    FOREIGN KEY (cs_id)      REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WISHLISTS
CREATE TABLE IF NOT EXISTS wishlists (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    catalog_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (user_id, catalog_id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FOLLOWS
CREATE TABLE IF NOT EXISTS follows (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    follower_id  INT NOT NULL,
    following_id INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_follow (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- POSTS
CREATE TABLE IF NOT EXISTS posts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- LIKES
CREATE TABLE IF NOT EXISTS likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_like (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- COMMENTS
CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RATINGS
CREATE TABLE IF NOT EXISTS ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    catalog_id INT NOT NULL,
    rating     INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review     TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating (user_id, catalog_id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- REPORTS
CREATE TABLE IF NOT EXISTS reports (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id         INT NOT NULL,
    reported_post_id    INT DEFAULT NULL,
    reported_catalog_id INT DEFAULT NULL,
    report_type         ENUM('bug','spam','fake','inappropriate') NOT NULL,
    description         TEXT,
    status              ENUM('pending','process','done') NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id)         REFERENCES users(id),
    FOREIGN KEY (reported_post_id)    REFERENCES posts(id)    ON DELETE SET NULL,
    FOREIGN KEY (reported_catalog_id) REFERENCES catalogs(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    type        ENUM('like','comment','follow','review','verified','rejected','report') NOT NULL,
    target_id   INT DEFAULT NULL,
    message     VARCHAR(255) NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
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

-- Demo users (password: password123)
INSERT IGNORE INTO users (fullname, username, email, password, role, status, bio) VALUES
('Super Admin',    'superadmin', 'admin@yummyspot.id',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  'active', 'Administrator YummySpot'),
('CS Team',        'cs_team',    'cs@yummyspot.id',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs',     'active', 'Tim Customer Service'),
('Ahmad Owner',    'ahmadowner', 'owner@yummyspot.id',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',  'active', 'Pemilik Kafe Senja'),
('Rizky User',     'rizkyf',     'user@yummyspot.id',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'Suka jalan-jalan dan kuliner!'),
('Sari Dewi',      'saridewi',   'sari@yummyspot.id',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'Traveler & food lover');

-- Sample catalogs
INSERT IGNORE INTO catalogs (owner_id, category_id, name, slug, description, address, city, contact, open_time, close_time, verification_status, avg_rating, total_reviews, total_likes) VALUES
(3, 3, 'Kafe Senja Bali',   'kafe-senja-bali',   'Kafe cozy dengan pemandangan sunset terbaik di Seminyak.', 'Jl. Sunset Road No. 45', 'Seminyak', '08123456789', '08:00:00', '22:00:00', 'approved', 4.80, 32, 210),
(3, 1, 'Warung Bu Tini',    'warung-bu-tini',    'Nasi campur legendaris sejak 1985.', 'Jl. Raya Kuta No. 12', 'Kuta', '08987654321', '07:00:00', '21:00:00', 'approved', 4.60, 128, 543),
(3, 2, 'Tegallalang Rice Terrace', 'tegallalang', 'Sawah terasering ikonik di Ubud.', 'Jl. Raya Tegallalang', 'Ubud', '08111222333', '07:00:00', '18:00:00', 'pending',  0.00, 0,   0);

-- Sample posts
INSERT IGNORE INTO posts (user_id, catalog_id, caption, post_type, status) VALUES
(4, 1, 'Sore yang sempurna di Kafe Senja! Kopinya enak banget, viewnya juara ☕', 'user', 'published'),
(5, 2, 'Nasi campur Bu Tini selalu juara, udah langganan 3 tahun!', 'user', 'published'),
(3, 1, 'Menu baru hadir! Cold brew dengan susu oat lokal. Yuk mampir!', 'business', 'published');

-- Sample ratings
INSERT IGNORE INTO ratings (user_id, catalog_id, rating, review) VALUES
(4, 1, 5, 'Tempatnya keren banget! Pasti balik lagi.'),
(5, 1, 5, 'View sunset-nya luar biasa, kopi enak, harga wajar.'),
(4, 2, 4, 'Nasi campurnya lezat tapi kadang rame banget.');

-- Sample follows
INSERT IGNORE INTO follows (follower_id, following_id) VALUES (4, 5), (5, 4), (4, 3);

-- Sample likes
INSERT IGNORE INTO likes (user_id, post_id) VALUES (4, 2), (5, 1), (5, 3);

-- Sample wishlist
INSERT IGNORE INTO wishlists (user_id, catalog_id) VALUES (4, 2), (5, 1);
