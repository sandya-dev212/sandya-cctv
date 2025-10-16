<?php
$uri      = service('uri');
$seg1     = strtolower($uri->getSegment(1) ?? '');
$isLogin  = ($seg1 === 'login');
$role     = session('role') ?? 'user';
$isAuthed = (bool) session('isLoggedIn');

function activeBtn(string $path): string {
    $curr = strtolower(parse_url(current_url(), PHP_URL_PATH) ?? '');
    return (str_starts_with($curr, $path)) ? 'btn-nav active' : 'btn-nav';
}
?>
<style>
.nav{
  display:flex; justify-content:space-between; align-items:center; gap:14px;
  padding:12px 6px;
}
.nav-left,.nav-right{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none}
.brand-logo{height:22px;display:block}
.brand-title{
  font-weight:900; font-size:22px; line-height:1;
  letter-spacing:.4px; color:#e5e7eb;
  text-shadow:0 1px 0 rgba(0,0,0,.25); margin-right:6px; user-select:none;
}
.brand-title .accent{color:#a78bfa}
.btn-nav{
  display:inline-flex; align-items:center; gap:8px;
  padding:8px 12px; border-radius:999px;
  background:#111827; color:#e5e7eb; text-decoration:none;
  border:1px solid #1f2937; transition:all .15s ease;
}
.btn-nav:hover{background:#0b1220;border-color:#374151}
.btn-nav.active{background:#7c3aed;color:#0b1020;border-color:#7c3aed;font-weight:700}
.role-badge{
  background:#1f2937; color:#cbd5e1; padding:6px 10px; border-radius:999px; font-size:12px;
}
.user{display:flex;align-items:center;gap:10px}
.btn-out{
  padding:8px 12px; border-radius:999px; background:#ef4444; color:#fff; text-decoration:none; border:none
}
@media (max-width:768px){
  .nav{flex-direction:column; align-items:flex-start; gap:8px}
  .nav-right{width:100%; justify-content:space-between}
  .brand-title{font-size:18px}
}
</style>

<nav class="nav">
  <div class="nav-left">
    <a href="/" class="brand" aria-label="Home">
      <img class="brand-logo" src="/assets/logo.png" alt="Sandya">
      <span class="brand-title">Sandya <span class="accent">NVR</span></span>
    </a>

    <?php if (!$isLogin && $isAuthed): ?>
      <a href="/dashboard"  class="<?= activeBtn('/dashboard') ?>">Dashboard</a>

      <?php if (in_array($role, ['admin','superadmin'], true)): ?>
        <a href="/nvrs"       class="<?= activeBtn('/nvrs') ?>">NVRs</a>
        <a href="/cameras"    class="<?= activeBtn('/cameras') ?>">Cameras</a>
      <?php endif; ?>
      <!-- Videos tersedia untuk semua role -->
      <a href="/videos" class="<?= activeBtn('/videos') ?>">Videos</a>
      <?php if (in_array($role, ['admin','superadmin'], true)): ?>
        <a href="/dashboards" class="<?= activeBtn('/dashboards') ?>">User Dashboards</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="nav-right">
    <?php if (!$isLogin && $isAuthed): ?>
      <div class="user">
        <?php if ($role === 'superadmin'): ?>
          <a href="/users" class="<?= activeBtn('/users') ?>">Users List</a>
        <?php endif; ?>
        <span class="role-badge">Role: <?= esc($role) ?></span>

        <!-- Tombol username → klik buka popup account switcher -->
        <button id="btn-acc-switch" type="button" class="btn-nav" onclick="openAccSwitcher()">
          @<?= esc(session('username') ?? 'user') ?>
        </button>

        <a class="btn-out" href="/logout" onclick="return confirm('Logout?')">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<script>
async function openAccSwitcher() {
  const ex = document.getElementById('acc-switcher');
  if (ex) { ex.remove(); return; } // toggle off kalau udah ada

  try {
    const rsp = await fetch('/account-switcher', {headers: {'X-Requested-With':'fetch'}});
    const html = await rsp.text();
    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div.firstElementChild);
  } catch (err) {
    alert('Gagal load switcher.');
  }
}
</script>
