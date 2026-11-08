<?php
/** @var array $c @var ?string $error @var array $media @var array $products */
$isNew = !$c['id'];
$slot = function (string $k, string $label) use ($c, $media): string {
    $m = $media[$c[$k] ?? ''] ?? null;
    return '<div data-media-slot="id"><p class="mb-2 text-xs font-medium text-stone">' . e($label) . '</p>'
        . '<input type="hidden" name="' . $k . '" value="' . e($c[$k] ?? '') . '">'
        . '<div data-slot-preview data-class="aspect-[4/5] w-40 rounded">' . ($m ? thumb($m, 'aspect-[4/5] w-40 rounded') : '<div class="grid aspect-[4/5] w-40 place-items-center rounded border border-dashed border-black/20 text-xs text-stone">None</div>') . '</div>'
        . '<div class="mt-2 flex gap-3 text-sm"><button type="button" class="underline" data-slot-pick>' . ($m ? 'Replace' : 'Choose / upload') . '</button>'
        . '<button type="button" class="text-red-700"' . ($m ? '' : ' hidden') . ' data-slot-clear>Remove</button></div></div>';
};
$inp = fn (string $k, string $extra = '') => '<input name="' . $k . '" class="' . A_INP . '" value="' . e($c[$k]) . '" ' . $extra . '>';
$area = fn (string $k, string $extra = '') => '<textarea name="' . $k . '" rows="3" class="' . A_INP . '" ' . $extra . '>' . e($c[$k]) . '</textarea>';
?>
<?= page_head($isNew ? 'New collection' : $c['nameEn'], '<a href="/admin/collections" class="' . A_BTN2 . '">← All collections</a>') ?>
<form method="post" action="/admin/collections/<?= $isNew ? 'new' : e($c['id']) ?>" class="grid gap-6 xl:grid-cols-[1fr_320px]">
  <?= csrf_field() ?>
  <div class="space-y-6">
    <section class="<?= A_CARD ?> space-y-4">
      <div class="grid gap-4 md:grid-cols-2">
        <?= field('Name (Arabic)', $inp('nameAr', 'dir="rtl"')) ?>
        <?= field('Name (English)', $inp('nameEn')) ?>
        <?= field('Description (Arabic)', $area('descAr', 'dir="rtl"')) ?>
        <?= field('Description (English)', $area('descEn')) ?>
      </div>
      <?= field('URL slug', $inp('slug'), 'Leave empty to generate from English name') ?>
    </section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Cover (image or video)</h2><div class="flex flex-wrap gap-8"><?= $slot('coverId', 'Desktop') ?><?= $slot('coverMobileId', 'Mobile (optional)') ?></div></section>
    <section class="<?= A_CARD ?>"><h2 class="mb-4 font-medium">Products in this collection</h2>
      <div data-item-picker="<?= attr_json($products) ?>"><input type="hidden" name="productIds" value="<?= attr_json($c['productIds']) ?>"></div>
    </section>
  </div>
  <div class="space-y-4 xl:sticky xl:top-6 xl:self-start">
    <section class="<?= A_CARD ?>">
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="published" class="accent-ink"<?= $c['published'] ? ' checked' : '' ?>>Published</label>
      <div class="mt-4 flex items-center gap-3"><button class="<?= A_BTN ?>">Save collection</button>
        <?php if ($error): ?><span class="text-sm text-red-700"><?= e($error) ?></span><?php elseif (isset($_GET['saved'])): ?><span class="text-sm text-stone">Saved</span><?php endif ?></div>
    </section>
    <?php if (!$isNew): ?><button formaction="/admin/collections/<?= e($c['id']) ?>/delete" formnovalidate data-confirm="Delete collection? Products are kept." class="<?= A_DANGER ?>">Delete collection</button><?php endif ?>
  </div>
</form>
