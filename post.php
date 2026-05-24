<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
$user = currentUser();

// Smart back URL - jangan kembali ke halaman yang sama
$_referer = $_SERVER['HTTP_REFERER'] ?? '';
$_self    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
// Kalau referrer adalah halaman ini sendiri (setelah submit komentar), abaikan
if ($_referer && strpos($_referer, 'post.php') === false) {
    $backUrl = $_referer;
} else {
    $backUrl = route('home');
}

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) redirect(route('home'));

$db = getDB();
$st = $db->prepare("
    SELECT p.*, u.fullname, u.username, u.profile_picture,
           c.name AS cat_name, c.slug AS cat_slug
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN catalogs c ON p.catalog_id = c.id
    WHERE p.id = ? AND p.status = 'published'
");
$st->execute([$postId]);
$post = $st->fetch();
if (!$post) { http_response_code(404); die('Postingan tidak ditemukan.'); }

$pageTitle = 'Postingan ' . $post['fullname'] . ' — YummySpot';
// Like & comment counts
$lcSt = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");    $lcSt->execute([$postId]); $likeCount = (int)$lcSt->fetchColumn();
$ccSt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?"); $ccSt->execute([$postId]); $cmtCount  = (int)$ccSt->fetchColumn();

$isLiked = false;
if ($user) {
    $lk = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
    $lk->execute([$postId, $user['id']]);
    $isLiked = (bool)$lk->fetchColumn();
}

// Handle comment submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    requireLogin();
    verifyCsrf();
    $content   = trim($_POST['comment'] ?? '');
    $parentId  = (int)($_POST['parent_id'] ?? 0) ?: null;
    if ($content) {
        $db->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?,?,?)")
           ->execute([$user['id'], $postId, $content]);
        if ($post['user_id'] !== $user['id']) {
            createNotif($post['user_id'], $user['id'], 'comment', $postId, $user['fullname'] . ' mengomentari postingan Anda');
        }
        flash('success', 'Komentar ditambahkan!');
        // Kembali ke halaman asal, bukan ke post itu sendiri
        redirect($backUrl);
    }
}

// Fetch comments
$cmts = $db->prepare("
    SELECT c.*, u.fullname, u.username, u.profile_picture
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
    LIMIT 50
");
$cmts->execute([$postId]);
$comments = $cmts->fetchAll();

// Related posts (same catalog or same user)
$related = $db->prepare("
    SELECT p.*, u.fullname, u.username
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id != ? AND p.status = 'published'
      AND (p.user_id = ? OR p.catalog_id = ?)
    ORDER BY p.created_at DESC
    LIMIT 6
");
$related->execute([$postId, $post['user_id'], $post['catalog_id']]);
$relPosts = $related->fetchAll();

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
$pp   = $pals[($post['user_id'] - 1) % count($pals)];

require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:640px; margin:0 auto;">

    <!-- Back -->
    <div style="margin-bottom:.85rem;">
        <a href="<?= e($backUrl) ?>" class="btn btn-ghost btn-sm text-dim">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Kembali
        </a>
    </div>

    <!-- Post Card -->
    <div class="post-card" style="margin-bottom:.85rem;">
        <!-- Header -->
        <div class="post-head">
            <div class="avatar av-44" style="background:<?= $pp[1] ?>;color:<?= $pp[0] ?>;">
                <?php if ($post['profile_picture']): ?>
                    <img src="<?= e($post['profile_picture']) ?>" alt="">
                <?php else: ?>
                    <?= initials($post['fullname']) ?>
                <?php endif; ?>
            </div>
            <div class="post-meta flex-1">
                <div class="pname">
                    <a href="profile.php?u=<?= e($post['username']) ?>"><?= e($post['fullname']) ?></a>
                    <?php if ($post['post_type'] === 'business'): ?>
                    <span class="badge badge-warning" style="font-size:.6rem;margin-left:.35rem;">
                        <i class="fa-solid fa-store fa-xs"></i> Bisnis
                    </span>
                    <?php endif; ?>
                </div>
                <div class="pmeta">
                    <i class="fa-regular fa-clock fa-xs"></i> <?= timeAgo($post['created_at']) ?>
                    <?php if ($post['cat_name']): ?>
                        &middot; <i class="fa-solid fa-location-dot fa-xs" style="color:var(--accent)"></i>
                        <a href="catalog-detail.php?slug=<?= e($post['cat_slug']) ?>"><?= e($post['cat_name']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="position:relative;">
              <button class="btn btn-ghost btn-icon btn-sm text-dim"
                onclick="togglePostMenu('d', event)" title="Opsi">
                <i class="fa-solid fa-ellipsis"></i>
              </button>
              <div id="post-menu-d" style="display:none;position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--border);border-radius:var(--r);min-width:160px;box-shadow:0 4px 20px rgba(0,0,0,.12);z-index:200;overflow:hidden;">
                <button onclick="navigator.clipboard.writeText(window.location.href).then(()=>toast('Link disalin!'))" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;background:transparent;border:none;cursor:pointer;font-size:.83rem;color:var(--text2);text-align:left;transition:background .15s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                  <i class="fa-solid fa-link fa-xs" style="color:var(--text3);width:14px;"></i> Salin Link
                </button>
                <?php if ($user && $user['id'] == $post['user_id']): ?>
                <div style="height:1px;background:var(--border);margin:.2rem 0;"></div>
                <button onclick="confirmDeletePost(<?= $post['id'] ?>)" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;background:transparent;border:none;cursor:pointer;font-size:.83rem;color:var(--red);text-align:left;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                  <i class="fa-solid fa-trash fa-xs" style="width:14px;"></i> Hapus Postingan
                </button>
                <?php elseif ($user): ?>
                <div style="height:1px;background:var(--border);margin:.2rem 0;"></div>
                <a href="<?= APP_URL ?>/report.php?type=post&id=<?= $post['id'] ?>" style="display:flex;align-items:center;gap:.55rem;width:100%;padding:.6rem .85rem;font-size:.83rem;color:var(--red);text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                  <i class="fa-regular fa-flag fa-xs" style="width:14px;"></i> Laporkan
                </a>
                <?php endif; ?>
              </div>
            </div>
        </div>

        <!-- Image -->
        <?php if ($post['image']): ?>
        <img src="<?= e($post['image']) ?>" alt="" class="post-img">
        <?php endif; ?>

        <!-- Caption -->
        <?php if ($post['caption']): ?>
        <div class="post-body">
            <div class="post-caption"><?= nl2br(e($post['caption'])) ?></div>
            <?php if ($post['cat_name']): ?>
            <a href="catalog-detail.php?slug=<?= e($post['cat_slug']) ?>" class="post-mention">
                <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($post['cat_name']) ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="post-actions">
            <?php if ($user): ?>
            <button class="act-btn <?= $isLiked ? 'liked' : '' ?>" id="like-btn" onclick="toggleLike(<?= $postId ?>, this)">
                <i class="fa-<?= $isLiked ? 'solid' : 'regular' ?> fa-heart"></i>
                <span class="lcount"><?= fmtNum($likeCount) ?></span>
            </button>
            <?php else: ?>
            <a href="login.php" class="act-btn">
                <i class="fa-regular fa-heart"></i> <?= fmtNum($likeCount) ?>
            </a>
            <?php endif; ?>

            <span class="act-btn" style="cursor:default;">
                <i class="fa-regular fa-comment"></i> <?= fmtNum($cmtCount) ?>
            </span>

            <button class="act-btn" style="margin-left:auto;"
                onclick="navigator.clipboard.writeText(window.location.href).then(()=>toast('Link disalin!'))">
                <i class="fa-solid fa-share-nodes"></i>
            </button>
            <?php if ($user && $user['id'] != $post['user_id']): ?>
            <a href="<?= APP_URL ?>/report.php?type=post&id=<?= $post['id'] ?>" class="act-btn" style="color:var(--text3);" title="Laporkan">
                <i class="fa-regular fa-flag"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="card" id="comments">
        <div class="card-header">
            <span class="card-title">
                <i class="fa-regular fa-comment" style="color:var(--accent)"></i>
                Komentar (<?= $cmtCount ?>)
            </span>
        </div>
        <div class="card-body">

            <!-- Comment form -->
            <?php if ($user): ?>
            <form method="POST" style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:1.1rem;">
                <?= csrfField() ?>
                <?php $mp = $pals[($user['id']-1) % count($pals)]; ?>
                <div class="avatar av-36" style="background:<?= $mp[1] ?>;color:<?= $mp[0] ?>;flex-shrink:0;">
                    <?php if ($user['profile_picture']): ?>
                        <img src="<?= e($user['profile_picture']) ?>" alt="">
                    <?php else: ?>
                        <?= initials($user['fullname']) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <textarea name="comment" class="form-control" rows="2"
                        placeholder="Tulis komentar..." data-autoresize required
                        style="min-height:60px;font-size:.85rem;"></textarea>
                    <div style="display:flex;justify-content:flex-end;margin-top:.4rem;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-paper-plane fa-xs"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:1rem;">
                <i class="fa-solid fa-circle-info"></i>
                <a href="login.php" style="color:var(--blue);font-weight:700;">Masuk</a> untuk menulis komentar.
            </div>
            <?php endif; ?>

            <!-- Comments list -->
            <?php if (empty($comments)): ?>
            <div class="empty" style="padding:1.5rem;">
                <div class="e-icon" style="font-size:1.5rem;"><i class="fa-regular fa-comment"></i></div>
                <h3 style="font-size:.875rem;">Belum ada komentar</h3>
                <p>Jadilah yang pertama berkomentar!</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:.85rem;">
                <?php foreach ($comments as $c):
                    $cp = $pals[($c['user_id'] - 1) % count($pals)];
                ?>
                <div style="display:flex;gap:.65rem;">
                    <div class="avatar av-36" style="background:<?= $cp[1] ?>;color:<?= $cp[0] ?>;flex-shrink:0;">
                        <?php if ($c['profile_picture']): ?>
                            <img src="<?= e($c['profile_picture']) ?>" alt="">
                        <?php else: ?>
                            <?= initials($c['fullname']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="background:var(--bg);border-radius:0 var(--r) var(--r) var(--r);padding:.6rem .85rem;">
                            <div style="font-weight:700;font-size:.83rem;margin-bottom:.2rem;">
                                <a href="profile.php?u=<?= e($c['username']) ?>" style="color:var(--text);">
                                    <?= e($c['fullname']) ?>
                                </a>
                            </div>
                            <div style="font-size:.85rem;color:var(--text2);line-height:1.6;">
                                <?= nl2br(e($c['comment'])) ?>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:.65rem;margin-top:.3rem;padding-left:.3rem;">
                            <span style="font-size:.7rem;color:var(--text3);">
                                <i class="fa-regular fa-clock fa-xs"></i> <?= timeAgo($c['created_at']) ?>
                            </span>
                            <?php if ($user): ?>
                            <button class="btn btn-ghost btn-sm text-dim"
                                style="font-size:.72rem;padding:.1rem .35rem;"
                                onclick="toggleReplyBox(<?= $c['id'] ?>)">
                                <i class="fa-solid fa-reply fa-xs"></i> Balas
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Reply form -->
                        <?php if ($user): ?>
                        <form method="POST" id="reply-<?= $c['id'] ?>" style="display:none;margin-top:.5rem;">
                            <?= csrfField() ?>
                            <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                            <div style="display:flex;gap:.4rem;align-items:flex-start;">
                                <textarea name="comment" class="form-control" rows="1"
                                    placeholder="Balas komentar..." data-autoresize required
                                    style="font-size:.82rem;min-height:48px;"></textarea>
                                <div style="display:flex;flex-direction:column;gap:.25rem;flex-shrink:0;">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-paper-plane fa-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline btn-sm text-dim"
                                        onclick="toggleReplyBox(<?= $c['id'] ?>)">
                                        <i class="fa-solid fa-xmark fa-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Posts -->
    <?php if ($relPosts): ?>
    <div style="margin-top:1rem;">
        <h2 style="font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:900;margin-bottom:.75rem;">
            <i class="fa-solid fa-images" style="color:var(--accent)"></i> Postingan Lainnya
        </h2>
        <div class="ig-grid" style="border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--border);">
            <?php foreach ($relPosts as $rp): ?>
            <a href="post.php?id=<?= $rp['id'] ?>" class="ig-cell">
                <?php if ($rp['image']): ?>
                    <img src="<?= e($rp['image']) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);">
                        <i class="fa-regular fa-image fa-2x text-dim"></i>
                    </div>
                <?php endif; ?>
                <div class="ig-over">
                    <i class="fa-solid fa-heart fa-xs"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- Right Panel -->
<aside class="right-panel">
    <!-- Post author info -->
    <?php
    $authorSt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $authorSt->execute([$post['user_id']]);
    $author = $authorSt->fetch();
    $fc2 = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?"); $fc2->execute([$post['user_id']]); $fCount = (int)$fc2->fetchColumn();
    $pc2 = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status='published'"); $pc2->execute([$post['user_id']]); $pCount = (int)$pc2->fetchColumn();
    $ap  = $pals[($author['id']-1) % count($pals)];
    $isFollowingAuthor = false;
    if ($user && $user['id'] != $author['id']) {
        $ifSt = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=? AND following_id=?");
        $ifSt->execute([$user['id'], $author['id']]);
        $isFollowingAuthor = (bool)$ifSt->fetchColumn();
    }
    ?>
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <div class="panel-ttl"><i class="fa-solid fa-user" style="color:var(--accent)"></i> Tentang Pembuat</div>
            <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:.75rem;">
                <div class="avatar av-44" style="background:<?= $ap[1] ?>;color:<?= $ap[0] ?>;">
                    <?php if ($author['profile_picture']): ?>
                        <img src="<?= e($author['profile_picture']) ?>" alt="">
                    <?php else: ?>
                        <?= initials($author['fullname']) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.87rem;"><?= e($author['fullname']) ?></div>
                    <div style="font-size:.73rem;color:var(--text3);">@<?= e($author['username']) ?></div>
                </div>
            </div>
            <?php if ($author['bio']): ?>
            <p style="font-size:.8rem;color:var(--text2);line-height:1.6;margin-bottom:.75rem;"><?= e($author['bio']) ?></p>
            <?php endif; ?>
            <div style="display:flex;gap:1rem;margin-bottom:.75rem;">
                <div style="text-align:center;">
                    <div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><?= fmtNum($pCount) ?></div>
                    <div style="font-size:.68rem;color:var(--text3);">Post</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;" id="a-fc"><?= fmtNum($fCount) ?></div>
                    <div style="font-size:.68rem;color:var(--text3);">Pengikut</div>
                </div>
            </div>
            <div style="display:flex;gap:.4rem;">
                <a href="profile.php?u=<?= e($author['username']) ?>" class="btn btn-outline btn-sm flex-1" style="justify-content:center;">
                    <i class="fa-regular fa-user fa-xs"></i> Profil
                </a>
                <?php if ($user && $user['id'] != $author['id']): ?>
                <button id="author-follow-btn"
                    class="btn <?= $isFollowingAuthor ? 'btn-outline' : 'btn-primary' ?> btn-sm flex-1"
                    onclick="followAuthor(<?= $author['id'] ?>, this)">
                    <?= $isFollowingAuthor
                        ? '<i class="fa-solid fa-user-check fa-xs"></i> Mengikuti'
                        : '<i class="fa-solid fa-user-plus fa-xs"></i> Ikuti' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Catalog info if tagged -->
    <?php if ($post['cat_name']): ?>
    <div class="card">
        <div class="card-body">
            <div class="panel-ttl"><i class="fa-solid fa-location-dot" style="color:var(--red)"></i> Tempat di Postingan</div>
            <?php
            $catInfo = $db->prepare("SELECT * FROM catalogs WHERE slug = ?");
            $catInfo->execute([$post['cat_slug']]);
            $catInfo = $catInfo->fetch();
            ?>
            <?php if ($catInfo): ?>
            <div style="font-weight:700;font-size:.88rem;margin-bottom:.25rem;"><?= e($catInfo['name']) ?></div>
            <div style="font-size:.75rem;color:var(--text3);margin-bottom:.5rem;">
                <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($catInfo['city']) ?>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;margin-bottom:.65rem;">
                <i class="fa-solid fa-star fa-xs" style="color:var(--amber)"></i>
                <strong><?= number_format($catInfo['avg_rating'], 1) ?></strong>
                <span style="color:var(--text3)">(<?= $catInfo['total_reviews'] ?> ulasan)</span>
            </div>
            <a href="catalog-detail.php?slug=<?= e($post['cat_slug']) ?>" class="btn btn-primary btn-sm w-100" style="justify-content:center;">
                <i class="fa-solid fa-map-pin fa-xs"></i> Lihat Katalog
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</aside>
</div>

<script>
function toggleReplyBox(id) {
    const box = document.getElementById('reply-' + id);
    if (!box) return;
    const isOpen = box.style.display !== 'none';
    document.querySelectorAll('[id^="reply-"]').forEach(b => b.style.display = 'none');
    if (!isOpen) {
        box.style.display = '';
        box.querySelector('textarea')?.focus();
    }
}

function followAuthor(uid, btn) {
    fetch('actions/follow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${uid}&csrf_token=${getCsrf()}`
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            btn.innerHTML = d.following
                ? '<i class="fa-solid fa-user-check fa-xs"></i> Mengikuti'
                : '<i class="fa-solid fa-user-plus fa-xs"></i> Ikuti';
            btn.className = `btn ${d.following ? 'btn-outline' : 'btn-primary'} btn-sm flex-1`;
            const fc = document.getElementById('a-fc');
            if (fc) fc.textContent = d.followers;
            toast(d.following ? 'Berhasil mengikuti' : 'Berhenti mengikuti');
        }
    });
}
</script>

<script>
function togglePostMenu(id, e) {
  e.stopPropagation();
  const m = document.getElementById('post-menu-' + id);
  m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', () => {
  document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.style.display = 'none');
});
function confirmDeletePost(id) {
  if (!confirm('Hapus postingan ini?')) return;
  fetch('<?= APP_URL ?>/actions/post_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'post_id=' + id + '&csrf_token=' + getCsrf()
  }).then(r => r.json()).then(d => {
    if (d.ok) { toast('Postingan dihapus'); setTimeout(() => location.href = '<?= APP_URL ?>/index.php', 1000); }
    else toast(d.msg || 'Gagal', 'err');
  });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
