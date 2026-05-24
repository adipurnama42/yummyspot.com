<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
requireLogin();
$user = currentUser();
$pageTitle = 'Notifikasi — YummySpot';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Mark all as read jika ada parameter
if (isset($_GET['mark_read'])) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);
    redirect(route('notifications'));
}

$page = max(1, (int)($_GET['page'] ?? 1));
$lmt  = 20;
$off  = ($page - 1) * $lmt;

$tSt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$tSt->execute([$user['id']]);
$total = (int)$tSt->fetchColumn();

$unread = (int)$db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0")->execute([$user['id']]) ? 0 : 0;
$uSt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$uSt->execute([$user['id']]);
$unread = (int)$uSt->fetchColumn();

$st = $db->prepare("
    SELECT n.*, u.fullname AS sender_name, u.username AS sender_username, u.profile_picture AS sender_pic
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$st->execute([$user['id'], $lmt, $off]);
$notifs = $st->fetchAll();

// Mark fetched as read
$db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user['id']]);

$pages = (int)ceil($total / $lmt);

// Notif icon & color per type
$typeMap = [
    'like'     => ['fa-heart',         'var(--red)',    'Menyukai postinganmu'],
    'comment'  => ['fa-comment',       'var(--blue)',   'Mengomentari postinganmu'],
    'follow'   => ['fa-user-plus',     'var(--accent)', 'Mulai mengikutimu'],
    'review'   => ['fa-star',          'var(--amber)',  'Memberi ulasan'],
    'verified' => ['fa-check-circle',  'var(--green)',  'Katalogmu diverifikasi'],
    'rejected' => ['fa-times-circle',  'var(--red)',    'Katalogmu ditolak'],
    'report'   => ['fa-flag',          'var(--amber)',  'Laporan baru'],
];

$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];

// Group by date
$grouped = [];
foreach ($notifs as $n) {
    $date = date('Y-m-d', strtotime($n['created_at']));
    $grouped[$date][] = $n;
}
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:640px; margin:0 auto;">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-regular fa-bell" style="color:var(--accent)"></i> Notifikasi
            </h1>
            <?php if ($unread > 0): ?>
            <div style="font-size:.78rem;color:var(--text3);margin-top:.1rem;">
                <?= $unread ?> belum dibaca
            </div>
            <?php endif; ?>
        </div>
        <?php if ($total > 0): ?>
        <a href="?mark_read=1" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-check-double fa-xs"></i> Tandai Semua Dibaca
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
    <!-- Empty state -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:3rem;color:var(--border2);margin-bottom:1rem;">
            <i class="fa-regular fa-bell"></i>
        </div>
        <h3 style="font-family:'Nunito',sans-serif;font-size:1rem;font-weight:800;margin-bottom:.4rem;color:var(--text2);">
            Belum ada notifikasi
        </h3>
        <p style="font-size:.85rem;color:var(--text3);">
            Notifikasi akan muncul saat ada aktivitas baru.
        </p>
    </div>

    <?php else: ?>

    <?php foreach ($grouped as $date => $group):
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $label = match($date) {
            $today     => 'Hari ini',
            $yesterday => 'Kemarin',
            default    => date('d F Y', strtotime($date))
        };
    ?>

    <!-- Date label -->
    <div style="font-size:.68rem;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;padding:.35rem .1rem;margin-top:.85rem;margin-bottom:.35rem;">
        <?= $label ?>
    </div>

    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;">
        <?php foreach ($group as $i => $n):
            $isLast  = $i === count($group) - 1;
            $tm      = $typeMap[$n['type']] ?? ['fa-bell', 'var(--text3)', ''];
            $icon    = $tm[0]; $color = $tm[1];
            $hasFrom = $n['sender_name'];
            $sp      = $hasFrom ? $pals[(($n['from_user_id'] ?? 1) - 1) % count($pals)] : ['#e5e7eb','#f9fafb'];
        ?>
        <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.9rem 1rem;<?= !$isLast ? 'border-bottom:1px solid var(--border);' : '' ?>background:<?= !$n['is_read'] ? '#fffbf8' : '#fff' ?>;transition:background .2s;"
             onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='<?= !$n['is_read'] ? '#fffbf8' : '#fff' ?>'">

            <!-- Avatar / Icon -->
            <div style="position:relative;flex-shrink:0;">
                <?php if ($hasFrom): ?>
                <div class="avatar av-44" style="background:<?= $sp[1] ?>;color:<?= $sp[0] ?>;">
                    <?php if ($n['sender_pic']): ?>
                        <img src="<?= e($n['sender_pic']) ?>" alt="">
                    <?php else: ?>
                        <?= initials($n['sender_name']) ?>
                    <?php endif; ?>
                </div>
                <!-- Type icon badge -->
                <div style="position:absolute;bottom:-2px;right:-2px;width:18px;height:18px;border-radius:50%;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;border:2px solid #fff;">
                    <i class="fa-solid <?= $icon ?>" style="font-size:.45rem;color:#fff;"></i>
                </div>
                <?php else: ?>
                <!-- System notification -->
                <div style="width:44px;height:44px;border-radius:50%;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;font-size:.9rem;"></i>
                </div>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div style="flex:1;min-width:0;">
                <div style="font-size:.875rem;line-height:1.5;color:var(--text);">
                    <?php if ($hasFrom): ?>
                    <a href="profile.php?u=<?= e($n['sender_username']) ?>" style="font-weight:700;color:var(--text);">
                        <?= e($n['sender_name']) ?>
                    </a>
                    <?php endif; ?>
                    <?= e($n['message']) ?>
                </div>
                <div style="font-size:.7rem;color:var(--text3);margin-top:.3rem;display:flex;align-items:center;gap:.4rem;">
                    <i class="fa-regular fa-clock fa-xs"></i>
                    <?= timeAgo($n['created_at']) ?>
                    <?php if (!$n['is_read']): ?>
                    <span style="width:6px;height:6px;background:var(--accent);border-radius:50%;display:inline-block;"></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action link -->
            <?php
            $link = null;
            if ($n['type'] === 'like' || $n['type'] === 'comment') $link = APP_URL . '/post.php?id=' . $n['target_id'];
            elseif ($n['type'] === 'follow')   $link = APP_URL . '/profile.php?u=' . $n['sender_username'] ?? '');
            elseif ($n['type'] === 'verified' || $n['type'] === 'rejected') $link = 'owner/catalogs.php';
            ?>
            <?php if ($link): ?>
            <a href="<?= e($link) ?>" style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text3);transition:all .18s;" onmouseover="this.style.background='var(--accent)';this.style.color='#fff'" onmouseout="this.style.background='var(--bg)';this.style.color='var(--text3)'" title="Lihat">
                <i class="fa-solid fa-arrow-right fa-xs"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="paging">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-a"><i class="fa-solid fa-chevron-left fa-xs"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>" class="page-a <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-a"><i class="fa-solid fa-chevron-right fa-xs"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
