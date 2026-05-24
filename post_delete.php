<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
verifyCsrf();

$postId = (int)($_POST['post_id'] ?? 0);
if (!$postId) { echo json_encode(['ok'=>false,'msg'=>'ID tidak valid']); exit; }

$db = getDB();
$me = currentUser();

// Hanya pemilik post atau admin yang bisa hapus
$st = $db->prepare("SELECT user_id FROM posts WHERE id = ? AND status = 'published'");
$st->execute([$postId]);
$post = $st->fetch();

if (!$post) { echo json_encode(['ok'=>false,'msg'=>'Postingan tidak ditemukan']); exit; }
if ($post['user_id'] != $me['id'] && $me['role'] !== 'admin') {
    echo json_encode(['ok'=>false,'msg'=>'Tidak diizinkan']); exit;
}

$db->prepare("UPDATE posts SET status = 'removed' WHERE id = ?")->execute([$postId]);
echo json_encode(['ok'=>true]);
