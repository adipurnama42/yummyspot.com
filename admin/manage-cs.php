<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('admin');
$user = currentUser();
$pageTitle = 'Kelola Tim CS — YummySpot';
$db  = getDB();
$msg = '';

// ── Handle actions ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['action'] ?? '';

    // Tambah CS baru
    if ($act === 'add_cs') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errs = [];
        if (!$fullname)                             $errs[] = 'Nama wajib diisi.';
        if (strlen($username) < 3)                  $errs[] = 'Username min. 3 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'Format email tidak valid.';
        if (strlen($password) < 6)                  $errs[] = 'Password min. 6 karakter.';

        if (!$errs) {
            // Cek duplikat
            $chk = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $chk->execute([$email, $username]);
            if ($chk->fetch()) {
                $errs[] = 'Email atau username sudah terdaftar.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("INSERT INTO users (fullname, username, email, password, role, status) VALUES (?,?,?,?,'cs','active')")
                   ->execute([$fullname, $username, $email, $hash]);
                flash('success', 'Akun CS berhasil ditambahkan!');
                redirect(APP_URL . '/admin/manage-cs.php');
            }
        }
        $msg = implode(' ', $errs);
    }

    // Ubah status CS (aktif/suspend)
    elseif ($act === 'toggle_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['active', 'suspended'])) {
            $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'cs'")
               ->execute([$status, $id]);
            flash('success', $status === 'active' ? 'Akun CS diaktifkan.' : 'Akun CS disuspend.');
            redirect(APP_URL . '/admin/manage-cs.php');
        }
    }

    // Reset password CS
    elseif ($act === 'reset_password') {
        $id      = (int)($_POST['id'] ?? 0);
        $newpass = trim($_POST['new_password'] ?? '');
        if ($id && strlen($newpass) >= 6) {
            $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'cs'")
               ->execute([$hash, $id]);
            flash('success', 'Password CS berhasil direset.');
            redirect(APP_URL . '/admin/manage-cs.php');
        } else {
            $msg = 'Password minimal 6 karakter.';
        }
    }

    // Hapus CS
    elseif ($act === 'delete_cs') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE users SET role = 'user', status = 'suspended' WHERE id = ? AND role = 'cs'")
               ->execute([$id]);
            flash('success', 'Akun CS dihapus.');
            redirect(APP_URL . '/admin/manage-cs.php');
        }
    }
}

// Ambil semua CS
$csUsers = $db->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM catalog_verifications WHERE cs_id = u.id) AS total_verified
    FROM users u
    WHERE u.role = 'cs'
    ORDER BY u.created_at DESC
")->fetchAll();

// Stats verifikasi per CS
$pals = [['#FF6B35','#fff5f0'],['#8b5cf6','#f5f3ff'],['#22c55e','#f0fdf4'],['#3b82f6','#eff6ff'],['#f59e0b','#fffbeb'],['#ec4899','#fdf2f8']];
$pg   = 'manage-cs';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="app-wrap">
<!-- Admin Sidebar -->
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--red);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-bolt"></i> Super Admin
        </div>
    </div>
    <a href="dashboard.php"              class="sb-item"><i class="fa-solid fa-gauge si"></i> Overview</a>
    <a href="dashboard.php?tab=users"    class="sb-item"><i class="fa-solid fa-users si"></i> Pengguna</a>
    <a href="dashboard.php?tab=catalogs" class="sb-item"><i class="fa-solid fa-building-store si"></i> Katalog</a>
    <a href="dashboard.php?tab=reports"  class="sb-item"><i class="fa-solid fa-flag si"></i> Laporan</a>
    <a href="manage-cs.php"              class="sb-item active"><i class="fa-solid fa-shield-halved si"></i> Kelola Tim CS</a>
    <a href="<?= APP_URL ?>/cs/dashboard.php" class="sb-item"><i class="fa-solid fa-eye si"></i> Lihat CS Panel</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali</a>
</aside>

<main class="main">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-shield-halved" style="color:var(--green)"></i> Kelola Tim CS
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;">
                <?= count($csUsers) ?> anggota CS aktif
            </div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('add-cs-modal')">
            <i class="fa-solid fa-user-plus fa-xs"></i> Tambah CS Baru
        </button>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-error" data-dismiss>
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- CS List -->
    <?php if (empty($csUsers)): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:4rem 2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--border2);margin-bottom:1rem;"><i class="fa-solid fa-shield-halved"></i></div>
        <h3 style="font-family:'Nunito',sans-serif;font-weight:800;margin-bottom:.4rem;color:var(--text2);">Belum ada tim CS</h3>
        <p style="font-size:.85rem;color:var(--text3);margin-bottom:1.25rem;">Tambahkan anggota CS untuk membantu verifikasi katalog.</p>
        <button class="btn btn-primary" onclick="openModal('add-cs-modal')">
            <i class="fa-solid fa-user-plus fa-xs"></i> Tambah CS Pertama
        </button>
    </div>
    <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($csUsers as $cs):
            $cp = $pals[($cs['id'] - 1) % count($pals)];
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1rem;transition:box-shadow .18s;"
             onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
            <div style="display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;">

                <!-- Avatar -->
                <div class="avatar av-44" style="background:<?= $cp[1] ?>;color:<?= $cp[0] ?>;flex-shrink:0;">
                    <?php if ($cs['profile_picture']): ?>
                        <img src="<?= e($cs['profile_picture']) ?>" alt="">
                    <?php else: ?>
                        <?= initials($cs['fullname']) ?>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:200px;">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem;">
                        <span style="font-weight:800;font-size:.95rem;"><?= e($cs['fullname']) ?></span>
                        <span class="badge <?= $cs['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fa-solid fa-circle fa-xs" style="font-size:.45rem;"></i>
                            <?= $cs['status'] === 'active' ? 'Aktif' : 'Suspended' ?>
                        </span>
                    </div>
                    <div style="font-size:.78rem;color:var(--text3);display:flex;gap:.75rem;flex-wrap:wrap;">
                        <span><i class="fa-solid fa-at fa-xs"></i> <?= e($cs['username']) ?></span>
                        <span><i class="fa-regular fa-envelope fa-xs"></i> <?= e($cs['email']) ?></span>
                        <span><i class="fa-regular fa-calendar fa-xs"></i> Bergabung <?= date('d M Y', strtotime($cs['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Stats -->
                <div style="text-align:center;padding:.5rem 1rem;background:var(--bg);border-radius:var(--r);min-width:80px;">
                    <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;color:var(--green);"><?= $cs['total_verified'] ?></div>
                    <div style="font-size:.65rem;color:var(--text3);font-weight:600;">Diverifikasi</div>
                </div>

                <!-- Actions -->
                <div style="display:flex;gap:.4rem;flex-shrink:0;">
                    <!-- Reset password -->
                    <button class="btn btn-outline btn-sm" onclick="showResetPw(<?= $cs['id'] ?>)" title="Reset Password">
                        <i class="fa-solid fa-key fa-xs"></i>
                    </button>

                    <!-- Toggle status -->
                    <?php if ($cs['status'] === 'active'): ?>
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= $cs['id'] ?>">
                        <input type="hidden" name="status" value="suspended">
                        <button type="submit" class="btn btn-danger btn-sm" title="Suspend"
                            onclick="return confirm('Suspend akun CS ini?')">
                            <i class="fa-solid fa-ban fa-xs"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= $cs['id'] ?>">
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                            <i class="fa-solid fa-check fa-xs"></i>
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Hapus -->
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_cs">
                        <input type="hidden" name="id" value="<?= $cs['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);" title="Hapus"
                            onclick="return confirm('Hapus akun CS <?= addslashes($cs['fullname']) ?>?')">
                            <i class="fa-solid fa-trash fa-xs"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reset password form (hidden) -->
            <div id="reset-pw-<?= $cs['id'] ?>" style="display:none;margin-top:.85rem;padding-top:.85rem;border-top:1px solid var(--border);">
                <form method="POST" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" value="<?= $cs['id'] ?>">
                    <div class="form-group" style="flex:1;margin:0;min-width:200px;">
                        <label style="font-size:.72rem;">Password Baru</label>
                        <input type="text" name="new_password" class="form-control"
                            placeholder="Min. 6 karakter" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk fa-xs"></i> Simpan
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="document.getElementById('reset-pw-<?= $cs['id'] ?>').style.display='none'">
                        Batal
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <!-- Info hak akses CS -->
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--r-lg);padding:1rem 1.25rem;margin-top:1.25rem;">
        <div style="font-weight:700;font-size:.875rem;color:#16a34a;margin-bottom:.5rem;">
            <i class="fa-solid fa-shield-halved fa-xs"></i> Hak Akses Tim CS
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.35rem;font-size:.8rem;color:#166534;">
            <div><i class="fa-solid fa-check fa-xs"></i> Melihat semua katalog (semua status)</div>
            <div><i class="fa-solid fa-check fa-xs"></i> Menyetujui / menolak verifikasi katalog</div>
            <div><i class="fa-solid fa-check fa-xs"></i> Menangani laporan pengguna</div>
            <div><i class="fa-solid fa-check fa-xs"></i> Melihat detail profil pengguna</div>
            <div><i class="fa-solid fa-xmark fa-xs" style="color:#dc2626;"></i> Tidak bisa kelola user/role</div>
            <div><i class="fa-solid fa-xmark fa-xs" style="color:#dc2626;"></i> Tidak bisa akses panel Admin</div>
        </div>
    </div>

</main>
</div>

<!-- Modal: Tambah CS Baru -->
<div class="modal-ov" id="add-cs-modal">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title">
                <i class="fa-solid fa-user-plus" style="color:var(--green)"></i> Tambah CS Baru
            </span>
            <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_cs">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Nama CS" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-at i-icon fa-xs"></i>
                            <input type="text" name="username" class="form-control" placeholder="username_cs" required minlength="3">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="cs@yummyspot.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Min. 6 karakter" required minlength="6">
                    <div class="form-hint">Berikan password ini ke anggota CS yang bersangkutan.</div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('add-cs-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus fa-xs"></i> Tambah CS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetPw(id) {
    document.querySelectorAll('[id^="reset-pw-"]').forEach(el => el.style.display = 'none');
    const el = document.getElementById('reset-pw-' + id);
    if (el) {
        el.style.display = 'block';
        el.querySelector('input[name="new_password"]').focus();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
