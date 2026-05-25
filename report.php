<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
requireLogin();
$user = currentUser();

$db         = getDB();
$targetType = $_GET['type'] ?? '';
$targetId   = (int)($_GET['id'] ?? 0);

// Validasi target
if (!in_array($targetType, ['post','catalog']) || !$targetId) {
    flash('error', 'Target laporan tidak valid.');
    redirect(APP_URL . '/index.php');
}

// Ambil nama target
if ($targetType === 'post') {
    $st = $db->prepare("SELECT p.id, u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.status='published'");
    $st->execute([$targetId]);
    $target = $st->fetch();
    if (!$target) { flash('error','Postingan tidak ditemukan.'); redirect(APP_URL . '/index.php'); }
    $targetName = 'Postingan oleh ' . $target['fullname'];
} else {
    $st = $db->prepare("SELECT id, name, slug FROM catalogs WHERE id=? AND verification_status='approved'");
    $st->execute([$targetId]);
    $target = $st->fetch();
    if (!$target) { flash('error','Katalog tidak ditemukan.'); redirect(APP_URL . '/catalog.php'); }
    $targetName = 'Katalog: ' . $target['name'];
}

// Back URL
$_referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($_referer && strpos($_referer, 'report.php') === false) {
    $backUrl = $_referer;
} elseif ($targetType === 'post') {
    $backUrl = route('post', $targetId;)
} else {
    $backUrl = APP_URL . '/catalog-detail.php?slug=' . $target['slug'] ?? '');
}

// Cek sudah pernah lapor
$exists = $db->prepare("SELECT id FROM reports WHERE reporter_id=? AND " .
    ($targetType === 'post' ? "reported_post_id=?" : "reported_catalog_id=?") . " AND status != 'done'");
$exists->execute([$user['id'], $targetId]);
$alreadyReported = $exists->fetch();

// ── Handle POST ──────────────────────────────────────────
$errs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $reason = $_POST['report_type'] ?? '';
    $desc   = trim($_POST['description'] ?? '');
    $validReasons = ['spam','fake','inappropriate','bug'];

    if (!in_array($reason, $validReasons)) $errs['reason'] = 'Pilih alasan laporan.';
    if (!$desc)                             $errs['desc']   = 'Deskripsi wajib diisi.';

    if (!$errs) {
        $db->prepare("INSERT INTO reports (reporter_id, reported_post_id, reported_catalog_id, report_type, description, status) VALUES (?,?,?,?,?,'pending')")
           ->execute([
               $user['id'],
               $targetType === 'post'    ? $targetId : null,
               $targetType === 'catalog' ? $targetId : null,
               $reason,
               $desc,
           ]);
        flash('success', 'Laporan berhasil dikirim. Tim CS akan meninjau dalam 1×24 jam.');
        redirect($backUrl);
    }
}

// ── Output HTML ──────────────────────────────────────────
$pageTitle = 'Kirim Laporan — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:560px;margin:0 auto;">

    <div style="margin-bottom:.85rem;">
        <a href="<?= e($backUrl) ?>" class="btn btn-ghost btn-sm text-dim">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fa-solid fa-flag" style="color:var(--red)"></i> Kirim Laporan
            </span>
        </div>
        <div class="card-body">

            <!-- Target -->
            <div style="background:var(--bg);border-radius:var(--r-sm);padding:.75rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.6rem;">
                <i class="fa-solid <?= $targetType==='post'?'fa-image':'fa-store' ?> fa-xs" style="color:var(--accent);flex-shrink:0;"></i>
                <div>
                    <div style="font-size:.72rem;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Dilaporkan</div>
                    <div style="font-size:.85rem;font-weight:700;"><?= e($targetName) ?></div>
                </div>
            </div>

            <?php if ($alreadyReported): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-clock fa-xs"></i>
                Kamu sudah melaporkan ini sebelumnya dan sedang dalam proses peninjauan.
            </div>
            <?php else: ?>

            <form method="POST">
                <?= csrfField() ?>

                <!-- Alasan -->
                <div class="form-group">
                    <label>Alasan Laporan <span style="color:var(--red)">*</span></label>
                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                        <?php
                        $reasons = [
                            'spam'          => ['fa-envelope-circle-check', 'Spam',                'Konten berulang atau promosi berlebihan'],
                            'fake'          => ['fa-times-circle',          'Informasi Palsu',     'Informasi tidak akurat atau menyesatkan'],
                            'inappropriate' => ['fa-triangle-exclamation',  'Konten Tidak Pantas', 'Melanggar komunitas atau mengandung SARA'],
                            'bug'           => ['fa-bug',                   'Bug / Masalah Teknis','Ada kesalahan atau masalah pada konten'],
                        ];
                        foreach ($reasons as $val => [$icon, $label, $sub]):
                            $checked = ($_POST['report_type'] ?? '') === $val;
                        ?>
                        <label style="display:flex;align-items:center;gap:.75rem;padding:.65rem .85rem;border:1.5px solid <?= $checked?'var(--accent)':'var(--border)' ?>;border-radius:var(--r-sm);cursor:pointer;background:<?= $checked?'var(--accent-bg)':'#fff' ?>;transition:all .15s;">
                            <input type="radio" name="report_type" value="<?= $val ?>" style="display:none;" <?= $checked?'checked':'' ?>>
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $checked?'var(--accent)':'var(--bg)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;">
                                <i class="fa-solid <?= $icon ?> fa-xs" style="color:<?= $checked?'#fff':'var(--text3)' ?>;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.85rem;"><?= $label ?></div>
                                <div style="font-size:.72rem;color:var(--text3);"><?= $sub ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errs['reason'])): ?><div class="form-err"><?= $errs['reason'] ?></div><?php endif; ?>
                </div>

                <!-- Deskripsi -->
                <div class="form-group">
                    <label>Deskripsi <span style="color:var(--red)">*</span></label>
                    <textarea name="description" class="form-control" rows="4" required
                        placeholder="Jelaskan lebih detail mengapa kamu melaporkan ini..."><?= e($_POST['description'] ?? '') ?></textarea>
                    <div class="form-hint">Semakin detail laporan, semakin cepat tim CS menindaklanjuti.</div>
                    <?php if (isset($errs['desc'])): ?><div class="form-err"><?= $errs['desc'] ?></div><?php endif; ?>
                </div>

                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--r-sm);padding:.65rem .85rem;font-size:.78rem;color:#92400e;margin-bottom:1rem;">
                    <i class="fa-solid fa-circle-info fa-xs"></i>
                    Laporan bersifat anonim. Tim CS akan meninjau dalam <strong>1×24 jam</strong>.
                </div>

                <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                    <a href="<?= e($backUrl) ?>" class="btn btn-outline btn-sm">Batal</a>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-flag fa-xs"></i> Kirim Laporan
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

</main>
</div>

<script>
document.querySelectorAll('input[name="report_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('label:has(input[name="report_type"])').forEach(lbl => {
            const inp    = lbl.querySelector('input');
            const circle = lbl.querySelector('div:first-of-type');
            const ico    = circle?.querySelector('i');
            lbl.style.borderColor = inp.checked ? 'var(--accent)' : 'var(--border)';
            lbl.style.background  = inp.checked ? 'var(--accent-bg)' : '#fff';
            if (circle) circle.style.background = inp.checked ? 'var(--accent)' : 'var(--bg)';
            if (ico)    ico.style.color = inp.checked ? '#fff' : 'var(--text3)';
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
