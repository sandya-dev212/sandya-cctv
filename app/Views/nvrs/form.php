<section class="page-head">
  <h1><?= $action==='create' ? 'Add NVR' : 'Edit NVR' ?></h1>
</section>

<form method="post" action="<?= $action==='create' ? '/nvrs/store' : '/nvrs/'.(int)$item['id'].'/update' ?>">
  <label>Nama</label>
  <input name="name" value="<?= esc($item['name'] ?? '') ?>" required>
  <label>Base URL (contoh: https://nvr-xxx.sandya.net.id)</label>
  <input name="base_url" value="<?= esc($item['base_url'] ?? '') ?>" required>
  <label>API Key</label>
  <input name="api_key" value="<?= esc($item['api_key'] ?? '') ?>" required>
  <label>Group Key</label>
  <input name="group_key" value="<?= esc($item['group_key'] ?? '') ?>" required>
  <label>Active</label>
  <select name="is_active">
    <option value="1" <?= isset($item['is_active']) && (int)$item['is_active']===1 ? 'selected':'' ?>>Yes</option>
    <option value="0" <?= isset($item['is_active']) && (int)$item['is_active']===0 ? 'selected':'' ?>>No</option>
  </select>
  <div style="margin-top:12px">
    <button class="btn" type="submit">Save</button>
    <a class="btn ghost" href="/nvrs">Cancel</a>
  </div>
</form>
