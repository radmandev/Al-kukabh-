<?php
/** @var string $l @var array $c @var array $ps @var array $media @var string $currency */
$t = dict($l);
$cv = $media[$c['coverId'] ?? ''] ?? null;
$mob = $media[$c['coverMobileId'] ?? ''] ?? null;
$desc = L($c, 'desc', $l);
?>
<section class="relative h-[70svh] min-h-[420px] overflow-hidden bg-ink text-ivory">
  <div class="kenburns absolute inset-0"><?= responsive_media($cv ? ['url' => $cv['url'], 'kind' => $cv['kind']] : null, $mob ? ['url' => $mob['url'], 'kind' => $mob['kind']] : null, '100vw', true) ?></div>
  <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/10"></div>
  <div class="absolute inset-x-0 bottom-0 mx-auto max-w-2xl px-6 pb-16 text-center">
    <p class="eyebrow reveal text-gold-soft"><?= e($t['collections']) ?></p>
    <h1 class="display reveal mt-3 text-5xl md:text-7xl"><?= e(L($c, 'name', $l)) ?></h1>
    <?php if ($desc !== ''): ?><p class="reveal mt-5 text-sm leading-7 text-ivory/80"><?= e($desc) ?></p><?php endif ?>
  </div>
</section>
<div class="mx-auto max-w-[1600px] px-4 py-16 md:px-10 md:py-24"><?= product_grid($ps, $l, $currency) ?></div>
