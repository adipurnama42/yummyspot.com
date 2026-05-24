<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('owner');
$user = currentUser();
$pageTitle = 'Dashboard Pemilik — YummySpot';
require_once __DIR__ . '/../includes/header.php';
$db = getDB(); $oid = $user['id'];
$cats   = $db->prepare("SELECT c.*,cat.name AS cat_name,(SELECT COUNT(*) FROM ratings WHERE catalog_id=c.id) AS rc FROM catalogs c JOIN categories cat ON c.category_id=cat.id WHERE c.owner_id=? ORDER BY c.created_at DESC"); $cats->execute([$oid]); $mycats=$cats->fetchAll();
$sts    = $db->prepare("SELECT SUM(total_likes) AS lk,SUM(total_reviews) AS rv,AVG(avg_rating) AS ar FROM catalogs WHERE owner_id=? AND verification_status='approved'"); $sts->execute([$oid]); $stats=$sts->fetch();
$revs   = $db->prepare("SELECT r.*,u.fullname,c.name AS cat_name FROM ratings r JOIN users u ON r.user_id=u.id JOIN catalogs c ON r.catalog_id=c.id WHERE c.owner_id=? ORDER BY r.created_at DESC LIMIT 5"); $revs->execute([$oid]); $revs=$revs->fetchAll();
$pg = basename($_SERVER['PHP_SELF'],'.php');
?>
<div class="app-wrap">
<aside class="sidebar dash-sidebar">
  <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem">
    <div style="font-size:.7rem;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.08em"><i class="fa-solid fa-store"></i> Panel Pemilik</div>
    <div style="font-size:.75rem;color:var(--text3);margin-top:.15rem"><?= e($user['fullname']) ?></div>
  </div>
  <a href="dashboard.php"      class="sb-item <?= $pg==='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
  <a href="catalogs.php"       class="sb-item <?= $pg==='catalogs'?'active':'' ?>"><i class="fa-solid fa-store si"></i> Katalog Saya</a>
  <a href="catalog-create.php" class="sb-item <?= $pg==='catalog-create'?'active':'' ?>"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
  <a href="reviews.php"        class="sb-item <?= $pg==='reviews'?'active':'' ?>"><i class="fa-solid fa-star si"></i> Ulasan</a>
  <a href="analytics.php"      class="sb-item <?= $pg==='analytics'?'active':'' ?>"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
  <div class="dd-sep"></div>
  <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed</a>
</aside>
<main class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem">
    <div><h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900">Dashboard</h1><div class="text-dim text-sm">Selamat datang, <?= e($user['fullname']) ?></div></div>
    <a href="catalog-create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Katalog</a>
  </div>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-store" style="color:var(--accent)"></i> Katalog Aktif</div><div class="stat-value" style="color:var(--accent)"><?= count(array_filter($mycats,fn($c)=>$c['verification_status']==='approved')) ?></div><div class="stat-sub">dari <?= count($mycats) ?> total</div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-heart" style="color:var(--red)"></i> Total Likes</div><div class="stat-value" style="color:var(--red)"><?= fmtNum((int)($stats['lk']??0)) ?></div><div class="stat-sub">semua katalog</div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-star" style="color:var(--amber)"></i> Total Ulasan</div><div class="stat-value" style="color:var(--amber)"><?= fmtNum((int)($stats['rv']??0)) ?></div><div class="stat-sub">rata-rata <?= number_format((float)($stats['ar']??0),1) ?> ★</div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-clock-rotate-left" style="color:var(--blue)"></i> Pending</div><div class="stat-value" style="color:var(--blue)"><?= count(array_filter($mycats,fn($c)=>$c['verification_status']==='pending')) ?></div><div class="stat-sub">menunggu verifikasi</div></div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem">
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-store" style="color:var(--accent)"></i> Katalog Saya</span><a href="catalog-create.php" class="btn btn-sm" style="color:var(--accent)"><i class="fa-solid fa-plus"></i></a></div>
      <div style="padding:.4rem">
        <?php if (empty($mycats)): ?><div class="empty" style="padding:1.5rem"><div class="e-icon" style="font-size:1.5rem"><i class="fa-solid fa-store"></i></div><h3 style="font-size:.85rem">Belum ada katalog</h3><a href="catalog-create.php" class="btn btn-primary btn-sm mt-2">Buat Sekarang</a></div>
        <?php else: ?>
        <?php foreach ($mycats as $c): ?>
        <a href="catalog-edit.php?id=<?= $c['id'] ?>" style="display:flex;align-items:center;gap:.65rem;padding:.55rem .65rem;border-radius:var(--r-sm);transition:background .15s;text-decoration:none" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
          <div style="width:40px;height:40px;border-radius:var(--r-sm);background:var(--accent-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
            <?php if ($c['thumbnail']): ?><img src="<?= e($c['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php else: ?><i class="fa-solid fa-store" style="color:var(--accent)"></i><?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.85rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($c['name']) ?></div>
            <div class="text-xs text-dim"><?= e($c['city']) ?> · <i class="fa-solid fa-star fa-xs" style="color:var(--amber)"></i><?= number_format($c['avg_rating'],1) ?></div>
          </div>
          <span class="badge <?= match($c['verification_status']){'approved'=>'badge-success','pending'=>'badge-warning',default=>'badge-danger'} ?>"><?= match($c['verification_status']){'approved'=>'Aktif','pending'=>'Pending',default=>'Ditolak'} ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-star" style="color:var(--amber)"></i> Ulasan Terbaru</span><a href="reviews.php" class="btn btn-ghost btn-sm text-dim" style="font-size:.75rem">Lihat semua</a></div>
      <div style="padding:.4rem">
        <?php if (empty($revs)): ?><div class="empty" style="padding:1.5rem"><div class="e-icon" style="font-size:1.5rem"><i class="fa-regular fa-star"></i></div><h3 style="font-size:.85rem">Belum ada ulasan</h3></div>
        <?php else: ?>
        <?php foreach ($revs as $r): ?>
        <div style="padding:.55rem .65rem;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.2rem">
            <span style="font-size:.82rem;font-weight:700"><?= e($r['fullname']) ?></span>
            <span style="color:var(--amber);font-size:.78rem"><?= str_repeat('★',$r['rating']) ?></span>
          </div>
          <div class="text-xs text-dim" style="margin-bottom:.15rem"><?= e($r['cat_name']) ?></div>
          <?php if ($r['review']): ?><div style="font-size:.78rem;color:var(--text2);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= e($r['review']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
