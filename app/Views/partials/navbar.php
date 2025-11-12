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
.btn-nav{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#111827;color:#e5e7eb;text-decoration:none;border:1px solid #1f2937;transition:all .15s ease}
.btn-nav:hover{background:#0b1220;border-color:#374151}
.btn-nav.active{background:#7c3aed;color:#hhh;border-color:#7c3aed;font-weight:700}
.role-badge{background:#1f2937;color:#cbd5e1;padding:6px 10px;border-radius:999px;font-size:12px}
.btn-out{padding:8px 12px;border-radius:999px;background:#ef4444;color:#fff;text-decoration:none;border:none}
@media (max-width:768px){.nav{flex-direction:column;align-items:flex-start;gap:8px}.nav-right{width:100%;justify-content:space-between}.brand-title{font-size:18px}}
</style>

<nav class="flex justify-between items-center gap-3.5 p-3 sticky top-0 z-10 border-b border-white backdrop-filter backdrop-blur-sm bg-opacity-10">
  <div class="flex items-center gap-3 flex-wrap">
    <a href="/dashboard/0" class="flex items-center justify-center gap-2.5 mr-3" aria-label="Home">
      <img width="100px" src="/assets/logo-white.png" alt="Sandya">
      <span class="font-extrabold text-xl text-[#a78bfa]"><span class="accent">Lensa</span></span>
    </a>

    <?php if (!$isLogin && $isAuthed): ?>
      <a href="/dashboard/0"  class="<?= activeBtn('/dashboard') ?>">Dashboard</a>

      <?php if (in_array($role, ['admin','superadmin'], true)): ?>
        <a href="/nvrs"       class="<?= activeBtn('/nvrs') ?>">NVRs</a>
        <a href="/cameras"    class="<?= activeBtn('/cameras') ?>">Cameras</a>
      <?php endif; ?>
      <a href="/videos" class="<?= activeBtn('/videos') ?>">Videos</a>
      <?php if (in_array($role, ['admin','superadmin'], true)): ?>
        <a href="/user-dashboards" class="<?= activeBtn('/user-dashboards') ?>">User Dashboards</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="flex items-center gap-3 flex-wrap">
    <?php if (!$isLogin && $isAuthed): ?>
      <div class="flex items-center gap-2.5">
        <?php if ($role === 'superadmin'): ?>
          <a href="/users" class="<?= activeBtn('/users') ?>">Users List</a>
        <?php endif; ?>
        <span class="role-badge">Role: <?= esc($role) ?></span>

        <button id="btn-acc-switch" type="button" class="btn-nav hover:cursor-pointer" onclick="openAccSwitcher()">
          <?= esc(session('username') ?? 'user') ?>
        </button>

        <div id="switchAccComp"></div>
        
        <a class="btn-out" href="/logout" onclick="return confirm('Logout?')">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<script>
    async function openAccSwitcher() {

      const role = '<?= session('role') ?>';
      const parentId = '<?= session('parentId') ?>';

      if (role == 'user' && parentId == '') return;

      try {
        $.ajax({
            url: "/account-switcher" ,
            type: 'GET',
            success: function(response) {
              $('#switchAccComp').html(response);
            },
            error: function(xhr) {
              alert('Error: ' + xhr.responseText);
            }
          });
      } catch (e) { 
        alert('Gagal load switcher. ', e); 
      }
    }
</script>
