<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
$user  = currentUser();
$db    = getDB();
$uname = trim($_GET['u'] ?? '');
if (!$uname && isLoggedIn()) $uname = currentUser()['username'];
elseif (!$uname) redirect(APP_URL . '/login.php');

$st = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
$st->execute([$uname]);
$profile = $st->fetch();
if (!$profile) { http_response_code(404); die('User tidak ditemukan.'); }

$pageTitle = e($profile['fullname']) . ' — YummySpot';
require_once __DIR__ . '/includes/header.php';

$isOwn      = $user && $user['id'] == $profile['id'];
$isCS       = $user && in_array($user['role'], ['cs', 'admin']);
$isFollowing = false;
if ($user && !$isOwn && !$isCS) {
    $fq = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $fq->execute([$user['id'], $profile['id']]);
    $isFollowing = (bool)$fq->fetchColumn();
}

$fcSt = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?"); $fcSt->execute([$profile['id']]); $followers = (int)$fcSt->fetchColumn();
$fgSt = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");  $fgSt->execute([$profile['id']]); $following = (int)$fgSt->fetchColumn();
$pcSt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'published'"); $pcSt->execute([$profile['id']]); $postCount = (int)$pcSt->fetchColumn();
$rcSt = $db->prepare("SELECT COUNT(*) FROM ratings WHERE user_id = ?"); $rcSt->execute([$profile['id']]); $reviewCount = (int)$rcSt->fetchColumn();

$posts = $db->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM likes WHERE post_id=p.id) AS lc,
                (SELECT COUNT(*) FROM comments WHERE post_id=p.id) AS cc
    FROM posts p WHERE p.user_id = ? AND p.status = 'published'
    ORDER BY p.created_at DESC LIMIT 18
");
$posts->execute([$profile['id']]);
$posts = $posts->fetchAll();

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
$pp   = $pals[($profile['id'] - 1) % count($pals)];

// Edit profil handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwn) {
    verifyCsrf();
    $name  = trim($_POST['fullname'] ?? $profile['fullname']);
    $bio   = trim($_POST['bio'] ?? '');
    $avUrl = $profile['profile_picture'];

    // Upload avatar baru jika ada
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $nv = uploadImage($_FILES['avatar'], 'avatars');
        if ($nv) {
            $avUrl = $nv;
        } else {
            flash('error', 'Gagal upload foto. Pastikan format JPG/PNG/WebP dan maks. 5MB.');
            redirect(APP_URL . '/profile.php?u=' . $profile['username']);
        }
    }

    // Simpan ke DB
    $db->prepare("UPDATE users SET fullname=?, bio=?, profile_picture=? WHERE id=?")
       ->execute([$name, $bio, $avUrl, $user['id']]);

    // Update session agar navbar langsung berubah
    $_SESSION['user']['fullname']       = $name;
    $_SESSION['user']['profile_picture'] = $avUrl;

    flash('success', 'Profil berhasil diperbarui!');
    redirect(APP_URL . '/profile.php?u=' . $profile['username']);
}
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:720px; margin:0 auto;">

  <!-- ── CS VIEW BANNER ──────────────────────────────────── -->
  <?php if ($isCS && !$isOwn): ?>
  <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:var(--r-lg);padding:.85rem 1.1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.75rem;">
    <i class="fa-solid fa-shield-halved fa-lg" style="color:var(--green);flex-shrink:0;"></i>
    <div style="flex:1;">
      <div style="font-weight:700;font-size:.875rem;color:#16a34a;">Mode Tinjauan CS</div>
      <div style="font-size:.78rem;color:#166534;">Kamu melihat profil ini sebagai CS. Data ditampilkan lebih lengkap.</div>
    </div>
    <a href="<?= APP_URL ?>/cs/dashboard.php" class="btn btn-sm" style="background:#16a34a;color:#fff;flex-shrink:0;">
      <i class="fa-solid fa-arrow-left fa-xs"></i> CS Panel
    </a>
  </div>
  <?php endif; ?>

  <!-- ── PROFILE CARD ───────────────────────────────────── -->
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);margin-bottom:1rem;overflow:hidden;position:relative;">

    <!-- Cover -->
    <div style="height:160px;position:relative;overflow:hidden;background:linear-gradient(135deg,<?= $pp[0] ?>44,<?= $pp[1] ?>);">
      <div style="position:absolute;inset:0;background:linear-gradient(135deg,<?= $pp[0] ?>55 0%,transparent 60%,<?= $pp[1] ?> 100%);"></div>
      <!-- Badge CS di pojok cover -->
      <?php if ($isCS && !$isOwn): ?>
      <div style="position:absolute;top:.75rem;left:.75rem;background:rgba(22,163,74,.9);color:#fff;padding:.3rem .75rem;border-radius:20px;font-size:.72rem;font-weight:700;display:flex;align-items:center;gap:.3rem;">
        <i class="fa-solid fa-shield-halved fa-xs"></i> Ditinjau CS
      </div>
      <?php endif; ?>
    </div>

    <div style="padding:0 1.25rem 1.25rem;">
      <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-top:-44px;margin-bottom:.85rem;">
        <!-- Avatar -->
        <div style="flex-shrink:0;z-index:1;position:relative;">
          <div class="avatar" style="width:88px;height:88px;font-size:1.7rem;font-weight:900;background:<?= $pp[1] ?>;color:<?= $pp[0] ?>;border:4px solid #fff;box-shadow:0 2px 16px rgba(0,0,0,.13);">
            <?php if ($profile['profile_picture']): ?>
              <img src="<?= e($profile['profile_picture']) ?>" alt="">
            <?php else: ?>
              <?= initials($profile['fullname']) ?>
            <?php endif; ?>
          </div>
        </div>
        <!-- Action buttons -->
        <div style="display:flex;gap:.4rem;padding-bottom:.25rem;">
          <?php if ($isOwn): ?>
            <button class="btn btn-outline btn-sm" onclick="openModal('edit-modal')">
              <i class="fa-solid fa-pen fa-xs"></i> Edit Profil
            </button>
          <?php elseif ($isCS): ?>
            <!-- CS: tombol aksi moderasi -->
            <?php if ($profile['status'] === 'active'): ?>
            <form method="POST" action="<?= APP_URL ?>/admin/dashboard.php" style="display:inline;">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="ban_user">
              <input type="hidden" name="id" value="<?= $profile['id'] ?>">
              <input type="hidden" name="tab" value="users">
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Suspend akun ini?')">
                <i class="fa-solid fa-ban fa-xs"></i> Suspend
              </button>
            </form>
            <?php else: ?>
            <span class="badge badge-danger" style="padding:.4rem .75rem;">
              <i class="fa-solid fa-ban fa-xs"></i> Akun Suspended
            </span>
            <?php endif; ?>
          <?php elseif ($user): ?>
            <button id="follow-btn" class="btn <?= $isFollowing ? 'btn-outline' : 'btn-primary' ?> btn-sm" onclick="handleFollow(<?= $profile['id'] ?>, this)">
              <?= $isFollowing
                ? '<i class="fa-solid fa-user-check fa-xs"></i> Mengikuti'
                : '<i class="fa-solid fa-user-plus fa-xs"></i> Ikuti' ?>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Nama & username -->
      <div style="font-family:'Nunito',sans-serif;font-size:1.15rem;font-weight:900;margin-bottom:.15rem;">
        <?= e($profile['fullname']) ?>
        <?php if ($profile['status'] !== 'active'): ?>
        <span class="badge badge-danger" style="font-size:.62rem;margin-left:.4rem;vertical-align:middle;">Suspended</span>
        <?php endif; ?>
      </div>
      <div style="font-size:.8rem;color:var(--text3);margin-bottom:.45rem;">
        @<?= e($profile['username']) ?>
        <span class="role-badge role-<?= $profile['role'] ?>" style="margin-left:.35rem;"><?= ucfirst($profile['role']) ?></span>
      </div>

      <?php if ($profile['bio']): ?>
      <p style="font-size:.87rem;color:var(--text2);line-height:1.65;margin-bottom:.75rem;">
        <?= nl2br(e($profile['bio'])) ?>
      </p>
      <?php endif; ?>

      <!-- Stats row — CS lihat lebih banyak -->
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
        <div style="text-align:center;">
          <div style="font-family:'Nunito',sans-serif;font-size:1.15rem;font-weight:900;line-height:1.2;"><?= fmtNum($postCount) ?></div>
          <div style="font-size:.72rem;color:var(--text3);margin-top:.1rem;">Postingan</div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Nunito',sans-serif;font-size:1.15rem;font-weight:900;line-height:1.2;" id="fc"><?= fmtNum($followers) ?></div>
          <div style="font-size:.72rem;color:var(--text3);margin-top:.1rem;">Pengikut</div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Nunito',sans-serif;font-size:1.15rem;font-weight:900;line-height:1.2;"><?= fmtNum($following) ?></div>
          <div style="font-size:.72rem;color:var(--text3);margin-top:.1rem;">Mengikuti</div>
        </div>
        <?php if ($isCS): ?>
        <div style="text-align:center;">
          <div style="font-family:'Nunito',sans-serif;font-size:1.15rem;font-weight:900;line-height:1.2;color:var(--amber);"><?= fmtNum($reviewCount) ?></div>
          <div style="font-size:.72rem;color:var(--text3);margin-top:.1rem;">Ulasan</div>
        </div>
        <?php endif; ?>
      </div>

      <!-- CS: info tambahan yang tidak tampil ke user biasa -->
      <?php if ($isCS && !$isOwn): ?>
      <div style="margin-top:1rem;padding-top:.85rem;border-top:1px solid var(--border);">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);margin-bottom:.55rem;">
          <i class="fa-solid fa-shield-halved fa-xs"></i> Info CS (tidak tampil ke publik)
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.8rem;">
          <div style="background:var(--bg);border-radius:var(--r-sm);padding:.55rem .75rem;">
            <div style="color:var(--text3);font-size:.68rem;margin-bottom:.15rem;">Email</div>
            <div style="font-weight:600;"><?= e($profile['email']) ?></div>
          </div>
          <div style="background:var(--bg);border-radius:var(--r-sm);padding:.55rem .75rem;">
            <div style="color:var(--text3);font-size:.68rem;margin-bottom:.15rem;">Role</div>
            <div><span class="role-badge role-<?= $profile['role'] ?>"><?= ucfirst($profile['role']) ?></span></div>
          </div>
          <div style="background:var(--bg);border-radius:var(--r-sm);padding:.55rem .75rem;">
            <div style="color:var(--text3);font-size:.68rem;margin-bottom:.15rem;">Status Akun</div>
            <div><span class="badge <?= $profile['status']==='active'?'badge-success':'badge-danger' ?>"><?= ucfirst($profile['status']) ?></span></div>
          </div>
          <div style="background:var(--bg);border-radius:var(--r-sm);padding:.55rem .75rem;">
            <div style="color:var(--text3);font-size:.68rem;margin-bottom:.15rem;">Bergabung</div>
            <div style="font-weight:600;"><?= date('d M Y', strtotime($profile['created_at'])) ?></div>
          </div>
        </div>
        <?php if ($profile['role'] === 'owner'):
          $ownerCats = $db->prepare("SELECT COUNT(*) FROM catalogs WHERE owner_id=?"); $ownerCats->execute([$profile['id']]); $catTotal=(int)$ownerCats->fetchColumn();
          $approvedCats=$db->prepare("SELECT COUNT(*) FROM catalogs WHERE owner_id=? AND verification_status='approved'"); $approvedCats->execute([$profile['id']]); $catApproved=(int)$approvedCats->fetchColumn();
        ?>
        <div style="margin-top:.5rem;background:var(--accent-bg);border:1px solid #ffd4b8;border-radius:var(--r-sm);padding:.55rem .75rem;font-size:.8rem;">
          <div style="color:var(--text3);font-size:.68rem;margin-bottom:.15rem;">Katalog Terdaftar</div>
          <div style="font-weight:700;"><?= $catApproved ?> aktif / <?= $catTotal ?> total</div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── POSTS GRID ─────────────────────────────────────── -->
  <?php if (empty($posts)): ?>
  <div class="empty">
    <div class="e-icon"><i class="fa-regular fa-image"></i></div>
    <h3>Belum ada postingan</h3>
  </div>
  <?php else: ?>
  <div class="ig-grid" style="border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--border);">
    <?php foreach ($posts as $p): ?>
    <a href="post.php?id=<?= $p['id'] ?>" class="ig-cell">
      <?php if ($p['image']): ?>
        <img src="<?= e($p['image']) ?>" alt="" loading="lazy">
      <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);">
          <i class="fa-regular fa-image fa-2x text-dim"></i>
        </div>
      <?php endif; ?>
      <div class="ig-over">
        <i class="fa-solid fa-heart fa-xs"></i> <?= fmtNum($p['lc']) ?>
        &nbsp;
        <i class="fa-regular fa-comment fa-xs"></i> <?= fmtNum($p['cc']) ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<!-- Edit Profile Modal (hanya untuk owner profile sendiri) -->
<?php if ($isOwn): ?>
<div class="modal-ov" id="edit-modal">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title"><i class="fa-solid fa-pen" style="color:var(--accent)"></i> Edit Profil</span>
      <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div style="display:flex;align-items:center;gap:.85rem;margin-bottom:1rem;">
          <div class="avatar" id="avwrap" style="width:72px;height:72px;font-size:1.4rem;font-weight:900;background:<?= $pp[1] ?>;color:<?= $pp[0] ?>;cursor:pointer;flex-shrink:0;border:3px solid var(--border);" onclick="document.getElementById('avf').click()">
            <?php if ($profile['profile_picture']): ?>
              <img src="<?= e($profile['profile_picture']) ?>" alt="" id="av-img">
            <?php else: ?>
              <span id="av-ini"><?= initials($profile['fullname']) ?></span>
            <?php endif; ?>
          </div>
          <div>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('avf').click()">
              <i class="fa-solid fa-camera fa-xs"></i> Ganti Foto
            </button>
            <div class="form-hint">JPG, PNG, WebP maks. 5MB</div>
          </div>
        </div>
        <input type="file" id="avf" name="avatar" accept="image/*" style="display:none" onchange="previewAv(this)">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="fullname" class="form-control" value="<?= e($profile['fullname']) ?>" required>
        </div>
        <div class="form-group">
          <label>Bio</label>
          <textarea name="bio" class="form-control" rows="3" placeholder="Ceritakan tentang dirimu..." data-autoresize><?= e($profile['bio'] ?? '') ?></textarea>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('edit-modal')">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk fa-xs"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function previewAv(input) {
  if (input.files?.[0]) {
    const r = new FileReader();
    r.onload = e => {
      let img = document.getElementById('av-img');
      if (!img) {
        img = document.createElement('img');
        img.id = 'av-img';
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%';
        const ini = document.getElementById('av-ini');
        if (ini) ini.style.display = 'none';
        document.getElementById('avwrap').appendChild(img);
      }
      img.src = e.target.result;
    };
    r.readAsDataURL(input.files[0]);
  }
}
function handleFollow(uid, btn) {
  fetch('actions/follow.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `user_id=${uid}&csrf_token=${getCsrf()}`
  }).then(r => r.json()).then(d => {
    if (d.ok) {
      btn.innerHTML = d.following
        ? '<i class="fa-solid fa-user-check fa-xs"></i> Mengikuti'
        : '<i class="fa-solid fa-user-plus fa-xs"></i> Ikuti';
      btn.className = `btn ${d.following ? 'btn-outline' : 'btn-primary'} btn-sm`;
      const fc = document.getElementById('fc');
      if (fc) fc.textContent = d.followers;
      toast(d.following ? 'Berhasil mengikuti' : 'Berhenti mengikuti');
    }
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
