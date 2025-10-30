<?php /* Views/dashboard/index.php */ ?>
<section class="page-head flex flex-col" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
  
  <div class="w-full flex flex-col gap-3 items-start">
    <p class="text-3xl text-white font-bold">Dashboard</p>
    <div class="flex flex-row gap-3">
      
      <?php foreach($dashAccess as $dash):?>
        <a href="/dashboard/<?= $dash['id'] ?>" class="text-white hover:cursor-pointer p-2 rounded-md <?= $curDashId == $dash['id'] ? 'bg-slate-400' : 'bg-slate-600' ?>"> <?= $dash['name']?> </a>
      <?php endforeach;?>
    </div>

    <?php if(session()->getFlashdata('message')): ?>
        <div class="alert alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('message') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
  </div>

  <div class="w-full flex items-start">
    <!-- Filter -->
    <form method="get" action="/dashboard/<?= $curDashId ?>" id="flt" class="flex flex-col gap-3">
      <div class="flex gap-3">
        <input type="text" name="q" value="<?= esc($q ?? '') ?>" placeholder="Cari alias/NVR/monitor..." style="min-width:240px" class="bg-slate-800 p-2 rounded-md">
        <input type="hidden" name="page" value="<?= (int)($page ?? 1) ?>" >
        <button class="btn rounded-md bg-blue-500 hover:bg-blue-400" type="submit">Apply</button>
        <a class="btn" href="/dashboard" style="background:#ef4444">Reset</a>
      </div>
      
      <div class="w-full flex flex-row items-center gap-3">
        <label for="per">Per page</label>
        <select name="per" id="per" class="bg-slate-800 rounded-md p-2">
          <?php foreach ([6, 12, 24, 46, 100] as $opt): ?>
            <option value="<?= $opt ?>" <?= (isset($per) && (int)$per === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
  
      <!-- Slideshow toggle + interval -->
      <button type="button" id="btnSlide" class="btn" style="background:#7c3aed">Slideshow Cameras</button>
      <select id="slideMsSel" title="Interval slideshow (detik)" style="background:#111827;border:1px solid #1f2937;color:#e5e7eb;border-radius:10px;padding:8px">
        <?php foreach ([5,10,15,30,60,120,300] as $s): ?>
          <option value="<?= $s ?>"><?= $s ?>s</option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</section>

<?php if (empty($tiles)): ?>
  <p style="color:#94a3b8">Belum ada kamera untuk ditampilkan.</p>
<?php else: ?>

  <div id="slideDiv" >
    <div class="grid-stack bg-slate-50">
      <?php foreach ($tiles as $t): ?>
        <div class="grid-stack-item size-to-content" 
          gs-x="<?= esc($t['size'])['x'] ?>"
          gs-y="<?= esc($t['size'])['y'] ?>" 
          gs-w="4" 
          gs-h="4"
          gs-size-to-content="3"
        >
          <div class="cam grid-stack-item-content" 
            data-id="<?= esc($t['id']) ?>"
            data-hls="<?= esc($t['hls']) ?>"
            data-alias="<?= esc($t['alias']) ?>"
            data-nvr-id="<?= $t['nvr_id'] ?? 0 ?>"
            data-mon="<?= esc($t['monitor_id']) ?>"
          >

            <div class="thumb">
              <video class="vid h-full w-full object-cover bg-black" muted playsinline autoplay></video>
  
              <!-- fullscreen -->
              <button class="btn ghost fs-btn" onclick="fsTile(event,this)" title="Fullscreen">⤢</button>
  
              <!-- label -->
              <span class="chip cam-label" title="<?= esc($t['alias']) ?>">
                <?= esc($t['nvr']) ?> / <?= esc($t['monitor_id']) ?>
              </span>
  
              <!-- actions (hidden; slide-in on hover/tap) -->
              <!-- <div class="actions">
                <a class="btn videos-btn" href="#" onclick="openVideos(this);return false;">Videos</a>
                <button class="btn sBtn" title="Resize" onclick="cycleSize(this);return false;">⇲</button>
              </div> -->
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Slideshow controls (muncul hanya saat slideshow ON) -->
  <div id="slideCtrls" style="display:none;gap:8px;justify-content:center;margin:16px 0">
    <button class="btn ghost" id="btnPrev">Previous</button>
    <button class="btn ghost" id="btnNext">Next</button>
  </div>
  
  <!-- Pagination -->
  <div id="pager" class="pagination" style="display:flex;gap:6px;justify-content:center;margin:16px 0">
    <?php
      $curr = $page ?? 1;
      $max  = $pages ?? 1;
      $perQ = $per ?? 10;
      $qStr = ($q ?? '') !== '' ? '&q=' . urlencode($q) : '';
      $window = 2; $start = max(1, $curr-$window); $end = min($max, $curr+$window);
    ?>

    <?php if ($curr > 1): ?>
      <a class="btn ghost" href="<?= '/dashboard/' . $curDashId . '?page='. $curr - 1 .'&per='.$perQ.$qStr ?>">&laquo; Prev</a>
    <?php else: ?>
      <span class="btn ghost" style="opacity:.5;pointer-events:none">&laquo; Prev</span>
    <?php endif; ?>

    <?php if ($start > 1): ?>
      <a class="btn ghost" href="<?= '/dashboard/' . $curDashId . '?page='. 1 .'&per='.$perQ.$qStr ?>">1</a>
      <?php if ($start > 2): ?><span class="btn ghost" style="pointer-events:none">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i=$start; $i<=$end; $i++): ?>
      <?php if ($i === $curr): ?>
        <span class="btn" style="pointer-events:none"><?= $i ?></span>
      <?php else: ?>
        <a class="btn ghost" href="<?= '/dashboard/' . $curDashId . '?page='. $i .'&per='.$perQ.$qStr ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $max): ?>
      <?php if ($end < $max-1): ?><span class="btn ghost" style="pointer-events:none">…</span><?php endif; ?>
      <a class="btn ghost" href="<?= '/dashboard/' . $curDashId . '?page='. $max .'&per='.$perQ.$qStr ?>"><?= $max ?></a>
    <?php endif; ?>

    <?php if ($curr < $max): ?>
      <a class="btn ghost" href="<?= '/dashboard/' . $curDashId . '?page='. $curr + 1 .'&per='.$perQ.$qStr ?>">Next &raquo;</a>
    <?php else: ?>
      <span class="btn ghost" style="opacity:.5;pointer-events:none">Next &raquo;</span>
    <?php endif; ?>
  </div>

  <!-- Slideshow controls (muncul hanya saat slideshow ON) -->
  <div id="slideCtrls" style="display:none;gap:8px;justify-content:center;margin:16px 0">
    <button class="btn ghost" id="btnPrev">Previous</button>
    <button class="btn ghost" id="btnNext">Next</button>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.8/dist/hls.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if(session()->getFlashdata('message')): ?>
  <script>
      $(document).ready(function() {
          let message = "<?= session()->getFlashdata('message') ?>";
          alert(message);
      });
  </script>
<?php endif; ?>

<script>
  
$(document).ready(() => {
  $('.mainGrid .itemGrid').each(function(index) {
    console.log('Grid #' + index, $(this).text());
  });

  const perPage = <?= $per ?>;
  console.log(perPage);
})

/* ====== Helpers: cookies ====== */
function setCookie(name, value, days=30) {
  const d = new Date(); d.setTime(d.getTime() + (days*24*60*60*1000));
  document.cookie = name + "=" + value + ";expires=" + d.toUTCString() + ";path=/";
}

function getCookie(name) {
  const n = name + "="; const ca = document.cookie.split(';');
  for (let i=0;i<ca.length;i++){ let c=ca[i].trim(); if (c.indexOf(n)==0) return c.substring(n.length,c.length); }
  return "";
}

/* ====== HLS attach (PERSIS yang jalan) ====== */
function attachHls(videoEl, url){
  if (!videoEl) return null;
  videoEl.style.width = '100%';
  videoEl.style.height = '100%';
  videoEl.style.objectFit = 'cover';
  videoEl.style.background = '#000';
  if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
    videoEl.src = url; videoEl.muted = true; videoEl.play().catch(()=>{});
    return {type:'native'};
  } else if (window.Hls && window.Hls.isSupported()) {
    const hls = new Hls({liveDurationInfinity:true});
    hls.loadSource(url); hls.attachMedia(videoEl);
    videoEl.muted = true; videoEl.play().catch(()=>{});
    return {type:'hls', hls};
  }
  return null;
}

/* attach semua video sekali di awal */
(function initVideos(){
  document.querySelectorAll('.cam').forEach(card => {
    card._hlsObj = attachHls(card.querySelector('.vid'), card.dataset.hls);
  });
})();

/* ====== Fullscreen ====== */
function fsTile(ev, btn){
  ev.stopPropagation();
  const elem = btn.closest('.cam').querySelector('.thumb');
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

/* ====== Drag order persist ====== */
const grid = document.getElementById('grid');
let dragSrc = null;
// grid?.addEventListener('dragstart', (e) => {
//   const card = e.target.closest('.cam'); if (!card) return;
//   dragSrc = card; e.dataTransfer.effectAllowed = 'move';
//   e.dataTransfer.setData('text/plain', card.dataset.id);
//   card.classList.add('dragging');
// });
// grid?.addEventListener('dragover', (e) => {
//   e.preventDefault();
//   const over = e.target.closest('.cam'); if (!over || over === dragSrc) return;
//   const cards = [...grid.querySelectorAll('.cam')];
//   const srcIndex  = cards.indexOf(dragSrc);
//   const overIndex = cards.indexOf(over);
//   if (srcIndex < overIndex) grid.insertBefore(dragSrc, over.nextSibling);
//   else grid.insertBefore(dragSrc, over);
// });
// grid?.addEventListener('drop', (e) => { e.preventDefault(); saveOrder(); });
// grid?.addEventListener('dragend', (e) => {
//   const card = e.target.closest('.cam');
//   if (card) card.classList.remove('dragging');
//   saveOrder();
// });
function saveOrder(){
  if (!grid) return;
  const ids = [...grid.querySelectorAll('.cam')].map(c => c.dataset.id);
  localStorage.setItem('sandya_nvr_dash_order', JSON.stringify(ids));
}
(function applySavedOrder(){
  try{
    const ids = JSON.parse(localStorage.getItem('sandya_nvr_dash_order') || '[]');
    if (!Array.isArray(ids) || !ids.length || !grid) return;
    const map = {};
    [...grid.querySelectorAll('.cam')].forEach(c => map[c.dataset.id] = c);
    ids.forEach(id => { if (map[id]) grid.appendChild(map[id]); });
  }catch(e){}
})();

/* ====== Resize per-tile (persist) ====== */
// const SIZE_SEQ = [[1,1],[2,1],[2,2]]; // cycle
// function loadSizes(){ try { return JSON.parse(localStorage.getItem('sandya_nvr_tile_sizes')||'{}') } catch(e){ return {}; } }
// function saveSizes(s){ localStorage.setItem('sandya_nvr_tile_sizes', JSON.stringify(s)); }
// function applySizes(){
//   const sizes = loadSizes();
//   document.querySelectorAll('.cam').forEach(c=>{
//     const id = c.dataset.id;
//     const s  = sizes[id];
//     if (s && s.w && s.h) {
//       c.style.setProperty('--w', s.w);
//       c.style.setProperty('--h', s.h);
//     }
//   });
// }

// function cycleSize(btn){
//   const card = btn.closest('.cam');
//   const id   = card.dataset.id;
//   const sizes= loadSizes();
//   const curW = parseInt(getComputedStyle(card).getPropertyValue('--w')) || 1;
//   const curH = parseInt(getComputedStyle(card).getPropertyValue('--h')) || 1;
//   let idx = SIZE_SEQ.findIndex(([w,h])=> w===curW && h===curH);
//   idx = (idx+1) % SIZE_SEQ.length;
//   const [nw,nh] = SIZE_SEQ[idx];
//   card.style.setProperty('--w', nw);
//   card.style.setProperty('--h', nh);
//   sizes[id] = {w:nw, h:nh};
//   saveSizes(sizes);
// }

// applySizes();

/* ====== Per page persist ====== */
const perSel = document.getElementById('per');

perSel?.addEventListener('change', () => {
  localStorage.setItem('sandya_nvr_perpage', perSel.value);
  document.getElementById('flt').submit();
});

(function applySavedPerPage(){
  try{
    const hasPerInUrl = new URLSearchParams(location.search).has('per');
    const saved = localStorage.getItem('sandya_nvr_perpage');
    if (!hasPerInUrl && saved && ['6','12','24','100'].includes(saved) && perSel.value !== saved){
      perSel.value = saved;
      document.getElementById('flt').submit();
    }
  }catch(e){}
})();

/* ====== Slideshow dengan interval pilih + FIX start tanpa Apply ====== */
const SLIDE_SIZE = 6;
const btnSlide   = document.getElementById('btnSlide');
const ctrls      = document.getElementById('slideCtrls');
const pager      = document.getElementById('pager');
const slideMsSel = document.getElementById('slideMsSel');
const slideDiv   = document.getElementById('slideDiv');

let ROTATE_MS = parseInt(getCookie('sandya_slide_ms') || localStorage.getItem('sandya_slide_ms') || '8000') || 8000;
if (slideMsSel) {
  const sec = Math.max(1, Math.round(ROTATE_MS/1000));
  let matched = false;
  [...slideMsSel.options].forEach(o => {
    if (parseInt(o.value) === sec) { o.selected = true; matched = true; }
  });
  if (!matched) slideMsSel.value = String(sec);
}

let slideOn    = false;
let slideIndex = parseInt(getCookie('sandya_slide_index') || localStorage.getItem('sandya_slide_index') || '0') || 0;
let slideTimer = null;

function cams(){ return [...(grid?.querySelectorAll('.cam')||[])]; }

function showSlice() {
  // if (!grid) return;
  const all = cams();
  // if (!all.length) return;

  const perPage = <?= $per ?>;
  // console.log('perPage ' + perPage);

  const pages = Math.max(1, Math.ceil(perPage / SLIDE_SIZE));
  // console.log(pages);
  slideIndex = ((slideIndex % pages) + pages) % pages; // wrap
  const start = slideIndex * SLIDE_SIZE;
  const end   = start + SLIDE_SIZE;

  all.forEach((c, idx) => { c.style.display = (idx>=start && idx<end) ? '' : 'none'; });

  // hide server pager, show our controls
  if (pager) pager.style.display = 'none';
  if (ctrls) ctrls.style.display = 'flex';

  // persist
  setCookie('sandya_slideshow','1');
  setCookie('sandya_slide_index', String(slideIndex));
  localStorage.setItem('sandya_slideshow','1');
  localStorage.setItem('sandya_slide_index', String(slideIndex));
}

function clearSlice() {
  if (!grid) return;
  cams().forEach(c => c.style.display = '');
  if (pager) pager.style.display = 'flex';
  if (ctrls) ctrls.style.display = 'none';
  setCookie('sandya_slideshow','0');
  localStorage.setItem('sandya_slideshow','0');
}

function updateBtn() {
  if (!btnSlide) return;
  btnSlide.textContent = slideOn ? 'Stop Slideshow' : 'Slideshow Cameras';
  btnSlide.style.background = slideOn ? '#ef4444' : '#7c3aed';
}

function startAuto() {
  stopAuto();
  slideTimer = setInterval(()=>{ slideIndex++; showSlice(); }, ROTATE_MS);
}
function stopAuto() {
  if (slideTimer) { clearInterval(slideTimer); slideTimer = null; }
}

/* CLICK: langsung jalan tanpa Apply */
btnSlide?.addEventListener('click', () => {

  // let totalShow = <?= $per ?> / 2;
  // $('.mainGrid .itemGrid').each( function(index) {
  //   if (index < totalShow ) {
  //     // $(this).addClass('hidden');
  //   }
  // });

  slideOn = !slideOn;
  
  if (slideOn) {
    slideIndex = 0;
    // pastikan DOM siap & elemen kamera sudah ada
    console.log('start slideshow');
    requestAnimationFrame(()=>{ showSlice(); startAuto(); });
  } else {
    stopAuto(); clearSlice();
  }

  // if (slideDiv.requestFullscreen) {
  //   slideDiv.requestFullscreen();
  // } else if (slideDiv.webkitRequestFullscreen) { // Safari
  //   slideDiv.webkitRequestFullscreen();
  // } else if (slideDiv.msRequestFullscreen) { // IE11
  //   slideDiv.msRequestFullscreen();
  // }

  updateBtn();
});

document.getElementById('btnPrev')?.addEventListener('click', () => { slideIndex--; showSlice(); });
document.getElementById('btnNext')?.addEventListener('click', () => { slideIndex++; showSlice(); });

/* Ubah interval → restart timer kalau sedang ON */
slideMsSel?.addEventListener('change', ()=>{
  const sec = parseInt(slideMsSel.value) || 8;
  ROTATE_MS = sec * 1000;
  setCookie('sandya_slide_ms', String(ROTATE_MS));
  localStorage.setItem('sandya_slide_ms', String(ROTATE_MS));
  if (slideOn) { startAuto(); }
});

/* INIT: tanpa perlu Apply */
(function initSlide(){
  // jalankan setelah DOM render + sedikit delay supaya .cam sudah lengkap
  const boot = () => {
    updateBtn();
    if (slideOn) {
      setTimeout(() => { showSlice(); startAuto(); }, 150);
    }
  };
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(boot, 0);
  } else {
    document.addEventListener('DOMContentLoaded', boot, {once:true});
  }
})();
</script>

<style>
/* ====== Grid & tile ====== */
/* #grid.grid{
  --row: 200px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  grid-auto-rows: var(--row);
  grid-auto-flow: dense;
  gap: 16px;
} */
/* .cam{
  grid-column: span var(--w,1);
  grid-row: span var(--h,1);
  min-height: calc(var(--h,1) * var(--row));
  height: 100%;
  display: flex;
  flex-direction: column;
}
.cam.dragging { opacity:.6; transform:scale(.98); } */

.thumb{
  position: relative;
  overflow:hidden;
  border-radius: 16px;
  flex: 1 1 auto;
  height: 100%;
  display: flex;
}
/* .vid{ width:100%; height:100%; object-fit:cover; background:#000; }   */

/* .actions{
  position:absolute; left:0; right:0; bottom:0;
  display:flex; justify-content:center; align-items:center; gap:10px;
  padding:12px;
  transform: translateY(110%);
  opacity:0; transition: all .18s ease;
  background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.45) 60%, rgba(0,0,0,.65) 100%);
} */
.thumb:hover .actions,
.thumb:active .actions { transform: translateY(0%); opacity:1; }
.videos-btn { background:#7c3aed; color:#fff; text-decoration:none; padding:10px 16px; border-radius:10px; font-weight:700; }
.sBtn{ background:#111827; color:#e5e7eb; padding:10px 12px; border-radius:10px; }
.fs-btn{ position:absolute; right:8px; top:8px; z-index:3; }
.cam-label{ position:absolute; left:12px; top:10px; z-index:2 }

@media (max-width: 768px){
  #grid.grid{ grid-template-columns: 1fr; grid-auto-rows: 220px; gap:12px; }
  .vid{ object-fit: contain; }
  .fs-btn{ right:6px; top:6px; }
}
@media (max-width: 380px){
  #grid.grid{ grid-auto-rows: 200px; }
}
</style>
