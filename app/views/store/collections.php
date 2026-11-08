<?php /** @var string $l @var array $t @var array $cols @var array $media */ ?>
<div class="pb-24">
  <header class="py-14 text-center md:py-20"><h1 class="display text-4xl md:text-6xl"><?= e($t['collections']) ?></h1></header>
  <div class="space-y-4 px-4 md:space-y-6 md:px-10">
    <?php foreach ($cols as $i => $c): $cv = $media[$c['coverId'] ?? ''] ?? null; ?>
      <a href="/<?= $l ?>/collections/<?= e(rawurlencode($c['slug'])) ?>" class="group grid items-center gap-6 md:grid-cols-2 md:gap-16 <?= $i % 2 ? 'md:[&>*:first-child]:order-2' : '' ?>">
        <div class="relative aspect-[4/5] overflow-hidden bg-sand md:aspect-[5/6]">
          <div class="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-[1.03]"><?= media_el($cv ? ['url' => $cv['url'], 'kind' => $cv['kind']] : null, '50vw', $i === 0) ?></div>
        </div>
        <div class="px-2 pb-10 text-center md:pb-0">
          <p class="eyebrow text-gold"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></p>
          <h2 class="display mt-3 text-4xl md:text-6xl"><?= e(L($c, 'name', $l)) ?></h2>
          <p class="mx-auto mt-5 max-w-md text-sm leading-7 text-stone"><?= e(L($c, 'desc', $l)) ?></p>
          <span class="eyebrow link-u mt-8 inline-block"><?= e($t['discover']) ?></span>
        </div>
      </a>
    <?php endforeach ?>
  </div>
</div>
