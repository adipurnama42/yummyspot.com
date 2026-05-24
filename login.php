<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    $u = currentUser();
    if ($u['role'] === 'admin')     redirect(APP_URL . '/admin/dashboard.php');
    elseif ($u['role'] === 'cs')    redirect(APP_URL . '/cs/dashboard.php');
    elseif ($u['role'] === 'owner') redirect(APP_URL . '/owner/dashboard.php');
    else redirect(APP_URL . '/index.php');
}

// ── Handle POST sebelum output apapun ───────────────────
$err   = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    if ($email && $pass) {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            loginUser($u);
            if ($u['role'] === 'admin')     redirect(APP_URL . '/admin/dashboard.php');
            elseif ($u['role'] === 'cs')    redirect(APP_URL . '/cs/dashboard.php');
            elseif ($u['role'] === 'owner') redirect(APP_URL . '/owner/dashboard.php');
            else redirect($_GET['redirect'] ?? APP_URL . '/index.php');
        } else {
            $err = 'Email atau password salah.';
        }
    } else {
        $err = 'Harap isi semua kolom.';
    }
}

// ── Output HTML ──────────────────────────────────────────
$pageTitle = 'Masuk — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-main">Yummy<span>Spot</span></div>
      <div class="logo-sub">Social Media Katalog Tempat Wisata &amp; Kuliner</div>
    </div>
    <div class="auth-tabs">
      <a href="login.php"    class="auth-tab active">Masuk</a>
      <a href="register.php" class="auth-tab">Daftar</a>
    </div>

    <?php if ($err): ?>
    <div class="alert alert-error" data-dismiss>
      <i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfField() ?>
      <div class="form-group">
        <label>Email</label>
        <div class="input-wrap">
          <i class="fa-regular fa-envelope i-icon fa-xs"></i>
          <input type="email" name="email" class="form-control"
            placeholder="email@kamu.com" value="<?= e($email) ?>" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock i-icon fa-xs"></i>
          <input type="password" name="password" id="pw" class="form-control"
            placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:.35rem;">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk
      </button>
    </form>

    <div class="divider"><span>Akun Demo</span></div>
    <div class="demo-grid">
      <button class="demo-btn" onclick="fillDemo('user@yummyspot.id')">
        <div class="d-icon"><i class="fa-solid fa-user" style="color:var(--blue)"></i></div>
        <div class="d-name">User</div>
        <div class="d-role">Pengguna umum</div>
      </button>
      <button class="demo-btn" onclick="fillDemo('owner@yummyspot.id')">
        <div class="d-icon"><i class="fa-solid fa-store" style="color:var(--amber)"></i></div>
        <div class="d-name">Pemilik</div>
        <div class="d-role">Owner katalog</div>
      </button>
      <button class="demo-btn" onclick="fillDemo('cs@yummyspot.com')">
        <div class="d-icon"><i class="fa-solid fa-shield-halved" style="color:var(--green)"></i></div>
        <div class="d-name">CS</div>
        <div class="d-role">Customer Service</div>
      </button>
      <button class="demo-btn" onclick="fillDemo('superadmin@yummyspot.com')">
        <div class="d-icon"><i class="fa-solid fa-bolt" style="color:var(--red)"></i></div>
        <div class="d-name">Admin</div>
        <div class="d-role">Super Admin</div>
      </button>
    </div>

    <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:1.1rem;">
      Belum punya akun?
      <a href="register.php" style="color:var(--accent);font-weight:700;">Daftar sekarang</a>
    </p>
  </div>
</div>

<script>
function fillDemo(email) {
  document.querySelector('[name="email"]').value = email;
  document.getElementById('pw').value = 'password123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
