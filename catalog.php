<?php
$pageTitle = 'Katalog Tempat — YummySpot';
require_once __DIR__ . '/includes/header.php';
$db   = getDB();
$page = max(1,(int)($_GET['page']??1));
$lmt  = ITEMS_PER_PAGE; $off = ($page-1)*$lmt;
$q    = trim(is_array($_GET['q']??'') ? '' : ($_GET['q']??''));
$cat  = (int)($_GET['cat']??0);
$city = trim(is_array($_GET['city']??'') ? '' : ($_GET['city']??''));
$sort = in_array($_GET['sort']??'',['popular','rating','newest'])?$_GET['sort']:'popular';

$where = ["c.verification_status='approved'"]; $params=[];
if ($q)    { $where[]="MATCH(c.name,c.description) AGAINST(? IN BOOLEAN MODE)"; $params[]=$q.'*'; }
if ($cat)  { $where[]="c.category_id=?"; $params[]=$cat; }
if ($city) { $where[]="c.city LIKE ?"; $params[]='%'.$city.'%'; }
$ws  = 'WHERE '.implode(' AND ',$where);
$ord = match($sort){'rating'=>'c.avg_rating DESC','newest'=>'c.created_at DESC',default=>'c.total_likes DESC, c.avg_rating DESC'};

$ts = $db->prepare("SELECT COUNT(*) FROM catalogs c $ws"); $ts->execute($params); $total=(int)$ts->fetchColumn();
$st = $db->prepare("SELECT c.*,cat.name AS cat_name,cat.icon AS cat_icon FROM catalogs c JOIN categories cat ON c.category_id=cat.id $ws ORDER BY $ord LIMIT $lmt OFFSET $off");
$st->execute($params); $catalogs=$st->fetchAll();

$cats  = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$cities= $db->query("SELECT DISTINCT city FROM catalogs WHERE verification_status='approved' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$pages = (int)ceil($total/$lmt);
?>
<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
  <!-- Filter bar -->
  <div class="card" style="margin-bottom:1rem;padding:.85rem 1.1rem">
    <form method="GET">
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <div class="input-wrap" style="flex:1;min-width:180px">
          <i class="fa-solid fa-magnifying-glass i-icon fa-xs"></i>
          <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari nama tempat...">
        </div>
        <select name="cat" class="form-control" style="width:auto">
          <option value="">Semua Kategori</option>
          <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="city" class="form-control" style="width:auto">
          <option value="">Semua Kota</option>
          <?php foreach ($cities as $ct): ?><option value="<?= e($ct) ?>" <?= $city===$ct?'selected':'' ?>><?= e($ct) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        <?php if ($q||$cat||$city): ?><a href="catalog.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a><?php endif; ?>
      </div>
    </form>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
    <div><span style="font-weight:800;font-family:'Nunito',sans-serif"><?= number_format($total) ?></span> <span class="text-dim">tempat ditemukan</span></div>
    <div style="display:flex;gap:.3rem">
      <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'popular'])) ?>" class="btn btn-sm <?= $sort==='popular'?'btn-primary':'btn-outline' ?>"><i class="fa-solid fa-fire"></i> Populer</a>
      <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'rating'])) ?>"  class="btn btn-sm <?= $sort==='rating' ?'btn-primary':'btn-outline' ?>"><i class="fa-solid fa-star"></i> Rating</a>
      <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'newest'])) ?>" class="btn btn-sm <?= $sort==='newest'?'btn-primary':'btn-outline' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Terbaru</a>
    </div>
  </div>

  <?php if (empty($catalogs)): ?>
  <div class="empty"><div class="e-icon"><i class="fa-solid fa-map-pin"></i></div><h3>Tidak ditemukan</h3><p>Coba ubah kata kunci atau filter.</p></div>
  <?php else: ?>
  <div class="cat-grid">
    <?php foreach ($catalogs as $c):
      $wl = false;
      if ($user) { $ws2=$db->prepare("SELECT id FROM wishlists WHERE user_id=? AND catalog_id=?"); $ws2->execute([$user['id'],$c['id']]); $wl=(bool)$ws2->fetchColumn(); }
    ?>
    <div class="cat-card" onclick="location.href='catalog-detail.php?slug=<?= e($c['slug']) ?>'">
      <div class="cat-cover">
        <?php if ($c['thumbnail']): ?><img src="<?= e($c['thumbnail']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
        <?php else: ?><div class="cover-ph"><i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.4"></i></div><?php endif; ?>
        <?php if ($c['verification_status']==='approved'): ?><div class="verified-badge"><i class="fa-solid fa-check fa-xs"></i></div><?php endif; ?>
        <?php if ($user): ?>
        <button onclick="event.stopPropagation();toggleWishlist(<?= $c['id'] ?>,this)"
          class="btn btn-icon btn-sm <?= $wl?'':'btn-ghost' ?>" style="position:absolute;top:.5rem;left:.5rem;background:rgba(255,255,255,.85);color:<?= $wl?'var(--accent)':'var(--text2)' ?>" title="Wishlist">
          <i class="fa-<?= $wl?'solid':'regular' ?> fa-bookmark fa-xs"></i>
        </button>
        <?php endif; ?>
      </div>
      <div class="cat-info">
        <div class="cat-name"><?= e($c['name']) ?></div>
        <div class="cat-sub"><i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?> · <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?></div>
        <div class="cat-stats">
          <span class="cat-rating"><i class="fa-solid fa-star fa-xs"></i> <?= number_format($c['avg_rating'],1) ?></span>
          <span><i class="fa-regular fa-comment fa-xs"></i> <?= fmtNum($c['total_reviews']) ?></span>
          <span><i class="fa-regular fa-heart fa-xs"></i> <?= fmtNum($c['total_likes']) ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($pages>1): ?>
  <div class="paging">
    <?php if ($page>1): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="page-a"><i class="fa-solid fa-chevron-left"></i></a><?php endif; ?>
    <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-a <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
    <?php if ($page<$pages): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="page-a"><i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
