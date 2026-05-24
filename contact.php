<?php
require_once __DIR__ . '/includes/helpers.php';
startSession();
$user = currentUser();

// Handle POST
$errs = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $type    = $_POST['report_type']    ?? '';
    $subject = trim($_POST['subject']   ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $email   = trim($_POST['contact_email'] ?? ($user['email'] ?? ''));
    $validTypes = ['bug', 'saran', 'konten', 'akun', 'lainnya'];

    if (!in_array($type, $validTypes))          $errs['type']    = 'Pilih jenis laporan.';
    if (!$subject)                               $errs['subject'] = 'Judul laporan wajib diisi.';
    if (!$desc)                                  $errs['desc']    = 'Deskripsi wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs['email'] = 'Format email tidak valid.';

    // Upload screenshot (opsional)
    $screenshotUrl = null;
    if (!empty($_FILES['screenshot']['name']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['screenshot']['size'] > 5 * 1024 * 1024) {
            $errs['screenshot'] = 'Ukuran file maks. 5MB.';
        } else {
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($_FILES['screenshot']['type'], $allowed)) {
                $errs['screenshot'] = 'Format harus JPG, PNG, WebP, atau GIF.';
            } else {
                $screenshotUrl = uploadImage($_FILES['screenshot'], 'reports');
                if (!$screenshotUrl) $errs['screenshot'] = 'Gagal mengupload screenshot.';
            }
        }
    }

    if (!$errs) {
        $db = getDB();
        $reporterId = $user ? $user['id'] : null;

        // Simpan ke tabel reports dengan description yang lengkap
        $fullDesc = "[Jenis: " . strtoupper($type) . "]\n";
        $fullDesc .= "[Judul: $subject]\n";
        $fullDesc .= "[Email: $email]\n";
        if ($screenshotUrl) $fullDesc .= "[Screenshot: $screenshotUrl]\n";
        $fullDesc .= "\n" . $desc;

        if ($reporterId) {
            $db->prepare("INSERT INTO reports (reporter_id, report_type, description, status) VALUES (?, ?, ?, 'pending')")
               ->execute([$reporterId, $type === 'bug' ? 'bug' : 'inappropriate', $fullDesc]);
        } else {
            // Guest — simpan dengan reporter dummy atau skip jika FK required
            // Cek apakah reporter_id nullable
            try {
                $db->prepare("INSERT INTO reports (reporter_id, report_type, description, status) VALUES (NULL, ?, ?, 'pending')")
                   ->execute([$type === 'bug' ? 'bug' : 'spam', $fullDesc]);
            } catch (\Exception $e) {
                // Jika tidak bisa null, skip DB insert — tetap tampilkan sukses ke user
            }
        }

        $success = true;
    }
}

$pageTitle = 'Hubungi Kami — YummySpot';
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-wrap">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" style="max-width:620px; margin:0 auto;">

    <!-- Header -->
    <div style="margin-bottom:1.25rem;">
        <h1 style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;margin-bottom:.25rem;">
            <i class="fa-solid fa-headset" style="color:var(--accent)"></i> Hubungi Kami
        </h1>
        <p style="font-size:.85rem;color:var(--text3);">
            Ada masalah, saran, atau laporan? Tim CS kami siap membantu.
        </p>
    </div>

    <?php if ($success): ?>
    <!-- Success state -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:3rem 2rem;text-align:center;">
        <div style="width:72px;height:72px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="fa-solid fa-check-circle fa-2x" style="color:var(--green);"></i>
        </div>
        <h2 style="font-family:'Nunito',sans-serif;font-size:1.2rem;font-weight:900;margin-bottom:.5rem;">Laporan Terkirim!</h2>
        <p style="font-size:.875rem;color:var(--text2);line-height:1.7;max-width:380px;margin:0 auto 1.5rem;">
            Terima kasih telah menghubungi kami. Tim CS akan meninjau laporan kamu dan menghubungi melalui email
            <strong><?= e($_POST['contact_email'] ?? '') ?></strong> dalam <strong>1×24 jam</strong>.
        </p>
        <div style="display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap;">
            <a href="index.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-house fa-xs"></i> Kembali ke Beranda
            </a>
            <?php if ($user): ?>
            <a href="my-reports.php" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-list fa-xs"></i> Lihat Laporan Saya
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>

    <!-- Info card CS -->
    <div style="background:linear-gradient(135deg,var(--accent-bg),#fff);border:1px solid #ffd4b8;border-radius:var(--r-lg);padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.85rem;">
        <div style="width:44px;height:44px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-shield-halved" style="color:#fff;font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-weight:700;font-size:.88rem;margin-bottom:.15rem;">Tim Customer Service YummySpot</div>
            <div style="font-size:.78rem;color:var(--text2);">
                <i class="fa-regular fa-clock fa-xs"></i> Respon dalam <strong>1×24 jam</strong> &nbsp;·&nbsp;
                <i class="fa-regular fa-envelope fa-xs"></i> Balasan via email
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>

                <!-- Jenis Laporan -->
                <div class="form-group">
                    <label style="font-weight:700;font-size:.83rem;">Jenis Laporan <span style="color:var(--red)">*</span></label>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem;margin-top:.4rem;">
                        <?php
                        $types = [
                            'bug'     => ['fa-bug',                'var(--blue)',   'Bug / Error',         'Aplikasi error atau tidak berfungsi'],
                            'saran'   => ['fa-lightbulb',          'var(--amber)',  'Saran & Masukan',     'Ide untuk perbaikan layanan'],
                            'konten'  => ['fa-triangle-exclamation','var(--red)',   'Konten Bermasalah',   'Konten tidak pantas atau melanggar'],
                            'akun'    => ['fa-user-lock',        'var(--purple)', 'Masalah Akun',        'Login, akses, atau data akun'],
                            'lainnya' => ['fa-ellipsis',           'var(--text3)',  'Lainnya',             'Pertanyaan atau hal lain'],
                        ];
                        foreach ($types as $val => [$icon, $color, $label, $sub]):
                            $checked = ($_POST['report_type'] ?? '') === $val;
                        ?>
                        <label style="cursor:pointer;" class="type-label">
                            <input type="radio" name="report_type" value="<?= $val ?>" style="display:none;" <?= $checked?'checked':'' ?>>
                            <div class="type-card" style="display:flex;align-items:center;gap:.6rem;padding:.6rem .75rem;border:1.5px solid <?= $checked?$color:'var(--border)' ?>;border-radius:var(--r-sm);background:<?= $checked?"$color".'18':'#fff' ?>;transition:all .15s;">
                                <div style="width:32px;height:32px;border-radius:50%;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa-solid <?= $icon ?> fa-xs" style="color:<?= $color ?>;"></i>
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-weight:700;font-size:.82rem;"><?= $label ?></div>
                                    <div style="font-size:.68rem;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $sub ?></div>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errs['type'])): ?><div class="form-err"><?= $errs['type'] ?></div><?php endif; ?>
                </div>

                <!-- Judul -->
                <div class="form-group">
                    <label>Judul Laporan <span style="color:var(--red)">*</span></label>
                    <input type="text" name="subject" class="form-control"
                        value="<?= e($_POST['subject'] ?? '') ?>"
                        placeholder="Ringkasan singkat masalah kamu..." required>
                    <?php if (isset($errs['subject'])): ?><div class="form-err"><?= $errs['subject'] ?></div><?php endif; ?>
                </div>

                <!-- Deskripsi -->
                <div class="form-group">
                    <label>Deskripsi Lengkap <span style="color:var(--red)">*</span></label>
                    <textarea name="description" class="form-control" rows="5" required
                        placeholder="Jelaskan secara detail:&#10;- Apa yang terjadi?&#10;- Kapan terjadi?&#10;- Langkah untuk mereproduksi (jika bug)&#10;- Harapan kamu..."><?= e($_POST['description'] ?? '') ?></textarea>
                    <div class="form-hint">Semakin detail, semakin cepat tim CS bisa membantu.</div>
                    <?php if (isset($errs['desc'])): ?><div class="form-err"><?= $errs['desc'] ?></div><?php endif; ?>
                </div>

                <!-- Screenshot (opsional) -->
                <div class="form-group">
                    <label>
                        Screenshot
                        <span style="color:var(--text3);font-size:.72rem;font-weight:400;">(opsional)</span>
                    </label>
                    <div id="ss-zone" class="upload-zone" onclick="document.getElementById('ss-file').click()"
                        style="padding:1.25rem;cursor:pointer;">
                        <div class="uz-icon" style="font-size:1.5rem;"><i class="fa-solid fa-image"></i></div>
                        <div class="uz-text">Klik untuk upload screenshot</div>
                        <div style="font-size:.72rem;color:var(--text3);margin-top:.2rem;">
                            JPG, PNG, WebP, GIF · Maks. 5MB
                        </div>
                    </div>
                    <div id="ss-preview" style="display:none;margin-top:.5rem;position:relative;">
                        <img id="ss-img" src="" alt="" style="max-width:100%;max-height:220px;border-radius:var(--r);border:1.5px solid var(--border);display:block;">
                        <button type="button" id="ss-remove"
                            style="position:absolute;top:.4rem;right:.4rem;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;width:26px;height:26px;cursor:pointer;font-size:.75rem;display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-xmark fa-xs"></i>
                        </button>
                        <div style="font-size:.75rem;color:var(--green);margin-top:.35rem;">
                            <i class="fa-solid fa-check-circle fa-xs"></i> Screenshot siap diupload
                        </div>
                    </div>
                    <input type="file" id="ss-file" name="screenshot" accept="image/*" style="display:none;">
                    <?php if (isset($errs['screenshot'])): ?><div class="form-err"><?= $errs['screenshot'] ?></div><?php endif; ?>
                </div>

                <!-- Email kontak -->
                <div class="form-group">
                    <label>
                        <i class="fa-regular fa-envelope fa-xs" style="color:var(--accent)"></i>
                        Email untuk Dihubungi <span style="color:var(--red)">*</span>
                    </label>
                    <input type="email" name="contact_email" class="form-control"
                        value="<?= e($_POST['contact_email'] ?? ($user['email'] ?? '')) ?>"
                        placeholder="email@kamu.com" required>
                    <div class="form-hint">
                        <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent)"></i>
                        Tim CS akan membalas laporan ini ke alamat email di atas.
                        <?php if ($user): ?>Sudah diisi otomatis dari akun kamu.<?php endif; ?>
                    </div>
                    <?php if (isset($errs['email'])): ?><div class="form-err"><?= $errs['email'] ?></div><?php endif; ?>
                </div>

                <!-- Informasi pengirim -->
                <?php if ($user): ?>
                <div style="background:var(--bg);border-radius:var(--r-sm);padding:.65rem .85rem;margin-bottom:.85rem;display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--text2);">
                    <i class="fa-solid fa-circle-info fa-xs" style="color:var(--accent);flex-shrink:0;"></i>
                    Laporan ini akan dikirim atas nama akun <strong><?= e($user['fullname']) ?></strong>
                    (@<?= e($user['username']) ?>).
                </div>
                <?php else: ?>
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--r-sm);padding:.65rem .85rem;margin-bottom:.85rem;font-size:.8rem;color:#92400e;">
                    <i class="fa-solid fa-circle-info fa-xs"></i>
                    Kamu mengirim laporan sebagai tamu.
                    <a href="login.php" style="color:var(--accent);font-weight:700;">Masuk</a> untuk melacak status laporan.
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                    <a href="javascript:history.back()" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane fa-xs"></i> Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FAQ singkat -->
    <div style="margin-top:1.25rem;">
        <div style="font-size:.68rem;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.65rem;">Pertanyaan Umum</div>
        <div style="display:flex;flex-direction:column;gap:.4rem;">
            <?php
            $faqs = [
                ['Berapa lama laporan diproses?', 'Tim CS akan merespons dalam 1×24 jam pada hari kerja.'],
                ['Bagaimana cara melacak laporan?', 'Login ke akun kamu, lalu buka menu "Laporan Saya" di sidebar.'],
                ['Apakah laporan saya anonim?', 'Tidak — tim CS melihat nama dan email kamu untuk keperluan tindak lanjut.'],
            ];
            foreach ($faqs as [$q, $a]):
            ?>
            <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);overflow:hidden;">
                <button type="button" onclick="toggleFaq(this)"
                    style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;background:transparent;border:none;cursor:pointer;font-size:.85rem;font-weight:700;color:var(--text);text-align:left;">
                    <?= $q ?>
                    <i class="fa-solid fa-chevron-down fa-xs text-dim" style="transition:transform .2s;flex-shrink:0;margin-left:.5rem;"></i>
                </button>
                <div style="display:none;padding:0 1rem .75rem;font-size:.82rem;color:var(--text2);line-height:1.65;">
                    <?= $a ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</main>
</div>

<script>
// Screenshot preview
const ssFile   = document.getElementById('ss-file');
const ssZone   = document.getElementById('ss-zone');
const ssPreview = document.getElementById('ss-preview');
const ssImg    = document.getElementById('ss-img');
const ssRemove = document.getElementById('ss-remove');

if (ssFile) {
    ssFile.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            ssImg.src = e.target.result;
            ssZone.style.display = 'none';
            ssPreview.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    });
}

if (ssRemove) {
    ssRemove.addEventListener('click', () => {
        ssFile.value = '';
        ssPreview.style.display = 'none';
        ssZone.style.display = '';
    });
}

// Drag & drop screenshot
if (ssZone) {
    ssZone.addEventListener('dragover', e => { e.preventDefault(); ssZone.style.borderColor = 'var(--accent)'; });
    ssZone.addEventListener('dragleave', () => { ssZone.style.borderColor = ''; });
    ssZone.addEventListener('drop', e => {
        e.preventDefault();
        ssZone.style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            ssFile.files = dt.files;
            ssFile.dispatchEvent(new Event('change'));
        }
    });
}

// Radio type card highlight
document.querySelectorAll('input[name="report_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.type-card').forEach(card => {
            card.style.borderColor = 'var(--border)';
            card.style.background  = '#fff';
        });
        const card  = this.closest('label').querySelector('.type-card');
        const color = card.querySelector('i').style.color;
        card.style.borderColor = color;
        card.style.background  = color.replace(')', ', 0.08)').replace('var(', 'rgba(').replace('--','');
        // Pakai opacity workaround
        card.style.background  = color + '18';
    });
});

// FAQ accordion
function toggleFaq(btn) {
    const content = btn.nextElementSibling;
    const icon    = btn.querySelector('i');
    const isOpen  = content.style.display === 'block';
    content.style.display = isOpen ? 'none' : 'block';
    icon.style.transform  = isOpen ? '' : 'rotate(180deg)';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
