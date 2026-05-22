<?php
$pageTitle = 'Katalog Saya — YummySpot';
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$db  = getDB();
$tab = $_GET['tab'] ?? 'active'; // active | trash

// ── Handle POST actions ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);

    if ($act === 'request_verify') {
        $own = $db->prepare("SELECT id FROM catalogs WHERE id=? AND owner_id=? AND deleted_at IS NULL");
        $own->execute([$id, $user['id']]);
        if ($own->fetchColumn()) {
            $db->prepare("UPDATE catalogs SET verification_status='pending' WHERE id=? AND owner_id=?")->execute([$id, $user['id']]);
            flash('success', 'Permintaan verifikasi berhasil dikirim!');
        }

    } elseif ($act === 'soft_delete') {
        // Pindah ke trash — set deleted_at, sembunyikan dari publik
        $db->prepare("UPDATE catalogs SET deleted_at=NOW(), verification_status='draft' WHERE id=? AND owner_id=? AND deleted_at IS NULL")
           ->execute([$id, $user['id']]);
        flash('success', 'Katalog dipindahkan ke Sampah. Akan dihapus permanen setelah 30 hari.');

    } elseif ($act === 'restore') {
        // Pulihkan dari trash
        $db->prepare("UPDATE catalogs SET deleted_at=NULL WHERE id=? AND owner_id=?")
           ->execute([$id, $user['id']]);
        flash('success', 'Katalog berhasil dipulihkan.');

    } elseif ($act === 'permanent_delete') {
        // Hapus permanen
        $db->prepare("DELETE FROM catalogs WHERE id=? AND owner_id=? AND deleted_at IS NOT NULL")
           ->execute([$id, $user['id']]);
        flash('success', 'Katalog dihapus secara permanen.');

    } elseif ($act === 'empty_trash') {
        // Kosongkan semua sampah milik owner ini
        $db->prepare("DELETE FROM catalogs WHERE owner_id=? AND deleted_at IS NOT NULL")
           ->execute([$user['id']]);
        flash('success', 'Semua sampah berhasil dikosongkan.');
    }

    redirect(APP_URL . '/owner/catalogs.php?tab=' . $tab);
}

// ── Auto purge katalog yang sudah 30 hari di trash ──────
$db->prepare("DELETE FROM catalogs WHERE owner_id=? AND deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")
   ->execute([$user['id']]);

// ── Fetch katalog aktif ──────────────────────────────────
$activeSt = $db->prepare("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon,
        (SELECT COUNT(*) FROM ratings   WHERE catalog_id=c.id) AS review_count,
        (SELECT COUNT(*) FROM wishlists WHERE catalog_id=c.id) AS wishlist_count,
        (SELECT note FROM catalog_verifications WHERE catalog_id=c.id ORDER BY verified_at DESC LIMIT 1) AS last_note
    FROM catalogs c
    JOIN categories cat ON c.category_id=cat.id
    WHERE c.owner_id=? AND c.deleted_at IS NULL
    ORDER BY c.created_at DESC
");
$activeSt->execute([$user['id']]);
$activeCatalogs = $activeSt->fetchAll();

// ── Fetch katalog di trash ───────────────────────────────
$trashSt = $db->prepare("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon,
        DATEDIFF(DATE_ADD(c.deleted_at, INTERVAL 30 DAY), NOW()) AS days_left
    FROM catalogs c
    JOIN categories cat ON c.category_id=cat.id
    WHERE c.owner_id=? AND c.deleted_at IS NOT NULL
    ORDER BY c.deleted_at DESC
");
$trashSt->execute([$user['id']]);
$trashCatalogs = $trashSt->fetchAll();

$trashCount = count($trashCatalogs);
$pg = 'catalogs';

$statusMap = [
    'approved' => ['badge-success', 'fa-circle-check', 'Aktif'],
    'pending'  => ['badge-warning', 'fa-clock',         'Pending'],
    'draft'    => ['badge-default', 'fa-file',           'Draft'],
    'rejected' => ['badge-danger',  'fa-circle-xmark',  'Ditolak'],
];
?>

<div class="app-wrap">
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-store"></i> Panel Pemilik
        </div>
        <div style="font-size:.75rem;color:var(--text3);margin-top:.15rem;"><?= e($user['fullname']) ?></div>
    </div>
    <a href="dashboard.php"      class="sb-item"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
    <a href="catalogs.php"       class="sb-item active"><i class="fa-solid fa-building-store si"></i> Katalog Saya</a>
    <a href="catalog-create.php" class="sb-item"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
    <a href="reviews.php"        class="sb-item"><i class="fa-solid fa-star si"></i> Ulasan</a>
    <a href="analytics.php"      class="sb-item"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed</a>
</aside>

<main class="main">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.65rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-building-store" style="color:var(--accent)"></i> Katalog Saya
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">
                <?= count($activeCatalogs) ?> katalog aktif<?= $trashCount ? " · $trashCount di sampah" : '' ?>
            </div>
        </div>
        <a href="catalog-create.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus fa-xs"></i> Tambah Katalog
        </a>
    </div>

    <!-- Tab -->
    <div class="tabs-nav" style="margin-bottom:1rem;">
        <a href="?tab=active" class="tab-item <?= $tab==='active'?'active':'' ?>">
            <i class="fa-solid fa-building-store fa-xs"></i> Katalog Aktif
            <span style="background:var(--accent);color:#fff;border-radius:20px;padding:.05rem .45rem;font-size:.65rem;margin-left:.3rem;"><?= count($activeCatalogs) ?></span>
        </a>
        <a href="?tab=trash" class="tab-item <?= $tab==='trash'?'active':'' ?>" style="<?= $trashCount?'color:var(--red)':'' ?>">
            <i class="fa-solid fa-trash fa-xs"></i> Sampah
            <?php if ($trashCount): ?>
            <span style="background:var(--red);color:#fff;border-radius:20px;padding:.05rem .45rem;font-size:.65rem;margin-left:.3rem;"><?= $trashCount ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ══ TAB AKTIF ════════════════════════════════════════ -->
    <?php if ($tab === 'active'): ?>

    <?php if (empty($activeCatalogs)): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--border2);margin-bottom:1rem;"><i class="fa-solid fa-store"></i></div>
        <h3 style="font-family:'Nunito',sans-serif;font-weight:800;margin-bottom:.4rem;color:var(--text2);">Belum ada katalog</h3>
        <p style="font-size:.85rem;color:var(--text3);margin-bottom:1.25rem;">Daftarkan tempat wisata atau kuliner milikmu.</p>
        <a href="catalog-create.php" class="btn btn-primary"><i class="fa-solid fa-plus fa-xs"></i> Buat Katalog</a>
    </div>
    <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($activeCatalogs as $c):
            [$badgeClass, $statusIcon, $statusLabel] = $statusMap[$c['verification_status']] ?? ['badge-default','fa-circle','Unknown'];
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;transition:box-shadow .18s;"
             onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
            <div style="display:flex;gap:1rem;padding:1rem;align-items:flex-start;flex-wrap:wrap;">

                <!-- Thumbnail -->
                <div style="width:80px;height:80px;border-radius:var(--r);overflow:hidden;background:var(--bg);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                    <?php if ($c['thumbnail']): ?>
                    <img src="<?= e($c['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <i class="fa-solid <?= e($c['cat_icon']) ?> fa-2x" style="color:var(--accent);opacity:.35;"></i>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                        <div>
                            <div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;"><?= e($c['name']) ?></div>
                            <div style="font-size:.77rem;color:var(--text3);">
                                <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs" style="color:var(--accent)"></i> <?= e($c['cat_name']) ?>
                                &middot; <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($c['city']) ?>
                                &middot; <i class="fa-regular fa-calendar fa-xs"></i> <?= date('d M Y', strtotime($c['created_at'])) ?>
                            </div>
                        </div>
                        <span class="badge <?= $badgeClass ?>">
                            <i class="fa-solid <?= $statusIcon ?> fa-xs"></i> <?= $statusLabel ?>
                        </span>
                    </div>

                    <!-- Stats -->
                    <div style="display:flex;gap:1rem;margin-bottom:.6rem;">
                        <div style="text-align:center;"><div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;color:var(--amber);"><?= number_format($c['avg_rating'],1) ?></div><div style="font-size:.65rem;color:var(--text3);">Rating</div></div>
                        <div style="text-align:center;"><div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;color:var(--blue);"><?= $c['review_count'] ?></div><div style="font-size:.65rem;color:var(--text3);">Ulasan</div></div>
                        <div style="text-align:center;"><div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;color:var(--red);"><?= $c['total_likes'] ?></div><div style="font-size:.65rem;color:var(--text3);">Suka</div></div>
                        <div style="text-align:center;"><div style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:900;color:var(--accent);"><?= $c['wishlist_count'] ?></div><div style="font-size:.65rem;color:var(--text3);">Wishlist</div></div>
                    </div>

                    <!-- Rejection note -->
                    <?php if ($c['verification_status']==='rejected' && $c['last_note']): ?>
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--r-sm);padding:.5rem .75rem;font-size:.78rem;color:var(--red);margin-bottom:.55rem;">
                        <i class="fa-solid fa-circle-exclamation fa-xs"></i> <strong>Alasan penolakan:</strong> <?= e($c['last_note']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;">
                        <?php if ($c['verification_status']==='approved'): ?>
                        <a href="<?= APP_URL ?>/catalog-detail.php?slug=<?= e($c['slug']) ?>" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-eye fa-xs"></i> Lihat
                        </a>
                        <?php endif; ?>

                        <a href="catalog-edit.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-pen fa-xs"></i> Edit
                        </a>

                        <?php if (in_array($c['verification_status'], ['draft','rejected'])): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="request_verify">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm"
                                onclick="return confirm('Ajukan verifikasi untuk katalog ini?')">
                                <i class="fa-solid fa-paper-plane fa-xs"></i>
                                <?= $c['verification_status']==='rejected' ? 'Ajukan Ulang' : 'Ajukan Verifikasi' ?>
                            </button>
                        </form>
                        <?php elseif ($c['verification_status']==='pending'): ?>
                        <button class="btn btn-outline btn-sm" disabled style="opacity:.5;cursor:not-allowed;">
                            <i class="fa-solid fa-clock fa-xs"></i> Sedang Diproses
                        </button>
                        <?php endif; ?>

                        <!-- Pindah ke Sampah -->
                        <form method="POST" style="display:inline;margin-left:auto;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="soft_delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--text3);"
                                onclick="return confirm('Pindahkan \'<?= addslashes($c['name']) ?>\' ke sampah?\n\nKatalog akan dihapus permanen setelah 30 hari.')">
                                <i class="fa-solid fa-trash fa-xs"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ TAB SAMPAH ═══════════════════════════════════════ -->
    <?php elseif ($tab === 'trash'): ?>

    <?php if (empty($trashCatalogs)): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--border2);margin-bottom:1rem;"><i class="fa-solid fa-trash"></i></div>
        <h3 style="font-family:'Nunito',sans-serif;font-weight:800;margin-bottom:.4rem;color:var(--text2);">Sampah kosong</h3>
        <p style="font-size:.85rem;color:var(--text3);">Tidak ada katalog yang dihapus.</p>
    </div>
    <?php else: ?>

    <!-- Info banner sampah -->
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--r);padding:.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
        <div style="font-size:.83rem;color:#92400e;">
            <i class="fa-solid fa-clock fa-xs"></i>
            Katalog di sampah akan <strong>dihapus permanen otomatis setelah 30 hari</strong>.
        </div>
        <form method="POST" style="flex-shrink:0;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="empty_trash">
            <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('Hapus permanen SEMUA katalog di sampah?\nTindakan ini tidak bisa dibatalkan!')">
                <i class="fa-solid fa-trash fa-xs"></i> Kosongkan Sampah
            </button>
        </form>
    </div>

    <div style="display:flex;flex-direction:column;gap:.65rem;">
        <?php foreach ($trashCatalogs as $c):
            $daysLeft = max(0, (int)$c['days_left']);
            $urgency  = $daysLeft <= 3 ? 'var(--red)' : ($daysLeft <= 7 ? 'var(--amber)' : 'var(--text3)');
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;opacity:.85;">
            <div style="display:flex;gap:1rem;padding:.9rem 1rem;align-items:center;flex-wrap:wrap;">

                <!-- Thumbnail (grayscale) -->
                <div style="width:64px;height:64px;border-radius:var(--r-sm);overflow:hidden;background:var(--bg);flex-shrink:0;display:flex;align-items:center;justify-content:center;filter:grayscale(1);opacity:.6;">
                    <?php if ($c['thumbnail']): ?>
                    <img src="<?= e($c['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <i class="fa-solid <?= e($c['cat_icon']) ?> fa-lg" style="color:var(--text3)"></i>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.9rem;color:var(--text2);margin-bottom:.2rem;"><?= e($c['name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text3);">
                        <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs"></i> <?= e($c['cat_name']) ?>
                        &middot; <i class="fa-solid fa-location-dot fa-xs"></i> <?= e($c['city']) ?>
                    </div>
                    <div style="font-size:.73rem;margin-top:.3rem;color:<?= $urgency ?>;font-weight:600;">
                        <i class="fa-solid fa-clock fa-xs"></i>
                        <?php if ($daysLeft <= 0): ?>
                            Akan segera dihapus
                        <?php elseif ($daysLeft === 1): ?>
                            Dihapus permanen besok
                        <?php else: ?>
                            Dihapus permanen dalam <?= $daysLeft ?> hari
                        <?php endif; ?>
                        &nbsp;·&nbsp; Dihapus <?= date('d M Y', strtotime($c['deleted_at'])) ?>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display:flex;gap:.4rem;flex-shrink:0;">
                    <!-- Pulihkan -->
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-rotate-left fa-xs"></i> Pulihkan
                        </button>
                    </form>
                    <!-- Hapus permanen -->
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="permanent_delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Hapus permanen \'<?= addslashes($c['name']) ?>\'?\nTindakan ini TIDAK BISA dibatalkan!')">
                            <i class="fa-solid fa-trash fa-xs"></i> Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
