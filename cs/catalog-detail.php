<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole(['cs', 'admin']);
$pageTitle = 'Review Katalog — CS YummySpot';
require_once __DIR__ . '/../includes/header.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/cs/dashboard.php');

// Fetch katalog — CS bisa lihat semua status
$st = $db->prepare("
    SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon,
           u.fullname AS owner_name, u.username AS owner_username, u.email AS owner_email
    FROM catalogs c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.owner_id = u.id
    WHERE c.id = ?
");
$st->execute([$id]);
$cat = $st->fetch();
if (!$cat) { http_response_code(404); die('Katalog tidak ditemukan.'); }

// Riwayat verifikasi
$histSt = $db->prepare("
    SELECT cv.*, u.fullname AS cs_name
    FROM catalog_verifications cv
    JOIN users u ON cv.cs_id = u.id
    WHERE cv.catalog_id = ?
    ORDER BY cv.verified_at DESC
");
$histSt->execute([$id]);
$history = $histSt->fetchAll();

// Statistik
$gallery = $db->prepare("SELECT * FROM catalog_images WHERE catalog_id = ? LIMIT 9");
$gallery->execute([$id]); $gallery = $gallery->fetchAll();

$reviews = $db->prepare("
    SELECT r.*, u.fullname, u.username
    FROM ratings r JOIN users u ON r.user_id = u.id
    WHERE r.catalog_id = ? ORDER BY r.created_at DESC LIMIT 5
");
$reviews->execute([$id]); $reviews = $reviews->fetchAll();

$postCount = (int)$db->prepare("SELECT COUNT(*) FROM posts WHERE catalog_id = ? AND status='published'")->execute([$id]) ? 0 : 0;
$pcSt = $db->prepare("SELECT COUNT(*) FROM posts WHERE catalog_id = ? AND status='published'");
$pcSt->execute([$id]); $postCount = (int)$pcSt->fetchColumn();

$wishCount = (int)$db->prepare("SELECT COUNT(*) FROM wishlists WHERE catalog_id = ?")->execute([$id]) ? 0 : 0;
$wcSt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE catalog_id = ?");
$wcSt->execute([$id]); $wishCount = (int)$wcSt->fetchColumn();

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act  = $_POST['action'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if ($act === 'approve') {
        $db->prepare("UPDATE catalogs SET verification_status='approved' WHERE id=?")->execute([$id]);
        $db->prepare("INSERT INTO catalog_verifications (catalog_id, cs_id, status, note) VALUES (?,?,'approved',?)")->execute([$id, $user['id'], $note ?: 'Disetujui oleh CS']);
        // Notif ke owner
        createNotif($cat['owner_id'], $user['id'], 'verified', $id, 'Katalog "' . $cat['name'] . '" telah diverifikasi dan aktif!');
        flash('success', 'Katalog berhasil disetujui!');
    } elseif ($act === 'reject') {
        if (!$note) { flash('error', 'Alasan penolakan wajib diisi.'); redirect(APP_URL . '/cs/catalog-detail.php?id=' . $id); }
        $db->prepare("UPDATE catalogs SET verification_status='rejected' WHERE id=?")->execute([$id]);
        $db->prepare("INSERT INTO catalog_verifications (catalog_id, cs_id, status, note) VALUES (?,?,'rejected',?)")->execute([$id, $user['id'], $note]);
        createNotif($cat['owner_id'], $user['id'], 'rejected', $id, 'Katalog "' . $cat['name'] . '" ditolak: ' . $note);
        flash('success', 'Katalog ditolak.');
    }
    redirect(APP_URL . '/cs/catalog-detail.php?id=' . $id);
}

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
$statusMap = [
    'approved' => ['var(--green)',  'fa-circle-check',  'Approved'],
    'pending'  => ['var(--amber)', 'fa-clock',          'Pending'],
    'rejected' => ['var(--red)',   'fa-circle-xmark',   'Ditolak'],
];
[$stColor, $stIcon, $stLabel] = $statusMap[$cat['verification_status']] ?? ['var(--text3)','fa-circle','Unknown'];
$pg = 'dashboard';
?>

<div class="app-wrap">
<!-- CS Sidebar -->
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--green);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-shield-halved"></i> CS Panel
        </div>
        <div style="font-size:.75rem;color:var(--text3);margin-top:.15rem;"><?= e($user['fullname']) ?></div>
    </div>
    <a href="dashboard.php?tab=catalogs" class="sb-item active">
        <i class="fa-solid fa-building-store si"></i> Semua Katalog
    </a>
    <a href="dashboard.php?tab=reports" class="sb-item">
        <i class="fa-solid fa-flag si"></i> Laporan
    </a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim">
        <i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed
    </a>
</aside>

<main class="main" style="max-width:820px;">

    <!-- Breadcrumb -->
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:.82rem;color:var(--text3);">
        <a href="dashboard.php?tab=catalogs" style="color:var(--accent);font-weight:600;">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Semua Katalog
        </a>
        <i class="fa-solid fa-chevron-right fa-xs"></i>
        <span><?= e($cat['name']) ?></span>
    </div>

    <!-- ── STATUS BANNER & VERIFIKASI ACTIONS ──────────────── -->
    <div style="background:#fff;border:1.5px solid <?= $stColor ?>;border-radius:var(--r-lg);padding:1.1rem 1.25rem;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:44px;height:44px;border-radius:50%;background:<?= $stColor ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid <?= $stIcon ?>" style="color:<?= $stColor ?>;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1rem;">
                        Status: <span style="color:<?= $stColor ?>"><?= $stLabel ?></span>
                    </div>
                    <div style="font-size:.78rem;color:var(--text3);">
                        <?php if ($cat['verification_status'] === 'pending'): ?>
                            Menunggu tinjauan CS — belum tampil ke publik
                        <?php elseif ($cat['verification_status'] === 'approved'): ?>
                            Aktif dan tampil di katalog publik
                        <?php else: ?>
                            Ditolak — tidak tampil ke publik
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Action buttons -->
            <div style="display:flex;gap:.5rem;">
                <?php if ($cat['verification_status'] !== 'approved'): ?>
                <button class="btn btn-success btn-sm" onclick="openModal('approve-modal')">
                    <i class="fa-solid fa-circle-check fa-xs"></i> Setujui
                </button>
                <?php endif; ?>
                <?php if ($cat['verification_status'] !== 'rejected'): ?>
                <button class="btn btn-danger btn-sm" onclick="openModal('reject-modal')">
                    <i class="fa-solid fa-circle-xmark fa-xs"></i> Tolak
                </button>
                <?php endif; ?>
                <?php if ($cat['verification_status'] === 'rejected'): ?>
                <button class="btn btn-primary btn-sm" onclick="openModal('approve-modal')">
                    <i class="fa-solid fa-rotate-right fa-xs"></i> Aktifkan Ulang
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:1rem;">

        <!-- ── KOLOM KIRI ───────────────────────────────────── -->
        <div>

            <!-- Cover -->
            <div style="height:220px;border-radius:var(--r-lg);overflow:hidden;margin-bottom:.85rem;background:var(--bg);position:relative;">
                <?php if ($cat['thumbnail']): ?>
                    <img src="<?= e($cat['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;color:var(--text3);opacity:.25;">
                        <i class="fa-solid <?= e($cat['cat_icon']) ?>"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info Dasar -->
            <div class="card" style="margin-bottom:.85rem;">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-circle-info" style="color:var(--accent)"></i> Informasi Katalog</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Nama</div>
                            <div style="font-weight:700;"><?= e($cat['name']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Kategori</div>
                            <div><i class="fa-solid <?= e($cat['cat_icon']) ?> fa-xs" style="color:var(--accent)"></i> <?= e($cat['cat_name']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Kota</div>
                            <div><i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($cat['city']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Kontak</div>
                            <div><?= $cat['contact'] ? '<i class="fa-brands fa-whatsapp fa-xs" style="color:var(--green)"></i> '.e($cat['contact']) : '<span class="text-dim">—</span>' ?></div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Jam Buka</div>
                            <div><?= $cat['open_time'] ? '<i class="fa-regular fa-clock fa-xs" style="color:var(--blue)"></i> '.substr($cat['open_time'],0,5).' – '.substr($cat['close_time'],0,5) : '<span class="text-dim">—</span>' ?></div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Terdaftar</div>
                            <div><?= date('d M Y', strtotime($cat['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php if ($cat['address']): ?>
                    <div style="margin-top:.85rem;padding-top:.75rem;border-top:1px solid var(--border);">
                        <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Alamat</div>
                        <div style="font-size:.85rem;color:var(--text2);"><?= e($cat['address']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($cat['description']): ?>
                    <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--border);">
                        <div style="font-size:.68rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;">Deskripsi</div>
                        <div style="font-size:.85rem;color:var(--text2);line-height:1.65;"><?= nl2br(e($cat['description'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gallery -->
            <?php if ($gallery): ?>
            <div class="card" style="margin-bottom:.85rem;">
                <div class="card-header"><span class="card-title"><i class="fa-solid fa-images" style="color:var(--accent)"></i> Galeri (<?= count($gallery) ?> foto)</span></div>
                <div class="card-body" style="padding:.65rem;">
                    <div class="ig-grid">
                        <?php foreach ($gallery as $g): ?>
                        <div class="ig-cell" style="border-radius:var(--r-sm);overflow:hidden;">
                            <img src="<?= e($g['image']) ?>" alt="" loading="lazy">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ulasan terbaru -->
            <?php if ($reviews): ?>
            <div class="card">
                <div class="card-header"><span class="card-title"><i class="fa-solid fa-star" style="color:var(--amber)"></i> Ulasan Terbaru</span></div>
                <div class="card-body" style="padding:.5rem;">
                    <?php foreach ($reviews as $rv):
                        $rp = $pals[($rv['user_id']-1) % count($pals)];
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:.6rem;padding:.6rem .5rem;border-bottom:1px solid var(--border);">
                        <div class="avatar av-36" style="background:<?= $rp[1] ?>;color:<?= $rp[0] ?>;flex-shrink:0;">
                            <?= initials($rv['fullname']) ?>
                        </div>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem;">
                                <span style="font-weight:700;font-size:.83rem;"><?= e($rv['fullname']) ?></span>
                                <span style="color:var(--amber);font-size:.78rem;"><?= str_repeat('★',$rv['rating']) ?><?= str_repeat('☆',5-$rv['rating']) ?></span>
                                <span style="font-size:.7rem;color:var(--text3);margin-left:auto;"><?= timeAgo($rv['created_at']) ?></span>
                            </div>
                            <?php if ($rv['review']): ?>
                            <div style="font-size:.82rem;color:var(--text2);"><?= e(mb_strimwidth($rv['review'], 0, 120, '...')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ── KOLOM KANAN ──────────────────────────────────── -->
        <div>

            <!-- Info Pemilik -->
            <div class="card" style="margin-bottom:.85rem;">
                <div class="card-body">
                    <div class="panel-ttl"><i class="fa-solid fa-user-tie" style="color:var(--accent)"></i> Data Pemilik</div>
                    <?php $op = $pals[($cat['owner_id']-1) % count($pals)]; ?>
                    <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:.85rem;">
                        <div class="avatar av-44" style="background:<?= $op[1] ?>;color:<?= $op[0] ?>;">
                            <?= initials($cat['owner_name']) ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:.88rem;"><?= e($cat['owner_name']) ?></div>
                            <div style="font-size:.73rem;color:var(--text3);">@<?= e($cat['owner_username']) ?></div>
                        </div>
                    </div>
                    <div style="font-size:.8rem;color:var(--text2);display:flex;flex-direction:column;gap:.4rem;">
                        <div><i class="fa-regular fa-envelope fa-xs" style="color:var(--blue);width:14px;text-align:center;"></i> <?= e($cat['owner_email']) ?></div>
                    </div>
                    <a href="<?= APP_URL ?>/profile.php?u=<?= e($cat['owner_username']) ?>" target="_blank" class="btn btn-outline btn-sm w-100" style="margin-top:.85rem;justify-content:center;">
                        <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i> Lihat Profil Pemilik
                    </a>
                </div>
            </div>

            <!-- Statistik -->
            <div class="card" style="margin-bottom:.85rem;">
                <div class="card-body">
                    <div class="panel-ttl"><i class="fa-solid fa-chart-simple" style="color:var(--accent)"></i> Statistik</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
                        <div style="text-align:center;background:var(--bg);border-radius:var(--r-sm);padding:.65rem;">
                            <div style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;color:var(--amber);"><?= number_format($cat['avg_rating'],1) ?></div>
                            <div style="font-size:.65rem;color:var(--text3);">Avg Rating</div>
                        </div>
                        <div style="text-align:center;background:var(--bg);border-radius:var(--r-sm);padding:.65rem;">
                            <div style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;color:var(--blue);"><?= $cat['total_reviews'] ?></div>
                            <div style="font-size:.65rem;color:var(--text3);">Ulasan</div>
                        </div>
                        <div style="text-align:center;background:var(--bg);border-radius:var(--r-sm);padding:.65rem;">
                            <div style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;color:var(--red);"><?= $cat['total_likes'] ?></div>
                            <div style="font-size:.65rem;color:var(--text3);">Likes</div>
                        </div>
                        <div style="text-align:center;background:var(--bg);border-radius:var(--r-sm);padding:.65rem;">
                            <div style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;color:var(--accent);"><?= $wishCount ?></div>
                            <div style="font-size:.65rem;color:var(--text3);">Wishlist</div>
                        </div>
                        <div style="text-align:center;background:var(--bg);border-radius:var(--r-sm);padding:.65rem;grid-column:1/-1;">
                            <div style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;color:var(--purple);"><?= $postCount ?></div>
                            <div style="font-size:.65rem;color:var(--text3);">Postingan Terkait</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Verifikasi -->
            <?php if ($history): ?>
            <div class="card">
                <div class="card-body">
                    <div class="panel-ttl"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent)"></i> Riwayat Verifikasi</div>
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <?php foreach ($history as $h):
                            $hColor = $h['status']==='approved' ? 'var(--green)' : 'var(--red)';
                            $hIcon  = $h['status']==='approved' ? 'fa-circle-check' : 'fa-circle-xmark';
                        ?>
                        <div style="border-left:3px solid <?= $hColor ?>;padding:.5rem .65rem;background:var(--bg);border-radius:0 var(--r-sm) var(--r-sm) 0;">
                            <div style="display:flex;align-items:center;gap:.35rem;margin-bottom:.2rem;">
                                <i class="fa-solid <?= $hIcon ?> fa-xs" style="color:<?= $hColor ?>"></i>
                                <span style="font-weight:700;font-size:.8rem;color:<?= $hColor ?>"><?= ucfirst($h['status']) ?></span>
                                <span style="font-size:.68rem;color:var(--text3);margin-left:auto;"><?= date('d M Y', strtotime($h['verified_at'])) ?></span>
                            </div>
                            <div style="font-size:.75rem;color:var(--text3);">oleh <?= e($h['cs_name']) ?></div>
                            <?php if ($h['note']): ?>
                            <div style="font-size:.78rem;color:var(--text2);margin-top:.2rem;font-style:italic;">"<?= e($h['note']) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>
</div>

<!-- Modal: Setujui -->
<div class="modal-ov" id="approve-modal">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> Setujui Katalog</span>
            <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:.875rem;color:var(--text2);margin-bottom:1rem;">
                Katalog <strong><?= e($cat['name']) ?></strong> akan diaktifkan dan tampil di halaman publik.
            </p>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="approve">
                <div class="form-group">
                    <label>Catatan (opsional)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Misal: Semua dokumen lengkap dan valid."></textarea>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('approve-modal')">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-circle-check fa-xs"></i> Ya, Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Tolak -->
<div class="modal-ov" id="reject-modal">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title"><i class="fa-solid fa-circle-xmark" style="color:var(--red)"></i> Tolak Katalog</span>
            <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:.875rem;color:var(--text2);margin-bottom:1rem;">
                Katalog <strong><?= e($cat['name']) ?></strong> akan ditolak dan tidak tampil ke publik. Pemilik akan mendapat notifikasi.
            </p>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label>Alasan Penolakan <span style="color:var(--red)">*</span></label>
                    <textarea name="note" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan agar pemilik bisa memperbaiki..." required></textarea>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('reject-modal')">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-circle-xmark fa-xs"></i> Ya, Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
