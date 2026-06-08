<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
$user = currentUser();

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$lmt  = ITEMS_PER_PAGE;
$off  = ($page - 1) * $lmt;

// ── Filter params ─────────────────────────────────────────
$q    = trim($_GET['q']    ?? '');
$cat  = (int)($_GET['cat'] ?? 0);
$city = trim($_GET['city'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['popular', 'rating', 'newest'])
  ? $_GET['sort'] : 'popular';

// ── Build WHERE ───────────────────────────────────────────
$where  = ["c.verification_status = 'approved'"];
$params = [];

if ($q) {
  $where[] = "(c.name LIKE ? OR c.description LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if ($cat) {
  $where[] = "c.category_id = ?";
  $params[] = (int)$cat;
}
if ($city) {
  $where[] = "c.city LIKE ?";
  $params[] = "%$city%";
}

$ws  = 'WHERE ' . implode(' AND ', $where);
$ord = match ($sort) {
  'rating' => 'c.avg_rating DESC, c.total_reviews DESC',
  'newest' => 'c.created_at DESC',
  default  => 'c.total_likes DESC, c.avg_rating DESC',
};

// ── Total ─────────────────────────────────────────────────
$tSt = $db->prepare("SELECT COUNT(*) FROM catalogs c $ws");
$tSt->execute($params);
$total = (int)$tSt->fetchColumn();

// ── Fetch catalogs ────────────────────────────────────────
$sql = "SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon
        FROM catalogs c
        JOIN categories cat ON c.category_id = cat.id
        $ws
        ORDER BY $ord
        LIMIT $lmt OFFSET $off";

$cSt = $db->prepare($sql);
$cSt->execute($params);
$catalogs = $cSt->fetchAll();

$pages  = (int)ceil($total / $lmt);
$cats   = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$cities = $db->query("SELECT DISTINCT city FROM catalogs WHERE verification_status='approved' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

// ── Output HTML ───────────────────────────────────────────
$pageTitle = 'Katalog Tempat — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrap">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">

    <!-- Filter bar -->
    <div class="card" style="margin-bottom:1rem;padding:.85rem 1.1rem;">
      <form method="GET" id="filter-form">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
          <div class="input-wrap" style="flex:1;min-width:180px;">
            <i class="fa-solid fa-magnifying-glass i-icon fa-xs"></i>
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari nama tempat...">
          </div>
          <select name="cat" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <select name="city" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">Semua Kota</option>
            <?php foreach ($cities as $ct): ?>
              <option value="<?= e($ct) ?>" <?= $city === $ct ? 'selected' : '' ?>><?= e($ct) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-filter"></i> Filter
          </button>
          <?php if ($q || $cat || $city): ?>
            <a href="catalog.php" class="btn btn-outline btn-sm">
              <i class="fa-solid fa-xmark"></i> Reset
            </a>
          <?php endif; ?>
        </div>

        <!-- Kategori pills -->
        <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.65rem;">
          <a href="catalog.php?sort=<?= $sort ?>"
            class="btn btn-sm <?= !$cat ? 'btn-primary' : 'btn-outline' ?>" style="font-size:.75rem;">
            Semua
          </a>
          <?php foreach ($cats as $c): ?>
            <a href="catalog.php?cat=<?= (int)$c['id'] ?>&sort=<?= $sort ?>"
              class="btn btn-sm <?= $cat === (int)$c['id'] ? 'btn-primary' : 'btn-outline' ?>"
              style="font-size:.75rem;">
              <i class="fa-solid <?= e($c['icon']) ?> fa-xs"></i> <?= e($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </form>
    </div>

    <!-- Sort + total -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem;flex-wrap:wrap;gap:.5rem;">
      <div>
        <span style="font-weight:800;font-family:'Nunito',sans-serif;"><?= number_format($total) ?></span>
        <span class="text-dim"> tempat ditemukan</span>
        <?php if ($cat): ?>
          <?php $ac = array_values(array_filter($cats, fn($c) => (int)$c['id'] === $cat))[0] ?? null; ?>
          <?php if ($ac): ?>
            <span style="background:var(--accent-bg);color:var(--accent);padding:.15rem .55rem;border-radius:20px;font-size:.72rem;font-weight:700;margin-left:.35rem;">
              <i class="fa-solid <?= e($ac['icon']) ?> fa-xs"></i> <?= e($ac['name']) ?>
            </span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:.3rem;">
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'popular', 'page' => 1])) ?>"
          class="btn btn-sm <?= $sort === 'popular' ? 'btn-primary' : 'btn-outline' ?>">
          <i class="fa-solid fa-fire fa-xs"></i> Populer
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'rating', 'page' => 1])) ?>"
          class="btn btn-sm <?= $sort === 'rating' ? 'btn-primary' : 'btn-outline' ?>">
          <i class="fa-solid fa-star fa-xs"></i> Rating
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'newest', 'page' => 1])) ?>"
          class="btn btn-sm <?= $sort === 'newest' ? 'btn-primary' : 'btn-outline' ?>">
          <i class="fa-solid fa-clock-rotate-left fa-xs"></i> Terbaru
        </a>
      </div>
    </div>

    <!-- Catalog grid -->
    <?php if (empty($catalogs)): ?>
      <div class="empty">
        <div class="e-icon"><i class="fa-solid fa-map-pin"></i></div>
        <h3>Tidak ditemukan</h3>
        <p>Coba ubah kata kunci atau pilih kategori lain.</p>
        <a href="catalog.php" class="btn btn-primary mt-2">Lihat Semua</a>
      </div>
    <?php else: ?>
      <div class="cat-grid">
        <?php foreach ($catalogs as $c):
          $wl = false;
          if ($user) {
            $wsSt = $db->prepare("SELECT id FROM wishlists WHERE user_id=? AND catalog_id=?");
            $wsSt->execute([$user['id'], $c['id']]);
            $wl = (bool)$wsSt->fetchColumn();
          }
        ?>
          <div class="cat-card" onclick="location.href='<?= APP_URL ?>/catalog-detail.php?slug=<?= e($c['slug']) ?>'">
            <div class="cat-cover">
              <?php if ($c['thumbnail']): ?>
                <img src="<?= e($c['thumbnail']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
              <?php else: ?>
                <div class="cover-ph">
                  <i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.4;"></i>
                </div>
              <?php endif; ?>
              <div class="verified-badge"><i class="fa-solid fa-check fa-xs"></i></div>
              <?php if ($user): ?>
                <button onclick="event.stopPropagation();toggleWishlist(<?= $c['id'] ?>,this)"
                  class="btn btn-icon btn-sm"
                  style="position:absolute;top:.5rem;left:.5rem;background:rgba(255,255,255,.85);color:<?= $wl ? 'var(--accent)' : 'var(--text2)' ?>;">
                  <i class="fa-<?= $wl ? 'solid' : 'regular' ?> fa-bookmark fa-xs"></i>
                </button>
              <?php endif; ?>
            </div>
            <div class="cat-info">
              <div class="cat-name"><?= e($c['name']) ?></div>
              <div class="cat-sub">
                <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?>
                &middot; <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?>
              </div>
              <div class="cat-stats">
                <span class="cat-rating"><i class="fa-solid fa-star fa-xs"></i> <?= number_format($c['avg_rating'], 1) ?></span>
                <span><i class="fa-regular fa-comment fa-xs"></i> <?= fmtNum($c['total_reviews']) ?></span>
                <span><i class="fa-regular fa-heart fa-xs"></i> <?= fmtNum($c['total_likes']) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <div class="paging">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-a">
              <i class="fa-solid fa-chevron-left fa-xs"></i>
            </a>
          <?php endif; ?>
          <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
              class="page-a <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-a">
              <i class="fa-solid fa-chevron-right fa-xs"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>