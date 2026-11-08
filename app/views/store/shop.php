<?php
/** @var string $l @var array $t @var array $s @var array $cats @var array $q @var array $ps @var string $title @var array $allSizes */
$sel = 'border-0 border-b border-line bg-transparent py-2 pe-6 text-sm outline-none focus:border-ink';
$opt = fn (string $value, string $label, ?string $cur) => '<option value="' . e($value) . '"' . ($value === (string) $cur ? ' selected' : '') . '>' . e($label) . '</option>';
?>
<div class="mx-auto max-w-[1600px] px-4 pb-24 md:px-10">
  <header class="py-14 text-center md:py-20">
    <p class="eyebrow text-gold"><?= e($t['shop']) ?></p>
    <h1 class="display mt-3 text-4xl md:text-6xl"><?= e($title) ?></h1>
  </header>
  <form data-autoform class="mb-10 flex flex-wrap items-end justify-between gap-4 border-b border-line pb-4">
    <?php if ($q['q'] !== null): ?><input type="hidden" name="q" value="<?= e($q['q']) ?>"><?php endif ?>
    <?php if ($q['tag'] !== null): ?><input type="hidden" name="tag" value="<?= e($q['tag']) ?>"><?php endif ?>
    <div class="flex flex-wrap gap-4">
      <label class="sr-only" for="f-cat"><?= e($t['category']) ?></label>
      <select id="f-cat" name="category" class="<?= $sel ?>">
        <?= $opt('', $t['category'] . ': ' . $t['all'], $q['category']) ?>
        <?php foreach ($cats as $c) echo $opt($c['slug'], L($c, 'name', $l), $q['category']) ?>
      </select>
      <?php if ($allSizes): ?>
        <select name="size" class="<?= $sel ?>" aria-label="<?= e($t['size']) ?>">
          <?= $opt('', $t['size'] . ': ' . $t['all'], $q['size']) ?>
          <?php foreach ($allSizes as $x) echo $opt($x, $x, $q['size']) ?>
        </select>
      <?php endif ?>
      <select name="availability" class="<?= $sel ?>" aria-label="<?= e($t['availability']) ?>">
        <?= $opt('', $t['availability'] . ': ' . $t['all'], $q['availability']) ?>
        <?= $opt('in_stock', $t['inStock'], $q['availability']) ?>
        <?= $opt('made_to_order', $t['madeToOrder'], $q['availability']) ?>
      </select>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-xs text-stone"><?= count($ps) ?> <?= e($t['results']) ?></span>
      <select name="sort" class="<?= $sel ?>" aria-label="<?= e($t['sortBy']) ?>">
        <?= $opt('', $t['newest'], $q['sort']) ?>
        <?= $opt('priceAsc', $t['priceAsc'], $q['sort']) ?>
        <?= $opt('priceDesc', $t['priceDesc'], $q['sort']) ?>
      </select>
    </div>
    <noscript><button class="btn"><?= e($t['filter']) ?></button></noscript>
  </form>
  <?= product_grid($ps, $l, $s['currency']) ?>
</div>
