<?php /** @var array $users @var array $guests @var ?string $q */ ?>
<?= page_head('Customers', '<form><input name="q" value="' . e($q) . '" placeholder="Search" class="' . A_INP . '"></form>') ?>
<section class="<?= A_CARD ?> overflow-x-auto">
  <h2 class="mb-3 font-medium">Registered (<?= count($users) ?>)</h2>
  <table class="w-full min-w-[600px] text-sm"><tbody class="divide-y divide-black/5">
    <?php foreach ($users as $c): ?>
      <tr><td class="py-2"><a href="/admin/customers/<?= e($c['id']) ?>" class="font-medium hover:underline"><?= e($c['name']) ?></a></td><td class="text-stone"><?= e($c['email']) ?></td><td dir="ltr" class="text-stone"><?= e($c['phone']) ?></td><td><?= (int) $c['n'] ?> orders</td><td class="text-stone"><?= e(admin_date($c['createdAt'])) ?></td></tr>
    <?php endforeach ?>
  </tbody></table>
</section>
<section class="<?= A_CARD ?> mt-6 overflow-x-auto">
  <h2 class="mb-3 font-medium">Guest buyers</h2>
  <table class="w-full min-w-[500px] text-sm"><tbody class="divide-y divide-black/5">
    <?php foreach ($guests as $g): ?>
      <tr><td class="py-2"><?= e($g['name']) ?></td><td class="text-stone"><a href="/admin/orders?q=<?= e(rawurlencode($g['email'])) ?>" class="hover:underline"><?= e($g['email']) ?></a></td><td><?= (int) $g['n'] ?> orders</td></tr>
    <?php endforeach ?>
  </tbody></table>
</section>
