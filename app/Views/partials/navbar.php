<nav class="nav">
  <div class="nav-left">
    <a href="/" class="brand" style="text-decoration:none">Sandya NVR</a>
    <a href="/dashboard" class="nav-link">Dashboard</a>

    <?php $role = session('role') ?? 'user'; ?>
    <?php if (in_array($role, ['admin','superadmin'], true)): ?>
      <a href="/dashboards" class="nav-link">Dashboards</a>
      <a href="/cameras"    class="nav-link">Cameras</a>
      <a href="/nvrs"       class="nav-link">NVRs</a>
      <?php if ($role === 'superadmin'): ?>
        <a href="/users"    class="nav-link">Users</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="nav-right">
    <?php if (session('isLoggedIn')): ?>
      <div class="user">
        <span class="user-name"><?= esc(session('name')) ?></span>
        <span class="badge"><?= esc($role) ?></span>
        <a class="btn-out" href="/logout" onclick="return confirm('Logout?')">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
