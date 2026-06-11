<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/helpers.php';
startSession();
$user     = currentUser();
$notifCnt = $user ? unreadNotifCount($user['id']) : 0;
$isOwner  = $user && $user['role'] === 'owner';
$isCS     = $user && $user['role'] === 'cs';
$isAdmin  = $user && $user['role'] === 'admin';
$isDash   = $isOwner || $isCS || $isAdmin;

// Avatar color palette
$palettes = [
  ['#FF6B35', '#fff5f0'],
  ['#8b5cf6', '#f5f3ff'],
  ['#22c55e', '#f0fdf4'],
  ['#3b82f6', '#eff6ff'],
  ['#f59e0b', '#fffbeb'],
  ['#ec4899', '#fdf2f8'],
];
$pal = $user ? $palettes[($user['id'] - 1) % count($palettes)] : $palettes[0];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf" content="<?= csrfToken() ?>">
  <title><?= e($pageTitle ?? APP_NAME) ?></title>
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <?= $extraHead ?? '' ?>
</head>

<body>

  <nav class="navbar">
    <a href="<?= APP_URL ?>/index.php" class="nav-logo">
      Yummy<span>Spot</span>
    </a>

    <!-- Hamburger mobile -->
    <button class="nav-icon-btn" id="hamburger-btn" onclick="openDrawer()" style="display:none;" title="Menu">
      <i class="fa-solid fa-bars"></i>
    </button>

    <form class="nav-search" action="<?= APP_URL ?>/explore.php" method="GET">
      <i class="fa-solid fa-magnifying-glass si"></i>
      <input type="search" name="q" placeholder="Cari tempat, kuliner, wisata..." value="<?= e($_GET['q'] ?? '') ?>">
    </form>

    <div class="nav-actions">
      <?php if ($user): ?>

        <?php if (!$isDash): ?>
          <button class="nav-icon-btn" onclick="openModal('create-post-modal')" title="Buat Postingan">
            <i class="fa-solid fa-square-plus"></i>
          </button>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/notifications.php" class="nav-icon-btn" title="Notifikasi">
          <i class="fa-regular fa-bell"></i>
          <?php if ($notifCnt > 0): ?>
            <span class="nav-badge"><?= $notifCnt > 9 ? '9+' : $notifCnt ?></span>
          <?php endif; ?>
        </a>

        <div class="user-menu">
          <div class="user-trigger" data-dd="user-dd">
            <div class="avatar av-36" style="background:<?= $pal[1] ?>;color:<?= $pal[0] ?>">
              <?php if ($user['profile_picture']): ?>
                <img src="<?= e($user['profile_picture']) ?>" alt="">
              <?php else: ?>
                <?= initials($user['fullname']) ?>
              <?php endif; ?>
            </div>
            <span class="role-badge role-<?= $user['role'] ?>"><?= match ($user['role']) {
                                                                  'admin' => 'Admin',
                                                                  'cs' => 'CS',
                                                                  'owner' => 'Pemilik',
                                                                  default => 'User'
                                                                } ?></span>
            <i class="fa-solid fa-chevron-down fa-xs text-dim"></i>
          </div>
          <div class="user-dd" id="user-dd">
            <div style="padding:.5rem .7rem .4rem;border-bottom:1px solid var(--border);margin-bottom:.25rem">
              <div style="font-weight:700;font-size:.87rem"><?= e($user['fullname']) ?></div>
              <div style="font-size:.73rem;color:var(--text3)">@<?= e($user['username']) ?></div>
            </div>
            <?php if (!$isDash): ?>
              <a href="<?= APP_URL ?>/profile.php" class="dd-item"><i class="fa-regular fa-user fa-fw"></i> Profil Saya</a>
              <a href="<?= APP_URL ?>/wishlist.php" class="dd-item"><i class="fa-regular fa-bookmark fa-fw"></i> Wishlist</a>
              <a href="<?= APP_URL ?>/my-reports.php" class="dd-item"><i class="fa-regular fa-flag fa-fw"></i> Laporan Saya</a>
              <a href="<?= APP_URL ?>/contact.php" class="dd-item"><i class="fa-solid fa-headset fa-fw"></i> Hubungi Kami</a>
            <?php endif; ?>
            <?php if ($isOwner): ?>
              <a href="<?= APP_URL ?>/owner/dashboard.php" class="dd-item"><i class="fa-solid fa-store fa-fw"></i> Dashboard Pemilik</a>
            <?php elseif ($isCS): ?>
              <a href="<?= APP_URL ?>/cs/dashboard.php" class="dd-item"><i class="fa-solid fa-shield-halved fa-fw"></i> CS Panel</a>
            <?php elseif ($isAdmin): ?>
              <a href="<?= APP_URL ?>/admin/dashboard.php" class="dd-item"><i class="fa-solid fa-bolt fa-fw"></i> Admin Panel</a>
            <?php endif; ?>
            <div class="dd-sep"></div>
            <a href="<?= APP_URL ?>/logout.php" class="dd-item red"><i class="fa-solid fa-right-from-bracket fa-fw"></i> Keluar</a>
          </div>
        </div>

      <?php else: ?>
        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline btn-sm">Masuk</a>
        <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-sm">Daftar</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- ── Mobile Sidebar Overlay ─────────────────── -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeDrawer()"></div>

  <!-- ── Mobile Sidebar Drawer ──────────────────── -->
  <div class="sidebar-drawer" id="sidebar-drawer">
    <button class="drawer-close" onclick="closeDrawer()">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div style="padding:.25rem .5rem 1.25rem;border-bottom:1px solid var(--border);margin-bottom:.75rem;">
      <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.2rem;color:var(--accent);">
        Yummy<span style="color:var(--text)">Spot</span>
      </div>
    </div>

    <?php if ($user): ?>
      <!-- User info -->
      <div style="display:flex;align-items:center;gap:.65rem;padding:.5rem .65rem;margin-bottom:.5rem;">
        <div class="avatar av-36" style="background:<?= $pal[1] ?>;color:<?= $pal[0] ?>;">
          <?php if ($user['profile_picture']): ?>
            <img src="<?= e($user['profile_picture']) ?>" alt="">
          <?php else: ?>
            <?= initials($user['fullname']) ?>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($user['fullname']) ?></div>
          <div style="font-size:.72rem;color:var(--text3);">@<?= e($user['username']) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Nav items — berbeda per role -->
    <?php if ($isAdmin): ?>
      <!-- ── ADMIN DRAWER ───────────────────── -->
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=overview" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-gauge si"></i> Overview</a>
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=users" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-users si"></i> Pengguna</a>
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=catalogs" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-store si"></i> Katalog</a>
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=posts" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-image si"></i> Postingan</a>
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=categories" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-layer-group si"></i> Kategori</a>
      <a href="<?= APP_URL ?>/admin/dashboard.php?tab=reports" class="sb-item" onclick="closeDrawer()">
        <i class="fa-solid fa-flag si"></i> Laporan
        <?php if ($notifCnt > 0): ?><span class="sb-count"><?= $notifCnt ?></span><?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/admin/manage-cs.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-shield-halved si"></i> Kelola Tim CS</a>
      <div class="dd-sep"></div>
      <a href="<?= APP_URL ?>/logout.php" class="sb-item" style="color:var(--red);" onclick="closeDrawer()"><i class="fa-solid fa-right-from-bracket si"></i> Keluar</a>

    <?php elseif ($isCS): ?>
      <!-- ── CS DRAWER ─────────────────────── -->
      <a href="<?= APP_URL ?>/cs/dashboard.php?tab=catalogs" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-store si"></i> Semua Katalog</a>
      <a href="<?= APP_URL ?>/cs/dashboard.php?tab=reports" class="sb-item" onclick="closeDrawer()">
        <i class="fa-solid fa-flag si"></i> Laporan
        <?php if ($notifCnt > 0): ?><span class="sb-count"><?= $notifCnt ?></span><?php endif; ?>
      </a>
      <div class="dd-sep"></div>
      <a href="<?= APP_URL ?>/logout.php" class="sb-item" style="color:var(--red);" onclick="closeDrawer()"><i class="fa-solid fa-right-from-bracket si"></i> Keluar</a>

    <?php elseif ($isOwner): ?>
      <!-- ── OWNER DRAWER ──────────────────── -->
      <a href="<?= APP_URL ?>/owner/dashboard.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/owner/catalogs.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-store si"></i> Katalog Saya</a>
      <a href="<?= APP_URL ?>/owner/catalog-create.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
      <a href="<?= APP_URL ?>/owner/reviews.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-star si"></i> Ulasan</a>
      <a href="<?= APP_URL ?>/owner/analytics.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
      <div class="dd-sep"></div>
      <a href="<?= APP_URL ?>/notifications.php" class="sb-item" onclick="closeDrawer()">
        <i class="fa-regular fa-bell si"></i> Notifikasi
        <?php if ($notifCnt > 0): ?><span class="sb-count"><?= $notifCnt ?></span><?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/profile.php" class="sb-item" onclick="closeDrawer()"><i class="fa-regular fa-user si"></i> Profil Saya</a>
      <div class="dd-sep"></div>
      <a href="<?= APP_URL ?>/logout.php" class="sb-item" style="color:var(--red);" onclick="closeDrawer()"><i class="fa-solid fa-right-from-bracket si"></i> Keluar</a>

    <?php elseif ($user): ?>
      <!-- ── USER DRAWER ───────────────────── -->
      <a href="<?= APP_URL ?>/index.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-house si"></i> Beranda</a>
      <a href="<?= APP_URL ?>/explore.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-compass si"></i> Eksplorasi</a>
      <a href="<?= APP_URL ?>/catalog.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-map-pin si"></i> Katalog Tempat</a>
      <a href="<?= APP_URL ?>/wishlist.php" class="sb-item" onclick="closeDrawer()"><i class="fa-regular fa-bookmark si"></i> Wishlist</a>
      <a href="<?= APP_URL ?>/notifications.php" class="sb-item" onclick="closeDrawer()">
        <i class="fa-regular fa-bell si"></i> Notifikasi
        <?php if ($notifCnt > 0): ?><span class="sb-count"><?= $notifCnt ?></span><?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/profile.php" class="sb-item" onclick="closeDrawer()"><i class="fa-regular fa-user si"></i> Profil Saya</a>
      <a href="<?= APP_URL ?>/my-reports.php" class="sb-item" onclick="closeDrawer()"><i class="fa-regular fa-flag si"></i> Laporan Saya</a>
      <a href="<?= APP_URL ?>/contact.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-headset si"></i> Hubungi Kami</a>
      <div class="dd-sep"></div>
      <!-- Kategori -->
      <div class="sb-label">Kategori</div>
      <?php
      $dcats = getDB()->query("SELECT * FROM categories ORDER BY id LIMIT 8")->fetchAll();
      foreach ($dcats as $dc): ?>
        <a href="<?= APP_URL ?>/catalog.php?cat=<?= $dc['id'] ?>" class="sb-item" onclick="closeDrawer()" style="font-size:.8rem;">
          <i class="fa-solid <?= e($dc['icon']) ?> si"></i> <?= e($dc['name']) ?>
        </a>
      <?php endforeach; ?>
      <div class="dd-sep"></div>
      <a href="<?= APP_URL ?>/logout.php" class="sb-item" style="color:var(--red);" onclick="closeDrawer()"><i class="fa-solid fa-right-from-bracket si"></i> Keluar</a>

    <?php else: ?>
      <!-- ── GUEST DRAWER ──────────────────── -->
      <a href="<?= APP_URL ?>/index.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-house si"></i> Beranda</a>
      <a href="<?= APP_URL ?>/explore.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-compass si"></i> Eksplorasi</a>
      <a href="<?= APP_URL ?>/catalog.php" class="sb-item" onclick="closeDrawer()"><i class="fa-solid fa-map-pin si"></i> Katalog Tempat</a>
      <div class="dd-sep"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-top:.5rem;">
        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline btn-sm" style="justify-content:center;">Masuk</a>
        <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-sm" style="justify-content:center;">Daftar</a>
      </div>
    <?php endif; ?>
  </div>