<?php
$pageTitle = 'Daftar — YummySpot';
require_once __DIR__ . '/includes/header.php';
if ($user) redirect(APP_URL.'/index.php');
$errs = []; $v = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $v = ['fullname'=>trim($_POST['fullname']??''),'username'=>trim($_POST['username']??''),'email'=>trim($_POST['email']??''),'password'=>$_POST['password']??'','password2'=>$_POST['password2']??'','role'=>in_array($_POST['role']??'',['user','owner'])?$_POST['role']:'user'];
    if (!$v['fullname'])                             $errs['fullname']  = 'Nama wajib diisi.';
    if (strlen($v['username'])<3)                    $errs['username']  = 'Username min. 3 karakter.';
    if (!filter_var($v['email'],FILTER_VALIDATE_EMAIL)) $errs['email'] = 'Email tidak valid.';
    if (strlen($v['password'])<6)                    $errs['password']  = 'Password min. 6 karakter.';
    if ($v['password']!==$v['password2'])            $errs['password2'] = 'Konfirmasi tidak cocok.';
    if (!$errs) {
        $db = getDB();
        $ck = $db->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $ck->execute([$v['email'],$v['username']]);
        if ($ck->fetch()) { $errs['email']='Email atau username sudah terdaftar.'; }
        else {
            $hash = password_hash($v['password'], PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (fullname,username,email,password,role) VALUES (?,?,?,?,?)")->execute([$v['fullname'],$v['username'],$v['email'],$hash,$v['role']]);
            $id = $db->lastInsertId();
            $nu = $db->prepare("SELECT * FROM users WHERE id=?"); $nu->execute([$id]);
            loginUser($nu->fetch());
            flash('success','Selamat datang di YummySpot!');
            redirect($v['role']==='owner'?APP_URL.'/owner/dashboard.php':APP_URL.'/index.php');
        }
    }
}
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
    <form method="POST">
      <?= csrfField() ?>
      <!-- Role selector -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.85rem">
        <label style="cursor:pointer">
          <input type="radio" name="role" value="user" style="display:none" <?= ($v['role']??'user')==='user'?'checked':'' ?>>
          <div class="demo-btn role-opt" data-role="user">
            <div class="d-icon"><i class="fa-solid fa-user" style="color:var(--blue)"></i></div>
            <div class="d-name">User</div>
            <div class="d-role">Jelajahi & review</div>
          </div>
        </label>
        <label style="cursor:pointer">
          <input type="radio" name="role" value="owner" style="display:none" <?= ($v['role']??'')==='owner'?'checked':'' ?>>
          <div class="demo-btn role-opt" data-role="owner">
            <div class="d-icon"><i class="fa-solid fa-store" style="color:var(--amber)"></i></div>
            <div class="d-name">Pemilik</div>
            <div class="d-role">Daftarkan tempat</div>
          </div>
        </label>
      </div>
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="fullname" class="form-control" value="<?= e($v['fullname']??'') ?>" placeholder="Nama kamu" required>
        <?php if (isset($errs['fullname'])): ?><div class="form-err"><?= $errs['fullname'] ?></div><?php endif; ?>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <i class="fa-solid fa-at i-icon fa-xs"></i>
            <input type="text" name="username" class="form-control" value="<?= e($v['username']??'') ?>" placeholder="username" required>
          </div>
          <?php if (isset($errs['username'])): ?><div class="form-err"><?= $errs['username'] ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= e($v['email']??'') ?>" placeholder="email@kamu.com" required>
          <?php if (isset($errs['email'])): ?><div class="form-err"><?= $errs['email'] ?></div><?php endif; ?>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
          <?php if (isset($errs['password'])): ?><div class="form-err"><?= $errs['password'] ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>Konfirmasi</label>
          <input type="password" name="password2" class="form-control" placeholder="Ulangi password" required>
          <?php if (isset($errs['password2'])): ?><div class="form-err"><?= $errs['password2'] ?></div><?php endif; ?>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg"><i class="fa-solid fa-user-plus"></i> Buat Akun</button>
    </form>
    <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:1.1rem">Sudah punya akun? <a href="login.php" style="color:var(--accent);font-weight:700">Masuk di sini</a></p>
  </div>
</div>
<script>
document.querySelectorAll('.role-opt').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.role-opt').forEach(o => o.style.borderColor='var(--border)');
    opt.style.borderColor = 'var(--accent)';
    opt.closest('label').querySelector('input').checked = true;
  });
});
// set initial
document.querySelectorAll('.role-opt').forEach(opt => {
  const inp = opt.closest('label').querySelector('input');
  if (inp.checked) opt.style.borderColor = 'var(--accent)';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
