<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('owner');
$user = currentUser();
$pageTitle = 'Kelola Ulasan — YummySpot';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Fetch katalog milik owner untuk filter
$myCats = $db->prepare("SELECT id, name FROM catalogs WHERE owner_id = ? ORDER BY name");
$myCats->execute([$user['id']]);
$myCats = $myCats->fetchAll();

// Filter
$selectedCat = (int)($_GET['cat'] ?? 0);
$ratingFlt   = (int)($_GET['rating'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$lmt         = 15;
$off         = ($page - 1) * $lmt;

// Build WHERE
$where  = ["pc.owner_id = ?"]; 
$params = [$user['id']];
if ($selectedCat) { $where[] = "r.catalog_id = ?"; $params[] = $selectedCat; }
if ($ratingFlt)   { $where[] = "r.rating = ?";     $params[] = $ratingFlt; }
$ws = 'WHERE ' . implode(' AND ', $where);

// Total
$tSt = $db->prepare("SELECT COUNT(*) FROM ratings r JOIN catalogs pc ON r.catalog_id = pc.id $ws");
$tSt->execute($params);
$total = (int)$tSt->fetchColumn();

// Fetch reviews
$st = $db->prepare("
    SELECT r.*, u.fullname, u.username, u.profile_picture, pc.name AS cat_name
    FROM ratings r
    JOIN users u    ON r.user_id    = u.id
    JOIN catalogs pc ON r.catalog_id = pc.id
    $ws
    ORDER BY r.created_at DESC
    LIMIT $lmt OFFSET $off
");
$st->execute($params);
$reviews = $st->fetchAll();

$pages = (int)ceil($total / $lmt);

// Summary stats
$statSt = $db->prepare("
    SELECT COUNT(*) AS total, AVG(r.rating) AS avg_r,
        SUM(r.rating = 5) AS r5, SUM(r.rating = 4) AS r4,
        SUM(r.rating = 3) AS r3, SUM(r.rating = 2) AS r2,
        SUM(r.rating = 1) AS r1
    FROM ratings r
    JOIN catalogs pc ON r.catalog_id = pc.id
    WHERE pc.owner_id = ?
");
$statSt->execute([$user['id']]);
$stats = $statSt->fetch();

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
$pg   = 'reviews';
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
    <a href="reviews.php"        class="sb-item active"><i class="fa-solid fa-star si"></i> Ulasan</a>
    <a href="analytics.php"      class="sb-item"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed</a>
</aside>

<main class="main">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-star" style="color:var(--amber)"></i> Kelola Ulasan
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">
                <?= number_format($total) ?> ulasan ditemukan
            </div>
        </div>
    </div>

    <!-- Summary stats -->
    <?php if ($stats['total'] > 0): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1.1rem;margin-bottom:1rem;display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap;">
        <!-- Big rating -->
        <div style="text-align:center;flex-shrink:0;">
            <div style="font-family:'Nunito',sans-serif;font-size:3rem;font-weight:900;color:var(--amber);line-height:1;">
                <?= number_format((float)$stats['avg_r'], 1) ?>
            </div>
            <div style="color:var(--amber);font-size:1rem;margin:.15rem 0;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-<?= $i <= round($stats['avg_r']) ? 'solid' : 'regular' ?> fa-star fa-xs"></i>
                <?php endfor; ?>
            </div>
            <div style="font-size:.7rem;color:var(--text3);"><?= number_format($stats['total']) ?> ulasan</div>
        </div>
        <!-- Rating bars -->
        <div style="flex:1;min-width:180px;">
            <?php foreach ([5,4,3,2,1] as $r):
                $cnt = (int)($stats['r'.$r] ?? 0);
                $pct = $stats['total'] ? round($cnt / $stats['total'] * 100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
                <span style="font-size:.75rem;color:var(--text3);width:10px;"><?= $r ?></span>
                <i class="fa-solid fa-star fa-xs" style="color:var(--amber);"></i>
                <div style="flex:1;height:7px;background:var(--border);border-radius:4px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--amber);border-radius:4px;"></div>
                </div>
                <span style="font-size:.72rem;color:var(--text3);min-width:22px;text-align:right;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Quick filter by star -->
        <div style="display:flex;flex-direction:column;gap:.3rem;flex-shrink:0;">
            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.1rem;">Filter</div>
            <?php foreach ([5,4,3,2,1] as $r): ?>
            <a href="?cat=<?= $selectedCat ?>&rating=<?= $r ?>"
               style="font-size:.75rem;padding:.25rem .6rem;border-radius:var(--r-sm);border:1.5px solid <?= $ratingFlt===$r?'var(--amber)':'var(--border)' ?>;background:<?= $ratingFlt===$r?'#fffbeb':'transparent' ?>;color:<?= $ratingFlt===$r?'var(--amber)':'var(--text2)' ?>;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
                <i class="fa-solid fa-star fa-xs"></i> <?= $r ?> Bintang
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center;">
        <select onchange="location.href='?cat='+this.value+'&rating=<?= $ratingFlt ?>'" class="form-control" style="width:auto;">
            <option value="0">Semua Katalog</option>
            <?php foreach ($myCats as $mc): ?>
            <option value="<?= $mc['id'] ?>" <?= $selectedCat == $mc['id'] ? 'selected' : '' ?>><?= e($mc['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedCat || $ratingFlt): ?>
        <a href="reviews.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-xmark fa-xs"></i> Reset Filter
        </a>
        <?php endif; ?>
        <span style="font-size:.8rem;color:var(--text3);margin-left:auto;">
            <?= number_format($total) ?> ulasan
        </span>
    </div>

    <!-- Reviews list -->
    <?php if (empty($reviews)): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:3rem;color:var(--border2);margin-bottom:1rem;">
            <i class="fa-regular fa-star"></i>
        </div>
        <h3 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:800;margin-bottom:.4rem;color:var(--text2);">
            Belum ada ulasan
        </h3>
        <p style="font-size:.85rem;color:var(--text3);">
            Ulasan dari pengguna akan muncul di sini.
        </p>
    </div>

    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:.6rem;">
        <?php foreach ($reviews as $rv):
            $rp = $pals[($rv['user_id'] - 1) % count($pals)];
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1rem;transition:box-shadow .18s;" onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow='none'">
            <div style="display:flex;align-items:flex-start;gap:.75rem;">
                <!-- Avatar -->
                <div class="avatar av-44" style="background:<?= $rp[1] ?>;color:<?= $rp[0] ?>;flex-shrink:0;">
                    <?php if ($rv['profile_picture']): ?>
                        <img src="<?= e($rv['profile_picture']) ?>" alt="">
                    <?php else: ?>
                        <?= initials($rv['fullname']) ?>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:.4rem;margin-bottom:.3rem;">
                        <div>
                            <span style="font-weight:700;font-size:.88rem;">
                                <a href="<?= APP_URL ?>/profile.php?u=<?= e($rv['username']) ?>" style="color:var(--text);">
                                    <?= e($rv['fullname']) ?>
                                </a>
                            </span>
                            <span style="font-size:.72rem;color:var(--text3);margin-left:.4rem;">
                                @<?= e($rv['username']) ?>
                            </span>
                        </div>
                        <!-- Rating stars -->
                        <div style="display:flex;align-items:center;gap:.3rem;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-<?= $i <= $rv['rating'] ? 'solid' : 'regular' ?> fa-star fa-xs" style="color:var(--amber);"></i>
                            <?php endfor; ?>
                            <span style="font-size:.78rem;font-weight:700;color:var(--amber);margin-left:.15rem;"><?= $rv['rating'] ?>.0</span>
                        </div>
                    </div>

                    <!-- Catalog tag & date -->
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">
                        <span style="font-size:.72rem;background:var(--accent-bg);color:var(--accent);padding:.15rem .5rem;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-store fa-xs"></i> <?= e($rv['cat_name']) ?>
                        </span>
                        <span style="font-size:.72rem;color:var(--text3);">
                            <i class="fa-regular fa-clock fa-xs"></i> <?= timeAgo($rv['created_at']) ?>
                        </span>
                        <span style="font-size:.72rem;color:var(--text3);">
                            <?= date('d M Y', strtotime($rv['created_at'])) ?>
                        </span>
                    </div>

                    <!-- Review text -->
                    <?php if ($rv['review']): ?>
                    <p style="font-size:.875rem;color:var(--text2);line-height:1.65;background:var(--bg);border-radius:var(--r-sm);padding:.65rem .85rem;">
                        <?= nl2br(e($rv['review'])) ?>
                    </p>
                    <?php else: ?>
                    <p style="font-size:.8rem;color:var(--text3);font-style:italic;">
                        <i class="fa-regular fa-comment fa-xs"></i> Tidak ada teks ulasan
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="paging">
        <?php if ($page > 1): ?>
        <a href="?cat=<?= $selectedCat ?>&rating=<?= $ratingFlt ?>&page=<?= $page - 1 ?>" class="page-a">
            <i class="fa-solid fa-chevron-left fa-xs"></i>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <a href="?cat=<?= $selectedCat ?>&rating=<?= $ratingFlt ?>&page=<?= $i ?>" class="page-a <?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?cat=<?= $selectedCat ?>&rating=<?= $ratingFlt ?>&page=<?= $page + 1 ?>" class="page-a">
            <i class="fa-solid fa-chevron-right fa-xs"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
