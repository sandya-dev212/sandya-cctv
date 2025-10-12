<?php
$isEdit = ($mode ?? '') === 'edit';
$auth   = $isEdit ? ($item['auth_source'] ?: 'local') : 'local';
$action = $isEdit ? '/users/'.(int)$item['id'].'/update' : '/users/store';
?>
<div class="card" style="max-width:720px;margin:24px auto">
  <h2><?= $isEdit ? 'Edit User' : 'Add Local User' ?></h2>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= $action ?>">
    <?php if (!$isEdit): ?>
      <label>Username</label>
      <input name="username" required>
    <?php else: ?>
      <label>Username</label>
      <input value="<?= esc($item['username']) ?>" disabled>
    <?php endif; ?>

    <label>Auth Source</label>
    <input value="<?= strtoupper($auth) ?>" disabled>

    <?php if ($auth === 'local'): ?>
      <label>Full Name</label>
      <input name="full_name" value="<?= esc($item['full_name'] ?? '') ?>">

      <label>Email</label>
      <input name="email" type="email" value="<?= esc($item['email'] ?? '') ?>">

      <label><?= $isEdit ? 'New Password (optional)' : 'Password' ?></label>
      <input type="password" name="password" <?= $isEdit?'':'required' ?>>
    <?php else: ?>
      <!-- LDAP: read-only field untuk info -->
      <label>Full Name</label>
      <input value="<?= esc($item['full_name'] ?? '') ?>" disabled>

      <label>Email</label>
      <input value="<?= esc($item['email'] ?? '') ?>" disabled>
    <?php endif; ?>

    <label>Role</label>
    <select name="role">
      <?php
        $r = $item['role'] ?? 'user';
        foreach (['user','admin','superadmin'] as $opt):
      ?>
        <option value="<?= $opt ?>" <?= $r===$opt?'selected':'' ?>><?= strtoupper($opt) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Active</label>
    <input type="checkbox" name="is_active" value="1" <?= ((int)($item['is_active'] ?? 1)===1)?'checked':'' ?>>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
      <a class="btn ghost" href="/users">Cancel</a>
      <button class="btn"><?= $isEdit ? 'Save' : 'Create' ?></button>
    </div>
  </form>
</div>
