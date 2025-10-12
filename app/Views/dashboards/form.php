<?php
  $isEdit = !empty($data['id']);
  $action = $isEdit
    ? '/dashboards/' . (int)$data['id'] . '/update'   // POST update
    : '/dashboards/store';                            // POST create
?>
<section class="page-head" style="display:flex;align-items:center;gap:12px">
  <h1 style="margin-right:auto"><?= $isEdit ? 'Edit Dashboard' : 'Add Dashboard' ?></h1>
  <a class="btn ghost" href="/dashboards">Cancel</a>
</section>

<form method="post" action="<?= esc($action) ?>" style="max-width:720px">
  <label for="name">Nama Dashboard</label>
  <input id="name" name="name" value="<?= esc($data['name'] ?? '') ?>" required>

  <label for="user_ids" style="margin-top:12px">Assign ke Users (role USER)</label>
  <select id="user_ids" name="user_ids[]" multiple size="8" style="width:100%">
    <?php foreach ($users as $u): ?>
      <?php $sel = in_array((int)$u['id'], $selected ?? [], true) ? 'selected' : ''; ?>
      <option value="<?= (int)$u['id'] ?>" <?= $sel ?>>
        <?= esc($u['username']) ?> — <?= esc($u['full_name'] ?? '') ?>
      </option>
    <?php endforeach; ?>
  </select>

  <p class="muted" style="margin:6px 0 16px">Gunakan Ctrl/Cmd untuk multi-select.</p>

  <button class="btn" type="submit">Save</button>
</form>
