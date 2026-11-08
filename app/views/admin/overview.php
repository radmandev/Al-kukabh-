<?php
/** @var array $s @var array $recent @var array $month @var int $pending @var int $products @var array $low @var int $customers @var int $subs */
$stat = function (string $label, string $value, ?string $href = null): string {
    $body = '<p class="text-xs text-stone">' . e($label) . '</p><p class="display mt-2 text-3xl">' . e($value) . '</p>';
    return $href ? '<a href="' . $href . '" class="' . A_CARD . ' hover:border-ink">' . $body . '</a>' : '<div class="' . A_CARD . '">' . $body . '</div>';
};
?>
<?= page_head('Overview') ?>
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
  <?= $stat('Revenue · 30 days', money($month['total'], $s['currency'], 'en')) ?>
  <?= $stat('Orders · 30 days', (string) $month['n'], '/admin/orders') ?>
  <?= $stat('Awaiting review', (string) $pending, '/admin/orders?status=pending') ?>
  <?= $stat('Live products', (string) $products, '/admin/products') ?>
  <?= $stat('Customers', (string) $customers, '/admin/customers') ?>
  <?= $stat('Newsletter subscribers', (string) $subs) ?>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
  <section class="<?= A_CARD ?>">
    <h2 class="mb-4 font-medium">Latest orders</h2>
    <ul class="divide-y divide-black/5 text-sm">
      <?php foreach ($recent as $o): ?>
        <li><a href="/admin/orders/<?= e($o['id']) ?>" class="flex flex-wrap items-center gap-3 py-2.5 hover:bg-black/[.02]">
          <span class="w-28 font-medium"><?= e($o['number']) ?></span><span class="flex-1"><?= e($o['name']) ?></span>
          <?= badge($o['status'], tone_for($o['status'])) ?><span class="w-28 text-end"><?= e(money($o['total'], $o['currency'], 'en')) ?></span>
        </a></li>
      <?php endforeach ?>
      <?php if (!$recent): ?><li class="py-6 text-stone">No orders yet.</li><?php endif ?>
    </ul>
  </section>
  <section class="<?= A_CARD ?>">
    <h2 class="mb-4 font-medium">Low stock (≤ 3)</h2>
    <ul class="space-y-2 text-sm">
      <?php foreach ($low as $p): ?><li class="flex justify-between"><a href="/admin/products/<?= e($p['id']) ?>" class="hover:underline"><?= e($p['nameEn']) ?></a><span class="text-red-700"><?= (int) $p['stock'] ?></span></li><?php endforeach ?>
      <?php if (!$low): ?><li class="text-stone">All good.</li><?php endif ?>
    </ul>
  </section>
</div>
