<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sandya NVR — Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- favicon & meta dasar -->
  <link rel="icon" type="image/x-icon" href="/assets/favico.ico">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/favico.ico">
  <link rel="apple-touch-icon" href="/assets/logo.png">
  <meta name="theme-color" content="#0f172a">

  <style>
    html,body{height:100%;margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu}
    .wrap{display:grid;place-items:center;height:100%;background:#0f172a;color:#e2e8f0;padding:16px}
    .card{
      background:#111827;padding:28px 24px;border-radius:16px;
      width:min(92vw,380px);box-shadow:0 10px 30px rgba(0,0,0,.4)
    }
    .logo{
      display:block;margin:0 auto 14px;
      width:48%;               /* relatif terhadap card */
      max-width:180px;         /* batasi biar gak kebesaran di desktop */
      height:auto;             /* jaga rasio */
      object-fit:contain;
    }
    .muted{font-size:12px;color:#94a3b8;margin:0 0 16px;text-align:center}
    label{display:block;font-size:12px;margin:10px 0 6px;color:#cbd5e1}
    input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #334155;background:#0b1220;color:#e2e8f0;outline:none}
    .btn{margin-top:16px;width:100%;padding:12px;border:none;border-radius:10px;background:#22c55e;color:#0b1220;font-weight:600;cursor:pointer}
    .btn:active{transform:translateY(1px)}
    .error{background:#7f1d1d;color:#fecaca;padding:8px 10px;border-radius:8px;margin-bottom:10px;font-size:13px}
    @media (max-width:480px){
      .logo{width:58%;max-width:160px}
    }
  </style>
</head>
<body>
<div class="wrap">
  <form class="card" method="post" action="/login" autocomplete="on">
    <?= csrf_field() ?>

    <!-- Logo (gantikan teks Sandya NVR) -->
    <img src="/assets/logo.png" alt="Sandya NVR" class="logo">
    <p class="muted">Silakan login</p>

    <?php $err = session()->getFlashdata('error') ?? ($error ?? null); ?>
    <?php if (!empty($err)): ?>
      <div class="error"><?= esc($err) ?></div>
    <?php endif; ?>

    <label for="username">Username</label>
    <input id="username" name="username" placeholder="mis. administrator" required value="<?= esc(old('username')) ?>">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" placeholder="••••••••" required>

    <button class="btn" type="submit">Login</button>
  </form>
</div>
</body>
</html>
