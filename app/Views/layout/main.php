<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= esc($title ?? 'Lensa') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

  <!-- Favicon / icons -->
  <link rel="icon" type="image/x-icon" href="/assets/favico.ico">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/favico.ico">
  <link rel="apple-touch-icon" href="/assets/logo.png">
  <meta name="theme-color" content="#0f172a">

  <!-- App assets -->
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
