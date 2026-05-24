
<?php if ($user && !$isDash): ?>
<!-- CREATE POST MODAL -->
<div class="modal-ov" id="create-post-modal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-head">
      <span class="modal-title">
        <i class="fa-solid fa-camera" style="color:var(--accent)"></i> Buat Postingan
      </span>
      <button class="modal-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form id="post-form" action="<?= APP_URL ?>/actions/post_create.php" method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <!-- Upload Foto WAJIB -->
        <div id="upload-zone-wrap">
          <div class="upload-zone" id="upload-zone" onclick="document.getElementById('pf').click()" style="margin-bottom:.85rem;">
            <div class="uz-icon"><i class="fa-solid fa-image"></i></div>
            <div class="uz-text">Klik untuk pilih foto <strong style="color:var(--accent)">*</strong></div>
            <div style="font-size:.72rem;color:var(--text3);margin-top:.25rem;">
              <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent)"></i>
              Rekomendasi: <strong>1:1</strong> (square) atau <strong>4:5</strong> · Min. <strong>600×600px</strong> · Maks. <strong>5MB</strong> · JPG/PNG/WebP
            </div>
          </div>
          <div id="post-previews" style="display:none;margin-bottom:.85rem;"></div>
        </div>
        <input type="file" id="pf" name="images[]" accept="image/*" style="display:none">

        <!-- Caption -->
        <div class="form-group">
          <label>Caption <span style="color:var(--text3);font-size:.72rem;font-weight:400;">(opsional)</span></label>
          <textarea id="post-caption" name="caption" class="form-control" rows="3"
            placeholder="Ceritakan pengalamanmu..." style="min-height:72px;"></textarea>
        </div>

        <!-- Tag Katalog -->
        <div class="form-group">
          <label>
            <i class="fa-solid fa-location-dot fa-xs" style="color:var(--accent)"></i>
            Tag Katalog Tempat
            <?php if ($user['role'] === 'owner'): ?>
              <span style="color:var(--text3);font-size:.72rem;font-weight:400;">(opsional)</span>
            <?php else: ?>
              <span style="color:var(--red);font-size:.72rem;font-weight:700;">* wajib</span>
            <?php endif; ?>
          </label>

          <!-- Input search + dropdown dalam satu wrapper relative -->
          <div id="catalog-wrap" style="position:relative;">
            <div class="input-wrap">
              <i class="fa-solid fa-magnifying-glass i-icon fa-xs"></i>
              <input type="text" id="catalog-search-input" class="form-control"
                placeholder="Ketik nama tempat..." autocomplete="off">
            </div>
            <!-- Dropdown hasil pencarian -->
            <div id="catalog-dropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #FF6B35;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15);max-height:240px;overflow-y:auto;z-index:9999;"></div>
          </div>

          <input type="hidden" name="catalog_slug" id="catalog-slug-val">

          <div id="catalog-hint" style="font-size:.72rem;margin-top:.3rem;color:var(--text3);">
            <?php if ($user['role'] !== 'owner'): ?>
              <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent)"></i> Wajib pilih katalog tempat yang dikunjungi
            <?php else: ?>
              <i class="fa-solid fa-circle-info fa-xs"></i> Ketik minimal 2 huruf untuk mencari
            <?php endif; ?>
          </div>

          <!-- Chip katalog terpilih -->
          <div id="catalog-selected" style="display:none;margin-top:.5rem;">
            <span style="display:inline-flex;align-items:center;gap:.45rem;background:#fff3ee;border:1.5px solid #ffd4b8;border-radius:20px;padding:.3rem .75rem;font-size:.82rem;color:#FF6B35;font-weight:700;">
              <i class="fa-solid fa-location-dot fa-xs"></i>
              <span id="catalog-selected-name"></span>
              <button type="button" id="catalog-clear-btn"
                style="background:none;border:none;cursor:pointer;color:#FF6B35;padding:0;line-height:1;margin-left:.1rem;">
                <i class="fa-solid fa-xmark fa-xs"></i>
              </button>
            </span>
          </div>
        </div>

        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.85rem;">
          <button type="button" id="post-cancel-btn" class="btn btn-outline btn-sm">Batal</button>
          <button type="button" id="post-submit-btn" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-paper-plane fa-xs"></i> Bagikan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── Mobile Bottom Navigation ───────────────── -->
<?php
$curPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="mobile-nav">
  <div class="mobile-nav-inner">
    <a href="<?= APP_URL ?>/index.php" class="mob-nav-item <?= $curPage==='index'?'active':'' ?>">
      <i class="fa-solid fa-house"></i>
      <span>Beranda</span>
    </a>
    <a href="<?= APP_URL ?>/explore.php" class="mob-nav-item <?= $curPage==='explore'?'active':'' ?>">
      <i class="fa-solid fa-compass"></i>
      <span>Eksplorasi</span>
    </a>
    <?php if ($user && !$isDash): ?>
    <button class="mob-nav-item" onclick="openModal('create-post-modal')" style="position:relative;">
      <div style="width:44px;height:44px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;margin-top:-18px;box-shadow:0 4px 12px rgba(255,107,53,.4);">
        <i class="fa-solid fa-plus" style="color:#fff;font-size:1.1rem;"></i>
      </div>
      <span style="margin-top:.15rem;">Post</span>
    </button>
    <?php else: ?>
    <a href="<?= APP_URL ?>/catalog.php" class="mob-nav-item <?= $curPage==='catalog'?'active':'' ?>">
      <i class="fa-solid fa-map-pin"></i>
      <span>Katalog</span>
    </a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/<?= $user ? 'notifications.php' : 'login.php' ?>" class="mob-nav-item <?= $curPage==='notifications'?'active':'' ?>" style="position:relative;">
      <i class="fa-solid fa-bell"></i>
      <?php if ($user && $notifCnt > 0): ?>
      <span class="mob-nav-badge"><?= $notifCnt > 9 ? '9+' : $notifCnt ?></span>
      <?php endif; ?>
      <span>Notif</span>
    </a>
    <a href="<?= APP_URL ?>/<?= $user ? 'profile.php' : 'login.php' ?>" class="mob-nav-item <?= $curPage==='profile'?'active':'' ?>">
      <i class="fa-solid fa-user"></i>
      <span>Profil</span>
    </a>
  </div>
</nav>

<div id="toast-wrap"></div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
<?php if ($f = flash('success')): ?>toast('<?= addslashes($f) ?>','ok');<?php endif; ?>
<?php if ($f = flash('error')): ?>toast('<?= addslashes($f) ?>','err');<?php endif; ?>

(function() {
  // ── Konstanta ───────────────────────────────────────────
  var APP_URL   = '<?= APP_URL ?>';
  var USER_ROLE = '<?= $user["role"] ?? "" ?>';

  var postHasImage    = false;
  var selectedCatalog = null;
  var searchTimer     = null;

  // ── Elemen ──────────────────────────────────────────────
  function el(id) { return document.getElementById(id); }

  // ── Init setelah DOM siap ────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    initUpload();
    initCatalogSearch();
    initFormButtons();
  });

  // ── Upload foto ──────────────────────────────────────────
  function initUpload() {
    var fileInput = el('pf');
    if (!fileInput) return;

    fileInput.addEventListener('change', function() {
      if (!this.files || !this.files[0]) return;
      var file = this.files[0];

      if (file.size > 5 * 1024 * 1024) {
        toast('Ukuran file maks. 5MB', 'err');
        this.value = '';
        return;
      }

      postHasImage = true;
      el('upload-zone').style.display = 'none';

      var preview = el('post-previews');
      preview.innerHTML = '';
      preview.style.display = 'block';

      var reader = new FileReader();
      reader.onload = function(e) {
        var wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative;';

        var img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width:100%;max-height:280px;object-fit:cover;border-radius:10px;border:1.5px solid #e0e0e0;display:block;';

        var changeBtn = document.createElement('button');
        changeBtn.type = 'button';
        changeBtn.innerHTML = '<i class="fa-solid fa-camera fa-xs"></i> Ganti Foto';
        changeBtn.style.cssText = 'position:absolute;bottom:.5rem;right:.5rem;background:rgba(0,0,0,.65);color:#fff;border:none;border-radius:8px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;cursor:pointer;';
        changeBtn.onclick = function() { el('pf').click(); };

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        removeBtn.style.cssText = 'position:absolute;top:.4rem;right:.4rem;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;width:26px;height:26px;cursor:pointer;font-size:.75rem;';
        removeBtn.onclick = function() {
          postHasImage = false;
          preview.style.display = 'none';
          preview.innerHTML = '';
          el('upload-zone').style.display = '';
          el('pf').value = '';
        };

        wrap.appendChild(img);
        wrap.appendChild(changeBtn);
        wrap.appendChild(removeBtn);
        preview.appendChild(wrap);
      };
      reader.readAsDataURL(file);
    });
  }

  // ── Catalog Search ───────────────────────────────────────
  function initCatalogSearch() {
    var inp      = el('catalog-search-input');
    var dropdown = el('catalog-dropdown');
    var clearBtn = el('catalog-clear-btn');

    if (!inp) return;

    // Ketik → cari
    inp.addEventListener('input', function() {
      var val = this.value.trim();
      clearTimeout(searchTimer);

      if (val.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
      }

      // Tampilkan loading
      dropdown.innerHTML = '<div style="padding:.85rem;text-align:center;color:#999;font-size:.82rem;"><i class="fa-solid fa-spinner fa-spin fa-xs"></i> Mencari...</div>';
      dropdown.style.display = 'block';

      searchTimer = setTimeout(function() {
        fetch(APP_URL + '/actions/search_catalog.php?q=' + encodeURIComponent(val))
          .then(function(r) { return r.json(); })
          .then(function(data) {
            renderDropdown(data);
          })
          .catch(function() {
            dropdown.innerHTML = '<div style="padding:.85rem;text-align:center;color:#ef4444;font-size:.82rem;">Gagal memuat data</div>';
          });
      }, 300);
    });

    // Clear btn
    if (clearBtn) {
      clearBtn.addEventListener('click', function() {
        clearCatalog();
      });
    }

    // Klik di luar → tutup
    document.addEventListener('click', function(e) {
      if (!e.target.closest('#catalog-wrap')) {
        dropdown.style.display = 'none';
      }
    });
  }

  function renderDropdown(data) {
    var dropdown = el('catalog-dropdown');
    dropdown.innerHTML = '';

    if (!data || data.length === 0) {
      dropdown.innerHTML = '<div style="padding:.85rem;text-align:center;color:#999;font-size:.82rem;"><i class="fa-solid fa-magnifying-glass fa-xs"></i> Tidak ditemukan</div>';
      dropdown.style.display = 'block';
      return;
    }

    data.forEach(function(cat, idx) {
      var item = document.createElement('div');
      item.style.cssText = 'display:flex;align-items:center;gap:.7rem;padding:.7rem .9rem;cursor:pointer;' +
        (idx < data.length - 1 ? 'border-bottom:1px solid #f0f0f0;' : '');

      var thumb = cat.thumbnail
        ? '<img src="' + cat.thumbnail + '" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">'
        : '<i class="fa-solid ' + (cat.cat_icon || 'fa-store') + '" style="color:#FF6B35;"></i>';

      item.innerHTML =
        '<div style="width:40px;height:40px;border-radius:8px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">' + thumb + '</div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-weight:700;font-size:.87rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#1a1a1a;">' + cat.name + '</div>' +
          '<div style="font-size:.71rem;color:#888;margin-top:.1rem;">' + cat.cat_name + ' &bull; ' + cat.city + '</div>' +
        '</div>' +
        '<div style="width:24px;height:24px;border-radius:50%;background:#fff3ee;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
          '<i class="fa-solid fa-plus fa-xs" style="color:#FF6B35;"></i>' +
        '</div>';

      item.addEventListener('mouseover', function() { this.style.background = '#fff5f0'; });
      item.addEventListener('mouseout',  function() { this.style.background = ''; });

      // mousedown bukan click — agar blur tidak menutup dropdown lebih dulu
      item.addEventListener('mousedown', function(e) {
        e.preventDefault();
        pickCatalog(cat);
      });

      dropdown.appendChild(item);
    });

    dropdown.style.display = 'block';
  }

  function pickCatalog(cat) {
    selectedCatalog = cat;
    el('catalog-slug-val').value = cat.slug;
    el('catalog-selected-name').textContent = cat.name;
    el('catalog-selected').style.display = 'block';
    el('catalog-wrap').style.display = 'none';
    if (el('catalog-hint')) el('catalog-hint').style.display = 'none';
  }

  function clearCatalog() {
    selectedCatalog = null;
    el('catalog-slug-val').value = '';
    el('catalog-selected').style.display = 'none';
    el('catalog-wrap').style.display = '';
    el('catalog-search-input').value = '';
    el('catalog-dropdown').style.display = 'none';
    el('catalog-dropdown').innerHTML = '';
    if (el('catalog-hint')) el('catalog-hint').style.display = '';
  }

  // Expose ke global untuk tombol × di HTML
  window.clearCatalogSelection = clearCatalog;

  // ── Form buttons ─────────────────────────────────────────
  function initFormButtons() {
    // Tombol Bagikan
    var submitBtn = el('post-submit-btn');
    if (submitBtn) {
      submitBtn.addEventListener('click', function() {
        // Validasi gambar
        if (!postHasImage) {
          toast('Foto wajib dipilih sebelum posting!', 'err');
          var zone = el('upload-zone');
          zone.style.borderColor = '#ef4444';
          zone.style.background  = '#fef2f2';
          setTimeout(function() { zone.style.borderColor = ''; zone.style.background = ''; }, 2000);
          return;
        }
        // Validasi katalog wajib (bukan owner)
        if (USER_ROLE !== 'owner' && !selectedCatalog) {
          toast('Pilih katalog tempat yang dikunjungi!', 'err');
          var inp = el('catalog-search-input');
          if (inp) {
            inp.style.borderColor = '#ef4444';
            inp.focus();
            setTimeout(function() { inp.style.borderColor = ''; }, 2000);
          }
          return;
        }
        // Submit form
        el('post-form').submit();
      });
    }

    // Tombol Batal
    var cancelBtn = el('post-cancel-btn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function() {
        closeModal('create-post-modal');
        resetForm();
      });
    }
  }

  function resetForm() {
    postHasImage = false;
    if (el('upload-zone'))    el('upload-zone').style.display = '';
    if (el('post-previews'))  { el('post-previews').style.display = 'none'; el('post-previews').innerHTML = ''; }
    if (el('pf'))             el('pf').value = '';
    if (el('post-caption'))   el('post-caption').value = '';
    clearCatalog();
  }

  // Reset saat modal ditutup via X
  var modal = el('create-post-modal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-close') || e.target.closest('.modal-close')) {
        setTimeout(resetForm, 300);
      }
    });
  }

})(); // IIFE — semua variabel terisolasi
</script>
<?= $extraJs ?? '' ?>
<script>
// ── Mobile Drawer ────────────────────────────────────
function openDrawer() {
  document.getElementById('sidebar-drawer').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('sidebar-drawer').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
  document.body.style.overflow = '';
}
// Show hamburger on mobile
function checkMobile() {
  const hb = document.getElementById('hamburger-btn');
  if (hb) hb.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
}
checkMobile();
window.addEventListener('resize', checkMobile);
// Close drawer on ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>
</body>
</html>
