<?php
/** @var string $title @var string $content @var array $u */
$path = request_path();
$links = [['/admin', 'Overview'], ['/admin/orders', 'Orders'], ['/admin/products', 'Products'], ['/admin/collections', 'Collections'],
    ['/admin/categories', 'Categories'], ['/admin/homepage', 'Homepage'], ['/admin/media', 'Media library'],
    ['/admin/customers', 'Customers'], ['/admin/discounts', 'Discount codes']];
if ($u['role'] === 'ADMIN') array_push($links, ['/admin/settings', 'Settings'], ['/admin/staff', 'Staff & roles']);
?><!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> — Al Kawkaba Admin</title>
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<link rel="icon" href="<?= e(img_url('/brand/logo.jpg', 128)) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Almarai:wght@300;400;700&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400&family=Jost:wght@300;400;500;700&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=<?= @filemtime(ROOT . '/public/assets/app.css') ?>">
</head>
<body class="admin min-h-screen bg-[#f4f2ee] text-ink">
<div class="md:grid md:grid-cols-[230px_1fr]">
  <aside class="bg-ink text-ivory md:sticky md:top-0 md:h-screen md:overflow-y-auto">
    <div class="flex items-center gap-3 p-5">
      <?= img_fixed('/brand/logo.jpg', 40, '', 'rounded-full') ?>
      <div class="leading-tight"><p class="display text-lg">Al Kawkaba</p><p class="text-[.65rem] uppercase tracking-widest text-gold-soft"><?= e(strtolower($u['role'])) ?></p></div>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3 text-sm md:flex-col md:pb-6">
      <?php foreach ($links as [$href, $label]): $on = $href === '/admin' ? $path === $href : str_starts_with($path, $href); ?>
        <a href="<?= $href ?>" class="whitespace-nowrap rounded px-3 py-2 transition <?= $on ? 'bg-white/10 text-gold-soft' : 'text-ivory/70 hover:text-ivory' ?>"><?= e($label) ?></a>
      <?php endforeach ?>
    </nav>
    <div class="flex gap-4 border-t border-white/10 p-5 text-xs text-ivory/60">
      <a href="/" target="_blank" class="hover:text-ivory">View store ↗</a>
      <form method="post" action="/admin/logout"><?= csrf_field() ?><button class="hover:text-ivory">Sign out</button></form>
    </div>
  </aside>
  <main class="min-w-0 p-4 md:p-10"><?= $content ?></main>
</div>
<script src="/assets/admin.js?v=<?= @filemtime(ROOT . '/public/assets/admin.js') ?>" defer></script>
</body>
</html>
