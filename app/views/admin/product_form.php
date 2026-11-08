<?php
/** @var array $p @var bool $isNew @var ?string $error @var array $categories @var array $collections */
$text = function (string $k, string $label, array $o = []) use ($p): string {
    $dir = !empty($o['rtl']) ? ' dir="rtl"' : '';
    $v = e($p[$k] ?? '');
    $control = !empty($o['area'])
        ? '<textarea name="' . $k . '" rows="4"' . $dir . ' class="' . A_INP . '">' . $v . '</textarea>'
        : '<input name="' . $k . '"' . $dir . ' class="' . A_INP . '" value="' . $v . '">';
    return field($label, $control, $o['hint'] ?? '');
};
$pair = fn (string $k, string $label, bool $area = false) => '<div class="grid gap-4 md:grid-cols-2">' . $text($k . 'Ar', "$label (Arabic)", ['area' => $area, 'rtl' => true]) . $text($k . 'En', "$label (English)", ['area' => $area]) . '</div>';
$flag = fn (string $k, string $label) => '<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="' . $k . '"' . ($p[$k] ? ' checked' : '') . ' class="accent-ink">' . e($label) . '</label>';
$sel = function (string $name, array $opts, ?string $cur): string {
    $h = '<select name="' . $name . '" class="' . A_INP . '"><option value="">—</option>';
    foreach ($opts as $o) $h .= '<option value="' . e($o['id']) . '"' . ($o['id'] === $cur ? ' selected' : '') . '>' . e($o['nameEn']) . '</option>';
    return $h . '</select>';
};
$colorRow = fn (array $c) => '<div class="flex items-center gap-2" data-color-row>'
    . '<input type="color" name="colors_hex[]" value="' . e($c['hex'] ?? '#000000') . '" class="h-9 w-10 rounded border border-black/15" aria-label="Colour">'
    . '<input name="colors_ar[]" class="' . A_INP . '" dir="rtl" placeholder="الاسم" value="' . e($c['nameAr'] ?? '') . '">'
    . '<input name="colors_en[]" class="' . A_INP . '" placeholder="Name" value="' . e($c['nameEn'] ?? '') . '">'
    . '<button type="button" class="' . A_DANGER . '" data-color-remove>✕</button></div>';
$action = $isNew ? '/admin/products/new' : '/admin/products/' . e($p['id']);
?>
<?= page_head($isNew ? 'New product' : $p['nameEn'],
    '<a href="/admin/products" class="' . A_BTN2 . '">← All products</a>'
    . (!$isNew ? '<a href="/en/product/' . e(rawurlencode($p['slug'])) . '" target="_blank" class="' . A_BTN2 . '">View ↗</a>' : '')) ?>

<form method="post" action="<?= $action ?>" class="grid gap-6 xl:grid-cols-[1fr_340px]">
  <?= csrf_field() ?>
  <div class="space-y-6">
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Basics</h2>
      <div class="space-y-4"><?= $pair('name', 'Name') ?><?= $pair('desc', 'Description', true) ?></div></section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Images &amp; videos</h2>
      <div data-media-manager="<?= attr_json($p['gallery']) ?>"><input type="hidden" name="media" value="<?= attr_json(array_column($p['gallery'], 'id')) ?>"></div>
    </section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Product details</h2>
      <div class="space-y-4"><?= $pair('fabric', 'Fabric', true) ?><?= $pair('details', 'Details', true) ?><?= $pair('care', 'Care instructions', true) ?><?= $pair('delivery', 'Delivery info', true) ?></div></section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Sizes &amp; colours</h2>
      <?= field('Sizes', '<input name="sizes" class="' . A_INP . '" value="' . e(implode(', ', $p['sizes'])) . '">', 'Comma separated, e.g. 50, 52, 54, 56, 58 or S, M, L') ?>
      <div class="mt-4 space-y-2" data-colors>
        <span class="text-xs font-medium text-stone">Colours</span>
        <?php foreach ($p['colors'] as $c) echo $colorRow($c) ?>
        <template data-color-template><?= $colorRow(['hex' => '#000000']) ?></template>
        <button type="button" class="<?= A_BTN2 ?>" data-color-add>+ Add colour</button>
      </div>
    </section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">SEO</h2>
      <div class="space-y-4"><?= $text('slug', 'URL slug', ['hint' => 'Leave empty to generate from the English name']) ?><?= $text('seoTitle', 'SEO title', ['hint' => 'Defaults to product name']) ?><?= $text('seoDesc', 'SEO description', ['area' => true]) ?></div></section>
  </div>

  <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
    <section class="<?= A_CARD ?>">
      <div class="space-y-2"><?= $flag('published', 'Published (visible in store)') ?></div>
      <div class="mt-4 flex items-center gap-3">
        <button class="<?= A_BTN ?>">Save product</button>
        <?php if ($error): ?><span class="text-sm text-red-700"><?= e($error) ?></span><?php elseif (isset($_GET['saved'])): ?><span class="text-sm text-emerald-700">Saved</span><?php endif ?>
      </div>
    </section>
    <section class="<?= A_CARD ?> space-y-4"><h2 class="font-medium">Pricing &amp; stock</h2>
      <div class="grid grid-cols-2 gap-3">
        <?= field('Price', '<input type="number" name="price" min="0" step="0.01" class="' . A_INP . '" value="' . e($p['price']) . '">') ?>
        <?= field('Discount price', '<input type="number" name="salePrice" min="0" step="0.01" class="' . A_INP . '" value="' . e($p['salePrice'] ?? '') . '">') ?>
        <?= field('SKU', '<input name="sku" required class="' . A_INP . '" value="' . e($p['sku']) . '">') ?>
        <?= field('Stock qty', '<input type="number" name="stock" min="0" step="1" class="' . A_INP . '" value="' . e($p['stock']) . '">') ?>
      </div>
      <?php $av = fn ($v, $label) => '<option value="' . $v . '"' . ($p['availability'] === $v ? ' selected' : '') . '>' . $label . '</option>'; ?>
      <?= field('Availability', '<select name="availability" class="' . A_INP . '">' . $av('in_stock', 'In stock (uses stock qty)') . $av('made_to_order', 'Made to order') . $av('out_of_stock', 'Out of stock') . '</select>') ?>
    </section>
    <section class="<?= A_CARD ?> space-y-4"><h2 class="font-medium">Organisation</h2>
      <?= field('Category', $sel('categoryId', $categories, $p['categoryId'])) ?>
      <?= field('Collection', $sel('collectionId', $collections, $p['collectionId'])) ?>
      <div class="space-y-2"><?= $flag('isNew', 'New arrival') ?><?= $flag('isFeatured', 'Featured') ?><?= $flag('isLimited', 'Limited edition') ?><?= $flag('isReady', 'Ready to wear / ships now') ?></div>
    </section>
    <?php if (!$isNew): ?>
      <button formaction="/admin/products/<?= e($p['id']) ?>/delete" formnovalidate data-confirm="Delete this product permanently?" class="<?= A_DANGER ?>">Delete product</button>
    <?php endif ?>
  </div>
</form>
