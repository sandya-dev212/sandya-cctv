<section class="page-head" style="display:flex;align-items:center;gap:12px">
  <h1 style="margin-right:auto">Dashboards</h1>
  <a class="btn" href="/dashboards/new">Add Dashboard</a>
</section>

<?php if (empty($items)): ?>
  <p class="muted">Belum ada dashboard.</p>
<?php else: ?>
  <table class="table-auto border-collapse w-full">
    <thead>
      <tr align="left" class="p-3">
        <th align="left">Nama Dashboard</th>
        <th align="left">Users</th>
        <th align="left">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $d): ?>
        <tr class="border-t border-slate-600">
          <td align="left"><?= esc($d['name']) ?></td>
          <td align="left">
              <?php
                $list = $assigns[$d['id']] ?? [];
                foreach($list as $name): 
              ?>
                <div class="inline-block font-bold my-1 py-1 px-2 bg-violet-400/50 rounded-full w-max"><?= $name ?></div>
              <?php endforeach; ?>
          </td>
          <td>
            <div class="flex flex-row gap-3">
              <a class="btn ghost" href="/dashboards/<?= (int)$d['id'] ?>/edit">Edit</a>
              <form method="post" action="/dashboards/<?= (int)$d['id'] ?>/delete" onsubmit="return confirm('Hapus dashboard ini?')">
                <button class="btn" style="background:#ef4444">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
