<?php /** @var array $orders @var ?string $status @var ?string $q */
$opts = '<option value="">All statuses</option>';
foreach (ORDER_STATUSES as $s) $opts .= '<option' . ($s === $status ? ' selected' : '') . '>' . $s . '</option>';
?>
<?= page_head('Orders', '<form class="flex gap-2"><select name="status" class="' . A_INP . '">' . $opts . '</select>'
    . '<input name="q" value="' . e($q) . '" placeholder="Order #, name, email, phone" class="' . A_INP . '">'
    . '<button class="rounded bg-ink px-3 text-sm text-ivory">Filter</button></form>') ?>
<div class="<?= A_CARD ?> overflow-x-auto p-0 md:p-0">
  <table class="w-full min-w-[800px] text-sm">
    <thead class="border-b border-black/10 text-xs text-stone"><tr><?php foreach (['Order', 'Date', 'Customer', 'Total', 'Payment', 'Status', 'Shipping'] as $h): ?><th class="p-3 text-start font-medium"><?= $h ?></th><?php endforeach ?></tr></thead>
    <tbody class="divide-y divide-black/5">
      <?php foreach ($orders as $o): ?>
        <tr class="hover:bg-black/[.02]">
          <td class="p-3"><a href="/admin/orders/<?= e($o['id']) ?>" class="font-medium hover:underline"><?= e($o['number']) ?></a></td>
          <td class="p-3 text-stone"><?= e(admin_datetime($o['createdAt'])) ?></td>
          <td class="p-3"><?= e($o['name']) ?><div class="text-xs text-stone"><?= e($o['city']) ?>, <?= e($o['country']) ?></div></td>
          <td class="p-3"><?= e(money($o['total'], $o['currency'], 'en')) ?></td>
          <td class="p-3"><?= badge($o['paymentStatus'], tone_for($o['paymentStatus'])) ?> <span class="text-xs text-stone"><?= e($o['paymentMethod']) ?></span></td>
          <td class="p-3"><?= badge($o['status'], tone_for($o['status'])) ?></td>
          <td class="p-3"><?= badge($o['shipStatus'], tone_for($o['shipStatus'])) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$orders): ?><p class="p-10 text-center text-stone">No orders found.</p><?php endif ?>
</div>
