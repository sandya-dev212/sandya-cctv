<?php /* app/Views/cameras/mappings.php */ ?>
<section class="page-head" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
  <h1 style="margin-right:auto">Camera Mappings</h1>

  <div style="display:flex;gap:8px;align-items:center">
    <label for="dashboard_id">Dashboard</label>
    <select id="dashboard_id" name="dashboard_id">
      <?php foreach ($dashboards as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= ((int)$dashboardId===(int)$d['id'])?'selected':'' ?>>
          <?= esc($d['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button id="btnOpen" class="btn ghost" type="button">Open</button>
  </div>
</section>

<?php if (!$dashboardId): ?>
  <p class="muted">Belum ada dashboard. Buat dulu di menu <b>Dashboards</b>.</p>
<?php else: ?>
  <?php if (empty($rows)): ?>
    <p class="muted">Belum ada mapping.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:90px">ID</th>
          <th>Alias</th>
          <th style="width:140px">Sort</th>
          <th style="width:160px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-id="<?= (int)$r['id'] ?>">
            <td><?= esc($r['monitor_id']) ?></td>
            <td><input class="inp-alias" value="<?= esc($r['alias'] ?? '') ?>" placeholder="alias (opsional)"></td>
            <td><input class="inp-sort" type="number" value="<?= (int)$r['sort_order'] ?>"></td>
            <td style="display:flex;gap:6px">
              <button class="btn ghost btn-save">Save</button>
              <button class="btn" style="background:#ef4444" onclick="return delRow(this)">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

<script>
// Tombol Open: pastiin redirect dengan query dashboard_id (lebih tahan terhadap base_url/index.php)
document.getElementById('btnOpen')?.addEventListener('click', ()=>{
  const id = document.getElementById('dashboard_id')?.value || '';
  if (!id) return;
  location.href = '/cameras/mappings?dashboard_id=' + encodeURIComponent(id);
});

// Save masing-masing baris
document.querySelectorAll('.btn-save').forEach(btn=>{
  btn.addEventListener('click', async (e)=>{
    e.preventDefault();
    const tr    = e.target.closest('tr');
    const id    = tr.dataset.id;
    const alias = tr.querySelector('.inp-alias').value;
    const sort  = tr.querySelector('.inp-sort').value;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('alias', alias);
    fd.append('sort_order', sort);

    const r = await fetch('/cameras/mappings/update', { method:'POST', body: fd });
    const j = await r.json().catch(()=>({ ok:false }));
    if (j.ok) {
      e.target.textContent = 'Saved';
      setTimeout(()=> e.target.textContent = 'Save', 800);
    } else {
      alert('Gagal menyimpan');
    }
  });
});

// Hapus baris mapping
async function delRow(btn){
  if (!confirm('Hapus mapping ini?')) return false;
  const tr = btn.closest('tr');
  const fd = new FormData();
  fd.append('id', tr.dataset.id);
  const r = await fetch('/cameras/mappings/delete', { method:'POST', body: fd });
  const j = await r.json().catch(()=>({ ok:false }));
  if (j.ok) tr.remove(); else alert('Gagal hapus');
  return false;
}
</script>
