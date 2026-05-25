<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();

// CS dan Admin redirect ke panel masing-masing — tidak boleh akses feed
if (isLoggedIn()) {
    $r = currentUser()['role'];
    if ($r === 'cs')    redirect(APP_URL . '/cs/dashboard.php');
    if ($r === 'admin') redirect(APP_URL . '/admin/dashboard.php');
}

$pageTitle = 'Beranda — YummySpot';
require_once __DIR__ . '/includes/header.php';

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$lmt  = FEED_PER_PAGE;
$off  = ($page - 1) * $lmt;
$flt  = $_GET['filter'] ?? 'all';

// Posts query
if ($user && $flt === 'following') {
    $st = $db->prepare("
        SELECT p.*, u.fullname, u.username, u.profile_picture,
               c.name AS cat_name, c.slug AS cat_slug,
               (SELECT COUNT(*) FROM likes WHERE post_id=p.id) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id=p.id) AS cmt_count,
               (SELECT COUNT(*) FROM likes WHERE post_id=p.id AND user_id={$user['id']}) AS is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN catalogs c ON p.catalog_id = c.id
        JOIN follows f ON p.user_id = f.following_id AND f.follower_id = ?
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $st->execute([$user['id'], $lmt, $off]);
} else {
    $isLikedSql = $user ? "(SELECT COUNT(*) FROM likes WHERE post_id=p.id AND user_id={$user['id']})" : "0";
    $st = $db->prepare("
        SELECT p.*, u.fullname, u.username, u.profile_picture,
               c.name AS cat_name, c.slug AS cat_slug,
               (SELECT COUNT(*) FROM likes WHERE post_id=p.id) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id=p.id) AS cmt_count,
               $isLikedSql AS is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN catalogs c ON p.catalog_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $st->execute([$lmt, $off]);
}
$posts = $st->fetchAll();

// Stories (users yang posting dalam 24 jam terakhir)
$stories = $db->query("
    SELECT DISTINCT u.id, u.username, u.fullname, u.profile_picture
    FROM users u
    JOIN posts p ON p.user_id = u.id
    WHERE p.status = 'published' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    LIMIT 10
")->fetchAll();

// Trending catalogs
$trending = $db->query("
    SELECT * FROM catalogs
    WHERE verification_status = 'approved'
    ORDER BY total_likes DESC, avg_rating DESC LIMIT 5
")->fetchAll();

// Suggested users
$suggested = [];
if ($user) {
    $suSt = $db->prepare("
        SELECT id, username, fullname, profile_picture
        FROM users
        WHERE id != ? AND role = 'user' AND status = 'active'
          AND id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
        ORDER BY RAND() LIMIT 4
    ");
    $suSt->execute([$user['id'], $user['id']]);
    $suggested = $suSt->fetchAll();
}

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:640px; margin:0 auto;">

  <!-- Stories — hanya tampil jika ada konten -->
  <?php if ($user || !empty($stories)): ?>
  <div class="card" style="margin-bottom:1rem;padding:.85rem 1rem;">
    <div class="stories-wrap">
      <?php if ($user): ?>
      <div class="story-item" onclick="openModal('create-post-modal')">
        <div class="story-ring" style="background:var(--accent);">
          <div class="story-avatar" style="display:flex;align-items:center;justify-content:center;background:var(--accent-bg);">
            <i class="fa-solid fa-plus" style="color:var(--accent);font-size:.9rem;"></i>
          </div>
        </div>
        <div class="story-name">Kamu</div>
      </div>
      <?php endif; ?>
      <?php foreach ($stories as $s):
        $sp = $pals[($s['id'] - 1) % count($pals)]; ?>
      <a href="<?= APP_URL ?>/profile.php?u=<?= e($s['username']) ?>" class="story-item">
        <div class="story-ring">
          <div class="story-avatar" style="background:<?= $sp[1] ?>;color:<?= $sp[0] ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-family:'Nunito',sans-serif;">
            <?php if ($s['profile_picture']): ?>
              <img src="<?= e($s['profile_picture']) ?>" alt="">
            <?php else: ?>
              <?= initials($s['fullname']) ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="story-name"><?= e($s['username']) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter tabs -->
  <div style="display:flex;gap:.4rem;margin-bottom:1rem;">
    <a href="?filter=all"       class="btn btn-sm <?= $flt !== 'following' ? 'btn-primary' : 'btn-outline' ?>">
      <i class="fa-solid fa-globe fa-xs"></i> Semua
    </a>
    <?php if ($user): ?>
    <a href="?filter=following" class="btn btn-sm <?= $flt === 'following' ? 'btn-primary' : 'btn-outline' ?>">
      <i class="fa-solid fa-users fa-xs"></i> Mengikuti
    </a>
    <?php endif; ?>
  </div>

  <!-- Posts -->
  <?php if (empty($posts)): ?>
  <div class="empty">
    <div class="e-icon"><i class="fa-regular fa-image"></i></div>
    <h3>Belum ada postingan</h3>
    <p>Jelajahi katalog atau ikuti pengguna lain.</p>
    <a href="explore.php" class="btn btn-primary mt-2">Eksplorasi Sekarang</a>
  </div>
  <?php else: ?>

  <?php foreach ($posts as $p):
    $pp = $pals[($p['user_id'] - 1) % count($pals)]; ?>
  <div class="post-card">
    <!-- Header -->
    <div class="post-head">
      <div class="avatar av-44" style="background:<?= $pp[1] ?>;color:<?= $pp[0] ?>;">
        <?php if ($p['profile_picture']): ?>
          <img src="<?= e($p['profile_picture']) ?>" alt="">
        <?php else: ?>
          <?= initials($p['fullname']) ?>
        <?php endif; ?>
      </div>
      <div class="post-meta flex-1">
        <div class="pname">
          <a href="profile.php?u=<?= e($p['username']) ?>"><?= e($p['fullname']) ?></a>
          <?php if ($p['post_type'] === 'business'): ?>
          <span class="badge badge-warning" style="font-size:.6rem;margin-left:.35rem;">
            <i class="fa-solid fa-store fa-xs"></i> Bisnis
          </span>
          <?php endif; ?>
        </div>
        <div class="pmeta">
          <i class="fa-regular fa-clock fa-xs"></i> <?= timeAgo($p['created_at']) ?>
          <?php if ($p['cat_name']): ?>
            &middot; <i class="fa-solid fa-location-dot fa-xs" style="color:var(--accent)"></i>
            <a href="catalog-detail.php?slug=<?= e($p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a>
          <?php endif; ?>
        </div>
      </div>
      <!-- Post options dropdown -->
      <div style="position:relative;">
        <button class="btn btn-ghost btn-icon btn-sm text-dim"
          onclick="togglePostMenu(<?= $p['id'] ?>, event)"
          title="Opsi">
          <i class="fa-solid fa-ellipsis"></i>
        </button>
        <div id="post-menu-<?= $p['id'] ?>" style="
          display:none;position:absolute;right:0;top:calc(100% + 6px);
          background:#fff;border:1px solid var(--border);border-radius:var(--r);
          min-width:160px;box-shadow:0 4px 20px rgba(0,0,0,.12);z-index:200;
          overflow:hidden;">
          <button onclick="copyPostLink(<?= $p['id'] ?>)" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;background:transparent;border:none;cursor:pointer;font-size:.83rem;color:var(--text2);text-align:left;transition:background .15s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-link fa-xs" style="color:var(--text3);width:14px;"></i> Salin Link
          </button>
          <a href="post.php?id=<?= $p['id'] ?>" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;font-size:.83rem;color:var(--text2);text-decoration:none;transition:background .15s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-expand fa-xs" style="color:var(--text3);width:14px;"></i> Lihat Detail
          </a>
          <?php if ($user && $user['id'] == $p['user_id']): ?>
          <div style="height:1px;background:var(--border);margin:.2rem 0;"></div>
          <button onclick="confirmDeletePost(<?= $p['id'] ?>)" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;background:transparent;border:none;cursor:pointer;font-size:.83rem;color:var(--red);text-align:left;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-trash fa-xs" style="width:14px;"></i> Hapus Postingan
          </button>
          <?php elseif ($user && $user['id'] != $p['user_id']): ?>
          <div style="height:1px;background:var(--border);margin:.2rem 0;"></div>
          <a href="report.php?type=post&id=<?= $p['id'] ?>" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;font-size:.83rem;color:var(--red);text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
            <i class="fa-regular fa-flag fa-xs" style="width:14px;"></i> Laporkan
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Image -->
    <?php if ($p['image']): ?>
    <img src="<?= e($p['image']) ?>" alt="" class="post-img">
    <?php endif; ?>

    <!-- Caption -->
    <div class="post-body">
      <?php if ($p['caption']): ?>
      <div class="post-caption"><?= nl2br(e($p['caption'])) ?></div>
      <?php endif; ?>
      <?php if ($p['cat_name']): ?>
      <a href="catalog-detail.php?slug=<?= e($p['cat_slug']) ?>" class="post-mention">
        <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($p['cat_name']) ?>
      </a>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="post-actions">
      <?php if ($user): ?>
      <button class="act-btn <?= $p['is_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $p['id'] ?>, this)">
        <i class="fa-<?= $p['is_liked'] ? 'solid' : 'regular' ?> fa-heart"></i>
        <span class="lcount"><?= fmtNum($p['like_count']) ?></span>
      </button>
      <?php else: ?>
      <a href="login.php" class="act-btn">
        <i class="fa-regular fa-heart"></i> <?= fmtNum($p['like_count']) ?>
      </a>
      <?php endif; ?>

      <a href="post.php?id=<?= $p['id'] ?>" class="act-btn">
        <i class="fa-regular fa-comment"></i> <?= fmtNum($p['cmt_count']) ?>
      </a>

      <button class="act-btn" style="margin-left:auto;"
        onclick="navigator.clipboard.writeText('<?= APP_URL ?>/post.php?id=<?= $p['id'] ?>').then(()=>toast('Link disalin!'))">
        <i class="fa-solid fa-share-nodes"></i>
      </button>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Pagination -->
  <div style="display:flex;gap:.5rem;justify-content:center;margin-top:.5rem;">
    <?php if ($page > 1): ?>
    <a href="?filter=<?= $flt ?>&page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-chevron-left fa-xs"></i> Sebelumnya
    </a>
    <?php endif; ?>
    <?php if (count($posts) === $lmt): ?>
    <a href="?filter=<?= $flt ?>&page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">
      Berikutnya <i class="fa-solid fa-chevron-right fa-xs"></i>
    </a>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</main>

<!-- Right Panel -->
<aside class="right-panel">
  <?php if ($user): ?>
  <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <?php $mp = $pals[($user['id'] - 1) % count($pals)]; ?>
    <div class="avatar av-44" style="background:<?= $mp[1] ?>;color:<?= $mp[0] ?>;">
      <?php if ($user['profile_picture']): ?>
        <img src="<?= e($user['profile_picture']) ?>" alt="">
      <?php else: ?>
        <?= initials($user['fullname']) ?>
      <?php endif; ?>
    </div>
    <div style="flex:1;min-width:0;">
      <div style="font-weight:700;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($user['fullname']) ?></div>
      <div style="font-size:.73rem;color:var(--text3);">@<?= e($user['username']) ?></div>
    </div>
    <a href="profile.php" class="btn btn-outline btn-sm">Profil</a>
  </div>
  <?php endif; ?>

  <!-- Trending -->
  <div style="margin-bottom:1.5rem;">
    <div class="panel-ttl"><i class="fa-solid fa-fire" style="color:var(--accent)"></i> Trending Tempat</div>
    <?php if (empty($trending)): ?>
    <div style="text-align:center;padding:1.25rem .5rem;color:var(--text3);">
      <i class="fa-solid fa-map-pin fa-2x" style="opacity:.25;margin-bottom:.5rem;display:block;"></i>
      <div style="font-size:.78rem;">Belum ada katalog.</div>
      <a href="explore.php" style="font-size:.75rem;color:var(--accent);font-weight:600;margin-top:.35rem;display:inline-block;">Jelajahi Tempat</a>
    </div>
    <?php else: ?>
    <?php foreach ($trending as $t): ?>
    <a href="catalog-detail.php?slug=<?= e($t['slug']) ?>" class="trend-item">
      <div class="trend-thumb">
        <?php if ($t['thumbnail']): ?>
          <img src="<?= e($t['thumbnail']) ?>" alt="">
        <?php else: ?>
          <i class="fa-solid fa-map-pin" style="color:var(--accent)"></i>
        <?php endif; ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div class="trend-name truncate"><?= e($t['name']) ?></div>
        <div class="trend-meta">
          <i class="fa-solid fa-star fa-xs" style="color:var(--amber)"></i>
          <?= number_format($t['avg_rating'], 1) ?> &middot; <?= fmtNum($t['total_reviews']) ?> ulasan
        </div>
      </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Suggested users -->
  <?php if ($suggested): ?>
  <div>
    <div class="panel-ttl"><i class="fa-solid fa-user-plus" style="color:var(--blue)"></i> Disarankan</div>
    <?php foreach ($suggested as $su):
      $sp = $pals[($su['id'] - 1) % count($pals)]; ?>
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.65rem;">
      <div class="avatar av-36" style="background:<?= $sp[1] ?>;color:<?= $sp[0] ?>;">
        <?php if ($su['profile_picture']): ?>
          <img src="<?= e($su['profile_picture']) ?>" alt="">
        <?php else: ?>
          <?= initials($su['fullname']) ?>
        <?php endif; ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.82rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($su['fullname']) ?></div>
        <div style="font-size:.7rem;color:var(--text3);">@<?= e($su['username']) ?></div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="toggleFollow(<?= $su['id'] ?>, this)">Ikuti</button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</aside>

</div>
<script>
function togglePostMenu(id, e) {
  e.stopPropagation();
  document.querySelectorAll('[id^="post-menu-"]').forEach(m => {
    if (m.id !== 'post-menu-' + id) m.style.display = 'none';
  });
  const m = document.getElementById('post-menu-' + id);
  m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', () => {
  document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.style.display = 'none');
});
function copyPostLink(id) {
  navigator.clipboard.writeText('<?= APP_URL ?>/post.php?id=' + id)
    .then(() => toast('Link berhasil disalin!'));
  document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.style.display = 'none');
}
function confirmDeletePost(id) {
  if (!confirm('Hapus postingan ini? Tindakan tidak bisa dibatalkan.')) return;
  fetch('<?= APP_URL ?>/actions/post_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'post_id=' + id + '&csrf_token=' + getCsrf()
  }).then(r => r.json()).then(d => {
    if (d.ok) {
      const card = document.getElementById('post-' + id);
      if (card) {
        card.style.transition = 'opacity .3s';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 300);
      }
      toast('Postingan berhasil dihapus');
    } else {
      toast(d.msg || 'Gagal menghapus', 'err');
    }
  });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
