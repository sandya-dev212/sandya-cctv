<section class="page-head" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex:1 1 100%">
  <h1 style="margin-right:auto">Dashboard</h1>

  <!-- Filter -->
  <form method="get" action="/dashboard" id="flt" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;max-width:100%">
    <input type="text" name="q" value="<?= esc($q ?? '') ?>" placeholder="Cari alias/NVR/monitor..."
           style="min-width:220px;flex:1 1 200px">

    <label for="per">Per page</label>
    <select name="per" id="per">
      <?php foreach ([5,10,25,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= (isset($per) && (int)$per === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <input type="hidden" name="page" value="<?= (int)($page ?? 1) ?>">
    <button class="btn ghost" type="submit">Apply</button>
    <a class="btn" href="/dashboard" style="background:#ef4444">Reset</a>

    <!-- Slideshow toggle -->
    <button type="button" id="btnSlide" class="btn" style="background:#7c3aed">Slideshow Cameras</button>
  </form>
</section>

<?php if (empty($tiles)): ?>
  <p style="color:#94a3b8">Belum ada kamera untuk ditampilkan.</p>
<?php else: ?>
  <section id="grid" class="grid">
    <?php foreach ($tiles as $t): ?>
      <article class="card cam" draggable="true"
               data-id="<?= esc($t['id']) ?>"
               data-hls="<?= esc($t['hls']) ?>"
               data-alias="<?= esc($t['alias']) ?>"
               data-nvr-id="<?= (int)($t['nvr_id'] ?? 0) ?>"
               data-mon="<?= esc($t['monitor_id']) ?>"
               style="--w:1;--h:1">
        <div class="thumb">
          <video class="vid" muted playsinline autoplay></video>

          <!-- fullscreen -->
          <button class="btn ghost fs-btn" onclick="fsTile(event,this)" title="Fullscreen">⤢</button>

          <!-- label -->
          <span class="chip cam-label" title="<?= esc($t['alias']) ?>">
            <?= esc($t['nvr']) ?> / <?= esc($t['monitor_id']) ?>
          </span>

          <!-- actions -->
          <div class="actions">
            <a class="btn videos-btn" href="#" onclick="openVideos(this);return false;">Videos</a>
            <button class="btn sBtn" title="Resize" onclick="cycleSize(this);return false;">⇲</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <!-- Pagination -->
  <div id="pager" class="pagination" style="display:flex;gap:6px;justify-content:center;margin:16px 0;flex-wrap:wrap">
    <?php
      $curr = (int)($page ?? 1);
      $max  = (int)($pages ?? 1);
      $perQ = (int)($per ?? 5);
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

  <!-- Slideshow controls -->
  <div id="slideCtrls" style="display:none;gap:8px;justify-content:center;margin:16px 0">
    <button class="btn ghost" id="btnPrev">Previous</button>
    <button class="btn ghost" id="btnNext">Next</button>
  </div>
<?php endif; ?>

<script>
/* Per page persist fix — tambahin opsi 5 dan default 5 */
const perSel = document.getElementById('per');
perSel?.addEventListener('change', () => {
  localStorage.setItem('sandya_nvr_perpage', perSel.value);
  document.getElementById('flt').submit();
});
(function applySavedPerPage(){
  try {
    const hasPerInUrl = new URLSearchParams(location.search).has('per');
    const saved = localStorage.getItem('sandya_nvr_perpage');
    const allowed = ['5','10','25','50','100'];
    if (!hasPerInUrl && saved && allowed.includes(saved) && perSel.value !== saved){
      perSel.value = saved;
      document.getElementById('flt').submit();
    } else if (!saved) {
      // default pertama kali = 5
      perSel.value = '5';
      localStorage.setItem('sandya_nvr_perpage', '5');
    }
  }catch(e){}
})();
</script>

<style>
/* tambahan fix biar filter wrap rapi */
#flt input[type="text"] { max-width: 300px; flex: 1 1 240px; }
#flt select, #flt button, #flt a { flex: 0 0 auto; }
#flt { flex-wrap: wrap; max-width: 100%; }
</style>
