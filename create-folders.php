<?php
// ============================================================
// YUMMYSPOT — Buat folder uploads
// Akses SEKALI: http://localhost/yummyspot/create-folders.php
// Hapus setelah selesai!
// ============================================================
$base = __DIR__ . '/uploads/';
$folders = ['avatars', 'catalogs', 'posts', 'gallery'];
$results = [];

foreach ($folders as $f) {
    $path = $base . $f;
    if (!is_dir($path)) {
        $ok = mkdir($path, 0755, true);
        $results[] = [$f, $ok ? '✓ Dibuat' : '✗ Gagal', $ok];
    } else {
        $results[] = [$f, '✓ Sudah ada', true];
    }
    // Pastikan writable
    if (is_dir($path) && !is_writable($path)) {
        chmod($path, 0755);
        $results[] = [$f, '⚠ Diperbaiki permission', true];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Create Folders</title>
<style>body{font-family:sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{background:#fff;border-radius:12px;padding:2rem;max-width:400px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.1)}table{width:100%;border-collapse:collapse;margin:1rem 0}td{padding:.5rem .75rem;border:1px solid #e0e0e0;font-size:.85rem}.ok{color:#16a34a}.err{color:#dc2626}.warn{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:.75rem 1rem;font-size:.82rem;color:#92400e;margin-top:1rem}a{color:#FF6B35;font-weight:700}</style>
</head>
<body>
<div class="box">
  <h2 style="color:#FF6B35;margin-bottom:.5rem;">📁 Setup Folder Uploads</h2>
  <table>
    <thead><tr><th>Folder</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($results as [$folder, $status, $ok]): ?>
      <tr><td>uploads/<?= $folder ?>/</td><td class="<?= $ok?'ok':'err' ?>"><?= $status ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="font-size:.85rem;color:#555;">Sekarang coba upload foto profil lagi.</p>
  <div class="warn">⚠️ Hapus file <code>create-folders.php</code> setelah selesai!</div>
  <br>
  <a href="login.php">→ Kembali ke Login</a>
</div>
</body>
</html>
