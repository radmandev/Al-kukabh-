<?php /** @var array $ps @var ?string $q @var array $s */ ?>
<?= page_head('Products', '<form><input name="q" value="' . e($q) . '" placeholder="Search name or SKU" class="' . A_INP . '"></form><a href="/admin/products/new" class="' . A_BTN . '">+ New product</a>') ?>
<div class="<?= A_CARD ?> overflow-x-auto p-0 md:p-0">
  <table class="w-full min-w-[760px] text-sm">
    <thead class="border-b border-black/10 text-start text-xs text-stone"><tr><?php foreach (['', 'Product', 'SKU', 'Price', 'Stock', 'Labels', 'Status'] as $h): ?><th class="p-3 text-start font-medium"><?= $h ?></th><?php endforeach ?></tr></thead>
    <tbody class="divide-y divide-black/5">
      <?php foreach ($ps as $p): ?>
        <tr class="hover:bg-black/[.02]">
          <td class="w-16 p-3"><?= $p['gallery'] ? thumb($p['gallery'][0], 'aspect-[3/4] w-12 rounded') : '<div class="aspect-[3/4] w-12 rounded bg-black/5"></div>' ?></td>
          <td class="p-3"><a href="/admin/products/<?= e($p['id']) ?>" class="font-medium hover:underline"><?= e($p['nameEn']) ?></a><div class="text-xs text-stone" dir="rtl"><?= e($p['nameAr']) ?></div><div class="text-xs text-stone"><?= e($p['collectionName'] ?? '') ?></div></td>
          <td class="p-3 text-stone"><?= e($p['sku']) ?></td>
          <td class="p-3"><?= e(money($p['salePrice'] ?? $p['price'], $s['currency'], 'en')) ?><?php if ($p['salePrice'] !== null): ?><s class="ms-2 text-xs text-stone"><?= e(money($p['price'], $s['currency'], 'en')) ?></s><?php endif ?></td>
          <td class="p-3"><?= $p['availability'] === 'in_stock' ? '<span class="' . ($p['stock'] <= 3 ? 'text-red-700' : '') . '">' . $p['stock'] . '</span>' : '<span class="text-xs text-stone">' . e(str_replace('_', ' ', $p['availability'])) . '</span>' ?></td>
          <td class="space-x-1 p-3"><?= ($p['isNew'] ? badge('New') : '') . ($p['isFeatured'] ? badge('Featured') : '') . ($p['isLimited'] ? badge('Limited', 'gold') : '') . ($p['isReady'] ? badge('Ready') : '') ?></td>
          <td class="p-3"><?= $p['published'] ? badge('Published', 'good') : badge('Draft') ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$ps): ?><p class="p-10 text-center text-stone">No products yet.</p><?php endif ?>
</div>
