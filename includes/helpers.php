<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Buffer output agar header tidak terlanjur terkirim
        if (!ob_get_level()) ob_start();
        session_set_cookie_params(['lifetime'=>86400*30,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
}
function currentUser()  { startSession(); return $_SESSION['user'] ?? null; }
function isLoggedIn()   { return currentUser() !== null; }
function requireLogin() {
    if (!isLoggedIn()) { header('Location:'.APP_URL.'/login.php?redirect='.urlencode($_SERVER['REQUEST_URI'])); exit; }
}
function requireRole($roles) {
    requireLogin();
    $u = currentUser(); $roles = (array)$roles;
    if (!in_array($u['role'], $roles)) { header('Location:'.APP_URL.'/index.php?error=forbidden'); exit; }
}
function loginUser(array $u) {
    startSession();
    // Hanya regenerate jika belum ada output
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = [
        'id'              => $u['id'],
        'email'           => $u['email'],
        'username'        => $u['username'],
        'fullname'        => $u['fullname'],
        'role'            => $u['role'],
        'profile_picture' => $u['profile_picture'],
    ];
}
function logoutUser()   { startSession(); session_destroy(); }

// Security
function e($s)           { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrfToken()     { startSession(); if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verifyCsrf()    { if (!hash_equals(csrfToken(), $_POST['csrf_token']??'')) { http_response_code(403); die('Invalid CSRF'); } }
function csrfField()     { return '<input type="hidden" name="csrf_token" value="'.csrfToken().'">'; }
function redirect($url) {
    // Flush buffer sebelum redirect
    while (ob_get_level()) ob_end_clean();
    header('Location:' . $url);
    exit;
}

// Flash
function flash($key, $msg=null) {
    startSession();
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return null; }
    $v = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $v;
}

// Upload
function uploadImage($file, $folder='posts') {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return false;
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = uniqid('img_', true).'.'.$ext;
    $dir  = UPLOAD_PATH.$folder.'/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dir.$name)) return UPLOAD_URL.$folder.'/'.$name;
    return false;
}

// Slug
function slugify($t) {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9\s-]/','',$t);
    return trim(preg_replace('/[\s-]+/','-',$t),'-');
}

// Time ago
function timeAgo($dt) {
    $s = time() - strtotime($dt);
    if ($s < 60)     return 'baru saja';
    if ($s < 3600)   return floor($s/60).' mnt lalu';
    if ($s < 86400)  return floor($s/3600).' jam lalu';
    if ($s < 604800) return floor($s/86400).' hari lalu';
    return date('d M Y', strtotime($dt));
}

// Number format
function fmtNum($n) {
    if ($n >= 1000000) return round($n/1000000,1).'jt';
    if ($n >= 1000)    return round($n/1000,1).'rb';
    return (string)$n;
}

// Avatar initials
function initials($name) {
    $p = explode(' ', trim($name));
    return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));
}

// Unread notifications
function unreadNotifCount($userId) {
    $s = getDB()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}

// Create notification
function createNotif($userId, $fromId, $type, $targetId, $msg) {
    if ($userId == $fromId) return;
    getDB()->prepare("INSERT INTO notifications (user_id,from_user_id,type,target_id,message) VALUES (?,?,?,?,?)")
           ->execute([$userId, $fromId, $type, $targetId, $msg]);
}

// ── URL Helper ───────────────────────────────────────────
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function route($name, $param = null) {
    $map = [
        'home'          => '',
        'login'         => 'login',
        'register'      => 'register',
        'logout'        => 'logout',
        'explore'       => 'explore',
        'catalog'       => 'katalog',
        'wishlist'      => 'wishlist',
        'notifications' => 'notifikasi',
        'my-reports'    => 'laporan-saya',
        'owner'         => 'owner',
        'owner.catalogs'=> 'owner/katalog',
        'owner.create'  => 'owner/tambah',
        'owner.reviews' => 'owner/ulasan',
        'owner.analytics'=> 'owner/analitik',
        'cs'            => 'cs',
        'admin'         => 'admin',
        'admin.cs'      => 'admin/cs',
    ];

    // Route dengan parameter
    if ($name === 'post'    && $param) return url('post/'    . $param);
    if ($name === 'profile' && $param) return url('user/'    . $param);
    if ($name === 'katalog' && $param) return url('katalog/' . $param);
    if ($name === 'owner.edit' && $param) return url('owner/edit/' . $param);
    if ($name === 'cs.katalog' && $param) return url('cs/katalog/' . $param);
    if ($name === 'report'  && $param) return url('laporan?' . $param);

    return url($map[$name] ?? $name);
}
