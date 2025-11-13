<?php
  $isEdit = !empty($data['id']);
  $action = $isEdit
    ? '/dashboards/' . (int)$data['id'] . '/update'   // POST update
    : '/dashboards/store';                            // POST create
?>

<div class="flex flex-col gap-5 mt-10">
  <form method="post" action="<?= esc($action) ?>" class="flex flex-col gap-5">
    <section>
      <p class="text-3xl font-bold text-white"><?= $isEdit ? 'Edit Dashboard' : 'Add Dashboard' ?></p>
      <?php if (session()->getFlashdata('success')): ?>
          <div class="border-2 border-green-700 font-bold p-3 rounded-md w-max" role="alert">
              <?= session()->getFlashdata('success') ?>
          </div>
      <?php endif; ?>
    </section>
    
    <div class="flex flex-col gap-3">
      <div>
        <p class="text-xl font-bold text-white"><?= $isEdit ? '' : 'Please fill the new dashboard name first' ?></p>
        <label for="name" class="font-bold">Nama Dashboard</label>
      </div>
      <div>
        <input id="name" name="name" class="bg-slate-800 p-2 rounded-md" value="<?= esc($data['name'] ?? '') ?>" required>
        <button onchange="this.form.submit()" class="ml-3 font-bold bg-green-700 p-2 rounded-md hover:cursor-pointer">Save Name</button>
      </div>
    </div>
  
    <?php if ($data['id'] != 0): ?>
      <div class="flex flex-col gap-3">
        <div>
          <label for="user_ids" class="font-bold">Assign ke Users (role USER)</label>
        </div>
    
        <select id="user_ids" onchange="this.form.submit()" name="user_id" class="w-full bg-slate-800 p-2 rounded-md hover:cursor-pointer">
          <option value=""> -- Tambah User di sini -- </option>
          <?php foreach ($users as $u): ?>
    
            <?php if (!in_array($u['id'], $selected)): ?>
              <option value="<?= $u['id'] ?>" class="p-2 hover:bg-slate-700 hover:cursor-pointer rounded-md">
                <?= esc($u['username']) ?> — <?= esc($u['full_name'] ?? '') ?>
              </option>
            <?php endif; ?>
    
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif;?>
  </form>

  <?php if ($data['id'] != 0): ?>
    <label for="" class="font-bold">User yang mempunyai akses:</label>
    <div class="max-h-[28dvh] overflow-auto w-[50%] max-[850px]:w-full">
        <?php foreach ($users as $u): ?>
          <?php if (in_array($u['id'], $selected)): ?>
            <form action="/dashboards/<?= $data['id'] ?>/delete-access" method="POST">
             <div class="flex flex-row justify-between items-center p-3 mb-5 border-2 border-slate-400 rounded-md">
               <p><?= esc($u['full_name'] ?? '') ?></p>
               <input hidden name="user_id" value="<?= esc($u['id'] ?? '') ?>" />
               <button type="submit" class="font-bold bg-red-500 p-2 rounded-md hover:cursor-pointer">Remove</button>
              </div>
            </form>
           <?php endif; ?>
         <?php endforeach; ?>
    </div>
    
    <section>
      <!-- pilih NVR -->
      <p class="text-xl font-bold text-white">Cameras</p>
      <div class="flex flex-col gap-3 my-2">
        <p class="font-bold text-white">NVR URL</p>
        <form>
          <select id="dashboardSelect" name="nvr_id" onchange="this.form.submit()" class="w-full bg-slate-800 p-2 rounded-md hover:cursor-pointer">
            <?php foreach ($nvrs as $n): ?>
              <option value="<?= (int)$n['id'] ?>" <?= ($nvrActive && $nvrActive['id']===$n['id'])?'selected':'' ?>>
                <?= esc($n['name']) ?> (<?= esc($n['base_url']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    
      <p class="font-bold text-white mb-2">Camera List</p>
      <?php if (!$nvrActive): ?>
        <p class="muted" style="text-align:center">Belum ada NVR aktif.</p>
      <?php else: ?>
        <?php if (!$streams['ok'] || empty($streams['items'])): ?>
          <p class="muted" style="text-align:center"><?= esc($streams['msg'] ?: 'Tidak ada monitor / Shinobi error.') ?></p>
        <?php else: ?>
  
          <div class="cam-list">
            <?php foreach ($streams['items'] as $m): ?>
              <?php $isAssigned = in_array($m['mid'], array_column($assigned, 'monitor_id')); ?>
  
              <div class="cam-row">
                <div class="font-bold"><?= esc($m['mid']) ?></div>
                <div class="text-[#cbd5e1]"><?= esc($m['name']) ?></div>
                <div>
                  <?php if(!$isAssigned): ?>
  
                    <button
                      class="btn ghost assign-btn hover:cursor-pointer"
                      data-mid="<?= esc($m['mid']) ?>"
                      data-nvr="<?= $nvrActive['id'] ?>"
                      style="<?= $isAssigned ? 'background:#ef4444;color:#fff' : '' ?>"
                    >Assign to Dashboard</button>
  
                    <?php else:?>
  
                      <button
                        href="#"
                        class="btn unassign-btn hover:cursor-pointer"
                        style="background-color: #ef4444; color: white;"
                        data-mid="<?= esc($m['mid']) ?>"
                        data-nvr="<?= $nvrActive['id'] ?>"
                      >Unassigned</button>
  
                  <?php endif?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
  
    </section>
  <?php endif;?>
</div>

<style>
  .cam-row{display:grid;grid-template-columns: 1fr 2fr auto;gap:12px;align-items:center;padding:12px 14px;border:1px solid #1f2937;background:#0f1420;border-radius:12px;margin-bottom:10px}
  .cam-row .btn{min-width:170px}
  @media (max-width: 720px){
    .cam-row{grid-template-columns: 1fr;gap:8px}
    .cam-row .btn{width:100%}
  }
</style>

<script>
  const selDash = document.getElementById('selDash');

  // assign tanpa alias + auto-disable tombol setelah sukses
  document.querySelectorAll('.assign-btn').forEach(btn => {
    btn.addEventListener('click', () => {

      const dashId = <?= $data['id'] ?>;
      
      btn.disabled = true; btn.textContent = 'Assigning...';

      $.ajax({
        url: "/cameras/assign" ,
        type: 'POST',
        data: {
          dashboard_id: dashId,
          nvr_id: btn.dataset.nvr,
          monitor_id: btn.dataset.mid
        },
        success: function(response) {
          window.location.reload();
        },
        error: function(xhr) {
            alert('Error: ' + xhr.responseText);
        }
      });
    });
  });

  $('.unassign-btn').on('click', async function (e)  {
    e.preventDefault();

    if (!confirm('Hapus mapping ini?')) return false;

    const assigned = <?= json_encode($assigned) ?>;

    const id = assigned.filter((item) => {
      return item.monitor_id == $(this).data('mid');
    })[0].id;

    const fd = new FormData();
    fd.append('id', id);

    await fetch('/cameras/mappings/delete', { 
      method:'POST', 
      body: fd 
    }).then((res) => {
        window.location.reload();
    }).catch((err) => {
      alert('Err ', err)
    });
  })
</script>
