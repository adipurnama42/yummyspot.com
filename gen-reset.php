<?php
// ============================================================
// YUMMYSPOT — Generate Hash & Reset Database
// Akses SEKALI di browser: http://localhost/yummyspot/gen-reset.php
// HAPUS file ini setelah digunakan!
// ============================================================
require_once __DIR__ . '/config/database.php';

$password = 'password123';
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verifikasi hash
$valid = password_verify($password, $hash);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>YummySpot — DB Reset Helper</title>
<style>
  body { font-family: sans-serif; background:#f5f5f5; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .box { background:#fff; border-radius:12px; padding:2rem; max-width:640px; width:100%; box-shadow:0 4px 20px rgba(0,0,0,.1); }
  h2   { color:#FF6B35; margin-bottom:1rem; }
  code { background:#f0f0f0; padding:.2rem .5rem; border-radius:4px; font-size:.85rem; word-break:break-all; display:block; margin:.5rem 0; }
  .ok  { color:#22c55e; font-weight:700; }
  .warn{ background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:.75rem 1rem; font-size:.85rem; color:#92400e; margin-top:1.5rem; }
  .btn { display:inline-block; background:#FF6B35; color:#fff; padding:.65rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:700; margin-top:1rem; border:none; cursor:pointer; font-size:.9rem; }
  .btn-danger { background:#ef4444; }
  table { width:100%; border-collapse:collapse; margin-top:1rem; font-size:.85rem; }
  th,td { padding:.5rem .75rem; border:1px solid #e0e0e0; text-align:left; }
  th    { background:#f9f9f9; font-weight:700; }
</style>
</head>
<body>
<div class="box">
  <h2>YummySpot — DB Reset Helper</h2>

  <p><strong>Hash generated:</strong> <span class="ok"><?= $valid ? '✓ Valid' : '✗ Invalid' ?></span></p>
  <code><?= htmlspecialchars($hash) ?></code>

  <?php if (isset($_POST['do_reset'])): ?>
    <?php
    try {
        $db = getDB();
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Hanya tabel yang ada di install.sql
        $tables = [
            'catalog_verifications',
            'reports',
            'notifications',
            'wishlists',
            'ratings',
            'comments',
            'likes',
            'posts',
            'catalog_images',
            'catalogs',
            'follows',
            'users',
        ];
        foreach ($tables as $t) {
            $db->exec("TRUNCATE TABLE `$t`");
        }
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Insert users
        $users = [
            ['Super Admin',  'superadmin',   'superadmin@yummyspot.com', 'admin', 'Super Administrator YummySpot'],
            ['CS Team',      'cs_yummyspot', 'cs@yummyspot.com',         'cs',    'Tim Customer Service YummySpot'],
        ];
        $stmt = $db->prepare("INSERT INTO users (fullname, username, email, password, role, status, bio) VALUES (?,?,?,?,?,?,?)");
        foreach ($users as $u) {
            $stmt->execute([$u[0], $u[1], $u[2], $hash, $u[3], 'active', $u[4]]);
        }

        // Restore categories
        $cats = [
            ['Kuliner','fa-utensils'],['Wisata Alam','fa-mountain'],
            ['Kafe','fa-mug-hot'],['Pantai','fa-umbrella-beach'],
            ['Budaya','fa-landmark'],['Hiburan','fa-masks-theater'],
            ['Hotel','fa-hotel'],['Belanja','fa-bag-shopping'],
        ];
        $cstmt = $db->prepare("INSERT IGNORE INTO categories (name, icon) VALUES (?,?)");
        foreach ($cats as $c) $cstmt->execute($c);

        $allUsers = $db->query("SELECT id, fullname, email, role FROM users")->fetchAll();
    ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.85rem 1rem;margin-top:1rem;">
      <strong style="color:#16a34a;">✓ Database berhasil direset!</strong>
    </div>
    <table>
      <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Password</th></tr></thead>
      <tbody>
        <?php foreach ($allUsers as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['fullname']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><strong><?= $u['role'] ?></strong></td>
          <td><code style="display:inline;background:#f0f0f0;padding:.1rem .4rem;border-radius:4px;">password123</code></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a href="login.php" class="btn" style="margin-top:1.25rem;">→ Login Sekarang</a>
    <?php
    } catch (Exception $e) {
        echo '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.85rem;margin-top:1rem;color:#dc2626;"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
  <?php else: ?>
    <form method="POST" onsubmit="return confirm('Reset semua data? Tidak bisa dibatalkan!')">
      <p style="color:#555;font-size:.9rem;margin-top:1rem;">
        Klik tombol di bawah untuk mengosongkan database dan membuat ulang akun default.
      </p>
      <table>
        <thead><tr><th>Email</th><th>Password</th><th>Role</th></tr></thead>
        <tbody>
          <tr><td>superadmin@yummyspot.com</td><td><code style="display:inline;padding:.1rem .4rem;background:#f0f0f0;border-radius:4px;">password123</code></td><td>Admin</td></tr>
          <tr><td>cs@yummyspot.com</td><td><code style="display:inline;padding:.1rem .4rem;background:#f0f0f0;border-radius:4px;">password123</code></td><td>CS</td></tr>
        </tbody>
      </table>
      <button type="submit" name="do_reset" class="btn btn-danger" style="margin-top:1.25rem;">
        ⚠ Reset Database Sekarang
      </button>
    </form>
  <?php endif; ?>

  <div class="warn">
    ⚠️ <strong>PENTING:</strong> Hapus file <code style="display:inline;">gen-reset.php</code> setelah selesai!
  </div>
</div>
</body>
</html>
