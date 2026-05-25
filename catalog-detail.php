<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
$user = currentUser();

$db   = getDB();
$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(APP_URL . '/catalog.php');

// Smart back URL
$_referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl  = ($_referer && strpos($_referer, 'catalog-detail.php') === false)
    ? $_referer
    : APP_URL . '/catalog.php';

// CS & Admin bisa lihat semua status
$isStaff = $user && in_array($user['role'], ['cs','admin']);
$ownerId = $user['id'] ?? 0;

if ($isStaff) {
    $st = $db->prepare("SELECT c.*,cat.name AS cat_name,cat.icon AS cat_icon,u.fullname AS owner_name,u.username AS owner_username,u.id AS owner_id FROM catalogs c JOIN categories cat ON c.category_id=cat.id JOIN users u ON c.owner_id=u.id WHERE c.slug=?");
    $st->execute([$slug]);
} else {
    $st = $db->prepare("SELECT c.*,cat.name AS cat_name,cat.icon AS cat_icon,u.fullname AS owner_name,u.username AS owner_username,u.id AS owner_id FROM catalogs c JOIN categories cat ON c.category_id=cat.id JOIN users u ON c.owner_id=u.id WHERE c.slug=? AND (c.verification_status='approved' OR c.owner_id=?)");
    $st->execute([$slug, $ownerId]);
}

$cat = $st->fetch();
if (!$cat) { http_response_code(404); die('<div style="font-family:sans-serif;padding:3rem;text-align:center"><h2>Katalog tidak ditemukan</h2><p>Katalog sudah dihapus atau belum diverifikasi.</p><a href="' . APP_URL . '/catalog.php">← Kembali ke Katalog</a></div>'); }

// ── Handle POST review SEBELUM header ────────────────────
$wl = false; $myRating = null;
if ($user) {
    $ws = $db->prepare("SELECT id FROM wishlists WHERE user_id=? AND catalog_id=?");
    $ws->execute([$user['id'], $cat['id']]);
    $wl = (bool)$ws->fetchColumn();

    $rs = $db->prepare("SELECT * FROM ratings WHERE user_id=? AND catalog_id=?");
    $rs->execute([$user['id'], $cat['id']]);
    $myRating = $rs->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'])) {
    requireLogin();
    verifyCsrf();
    $rating = (int)($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    if ($rating >= 1 && $rating <= 5) {
        if ($myRating) {
            $db->prepare("UPDATE ratings SET rating=?,review=? WHERE id=?")->execute([$rating, $review, $myRating['id']]);
        } else {
            $db->prepare("INSERT INTO ratings (user_id,catalog_id,rating,review) VALUES (?,?,?,?)")->execute([$user['id'], $cat['id'], $rating, $review]);
            $db->prepare("UPDATE catalogs SET total_reviews=total_reviews+1 WHERE id=?")->execute([$cat['id']]);
        }
        $avg = $db->prepare("SELECT AVG(rating) FROM ratings WHERE catalog_id=?");
        $avg->execute([$cat['id']]);
        $db->prepare("UPDATE catalogs SET avg_rating=? WHERE id=?")->execute([round($avg->fetchColumn(), 2), $cat['id']]);
        flash('success', 'Ulasan berhasil disimpan!');
        redirect(APP_URL . '/catalog-detail.php?slug=' . $slug);
    }
}

// ── Fetch data ────────────────────────────────────────────
$gallery  = $db->prepare("SELECT * FROM catalog_images WHERE catalog_id=? LIMIT 9"); $gallery->execute([$cat['id']]); $gallery=$gallery->fetchAll();
$reviews  = $db->prepare("SELECT r.*,u.fullname,u.username,u.profile_picture FROM ratings r JOIN users u ON r.user_id=u.id WHERE r.catalog_id=? ORDER BY r.created_at DESC LIMIT 10"); $reviews->execute([$cat['id']]); $reviews=$reviews->fetchAll();
$relPosts = $db->prepare("SELECT p.*,u.fullname,u.username FROM posts p JOIN users u ON p.user_id=u.id WHERE p.catalog_id=? AND p.status='published' ORDER BY p.created_at DESC LIMIT 6"); $relPosts->execute([$cat['id']]); $relPosts=$relPosts->fetchAll();
$ratingDist = $db->prepare("SELECT rating,COUNT(*) AS cnt FROM ratings WHERE catalog_id=? GROUP BY rating ORDER BY rating DESC"); $ratingDist->execute([$cat['id']]); $ratingDist=$ratingDist->fetchAll(PDO::FETCH_KEY_PAIR);

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];

// ── Output HTML ───────────────────────────────────────────
$pageTitle = e($cat['name']) . ' — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main" style="max-width:760px;margin:0 auto">

  <div style="margin-bottom:.85rem;">
    <a href="<?= e($backUrl) ?>" class="btn btn-ghost btn-sm text-dim">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <!-- CS/Admin status banner -->
  <?php if ($isStaff && $cat['verification_status'] !== 'approved'): ?>
  <?php $bMap=['pending'=>['alert-warning','fa-clock','Pending — belum tampil ke publik'],'rejected'=>['alert-error','fa-times-circle','Ditolak — tidak tampil ke publik']]; [$bc,$bi,$bm]=$bMap[$cat['verification_status']]??['alert-info','fa-circle','Unknown']; ?>
  <div class="alert <?= $bc ?>" style="margin-bottom:.85rem;">
    <i class="fa-solid <?= $bi ?>"></i>
    <div><strong>Status: <?= ucfirst($cat['verification_status']) ?></strong> — <?= $bm ?>
      <?php if ($cat['verification_status']==='pending'): ?>
      &nbsp;<a href="<?= APP_URL ?>/cs/dashboard.php?tab=verify" style="font-weight:700;color:inherit;text-decoration:underline">Proses Verifikasi</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Cover -->
  <div style="height:280px;border-radius:var(--r-lg);overflow:hidden;margin-bottom:1.1rem;background:var(--bg);position:relative;">
    <?php if ($cat['thumbnail']): ?><img src="<?= e($cat['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
    <?php else: ?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;color:var(--text3);opacity:.3;"><i class="fa-solid <?= e($cat['cat_icon']) ?>"></i></div><?php endif; ?>
    <?php if ($cat['verification_status']==='approved'): ?>
    <div style="position:absolute;top:.85rem;right:.85rem;background:var(--green);color:#fff;padding:.35rem .8rem;border-radius:20px;font-size:.75rem;font-weight:700;display:flex;align-items:center;gap:.3rem;"><i class="fa-solid fa-check-circle fa-xs"></i> Terverifikasi</div>
    <?php endif; ?>
  </div>

  <!-- Info -->
  <div style="display:flex;align-items:flex-start;gap:1rem;margin-bottom:1.1rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:0;">
      <h1 style="font-family:'Nunito',sans-serif;font-size:1.4rem;font-weight:900;margin-bottom:.25rem;"><?= e($cat['name']) ?></h1>
      <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-bottom:.5rem;">
        <span class="text-dim text-sm"><i class="fa-solid <?= e($cat['cat_icon']) ?> fa-xs" style="color:var(--accent)"></i> <?= e($cat['cat_name']) ?></span>
        <span class="text-dim">·</span>
        <span class="text-dim text-sm"><i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($cat['city']) ?></span>
        <?php if ($cat['open_time']): ?>
        <span class="text-dim">·</span>
        <span class="text-sm" style="color:var(--green);"><i class="fa-regular fa-clock fa-xs"></i> <?= substr($cat['open_time'],0,5) ?>–<?= substr($cat['close_time'],0,5) ?></span>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;">
        <span class="stars"><?= str_repeat('★',round($cat['avg_rating'])) ?><?= str_repeat('☆',5-round($cat['avg_rating'])) ?></span>
        <strong><?= number_format($cat['avg_rating'],1) ?></strong>
        <span class="text-dim text-sm">(<?= number_format($cat['total_reviews']) ?> ulasan)</span>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:.4rem;flex-shrink:0;">
      <?php if ($cat['contact']): ?><a href="https://wa.me/<?= e($cat['contact']) ?>" target="_blank" class="btn btn-success btn-sm"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a><?php endif; ?>
      <?php if ($user): ?>
      <button onclick="toggleWishlist(<?= $cat['id'] ?>,this)" class="btn btn-sm btn-outline" style="color:<?= $wl?'var(--accent)':'var(--text2)' ?>;">
        <i class="fa-<?= $wl?'solid':'regular' ?> fa-bookmark"></i> <?= $wl?'Tersimpan':'Simpan' ?>
      </button>
      <?php if ($user['id'] != $cat['owner_id']): ?>
      <a href="<?= APP_URL ?>/report.php?type=catalog&id=<?= $cat['id'] ?>" class="btn btn-ghost btn-sm" style="color:var(--text3);">
        <i class="fa-regular fa-flag fa-xs"></i> Laporkan
      </a>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Description -->
  <?php if ($cat['description']): ?>
  <div class="card" style="margin-bottom:.85rem;">
    <div class="card-body">
      <div class="card-title" style="margin-bottom:.5rem;"><i class="fa-solid fa-circle-info" style="color:var(--accent)"></i> Tentang Tempat</div>
      <p style="font-size:.875rem;color:var(--text2);line-height:1.7;"><?= nl2br(e($cat['description'])) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Gallery -->
  <?php if ($gallery): ?>
  <div class="card" style="margin-bottom:.85rem;">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-images" style="color:var(--accent)"></i> Galeri</span></div>
    <div class="card-body" style="padding:.65rem;"><div class="ig-grid"><?php foreach ($gallery as $g): ?><div class="ig-cell"><img src="<?= e($g['image']) ?>" alt="" loading="lazy"><div class="ig-over"></div></div><?php endforeach; ?></div></div>
  </div>
  <?php endif; ?>

  <!-- Reviews -->
  <div class="card" style="margin-bottom:.85rem;">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-star" style="color:var(--amber)"></i> Ulasan (<?= $cat['total_reviews'] ?>)</span>
      <?php if ($user && !$myRating && $cat['verification_status']==='approved'): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('review-modal')"><i class="fa-solid fa-pen"></i> Tulis Ulasan</button>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if ($cat['total_reviews'] > 0): ?>
      <div style="display:flex;gap:1.5rem;align-items:center;margin-bottom:1.25rem;padding-bottom:1.1rem;border-bottom:1px solid var(--border);">
        <div style="text-align:center;flex-shrink:0;">
          <div style="font-family:'Nunito',sans-serif;font-size:3rem;font-weight:900;color:var(--amber);line-height:1;"><?= number_format($cat['avg_rating'],1) ?></div>
          <div style="color:var(--amber);font-size:1.1rem;"><?= str_repeat('★',round($cat['avg_rating'])) ?></div>
          <div class="text-dim text-xs"><?= $cat['total_reviews'] ?> ulasan</div>
        </div>
        <div style="flex:1;">
          <?php for($r=5;$r>=1;$r--): $cnt=$ratingDist[$r]??0; $pct=$cat['total_reviews']?round($cnt/$cat['total_reviews']*100):0; ?>
          <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem;">
            <span class="text-xs text-dim" style="width:10px;"><?= $r ?></span>
            <div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden;"><div style="width:<?= $pct ?>%;height:100%;background:var(--amber);border-radius:3px;"></div></div>
            <span class="text-xs text-dim" style="width:20px;"><?= $cnt ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (empty($reviews)): ?>
      <div class="empty" style="padding:1.5rem;"><div class="e-icon"><i class="fa-regular fa-star"></i></div><h3>Belum ada ulasan</h3></div>
      <?php else: ?>
      <?php foreach ($reviews as $rv): $rp=$pals[($rv['user_id']-1)%count($pals)]; ?>
      <div style="padding:.7rem 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.4rem;">
          <div class="avatar av-36" style="background:<?= $rp[1] ?>;color:<?= $rp[0] ?>;">
            <?php if ($rv['profile_picture']): ?><img src="<?= e($rv['profile_picture']) ?>" alt=""><?php else: ?><?= initials($rv['fullname']) ?><?php endif; ?>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:.85rem;"><?= e($rv['fullname']) ?></div>
            <div style="display:flex;align-items:center;gap:.35rem;">
              <span style="color:var(--amber);font-size:.8rem;"><?= str_repeat('★',$rv['rating']) ?><?= str_repeat('☆',5-$rv['rating']) ?></span>
              <span class="text-xs text-dim"><?= timeAgo($rv['created_at']) ?></span>
            </div>
          </div>
        </div>
        <?php if ($rv['review']): ?><p style="font-size:.85rem;color:var(--text2);line-height:1.65;padding-left:2.85rem;"><?= nl2br(e($rv['review'])) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related Posts -->
  <?php if ($relPosts): ?>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-images" style="color:var(--accent)"></i> Postingan terkait</span></div>
    <div class="card-body" style="padding:.65rem;">
      <div class="ig-grid">
        <?php foreach ($relPosts as $rp2): ?>
        <a href="<?= APP_URL ?>/post.php?id=<?= $rp2['id'] ?>" class="ig-cell" style="border-radius:var(--r-sm);overflow:hidden;">
          <?php if ($rp2['image']): ?><img src="<?= e($rp2['image']) ?>" alt="" loading="lazy"><div class="ig-over"><i class="fa-regular fa-heart"></i></div>
          <?php else: ?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);"><i class="fa-regular fa-image fa-2x text-dim"></i></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>

<!-- Right Panel -->
<aside class="right-panel">
  <div class="card" style="margin-bottom:.85rem;">
    <div class="card-body">
      <div style="font-size:.68rem;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.09em;margin-bottom:.85rem;">
        <i class="fa-solid fa-circle-info" style="color:var(--accent)"></i> Informasi
      </div>
      <?php if ($cat['address']): ?>
      <div style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:.75rem;">
        <div style="width:30px;height:30px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red);"></i>
        </div>
        <div><div style="font-size:.68rem;color:var(--text3);font-weight:600;margin-bottom:.2rem;">Alamat</div>
        <div style="font-size:.82rem;color:var(--text);line-height:1.55;"><?= e($cat['address']) ?>, <?= e($cat['city']) ?></div></div>
      </div>
      <?php endif; ?>
      <?php if ($cat['contact']): ?>
      <div style="display:flex;gap:.65rem;align-items:center;margin-bottom:.75rem;">
        <div style="width:30px;height:30px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fa-brands fa-whatsapp fa-xs" style="color:var(--green);"></i>
        </div>
        <div><div style="font-size:.68rem;color:var(--text3);font-weight:600;margin-bottom:.2rem;">Kontak</div>
        <a href="https://wa.me/<?= e($cat['contact']) ?>" style="font-size:.82rem;color:var(--green);font-weight:700;"><?= e($cat['contact']) ?></a></div>
      </div>
      <?php endif; ?>
      <?php if ($cat['open_time']): ?>
      <div style="display:flex;gap:.65rem;align-items:center;">
        <div style="width:30px;height:30px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fa-regular fa-clock fa-xs" style="color:var(--blue);"></i>
        </div>
        <div><div style="font-size:.68rem;color:var(--text3);font-weight:600;margin-bottom:.2rem;">Jam Buka</div>
        <div style="font-size:.82rem;color:var(--text);font-weight:700;"><?= substr($cat['open_time'],0,5) ?> – <?= substr($cat['close_time'],0,5) ?></div></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.65rem;text-align:center;">
      <div style="font-family:'Nunito',sans-serif;font-size:1.1rem;font-weight:900;color:var(--red);"><?= fmtNum($cat['total_likes']) ?></div>
      <div style="font-size:.65rem;color:var(--text3);">Suka</div>
    </div>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.65rem;text-align:center;">
      <div style="font-family:'Nunito',sans-serif;font-size:1.1rem;font-weight:900;color:var(--amber);"><?= $cat['total_reviews'] ?></div>
      <div style="font-size:.65rem;color:var(--text3);">Ulasan</div>
    </div>
  </div>
</aside>
</div>

<!-- Review Modal -->
<?php if ($user && !$myRating && $cat['verification_status']==='approved'): ?>
<div class="modal-ov" id="review-modal">
  <div class="modal">
    <div class="modal-head"><span class="modal-title"><i class="fa-solid fa-star" style="color:var(--amber)"></i> Tulis Ulasan</span><button class="modal-close"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="review" value="1">
        <div style="text-align:center;margin-bottom:1rem;">
          <div style="font-weight:700;margin-bottom:.5rem;"><?= e($cat['name']) ?></div>
          <div class="stars-inp" style="justify-content:center;" id="star-inp">
            <?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="rat-val">
          <div class="text-dim text-xs" style="margin-top:.3rem;" id="star-lbl">Pilih rating</div>
        </div>
        <div class="form-group"><label>Ulasan (opsional)</label><textarea name="review" class="form-control" rows="4" placeholder="Ceritakan pengalamanmu..."></textarea></div>
        <div class="modal-foot"><button type="button" class="btn btn-outline btn-sm" onclick="closeModal('review-modal')">Batal</button><button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i> Kirim</button></div>
      </form>
    </div>
  </div>
</div>
<script>
const sl=['','Buruk','Kurang','Cukup','Bagus','Luar Biasa!'];
document.querySelectorAll('#star-inp i').forEach((s,i)=>{
  s.addEventListener('click',()=>{document.getElementById('rat-val').value=i+1;document.getElementById('star-lbl').textContent=sl[i+1];document.querySelectorAll('#star-inp i').forEach((x,j)=>x.classList.toggle('on',j<=i));});
  s.addEventListener('mouseenter',()=>document.querySelectorAll('#star-inp i').forEach((x,j)=>x.classList.toggle('on',j<=i)));
});
document.getElementById('star-inp').addEventListener('mouseleave',()=>{const v=parseInt(document.getElementById('rat-val').value)||0;document.querySelectorAll('#star-inp i').forEach((x,j)=>x.classList.toggle('on',j<v));});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
