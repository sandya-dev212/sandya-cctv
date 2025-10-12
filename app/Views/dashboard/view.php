<section class="page-head">
  <h1><?= esc($dash['name']) ?></h1>
</section>

<section class="grid">
  <?php foreach ($cards as $c): ?>
    <article class="card">
      <div class="thumb">
        <img src="<?= esc($c['snapshot']) ?>" alt="<?= esc($c['alias']) ?>" onerror="this.src='/assets/no-thumb.png'">
        <span class="chip"><?= esc($c['nvr_name']) ?> / <?= esc($c['monitor_id']) ?></span>
      </div>
      <div class="meta">
        <div class="title"><?= esc($c['alias']) ?></div>
        <div class="actions">
          <a class="btn" href="#" onclick="openHls('<?= esc($c['hls']) ?>');return false;">Stream</a>
          <button class="btn ghost" onclick="removeMap(<?= (int)$c['dashboard_monitor_id'] ?>)">Remove</button>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<script>
function openHls(url){
  showSpinner();
  setTimeout(()=>{
    hideSpinner();
    openModal(`
      <div style="padding:16px">
        <h3 style="margin-top:0">Live Stream</h3>
        <video id="vid" controls autoplay style="width:100%;max-height:70vh;border-radius:12px;border:1px solid #1f2937;background:#000"></video>
        <p style="color:#94a3b8;margin-top:8px">URL: ${url}</p>
      </div>
    `);
    // load HLS.js dinamis
    const s=document.createElement('script');
    s.src="https://cdn.jsdelivr.net/npm/hls.js@latest";
    s.onload=()=>{
      const video=document.getElementById('vid');
      if (video.canPlayType('application/vnd.apple.mpegURL')) {
        video.src=url;
      } else if (window.Hls && window.Hls.isSupported()) {
        const hls=new Hls();
        hls.loadSource(url);
        hls.attachMedia(video);
      } else {
        video.outerHTML = '<div style="color:#fecaca;background:#7f1d1d;padding:8px 10px;border-radius:8px">Browser tidak mendukung HLS.</div>';
      }
    };
    document.body.appendChild(s);
  },350);
}

function removeMap(id){
  if(!confirm('Hapus dari dashboard?')) return;
  const fd=new FormData(); fd.append('dashboard_monitor_id', id);
  fetch('/dashboards/remove', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(j=>{ if(j.ok) location.reload(); else alert('Gagal'); })
    .catch(()=> alert('Network error'));
}
</script>
