<?php
$isEdit = ($mode ?? '') === 'edit';
$auth   = $isEdit ? ($item['auth_source'] ?: 'local') : 'local';
$action = $isEdit ? '/users/'.(int)$item['id'].'/update' : '/users/store';
?>
<div class="card p-5" style="max-width:720px;margin:24px auto">
  <p class="font-bold text-3xl"><?= $isEdit ? 'Edit User' : 'Add Local User' ?></p>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= $action ?>" class="flex flex-col gap-4 mt-10">
    <div class="flex flex-col gap-2">
        <label class="font-bold">Username</label>
        <?php if (!$isEdit): ?>
          <input name="username" class="bg-slate-800 p-2 rounded-md" required>
        <?php else: ?>
          <input value="<?= esc($item['username']) ?>" class="bg-slate-800 p-2 rounded-md" disabled>
        <?php endif; ?>
    </div>

    <div class="flex flex-col gap-2">
      <label class="font-bold">Auth Source</label>
      <input value="<?= strtoupper($auth) ?>" class="bg-slate-800 p-2 rounded-md" disabled>
    </div>

    <div class="flex flex-col gap-2">
      <label class="font-bold">Full Name</label>
      <input name="full_name" value="<?= esc($item['full_name'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md" <?php $auth === 'local' ? '' : 'disabled' ?>>
    </div>
    
    <div class="flex flex-col gap-2">
      <label class="font-bold">Email</label>
      <input name="email" type="email" value="<?= esc($item['email'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md" <?php $auth === 'local' ? '' : 'disabled' ?>>
    </div>

    <?php if ($auth === 'local'): ?>
      <div class="flex flex-col gap-2">
        <label class="font-bold"><?= $isEdit ? 'New Password (optional)' : 'Password' ?></label>
        <input type="password" name="password" <?= $isEdit?'':'required' ?> class="bg-slate-800 p-2 rounded-md">
      </div>
    <?php endif; ?>

    <div class="flex flex-col gap-2">
      <label class="font-bold">Role</label>
      <select name="role" class="bg-slate-800 p-2 rounded-md">
        <?php
          $r = $item['role'] ?? 'user';
          foreach (['user','admin','superadmin'] as $opt):
        ?>
          <option value="<?= $opt ?>" <?= $r===$opt?'selected':'' ?>><?= strtoupper($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="flex flex-row items-center gap-3">
      <label class="font-bold">Active</label>
      <input type="checkbox" name="is_active" value="1" class="w-5 h-5 hover:cursor-pointer" <?= ((int)($item['is_active'] ?? 1)===1)?'checked':'' ?>>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
      <a class="btn ghost" href="/users">Cancel</a>
      <button class="btn"><?= $isEdit ? 'Save' : 'Create' ?></button>
    </div>
  </form>
</div>
