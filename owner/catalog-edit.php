<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('owner');
$user = currentUser();
$pageTitle = 'Edit Katalog — YummySpot';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(route('owner.catalogs'));

// Pastikan katalog milik owner ini
$st = $db->prepare("SELECT * FROM catalogs WHERE id = ? AND owner_id = ?");
$st->execute([$id, $user['id']]);
$catalog = $st->fetch();
if (!$catalog) {
    flash('error', 'Katalog tidak ditemukan.');
    redirect(route('owner.catalogs'));
}

$cats = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$errs = [];
$v    = $catalog; // isi default dari data existing

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $v = $_POST;

    // Validasi
    if (!trim($v['name']    ?? '')) $errs['name']        = 'Nama wajib diisi.';
    if (!trim($v['address'] ?? '')) $errs['address']     = 'Alamat wajib diisi.';
    if (!trim($v['city']    ?? '')) $errs['city']        = 'Kota wajib diisi.';
    if (!($v['category_id'] ?? 0))  $errs['category_id'] = 'Pilih kategori.';

    if (!$errs) {
        // Update thumbnail jika ada upload baru
        $thumb = $catalog['thumbnail'];
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $newThumb = uploadImage($_FILES['thumbnail'], 'catalogs');
            if ($newThumb) $thumb = $newThumb;
        }

        // Hapus thumbnail jika diminta
        if (isset($_POST['remove_thumbnail'])) $thumb = null;

        $db->prepare("
            UPDATE catalogs SET
                category_id   = ?,
                name          = ?,
                description   = ?,
                address       = ?,
                city          = ?,
                contact       = ?,
                open_time     = ?,
                close_time    = ?,
                latitude      = ?,
                longitude     = ?,
                thumbnail     = ?
            WHERE id = ? AND owner_id = ?
        ")->execute([
            (int)$v['category_id'],
            trim($v['name']),
            trim($v['description'] ?? ''),
            trim($v['address']),
            trim($v['city']),
            trim($v['contact'] ?? ''),
            $v['open_time']  ?: null,
            $v['close_time'] ?: null,
            $v['latitude']   ?: null,
            $v['longitude']  ?: null,
            $thumb,
            $id,
            $user['id'],
        ]);

        flash('success', 'Katalog berhasil diperbarui!');
        redirect(route('owner.catalogs'));
    }
}

$pg = 'catalogs';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="app-wrap">
<!-- Owner Sidebar -->
<aside class="sidebar dash-sidebar">
    <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem;">
        <div style="font-size:.7rem;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.08em;">
            <i class="fa-solid fa-store"></i> Panel Pemilik
        </div>
        <div style="font-size:.75rem;color:var(--text3);margin-top:.15rem;"><?= e($user['fullname']) ?></div>
    </div>
    <a href="dashboard.php"      class="sb-item"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
    <a href="catalogs.php"       class="sb-item active"><i class="fa-solid fa-store si"></i> Katalog Saya</a>
    <a href="catalog-create.php" class="sb-item"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
    <a href="reviews.php"        class="sb-item"><i class="fa-solid fa-star si"></i> Ulasan</a>
    <a href="analytics.php"      class="sb-item"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
    <div class="dd-sep"></div>
    <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali ke Feed</a>
</aside>

<main class="main" style="max-width:680px;">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;">
        <div>
            <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;">
                <i class="fa-solid fa-pen" style="color:var(--accent)"></i> Edit Katalog
            </h1>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.1rem;"><?= e($catalog['name']) ?></div>
        </div>
        <a href="catalogs.php" class="btn btn-ghost btn-sm text-dim">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Kembali
        </a>
    </div>

    <!-- Status badge -->
    <?php
    $statusMap = [
        'approved' => ['badge-success', 'fa-check-circle',  'Aktif & Terverifikasi'],
        'pending'  => ['badge-warning', 'fa-clock',          'Menunggu Verifikasi'],
        'rejected' => ['badge-danger',  'fa-times-circle',  'Ditolak'],
        'draft'    => ['badge-default', 'fa-file',           'Draft'],
    ];
    [$bc, $si, $sl] = $statusMap[$catalog['verification_status']] ?? ['badge-default','fa-circle','Unknown'];
    ?>
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding:.65rem .9rem;background:#fff;border:1px solid var(--border);border-radius:var(--r);">
        <span class="badge <?= $bc ?>"><i class="fa-solid <?= $si ?> fa-xs"></i> <?= $sl ?></span>
        <?php if ($catalog['verification_status'] === 'pending'): ?>
        <span style="font-size:.78rem;color:var(--text3);">Perubahan akan tersimpan. Status verifikasi tidak berubah.</span>
        <?php elseif ($catalog['verification_status'] === 'rejected'): ?>
        <span style="font-size:.78rem;color:var(--text3);">Perbaiki data lalu ajukan ulang verifikasi di halaman <a href="catalogs.php" style="color:var(--accent);">Katalog Saya</a>.</span>
        <?php endif; ?>
    </div>

    <?php if ($errs): ?>
    <div class="alert alert-error" data-dismiss>
        <i class="fa-solid fa-triangle-exclamation"></i> Periksa kesalahan di bawah ini.
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <!-- Informasi Dasar -->
        <div class="card" style="margin-bottom:.85rem;">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-circle-info" style="color:var(--accent)"></i> Informasi Dasar</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Nama Tempat <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="form-control"
                            value="<?= e($v['name'] ?? '') ?>" placeholder="Nama katalog tempat" required>
                        <?php if (isset($errs['name'])): ?><div class="form-err"><?= $errs['name'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Kategori <span style="color:var(--red)">*</span></label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($v['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errs['category_id'])): ?><div class="form-err"><?= $errs['category_id'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp / Kontak</label>
                        <div class="input-wrap">
                            <i class="fa-brands fa-whatsapp i-icon" style="color:var(--green)"></i>
                            <input type="text" name="contact" class="form-control"
                                value="<?= e($v['contact'] ?? '') ?>" placeholder="628123...">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4"
                        placeholder="Ceritakan tentang tempat ini..."><?= e($v['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Lokasi -->
        <div class="card" style="margin-bottom:.85rem;">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-location-dot" style="color:var(--red)"></i> Lokasi</span>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Alamat Lengkap <span style="color:var(--red)">*</span></label>
                    <textarea name="address" class="form-control" rows="2"
                        placeholder="Jl. Nama Jalan No. XX" required><?= e($v['address'] ?? '') ?></textarea>
                    <?php if (isset($errs['address'])): ?><div class="form-err"><?= $errs['address'] ?></div><?php endif; ?>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
                    <div class="form-group">
                        <label>Kota <span style="color:var(--red)">*</span></label>
                        <input type="text" name="city" class="form-control"
                            value="<?= e($v['city'] ?? '') ?>" placeholder="Nama kota" required>
                        <?php if (isset($errs['city'])): ?><div class="form-err"><?= $errs['city'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group"><!-- spacer --></div>
                    <div class="form-group">
                        <label>Jam Buka</label>
                        <input type="time" name="open_time" class="form-control"
                            value="<?= e($v['open_time'] ?? '08:00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Jam Tutup</label>
                        <input type="time" name="close_time" class="form-control"
                            value="<?= e($v['close_time'] ?? '22:00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Latitude <span style="color:var(--text3);font-size:.72rem;">(opsional)</span></label>
                        <input type="number" step="any" name="latitude" class="form-control"
                            value="<?= e($v['latitude'] ?? '') ?>" placeholder="-8.4095">
                    </div>
                    <div class="form-group">
                        <label>Longitude <span style="color:var(--text3);font-size:.72rem;">(opsional)</span></label>
                        <input type="number" step="any" name="longitude" class="form-control"
                            value="<?= e($v['longitude'] ?? '') ?>" placeholder="115.1889">
                    </div>
                </div>
            </div>
        </div>

        <!-- Foto Thumbnail -->
        <div class="card" style="margin-bottom:.85rem;">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-camera" style="color:var(--accent)"></i> Foto Thumbnail</span>
            </div>
            <div class="card-body">
                <?php if ($catalog['thumbnail']): ?>
                <!-- Foto existing -->
                <div style="position:relative;margin-bottom:.85rem;" id="existing-thumb">
                    <img src="<?= e($catalog['thumbnail']) ?>" alt=""
                        style="width:100%;max-height:200px;object-fit:cover;border-radius:var(--r);border:1.5px solid var(--border);">
                    <div style="position:absolute;top:.5rem;right:.5rem;display:flex;gap:.35rem;">
                        <button type="button" class="btn btn-sm"
                            style="background:rgba(0,0,0,.6);color:#fff;border:none;"
                            onclick="document.getElementById('tf').click()">
                            <i class="fa-solid fa-camera fa-xs"></i> Ganti
                        </button>
                        <button type="button" class="btn btn-sm"
                            style="background:rgba(239,68,68,.85);color:#fff;border:none;"
                            onclick="confirmRemoveThumb()">
                            <i class="fa-solid fa-trash fa-xs"></i> Hapus
                        </button>
                    </div>
                </div>
                <input type="hidden" name="remove_thumbnail" id="remove-thumb-input" value="">
                <?php else: ?>
                <!-- Upload baru -->
                <div class="upload-zone" id="upload-zone-edit" onclick="document.getElementById('tf').click()">
                    <div class="uz-icon"><i class="fa-solid fa-image"></i></div>
                    <div class="uz-text">Klik untuk upload foto thumbnail (maks. 5MB)</div>
                </div>
                <?php endif; ?>

                <!-- Preview foto baru -->
                <div id="new-thumb-preview" style="display:none;margin-top:.5rem;">
                    <img id="new-thumb-img" src="" alt=""
                        style="width:100%;max-height:200px;object-fit:cover;border-radius:var(--r);border:1.5px solid var(--accent);">
                    <div style="font-size:.75rem;color:var(--accent);margin-top:.3rem;">
                        <i class="fa-solid fa-check-circle fa-xs"></i> Foto baru siap diupload
                    </div>
                </div>

                <div style="font-size:.72rem;color:var(--text3);margin-top:.4rem;">
                    <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent)"></i>
                    Rekomendasi: <strong>4:3</strong> atau <strong>16:9</strong> · Min. <strong>800×600px</strong> · Maks. <strong>5MB</strong> · JPG/PNG/WebP
                </div>
                <input type="file" id="tf" name="thumbnail" accept="image/*" style="display:none"
                    onchange="previewNewThumb(this)">
            </div>
        </div>

        <!-- Tombol aksi -->
        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <a href="catalogs.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk fa-xs"></i> Simpan Perubahan
            </button>
        </div>
    </form>

</main>
</div>

<script>
function previewNewThumb(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        toast('Ukuran file maks. 5MB', 'err');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('new-thumb-img').src = e.target.result;
        document.getElementById('new-thumb-preview').style.display = 'block';
        // Sembunyikan upload zone jika ada
        const zone = document.getElementById('upload-zone-edit');
        if (zone) zone.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function confirmRemoveThumb() {
    if (!confirm('Hapus foto thumbnail ini?')) return;
    document.getElementById('remove-thumb-input').value = '1';
    document.getElementById('existing-thumb').style.display = 'none';
    // Tampilkan upload zone
    const zone = document.createElement('div');
    zone.className = 'upload-zone';
    zone.style.marginTop = '.5rem';
    zone.onclick = () => document.getElementById('tf').click();
    zone.innerHTML = '<div class="uz-icon"><i class="fa-solid fa-image"></i></div><div class="uz-text">Klik untuk upload foto thumbnail baru</div>';
    document.getElementById('existing-thumb').after(zone);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
