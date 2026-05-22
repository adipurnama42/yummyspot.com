<?php
$pageTitle = 'Admin Dashboard — YummySpot';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
$db  = getDB(); $tab = $_GET['tab']??'overview';
$sts = [
  'users'    =>(int)$db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
  'catalogs' =>(int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='approved'")->fetchColumn(),
  'posts'    =>(int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
  'reviews'  =>(int)$db->query("SELECT COUNT(*) FROM ratings")->fetchColumn(),
  'pending'  =>(int)$db->query("SELECT COUNT(*) FROM catalogs WHERE verification_status='pending'")->fetchColumn(),
  'reports'  =>(int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf(); $act=$_POST['action']??''; $id=(int)($_POST['id']??0);
    if ($act==='ban_user')        { $db->prepare("UPDATE users SET status='suspended' WHERE id=? AND role!='admin'")->execute([$id]); flash('success','Akun disuspend.'); }
    elseif ($act==='unban_user')  { $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]); flash('success','Akun diaktifkan.'); }
    elseif ($act==='change_role') { $role=in_array($_POST['role']??'',['user','owner','cs','admin'])?$_POST['role']:'user'; $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$id]); flash('success','Role diubah.'); }
    elseif ($act==='suspend_cat') { $db->prepare("UPDATE catalogs SET verification_status='rejected' WHERE id=?")->execute([$id]); flash('success','Katalog disuspend.'); }
    redirect(APP_URL.'/admin/dashboard.php?tab='.$tab);
}
$users    = $tab==='users'    ? $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll() : [];
$catalogs = $tab==='catalogs' ? $db->query("SELECT c.*,cat.name AS cat_name,u.fullname AS owner_name FROM catalogs c JOIN categories cat ON c.category_id=cat.id JOIN users u ON c.owner_id=u.id ORDER BY c.created_at DESC LIMIT 50")->fetchAll() : [];
$reports  = $tab==='reports'  ? $db->query("SELECT r.*,u.fullname AS reporter_name FROM reports r JOIN users u ON r.reporter_id=u.id ORDER BY r.created_at DESC LIMIT 50")->fetchAll() : [];
$pg = 'dashboard';
?>
<div class="app-wrap">
<aside class="sidebar dash-sidebar">
  <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem">
    <div style="font-size:.7rem;font-weight:800;color:var(--red);text-transform:uppercase;letter-spacing:.08em"><i class="fa-solid fa-bolt"></i> Super Admin</div>
  </div>
  <a href="?tab=overview"  class="sb-item <?= $tab==='overview' ?'active':'' ?>"><i class="fa-solid fa-gauge si"></i> Overview</a>
  <a href="?tab=users"     class="sb-item <?= $tab==='users'   ?'active':'' ?>"><i class="fa-solid fa-users si"></i> Pengguna</a>
  <a href="?tab=catalogs"  class="sb-item <?= $tab==='catalogs'?'active':'' ?>"><i class="fa-solid fa-building-store si"></i> Katalog</a>
  <a href="?tab=reports"   class="sb-item <?= $tab==='reports' ?'active':'' ?>"><i class="fa-solid fa-flag si"></i> Laporan <span class="sb-count"><?= $sts['reports'] ?></span></a>
  <a href="manage-cs.php"  class="sb-item"><i class="fa-solid fa-shield-halved si"></i> Kelola Tim CS</a>
  <a href="<?= APP_URL ?>/cs/dashboard.php" class="sb-item"><i class="fa-solid fa-eye si"></i> Lihat CS Panel</a>
  <div class="dd-sep"></div>
  <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali</a>
</aside>
<main class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem">
    <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900">Admin Dashboard</h1>
    <span style="display:flex;align-items:center;gap:.35rem;font-size:.78rem;color:var(--green);font-weight:700"><i class="fa-solid fa-circle" style="font-size:.45rem"></i> Sistem Normal</span>
  </div>

  <?php if ($tab==='overview'): ?>
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-users" style="color:var(--blue)"></i> Total User</div><div class="stat-value" style="color:var(--blue)"><?= fmtNum($sts['users']) ?></div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-store" style="color:var(--accent)"></i> Katalog Aktif</div><div class="stat-value" style="color:var(--accent)"><?= fmtNum($sts['catalogs']) ?></div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-image" style="color:var(--purple)"></i> Postingan</div><div class="stat-value" style="color:var(--purple)"><?= fmtNum($sts['posts']) ?></div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-regular fa-star" style="color:var(--amber)"></i> Ulasan</div><div class="stat-value" style="color:var(--amber)"><?= fmtNum($sts['reviews']) ?></div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-clock" style="color:var(--amber)"></i> Pending</div><div class="stat-value" style="color:var(--amber)"><?= $sts['pending'] ?></div><div class="stat-sub">verifikasi katalog</div></div>
    <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-flag" style="color:var(--red)"></i> Laporan</div><div class="stat-value" style="color:var(--red)"><?= $sts['reports'] ?></div><div class="stat-sub">belum ditangani</div></div>
  </div>
  <!-- Role distribution -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-chart-pie" style="color:var(--accent)"></i> Distribusi Role Pengguna</span></div>
    <div class="card-body">
      <?php $roles=$db->query("SELECT role,COUNT(*) AS cnt FROM users WHERE status='active' GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
      $total=array_sum($roles); $rc=['user'=>['var(--blue)','Pengguna'],'owner'=>['var(--amber)','Pemilik'],'cs'=>['var(--green)','CS'],'admin'=>['var(--red)','Admin']];
      foreach ($rc as $r=>[$color,$label]): $cnt=$roles[$r]??0; $pct=$total?round($cnt/$total*100):0; ?>
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.65rem">
        <span style="font-size:.78rem;font-weight:700;width:55px;color:<?= $color ?>"><?= $label ?></span>
        <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden"><div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:4px"></div></div>
        <span style="font-size:.8rem;font-weight:700;min-width:30px;text-align:right"><?= number_format($cnt) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php elseif ($tab==='users'): ?>
  <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
    <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td class="text-dim text-xs">#<?= $u['id'] ?></td>
        <td><div style="font-weight:700;font-size:.85rem"><?= e($u['fullname']) ?></div><div class="text-xs text-dim">@<?= e($u['username']) ?></div></td>
        <td class="text-sm text-muted"><?= e($u['email']) ?></td>
        <td>
          <form method="POST" style="display:inline">
            <?= csrfField() ?><input type="hidden" name="action" value="change_role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <select name="role" class="form-control" style="padding:.2rem .4rem;font-size:.72rem;width:auto" onchange="this.form.submit()" <?= $u['id']==$user['id']?'disabled':'' ?>>
              <?php foreach (['user','owner','cs','admin'] as $r): ?><option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
        <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>"><?= $u['status']==='active'?'Aktif':'Suspended' ?></span></td>
        <td class="text-xs text-dim"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ($u['id']!=$user['id']): ?>
          <form method="POST" style="display:inline">
            <?= csrfField() ?><input type="hidden" name="action" value="<?= $u['status']==='active'?'ban_user':'unban_user' ?>"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $u['status']==='active'?'btn-danger':'btn-success' ?>" onclick="return confirm('<?= $u['status']==='active'?'Suspend akun ini?':'Aktifkan akun?' ?>')">
              <i class="fa-solid fa-<?= $u['status']==='active'?'ban':'check' ?> fa-xs"></i>
            </button>
          </form>
          <?php else: ?><span class="text-dim text-xs">Anda</span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div></div>

  <?php elseif ($tab==='catalogs'): ?>
  <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
    <thead><tr><th>Nama</th><th>Pemilik</th><th>Kategori</th><th>Kota</th><th>Status</th><th>Rating</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($catalogs as $c): ?>
      <tr>
        <td><div style="font-weight:700;font-size:.85rem"><?= e($c['name']) ?></div><div class="text-xs text-dim"><?= e($c['slug']) ?></div></td>
        <td class="text-sm"><?= e($c['owner_name']) ?></td>
        <td class="text-sm text-muted"><?= e($c['cat_name']) ?></td>
        <td class="text-sm text-muted"><?= e($c['city']) ?></td>
        <td><span class="badge <?= match($c['verification_status']){'approved'=>'badge-success','pending'=>'badge-warning',default=>'badge-danger'} ?>"><?= ucfirst($c['verification_status']) ?></span></td>
        <td><span style="color:var(--amber)"><i class="fa-solid fa-star fa-xs"></i></span> <?= number_format($c['avg_rating'],1) ?></td>
        <td style="display:flex;gap:.3rem">
          <a href="<?= APP_URL ?>/catalog-detail.php?slug=<?= e($c['slug']) ?>" class="btn btn-outline btn-sm" target="_blank"><i class="fa-solid fa-eye fa-xs"></i></a>
          <?php if ($c['verification_status']==='approved'): ?>
          <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="suspend_cat"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Suspend katalog ini?')"><i class="fa-solid fa-ban fa-xs"></i></button></form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div></div>

  <?php elseif ($tab==='reports'): ?>
  <div class="tbl-wrap"><div class="tbl-over"><table class="data-tbl">
    <thead><tr><th>ID</th><th>Reporter</th><th>Tipe</th><th>Target</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($reports as $r): ?>
      <tr>
        <td class="text-dim text-xs">#<?= $r['id'] ?></td>
        <td class="text-sm font-700"><?= e($r['reporter_name']) ?></td>
        <td><span class="badge badge-warning text-xs"><?= ucfirst($r['report_type']) ?></span></td>
        <td class="text-xs text-muted"><?= $r['reported_post_id']?'Post #'.$r['reported_post_id']:'' ?><?= $r['reported_catalog_id']?'Katalog #'.$r['reported_catalog_id']:'' ?></td>
        <td><span class="badge <?= match($r['status']){'done'=>'badge-success','process'=>'badge-info',default=>'badge-danger'} ?>"><?= ucfirst($r['status']) ?></span></td>
        <td class="text-xs text-dim"><?= date('d M Y',strtotime($r['created_at'])) ?></td>
        <td>
          <?php if ($r['status']==='pending'): ?>
          <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="process_report"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check fa-xs"></i></button></form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div></div>
  <?php endif; ?>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
