<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    $u = currentUser();
    if ($u['role'] === 'admin')     redirect(route('admin'));
    elseif ($u['role'] === 'cs')    redirect(route('cs'));
    elseif ($u['role'] === 'owner') redirect(route('owner'));
    else redirect(route('home'));
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
            if ($u['role'] === 'admin')     redirect(route('admin'));
            elseif ($u['role'] === 'cs')    redirect(route('cs'));
            elseif ($u['role'] === 'owner') redirect(route('owner'));
            else redirect($_GET['redirect'] ?? route('home'));
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



    <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:1.1rem;">
      Belum punya akun?
      <a href="register.php" style="color:var(--accent);font-weight:700;">Daftar sekarang</a>
    </p>
  </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
