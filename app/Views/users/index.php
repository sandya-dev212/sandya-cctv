<section class="page-head">
  <h1 style="margin-right:auto">Users</h1>
  <a class="btn" href="/users/new">Add Local User</a>
</section>

<table class="table">
  <thead>
    <tr>
      <th>Username</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Auth</th>
      <th>Role</th>
      <th>Active</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $u): ?>
    <?php
      // --- Penentuan Auth Source (fix): ---
      // 1) pakai kolom auth_source kalau ada nilainya
      // 2) kalau kosong: jika ada password_hash -> local, kalau tidak -> ldap
      $authRaw = strtolower(trim((string)($u['auth_source'] ?? '')));
      if ($authRaw === '') {
        $authRaw = empty($u['password_hash']) ? 'ldap' : 'local';
      }
      $authLabel = ucfirst($authRaw); // "Local" / "Ldap"
    ?>
    <tr>
      <td><?= esc($u['username']) ?></td>
      <td><?= esc($u['full_name']) ?></td>
      <td><?= esc($u['email']) ?></td>
      <td>
        <span class="chip" title="<?= esc($authLabel) ?>">
          <?= esc($authLabel) ?>
        </span>
      </td>
      <td><?= esc($u['role']) ?></td>
      <td><?= ((int)$u['is_active']===1)?'✓':'—' ?></td>
      <td style="display:flex;gap:8px">
        <a class="btn ghost" href="/users/<?= (int)$u['id'] ?>/edit">Edit</a>
        <form action="/users/<?= (int)$u['id'] ?>/delete" method="post" onsubmit="return confirm('Delete user ini?')" style="display:inline">
          <button class="btn" style="background:#ef4444">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
