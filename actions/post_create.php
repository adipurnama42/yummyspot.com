<?php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
verifyCsrf();

$db      = getDB();
$u       = currentUser();
$caption = trim($_POST['caption'] ?? '');
$cslug   = trim($_POST['catalog_slug'] ?? '');

// ── Validasi gambar WAJIB ────────────────────────────────
$hasImage = !empty($_FILES['images']['name'][0]) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK;
if (!$hasImage) {
    flash('error', 'Foto wajib dipilih untuk membuat postingan!');
    redirect(route('home'));
}

// ── Upload gambar ────────────────────────────────────────
$f = [
    'name'     => $_FILES['images']['name'][0],
    'type'     => $_FILES['images']['type'][0],
    'tmp_name' => $_FILES['images']['tmp_name'][0],
    'error'    => $_FILES['images']['error'][0],
    'size'     => $_FILES['images']['size'][0],
];

// Validasi ukuran dan tipe
if ($f['size'] > 5 * 1024 * 1024) {
    flash('error', 'Ukuran file melebihi batas 5MB.');
    redirect(route('home'));
}
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($f['type'], $allowed)) {
    flash('error', 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.');
    redirect(route('home'));
}

$imgUrl = uploadImage($f, 'posts');
if (!$imgUrl) {
    flash('error', 'Gagal mengupload foto. Coba lagi.');
    redirect(route('home'));
}

// ── Cari catalog_id dari slug ────────────────────────────
$cid = null;
if ($cslug !== '') {
    $cs = $db->prepare("SELECT id FROM catalogs WHERE slug = ? AND verification_status = 'approved' LIMIT 1");
    $cs->execute([$cslug]);
    $found = $cs->fetchColumn();
    $cid   = $found ? (int)$found : null;
}

// Validasi katalog WAJIB untuk user biasa (bukan owner)
if ($u['role'] !== 'owner' && $cid === null) {
    flash('error', 'Katalog tempat wajib dipilih!');
    redirect(route('home'));
}

// ── Simpan post ──────────────────────────────────────────
$type = $u['role'] === 'owner' ? 'business' : 'user';
$db->prepare("INSERT INTO posts (user_id, catalog_id, caption, image, post_type) VALUES (?, ?, ?, ?, ?)")
   ->execute([$u['id'], $cid, $caption, $imgUrl, $type]);

$postId = (int)$db->lastInsertId();

flash('success', 'Postingan berhasil dibagikan!');
redirect(APP_URL . '/post.php?id=' . $postId);
