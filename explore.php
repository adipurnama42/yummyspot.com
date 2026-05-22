<?php
$pageTitle = 'Eksplorasi — YummySpot';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Featured (top rated & approved)
$featured = $db->query("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon
    FROM catalogs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.verification_status = 'approved'
    ORDER BY c.avg_rating DESC, c.total_reviews DESC
    LIMIT 6
")->fetchAll();

// Newest catalogs
$newest = $db->query("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon
    FROM catalogs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.verification_status = 'approved'
    ORDER BY c.created_at DESC
    LIMIT 8
")->fetchAll();

// Most liked
$popular = $db->query("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon
    FROM catalogs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.verification_status = 'approved'
    ORDER BY c.total_likes DESC
    LIMIT 4
")->fetchAll();

// Categories with count
$categories = $db->query("
    SELECT cat.*, COUNT(c.id) AS total
    FROM categories cat
    LEFT JOIN catalogs c ON c.category_id = cat.id AND c.verification_status = 'approved'
    GROUP BY cat.id
    ORDER BY total DESC
")->fetchAll();

// Cities
$cities = $db->query("
    SELECT city, COUNT(*) AS total
    FROM catalogs
    WHERE verification_status = 'approved'
    GROUP BY city
    ORDER BY total DESC
    LIMIT 10
")->fetchAll();

// Platform stats
$stats = [
    'catalogs' => (int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='approved'")->fetchColumn(),
    'users'    => (int)$db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
    'reviews'  => (int)$db->query("SELECT COUNT(*) FROM ratings")->fetchColumn(),
    'posts'    => (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
];
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main">

    <!-- Hero Search Banner -->
    <div style="background:linear-gradient(135deg, #fff5f0 0%, #fff 60%, #fff0f9 100%); border:1px solid var(--border); border-radius:var(--r-lg); padding:2rem 2.5rem; margin-bottom:1.25rem; position:relative; overflow:hidden;">
        <div style="position:absolute;right:-1rem;top:-1rem;font-size:7rem;opacity:.05;transform:rotate(-10deg);line-height:1"><i class="fa-solid fa-map-location-dot" style="color:var(--accent)"></i></div>
        <h1 style="font-family:'Nunito',sans-serif;font-size:1.6rem;font-weight:900;margin-bottom:.35rem;">
            Temukan Tempat <span style="color:var(--accent)">Terbaik</span> di Sekitarmu
        </h1>
        <p style="color:var(--text2);font-size:.9rem;margin-bottom:1.25rem;">Ribuan tempat wisata dan kuliner tersedia, dikurasi dan diulas komunitas kami.</p>
        <form action="catalog.php" method="GET">
            <div style="display:flex;gap:.5rem;max-width:500px">
                <div class="input-wrap" style="flex:1">
                    <i class="fa-solid fa-magnifying-glass i-icon fa-xs"></i>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama tempat, kota..." style="border-radius:24px;padding-left:2.4rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="border-radius:24px;padding:.6rem 1.25rem;">
                    <i class="fa-solid fa-search fa-xs"></i> Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Platform Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem;margin-bottom:1.25rem;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                <i class="fa-solid fa-store" style="color:var(--accent);font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;"><?= fmtNum($stats['catalogs']) ?></div>
            <div style="font-size:.7rem;color:var(--text3);font-weight:600;">Katalog Aktif</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:44px;height:44px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                <i class="fa-solid fa-users" style="color:var(--blue);font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;"><?= fmtNum($stats['users']) ?></div>
            <div style="font-size:.7rem;color:var(--text3);font-weight:600;">Pengguna</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:44px;height:44px;border-radius:50%;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                <i class="fa-solid fa-star" style="color:var(--amber);font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;"><?= fmtNum($stats['reviews']) ?></div>
            <div style="font-size:.7rem;color:var(--text3);font-weight:600;">Total Ulasan</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:44px;height:44px;border-radius:50%;background:#f5f3ff;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                <i class="fa-solid fa-image" style="color:var(--purple);font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;"><?= fmtNum($stats['posts']) ?></div>
            <div style="font-size:.7rem;color:var(--text3);font-weight:600;">Postingan</div>
        </div>
    </div>

    <!-- Categories -->
    <div style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
            <h2 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><i class="fa-solid fa-grip" style="color:var(--accent)"></i> Jelajahi Kategori</h2>
            <a href="catalog.php" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem;">Lihat semua <i class="fa-solid fa-chevron-right fa-xs"></i></a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;">
            <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
            <a href="catalog.php?cat=<?= $cat['id'] ?>" style="background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:.85rem .65rem;text-align:center;text-decoration:none;transition:all .18s;" onmouseover="this.style.borderColor='var(--accent)';this.style.background='var(--accent-bg)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
                <div style="font-size:1.4rem;color:var(--accent);margin-bottom:.3rem;"><i class="fa-solid <?= e($cat['icon']) ?>"></i></div>
                <div style="font-size:.78rem;font-weight:700;color:var(--text);margin-bottom:.1rem;"><?= e($cat['name']) ?></div>
                <div style="font-size:.68rem;color:var(--text3);"><?= $cat['total'] ?> tempat</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Featured Top Rated -->
    <?php if ($featured): ?>
    <div style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
            <h2 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><i class="fa-solid fa-trophy" style="color:var(--amber)"></i> Rating Tertinggi</h2>
            <a href="catalog.php?sort=rating" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem;">Lihat semua <i class="fa-solid fa-chevron-right fa-xs"></i></a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.65rem;">
            <?php foreach (array_slice($featured, 0, 4) as $c): ?>
            <a href="catalog-detail.php?slug=<?= e($c['slug']) ?>" style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;display:flex;gap:.75rem;text-decoration:none;transition:all .2s;" onmouseover="this.style.boxShadow='var(--shadow-md)';this.style.borderColor='var(--border2)'" onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--border)'">
                <div style="width:80px;height:80px;flex-shrink:0;overflow:hidden;background:var(--bg);display:flex;align-items:center;justify-content:center;">
                    <?php if ($c['thumbnail']): ?>
                        <img src="<?= e($c['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-lg" style="color:var(--accent);opacity:.4;"></i>
                    <?php endif; ?>
                </div>
                <div style="padding:.65rem .75rem .65rem 0;flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.87rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.15rem;"><?= e($c['name']) ?></div>
                    <div style="font-size:.72rem;color:var(--text3);margin-bottom:.3rem;">
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs" style="color:var(--accent)"></i> <?= e($c['cat_name']) ?> &middot;
                        <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($c['city']) ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;">
                        <i class="fa-solid fa-star fa-xs" style="color:var(--amber)"></i>
                        <strong><?= number_format($c['avg_rating'], 1) ?></strong>
                        <span style="color:var(--text3)">(<?= $c['total_reviews'] ?> ulasan)</span>
                        <?php if ($c['verification_status'] === 'approved'): ?>
                        <i class="fa-solid fa-circle-check fa-xs" style="color:var(--green);margin-left:auto" title="Terverifikasi"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Explore by City -->
    <?php if ($cities): ?>
    <div style="margin-bottom:1.25rem;">
        <h2 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;margin-bottom:.75rem;"><i class="fa-solid fa-city" style="color:var(--blue)"></i> Jelajahi per Kota</h2>
        <div style="display:flex;flex-wrap:wrap;gap:.45rem;">
            <?php foreach ($cities as $city): ?>
            <a href="catalog.php?city=<?= urlencode($city['city']) ?>" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i>
                <?= e($city['city']) ?>
                <span style="color:var(--text3);font-size:.68rem;">(<?= $city['total'] ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Most Popular -->
    <?php if ($popular): ?>
    <div style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
            <h2 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><i class="fa-solid fa-fire" style="color:var(--accent)"></i> Paling Banyak Disukai</h2>
            <a href="catalog.php?sort=popular" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem;">Lihat semua <i class="fa-solid fa-chevron-right fa-xs"></i></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($popular as $c): ?>
            <div class="cat-card" onclick="location.href='catalog-detail.php?slug=<?= e($c['slug']) ?>'">
                <div class="cat-cover">
                    <?php if ($c['thumbnail']): ?>
                        <img src="<?= e($c['thumbnail']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="cover-ph"><i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.4"></i></div>
                    <?php endif; ?>
                    <?php if ($c['verification_status'] === 'approved'): ?>
                    <div class="verified-badge"><i class="fa-solid fa-check fa-xs"></i></div>
                    <?php endif; ?>
                </div>
                <div class="cat-info">
                    <div class="cat-name"><?= e($c['name']) ?></div>
                    <div class="cat-sub">
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?> &middot;
                        <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?>
                    </div>
                    <div class="cat-stats">
                        <span class="cat-rating"><i class="fa-solid fa-star fa-xs"></i> <?= number_format($c['avg_rating'], 1) ?></span>
                        <span><i class="fa-regular fa-heart fa-xs"></i> <?= fmtNum($c['total_likes']) ?></span>
                        <span><i class="fa-regular fa-comment fa-xs"></i> <?= fmtNum($c['total_reviews']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Newest -->
    <?php if ($newest): ?>
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
            <h2 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><i class="fa-solid fa-clock-rotate-left" style="color:var(--purple)"></i> Baru Ditambahkan</h2>
            <a href="catalog.php?sort=newest" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem;">Lihat semua <i class="fa-solid fa-chevron-right fa-xs"></i></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($newest as $c): ?>
            <div class="cat-card" onclick="location.href='catalog-detail.php?slug=<?= e($c['slug']) ?>'">
                <div class="cat-cover">
                    <?php if ($c['thumbnail']): ?>
                        <img src="<?= e($c['thumbnail']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="cover-ph"><i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.4"></i></div>
                    <?php endif; ?>
                    <?php if ($c['verification_status'] === 'approved'): ?>
                    <div class="verified-badge"><i class="fa-solid fa-check fa-xs"></i></div>
                    <?php endif; ?>
                    <div style="position:absolute;top:.5rem;left:.5rem;background:var(--purple);color:#fff;padding:.18rem .55rem;border-radius:20px;font-size:.65rem;font-weight:800;">
                        <i class="fa-solid fa-sparkles fa-xs"></i> Baru
                    </div>
                </div>
                <div class="cat-info">
                    <div class="cat-name"><?= e($c['name']) ?></div>
                    <div class="cat-sub">
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?> &middot;
                        <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?>
                    </div>
                    <div class="cat-stats">
                        <span class="cat-rating"><i class="fa-solid fa-star fa-xs"></i> <?= number_format($c['avg_rating'], 1) ?></span>
                        <span><i class="fa-regular fa-heart fa-xs"></i> <?= fmtNum($c['total_likes']) ?></span>
                        <span style="margin-left:auto;font-size:.68rem;color:var(--text3);"><?= timeAgo($c['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- Right Panel -->
<aside class="right-panel">
    <div style="margin-bottom:1.5rem;">
        <div class="panel-ttl"><i class="fa-solid fa-chart-simple" style="color:var(--accent)"></i> Statistik Platform</div>
        <?php foreach ([
            ['fa-building-store','Katalog Aktif', $stats['catalogs'], 'var(--accent)'],
            ['fa-users',         'Pengguna',      $stats['users'],    'var(--blue)'],
            ['fa-star',          'Total Ulasan',  $stats['reviews'],  'var(--amber)'],
            ['fa-image',         'Postingan',     $stats['posts'],    'var(--purple)'],
        ] as [$icon,$label,$val,$color]): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.45rem .1rem;border-bottom:1px solid var(--border);">
            <span style="font-size:.81rem;color:var(--text2);display:flex;align-items:center;gap:.4rem;">
                <i class="fa-solid <?= $icon ?> fa-xs" style="color:<?= $color ?>"></i> <?= $label ?>
            </span>
            <span style="font-size:.85rem;font-weight:800;color:<?= $color ?>"><?= fmtNum($val) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-bottom:1.5rem;">
        <div class="panel-ttl"><i class="fa-solid fa-tags" style="color:var(--blue)"></i> Kategori Populer</div>
        <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
        <a href="catalog.php?cat=<?= $cat['id'] ?>" class="trend-item">
            <div class="trend-thumb" style="background:var(--accent-bg);">
                <i class="fa-solid <?= e($cat['icon']) ?>" style="color:var(--accent)"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="trend-name"><?= e($cat['name']) ?></div>
                <div class="trend-meta"><?= $cat['total'] ?> tempat</div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($user): ?>
    <div>
        <div class="panel-ttl"><i class="fa-solid fa-bolt" style="color:var(--amber)"></i> Menu Cepat</div>
        <a href="index.php"        class="trend-item"><div class="trend-thumb"><i class="fa-solid fa-house" style="color:var(--accent)"></i></div><div class="trend-name">Feed Beranda</div></a>
        <a href="catalog.php"      class="trend-item"><div class="trend-thumb"><i class="fa-solid fa-map-pin" style="color:var(--red)"></i></div><div class="trend-name">Semua Katalog</div></a>
        <a href="wishlist.php"     class="trend-item"><div class="trend-thumb"><i class="fa-solid fa-bookmark" style="color:var(--amber)"></i></div><div class="trend-name">Wishlist Saya</div></a>
        <a href="notifications.php"class="trend-item"><div class="trend-thumb"><i class="fa-solid fa-bell" style="color:var(--blue)"></i></div><div class="trend-name">Notifikasi</div></a>
    </div>
    <?php endif; ?>
</aside>

</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
