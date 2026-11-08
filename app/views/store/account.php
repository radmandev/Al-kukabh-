<?php /** @var string $l @var array $t @var array $u @var array $orders */ ?>
<div class="mx-auto max-w-4xl px-4 py-16 md:py-24">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div><p class="eyebrow text-gold"><?= e($t['account']) ?></p><h1 class="display mt-2 text-4xl"><?= e($u['name']) ?></h1></div>
    <div class="flex gap-6 text-sm">
      <?php if ($u['role'] !== 'CUSTOMER'): ?><a href="/admin" class="link-u">Dashboard</a><?php endif ?>
      <form method="post" action="/<?= $l ?>/logout"><?= csrf_field() ?><button class="link-u"><?= e($t['logout']) ?></button></form>
    </div>
  </div>
  <h2 class="tracked mt-16 text-xs"><?= e($t['myOrders']) ?></h2>
  <?php if (!$orders): ?>
    <p class="mt-6 text-stone">—</p>
  <?php else: ?>
    <ul class="mt-6 divide-y divide-line border-y border-line">
      <?php foreach ($orders as $o): ?>
        <li>
          <a href="/<?= $l ?>/order/<?= e($o['number']) ?>?e=<?= e(rawurlencode($o['email'])) ?>" class="grid grid-cols-2 gap-2 py-5 text-sm hover:bg-sand sm:grid-cols-4">
            <span><?= e($o['number']) ?></span><span class="text-stone"><?= e(local_date($o['createdAt'], $l)) ?></span>
            <span><?= e(status_label($o['status'], $l)) ?></span><span class="text-end"><?= e(money($o['total'], $o['currency'], $l)) ?></span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>
  <?php endif ?>
</div>
