<section class="page-head">
  <p class="text-3xl text-white font-bold"><?= $action==='create' ? 'Add NVR' : 'Edit NVR' ?></p>
</section>

<form method="post" action="<?= $action==='create' ? '/nvrs/store' : '/nvrs/'.(int)$item['id'].'/update' ?>" class="w-full">
  <div class="flex flex-col gap-1 my-3 w-full">
    <label class="font-bold">Nama</label>
    <input name="name" value="<?= esc($item['name'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md max-w-[25%] max-[850px]:max-w-full" required>
  </div>
  <div class="flex flex-col gap-1 my-3">
    <label class="font-bold">Base URL (contoh: https://nvr-xxx.sandya.net.id)</label>
    <input name="base_url" value="<?= esc($item['base_url'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md max-w-[25%] max-[850px]:max-w-full" required>
  </div>
  <div class="flex flex-col gap-1 my-3">
    <label class="font-bold">API Key</label>
    <input name="api_key" value="<?= esc($item['api_key'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md max-w-[25%] max-[850px]:max-w-full" required>
  </div>
  <div class="flex flex-col gap-1 my-3">
    <label class="font-bold">Group Key</label>
    <input name="group_key" value="<?= esc($item['group_key'] ?? '') ?>" class="bg-slate-800 p-2 rounded-md max-w-[25%] max-[850px]:max-w-full" required>
  </div>
  <div class="flex flex-col gap-1 my-3">
    <label class="font-bold">Active</label>
    <select name="is_active" class="bg-slate-800 rounded-md p-2 max-w-[25%] max-[850px]:max-w-full">
      <option value="1" <?= isset($item['is_active']) && (int)$item['is_active']===1 ? 'selected':'' ?>>Yes</option>
      <option value="0" <?= isset($item['is_active']) && (int)$item['is_active']===0 ? 'selected':'' ?>>No</option>
    </select>
  </div>
  <div class="flex flex-row gap-3 mt-10">
    <button class="btn" type="submit">Save</button>
    <a class="btn ghost" href="/nvrs">Cancel</a>
  </div>
</form>
