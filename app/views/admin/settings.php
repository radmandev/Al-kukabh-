<?php
/** @var string $tab @var array $s */
$human = fn (string $k) => preg_replace(['/ Ar$/', '/ En$/'], [' (Arabic)', ' (English)'], ucfirst(preg_replace('/([A-Z])/', ' $1', $k)));
$long = fn (string $k) => (bool) preg_match('/about|policy|shipping[AE]|returns|privacy|terms|bankDetails|desc[AE]/i', $k);
$v = $s[$tab];
$control = function (string $k, mixed $val) use ($human, $long, $tab): string {
    if (is_bool($val)) {
        return '<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="' . e($k) . '"' . ($val ? ' checked' : '') . '>' . e($human($k)) . '</label>';
    }
    if ($k === 'logoUrl' || $k === 'ogImage') {
        $img = $val ? img_fixed((string) $val, 80, '', 'h-20 w-20 rounded-full object-cover') : '';
        return field($human($k), '<div class="flex items-center gap-4" data-media-slot="url" data-kind="image"><input type="hidden" name="' . $k . '" value="' . e($val) . '">'
            . '<div data-slot-preview data-round>' . $img . '</div><button type="button" class="underline" data-slot-pick>Choose / upload</button>'
            . '<button type="button" class="text-sm text-red-700"' . ($val ? '' : ' hidden') . ' data-slot-clear>Remove</button></div>', '', 'md:col-span-2');
    }
    if ($k === 'gateway') {
        $o = '';
        foreach (['none', 'tap', 'moyasar', 'stripe', 'hyperpay'] as $g) $o .= '<option' . ($g === $val ? ' selected' : '') . '>' . $g . '</option>';
        return field('Card gateway', '<select name="gateway" class="' . A_INP . '">' . $o . '</select>');
    }
    $dir = str_ends_with($k, 'Ar') ? ' dir="rtl"' : '';
    $label = $tab === 'currency' ? SETTINGS_TABS[$tab][0] : $human($k);
    if ($long($k)) return field($label, '<textarea name="' . e($k) . '" rows="6" class="' . A_INP . '"' . $dir . '>' . e($val) . '</textarea>', '', 'md:col-span-2');
    $type = is_int($val) || is_float($val) ? 'number" step="any' : 'text';
    return field($label, '<input name="' . e($k) . '" type="' . $type . '" class="' . A_INP . '"' . $dir . ' value="' . e($val) . '">');
};
?>
<?= page_head('Settings') ?>
<div class="grid gap-6 lg:grid-cols-[200px_1fr]">
  <nav class="flex gap-1 overflow-x-auto lg:flex-col">
    <?php foreach (SETTINGS_TABS as $k => [$label]): ?>
      <a href="/admin/settings?tab=<?= $k ?>" class="whitespace-nowrap rounded px-3 py-2 text-start text-sm <?= $tab === $k ? 'bg-ink text-ivory' : 'hover:bg-black/5' ?>"><?= e($label) ?></a>
    <?php endforeach ?>
  </nav>
  <form method="post" action="/admin/settings" class="<?= A_CARD ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <?php if (SETTINGS_TABS[$tab][1]): ?><p class="mb-4 text-sm text-stone"><?= e(SETTINGS_TABS[$tab][1]) ?></p><?php endif ?>
    <div class="grid gap-4 md:grid-cols-2">
      <?php if (is_array($v)) { foreach ($v as $k => $x) echo $control($k, $x); } else echo $control($tab, $v); ?>
    </div>
    <div class="mt-6 flex items-center gap-3">
      <button class="<?= A_BTN ?>">Save</button>
      <?php if (isset($_GET['saved'])): ?><span class="text-sm text-emerald-700">Saved ✓</span><?php endif ?>
    </div>
  </form>
</div>

<?php if (query('restored')): ?>
  <p class="mt-8 rounded border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900">✓ تمت استعادة الصور والمحتوى بنجاح. Photos and store content restored.</p>
<?php elseif (query('restore_failed')): ?>
  <p class="mt-8 rounded border border-red-300 bg-red-50 p-4 text-sm text-red-900">تعذّرت الاستعادة. Restore failed — check the server error log.</p>
<?php endif ?>
<section class="mt-10 rounded-md border border-amber-300 bg-amber-50 p-5 md:p-6">
  <h2 class="font-medium">استعادة الصور ومحتوى المتجر · Restore photos &amp; store content</h2>
  <p class="mt-2 text-sm leading-7 text-stone">
    إذا ظهرت صور المتجر فارغة (مثلاً بعد نقل السيرفر)، هذا الزر يعيد بناء الصور والعبايات والمجموعات والصفحة الرئيسية من ملفات العلامة الموجودة مع الموقع.
    لا يمسّ الطلبات ولا العملاء ولا الإعدادات. ⚠️ أي عباية أو صورة أضفتِها يدوياً من لوحة التحكم ستُحذف ويُعاد المتجر لمحتواه الأساسي.<br>
    Rebuilds photos, abayas, collections and the homepage from the brand files shipped with the site. Orders, customers and settings are kept;
    products or media added by hand in the dashboard are replaced.
  </p>
  <form method="post" action="/admin/settings/restore" class="mt-4" onsubmit="return confirm('استعادة الصور ومحتوى المتجر؟ قد تستغرق دقيقة.\nRestore photos and store content? This can take a minute.')">
    <?= csrf_field() ?>
    <button class="<?= A_BTN ?>">استعادة الآن · Restore now</button>
  </form>
</section>
