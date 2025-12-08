<?php
$uri      = service('uri');
$seg1     = strtolower($uri->getSegment(1) ?? '');
$isLogin  = ($seg1 === 'login');
$role     = session('role') ?? 'user';
$isAuthed = (bool) session('isLoggedIn');

function activeBtn(string $path): string {
    $curr = strtolower(parse_url(current_url(), PHP_URL_PATH) ?? '');
    if (str_contains($curr, 'dashboards')) {
      $curr = '/user-dashboards ';
    }
    return (str_starts_with($curr, $path)) ? 'btn-nav active' : 'btn-nav';
}

function activeBtnMobile(string $path): string {
    $curr = strtolower(parse_url(current_url(), PHP_URL_PATH) ?? '');
    return (str_starts_with($curr, $path)) ? 'bg-[#7c3aed]/90' : 'bg-[#111827]';
}
?>
<style>
.btn-nav{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#111827;color:#e5e7eb;text-decoration:none;border:1px solid #1f2937;transition:all .15s ease}
.btn-nav:hover{background:#0b1220;border-color:#374151}
.btn-nav.active{background:#7c3aed;color:#hhh;border-color:#7c3aed;font-weight:700}
.role-badge{background:#1f2937;color:#cbd5e1;padding:6px 12px;border-radius:999px;font-size:12px}
.btn-out{padding:8px 12px;border-radius:999px;background:#ef4444;color:#fff;text-decoration:none;border:none}
@media (max-width:768px){.nav{flex-direction:column;align-items:flex-start;gap:8px}.nav-right{width:100%;justify-content:space-between}.brand-title{font-size:18px}}
</style>

<nav>
  <div class="flex justify-between items-center gap-3.5 p-3 sticky top-0 z-10 border-b border-white backdrop-filter backdrop-blur-sm bg-opacity-10 max-[850px]:hidden">
    <div class="flex items-center gap-3 flex-wrap">
      <a href="/dashboard?id=0" class="flex items-center justify-center gap-2.5 mr-3" aria-label="Home">
        <img width="100px" src="/assets/logo-white.png" alt="Sandya">
        <span class="font-extrabold text-xl text-[#a78bfa]"><span class="accent">Lensa</span></span>
      </a>

      <?php if (!$isLogin && $isAuthed): ?>
        <a href="/dashboard?id=0"  class="<?= activeBtn('/dashboard') ?>">Dashboard</a>

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
  </div>
  
  <div class="min-[850px]:hidden fixed top-0 right-0 left-0 z-10">
      <div class="absolute top-0 right-0 left-0 z-40 flex flex-row justify-between items-center border-b border-white py-2 px-3 shadow-md backdrop-filter backdrop-blur-sm bg-opacity-10">
          <a href="/dashboard?id=0" class="flex items-center justify-center gap-2.5 mr-3" aria-label="Home">
            <img width="100px" src="/assets/logo-white.png" alt="Sandya">
            <span class="font-extrabold text-xl text-[#a78bfa]"><span class="accent">Lensa</span></span>
          </a>
          <button onclick="setOpen()" class="min-[850px]:hidden">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
              </svg>
          </button>
      </div>
  
      <div onclick="setOpen()" id="navOverlay" class="absolute w-screen h-screen backdrop-filter backdrop-blur-sm bg-opacity-10 top-14 opacity-0 transition-shadow duration-200 ease-in-out hidden"></div>
      <div id="navOverflow" class="flex flex-col items-center justify-end w-full min-[850px]:hidden absolute top-12 z-10 shadow-md -translate-y-[120%] duration-500 ease-out transition-all mt-0" translate="no">
        <div>
          <?php if (!$isLogin && $isAuthed): ?>
            <a href="/dashboard?id=0" class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/dashboard') ?>">Dashboard</a>
            <?php if (in_array($role, ['admin','superadmin'], true)): ?>
              <a href="/nvrs"       class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/nvrs') ?>">NVRs</a>
              <a href="/cameras"    class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/cameras') ?>">Cameras</a>
            <?php endif; ?>
            <a href="/videos" class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/videos') ?>">Videos</a>
            <?php if (in_array($role, ['admin','superadmin'], true)): ?>
              <a href="/user-dashboards" class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/user-dashboards') ?>">User Dashboards <?= strtolower(parse_url(current_url(), PHP_URL_PATH) ?? '')?></a>
            <?php endif; ?>
            <?php if ($role === 'superadmin'): ?>
              <a href="/users" class="inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full <?= activeBtnMobile('/users') ?>">Users List</a>
            <?php endif; ?>
            <a href="/logout" onclick="return confirm('Logout?')" class="bg-red-700/80 inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full">Logout</a>
            <div class="bg-[#111827] inline-flex items-center gap-2 px-2 py-3 border-b border-white text-[#e5e7eb] font-bold w-full">
              <a id="btn-acc-switch" type="button" class="w-full" onclick="openAccSwitcher()">
                <span class="role-badge">Role: <?= esc($role) ?></span>
                Signed as: <?= esc(session('username') ?? 'user') ?>
              </a>
            </div>
            <div id="switchAccCompMobile"></div>
          <?php endif; ?>
        </div>
      </div>
  </div>
</nav>

<script>
    let isOpened = false;
    
    function setOpen() {
      $('#navOverflow').addClass( isOpened ? '-translate-y-[120%]' : 'translate-y');
      $('#navOverflow').removeClass( isOpened ? 'translate-y' : '-translate-y-[120%]');
      
      $('#navOverflow').addClass( isOpened ? 'mt-0' : 'mt-2');
      $('#navOverflow').removeClass( isOpened ? 'mt-2' : 'mt-0');

      $('#navOverlay').addClass( isOpened ? 'opacity-0' : 'opacity-100');
      $('#navOverlay').addClass( isOpened ? 'hidden' : 'block');

      $('#navOverlay').removeClass( isOpened ? 'opacity-100' : 'opacity-0');
      $('#navOverlay').removeClass( isOpened ? 'block' : 'hidden');

      isOpened = !isOpened;
    }
    
    async function openAccSwitcher() {

      const role = '<?= session('role') ?>';
      const parentId = '<?= session('parentId') ?>';

      if (role == 'user' && parentId == '') return;

      try {
        $.ajax({
            url: "/account-switcher" ,
            type: 'GET',
            success: function(response) {
              let vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0)
              if (vw <= 850) {
                $('#switchAccCompMobile').html(response);
              } else {
                $('#switchAccComp').html(response);
              }
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
