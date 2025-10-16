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
      // --- Ambil nilai auth dari berbagai kemungkinan nama kolom:
      $authRaw = '';
      foreach (['auth_source','auth','auth_type','provider','auth_provider'] as $k) {
        if (isset($u[$k]) && $u[$k] !== '' && $u[$k] !== null) { $authRaw = (string)$u[$k]; break; }
      }
      $authRaw = strtolower(trim($authRaw));

      // --- Fallback: jika kosong, lihat password hash/field lain
      if ($authRaw === '') {
        $hasPwd = false;
        foreach (['password_hash','password'] as $kp) {
          if (!empty($u[$kp] ?? '')) { $hasPwd = true; break; }
        }
        $authRaw = $hasPwd ? 'local' : 'ldap';
      }
      $authLabel = ($authRaw === 'ldap') ? 'LDAP' : 'Local';

      // Hanya superadmin yang bisa jadi parent untuk Link Accounts
      $isParent = (($u['role'] ?? 'user') === 'superadmin');
    ?>
    <tr>
      <td><?= esc($u['username']) ?></td>
      <td><?= esc($u['full_name']) ?></td>
      <td><?= esc($u['email']) ?></td>
      <td>
        <span class="chip"><?= esc($authLabel) ?></span>
        <span style="margin-left:6px;color:#94a3b8"><?= esc($authRaw) !== $authLabel ? esc(strtoupper($authRaw)) : '' ?></span>
      </td>
      <td><?= esc($u['role']) ?></td>
      <td><?= ((int)$u['is_active']===1)?'✓':'—' ?></td>
      <td style="display:flex;gap:8px;align-items:center">
        <?php if ($isParent): ?>
          <!-- Parent superadmin: tombol aktif (menuju UI checklist link) -->
          <a class="btn" style="background:#22c55e" href="/users/link/<?= (int)$u['id'] ?>">Link Accounts</a>
        <?php else: ?>
          <!-- Bukan superadmin: tombol di-grey (non-klik) -->
          <span class="btn" style="background:#6b7280;cursor:not-allowed;opacity:.6" title="Hanya superadmin bisa jadi parent">Link Accounts</span>
        <?php endif; ?>

        <a class="btn ghost" href="/users/<?= (int)$u['id'] ?>/edit">Edit</a>
        <form action="/users/<?= (int)$u['id'] ?>/delete" method="post" style="display:inline" onsubmit="return confirm('Delete user ini?')">
          <button class="btn" style="background:#ef4444">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
