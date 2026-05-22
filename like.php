<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
verifyCsrf();

$pid = (int)($_POST['post_id'] ?? 0);
if (!$pid) { echo json_encode(['ok' => false]); exit; }

$db  = getDB();
$me  = currentUser();
$uid = $me['id'];

// Cek existing like
$ex = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$ex->execute([$uid, $pid]);

if ($ex->fetchColumn()) {
    // Unlike
    $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?")->execute([$uid, $pid]);
    $liked = false;
} else {
    // Like
    $db->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)")->execute([$uid, $pid]);
    $liked = true;

    // Kirim notifikasi ke pemilik post (jika bukan diri sendiri)
    $pSt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $pSt->execute([$pid]);
    $authorId = (int)$pSt->fetchColumn();

    if ($authorId && $authorId !== $uid) {
        createNotif(
            $authorId,
            $uid,
            'like',
            $pid,
            $me['fullname'] . ' menyukai postinganmu'
        );
    }
}

// Hitung total like terbaru
$cSt = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$cSt->execute([$pid]);
$count = (int)$cSt->fetchColumn();

echo json_encode(['ok' => true, 'liked' => $liked, 'count' => $count]);
