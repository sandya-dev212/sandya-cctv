<section class="page-head">
  <h1 style="margin-right:auto">Users</h1>
  <a class="btn" href="/users/new">Add Local User</a>
</section>

<table class="table">
  <thead>
    <tr>
      <th>Username</th><th>Full Name</th><th>Email</th>
      <th>Auth</th><th>Role</th><th>Active</th><th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $u): ?>
    <tr>
      <td><?= esc($u['username']) ?></td>
      <td><?= esc($u['full_name']) ?></td>
      <td><?= esc($u['email']) ?></td>
      <td><span class="chip"><?= esc($u['auth_source'] ?: 'local') ?></span></td>
      <td><?= esc($u['role']) ?></td>
      <td><?= ((int)$u['is_active']===1)?'✓':'—' ?></td>
      <td>
        <a class="btn ghost" href="/users/<?= (int)$u['id'] ?>/edit">Edit</a>
        <form action="/users/<?= (int)$u['id'] ?>/delete" method="post" style="display:inline" onsubmit="return confirm('Delete user ini?')">
          <button class="btn" style="background:#ef4444">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
