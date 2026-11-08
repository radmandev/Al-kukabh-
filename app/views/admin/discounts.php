<?php /** @var array $ds */ ?>
<?= page_head('Discount codes') ?>
<?php if (isset($_GET['invalid'])): ?><p class="mb-4 text-sm text-red-700">Enter a code (2+ characters) and a value above 0.</p><?php endif ?>
<form method="post" action="/admin/discounts" class="<?= A_CARD ?> mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-7 lg:items-end">
  <?= csrf_field() ?>
  <?= field('Code', '<input name="code" required minlength="2" class="' . A_INP . ' uppercase">') ?>
  <?= field('Type', '<select name="type" class="' . A_INP . '"><option value="percent">% off</option><option value="fixed">Fixed amount</option></select>') ?>
  <?= field('Value', '<input name="value" type="number" step="0.01" min="0.01" required class="' . A_INP . '">') ?>
  <?= field('Min. order', '<input name="minTotal" type="number" min="0" value="0" class="' . A_INP . '">') ?>
  <?= field('Max uses', '<input name="maxUses" type="number" min="1" placeholder="∞" class="' . A_INP . '">') ?>
  <?= field('Expires', '<input name="expiresAt" type="date" class="' . A_INP . '">') ?>
  <button class="<?= A_BTN ?>">Save code</button>
</form>
<div class="<?= A_CARD ?> overflow-x-auto">
  <table class="w-full min-w-[600px] text-sm">
    <thead class="text-xs text-stone"><tr><?php foreach (['Code', 'Discount', 'Min. order', 'Used', 'Expires', '', ''] as $h): ?><th class="pb-2 text-start font-medium"><?= $h ?></th><?php endforeach ?></tr></thead>
    <tbody class="divide-y divide-black/5">
      <?php foreach ($ds as $d): $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); ?>
        <tr>
          <td class="py-2 font-mono"><?= e($d['code']) ?></td>
          <td><?= $d['type'] === 'percent' ? $num($d['value']) . '%' : $num($d['value']) ?></td>
          <td><?= (float) $d['minTotal'] ? $num($d['minTotal']) : '—' ?></td>
          <td><?= (int) $d['used'] ?><?= $d['maxUses'] ? ' / ' . (int) $d['maxUses'] : '' ?></td>
          <td><?= e(admin_date($d['expiresAt'])) ?></td>
          <td><?= post_button('/admin/discounts/' . e($d['id']) . '/toggle', $d['active'] ? badge('Active', 'good') : badge('Off'), A_BTN2) ?></td>
          <td><?= post_button('/admin/discounts/' . e($d['id']) . '/delete', 'Delete', 'text-red-700', [], 'Delete this code?') ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$ds): ?><p class="py-6 text-center text-stone">No codes yet.</p><?php endif ?>
</div>
