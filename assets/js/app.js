// Toast
function toast(msg, type='ok') {
  let w = document.getElementById('toast-wrap');
  if (!w) { w = document.createElement('div'); w.id='toast-wrap'; document.body.appendChild(w); }
  const t = document.createElement('div');
  const icon = type==='ok' ? '<i class="fa-solid fa-circle-check" style="color:var(--green)"></i>' : '<i class="fa-solid fa-circle-exclamation" style="color:var(--red)"></i>';
  t.className = `toast ${type}`;
  t.innerHTML = `${icon} ${msg}`;
  w.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='.3s'; setTimeout(()=>t.remove(),300); }, 2800);
}

// Dropdown
document.addEventListener('click', e => {
  const tr = e.target.closest('[data-dd]');
  if (tr) {
    const el = document.getElementById(tr.dataset.dd);
    if (el) {
      document.querySelectorAll('.user-dd.open').forEach(d => { if(d!==el) d.classList.remove('open'); });
      el.classList.toggle('open'); e.stopPropagation(); return;
    }
  }
  document.querySelectorAll('.user-dd.open').forEach(d => d.classList.remove('open'));
});

// Modal
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-ov')) e.target.classList.remove('open');
  if (e.target.closest('.modal-close')) e.target.closest('.modal-ov')?.classList.remove('open');
});

// CSRF
function getCsrf() { return document.querySelector('meta[name="csrf"]')?.content || ''; }

// AJAX Like
function toggleLike(postId, btn) {
  fetch('actions/like.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`post_id=${postId}&csrf_token=${getCsrf()}` })
  .then(r=>r.json()).then(d => {
    if (d.ok) {
      btn.classList.toggle('liked', d.liked);
      btn.querySelector('i').className = d.liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
      const c = btn.querySelector('.lcount'); if(c) c.textContent = d.count;
    }
  });
}

// AJAX Wishlist
function toggleWishlist(catalogId, btn) {
  fetch('actions/wishlist.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`catalog_id=${catalogId}&csrf_token=${getCsrf()}` })
  .then(r=>r.json()).then(d => {
    if (d.ok) {
      btn.classList.toggle('liked', d.saved);
      btn.querySelector('i').className = d.saved ? 'fa-solid fa-bookmark' : 'fa-regular fa-bookmark';
      toast(d.saved ? 'Ditambahkan ke wishlist' : 'Dihapus dari wishlist');
    }
  });
}

// AJAX Follow
function toggleFollow(uid, btn) {
  fetch('actions/follow.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`user_id=${uid}&csrf_token=${getCsrf()}` })
  .then(r=>r.json()).then(d => {
    if (d.ok) {
      btn.textContent = d.following ? 'Mengikuti' : 'Ikuti';
      btn.className = d.following ? 'btn btn-outline btn-sm' : 'btn btn-primary btn-sm';
      toast(d.following ? 'Berhasil mengikuti' : 'Berhenti mengikuti');
    }
  });
}

// Image preview
function previewImg(input, targetId) {
  if (input.files?.[0]) {
    const r = new FileReader();
    r.onload = e => { const el=document.getElementById(targetId); if(el){el.src=e.target.result;el.style.display='block';} };
    r.readAsDataURL(input.files[0]);
  }
}

// Multi image preview
function previewMulti(input, containerId) {
  const c = document.getElementById(containerId); if(!c) return;
  c.innerHTML = '';
  Array.from(input.files).slice(0,5).forEach(f => {
    const r = new FileReader();
    r.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:68px;height:68px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border)';
      c.appendChild(img);
    };
    r.readAsDataURL(f);
  });
}

// Star rating
function initStars(containerId, inputId) {
  const c = document.getElementById(containerId);
  const inp = document.getElementById(inputId);
  if (!c||!inp) return;
  const stars = c.querySelectorAll('i');
  stars.forEach((s,i) => {
    s.addEventListener('click', () => {
      inp.value = i+1;
      stars.forEach((x,j) => x.classList.toggle('on', j<=i));
    });
    s.addEventListener('mouseenter', () => stars.forEach((x,j) => x.classList.toggle('on', j<=i)));
  });
  c.addEventListener('mouseleave', () => {
    const v = parseInt(inp.value)||0;
    stars.forEach((x,j) => x.classList.toggle('on', j<v));
  });
}

// Auto-resize textarea
document.addEventListener('input', e => {
  if (e.target.dataset.autoresize) { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px'; }
});

// Flash auto-dismiss
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-dismiss]').forEach(el => {
    setTimeout(() => { el.style.opacity='0'; el.style.transition='.4s'; setTimeout(()=>el.remove(),400); }, 3500);
  });
});
