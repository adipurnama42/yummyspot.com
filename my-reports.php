<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
requireLogin();
$user = currentUser();

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$lmt  = 10;
$off  = ($page - 1) * $lmt;

$tSt = $db->prepare("SELECT COUNT(*) FROM reports WHERE reporter_id = ?");
$tSt->execute([$user['id']]);
$total = (int)$tSt->fetchColumn();
$pages = (int)ceil($total / $lmt);

$st = $db->prepare("
    SELECT r.*,
        p.caption AS post_caption, p.image AS post_image,
        pu.fullname AS post_owner,
        c.name AS catalog_name, c.slug AS catalog_slug
    FROM reports r
    LEFT JOIN posts    p  ON r.reported_post_id    = p.id
    LEFT JOIN users    pu ON p.user_id             = pu.id
    LEFT JOIN catalogs c  ON r.reported_catalog_id = c.id
    WHERE r.reporter_id = ?
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$st->execute([$user['id'], $lmt, $off]);
$reports = $st->fetchAll();

$pageTitle = 'Laporan Saya — YummySpot';
require_once __DIR__ . '/includes/header.php';

$statusMap = [
    'pending' => ['badge-danger',  'fa-clock',         'Menunggu',   'Laporan sedang menunggu ditinjau CS'],
    'process' => ['badge-warning', 'fa-spinner',       'Diproses',   'Laporan sedang ditangani CS'],
    'done'    => ['badge-success', 'fa-circle-check',  'Selesai',    'Laporan telah diselesaikan'],
];
$typeMap = [
    'spam'          => ['fa-envelope-circle-check', 'var(--amber)', 'Spam'],
    'fake'          => ['fa-circle-xmark',          'var(--red)',   'Informasi Palsu'],
    'inappropriate' => ['fa-triangle-exclamation',  'var(--red)',   'Konten Tidak Pantas'],
    'bug'           => ['fa-bug',                   'var(--blue)',  'Bug / Masalah Teknis'],
];
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:680px; margin:0 auto;">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-flag" style="color:var(--red)"></i> Laporan Saya
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">
                <?= number_format($total) ?> laporan dikirim
            </div>
        </div>
    </div>

    <?php if (empty($reports)): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--border2);margin-bottom:1rem;"><i class="fa-regular fa-flag"></i></div>
        <h3 style="font-family:'Nunito',sans-serif;font-weight:800;margin-bottom:.4rem;color:var(--text2);">Belum ada laporan</h3>
        <p style="font-size:.85rem;color:var(--text3);">Kamu belum pernah melaporkan konten apapun.</p>
    </div>

    <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($reports as $r):
            [$badgeClass, $statusIcon, $statusLabel, $statusDesc] = $statusMap[$r['status']] ?? ['badge-default','fa-circle','Unknown',''];
            [$typeIcon, $typeColor, $typeLabel] = $typeMap[$r['report_type']] ?? ['fa-flag','var(--text3)','Lainnya'];
            $isPost    = !empty($r['reported_post_id']);
            $isCatalog = !empty($r['reported_catalog_id']);
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;">

            <!-- Header card -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;border-bottom:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <!-- Tipe laporan icon -->
                    <div style="width:34px;height:34px;border-radius:50%;background:<?= $typeColor ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid <?= $typeIcon ?> fa-xs" style="color:<?= $typeColor ?>;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;"><?= $typeLabel ?></div>
                        <div style="font-size:.72rem;color:var(--text3);">
                            <?= $isPost ? '<i class="fa-solid fa-image fa-xs"></i> Postingan' : '<i class="fa-solid fa-store fa-xs"></i> Katalog' ?>
                            &middot; <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <!-- Status badge -->
                <span class="badge <?= $badgeClass ?>" title="<?= $statusDesc ?>">
                    <i class="fa-solid <?= $statusIcon ?> fa-xs"></i> <?= $statusLabel ?>
                </span>
            </div>

            <div style="padding:.85rem 1rem;">

                <!-- Target konten -->
                <?php if ($isPost && $r['post_caption']): ?>
                <div style="background:var(--bg);border-radius:var(--r-sm);padding:.6rem .85rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.65rem;">
                    <?php if ($r['post_image']): ?>
                    <img src="<?= e($r['post_image']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:var(--r-sm);flex-shrink:0;">
                    <?php else: ?>
                    <div style="width:44px;height:44px;background:var(--border);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-regular fa-image" style="color:var(--text3);"></i>
                    </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.72rem;color:var(--text3);margin-bottom:.1rem;">Postingan oleh <strong><?= e($r['post_owner'] ?? '—') ?></strong></div>
                        <div style="font-size:.82rem;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['post_caption']) ?></div>
                    </div>
                    <?php if ($r['reported_post_id']): ?>
                    <a href="post.php?id=<?= $r['reported_post_id'] ?>" target="_blank" style="flex-shrink:0;color:var(--accent);font-size:.75rem;font-weight:600;">
                        Lihat <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php elseif ($isCatalog && $r['catalog_name']): ?>
                <div style="background:var(--bg);border-radius:var(--r-sm);padding:.6rem .85rem;margin-bottom:.75rem;display:flex;align-items:center;justify-content:space-between;gap:.65rem;">
                    <div>
                        <div style="font-size:.72rem;color:var(--text3);margin-bottom:.1rem;">Katalog</div>
                        <div style="font-size:.88rem;font-weight:700;"><?= e($r['catalog_name']) ?></div>
                    </div>
                    <?php if ($r['catalog_slug']): ?>
                    <a href="catalog-detail.php?slug=<?= e($r['catalog_slug']) ?>" target="_blank" style="flex-shrink:0;color:var(--accent);font-size:.75rem;font-weight:600;">
                        Lihat <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="background:var(--bg);border-radius:var(--r-sm);padding:.6rem .85rem;margin-bottom:.75rem;font-size:.82rem;color:var(--text3);">
                    <i class="fa-solid fa-triangle-exclamation fa-xs"></i> Konten sudah dihapus
                </div>
                <?php endif; ?>

                <!-- Deskripsi laporan -->
                <div style="font-size:.68rem;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem;">Keterangan Laporan</div>
                <div style="font-size:.85rem;color:var(--text2);line-height:1.6;background:var(--bg);border-radius:var(--r-sm);padding:.6rem .85rem;">
                    <?= nl2br(e($r['description'] ?? '—')) ?>
                </div>

                <!-- Status info -->
                <div style="margin-top:.65rem;display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--text3);">
                    <i class="fa-solid <?= $statusIcon ?> fa-xs" style="color:<?= $r['status']==='done'?'var(--green)':($r['status']==='process'?'var(--amber)':'var(--text3)') ?>"></i>
                    <?= $statusDesc ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="paging">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>" class="page-a"><i class="fa-solid fa-chevron-left fa-xs"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
        <a href="?page=<?= $i ?>" class="page-a <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?page=<?= $page+1 ?>" class="page-a"><i class="fa-solid fa-chevron-right fa-xs"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
