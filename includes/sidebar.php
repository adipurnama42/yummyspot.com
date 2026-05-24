<?php
$pg       = basename($_SERVER['PHP_SELF'], '.php');
$isCatalogPage = in_array($pg, ['catalog', 'catalog-detail']);
?>
<aside class="sidebar">
  <div class="sidebar-section">
    <a href="<?= APP_URL ?>/index.php" class="sb-item <?= $pg==='index' ? 'active' : '' ?>">
      <i class="fa-solid fa-house si"></i> Beranda
    </a>
    <a href="<?= APP_URL ?>/explore.php" class="sb-item <?= $pg==='explore' ? 'active' : '' ?>">
      <i class="fa-solid fa-compass si"></i> Eksplorasi
    </a>
    <a href="<?= APP_URL ?>/catalog.php" class="sb-item <?= $isCatalogPage ? 'active' : '' ?>">
      <i class="fa-solid fa-map-pin si"></i> Katalog Tempat
    </a>
    <?php if ($user): ?>
    <a href="<?= APP_URL ?>/wishlist.php" class="sb-item <?= $pg==='wishlist' ? 'active' : '' ?>">
      <i class="fa-regular fa-bookmark si"></i> Wishlist
    </a>
    <a href="<?= APP_URL ?>/notifications.php" class="sb-item <?= $pg==='notifications' ? 'active' : '' ?>">
      <i class="fa-regular fa-bell si"></i> Notifikasi
      <?php if ($notifCnt > 0): ?>
      <span class="sb-count"><?= $notifCnt ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/profile.php" class="sb-item <?= $pg==='profile' ? 'active' : '' ?>">
      <i class="fa-regular fa-user si"></i> Profil
    </a>
    <a href="<?= APP_URL ?>/my-reports.php" class="sb-item <?= $pg==='my-reports' ? 'active' : '' ?>">
      <i class="fa-regular fa-flag si"></i> Laporan Saya
    </a>
    <a href="<?= APP_URL ?>/contact.php" class="sb-item <?= $pg==='contact' ? 'active' : '' ?>">
      <i class="fa-solid fa-headset si"></i> Hubungi Kami
    </a>
    <?php endif; ?>
  </div>

  <div class="sb-label">Kategori</div>
  <?php
  $activeCatId = (int)($_GET['cat'] ?? 0);
  $cats = getDB()->query("SELECT * FROM categories ORDER BY id LIMIT 8")->fetchAll();
  foreach ($cats as $c):
    $isCatActive = $isCatalogPage && $activeCatId === (int)$c['id'];
  ?>
  <a href="<?= APP_URL ?>/catalog.php?cat=<?= $c['id'] ?>"
     class="sb-item <?= $isCatActive ? 'active' : '' ?>"
     style="font-size:.8rem;">
    <i class="fa-solid <?= e($c['icon']) ?> si"></i> <?= e($c['name']) ?>
  </a>
  <?php endforeach; ?>
</aside>
