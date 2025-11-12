<section class="flex flex-col justify-start">
  <p class="text-3xl font-bold text-white">Cameras</p>

  <!-- pilih NVR -->
  <form method="get" action="/cameras" class="flex flex-col justify-start gap-3 mt-10 mb-5 w-max">
    <p class="font-bold text-white">NVR URL</p>
    <select name="nvr_id" onchange="this.form.submit()" class="w-full bg-slate-800 p-2 rounded-md hover:cursor-pointer">
      <?php foreach ($nvrs as $n): ?>
        <option value="<?= (int)$n['id'] ?>" <?= ($nvrActive && $nvrActive['id']===$n['id'])?'selected':'' ?>>
          <?= esc($n['name']) ?> (<?= esc($n['base_url']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <p>Total Camera: <span class="font-bold"><?= count($streams['items']) ?></span></p>
  </form>

</section>

  <p class="font-bold text-white mb-2">Camera List</p>
  <?php if (!$nvrActive): ?>
    <p class="muted" style="text-align:center">Belum ada NVR aktif.</p>
  <?php else: ?>
    <?php if (!$streams['ok'] || empty($streams['items'])): ?>
      <p class="muted" style="text-align:center"><?= esc($streams['msg'] ?: 'Tidak ada monitor / Shinobi error.') ?></p>
    <?php else: ?>
      <div class="cam-list">
        <?php foreach ($streams['items'] as $m): ?>
          
          <?php $isAssignedSet = in_array($m['mid'], array_column($assignedBy, 'monitor_id')); ?>

          <div class="flex flex-col mb-5">
            <div class="flex flex-row gap-3 border-2 border-[#1f2937] p-2 rounded-tr-md rounded-tl-md">
              <p class="font-bold"><?= esc($m['mid']) ?></p>
              <p><?= esc($m['name']) ?></p>
            </div>
            <?php $found = array_values(array_filter($assignedBy, fn($r) => $r['monitor_id'] === $m['mid'])); ?>
            <?php if ($isAssignedSet): ?>
              <div class="bg-[#1f2937] p-2 rounded-br-md rounded-bl-md">
                <p class="font-semibold mb-1">Available at (dashboard name):</p>
                <div class="flex flex-row gap-2 items-center">
                  <?php foreach ($found as $f): ?>
                    <p class="bg-violet-400/50 rounded-full py-1 px-2 w-max"><?= $f['name'] ?></p>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

<style>
  .cam-row{display:grid;grid-template-columns: 1fr 2fr auto;gap:12px;align-items:center;padding:12px 14px;border:1px solid #1f2937;background:#0f1420;border-radius:12px;margin-bottom:10px}
  @media (max-width: 720px){
    .cam-row{grid-template-columns: 1fr;gap:8px}
    .cam-row .btn{width:100%}
  }
</style>

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
