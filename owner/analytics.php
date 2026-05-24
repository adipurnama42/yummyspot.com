<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('owner');
$user = currentUser();
$pageTitle = 'Analitik — YummySpot';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Katalog milik owner yang sudah approved
$myCats = $db->prepare("SELECT id, name FROM catalogs WHERE owner_id = ? AND verification_status = 'approved' ORDER BY name");
$myCats->execute([$user['id']]);
$myCats = $myCats->fetchAll();

$selectedId = (int)($_GET['catalog'] ?? ($myCats[0]['id'] ?? 0));

$catalog = null;
$ratingDist   = [];
$recentReviews = [];
$monthlyData  = [];

if ($selectedId) {
    $cSt = $db->prepare("SELECT * FROM catalogs WHERE id = ? AND owner_id = ?");
    $cSt->execute([$selectedId, $user['id']]);
    $catalog = $cSt->fetch();

    if ($catalog) {
        // Distribusi rating
        $rdSt = $db->prepare("SELECT rating, COUNT(*) AS cnt FROM ratings WHERE catalog_id = ? GROUP BY rating ORDER BY rating DESC");
        $rdSt->execute([$selectedId]);
        foreach ($rdSt->fetchAll() as $r) $ratingDist[$r['rating']] = $r['cnt'];

        // Ulasan terbaru
        $rrSt = $db->prepare("SELECT r.*, u.fullname, u.username FROM ratings r JOIN users u ON r.user_id = u.id WHERE r.catalog_id = ? ORDER BY r.created_at DESC LIMIT 5");
        $rrSt->execute([$selectedId]);
        $recentReviews = $rrSt->fetchAll();

        // Data ulasan per bulan (6 bulan terakhir)
        $mSt = $db->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mon,
                   DATE_FORMAT(created_at, '%b %Y') AS label,
                   COUNT(*) AS cnt,
                   AVG(rating) AS avg_r
            FROM ratings
            WHERE catalog_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY mon, label
            ORDER BY mon ASC
        ");
        $mSt->execute([$selectedId]);
        $monthlyData = $mSt->fetchAll();

        // Jumlah postingan yang mention katalog ini
        $postSt = $db->prepare("SELECT COUNT(*) FROM posts WHERE catalog_id = ? AND status = 'published'");
        $postSt->execute([$selectedId]);
        $postCount = (int)$postSt->fetchColumn();

        // Jumlah wishlist
        $wlSt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE catalog_id = ?");
        $wlSt->execute([$selectedId]);
        $wishCount = (int)$wlSt->fetchColumn();
    }
}

$pg = 'analytics';
?>

<div class="app-wrap">
<!-- Owner Sidebar -->
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-store"></i> Panel Pemilik
        </div>
        <div style="font-size:.75rem;color:var(--text3);margin-top:.15rem;"><?= e($user['fullname']) ?></div>
    </div>
    <a href="dashboard.php"      class="sb-item"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
    <a href="catalogs.php"       class="sb-item"><i class="fa-solid fa-store si"></i> Katalog Saya</a>
    <a href="catalog-create.php" class="sb-item"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
    <a href="reviews.php"        class="sb-item"><i class="fa-solid fa-star si"></i> Ulasan</a>
    <a href="analytics.php"      class="sb-item active"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed</a>
</aside>

<main class="main">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:.75rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-chart-bar" style="color:var(--accent)"></i> Analitik
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">Pantau performa katalogmu</div>
        </div>
        <!-- Pilih katalog -->
        <?php if ($myCats): ?>
        <form method="GET">
            <select name="catalog" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ($myCats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedId ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <?php if (!$catalog): ?>
    <!-- Belum ada katalog aktif -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--border2);margin-bottom:1rem;"><i class="fa-solid fa-chart-bar"></i></div>
        <h3 style="font-family:'Nunito',sans-serif;font-weight:800;margin-bottom:.4rem;color:var(--text2);">Belum ada katalog aktif</h3>
        <p style="font-size:.85rem;color:var(--text3);margin-bottom:1.25rem;">Verifikasi katalogmu terlebih dahulu untuk melihat analitik.</p>
        <a href="catalogs.php" class="btn btn-primary">
            <i class="fa-solid fa-store fa-xs"></i> Lihat Katalog Saya
        </a>
    </div>

    <?php else: ?>

    <!-- Stats Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.65rem;margin-bottom:1.25rem;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;margin:0 auto .4rem;">
                <i class="fa-solid fa-star" style="color:var(--amber);font-size:1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.5rem;font-weight:900;color:var(--amber);"><?= number_format($catalog['avg_rating'], 1) ?></div>
            <div style="font-size:.68rem;color:var(--text3);font-weight:600;">Avg Rating</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto .4rem;">
                <i class="fa-regular fa-comment" style="color:var(--green);font-size:1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.5rem;font-weight:900;color:var(--green);"><?= fmtNum($catalog['total_reviews']) ?></div>
            <div style="font-size:.68rem;color:var(--text3);font-weight:600;">Total Ulasan</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto .4rem;">
                <i class="fa-regular fa-heart" style="color:var(--red);font-size:1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.5rem;font-weight:900;color:var(--red);"><?= fmtNum($catalog['total_likes']) ?></div>
            <div style="font-size:.68rem;color:var(--text3);font-weight:600;">Total Likes</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin:0 auto .4rem;">
                <i class="fa-regular fa-bookmark" style="color:var(--amber);font-size:1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.5rem;font-weight:900;color:var(--amber);"><?= fmtNum($wishCount) ?></div>
            <div style="font-size:.68rem;color:var(--text3);font-weight:600;">Wishlist</div>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.85rem;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;background:#f5f3ff;display:flex;align-items:center;justify-content:center;margin:0 auto .4rem;">
                <i class="fa-regular fa-image" style="color:var(--purple);font-size:1rem;"></i>
            </div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.5rem;font-weight:900;color:var(--purple);"><?= fmtNum($postCount) ?></div>
            <div style="font-size:.68rem;color:var(--text3);font-weight:600;">Postingan</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">

        <!-- Rating Distribution -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-star" style="color:var(--amber)"></i> Distribusi Rating</span>
            </div>
            <div class="card-body">
                <?php if ($catalog['total_reviews'] > 0): ?>
                <div style="display:flex;gap:1.5rem;align-items:center;">
                    <div style="text-align:center;flex-shrink:0;">
                        <div style="font-family:'Nunito',sans-serif;font-size:3rem;font-weight:900;color:var(--amber);line-height:1;"><?= number_format($catalog['avg_rating'], 1) ?></div>
                        <div style="color:var(--amber);font-size:1rem;margin:.2rem 0;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-<?= $i <= round($catalog['avg_rating']) ? 'solid' : 'regular' ?> fa-star fa-xs"></i>
                            <?php endfor; ?>
                        </div>
                        <div style="font-size:.7rem;color:var(--text3);"><?= $catalog['total_reviews'] ?> ulasan</div>
                    </div>
                    <div style="flex:1;">
                        <?php for ($r = 5; $r >= 1; $r--):
                            $cnt = $ratingDist[$r] ?? 0;
                            $pct = $catalog['total_reviews'] ? round($cnt / $catalog['total_reviews'] * 100) : 0;
                        ?>
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem;">
                            <span style="font-size:.75rem;color:var(--text3);width:10px;"><?= $r ?></span>
                            <i class="fa-solid fa-star fa-xs" style="color:var(--amber);"></i>
                            <div style="flex:1;height:7px;background:var(--border);border-radius:4px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;background:var(--amber);border-radius:4px;transition:width .6s ease;"></div>
                            </div>
                            <span style="font-size:.72rem;color:var(--text3);min-width:22px;text-align:right;"><?= $cnt ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty" style="padding:1.5rem;">
                    <div class="e-icon" style="font-size:1.5rem;"><i class="fa-regular fa-star"></i></div>
                    <h3 style="font-size:.875rem;">Belum ada ulasan</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Chart -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-chart-column" style="color:var(--accent)"></i> Ulasan per Bulan</span>
            </div>
            <div class="card-body">
                <?php if (empty($monthlyData)): ?>
                <div class="empty" style="padding:1.5rem;">
                    <div class="e-icon" style="font-size:1.5rem;"><i class="fa-solid fa-chart-column"></i></div>
                    <h3 style="font-size:.875rem;">Belum ada data</h3>
                    <p>Data akan muncul setelah ada ulasan.</p>
                </div>
                <?php else:
                    $maxCnt = max(array_column($monthlyData, 'cnt')) ?: 1;
                ?>
                <div style="display:flex;align-items:flex-end;gap:.5rem;height:110px;padding:.5rem 0 0;">
                    <?php foreach ($monthlyData as $m):
                        $barH = max(8, round(($m['cnt'] / $maxCnt) * 90));
                    ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.25rem;">
                        <span style="font-size:.65rem;color:var(--accent);font-weight:700;"><?= $m['cnt'] ?></span>
                        <div style="width:100%;height:<?= $barH ?>px;background:linear-gradient(to top,var(--accent),var(--accent2));border-radius:4px 4px 0 0;transition:height .5s ease;" title="<?= $m['label'] ?>: <?= $m['cnt'] ?> ulasan, avg <?= number_format($m['avg_r'],1) ?>★"></div>
                        <span style="font-size:.62rem;color:var(--text3);text-align:center;white-space:nowrap;"><?= substr($m['label'], 0, 3) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ulasan Terbaru -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent)"></i> Ulasan Terbaru</span>
            <a href="reviews.php?cat=<?= $selectedId ?>" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem;">
                Lihat semua <i class="fa-solid fa-chevron-right fa-xs"></i>
            </a>
        </div>
        <div class="card-body" style="padding:.5rem;">
            <?php if (empty($recentReviews)): ?>
            <div class="empty" style="padding:1.5rem;">
                <div class="e-icon" style="font-size:1.5rem;"><i class="fa-regular fa-comment"></i></div>
                <h3 style="font-size:.875rem;">Belum ada ulasan</h3>
            </div>
            <?php else:
                $pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
            ?>
            <?php foreach ($recentReviews as $rv):
                $rp = $pals[($rv['user_id'] - 1) % count($pals)];
            ?>
            <div style="display:flex;align-items:flex-start;gap:.65rem;padding:.65rem .5rem;border-bottom:1px solid var(--border);">
                <div class="avatar av-36" style="background:<?= $rp[1] ?>;color:<?= $rp[0] ?>;flex-shrink:0;">
                    <?= initials($rv['fullname']) ?>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.3rem;margin-bottom:.2rem;">
                        <span style="font-weight:700;font-size:.85rem;"><?= e($rv['fullname']) ?></span>
                        <div style="display:flex;align-items:center;gap:.3rem;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-<?= $i <= $rv['rating'] ? 'solid' : 'regular' ?> fa-star fa-xs" style="color:var(--amber);"></i>
                            <?php endfor; ?>
                            <span style="font-size:.7rem;color:var(--text3);margin-left:.25rem;"><?= timeAgo($rv['created_at']) ?></span>
                        </div>
                    </div>
                    <?php if ($rv['review']): ?>
                    <p style="font-size:.83rem;color:var(--text2);line-height:1.6;margin:0;"><?= e(mb_strimwidth($rv['review'], 0, 150, '...')) ?></p>
                    <?php else: ?>
                    <p style="font-size:.78rem;color:var(--text3);font-style:italic;margin:0;">Tidak ada teks ulasan</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
