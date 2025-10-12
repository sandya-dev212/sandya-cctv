<section class="page-head" style="display:flex;align-items:center;gap:12px">
  <h1 style="margin-right:auto">Dashboards</h1>
  <a class="btn" href="/dashboards/new">Add Dashboard</a>
</section>

<?php if (empty($items)): ?>
  <p class="muted">Belum ada dashboard.</p>
<?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th style="width:260px; text-align:center">Nama Dashboard</th>
        <th style="text-align:center">Users</th>
        <th style="width:260px; text-align:center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $d): ?>
        <tr>
          <td style="text-align:center"><?= esc($d['name']) ?></td>
          <td style="text-align:center">
            <?php
              $list = $assigns[$d['id']] ?? [];
              echo $list ? esc(implode(', ', $list)) : '&mdash;';
            ?>
          </td>
          <td style="display:flex;gap:8px;justify-content:center">
            <a class="btn ghost" href="/dashboards/<?= (int)$d['id'] ?>/edit">Edit</a>
            <a class="btn ghost" href="/cameras/mappings?dashboard_id=<?= (int)$d['id'] ?>">Mappings</a>
            <form method="post" action="/dashboards/<?= (int)$d['id'] ?>/delete" onsubmit="return confirm('Hapus dashboard ini?')">
              <button class="btn" style="background:#ef4444">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
