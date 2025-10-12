<section class="page-head">
  <h1>NVRs</h1>
  <a class="btn" href="/nvrs/new">Add NVR</a>
</section>

<?php if (empty($items)): ?>
  <p style="color:#94a3b8">Belum ada NVR.</p>
<?php else: ?>
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr><th align="left">Name</th><th align="left">Base URL</th><th>Active</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr style="border-top:1px solid #1f2937">
        <td><?= esc($it['name']) ?></td>
        <td><?= esc($it['base_url']) ?></td>
        <td align="center"><?= (int)$it['is_active'] ? '✔' : '—' ?></td>
        <td>
          <a class="btn ghost" href="/nvrs/<?= (int)$it['id'] ?>/edit">Edit</a>
          <form style="display:inline" method="post" action="/nvrs/<?= (int)$it['id'] ?>/delete" onsubmit="return confirm('Delete NVR?')">
            <button class="btn" type="submit" style="background:#ef4444">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
