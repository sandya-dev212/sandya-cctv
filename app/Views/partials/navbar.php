<style>
/* responsive navbar */
.nav{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.nav-left,.nav-right{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.nav-link{color:#e5e7eb;text-decoration:none;padding:6px 8px;border-radius:8px}
.nav-link:hover{background:#111827}
.user{display:flex;align-items:center;gap:8px}
.btn-out{padding:6px 10px;border-radius:10px;background:#ef4444;color:#fff;text-decoration:none}

/* brand logo: anti gepeng */
.brand{display:flex;align-items:center;text-decoration:none}
.brand img{
  height:28px;              /* tinggi default navbar */
  width:auto;               /* pertahankan rasio */
  object-fit:contain;
  display:block;
}
@media (min-width: 1024px){
  .brand img{ height:32px; }
}

/* collapse di mobile */
@media (max-width: 768px){
  .nav{flex-direction:column;align-items:flex-start}
  .nav-right{width:100%;justify-content:space-between}
}
</style>

<nav class="nav">
  <div class="nav-left">
    <a href="/" class="brand" aria-label="Sandya NVR">
      <img src="/assets/logo.png" alt="Sandya NVR">
    </a>

    <a href="/dashboard" class="nav-link">Dashboard</a>

    <?php $role = session('role') ?? 'user'; ?>
    <?php if (in_array($role, ['admin','superadmin'], true)): ?>
      <a href="/dashboards" class="nav-link">Dashboards</a>
      <a href="/cameras"    class="nav-link">Cameras</a>
      <a href="/nvrs"       class="nav-link">NVRs</a>
      <?php if ($role === 'superadmin'): ?>
        <a href="/users"    class="nav-link">Users</a>
      <?php endif; ?>
      <a href="/videos"     class="nav-link">Videos</a>
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
