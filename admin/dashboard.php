<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('admin');
$user = currentUser();

$db  = getDB();
$tab = $_GET['tab'] ?? 'overview';

$sts = [
    'users'    => (int)$db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
    'catalogs' => (int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='approved'")->fetchColumn(),
    'posts'    => (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
    'reviews'  => (int)$db->query("SELECT COUNT(*) FROM ratings")->fetchColumn(),
    'pending'  => (int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='pending'")->fetchColumn(),
    'reports'  => (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
];

// ── Handle POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);

    // ── User management ──────────────────────────────────
    if ($act === 'ban_user') {
        $db->prepare("UPDATE users SET status='suspended' WHERE id=? AND role!='admin'")->execute([$id]);
        flash('success', 'Akun disuspend.');
    } elseif ($act === 'unban_user') {
        $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
        flash('success', 'Akun diaktifkan.');
    } elseif ($act === 'change_role') {
        $role = in_array($_POST['role'] ?? '', ['user','owner','cs','admin']) ? $_POST['role'] : 'user';
        $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $id]);
        flash('success', 'Role diubah.');

    // ── Katalog takedown ─────────────────────────────────
    } elseif ($act === 'takedown_catalog') {
        $db->prepare("UPDATE catalogs SET verification_status='rejected' WHERE id=?")->execute([$id]);
        flash('success', 'Katalog di-takedown.');
    } elseif ($act === 'restore_catalog') {
        $db->prepare("UPDATE catalogs SET verification_status='approved' WHERE id=?")->execute([$id]);
        flash('success', 'Katalog dipulihkan.');

    // ── Post takedown ────────────────────────────────────
    } elseif ($act === 'takedown_post') {
        $db->prepare("UPDATE posts SET status='removed' WHERE id=?")->execute([$id]);
        flash('success', 'Postingan di-takedown.');
    } elseif ($act === 'restore_post') {
        $db->prepare("UPDATE posts SET status='published' WHERE id=?")->execute([$id]);
        flash('success', 'Postingan dipulihkan.');

    // ── Resolve report ───────────────────────────────────
    } elseif ($act === 'resolve_report') {
        $db->prepare("UPDATE reports SET status='done' WHERE id=?")->execute([$id]);
        flash('success', 'Laporan diselesaikan.');

    // ── Kategori ─────────────────────────────────────────
    } elseif ($act === 'add_category') {
        $name = trim($_POST['cat_name'] ?? '');
        $icon = trim($_POST['cat_icon'] ?? 'fa-tag');
        if ($name) {
            $chk = $db->prepare("SELECT id FROM categories WHERE name=?");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                flash('error', 'Kategori sudah ada.');
            } else {
                $db->prepare("INSERT INTO categories (name, icon) VALUES (?,?)")->execute([$name, $icon]);
                flash('success', "Kategori \"$name\" berhasil ditambahkan.");
            }
        }
        $tab = 'categories';
    } elseif ($act === 'edit_category') {
        $name = trim($_POST['cat_name'] ?? '');
        $icon = trim($_POST['cat_icon'] ?? 'fa-tag');
        if ($name && $id) {
            $db->prepare("UPDATE categories SET name=?, icon=? WHERE id=?")->execute([$name, $icon, $id]);
            flash('success', 'Kategori diperbarui.');
        }
        $tab = 'categories';
    } elseif ($act === 'delete_category') {
        // Cek apakah ada katalog yang pakai kategori ini
        $used = $db->prepare("SELECT COUNT(*) FROM catalogs WHERE category_id=?");
        $used->execute([$id]);
        if ((int)$used->fetchColumn() > 0) {
            flash('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh katalog.');
        } else {
            $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            flash('success', 'Kategori dihapus.');
        }
        $tab = 'categories';
    }

    redirect(APP_URL . '/admin/dashboard.php?tab=' . $tab);
}

// ── Fetch data per tab ───────────────────────────────────
$users      = $tab === 'users'      ? $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll() : [];
$catalogs   = $tab === 'catalogs'   ? $db->query("SELECT c.*,cat.name AS cat_name,u.fullname AS owner_name FROM catalogs c JOIN categories cat ON c.category_id=cat.id JOIN users u ON c.owner_id=u.id ORDER BY c.created_at DESC LIMIT 50")->fetchAll() : [];
$reports    = $tab === 'reports'    ? $db->query("SELECT r.*,u.fullname AS reporter_name FROM reports r JOIN users u ON r.reporter_id=u.id ORDER BY r.created_at DESC LIMIT 50")->fetchAll() : [];
$categories = $tab === 'categories' ? $db->query("SELECT cat.*, (SELECT COUNT(*) FROM catalogs WHERE category_id=cat.id) AS total FROM categories cat ORDER BY cat.id")->fetchAll() : [];
$posts      = $tab === 'posts'      ? $db->query("SELECT p.*,u.fullname,u.username FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 50")->fetchAll() : [];

$pg = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// FA icon options
$iconOptions = [
    'fa-utensils','fa-mug-saucer','fa-mountain','fa-water',
    'fa-landmark','fa-star','fa-hotel','fa-cart-shopping',
    'fa-map-pin','fa-star','fa-heart','fa-camera','fa-tree',
    'fa-fish','fa-burger','fa-pizza-slice','fa-ice-cream',
    'fa-wine-glass','fa-coffee','fa-store','fa-tag',
];
?>

<div class="app-wrap">
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--red);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-bolt"></i> Super Admin
        </div>
    </div>
    <a href="?tab=overview"    class="sb-item <?= $tab==='overview'   ?'active':'' ?>"><i class="fa-solid fa-gauge si"></i> Overview</a>
    <a href="?tab=users"       class="sb-item <?= $tab==='users'      ?'active':'' ?>"><i class="fa-solid fa-users si"></i> Pengguna</a>
    <a href="?tab=catalogs"    class="sb-item <?= $tab==='catalogs'   ?'active':'' ?>"><i class="fa-solid fa-store si"></i> Katalog</a>
    <a href="?tab=posts"       class="sb-item <?= $tab==='posts'      ?'active':'' ?>"><i class="fa-solid fa-image si"></i> Postingan</a>
    <a href="?tab=categories"  class="sb-item <?= $tab==='categories' ?'active':'' ?>"><i class="fa-solid fa-layer-group si"></i> Kategori</a>
    <a href="?tab=reports"     class="sb-item <?= $tab==='reports'    ?'active':'' ?>"><i class="fa-solid fa-flag si"></i> Laporan
        <?php if ($sts['reports']): ?><span class="sb-count"><?= $sts['reports'] ?></span><?php endif; ?>
    </a>
    <a href="manage-cs.php"    class="sb-item"><i class="fa-solid fa-shield-halved si"></i> Kelola Tim CS</a>
    <a href="<?= APP_URL ?>/cs/dashboard.php" class="sb-item"><i class="fa-solid fa-eye si"></i> Lihat CS Panel</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali</a>
</aside>

<main class="main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">Admin Dashboard</h1>
        <span style="display:flex;align-items:center;gap:.35rem;font-size:.78rem;color:var(--green);font-weight:700;">
            <i class="fa-solid fa-circle" style="font-size:.45rem;"></i> Sistem Normal
        </span>
    </div>

    <!-- ══ OVERVIEW ═══════════════════════════════════════ -->
    <?php if ($tab === 'overview'): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr));">
        <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-users" style="color:var(--blue)"></i> Total User</div><div class="stat-value" style="color:var(--blue);"><?= fmtNum($sts['users']) ?></div></div>
        <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-store" style="color:var(--accent)"></i> Katalog Aktif</div><div class="stat-value" style="color:var(--accent);"><?= fmtNum($sts['catalogs']) ?></div></div>
        <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-image" style="color:var(--purple)"></i> Postingan</div><div class="stat-value" style="color:var(--purple);"><?= fmtNum($sts['posts']) ?></div></div>
        <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-star" style="color:var(--amber)"></i> Ulasan</div><div class="stat-value" style="color:var(--amber);"><?= fmtNum($sts['reviews']) ?></div></div>
        <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-clock" style="color:var(--amber)"></i> Pending</div><div class="stat-value" style="color:var(--amber);"><?= $sts['pending'] ?></div><div class="stat-sub">verifikasi katalog</div></div>
        <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-flag" style="color:var(--red)"></i> Laporan</div><div class="stat-value" style="color:var(--red);"><?= $sts['reports'] ?></div><div class="stat-sub">belum ditangani</div></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-chart-pie" style="color:var(--accent)"></i> Distribusi Role</span></div>
        <div class="card-body">
            <?php $roles=$db->query("SELECT role,COUNT(*) AS cnt FROM users WHERE status='active' GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
            $total=array_sum($roles); $rc=['user'=>['var(--blue)','Pengguna'],'owner'=>['var(--amber)','Pemilik'],'cs'=>['var(--green)','CS'],'admin'=>['var(--red)','Admin']];
            foreach ($rc as $r=>[$color,$label]): $cnt=$roles[$r]??0; $pct=$total?round($cnt/$total*100):0; ?>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.65rem;">
                <span style="font-size:.78rem;font-weight:700;width:55px;color:<?= $color ?>;"><?= $label ?></span>
                <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden;"><div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div></div>
                <span style="font-size:.8rem;font-weight:700;min-width:30px;text-align:right;"><?= number_format($cnt) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══ USERS ══════════════════════════════════════════ -->
    <?php elseif ($tab === 'users'): ?>
    <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
        <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td class="text-dim text-xs">#<?= $u['id'] ?></td>
            <td><div style="font-weight:700;font-size:.85rem;"><?= e($u['fullname']) ?></div><div class="text-xs text-dim">@<?= e($u['username']) ?></div></td>
            <td class="text-sm text-muted"><?= e($u['email']) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="change_role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <select name="role" class="form-control" style="padding:.2rem .4rem;font-size:.72rem;width:auto;" onchange="this.form.submit()" <?= $u['id']==$user['id']?'disabled':'' ?>>
                        <?php foreach (['user','owner','cs','admin'] as $r): ?><option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
                    </select>
                </form>
            </td>
            <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>"><?= $u['status']==='active'?'Aktif':'Suspended' ?></span></td>
            <td class="text-xs text-dim"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['id']!=$user['id']): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="<?= $u['status']==='active'?'ban_user':'unban_user' ?>"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= $u['status']==='active'?'btn-danger':'btn-success' ?>" onclick="return confirm('<?= $u['status']==='active'?'Suspend akun?':'Aktifkan akun?' ?>')">
                        <i class="fa-solid fa-<?= $u['status']==='active'?'ban':'check' ?> fa-xs"></i>
                    </button>
                </form>
                <?php else: ?><span class="text-dim text-xs">Anda</span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>

    <!-- ══ KATALOG ════════════════════════════════════════ -->
    <?php elseif ($tab === 'catalogs'): ?>
    <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
        <thead><tr><th>Nama</th><th>Pemilik</th><th>Kategori</th><th>Kota</th><th>Status</th><th>Rating</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($catalogs as $c): ?>
        <tr>
            <td><div style="font-weight:700;font-size:.85rem;"><?= e($c['name']) ?></div><div class="text-xs text-dim"><?= e($c['slug']) ?></div></td>
            <td class="text-sm"><?= e($c['owner_name']) ?></td>
            <td class="text-sm text-muted"><?= e($c['cat_name']) ?></td>
            <td class="text-sm text-muted"><?= e($c['city']) ?></td>
            <td>
                <?php $sm=['approved'=>['badge-success','Approved'],'pending'=>['badge-warning','Pending'],'rejected'=>['badge-danger','Ditolak']];
                [$bc,$sl]=$sm[$c['verification_status']]??['badge-default','Unknown']; ?>
                <span class="badge <?= $bc ?>"><?= $sl ?></span>
            </td>
            <td><span style="color:var(--amber);"><i class="fa-solid fa-star fa-xs"></i></span> <?= number_format($c['avg_rating'],1) ?></td>
            <td style="display:flex;gap:.3rem;align-items:center;">
                <a href="<?= APP_URL ?>/catalog-detail.php?slug=<?= e($c['slug']) ?>" class="btn btn-outline btn-sm" target="_blank"><i class="fa-solid fa-eye fa-xs"></i></a>
                <?php if ($c['verification_status'] === 'approved'): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="takedown_catalog"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Takedown katalog ini? Tidak akan tampil ke publik.')">
                        <i class="fa-solid fa-ban fa-xs"></i> Takedown
                    </button>
                </form>
                <?php elseif ($c['verification_status'] === 'rejected'): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="restore_catalog"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Pulihkan katalog ini?')">
                        <i class="fa-solid fa-rotate-left fa-xs"></i> Pulihkan
                    </button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>

    <!-- ══ POSTINGAN ══════════════════════════════════════ -->
    <?php elseif ($tab === 'posts'): ?>
    <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
        <thead><tr><th>Postingan</th><th>Pengguna</th><th>Tipe</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($posts as $p): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <?php if ($p['image']): ?>
                    <img src="<?= e($p['image']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:var(--r-sm);flex-shrink:0;">
                    <?php else: ?>
                    <div style="width:40px;height:40px;background:var(--bg);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-regular fa-image text-dim"></i></div>
                    <?php endif; ?>
                    <div style="font-size:.82rem;color:var(--text2);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($p['caption'] ?? '(Tidak ada caption)') ?>
                    </div>
                </div>
            </td>
            <td><div style="font-size:.83rem;font-weight:600;"><?= e($p['fullname']) ?></div><div class="text-xs text-dim">@<?= e($p['username']) ?></div></td>
            <td><span class="badge <?= $p['post_type']==='business'?'badge-warning':'badge-info' ?>"><?= $p['post_type']==='business'?'Bisnis':'User' ?></span></td>
            <td>
                <?php if ($p['status']==='published'): ?>
                <span class="badge badge-success"><i class="fa-solid fa-circle fa-xs" style="font-size:.4rem;"></i> Aktif</span>
                <?php else: ?>
                <span class="badge badge-danger"><i class="fa-solid fa-ban fa-xs"></i> Takedown</span>
                <?php endif; ?>
            </td>
            <td class="text-xs text-dim"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td style="display:flex;gap:.3rem;">
                <a href="<?= APP_URL ?>/post.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" target="_blank"><i class="fa-solid fa-eye fa-xs"></i></a>
                <?php if ($p['status'] === 'published'): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="takedown_post"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Takedown postingan ini?')">
                        <i class="fa-solid fa-ban fa-xs"></i> Takedown
                    </button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="restore_post"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Pulihkan postingan ini?')">
                        <i class="fa-solid fa-rotate-left fa-xs"></i> Pulihkan
                    </button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>

    <!-- ══ KATEGORI ═══════════════════════════════════════ -->
    <?php elseif ($tab === 'categories'): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <div style="font-size:.85rem;color:var(--text3);"><?= count($categories) ?> kategori tersedia</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('add-cat-modal')">
            <i class="fa-solid fa-plus fa-xs"></i> Tambah Kategori
        </button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.75rem;">
        <?php foreach ($categories as $cat): ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1rem;display:flex;align-items:center;gap:.85rem;">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid <?= e($cat['icon']) ?>" style="color:var(--accent);font-size:1rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.9rem;"><?= e($cat['name']) ?></div>
                <div style="font-size:.72rem;color:var(--text3);"><?= $cat['total'] ?> katalog</div>
            </div>
            <div style="display:flex;gap:.3rem;flex-shrink:0;">
                <button class="btn btn-outline btn-sm btn-icon" onclick="openEditCat(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', '<?= $cat['icon'] ?>')" title="Edit">
                    <i class="fa-solid fa-pen fa-xs"></i>
                </button>
                <?php if ($cat['total'] == 0): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm btn-icon" style="color:var(--red);" onclick="return confirm('Hapus kategori \"<?= addslashes($cat['name']) ?>\"?')" title="Hapus">
                        <i class="fa-solid fa-trash fa-xs"></i>
                    </button>
                </form>
                <?php else: ?>
                <button class="btn btn-ghost btn-sm btn-icon" disabled style="opacity:.3;" title="Tidak bisa dihapus karena masih digunakan">
                    <i class="fa-solid fa-trash fa-xs"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ══ LAPORAN ════════════════════════════════════════ -->
    <?php elseif ($tab === 'reports'): ?>
    <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
        <thead><tr><th>Reporter</th><th>Tipe</th><th>Target</th><th>Deskripsi</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
            <td style="font-weight:700;font-size:.83rem;"><?= e($r['reporter_name']) ?></td>
            <td><?php $tc=['bug'=>'var(--blue)','spam'=>'var(--amber)','fake'=>'var(--red)','inappropriate'=>'var(--red)'][$r['report_type']]??'var(--text3)'; ?>
                <span class="badge" style="background:<?= $tc ?>18;color:<?= $tc ?>;"><?= ucfirst($r['report_type']) ?></span></td>
            <td style="font-size:.8rem;color:var(--text2);">
                <?php if ($r['reported_post_id']): ?>
                <a href="<?= APP_URL ?>/post.php?id=<?= $r['reported_post_id'] ?>" target="_blank" style="color:var(--accent);"><i class="fa-solid fa-image fa-xs"></i> Post #<?= $r['reported_post_id'] ?></a>
                <?php elseif ($r['reported_catalog_id']): ?>
                <a href="<?= APP_URL ?>/admin/dashboard.php?tab=catalogs" style="color:var(--accent);"><i class="fa-solid fa-store fa-xs"></i> Katalog #<?= $r['reported_catalog_id'] ?></a>
                <?php else: ?><span class="text-dim">—</span><?php endif; ?>
            </td>
            <td style="font-size:.78rem;color:var(--text2);max-width:200px;"><?= e(mb_strimwidth($r['description']??'—',0,80,'...')) ?></td>
            <td><?php $sm=['pending'=>['badge-danger','Baru'],'process'=>['badge-warning','Diproses'],'done'=>['badge-success','Selesai']];
                [$bc,$sl]=$sm[$r['status']]??['badge-default','Unknown']; ?><span class="badge <?= $bc ?>"><?= $sl ?></span></td>
            <td class="text-xs text-dim"><?= date('d M Y',strtotime($r['created_at'])) ?></td>
            <td>
                <?php if ($r['status'] !== 'done'): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?><input type="hidden" name="action" value="resolve_report"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Tandai selesai?')"><i class="fa-solid fa-check fa-xs"></i></button>
                </form>
                <?php else: ?><span class="text-dim text-xs"><i class="fa-solid fa-check-circle fa-xs" style="color:var(--green)"></i></span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <?php endif; ?>

</main>
</div>

<!-- ── Modal: Tambah Kategori ───────────────────────────── -->
<div class="modal-ov" id="add-cat-modal">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title"><i class="fa-solid fa-plus" style="color:var(--accent)"></i> Tambah Kategori</span>
            <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label>Nama Kategori <span style="color:var(--red)">*</span></label>
                    <input type="text" name="cat_name" class="form-control" placeholder="Contoh: Cafe, Wisata, Hotel..." required>
                </div>
                <div class="form-group">
                    <label>Icon Font Awesome</label>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <div class="input-wrap" style="flex:1;">
                            <i class="fa-solid fa-tag i-icon fa-xs" id="preview-icon-add"></i>
                            <input type="text" name="cat_icon" id="icon-input-add" class="form-control"
                                value="fa-tag" placeholder="fa-utensils"
                                oninput="document.getElementById('preview-icon-add').className='fa-solid '+this.value+' i-icon fa-xs'">
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem;">
                        <?php foreach ($iconOptions as $ico): ?>
                        <button type="button"
                            onclick="document.getElementById('icon-input-add').value='<?= $ico ?>'; document.getElementById('preview-icon-add').className='fa-solid <?= $ico ?> i-icon fa-xs'; document.querySelectorAll('.ico-btn-add').forEach(b=>b.style.borderColor='var(--border)'); this.style.borderColor='var(--accent)';"
                            class="ico-btn-add" title="<?= $ico ?>"
                            style="width:36px;height:36px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:border .15s;">
                            <i class="fa-solid <?= $ico ?> fa-xs" style="color:var(--text2);"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-hint">Pilih icon di atas atau ketik nama class FA (tanpa prefix "fa-solid")</div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('add-cat-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk fa-xs"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Edit Kategori ─────────────────────────────── -->
<div class="modal-ov" id="edit-cat-modal">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title"><i class="fa-solid fa-pen" style="color:var(--accent)"></i> Edit Kategori</span>
            <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="id" id="edit-cat-id">
                <div class="form-group">
                    <label>Nama Kategori <span style="color:var(--red)">*</span></label>
                    <input type="text" name="cat_name" id="edit-cat-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Icon Font Awesome</label>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <div class="input-wrap" style="flex:1;">
                            <i class="fa-solid fa-tag i-icon fa-xs" id="preview-icon-edit"></i>
                            <input type="text" name="cat_icon" id="icon-input-edit" class="form-control"
                                oninput="document.getElementById('preview-icon-edit').className='fa-solid '+this.value+' i-icon fa-xs'">
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem;">
                        <?php foreach ($iconOptions as $ico): ?>
                        <button type="button"
                            onclick="document.getElementById('icon-input-edit').value='<?= $ico ?>'; document.getElementById('preview-icon-edit').className='fa-solid <?= $ico ?> i-icon fa-xs'; document.querySelectorAll('.ico-btn-edit').forEach(b=>b.style.borderColor='var(--border)'); this.style.borderColor='var(--accent)';"
                            class="ico-btn-edit" title="<?= $ico ?>"
                            style="width:36px;height:36px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:border .15s;">
                            <i class="fa-solid <?= $ico ?> fa-xs" style="color:var(--text2);"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('edit-cat-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk fa-xs"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Tambah action resolve_report yang belum ada
document.addEventListener('submit', function(e) {
    const form = e.target;
    const action = form.querySelector('[name="action"]')?.value;
    if (action === 'resolve_report') {
        // sudah handled di PHP
    }
});

function openEditCat(id, name, icon) {
    document.getElementById('edit-cat-id').value   = id;
    document.getElementById('edit-cat-name').value = name;
    document.getElementById('icon-input-edit').value = icon;
    document.getElementById('preview-icon-edit').className = 'fa-solid ' + icon + ' i-icon fa-xs';
    openModal('edit-cat-modal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
