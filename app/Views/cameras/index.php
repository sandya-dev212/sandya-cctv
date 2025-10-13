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

    <!-- keep selected dashboard in query supaya status Assigned kebaca saat refresh -->
    <input type="hidden" name="dashboard_id" id="hidDash" value="<?= (int)($dashboardActiveId ?? 0) ?>">
  </form>

  <!-- pilih Dashboard untuk assign/mappings -->
  <div style="display:flex;gap:8px;align-items:center">
    <select id="selDash" style="min-width:220px" onchange="document.getElementById('hidDash').value=this.value; location.href='<?= '/cameras?nvr_id='.(int)($nvrActive['id'] ?? 0) ?>&dashboard_id='+this.value;">
      <?php foreach ($dashboards as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= ((int)$dashboardActiveId === (int)$d['id'])?'selected':'' ?>><?= esc($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <a class="btn ghost" href="/cameras/mappings?dashboard_id=<?= (int)$dashboardActiveId ?>">Mappings</a>
  </div>
</section>

<?php if (!$nvrActive): ?>
  <p class="muted">Belum ada NVR aktif.</p>
<?php else: ?>
  <?php if (!$streams['ok'] || empty($streams['items'])): ?>
    <p class="muted"><?= esc($streams['msg'] ?: 'Tidak ada monitor / Shinobi error.') ?></p>
  <?php else: ?>

    <table class="table" id="tblCam">
      <thead>
        <tr>
          <th style="width:260px">NVR / Monitor ID</th>
          <th>Camera Name</th>
          <th style="width:180px">Status</th>
          <th style="width:220px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($streams['items'] as $m): 
            $mid = (string)$m['mid'];
            $assigned = isset($assignedSet[$mid]);
      ?>
        <tr>
          <td><span class="chip"><?= esc(($nvrActive['name'] ?? 'NVR')) ?></span> — <b><?= esc($mid) ?></b></td>
          <td><?= esc($m['name']) ?></td>
          <td>
            <?php if ($assigned): ?>
              <span class="chip" style="background:#064e3b">Assigned</span>
            <?php else: ?>
              <span class="chip" style="background:#111827">Not Assigned</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($assigned): ?>
              <button class="btn" style="background:#ef4444;pointer-events:none;opacity:.8">Assigned</button>
            <?php else: ?>
              <button class="btn ghost assign-btn"
                      data-mid="<?= esc($mid) ?>"
                      data-nvr="<?= (int)$nvrActive['id'] ?>">
                Assign to Dashboard
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>
<?php endif; ?>

<script>
const selDash = document.getElementById('selDash');

// assign tanpa alias + update baris jadi Assigned
document.querySelectorAll('.assign-btn').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const dashId = selDash.value;
    if (!dashId) { alert('Pilih dashboard dulu.'); return; }
    const tr = btn.closest('tr');
    const fd = new FormData();
    fd.append('dashboard_id', dashId);
    fd.append('nvr_id', btn.dataset.nvr);
    fd.append('monitor_id', btn.dataset.mid);

    btn.disabled = true; btn.textContent = 'Assigning...';
    const r = await fetch('/cameras/assign', {method:'POST', body:fd});
    const j = await r.json().catch(()=>({ok:false,msg:'Bad response'}));
    if (j.ok) {
      btn.outerHTML = '<button class="btn" style="background:#ef4444;pointer-events:none;opacity:.8">Assigned</button>';
      const statusCell = tr.querySelector('td:nth-child(3)');
      statusCell.innerHTML = '<span class="chip" style="background:#064e3b">Assigned</span>';
    } else {
      btn.disabled = false; btn.textContent = 'Assign to Dashboard';
      alert('Gagal: ' + (j.msg||''));
    }
  });
});
</script>
