<section class="page-head">
  <h1>Edit Dashboard</h1>
</section>

<form method="post" action="/dashboards/<?= (int)$dash['id'] ?>/update" class="card" style="max-width:720px">
  <div style="display:flex;gap:12px;align-items:center">
    <label style="min-width:140px">Nama Dashboard</label>
    <input name="name" value="<?= esc($dash['name']) ?>" required>
  </div>

  <div style="margin-top:16px;display:flex;gap:12px;align-items:flex-start">
    <label style="min-width:140px">Assign ke Users (role USER)</label>
    <select name="user_ids[]" multiple size="8" style="min-width:360px">
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], $selectedIds, true) ? 'selected' : '' ?>>
          <?= esc($u['username']) . ' — ' . esc($u['full_name'] ?? $u['username']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div style="margin-top:16px;display:flex;gap:8px">
    <a class="btn ghost" href="/dashboards">Cancel</a>
    <button class="btn" type="submit" style="background:#22c55e">Save</button>
  </div>
</form>
