<?php
// ============================================================
// RESET PASSWORD DEMO — YummySpot
// Akses file ini sekali di browser, lalu HAPUS file ini!
// URL: http://localhost/yummyspot/reset-demo.php
// ============================================================
require_once __DIR__ . '/config/database.php';

$newPassword = 'password123';
$hash        = password_hash($newPassword, PASSWORD_DEFAULT);
$db          = getDB();

$emails = [
    'admin@yummyspot.id',
    'cs@yummyspot.id',
    'owner@yummyspot.id',
    'user@yummyspot.id',
    'sari@yummyspot.id',
];

$updated = 0;
foreach ($emails as $email) {
    $st = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $st->execute([$hash, $email]);
    $updated += $st->rowCount();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reset Password Demo</title>
<style>
  body { font-family: sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border-radius: 12px; padding: 2rem; max-width: 420px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.1); text-align: center; }
  h2   { color: #22c55e; margin-bottom: .5rem; }
  p    { color: #555; font-size: .9rem; }
  table{ width: 100%; border-collapse: collapse; margin: 1.25rem 0; text-align: left; }
  th,td{ padding: .5rem .75rem; border: 1px solid #e0e0e0; font-size: .85rem; }
  th   { background: #f9f9f9; font-weight: 700; }
  .warn{ background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: .75rem 1rem; font-size: .82rem; color: #854d0e; margin-top: 1rem; }
  a    { color: #FF6B35; font-weight: 700; }
</style>
</head>
<body>
<div class="box">
  <?php if ($updated > 0): ?>
  <h2>✓ Password berhasil direset!</h2>
  <p><?= $updated ?> akun diperbarui dengan password baru.</p>
  <table>
    <thead><tr><th>Email</th><th>Password</th></tr></thead>
    <tbody>
      <?php foreach ($emails as $email): ?>
      <tr><td><?= htmlspecialchars($email) ?></td><td><strong><?= $newPassword ?></strong></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="warn">
    ⚠️ <strong>Segera hapus file ini!</strong><br>
    Jangan biarkan <code>reset-demo.php</code> ada di server production.
  </div>
  <br>
  <a href="login.php">→ Pergi ke halaman Login</a>
  <?php else: ?>
  <h2 style="color:#ef4444">⚠ Tidak ada akun yang diperbarui</h2>
  <p>Pastikan database sudah diimport dan tabel <code>users</code> berisi data demo.</p>
  <?php endif; ?>
</div>
</body>
</html>
