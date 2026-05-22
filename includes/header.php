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
  ['#FF6B35','#fff5f0'], ['#8b5cf6','#f5f3ff'], ['#22c55e','#f0fdf4'],
  ['#3b82f6','#eff6ff'], ['#f59e0b','#fffbeb'], ['#ec4899','#fdf2f8'],
];
$pal = $user ? $palettes[($user['id']-1) % count($palettes)] : $palettes[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf" content="<?= csrfToken() ?>">
<title><?= e($pageTitle ?? APP_NAME) ?></title>
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
          <span class="role-badge role-<?= $user['role'] ?>"><?= match($user['role']){'admin'=>'Admin','cs'=>'CS','owner'=>'Pemilik',default=>'User'} ?></span>
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
