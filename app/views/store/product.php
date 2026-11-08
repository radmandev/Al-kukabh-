<?php
/**
 * @var string $l @var array $t @var array $s @var array $p @var array $posters
 * @var ?array $collection @var array $related @var string $name
 */
$items = array_map(fn ($m) => [
    'url' => $m['url'], 'kind' => $m['kind'],
    'poster' => $m['posterId'] ? ($posters[$m['posterId']]['url'] ?? null) : null,
    'alt' => L($m, 'alt', $l) ?: $name,
], $p['gallery']);
$slide = fn (array $g, bool $priority, string $sizes) => $g['kind'] === 'video'
    ? '<video src="' . e($g['url']) . '"' . ($g['poster'] ? ' poster="' . e(img_url($g['poster'], 1080)) . '"' : '') . ' class="absolute inset-0 h-full w-full object-cover" muted loop playsinline autoplay aria-label="' . e($g['alt']) . '"></video>'
    : img($g['url'], $g['alt'], 'object-cover', $sizes, $priority);
$acc = array_filter([
    [$t['description'], L($p, 'desc', $l)], [$t['fabric'], L($p, 'fabric', $l)], [$t['details'], L($p, 'details', $l)],
    [$t['care'], L($p, 'care', $l)], [$t['delivery'], L($p, 'delivery', $l)],
], fn ($x) => $x[1] !== '');
$n = count($items);
?>
<div class="mx-auto grid max-w-[1600px] gap-8 md:grid-cols-[1.25fr_1fr] md:gap-12 md:px-10 md:py-10 lg:gap-20">
  <?php if (!$items): ?>
    <div class="aspect-[3/4] bg-sand"></div>
  <?php else: ?>
  <div data-gallery="<?= attr_json($items) ?>" data-name="<?= e($name) ?>">
    <!-- mobile: swipe -->
    <div class="relative md:hidden">
      <div data-g-strip class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto">
        <?php foreach ($items as $j => $g): ?>
          <button type="button" data-g-open="<?= $j ?>" class="relative aspect-[3/4] w-full shrink-0 snap-center bg-sand" aria-label="<?= e($name . ' ' . ($j + 1)) ?>"><?= $slide($g, $j === 0, '100vw') ?></button>
        <?php endforeach ?>
      </div>
      <div class="absolute inset-x-0 bottom-4 flex justify-center gap-1.5">
        <?php foreach ($items as $j => $_): ?><span data-g-dot class="h-1 rounded-full transition-all <?= $j === 0 ? 'w-5 bg-ink' : 'w-1 bg-ink/30' ?>"></span><?php endforeach ?>
      </div>
    </div>
    <!-- desktop: vertical thumbs + main -->
    <div class="hidden gap-4 md:grid md:grid-cols-[84px_1fr]">
      <div class="no-scrollbar flex max-h-[calc((100vw-5rem)*0.55*1.33)] flex-col gap-3 overflow-y-auto lg:max-h-[85vh]">
        <?php foreach ($items as $j => $g): ?>
          <button type="button" data-g-thumb="<?= $j ?>" aria-label="<?= e($name . ' ' . ($j + 1)) ?>" aria-current="<?= $j === 0 ? 'true' : 'false' ?>"
            class="relative aspect-[3/4] w-full shrink-0 bg-sand transition <?= $j === 0 ? 'opacity-100 ring-1 ring-ink' : 'opacity-60 hover:opacity-100' ?>">
            <?php if ($g['kind'] === 'video'): ?>
              <?= $g['poster'] ? img($g['poster'], '', 'object-cover', '84px') : '<video src="' . e($g['url']) . '" class="absolute inset-0 h-full w-full object-cover" muted preload="metadata"></video>' ?>
              <?= icon('play', 'absolute inset-0 m-auto h-5 w-5 text-ivory', true) ?>
            <?php else: ?>
              <?= img($g['url'], '', 'object-cover', '84px') ?>
            <?php endif ?>
          </button>
        <?php endforeach ?>
      </div>
      <button type="button" data-g-open-current class="group relative aspect-[3/4] cursor-zoom-in overflow-hidden bg-sand" aria-label="Open full screen">
        <div data-g-main class="reveal absolute inset-0"><?= $slide($items[0], true, '55vw') ?></div>
        <?= icon('expand', 'absolute bottom-4 end-4 h-5 w-5 opacity-0 transition group-hover:opacity-100') ?>
      </button>
    </div>
    <!-- full screen -->
    <div data-g-full hidden class="fixed inset-0 z-[100] bg-ivory" role="dialog" aria-modal="true" aria-label="<?= e($name) ?>">
      <div data-g-stage class="absolute inset-0 overflow-hidden"></div>
      <button type="button" data-g-close class="absolute end-5 top-5 bg-ivory/80 p-2" aria-label="Close"><?= icon('close', 'h-6 w-6') ?></button>
      <?php if ($n > 1): ?>
        <button type="button" data-g-step="-1" class="absolute start-4 top-1/2 -translate-y-1/2 bg-ivory/80 p-3" aria-label="Previous"><?= icon('arrow', 'h-5 w-5 rotate-180 rtl:rotate-0') ?></button>
        <button type="button" data-g-step="1" class="absolute end-4 top-1/2 -translate-y-1/2 bg-ivory/80 p-3" aria-label="Next"><?= icon('arrow', 'h-5 w-5 rtl:rotate-180') ?></button>
        <p data-g-count class="absolute inset-x-0 bottom-5 text-center text-xs tracking-widest"></p>
      <?php endif ?>
    </div>
  </div>
  <?php endif ?>

  <div class="px-4 md:px-0">
    <div class="md:sticky md:top-36">
      <nav class="eyebrow mb-6 text-stone" aria-label="Breadcrumb">
        <a href="/<?= $l ?>/shop" class="link-u"><?= e($t['shop']) ?></a>
        <?php if ($collection): ?> / <a href="/<?= $l ?>/collections/<?= e(rawurlencode($collection['slug'])) ?>" class="link-u"><?= e(L($collection, 'name', $l)) ?></a><?php endif ?>
      </nav>
      <div class="flex gap-2">
        <?php if ($p['isNew']): ?><span class="eyebrow border border-line px-2 py-1 text-[.58rem]"><?= e($t['new']) ?></span><?php endif ?>
        <?php if ($p['isLimited']): ?><span class="eyebrow bg-ink px-2 py-1 text-[.58rem] text-gold-soft"><?= e($t['limitedLabel']) ?></span><?php endif ?>
      </div>
      <h1 class="display mt-4 text-4xl md:text-5xl"><?= e($name) ?></h1>
      <?= price_html($p, $l, $s['currency'], 'mt-4 text-lg') ?>
      <p class="mt-3 flex items-center gap-2 text-sm text-stone">
        <?= icon('truck', 'h-4 w-4') ?>
        <?= e($p['availability'] === 'out_of_stock' ? $t['outOfStock'] : ($p['availability'] === 'made_to_order' ? $t['madeToOrder'] : $t['inStock'])) ?>
      </p>
      <div id="buy" class="mt-10"><?= add_to_bag($p, $l) ?></div>
      <div class="mt-10 divide-y divide-line border-y border-line">
        <?php foreach (array_values($acc) as $i => [$k, $v]): ?>
          <details class="group py-5"<?= $i === 0 ? ' open' : '' ?>>
            <summary class="eyebrow flex cursor-pointer list-none items-center justify-between"><?= e($k) ?><?= icon('plus', 'h-3.5 w-3.5 transition group-open:rotate-45') ?></summary>
            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-stone"><?= e($v) ?></p>
          </details>
        <?php endforeach ?>
      </div>
      <p class="mt-6 text-xs text-stone">SKU <?= e($p['sku']) ?></p>
    </div>
  </div>
</div>

<?php if ($related): ?>
<section class="mx-auto max-w-[1600px] px-4 py-20 md:px-10">
  <h2 class="display mb-10 text-center text-3xl md:text-4xl"><?= e($t['related']) ?></h2>
  <?= product_grid($related, $l, $s['currency']) ?>
</section>
<?php endif ?>

<!-- recently viewed: filled by store.js from localStorage -->
<section data-recent="<?= e($p['id']) ?>" hidden class="mx-auto max-w-[1600px] px-4 py-16 md:px-10">
  <h2 class="display mb-10 text-center text-3xl md:text-4xl"><?= e($t['recent']) ?></h2>
  <div data-recent-grid class="grid grid-cols-2 gap-x-3 gap-y-10 md:gap-x-6 lg:grid-cols-4"></div>
</section>

<!-- mobile-only bar that appears once the main buy block scrolls out of view -->
<div data-sticky-buy class="fixed inset-x-0 bottom-0 z-40 flex translate-y-full items-center gap-4 border-t border-line bg-ivory/95 px-4 py-3 backdrop-blur transition-transform duration-500 md:hidden">
  <div class="min-w-0 flex-1"><p class="truncate text-sm"><?= e($name) ?></p><?= price_html($p, $l, $s['currency'], 'text-xs text-stone') ?></div>
  <button type="button" data-scroll-buy class="btn btn-dark px-6 py-3"><?= e($t['addToBag']) ?></button>
</div>
