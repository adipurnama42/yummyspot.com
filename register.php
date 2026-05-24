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
$errs = [];
$v    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $v = [
        'fullname'  => trim($_POST['fullname']  ?? ''),
        'username'  => trim($_POST['username']  ?? ''),
        'email'     => trim($_POST['email']     ?? ''),
        'password'  =>      $_POST['password']  ?? '',
        'password2' =>      $_POST['password2'] ?? '',
        'role'      => in_array($_POST['role'] ?? '', ['user','owner']) ? $_POST['role'] : 'user',
    ];

    if (!$v['fullname'])                                 $errs['fullname']  = 'Nama wajib diisi.';
    if (strlen($v['username']) < 3)                      $errs['username']  = 'Username min. 3 karakter.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errs['email']     = 'Format email tidak valid.';
    if (strlen($v['password']) < 6)                      $errs['password']  = 'Password min. 6 karakter.';
    if ($v['password'] !== $v['password2'])              $errs['password2'] = 'Konfirmasi tidak cocok.';

    if (!$errs) {
        $db = getDB();
        $ck = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $ck->execute([$v['email'], $v['username']]);
        if ($ck->fetch()) {
            $errs['email'] = 'Email atau username sudah terdaftar.';
        } else {
            $hash = password_hash($v['password'], PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (?,?,?,?,?)")
               ->execute([$v['fullname'], $v['username'], $v['email'], $hash, $v['role']]);
            $newId = (int)$db->lastInsertId();
            $nuSt  = $db->prepare("SELECT * FROM users WHERE id = ?");
            $nuSt->execute([$newId]);
            loginUser($nuSt->fetch());
            flash('success', 'Selamat datang di YummySpot, ' . $v['fullname'] . '!');
            if ($v['role'] === 'owner') redirect(APP_URL . '/owner/dashboard.php');
            else redirect(APP_URL . '/index.php');
        }
    }
}

// ── Output HTML ──────────────────────────────────────────
$pageTitle = 'Daftar — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-main">Yummy<span>Spot</span></div>
      <div class="logo-sub">Bergabung dan temukan tempat terbaik</div>
    </div>
    <div class="auth-tabs">
      <a href="login.php"    class="auth-tab">Masuk</a>
      <a href="register.php" class="auth-tab active">Daftar</a>
    </div>

    <?php if (!empty($errs)): ?>
    <div class="alert alert-error" data-dismiss>
      <i class="fa-solid fa-circle-exclamation"></i>
      <?= e(array_values($errs)[0]) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfField() ?>

      <!-- Role selector -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.85rem;">
        <label style="cursor:pointer;">
          <input type="radio" name="role" value="user" style="display:none;"
            <?= ($v['role'] ?? 'user') === 'user' ? 'checked' : '' ?>>
          <div class="demo-btn role-opt" id="role-user"
            style="border-color:<?= ($v['role'] ?? 'user') === 'user' ? 'var(--accent)' : 'var(--border)' ?>;">
            <div class="d-icon"><i class="fa-solid fa-user" style="color:var(--blue)"></i></div>
            <div class="d-name">User</div>
            <div class="d-role">Jelajahi &amp; review</div>
          </div>
        </label>
        <label style="cursor:pointer;">
          <input type="radio" name="role" value="owner" style="display:none;"
            <?= ($v['role'] ?? '') === 'owner' ? 'checked' : '' ?>>
          <div class="demo-btn role-opt" id="role-owner"
            style="border-color:<?= ($v['role'] ?? '') === 'owner' ? 'var(--accent)' : 'var(--border)' ?>;">
            <div class="d-icon"><i class="fa-solid fa-store" style="color:var(--amber)"></i></div>
            <div class="d-name">Pemilik</div>
            <div class="d-role">Daftarkan tempat</div>
          </div>
        </label>
      </div>

      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="fullname" class="form-control"
          value="<?= e($v['fullname'] ?? '') ?>" placeholder="Nama kamu" required autofocus>
        <?php if (isset($errs['fullname'])): ?>
          <div class="form-err"><?= e($errs['fullname']) ?></div>
        <?php endif; ?>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <i class="fa-solid fa-at i-icon fa-xs"></i>
            <input type="text" name="username" class="form-control"
              value="<?= e($v['username'] ?? '') ?>" placeholder="username" required>
          </div>
          <?php if (isset($errs['username'])): ?>
            <div class="form-err"><?= e($errs['username']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control"
            value="<?= e($v['email'] ?? '') ?>" placeholder="email@kamu.com" required>
          <?php if (isset($errs['email'])): ?>
            <div class="form-err"><?= e($errs['email']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control"
            placeholder="Min. 6 karakter" required>
          <?php if (isset($errs['password'])): ?>
            <div class="form-err"><?= e($errs['password']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Konfirmasi</label>
          <input type="password" name="password2" class="form-control"
            placeholder="Ulangi password" required>
          <?php if (isset($errs['password2'])): ?>
            <div class="form-err"><?= e($errs['password2']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
        <i class="fa-solid fa-user-plus"></i> Buat Akun
      </button>
    </form>

    <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:1.1rem;">
      Sudah punya akun?
      <a href="login.php" style="color:var(--accent);font-weight:700;">Masuk di sini</a>
    </p>
  </div>
</div>

<script>
document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-opt').forEach(o => {
            o.style.borderColor = 'var(--border)';
        });
        this.closest('label').querySelector('.role-opt').style.borderColor = 'var(--accent)';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
