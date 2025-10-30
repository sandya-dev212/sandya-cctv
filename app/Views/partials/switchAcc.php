<div id="acc-switcher" class="fixed top-[70px] right-1 z-9999">
  <div class="bg-[#0f172a] border border-[#1f2937] rounded-xl min-w-[320px] max-w-[420px] p-3.5">
    <div class="flex items-center justify-between gap-2 mb-2.5">
      <div><strong>Signed in:</strong> <br> <?= $user['username'] ?> <span style="opacity:.6"><?= $user['role'] ?></span></div>
      <button onclick="$('#acc-switcher').addClass('hidden')" class="bg-[#111827] border border-[#374151] rounded-xl color-[#e5e7eb] py-1.5 px-2.5 cursor-pointer">X</button>
    </div>
    
    <?php if (session('role') == 'user' && session('parentId') != null): ?>
        <form method="post" action="/switch-as/<?= session('parentId')?>" onsubmit="return confirm('Switch ke Main Dashboard?')">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf ?>">
            <button type="submit" class="w-full text-left p-2 rounded-lg  border border-[#374151] bg-[#0b1220] color-[#e5e7eb] cursor-pointer">
                <div><strong>Back To Main Dashboard <?= session('parentId')?></strong></div>
            </button>
        </form>
    <?php endif; ?>
    
    <div style="font-size:12px;opacity:.8;margin:6px 0 8px">Linked users:</div>
    <?php if (empty($result)): ?>
      <div style="opacity:.8">none</div>
    <?php else: ?>
      <div class="flex flex-col gap-2">
        <?php foreach ($result as $res): ?>

        <?php if ($res['username'] != $user['username']): ?>
            <form method="post" action="/switch-as/<?= $res['id']?>" onsubmit="return confirm('Switch ke <?= $res['username'] ?>?')">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf ?>">
                <button type="submit" class="w-full text-left p-2.5 rounded-lg  border border-[#374151] bg-[#0b1220] color-[#e5e7eb] cursor-pointer">
                    <div><strong><?= $res['username'] ?></strong></div>
                </button>
            </form>
        <?php endif; ?>
            
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>