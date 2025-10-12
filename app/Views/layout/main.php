<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= esc($title ?? 'Sandya NVR') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/assets/app.css" rel="stylesheet">
  <script defer src="/assets/app.js"></script>
</head>
<body>
  <?= view('partials/navbar') ?>

  <main class="container">
    <?= $content ?? '' ?>
  </main>

  <!-- Spinner overlay -->
  <div id="spinner" class="spinner hidden">
    <div class="loader"></div>
  </div>
</body>
</html>
