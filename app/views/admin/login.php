<?php /** @var ?string $error @var bool $firstRun */ ?><!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Dashboard — Al Kawkaba</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400&family=Jost:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=<?= @filemtime(ROOT . '/public/assets/app.css') ?>">
</head>
<body class="min-h-screen">
<div class="grid min-h-screen place-items-center bg-ink p-4">
  <form method="post" action="/admin/login" class="w-full max-w-sm space-y-6 bg-ivory p-10">
    <?= csrf_field() ?>
    <?= img_fixed('/brand/logo.jpg', 80, 'Al Kawkaba', 'mx-auto rounded-full') ?>
    <h1 class="display text-center text-3xl"><?= $firstRun ? 'Create admin account' : 'Dashboard' ?></h1>
    <?php if ($firstRun): ?>
      <p class="text-center text-sm text-stone">No staff accounts exist yet. This first account gets full admin access.</p>
      <label class="block"><span class="eyebrow text-stone">Name</span><input name="name" required class="field" autocomplete="name"></label>
    <?php endif ?>
    <label class="block"><span class="eyebrow text-stone">Email</span><input name="email" type="email" required class="field" autocomplete="email" value="<?= e(input('email')) ?>"></label>
    <label class="block"><span class="eyebrow text-stone">Password</span><input name="password" type="password" required class="field" <?= $firstRun ? 'minlength="8" autocomplete="new-password"' : 'autocomplete="current-password"' ?>></label>
    <?php if ($error): ?><p role="alert" class="text-sm text-red-700"><?= e($error) ?></p><?php endif ?>
    <button class="btn btn-dark w-full"><?= $firstRun ? 'Create account' : 'Sign in' ?></button>
  </form>
</div>
</body>
</html>
