<?php
/** @var array $c @var array $orders */
$spent = array_sum(array_map(fn ($o) => $o['status'] !== 'cancelled' ? (float) $o['total'] : 0, $orders));
?>
<?= page_head($c['name'], '<a href="/admin/customers" class="' . A_BTN2 . '">← Customers</a>') ?>
<div class="grid gap-6 lg:grid-cols-[320px_1fr]">
  <section class="<?= A_CARD ?> space-y-1 text-sm">
    <p><?= e($c['email']) ?></p><p dir="ltr" class="text-start"><?= e($c['phone'] ?: '—') ?></p>
    <p class="text-stone">Joined <?= e(admin_date($c['createdAt'])) ?></p>
    <p class="pt-3 font-medium"><?= count($orders) ?> orders · <?= $orders ? e(money($spent, $orders[0]['currency'], 'en')) : '—' ?> spent</p>
  </section>
  <section class="<?= A_CARD ?>">
    <h2 class="mb-3 font-medium">Order history</h2>
    <ul class="divide-y divide-black/5 text-sm">
      <?php foreach ($orders as $o): ?>
        <li><a href="/admin/orders/<?= e($o['id']) ?>" class="flex gap-4 py-2 hover:bg-black/[.02]"><span class="w-28 font-medium"><?= e($o['number']) ?></span><span class="flex-1 text-stone"><?= e(admin_date($o['createdAt'])) ?></span><?= badge($o['status'], tone_for($o['status'])) ?><span class="w-28 text-end"><?= e(money($o['total'], $o['currency'], 'en')) ?></span></a></li>
      <?php endforeach ?>
    </ul>
  </section>
</div>
