<?php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
requireRole('owner');
$user = currentUser();
$pageTitle = 'Tambah Katalog — YummySpot';
$db=$db=getDB(); $errs=[]; $v=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf(); $v=$_POST;
    if (!trim($v['name']??''))    $errs['name']='Nama wajib diisi.';
    if (!trim($v['address']??'')) $errs['address']='Alamat wajib diisi.';
    if (!trim($v['city']??''))    $errs['city']='Kota wajib diisi.';
    if (!($v['category_id']??0))  $errs['category_id']='Pilih kategori.';
    if (!$errs) {
        $slug=slugify($v['name']); $chk=$db->prepare("SELECT id FROM catalogs WHERE slug=?"); $chk->execute([$slug]); if($chk->fetchColumn()) $slug.='-'.uniqid();
        $thumb=null; if(!empty($_FILES['thumbnail']['name'])){$thumb=uploadImage($_FILES['thumbnail'],'catalogs');}
        $db->prepare("INSERT INTO catalogs (owner_id,category_id,name,slug,description,address,city,contact,open_time,close_time,latitude,longitude,verification_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending')")->execute([$user['id'],(int)$v['category_id'],trim($v['name']),$slug,trim($v['description']??''),trim($v['address']),trim($v['city']),trim($v['contact']??''),$v['open_time']??null,$v['close_time']??null,$v['latitude']??null,$v['longitude']??null]);
        $cid=$db->lastInsertId();
        if($thumb) $db->prepare("UPDATE catalogs SET thumbnail=? WHERE id=?")->execute([$thumb,$cid]);
        flash('success','Katalog berhasil dibuat! Menunggu verifikasi CS.');
        redirect(APP_URL.'/owner/dashboard.php');
    }
}
$cats=$db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$pg='catalog-create';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-wrap">
<aside class="sidebar dash-sidebar">
  <div style="padding:.5rem .65rem .85rem;border-bottom:1px solid var(--border);margin-bottom:.5rem"><div style="font-size:.7rem;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.08em"><i class="fa-solid fa-store"></i> Panel Pemilik</div></div>
  <a href="dashboard.php"      class="sb-item"><i class="fa-solid fa-chart-pie si"></i> Dashboard</a>
  <a href="catalogs.php"       class="sb-item"><i class="fa-solid fa-building-store si"></i> Katalog Saya</a>
  <a href="catalog-create.php" class="sb-item active"><i class="fa-solid fa-plus si"></i> Tambah Katalog</a>
  <a href="reviews.php"        class="sb-item"><i class="fa-solid fa-star si"></i> Ulasan</a>
  <a href="analytics.php"      class="sb-item"><i class="fa-solid fa-chart-bar si"></i> Analitik</a>
  <div class="dd-sep"></div>
  <a href="<?= APP_URL ?>/index.php" class="sb-item text-dim"><i class="fa-solid fa-arrow-left si"></i> Kembali</a>
</aside>
<main class="main" style="max-width:680px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem">
    <h1 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900"><i class="fa-solid fa-plus" style="color:var(--accent)"></i> Tambah Katalog Baru</h1>
    <a href="dashboard.php" class="btn btn-ghost btn-sm text-dim"><i class="fa-solid fa-arrow-left fa-xs"></i> Kembali</a>
  </div>
  <?php if ($errs): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> Periksa kesalahan di bawah ini.</div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="card" style="margin-bottom:.85rem">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-circle-info" style="color:var(--accent)"></i> Informasi Dasar</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
          <div class="form-group" style="grid-column:1/-1">
            <label>Nama Tempat *</label>
            <input type="text" name="name" class="form-control" value="<?= e($v['name']??'') ?>" placeholder="Contoh: Kafe Senja Bali" required>
            <?php if(isset($errs['name'])): ?><div class="form-err"><?= $errs['name'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label>Kategori *</label>
            <select name="category_id" class="form-control">
              <option value="">Pilih kategori</option>
              <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($v['category_id']??'')==$c['id']?'selected':'' ?>><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
            <?php if(isset($errs['category_id'])): ?><div class="form-err"><?= $errs['category_id'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label>No. WhatsApp</label>
            <div class="input-wrap"><i class="fa-brands fa-whatsapp i-icon" style="color:var(--green)"></i><input type="text" name="contact" class="form-control" value="<?= e($v['contact']??'') ?>" placeholder="628123..."></div>
          </div>
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="description" class="form-control" rows="4" placeholder="Ceritakan tentang tempat ini..."><?= e($v['description']??'') ?></textarea>
        </div>
      </div>
    </div>
    <div class="card" style="margin-bottom:.85rem">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-location-dot" style="color:var(--red)"></i> Lokasi</span></div>
      <div class="card-body">
        <div class="form-group"><label>Alamat Lengkap *</label><textarea name="address" class="form-control" rows="2" placeholder="Jl. Sunset Road No. 45" required><?= e($v['address']??'') ?></textarea><?php if(isset($errs['address'])): ?><div class="form-err"><?= $errs['address'] ?></div><?php endif; ?></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
          <div class="form-group"><label>Kota *</label><input type="text" name="city" class="form-control" value="<?= e($v['city']??'') ?>" placeholder="Kuta" required><?php if(isset($errs['city'])): ?><div class="form-err"><?= $errs['city'] ?></div><?php endif; ?></div>
          <div class="form-group"><label>Kontak/Telepon</label><input type="text" name="contact" class="form-control" value="<?= e($v['contact']??'') ?>" placeholder="+62..."></div>
          <div class="form-group"><label>Jam Buka</label><input type="time" name="open_time" class="form-control" value="<?= e($v['open_time']??'08:00') ?>"></div>
          <div class="form-group"><label>Jam Tutup</label><input type="time" name="close_time" class="form-control" value="<?= e($v['close_time']??'22:00') ?>"></div>
        </div>
      </div>
    </div>
    <div class="card" style="margin-bottom:.85rem">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-camera" style="color:var(--accent)"></i> Foto Thumbnail</span></div>
      <div class="card-body">
        <div class="upload-zone" onclick="document.getElementById('tf').click()">
          <div class="uz-icon"><i class="fa-solid fa-image"></i></div>
          <div class="uz-text">Klik untuk upload foto thumbnail</div>
          <div style="font-size:.72rem;color:var(--text3);margin-top:.25rem;">
            <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent)"></i>
            Rekomendasi: <strong>4:3</strong> atau <strong>16:9</strong> · Min. <strong>800×600px</strong> · Maks. <strong>5MB</strong> · JPG/PNG/WebP
          </div>
          <img id="thumb-preview" style="max-height:160px;margin:.65rem auto 0;border-radius:var(--r-sm);display:none;border:1.5px solid var(--border)" alt="">
        </div>
        <input type="file" id="tf" name="thumbnail" accept="image/*" style="display:none" onchange="previewImg(this,'thumb-preview');document.getElementById('thumb-preview').style.display='block'">
      </div>
    </div>
    <div style="display:flex;gap:.5rem;justify-content:flex-end">
      <a href="dashboard.php" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan & Ajukan Verifikasi</button>
    </div>
  </form>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
