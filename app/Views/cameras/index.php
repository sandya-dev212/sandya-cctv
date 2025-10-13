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

<?php
// buat set ID mapping yg sudah assigned untuk dashboard terpilih
$assigned = [];
if (!empty($streams['items']) && !empty($dashboards)) {
  $db = db_connect();
  $dashId = (int)($dashboards[0]['id'] ?? 0);
  $q = $db->table('dashboard_monitors')
          ->select('monitor_id')
          ->where('dashboard_id', $dashId)
          ->where('nvr_id', (int)($nvrActive['id'] ?? 0))
          ->get()->getResultArray();
  foreach ($q as $r) $assigned[$r['monitor_id']] = true;
}
?>

<?php if (!$nvrActive): ?>
  <p class="muted" style="text-align:center">Belum ada NVR aktif.</p>
<?php else: ?>
  <?php if (!$streams['ok'] || empty($streams['items'])): ?>
    <p class="muted" style="text-align:center"><?= esc($streams['msg'] ?: 'Tidak ada monitor / Shinobi error.') ?></p>
  <?php else: ?>
    <style>
      .cam-list{max-width:1100px;margin:0 auto}
      .cam-row{display:grid;grid-template-columns: 1fr 2fr auto;gap:12px;align-items:center;padding:12px 14px;border:1px solid #1f2937;background:#0f1420;border-radius:12px;margin-bottom:10px}
      .cam-id{font-weight:700}
      .cam-name{color:#cbd5e1}
      .cam-row .btn{min-width:170px}
      @media (max-width: 720px){
        .cam-row{grid-template-columns: 1fr;gap:8px}
        .cam-row .btn{width:100%}
      }
    </style>
    <div class="cam-list">
      <?php foreach ($streams['items'] as $m): ?>
        <?php $isAssigned = !empty($assigned[$m['mid']]); ?>
        <div class="cam-row">
          <div class="cam-id">— <?= esc($m['mid']) ?></div>
          <div class="cam-name"><?= esc($m['name']) ?></div>
          <div>
            <button
              class="btn ghost assign-btn"
              data-mid="<?= esc($m['mid']) ?>"
              data-nvr="<?= (int)$nvrActive['id'] ?>"
              <?= $isAssigned ? 'disabled' : '' ?>
              style="<?= $isAssigned ? 'background:#ef4444;color:#fff' : '' ?>"
            ><?= $isAssigned ? 'Assigned' : 'Assign to Dashboard' ?></button>
          </div>
        </div>
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
    if (btn.disabled) return;

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
