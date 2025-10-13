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

  <!-- GRID autoplay + drag n drop + resizable -->
  <section id="grid" class="grid">
    <?php foreach ($tiles as $t): ?>
      <article class="card cam" draggable="true"
               data-id="<?= esc($t['id']) ?>"
               data-hls="<?= esc($t['hls']) ?>"
               data-alias="<?= esc($t['alias']) ?>"
               data-nvr-id="<?= (int)$t['nvr_id'] ?>"
               data-mon="<?= esc($t['monitor_id']) ?>"
               style="--w:1;--h:1">
        <div class="thumb" style="cursor:move; position:relative">
          <video class="vid" muted playsinline autoplay
                 style="width:100%;height:100%;object-fit:cover;background:#000"></video>

          <!-- fullscreen -->
          <button class="btn ghost fs-btn" onclick="fsTile(event,this)" title="Fullscreen">⤢</button>

          <!-- label -->
          <span class="chip cam-label" title="<?= esc($t['alias']) ?>">
            <?= esc($t['nvr']) ?> / <?= esc($t['monitor_id']) ?>
          </span>

          <!-- actions (hidden; slide-in on hover) -->
          <div class="actions">
            <a class="btn videos-btn" href="#" onclick="openVideos(this);return false;">Videos</a>
            <div class="size-group">
              <button class="btn sBtn" title="Resize" onclick="cycleSize(this);return false;">⇲</button>
            </div>
          </div>
        </div>
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
/* ====== HLS attach ====== */
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

/* ====== Fullscreen ====== */
function fsTile(ev, btn){
  ev.stopPropagation();
  const card = btn.closest('.cam');
  const elem = card.querySelector('.thumb');
  if (elem.requestFullscreen) elem.requestFullscreen();
  else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
}

/* ====== Open Videos ====== */
function openVideos(btn){
  const card  = btn.closest('.cam');
  const nvrId = card?.dataset?.nvrId || card.getAttribute('data-nvr-id');
  const mon   = card?.dataset?.mon   || card.getAttribute('data-mon');
  const qs    = new URLSearchParams({ nvr_id: nvrId, mon });
  window.open('/videos?'+qs.toString(), '_blank');
}

/* ====== Drag & Drop order (persist) ====== */
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

/* ====== Resize per tile (persist) ====== */
const SIZE_SEQ = [[1,1],[2,1],[2,2]]; // cycle 1x1 → 2x1 → 2x2
function loadSizes(){
  try { return JSON.parse(localStorage.getItem('sandya_nvr_tile_sizes')||'{}') } catch(e){ return {}; }
}
function saveSizes(s){ localStorage.setItem('sandya_nvr_tile_sizes', JSON.stringify(s)); }
function applySizes(){
  const sizes = loadSizes();
  document.querySelectorAll('.cam').forEach(c=>{
    const id = c.dataset.id;
    const s  = sizes[id];
    if (s && s.w && s.h) {
      c.style.setProperty('--w', s.w);
      c.style.setProperty('--h', s.h);
    }
  });
}
function cycleSize(btn){
  const card = btn.closest('.cam');
  const id   = card.dataset.id;
  const sizes= loadSizes();
  const curW = parseInt(getComputedStyle(card).getPropertyValue('--w')) || 1;
  const curH = parseInt(getComputedStyle(card).getPropertyValue('--h')) || 1;
  let idx = SIZE_SEQ.findIndex(([w,h])=> w===curW && h===curH);
  idx = (idx+1) % SIZE_SEQ.length;
  const [nw,nh] = SIZE_SEQ[idx];
  card.style.setProperty('--w', nw);
  card.style.setProperty('--h', nh);
  sizes[id] = {w:nw, h:nh};
  saveSizes(sizes);
}
applySizes();

/* ====== Per page persistence ====== */
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
/* ====== Grid responsive + variable span ====== */
#grid.grid{
  --row: 200px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  grid-auto-rows: var(--row);
  grid-auto-flow: dense;
  gap: 16px;
}
.cam { grid-column: span var(--w,1); grid-row: span var(--h,1); }
.cam.dragging { opacity:.6; transform:scale(.98); }

/* ====== Hidden actions (slide-in) ====== */
.thumb { position: relative; overflow:hidden; border-radius: 16px; }
.actions{
  position:absolute; left:0; right:0; bottom:0;
  display:flex; justify-content:center; align-items:center; gap:10px;
  padding:12px;
  transform: translateY(110%);
  opacity:0; transition: all .18s ease;
  background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.45) 60%, rgba(0,0,0,.65) 100%);
}
.thumb:hover .actions,
.thumb:active .actions { transform: translateY(0%); opacity:1; }
.videos-btn { background:#7c3aed; color:#fff; text-decoration:none; padding:10px 16px; border-radius:10px; font-weight:700; }
.size-group .sBtn{ background:#111827; color:#e5e7eb; padding:10px 12px; border-radius:10px; }
.fs-btn{
  position:absolute; right:8px; top:8px; z-index:3;
}

/* Label position */
.cam-label{ position:absolute; left:12px; top:10px; z-index:2 }

/* Mobile: jangan crop video, biar letterboxed */
@media (max-width: 768px){
  #grid.grid{ grid-template-columns: 1fr; grid-auto-rows: 220px; gap:12px; }
  .vid{ object-fit: contain !important; }
  .fs-btn{ right:6px; top:6px; }
}

/* (Sedikit lebih ringkas di layar super kecil) */
@media (max-width: 380px){
  #grid.grid{ grid-auto-rows: 200px; }
}
</style>
