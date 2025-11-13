<section class="w-full flex flex-row justify-between mb-10">
  <p class="text-3xl text-white font-bold">NVRs</p>
  <a class="btn" href="/nvrs/new">Add NVR</a>
</section>

<?php if (empty($data)): ?>
  <p style="color:#94a3b8">Belum ada NVR.</p>
<?php else: ?>
  <div class="max-w-full overflow-x-auto">
    <table class="w-full max-[850px]:min-w-[850px] border-collapse">
      <thead>
        <tr>
          <th align="left">Name</th>
          <th align="left">Base URL</th>
          <th align="right">Total Camera(s)</th>
          <th align="center">Active</th>
          <th align="center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $item): ?>
        <tr style="border-top:1px solid #1f2937">
          <td><?= esc($item['name']) ?></td>
          <td><?= esc($item['base_url']) ?></td>
          <td align="right"><?= (int)($totals[$item['id']] ?? 0) ?></td>
          <td align="center"><?= (int)$item['is_active'] ? '✔' : '—' ?></td>
          <td align="center">
            <a class="btn ghost" href="/nvrs/<?= (int)$item['id'] ?>/edit">Edit</a>
            <form style="display:inline" method="post" action="/nvrs/<?= (int)$item['id'] ?>/delete" onsubmit="return confirm('Delete NVR?')">
              <button class="btn" type="submit" style="background:#ef4444">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
