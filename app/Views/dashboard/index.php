<section class="page-head" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
  <h1 style="margin-right:auto">Dashboard</h1>

  <!-- Filter -->
  <form method="get" action="/dashboard" id="flt" style="display:flex;gap:8px;align-items:center">
    <input type="text" name="q" value="<?= esc($q ?? '') ?>" placeholder="Cari alias/NVR/monitor..." style="min-width:240px">

    <label for="per">Per page</label>
    <select name="per" id="per">
      <?php foreach ([10,25,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= (isset($per) && (int)$per === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <input type="hidden" name="page" value="<?= (int)($page ?? 1) ?>">
    <button class="btn ghost" type="submit">Apply</button>
    <a class="btn" href="/dashboard" style="background:#ef4444">Reset</a>
  </form>
</section>

<p class="muted">Preview kamera. (Super Admin: agregat semua NVR)</p>

<?php if (empty($tiles)): ?>
  <p style="color:#94a3b8">Belum ada kamera untuk ditampilkan.</p>
<?php else: ?>

  <!-- GRID autoplay + drag n drop -->
  <section id="grid" class="grid">
    <?php foreach ($tiles as $t): ?>
      <article class="card cam" draggable="true"
               data-id="<?= esc($t['id']) ?>"
               data-hls="<?= esc($t['hls']) ?>"
               data-alias="<?= esc($t['alias']) ?>">
        <div class="thumb" style="cursor:move; position:relative">
          <!-- video langsung (tanpa thumbnail, auto play) -->
          <video class="vid" muted playsinline autoplay
                 style="width:100%;height:100%;object-fit:cover;background:#000"></video>

          <!-- fullscreen -->
          <button class="btn ghost" onclick="fsTile(event,this)" title="Fullscreen"
                  style="position:absolute;right:8px;top:8px;z-index:3">⤢</button>

          <!-- label overlay kiri-atas -->
          <span class="chip" title="<?= esc($t['alias']) ?>" style="z-index:2">
            <?= esc($t['nvr']) ?> / <?= esc($t['monitor_id']) ?>
          </span>

          <!-- overlay tombol Videos -->
          <div class="overlay">
            <a class="btn videos-btn" href="#" onclick="openVideos(this);return false;">Videos (3 hari)</a>
          </div>
        </div>
        <!-- meta/title bawah DIHAPUS sesuai request -->
      </article>
    <?php endforeach; ?>
  </section>

  <!-- Pagination -->
  <div class="pagination" style="display:flex;gap:6px;justify-content:center;margin:16px 0">
    <?php
      $curr = (int)($page ?? 1);
      $max  = (int)($pages ?? 1);
      $perQ = (int)($per ?? 10);
      $qStr = ($q ?? '') !== '' ? '&q=' . urlencode($q) : '';
      $mk   = function($p) use ($perQ, $qStr){ return '/dashboard?page='.$p.'&per='.$perQ.$qStr; };
      $window = 2; $start = max(1, $curr-$window); $end = min($max, $curr+$window);
    ?>

    <?php if ($curr > 1): ?>
      <a class="btn ghost" href="<?= $mk($curr-1) ?>">&laquo; Prev</a>
    <?php else: ?>
      <span class="btn ghost" style="opacity:.5;pointer-events:none">&laquo; Prev</span>
    <?php endif; ?>

    <?php if ($start > 1): ?>
      <a class="btn ghost" href="<?= $mk(1) ?>">1</a>
      <?php if ($start > 2): ?><span class="btn ghost" style="pointer-events:none">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i=$start; $i<=$end; $i++): ?>
      <?php if ($i === $curr): ?>
        <span class="btn" style="pointer-events:none"><?= $i ?></span>
      <?php else: ?>
        <a class="btn ghost" href="<?= $mk($i) ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $max): ?>
      <?php if ($end < $max-1): ?><span class="btn ghost" style="pointer-events:none">…</span><?php endif; ?>
      <a class="btn ghost" href="<?= $mk($max) ?>"><?= $max ?></a>
    <?php endif; ?>

    <?php if ($curr < $max): ?>
      <a class="btn ghost" href="<?= $mk($curr+1) ?>">Next &raquo;</a>
    <?php else: ?>
      <span class="btn ghost" style="opacity:.5;pointer-events:none">Next &raquo;</span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- HLS -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.8/dist/hls.min.js"></script>
<script>
function attachHls(videoEl, url){
  if (!videoEl) return null;
  if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
    videoEl.src = url;
    videoEl.muted = true;
    videoEl.play().catch(()=>{});
    return {type:'native'};
  } else if (window.Hls && window.Hls.isSupported()) {
    const hls = new Hls({liveDurationInfinity:true});
    hls.loadSource(url);
    hls.attachMedia(videoEl);
    videoEl.muted = true;
    videoEl.play().catch(()=>{});
    return {type:'hls', hls};
  } else {
    return null;
  }
}
document.querySelectorAll('.cam').forEach(card => {
  const url = card.dataset.hls;
  const vid = card.querySelector('.vid');
  card._hlsObj = attachHls(vid, url);
});
function fsTile(ev, btn){
  ev.stopPropagation();
  const card = btn.closest('.cam');
  const elem = card.querySelector('.thumb');
  if (elem.requestFullscreen) elem.requestFullscreen();
  else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
}

/* ====== FIX: parser HLS lebih robust ======
   Pola yang dicoba, berurutan:
   1) /{group}/{api}/hls/{monitor}/...
   2) /{group}/{api}/monitors/{monitor}/hls/...
   3) .../{group}/{api}/{monitor}/hls/...
*/
function parseFromHls(hlsUrl){
  try{
    const u   = new URL(hlsUrl);
    const seg = u.pathname.split('/').filter(Boolean);
    const low = seg.map(s => s.toLowerCase());
    const L   = seg.length;

    // 1) .../{group}/{api}/hls/{monitor}/...
    {
      const i = low.lastIndexOf('hls');
      if (i > 1 && i < L-1) {
        const group = seg[i-2], api = seg[i-1], mon = seg[i+1];
        if (group && api && mon) return { base:u.origin, g:group, k:api, mon };
      }
    }
    // 2) .../{group}/{api}/monitors/{monitor}/hls/...
    {
      const i = low.lastIndexOf('monitors');
      if (i > 1 && i < L-1) {
        const group = seg[i-2], api = seg[i-1], mon = seg[i+1];
        // pastikan setelah monitors ada "hls" di i+2 atau i+3
        if (group && api && mon) return { base:u.origin, g:group, k:api, mon };
      }
    }
    // 3) .../{group}/{api}/{monitor}/hls/...
    {
      const i = low.indexOf('hls');
      if (i >= 3) {
        const group = seg[i-3], api = seg[i-2], mon = seg[i-1];
        if (group && api && mon) return { base:u.origin, g:group, k:api, mon };
      }
    }
  }catch(e){}
  return null;
}

function openVideos(btn){
  const card  = btn.closest('.cam');
  const hls   = card?.dataset?.hls || '';
  const alias = card?.dataset?.alias || '';
  const p = parseFromHls(hls);
  if (p) {
    const qs = new URLSearchParams({ base:p.base, g:p.g, k:p.k, mon:p.mon, cam:alias });
    window.open('/videos?'+qs.toString(), '_blank');
  } else {
    // fallback: tetap buka /videos biar user bisa isi manual
    window.open('/videos', '_blank');
  }
}

const grid = document.getElementById('grid');
let dragSrc = null;
grid?.addEventListener('dragstart', (e) => {
  const card = e.target.closest('.cam'); if (!card) return;
  dragSrc = card; e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', card.dataset.id);
  card.classList.add('dragging');
});
grid?.addEventListener('dragover', (e) => {
  e.preventDefault();
  const over = e.target.closest('.cam'); if (!over || over === dragSrc) return;
  const cards = [...grid.querySelectorAll('.cam')];
  const srcIndex  = cards.indexOf(dragSrc);
  const overIndex = cards.indexOf(over);
  if (srcIndex < overIndex) grid.insertBefore(dragSrc, over.nextSibling);
  else grid.insertBefore(dragSrc, over);
});
grid?.addEventListener('drop', (e) => { e.preventDefault(); saveOrder(); });
grid?.addEventListener('dragend', (e) => {
  const card = e.target.closest('.cam');
  if (card) card.classList.remove('dragging');
  saveOrder();
});
function saveOrder(){
  const ids = [...grid.querySelectorAll('.cam')].map(c => c.dataset.id);
  localStorage.setItem('sandya_nvr_dash_order', JSON.stringify(ids));
}
(function applySavedOrder(){
  try{
    const ids = JSON.parse(localStorage.getItem('sandya_nvr_dash_order') || '[]');
    if (!Array.isArray(ids) || !ids.length) return;
    const map = {};
    [...grid.querySelectorAll('.cam')].forEach(c => map[c.dataset.id] = c);
    ids.forEach(id => { if (map[id]) grid.appendChild(map[id]); });
  }catch(e){}
})();
const perSel = document.getElementById('per');
perSel?.addEventListener('change', () => {
  localStorage.setItem('sandya_nvr_perpage', perSel.value);
  document.getElementById('flt').submit();
});
(function applySavedPerPage(){
  try{
    const hasPerInUrl = new URLSearchParams(location.search).has('per');
    const saved = localStorage.getItem('sandya_nvr_perpage');
    if (!hasPerInUrl && saved && ['10','25','50','100'].includes(saved) && perSel.value !== saved){
      perSel.value = saved;
      document.getElementById('flt').submit();
    }
  }catch(e){}
})();
</script>

<style>
.cam.dragging { opacity:.6; transform:scale(.98); }
/* overlay videos */
.overlay {
  position:absolute; inset:0;
  display:flex; align-items:center; justify-content:center;
  opacity:0; transition:opacity .2s; background:rgba(0,0,0,.25); z-index:1;
}
.thumb:hover .overlay { opacity:1; }
.videos-btn {
  background:#7c3aed; color:#fff; text-decoration:none;
  padding:10px 16px; border-radius:10px; font-weight:700;
}
</style>
