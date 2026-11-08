<?php /** @var string $l @var array $t @var array $s */ ?>
<header class="mx-auto max-w-2xl px-6 py-20 text-center md:py-32">
  <?= img_fixed($s['brand']['logoUrl'], 120, '', 'mx-auto h-28 w-28 rounded-full object-cover') ?>
  <p class="eyebrow mt-10 text-gold"><?= e($t['story']) ?></p>
  <h1 class="display mt-4 text-4xl md:text-6xl"><?= e(L($s['brand'], 'name', $l)) ?></h1>
  <p class="mt-8 text-base leading-8 text-stone"><?= e(L($s['brand'], 'about', $l)) ?></p>
</header>
