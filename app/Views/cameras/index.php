<section class="page-head" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
  <h1 style="margin-right:auto">Cameras</h1>

  <!-- pilih NVR -->
  <form method="get" action="/cameras" style="display:flex;gap:8px;align-items:center">
    <select name="nvr_id" onchange="this.form.submit()">
      <?php foreach ($nvrs as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= ($nvrActive && $nvrActive['id']===$n['id'])?'selected':'' ?>>
          <?= esc($n['name']) ?> (<?= esc($n['base_url']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <!-- pilih Dashboard untuk assign/mappings -->
  <div style="display:flex;gap:8px;align-items:center">
    <select id="selDash" style="min-width:220px">
      <?php foreach ($dashboards as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= esc($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <a class="btn ghost" href="#" id="btnMappings">Mappings</a>
  </div>
</section>

<?php if (!$nvrActive): ?>
  <p class="muted">Belum ada NVR aktif.</p>
<?php else: ?>
  <?php if (!$streams['ok'] || empty($streams['items'])): ?>
    <p class="muted"><?= esc($streams['msg'] ?: 'Tidak ada monitor / Shinobi error.') ?></p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($streams['items'] as $m): ?>
        <article class="card">
          <div class="thumb">
            <span class="chip"><?= esc(($nvrActive['name'] ?? 'NVR').' / '.$m['mid']) ?></span>
          </div>
          <div class="meta">
            <div class="title"><?= esc($m['name']) ?></div>
            <div class="actions">
              <button class="btn ghost assign-btn"
                      data-mid="<?= esc($m['mid']) ?>"
                      data-nvr="<?= (int)$nvrActive['id'] ?>">
                Assign to Dashboard
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
const selDash = document.getElementById('selDash');
document.getElementById('btnMappings').addEventListener('click', (e)=>{
  e.preventDefault();
  const id = selDash.value || '';
  if (!id) return;
  location.href = '/cameras/mappings?dashboard_id=' + encodeURIComponent(id);
});

// assign tanpa alias + auto-disable tombol setelah sukses
document.querySelectorAll('.assign-btn').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const dashId = selDash.value;
    if (!dashId) { alert('Pilih dashboard dulu.'); return; }
    const fd = new FormData();
    fd.append('dashboard_id', dashId);
    fd.append('nvr_id', btn.dataset.nvr);
    fd.append('monitor_id', btn.dataset.mid);

    btn.disabled = true; btn.textContent = 'Assigning...';
    const r = await fetch('/cameras/assign', {method:'POST', body:fd});
    const j = await r.json().catch(()=>({ok:false,msg:'Bad response'}));
    if (j.ok) {
      btn.textContent = 'Assigned';
      btn.classList.remove('ghost');
      btn.style.background = '#ef4444';
      btn.style.color = '#fff';
    } else {
      btn.disabled = false; btn.textContent = 'Assign to Dashboard';
      alert('Gagal: ' + (j.msg||''));
    }
  });
});
</script>
