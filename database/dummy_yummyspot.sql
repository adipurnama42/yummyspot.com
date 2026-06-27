
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `dummy_yummyspot`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `catalogs`
--

CREATE TABLE `catalogs` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `verification_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_reviews` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `catalogs`
--

INSERT INTO `catalogs` (`id`, `owner_id`, `category_id`, `name`, `slug`, `description`, `address`, `city`, `contact`, `thumbnail`, `open_time`, `close_time`, `latitude`, `longitude`, `verification_status`, `avg_rating`, `total_reviews`, `total_likes`, `created_at`, `deleted_at`) VALUES
(1, 3, 1, 'Caro Smoothies', 'caro-smoothies', 'Fresh. Simple. Creamy', 'Gatot Subroto Barat No. VA', 'Denpasar', '085739832726', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a2a4f5e9c3d85.49115899.png', '08:00:00', '16:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-05-22 16:38:20', NULL),
(2, 6, 2, 'Pantai Nunggalan', 'pantai-nunggalan', '', 'Jalan Batu Nunggalan, Desa Pecatu, Kecamatan Kuta Selatan, Kabupaten Badung,', 'Uluwatu', '081529942097', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a143bd1e382d3.31555990.jpg', '08:00:00', '20:00:00', NULL, NULL, 'approved', 4.00, 1, 0, '2026-05-25 12:08:49', NULL),
(3, 10, 3, 'caffe glory', 'caffe-glory', 'caffe', 'instiki', 'denpasar', '+6281237518165', NULL, '08:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-08 10:34:15', NULL),
(4, 11, 2, 'hutan pinus', 'hutan-pinus', 'hutan pinus', 'karangasem', 'amlapura', '', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a269f935e66a6.86941248.jpg', '08:00:00', '22:00:00', NULL, NULL, 'approved', 5.00, 1, 0, '2026-06-08 10:55:15', NULL),
(5, 10, 2, 'Pantai Suluban', 'pantai-suluban', 'ini pantai bagus untuk berselancar dan bagus untuk sunset', 'Desa Pecatu, Kecamatan Kuta Selatan, Kabupaten Badung, Bali', 'Badung', '081237518165', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a27fe2d5b81a1.27525991.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-09 11:51:09', NULL),
(6, 10, 3, 'nataraka coffe', 'nataraka-coffe', 'Ini adalah tempat coffe shop yang menyatu dengan alam', 'Jalan Dukuh I No.10, Kesiman Petilan, Denpasar Timur, Bali.', 'Denpasar', '081237518165', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a27ffb2381016.05910360.jpeg', '10:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-09 11:57:38', NULL),
(7, 10, 7, 'The Yani Hotel', 'the-yani-hotel', 'The Yani Hotel Bali adalah hotel bujet bergaya tradisional-modern yang terletak strategis di Jalan By Pass Ngurah Rai', 'Jl. By Pass Ngurah Rai No. 660, Pemogan, Denpasar Selatan, Bali.', 'Badung', '088567654', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a28011060c7b4.20648958.jpg', '08:00:00', '23:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-09 12:03:28', NULL),
(8, 10, 5, 'Desa Adat Penglipuran', 'desa-adat-penglipuran', 'Penglipuran adalah salah satu desa adat dari Kabupaten Bangli, Provinsi Bali, Indonesia. Desa ini terkenal sebagai salah satu destinasi wisata di Bali karena masyarakatnya yang masih menjalankan dan melestarikan budaya tradisional Bali dalam kehidupan mereka sehari-hari.', 'Kelurahan Kubu, Kecamatan Bangli, Kabupaten Bangli, Provinsi Bali', 'Bangli', '0888889999', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a280229159899.82717523.jpg', '08:00:00', '20:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-09 12:08:09', NULL),
(9, 10, 8, 'Pasar Seni Ubud', 'pasar-seni-ubud', 'Pasar Seni Ubud adalah pusat kerajinan tangan dan suvenir ikonik di jantung pariwisata Bali', 'Jalan Raya Ubud No.35, Ubud, Kabupaten Gianyar, Bali.', 'gianyar', '081765476', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a2802b6b1e0f2.01614360.jpg', '08:00:00', '20:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-09 12:10:30', NULL),
(10, 11, 2, 'mount batur kintamani', 'mount-batur-kintamani', 'Gunung Batur adalah gunung berapi aktif di Bali dengan ketinggian 1.717 meter, terkenal dengan kaldera besar dan Danau Batur di dalamnya.\r\nLokasi dan Ketinggian\r\nGunung Batur terletak di Desa Batur, Kecamatan Kintamani, Kabupaten Bangli, Bali, Indonesia. Gunung ini memiliki ketinggian sekitar 1.717 meter di atas permukaan laut, menjadikannya gunung tertinggi kedua di Bali setelah Gunung Agung', 'kintamani', 'kintamani', '0812345677', 'http://yummy.caro-studio.com/uploads/catalogs/img_6a2a22487557d1.23516371.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-09 12:10:55', NULL),
(11, 11, 2, 'kawah ijen', 'kawah-ijen', 'Kawah Ijen adalah sebuah danau berwarna hijau toska yang bersifat asam dan terletak di puncak Gunung Ijen, di perbatasan Kabupaten Banyuwangi dan Bondowoso, Jawa Timur. Kawah ini terkenal dengan fenomena blue fire yang langka dan merupakan danau kawah terbesar dan terindah di dunia, dengan kedalaman sekitar 200 meter dan luas mencapai 5.466 hektar', 'banyuwangi,', 'mataram', '0812345677', 'http://yummy.caro-studio.com/uploads/catalogs/img_6a2a22aae47c53.21828795.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-09 12:13:57', NULL),
(12, 10, 1, 'Warung Joglo Tepi Sawah Prasmanan', 'warung-joglo-tepi-sawah-prasmanan', 'tempat makan bergaya pedesaan di tengah Kota Denpasar, Bali, yang menyajikan hidangan rumahan halal khas Jawa dan Sunda dengan sistem prasmanan', 'Jl. Teuku Umar Barat No. 351, Padangsambian, Denpasar Barat (Area sekitar Marlboro).', 'Denpasar', '087675432', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a28047de758d0.38429457.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-09 12:18:05', NULL),
(13, 11, 2, 'ruteng', 'ruteng', 'Ruteng adalah ibu kota dari Ruteng yang terletak di Pulau Flores, Indonesia bagian timur. Kota ini berada di dataran tinggi sekitar 1.100–1.200 meter di atas permukaan laut, sehingga udaranya relatif sejuk dibandingkan banyak daerah lain di Nusa Tenggara.', 'ruteng ntt', 'ruteng', '', 'http://yummy.caro-studio.com/uploads/catalogs/img_6a2a2448accc32.34147926.webp', '08:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-11 02:58:16', NULL),
(14, 11, 4, 'pantai pererenan', 'pantai-pererenan', 'Pantai Pererenan adalah pantai di wilayah Pererenan, Badung, Bali, yang terkenal dengan pasir hitam, ombak yang cocok untuk berselancar, serta suasana yang lebih tenang untuk menikmati pemandangan laut dan matahari terbenam.', 'jln prrenana , badung bali', 'badung', '', 'http://yummy.caro-studio.com/uploads/catalogs/img_6a2a24d7598c34.56056659.jpg', '08:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-11 03:00:39', NULL),
(15, 11, 4, 'pantai batu bolong', 'pantai-batu-bolong', 'Pantai Batu Bolong adalah salah satu pantai paling terkenal di kawasan Canggu, Bali. Pantai ini dikenal karena suasananya yang santai, ombak yang cocok untuk berselancar, dan pemandangan matahari terbenam yang indah. Nama \"Batu Bolong\" berasal dari batu karang berlubang yang menjadi ciri khas kawasan tersebut', 'jln, batu bolong, canggu', 'badung', '', 'http://yummy.caro-studio.com/uploads/catalogs/img_6a2a2539003d05.23490203.jpg', '08:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-11 03:02:17', NULL),
(16, 11, 6, 'disney land', 'disney-land', 'Disneyland adalah taman hiburan ikonik yang pertama kali dibuka pada tahun 1955 di Anaheim, California,', 'Disneyland adalah taman hiburan ikonik yang pertama kali dibuka pada tahun 1955 di Anaheim, California,', 'california', '', NULL, '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 08:10:54', NULL),
(17, 11, 2, 'pantai pererenan', 'pantai-pererenan-6a3906d946b9d', 'salah satu pantai dengan patung gajah mina yang indah dan iconik dengan sunsetnya', 'jln raya prerenan', 'badung', '', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a3906d946bbe6.94883433.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 09:56:41', NULL),
(18, 11, 2, 'ranu kumbolo', 'ranu-kumbolo', 'salah satu danau yang iconik gnung semeru', 'semeru', 'jawa timur', '', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a3907a03d5a39.40398333.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:00:00', NULL),
(19, 11, 2, 'segara anaka rinjani', 'segara-anaka-rinjani', 'gunung tercantik seindonesia', 'jln  raya sembalun,lombok,', 'mataram lombok', '', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a3908efd3a496.35547286.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:05:35', NULL),
(20, 14, 5, 'Wisata Tari Kecak', 'wisata-tari-kecak', 'Tempat wisata budaya yang menampilkan tarian bali.', 'Uluwatu', 'Badung', '0896734718', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a39093d2f5e20.67322911.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:06:53', NULL),
(21, 14, 5, 'Wisata Tari Kecak', 'wisata-tari-kecak-6a39093f84a95', 'Tempat wisata budaya yang menampilkan tarian bali.', 'Uluwatu', 'Badung', '0896734718', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a39093f84a9c7.61218963.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:06:55', NULL),
(22, 14, 5, 'GWK', 'gwk', '', 'Jimbaran', 'Badung', '08318399423', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a39099866c9a7.25561303.jpg', '08:00:00', '22:00:00', NULL, NULL, 'approved', 0.00, 0, 0, '2026-06-22 10:08:24', NULL),
(23, 14, 2, 'Nusa Penida', 'nusa-penida', 'Tempat wisata alam yang menyajikan pemandangan pantai aestetic', 'Nusa Penida', 'Klungkung', '08936162772', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a390a8215c755.44942397.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:12:18', NULL),
(24, 11, 6, 'swiss', 'swiss', 'negara impian semua orang dengan pemandangn  gunung  yang sangat indah dan sejuk', 'swiss', 'swiss', '', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a390b44805db5.10039899.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:15:32', NULL),
(25, 14, 3, 'Cafe Bacara', 'cafe-bacara', 'Caffe yang menyajikan makanan dan minuman yang enak, serta interior caffe yang aestetic', 'Seminyak', 'Badung', '0836829123', 'https://yummy.caro-studio.com/uploads/catalogs/img_6a390b48c87eb8.30622690.jpg', '08:00:00', '22:00:00', NULL, NULL, 'pending', 0.00, 0, 0, '2026-06-22 10:15:36', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `catalog_images`
--

CREATE TABLE `catalog_images` (
  `id` int(11) NOT NULL,
  `catalog_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `catalog_verifications`
--

CREATE TABLE `catalog_verifications` (
  `id` int(11) NOT NULL,
  `catalog_id` int(11) NOT NULL,
  `cs_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `note` text DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `catalog_verifications`
--

INSERT INTO `catalog_verifications` (`id`, `catalog_id`, `cs_id`, `status`, `note`, `verified_at`) VALUES
(1, 1, 2, 'approved', 'Orang dalamnya Admin', '2026-05-22 16:39:17'),
(2, 2, 2, 'approved', 'Disetujui oleh CS', '2026-05-25 13:57:19'),
(3, 3, 2, 'approved', 'Disetujui oleh CS', '2026-06-09 11:43:36'),
(4, 4, 2, 'approved', 'Disetujui oleh CS', '2026-06-09 11:43:44'),
(5, 15, 2, 'approved', 'Disetujui oleh CS', '2026-06-11 06:29:48'),
(6, 14, 2, 'approved', 'Disetujui oleh CS', '2026-06-11 06:29:53'),
(7, 8, 2, 'approved', 'Disetujui oleh CS', '2026-06-11 06:30:11'),
(8, 7, 2, 'approved', 'Disetujui oleh CS', '2026-06-11 06:30:28'),
(9, 9, 2, 'approved', 'Disetujui oleh CS', '2026-06-11 06:30:42'),
(10, 6, 2, 'approved', 'Disetujui oleh CS', '2026-06-22 09:52:24'),
(11, 22, 2, 'approved', 'Disetujui oleh CS', '2026-06-22 10:49:53'),
(12, 13, 2, 'approved', 'Disetujui oleh CS', '2026-06-22 10:50:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'Kuliner', 'fa-utensils', '2026-05-21 02:59:14'),
(2, 'Wisata Alam', 'fa-mountain', '2026-05-21 02:59:14'),
(3, 'Kafe', 'fa-mug-hot', '2026-05-21 02:59:14'),
(4, 'Pantai', 'fa-umbrella-beach', '2026-05-21 02:59:14'),
(5, 'Budaya', 'fa-landmark', '2026-05-21 02:59:14'),
(6, 'Hiburan', 'fa-masks-theater', '2026-05-21 02:59:14'),
(7, 'Hotel', 'fa-hotel', '2026-05-21 02:59:14'),
(8, 'Belanja', 'fa-bag-shopping', '2026-05-21 02:59:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `post_id`, `comment`, `created_at`) VALUES
(1, 3, 1, 'Thanks sudah mampir! \r\nNanti datang lagi yaa kaa🥰', '2026-05-22 16:57:59'),
(2, 5, 1, 'Enak banget dan harganya terjangkau', '2026-05-24 11:00:50'),
(3, 4, 2, 'tes', '2026-06-08 10:29:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `follows`
--

CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(1, 7, 4, '2026-06-08 10:53:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(1, 3, 1, '2026-05-22 16:58:10'),
(2, 5, 1, '2026-05-24 11:00:53'),
(3, 7, 1, '2026-05-25 11:55:40'),
(4, 7, 2, '2026-06-08 10:53:25'),
(5, 13, 2, '2026-06-09 12:04:41'),
(6, 7, 3, '2026-06-22 10:45:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `type` enum('like','comment','follow','review','verified','rejected','report') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `from_user_id`, `type`, `target_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 2, 'verified', 1, 'Katalog \"Caro Smoothies\" telah diverifikasi dan aktif!', 1, '2026-05-22 16:39:17'),
(2, 4, 3, 'comment', 1, 'Bli Caro mengomentari postingan Anda', 1, '2026-05-22 16:57:59'),
(3, 4, 3, 'like', 1, 'Bli Caro menyukai postinganmu', 1, '2026-05-22 16:58:10'),
(4, 4, 5, 'comment', 1, 'Putu Ngurah Purnawan mengomentari postingan Anda', 1, '2026-05-24 11:00:50'),
(5, 4, 5, 'like', 1, 'Putu Ngurah Purnawan menyukai postinganmu', 1, '2026-05-24 11:00:53'),
(6, 4, 7, 'like', 1, 'vino menyukai postinganmu', 1, '2026-05-25 11:55:40'),
(7, 6, 2, 'verified', 2, 'Katalog \"Pantai Nunggalan\" telah diverifikasi dan aktif!', 0, '2026-05-25 13:57:19'),
(8, 4, 7, 'like', 2, 'vino menyukai postinganmu', 1, '2026-06-08 10:53:25'),
(9, 10, 2, 'verified', 3, 'Katalog \"caffe glory\" telah diverifikasi dan aktif!', 0, '2026-06-09 11:43:36'),
(10, 11, 2, 'verified', 4, 'Katalog \"hutan pinus\" telah diverifikasi dan aktif!', 1, '2026-06-09 11:43:44'),
(11, 4, 13, 'like', 2, 'tamu menyukai postinganmu', 1, '2026-06-09 12:04:41'),
(12, 11, 2, 'verified', 15, 'Katalog \"pantai batu bolong\" telah diverifikasi dan aktif!', 1, '2026-06-11 06:29:48'),
(13, 11, 2, 'verified', 14, 'Katalog \"pantai pererenan\" telah diverifikasi dan aktif!', 1, '2026-06-11 06:29:53'),
(14, 10, 2, 'verified', 8, 'Katalog \"Desa Adat Penglipuran\" telah diverifikasi dan aktif!', 0, '2026-06-11 06:30:11'),
(15, 10, 2, 'verified', 7, 'Katalog \"The Yani Hotel\" telah diverifikasi dan aktif!', 0, '2026-06-11 06:30:28'),
(16, 10, 2, 'verified', 9, 'Katalog \"Pasar Seni Ubud\" telah diverifikasi dan aktif!', 0, '2026-06-11 06:30:42'),
(17, 10, 2, 'verified', 6, 'Katalog \"nataraka coffe\" telah diverifikasi dan aktif!', 0, '2026-06-22 09:52:24'),
(18, 12, 7, 'like', 3, 'vino menyukai postinganmu', 1, '2026-06-22 10:45:52'),
(19, 14, 2, 'verified', 22, 'Katalog \"GWK\" telah diverifikasi dan aktif!', 0, '2026-06-22 10:49:53'),
(20, 11, 2, 'verified', 13, 'Katalog \"ruteng\" telah diverifikasi dan aktif!', 0, '2026-06-22 10:50:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `post_type` enum('user','business') NOT NULL DEFAULT 'user',
  `status` enum('published','removed') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `catalog_id`, `caption`, `image`, `post_type`, `status`, `created_at`) VALUES
(1, 4, 1, 'Topingnya gacor sih', 'https://yummy.caro-studio.com/uploads/posts/img_6a1088fcacc411.32280857.jpeg', 'user', 'published', '2026-05-22 16:49:00'),
(2, 4, 2, '', 'https://yummy.caro-studio.com/uploads/posts/img_6a26999bd45b07.88084696.jpg', 'user', 'published', '2026-06-08 10:29:47'),
(3, 12, 1, 'Icon Mall Bali', 'https://yummy.caro-studio.com/uploads/posts/img_6a27ff36b47159.31355306.jpeg', 'user', 'published', '2026-06-09 11:55:34'),
(4, 12, 3, 'Tempat makan dengan nuansa hijau alam', 'https://yummy.caro-studio.com/uploads/posts/img_6a39139999ea34.12626883.jpg', 'user', 'published', '2026-06-22 10:51:05'),
(5, 7, 15, 'batu bolong beach', 'https://yummy.caro-studio.com/uploads/posts/img_6a3913b96f6244.14470103.jpg', 'user', 'published', '2026-06-22 10:51:37'),
(6, 12, 1, 'Caffe dengan Makanan premium', 'https://yummy.caro-studio.com/uploads/posts/img_6a3913db345338.03920109.jpg', 'user', 'published', '2026-06-22 10:52:11'),
(7, 12, 3, 'Caffe ratu bali, dengan interior yang nyaman', 'https://yummy.caro-studio.com/uploads/posts/img_6a391415099cd5.28237239.jpg', 'user', 'published', '2026-06-22 10:53:09'),
(8, 12, 3, 'Caffe Viral', 'https://yummy.caro-studio.com/uploads/posts/img_6a3914645c6430.57437391.jpg', 'user', 'published', '2026-06-22 10:54:28'),
(9, 12, 1, 'Restorant dengan identik\" bali', 'https://yummy.caro-studio.com/uploads/posts/img_6a3914979aefb7.74633286.jpg', 'user', 'published', '2026-06-22 10:55:19'),
(10, 12, 2, 'Nusa penida', 'https://yummy.caro-studio.com/uploads/posts/img_6a3914c77e2aa5.98692480.jpg', 'user', 'published', '2026-06-22 10:56:07'),
(11, 12, 4, 'GWK', 'https://yummy.caro-studio.com/uploads/posts/img_6a391518631bb8.70657040.jpg', 'user', 'published', '2026-06-22 10:57:28'),
(12, 12, 2, 'Tarian budaya bali', 'https://yummy.caro-studio.com/uploads/posts/img_6a39157aafd296.70585736.jpg', 'user', 'published', '2026-06-22 10:59:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `catalog_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `catalog_id`, `rating`, `review`, `created_at`) VALUES
(1, 13, 2, 4, 'pantainya seru tapi jalannya curam', '2026-06-09 12:04:33'),
(2, 7, 4, 5, 'suasananya saangat menyenangkan', '2026-06-22 10:47:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_post_id` int(11) DEFAULT NULL,
  `reported_catalog_id` int(11) DEFAULT NULL,
  `report_type` enum('bug','spam','fake','inappropriate') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','process','done') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `reported_post_id`, `reported_catalog_id`, `report_type`, `description`, `status`, `created_at`) VALUES
(1, 4, NULL, NULL, 'inappropriate', '[Jenis: SARAN]\n[Judul: Reset Password]\n[Email: imadeadipurnamayasa@gmail.com]\n\nBuatkan fitur reset password', 'process', '2026-06-09 11:49:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','owner','cs','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `password`, `profile_picture`, `bio`, `role`, `status`, `created_at`) VALUES
(1, 'Super Admin', 'superadmin', 'superadmin@yummyspot.com', '$2y$12$vlcLXchBLFLvxY6/YRLhde9lAIaaTxAc8./omoYMvslmMAKNvvgTq', NULL, 'Super Administrator YummySpot', 'admin', 'active', '2026-05-22 14:33:19'),
(2, 'CS Team', 'cs_yummyspot', 'cs@yummyspot.com', '$2y$12$vlcLXchBLFLvxY6/YRLhde9lAIaaTxAc8./omoYMvslmMAKNvvgTq', NULL, 'Tim Customer Service YummySpot', 'cs', 'active', '2026-05-22 14:33:19'),
(3, 'Bli Caro', 'bli.caro', 'info@bli-caro.com', '$2y$12$vlcLXchBLFLvxY6/YRLhde9lAIaaTxAc8./omoYMvslmMAKNvvgTq', NULL, NULL, 'owner', 'active', '2026-05-22 16:35:15'),
(4, 'Adi Purnama', 'dip0912', 'imadeadipurnamayasa@gmail.com', '$2y$10$smuryl/hBuIK9yuVHaIFnuD8SCbWXFC/YTkciV3fI9nEqUTHsZrSa', 'https://yummy.caro-studio.com/uploads/avatars/img_6a2a510ba981f2.94985977.jpeg', 'Mari kulineran bersama!', 'user', 'active', '2026-05-22 16:44:34'),
(5, 'Putu Ngurah Purnawan', 'ngurahxmr29', 'ngurahxmr29@gmail.com', '$2y$10$viBFJ/aMeMq4/DG..817euNWYPnQrqYgzt5okL/FGyq6ZA6hD6UA.', NULL, NULL, 'user', 'active', '2026-05-24 11:00:30'),
(6, 'ar', 'artzy', 'aril12@gmail.com', '$2y$10$C.l2KTGrg3UEFap6f7cSueANvAyFR4WVlP/l58QQTggGtIQux.18G', NULL, NULL, 'owner', 'active', '2026-05-24 14:54:10'),
(7, 'vino', 'vino', 'vino@gmail.com', '$2y$10$94Kl.Ou9vVypdlfvU/pM4OX.49cyk.nN7YBifiIqQ89L/nAX.cOty', NULL, NULL, 'user', 'active', '2026-05-25 11:54:55'),
(8, 'tzyar', 'tzy', 'ar@gmail.com', '$2y$10$HwG79ESrgm6JYxl.0OOWJu6t1SL1YwrzIzUYbZKD.o9J7vbnq3t8S', NULL, NULL, 'user', 'active', '2026-05-25 12:14:48'),
(9, 'aril aditya', 'aril', 'ariladitya1707@gmail.com', '$2y$10$PB1y8rgxtQhjcFT389dei.YQbHCiAIE3zq/.Rj8APV9H1OaemxLLe', NULL, NULL, 'owner', 'active', '2026-05-25 12:38:14'),
(10, 'aril aditya', 'ariladit', 'aditya12@gmail.com', '$2y$10$D04mERAKPP4gHIzerFn53O/uMYuRi3zhMeB1itlcOcsMU7MtA.pnu', NULL, NULL, 'owner', 'active', '2026-06-08 10:33:07'),
(11, 'vino', 'vino@gmail.com', 'vinoe@gmail.com', '$2y$10$PS1YUmNwq5BhCmaiGJa0.e1hsI6fHWTppmfv2gNnVeIbra4HQ7zpO', NULL, NULL, 'owner', 'active', '2026-06-08 10:54:09'),
(12, 'Chaisa', 'chaiiii', 'chaisamaulidia12@gmail.com', '$2y$10$9HFRSNZ2.4zh2.6JTsqiMu0ZzkWICAuZLwdH/i.FZfMTOfhdqpn32', 'https://yummy.caro-studio.com/uploads/avatars/img_6a27ffb266be38.93124846.jpg', '', 'user', 'active', '2026-06-09 09:10:23'),
(13, 'tamu', 'tamu', 'tamu1@gmail.com', '$2y$10$tEgKszFIJQGVxXWJmL2GMOI/s0gZdsHLkLfa5WVJhfPR8KSKZp0qW', NULL, NULL, 'user', 'active', '2026-06-09 12:03:47'),
(14, 'Chaisa maulidia', 'cewejawa', 'canakbaik@gmail.com', '$2y$10$BukMBzSHEyx7R4QOJNsEGeLkOk1DpVJ6sMN6ii5e7ITnuEXPOFW6K', NULL, NULL, 'owner', 'active', '2026-06-22 10:04:17'),
(15, 'Yummy', 'yummy001', 'user@yummyspot.com', '$2y$10$PdwKxSiG3KPDniw/A9lYUeLEhyRxntvaON1lzbqL17ybY9tLp8I8e', NULL, NULL, 'user', 'active', '2026-06-27 09:43:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `catalog_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `catalog_id`, `created_at`) VALUES
(1, 4, 1, '2026-05-22 16:50:35'),
(3, 13, 3, '2026-06-09 12:05:47'),
(4, 7, 4, '2026-06-22 10:47:15'),
(5, 7, 2, '2026-06-22 11:11:36'),
(6, 4, 4, '2026-06-27 09:00:16'),
(7, 4, 6, '2026-06-27 09:00:18'),
(8, 4, 22, '2026-06-27 09:00:19');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `catalogs`
--
ALTER TABLE `catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_status` (`verification_status`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_deleted_at` (`deleted_at`);
ALTER TABLE `catalogs` ADD FULLTEXT KEY `ft_search` (`name`,`description`);

--
-- Indeks untuk tabel `catalog_images`
--
ALTER TABLE `catalog_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `catalog_id` (`catalog_id`);

--
-- Indeks untuk tabel `catalog_verifications`
--
ALTER TABLE `catalog_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `catalog_id` (`catalog_id`),
  ADD KEY `cs_id` (`cs_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_name` (`name`);

--
-- Indeks untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indeks untuk tabel `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_follow` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indeks untuk tabel `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_like` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indeks untuk tabel `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `catalog_id` (`catalog_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indeks untuk tabel `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rating` (`user_id`,`catalog_id`),
  ADD KEY `catalog_id` (`catalog_id`);

--
-- Indeks untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_post_id` (`reported_post_id`),
  ADD KEY `reported_catalog_id` (`reported_catalog_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist` (`user_id`,`catalog_id`),
  ADD KEY `catalog_id` (`catalog_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `catalogs`
--
ALTER TABLE `catalogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `catalog_images`
--
ALTER TABLE `catalog_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `catalog_verifications`
--
ALTER TABLE `catalog_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `catalogs`
--
ALTER TABLE `catalogs`
  ADD CONSTRAINT `catalogs_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `catalogs_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ketidakleluasaan untuk tabel `catalog_images`
--
ALTER TABLE `catalog_images`
  ADD CONSTRAINT `catalog_images_ibfk_1` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `catalog_verifications`
--
ALTER TABLE `catalog_verifications`
  ADD CONSTRAINT `catalog_verifications_ibfk_1` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catalog_verifications_ibfk_2` FOREIGN KEY (`cs_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE;
COMMIT;

