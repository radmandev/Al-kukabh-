<?php
/** @var string $l @var array $p @var string $currency */
$t = dict($l);
$name = L($p, 'name', $l);
?>
<div class="fixed inset-0 z-[90] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="<?= e($name) ?>" data-modal>
  <div class="absolute inset-0 bg-black/50" data-modal-close></div>
  <div class="reveal relative grid max-h-[90vh] w-full max-w-4xl overflow-y-auto bg-ivory md:grid-cols-2">
    <button type="button" data-modal-close class="absolute end-4 top-4 z-10" aria-label="<?= e($t['close']) ?>"><?= icon('close') ?></button>
    <div class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto">
      <?php foreach ($p['gallery'] as $m): if ($m['kind'] !== 'image') continue; ?>
        <div class="relative aspect-[3/4] w-full shrink-0 snap-center"><?= img($m['url'], L($m, 'alt', $l), 'object-cover', '50vw') ?></div>
      <?php endforeach ?>
    </div>
    <div class="p-8 md:p-10">
      <h2 class="display text-3xl"><?= e($name) ?></h2>
      <?= price_html($p, $l, $currency, 'mt-3 text-lg') ?>
      <div class="mt-8"><?= add_to_bag($p, $l, true) ?></div>
      <a href="/<?= $l ?>/product/<?= e(rawurlencode($p['slug'])) ?>" class="link-u mt-8 inline-block text-sm"><?= e($t['details']) ?> →</a>
    </div>
  </div>
</div>
