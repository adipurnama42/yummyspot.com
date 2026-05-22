<?php
$pageTitle = 'CS Dashboard — YummySpot';
require_once __DIR__ . '/../includes/header.php';
requireRole(['cs', 'admin']);

$db  = getDB();
$tab = $_GET['tab'] ?? 'catalogs';

// Counts untuk sidebar badge
$pendingCount  = (int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='pending'")->fetchColumn();
$reportCount   = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);

    if ($act === 'resolve_report') {
        $db->prepare("UPDATE reports SET status='done' WHERE id=?")->execute([$id]);
        flash('success', 'Laporan diselesaikan.');
    } elseif ($act === 'process_report') {
        $db->prepare("UPDATE reports SET status='process' WHERE id=?")->execute([$id]);
        flash('success', 'Laporan ditandai sedang diproses.');
    }
    redirect(APP_URL . '/cs/dashboard.php?tab=' . $tab);
}

// ── Data per tab ────────────────────────────────────────────
// Tab: Semua Katalog
$catFilter = $_GET['status'] ?? 'all';
$catSearch = trim($_GET['q'] ?? '');
$catPage   = max(1, (int)($_GET['page'] ?? 1));
$catLmt    = 15;
$catOff    = ($catPage - 1) * $catLmt;

if ($tab === 'catalogs') {
    $where  = []; $params = [];
    if ($catFilter !== 'all') { $where[] = "c.verification_status = ?"; $params[] = $catFilter; }
    if ($catSearch)           { $where[] = "(c.name LIKE ? OR c.city LIKE ?)"; $params[] = "%$catSearch%"; $params[] = "%$catSearch%"; }
    $ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $tSt = $db->prepare("SELECT COUNT(*) FROM catalogs c $ws");
    $tSt->execute($params);
    $catTotal = (int)$tSt->fetchColumn();

    $cSt = $db->prepare("
        SELECT c.*, cat.name AS cat_name, cat.icon AS cat_icon, u.fullname AS owner_name, u.email AS owner_email
        FROM catalogs c
        JOIN categories cat ON c.category_id = cat.id
        JOIN users u ON c.owner_id = u.id
        $ws
        ORDER BY FIELD(c.verification_status,'pending','approved','rejected'), c.created_at DESC
        LIMIT $catLmt OFFSET $catOff
    ");
    $cSt->execute($params);
    $catalogs  = $cSt->fetchAll();
    $catPages  = (int)ceil($catTotal / $catLmt);
}

// Tab: Laporan
if ($tab === 'reports') {
    $rpFilter = $_GET['rtype'] ?? 'all';
    $rpPage   = max(1, (int)($_GET['page'] ?? 1));
    $rpLmt    = 15;
    $rpOff    = ($rpPage - 1) * $rpLmt;

    $rpWhere  = []; $rpParams = [];
    if ($rpFilter !== 'all') { $rpWhere[] = "r.status = ?"; $rpParams[] = $rpFilter; }
    $rpWs = $rpWhere ? 'WHERE ' . implode(' AND ', $rpWhere) : '';

    $rtSt = $db->prepare("SELECT COUNT(*) FROM reports r $rpWs");
    $rtSt->execute($rpParams);
    $rpTotal = (int)$rtSt->fetchColumn();

    $rSt = $db->prepare("
        SELECT r.*, u.fullname AS reporter_name, u.username AS reporter_username
        FROM reports r
        JOIN users u ON r.reporter_id = u.id
        $rpWs
        ORDER BY FIELD(r.status,'pending','process','done'), r.created_at DESC
        LIMIT $rpLmt OFFSET $rpOff
    ");
    $rSt->execute($rpParams);
    $reports  = $rSt->fetchAll();
    $rpPages  = (int)ceil($rpTotal / $rpLmt);
}

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
    <a href="?tab=catalogs" class="sb-item <?= $tab==='catalogs'?'active':'' ?>">
        <i class="fa-solid fa-building-store si"></i> Semua Katalog
        <?php if ($pendingCount): ?><span class="sb-count"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="?tab=reports" class="sb-item <?= $tab==='reports'?'active':'' ?>">
        <i class="fa-solid fa-flag si"></i> Laporan
        <?php if ($reportCount): ?><span class="sb-count"><?= $reportCount ?></span><?php endif; ?>
    </a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim">
        <i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed
    </a>
</aside>

<main class="main">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <?= $tab === 'catalogs'
                    ? '<i class="fa-solid fa-building-store" style="color:var(--accent)"></i> Semua Katalog'
                    : '<i class="fa-solid fa-flag" style="color:var(--red)"></i> Laporan Pengguna' ?>
            </h1>
        </div>
        <div style="display:flex;gap:.4rem;">
            <?php if ($pendingCount): ?>
            <span class="badge badge-warning"><i class="fa-solid fa-clock fa-xs"></i> <?= $pendingCount ?> pending</span>
            <?php endif; ?>
            <?php if ($reportCount): ?>
            <span class="badge badge-danger"><i class="fa-solid fa-flag fa-xs"></i> <?= $reportCount ?> laporan baru</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ TAB: KATALOG ══════════════════════════════════════ -->
    <?php if ($tab === 'catalogs'): ?>

    <!-- Filter bar -->
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center;">
        <form method="GET" style="display:flex;gap:.4rem;flex-wrap:wrap;flex:1;">
            <input type="hidden" name="tab" value="catalogs">
            <div class="input-wrap" style="flex:1;min-width:180px;">
                <i class="fa-solid fa-magnifying-glass i-icon fa-xs"></i>
                <input type="text" name="q" value="<?= e($catSearch) ?>" class="form-control" placeholder="Cari nama atau kota...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search fa-xs"></i> Cari</button>
            <?php if ($catSearch): ?>
            <a href="?tab=catalogs&status=<?= $catFilter ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark fa-xs"></i></a>
            <?php endif; ?>
        </form>
        <!-- Status filter tabs -->
        <div style="display:flex;gap:.25rem;">
            <?php foreach (['all'=>'Semua','pending'=>'Pending','approved'=>'Approved','rejected'=>'Ditolak'] as $v=>$l): ?>
            <a href="?tab=catalogs&status=<?= $v ?>&q=<?= urlencode($catSearch) ?>"
               class="btn btn-sm <?= $catFilter===$v?'btn-primary':'btn-outline' ?>">
                <?= $l ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Katalog table -->
    <div class="tbl-wrap">
        <div class="tbl-over">
        <table class="data-tbl">
            <thead>
                <tr>
                    <th>Nama Katalog</th>
                    <th>Kategori</th>
                    <th>Pemilik</th>
                    <th>Kota</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($catalogs)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:2rem;">Tidak ada katalog ditemukan.</td></tr>
            <?php else: ?>
            <?php foreach ($catalogs as $c): ?>
            <tr>
                <td>
                    <div style="font-weight:700;font-size:.85rem;"><?= e($c['name']) ?></div>
                    <div style="font-size:.7rem;color:var(--text3);"><?= e($c['slug']) ?></div>
                </td>
                <td style="font-size:.82rem;">
                    <i class="fa-solid <?= e($c['cat_icon']) ?> fa-xs" style="color:var(--accent)"></i>
                    <?= e($c['cat_name']) ?>
                </td>
                <td>
                    <div style="font-size:.83rem;font-weight:600;"><?= e($c['owner_name']) ?></div>
                    <div style="font-size:.7rem;color:var(--text3);"><?= e($c['owner_email']) ?></div>
                </td>
                <td style="font-size:.82rem;color:var(--text2);">
                    <i class="fa-solid fa-location-dot fa-xs" style="color:var(--red)"></i> <?= e($c['city']) ?>
                </td>
                <td>
                    <span style="color:var(--amber);font-size:.8rem;"><i class="fa-solid fa-star fa-xs"></i></span>
                    <?= number_format($c['avg_rating'], 1) ?>
                    <div style="font-size:.68rem;color:var(--text3);"><?= $c['total_reviews'] ?> ulasan</div>
                </td>
                <td>
                    <?php
                    $smap = ['approved'=>['badge-success','fa-circle-check','Approved'],'pending'=>['badge-warning','fa-clock','Pending'],'rejected'=>['badge-danger','fa-circle-xmark','Ditolak']];
                    [$bc,$si,$sl] = $smap[$c['verification_status']] ?? ['badge-default','fa-circle','Unknown'];
                    ?>
                    <span class="badge <?= $bc ?>">
                        <i class="fa-solid <?= $si ?> fa-xs"></i> <?= $sl ?>
                    </span>
                </td>
                <td>
                    <a href="catalog-detail.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-eye fa-xs"></i> Review
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination katalog -->
    <?php if (isset($catPages) && $catPages > 1): ?>
    <div class="paging">
        <?php if ($catPage > 1): ?>
        <a href="?tab=catalogs&status=<?= $catFilter ?>&q=<?= urlencode($catSearch) ?>&page=<?= $catPage-1 ?>" class="page-a"><i class="fa-solid fa-chevron-left fa-xs"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1,$catPage-2); $i <= min($catPages,$catPage+2); $i++): ?>
        <a href="?tab=catalogs&status=<?= $catFilter ?>&q=<?= urlencode($catSearch) ?>&page=<?= $i ?>" class="page-a <?= $i===$catPage?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($catPage < $catPages): ?>
        <a href="?tab=catalogs&status=<?= $catFilter ?>&q=<?= urlencode($catSearch) ?>&page=<?= $catPage+1 ?>" class="page-a"><i class="fa-solid fa-chevron-right fa-xs"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: LAPORAN ══════════════════════════════════════ -->
    <?php elseif ($tab === 'reports'): ?>

    <!-- Filter status laporan -->
    <div style="display:flex;gap:.25rem;margin-bottom:1rem;flex-wrap:wrap;">
        <?php foreach (['all'=>'Semua','pending'=>'Baru','process'=>'Diproses','done'=>'Selesai'] as $v=>$l): ?>
        <a href="?tab=reports&rtype=<?= $v ?>"
           class="btn btn-sm <?= ($rpFilter??'all')===$v?'btn-primary':'btn-outline' ?>">
            <?= $l ?>
            <?php if ($v==='pending' && $reportCount): ?>
            <span style="background:rgba(255,255,255,.3);border-radius:20px;padding:.05rem .35rem;font-size:.65rem;margin-left:.2rem;"><?= $reportCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <span style="font-size:.8rem;color:var(--text3);margin-left:auto;align-self:center;"><?= number_format($rpTotal??0) ?> laporan</span>
    </div>

    <!-- Reports table -->
    <div class="tbl-wrap">
        <div class="tbl-over">
        <table class="data-tbl">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Tipe</th>
                    <th>Target</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reports)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:2rem;">Tidak ada laporan.</td></tr>
            <?php else: ?>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td>
                    <div style="font-weight:700;font-size:.83rem;"><?= e($r['reporter_name']) ?></div>
                    <div style="font-size:.7rem;color:var(--text3);">@<?= e($r['reporter_username']) ?></div>
                </td>
                <td>
                    <?php $typeColor = ['bug'=>'var(--blue)','spam'=>'var(--amber)','fake'=>'var(--red)','inappropriate'=>'var(--red)'];
                    $tc = $typeColor[$r['report_type']] ?? 'var(--text3)'; ?>
                    <span class="badge" style="background:<?= $tc ?>18;color:<?= $tc ?>;">
                        <?= ucfirst($r['report_type']) ?>
                    </span>
                </td>
                <td style="font-size:.8rem;color:var(--text2);">
                    <?php if ($r['reported_post_id']): ?>
                    <a href="<?= APP_URL ?>/post.php?id=<?= $r['reported_post_id'] ?>" target="_blank" style="color:var(--accent);">
                        <i class="fa-solid fa-image fa-xs"></i> Post #<?= $r['reported_post_id'] ?>
                    </a>
                    <?php elseif ($r['reported_catalog_id']): ?>
                    <a href="catalog-detail.php?id=<?= $r['reported_catalog_id'] ?>" style="color:var(--accent);">
                        <i class="fa-solid fa-store fa-xs"></i> Katalog #<?= $r['reported_catalog_id'] ?>
                    </a>
                    <?php else: ?>
                    <span class="text-dim">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:var(--text2);max-width:200px;">
                    <?= e(mb_strimwidth($r['description'] ?? '—', 0, 80, '...')) ?>
                </td>
                <td>
                    <?php $sm=['pending'=>['badge-danger','Baru'],'process'=>['badge-warning','Diproses'],'done'=>['badge-success','Selesai']];
                    [$bc,$sl]=$sm[$r['status']]??['badge-default','Unknown']; ?>
                    <span class="badge <?= $bc ?>"><?= $sl ?></span>
                </td>
                <td style="font-size:.75rem;color:var(--text3);">
                    <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                    <span style="font-size:.68rem;"><?= timeAgo($r['created_at']) ?></span>
                </td>
                <td>
                    <div style="display:flex;flex-direction:column;gap:.25rem;">
                    <?php if ($r['status'] === 'pending'): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="process_report">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm w-100">
                            <i class="fa-solid fa-spinner fa-xs"></i> Proses
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (in_array($r['status'], ['pending','process'])): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="resolve_report">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm w-100"
                            onclick="return confirm('Tandai laporan ini selesai?')">
                            <i class="fa-solid fa-check fa-xs"></i> Selesai
                        </button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:.72rem;color:var(--text3);text-align:center;">
                        <i class="fa-solid fa-circle-check fa-xs" style="color:var(--green)"></i> Done
                    </span>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination laporan -->
    <?php if (isset($rpPages) && $rpPages > 1): ?>
    <div class="paging">
        <?php if ($rpPage > 1): ?>
        <a href="?tab=reports&rtype=<?= $rpFilter ?>&page=<?= $rpPage-1 ?>" class="page-a"><i class="fa-solid fa-chevron-left fa-xs"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1,$rpPage-2); $i <= min($rpPages,$rpPage+2); $i++): ?>
        <a href="?tab=reports&rtype=<?= $rpFilter ?>&page=<?= $i ?>" class="page-a <?= $i===$rpPage?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($rpPage < $rpPages): ?>
        <a href="?tab=reports&rtype=<?= $rpFilter ?>&page=<?= $rpPage+1 ?>" class="page-a"><i class="fa-solid fa-chevron-right fa-xs"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
