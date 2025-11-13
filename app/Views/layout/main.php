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
  <link href="/assets/css/output.css" rel="stylesheet">

  <script defer src="/assets/app.js"></script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <link href="https://cdn.jsdelivr.net/npm/gridstack@9.2.2/dist/gridstack.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/gridstack@9.2.2/dist/gridstack-all.min.js"></script>
</head>

<?php
  $uri      = service('uri');
  $seg1     = strtolower($uri->getSegment(1) ?? '');
  $isLogin  = ($seg1 === 'login');
  $isAuthed = (bool) session('isLoggedIn');
?>

<body>
  <?php if (!$isLogin && $isAuthed): ?>
    <?= view('partials/navbar') ?>
  <?php endif; ?>

  <main class="bg-[#0b1220] px-[10vh] py-[5vh] max-[850px]:px-[3vh] max-[850px]:py-[10vh]">
    <?= $content ?? '' ?>
  </main>

  <!-- Spinner overlay -->
  <div id="spinner" class="spinner hidden">
    <div class="loader"></div>
  </div>
</body>
</html>
