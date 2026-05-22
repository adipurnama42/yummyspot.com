<?php
$pageTitle = 'Wishlist Saya — YummySpot';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$lmt  = 12;
$off  = ($page - 1) * $lmt;

$tSt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
$tSt->execute([$user['id']]);
$total = (int)$tSt->fetchColumn();

$st = $db->prepare("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon, w.created_at AS saved_at
    FROM wishlists w
    JOIN catalogs c   ON w.catalog_id  = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT ? OFFSET ?
");
$st->execute([$user['id'], $lmt, $off]);
$items = $st->fetchAll();

$pages = (int)ceil($total / $lmt);
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-bookmark" style="color:var(--accent)"></i> Wishlist Saya
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">
                <?= number_format($total) ?> tempat tersimpan
            </div>
        </div>
        <a href="catalog.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus fa-xs"></i> Tambah Tempat
        </a>
    </div>

    <?php if (empty($items)): ?>
    <!-- Empty state -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:3rem;color:var(--border2);margin-bottom:1rem;">
            <i class="fa-regular fa-bookmark"></i>
        </div>
        <h3 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:800;margin-bottom:.4rem;color:var(--text2);">
            Wishlist masih kosong
        </h3>
        <p style="font-size:.85rem;color:var(--text3);margin-bottom:1.25rem;">
            Simpan tempat-tempat menarik yang ingin kamu kunjungi.
        </p>
        <a href="explore.php" class="btn btn-primary">
            <i class="fa-solid fa-compass fa-xs"></i> Eksplorasi Sekarang
        </a>
    </div>

    <?php else: ?>

    <div class="cat-grid">
        <?php foreach ($items as $c): ?>
        <div class="cat-card" style="position:relative;">
            <!-- Card click ke detail -->
            <div onclick="location.href='catalog-detail.php?slug=<?= e($c['slug']) ?>'" style="cursor:pointer;">
                <div class="cat-cover">
                    <?php if ($c['thumbnail']): ?>
                        <img src="<?= e($c['thumbnail']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="cover-ph">
                            <i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.4"></i>
                        </div>
                    <?php endif; ?>
                    <?php if ($c['verification_status'] === 'approved'): ?>
                    <div class="verified-badge"><i class="fa-solid fa-check fa-xs"></i></div>
                    <?php endif; ?>
                </div>
                <div class="cat-info">
                    <div class="cat-name"><?= e($c['name']) ?></div>
                    <div class="cat-sub">
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?>
                        &middot;
                        <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?>
                    </div>
                    <div class="cat-stats">
                        <span class="cat-rating"><i class="fa-solid fa-star fa-xs"></i> <?= number_format($c['avg_rating'], 1) ?></span>
                        <span><i class="fa-regular fa-comment fa-xs"></i> <?= fmtNum($c['total_reviews']) ?></span>
                        <span><i class="fa-regular fa-heart fa-xs"></i> <?= fmtNum($c['total_likes']) ?></span>
                    </div>
                    <div style="font-size:.68rem;color:var(--text3);margin-top:.35rem;">
                        <i class="fa-regular fa-clock fa-xs"></i> Disimpan <?= timeAgo($c['saved_at']) ?>
                    </div>
                </div>
            </div>
            <!-- Tombol hapus dari wishlist -->
            <div style="padding:.5rem .85rem;border-top:1px solid var(--border);">
                <button class="btn btn-ghost btn-sm w-100"
                    style="color:var(--red);font-size:.78rem;justify-content:center;"
                    onclick="removeWishlist(<?= $c['id'] ?>, this)">
                    <i class="fa-solid fa-bookmark-slash fa-xs"></i> Hapus dari Wishlist
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="paging">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-a"><i class="fa-solid fa-chevron-left fa-xs"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>" class="page-a <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-a"><i class="fa-solid fa-chevron-right fa-xs"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
</div>

<script>
function removeWishlist(catalogId, btn) {
    fetch('actions/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `catalog_id=${catalogId}&csrf_token=${getCsrf()}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok && !d.saved) {
            const card = btn.closest('.cat-card');
            card.style.transition = 'opacity .3s, transform .3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(.95)';
            setTimeout(() => {
                card.remove();
                toast('Dihapus dari wishlist');
                // Update counter
                const counter = document.querySelector('h1 + div');
                if (counter) {
                    const current = parseInt(counter.textContent) || 0;
                    if (current > 0) counter.textContent = (current - 1) + ' tempat tersimpan';
                }
            }, 300);
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
