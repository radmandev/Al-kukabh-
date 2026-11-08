<?php
/** @var array $o @var ?array $customer */
$items = (array) jdecode($o['items'], []);
$f = fn ($v) => e(money($v, $o['currency'], 'en'));
$history = array_reverse((array) jdecode($o['history'], []));
$sel = function (string $name, array $opts) use ($o): string {
    $h = '<select name="' . $name . '" class="' . A_INP . '" data-track-change>';
    foreach ($opts as $x) $h .= '<option' . ($x === $o[$name] ? ' selected' : '') . '>' . $x . '</option>';
    return $h . '</select>';
};
?>
<?= page_head('Order ' . $o['number'], '<a href="/admin/orders" class="' . A_BTN2 . '">← Orders</a>') ?>
<div class="grid gap-6 xl:grid-cols-[1fr_340px]">
  <div class="space-y-6">
    <section class="<?= A_CARD ?>">
      <h2 class="mb-4 font-medium">Items</h2>
      <ul class="divide-y divide-black/5">
        <?php foreach ($items as $x): ?>
          <li class="flex items-center gap-4 py-3 text-sm">
            <?= !empty($x['image']) ? thumb(['url' => $x['image'], 'kind' => 'image'], 'aspect-[3/4] w-12 rounded') : '<div class="aspect-[3/4] w-12 rounded bg-black/5"></div>' ?>
            <div class="flex-1"><a href="/admin/products/<?= e($x['productId']) ?>" class="font-medium hover:underline"><?= e($x['nameEn']) ?></a><p class="text-xs text-stone">SKU <?= e($x['sku'] ?? '') ?> · Size <?= e(($x['size'] ?? '') ?: '—') ?> · <?= e(($x['color'] ?? '') ?: '—') ?></p></div>
            <span class="text-stone"><?= $f($x['price']) ?> × <?= (int) $x['qty'] ?></span><span class="w-24 text-end"><?= $f($x['price'] * $x['qty']) ?></span>
          </li>
        <?php endforeach ?>
      </ul>
      <dl class="ms-auto mt-4 max-w-xs space-y-1 text-sm">
        <div class="flex justify-between"><dt>Subtotal</dt><dd><?= $f($o['subtotal']) ?></dd></div>
        <?php if ((float) $o['discount'] > 0): ?><div class="flex justify-between"><dt>Discount (<?= e($o['discountCode']) ?>)</dt><dd>−<?= $f($o['discount']) ?></dd></div><?php endif ?>
        <div class="flex justify-between"><dt>Shipping</dt><dd><?= $f($o['shipping']) ?></dd></div>
        <div class="flex justify-between border-t border-black/10 pt-2 font-medium"><dt>Total</dt><dd><?= $f($o['total']) ?></dd></div>
      </dl>
    </section>
    <section class="<?= A_CARD ?> grid gap-6 text-sm md:grid-cols-2">
      <div><h2 class="mb-2 font-medium">Customer</h2><p><?= e($o['name']) ?></p><p><a class="underline" href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></p><p dir="ltr" class="text-start"><a class="underline" href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></p>
        <?php if ($customer): ?><a href="/admin/customers/<?= e($customer['id']) ?>" class="mt-2 inline-block text-xs underline">Customer account →</a><?php endif ?></div>
      <div><h2 class="mb-2 font-medium">Shipping address</h2><p><?= e($o['address']) ?></p><p><?= e($o['city']) ?>, <?= e($o['country']) ?></p><?php if ($o['notes']): ?><p class="mt-2 rounded bg-amber-50 p-2">Note: <?= e($o['notes']) ?></p><?php endif ?></div>
      <div><h2 class="mb-2 font-medium">Payment</h2><p><?= e(strtoupper($o['paymentMethod'])) ?> · <?= e($o['paymentStatus']) ?></p></div>
      <div><h2 class="mb-2 font-medium">Placed</h2><p><?= e(admin_datetime_full($o['createdAt'])) ?></p></div>
    </section>
    <section class="<?= A_CARD ?>">
      <h2 class="mb-4 font-medium">History</h2>
      <ol class="space-y-2 border-s border-black/10 ps-4 text-sm">
        <?php foreach ($history as $h): ?><li><span class="text-xs text-stone"><?= e(admin_datetime_full((string) $h['at'])) ?><?= !empty($h['by']) ? ' · ' . e($h['by']) : '' ?></span><p><?= e($h['note']) ?></p></li><?php endforeach ?>
      </ol>
    </section>
  </div>
  <section class="<?= A_CARD ?> xl:sticky xl:top-6 xl:self-start">
    <form method="post" action="/admin/orders/<?= e($o['id']) ?>" class="space-y-4" data-order-form data-was="<?= e($o['status']) ?>">
      <?= csrf_field() ?>
      <?= field('Order status', $sel('status', ORDER_STATUSES)) ?>
      <?= field('Payment status', $sel('paymentStatus', PAYMENT_STATUSES)) ?>
      <?= field('Shipping status', $sel('shipStatus', SHIP_STATUSES)) ?>
      <?= field('Tracking number / link', '<input name="tracking" class="' . A_INP . '" value="' . e($o['tracking']) . '">') ?>
      <?= field('Internal note (added to history)', '<textarea name="note" rows="2" class="' . A_INP . '"></textarea>') ?>
      <p data-cancel-warning hidden class="text-xs text-amber-800">Cancelling returns in-stock items to inventory.</p>
      <div class="flex items-center gap-3">
        <button class="<?= A_BTN ?>">Update order</button>
        <?php if (isset($_GET['saved'])): ?><span class="text-sm text-emerald-700">Updated ✓</span><?php endif ?>
      </div>
    </form>
  </section>
</div>
