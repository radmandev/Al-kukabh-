<?php /** @var array $me @var array $staff */ ?>
<?= page_head('Staff & roles') ?>
<p class="-mt-4 mb-6 text-sm text-stone"><b>Admin</b>: everything incl. settings &amp; staff. <b>Editor</b>: products, collections, homepage, media, orders, customers, discounts.</p>
<?php if (isset($_GET['invalid'])): ?><p class="mb-4 text-sm text-red-700">Enter a name, a valid email and a password of at least 8 characters.</p><?php endif ?>
<div class="<?= A_CARD ?> mb-6">
  <ul class="divide-y divide-black/5 text-sm">
    <?php foreach ($staff as $s): ?>
      <li class="flex flex-wrap items-center gap-3 py-2">
        <span class="flex-1"><?= e($s['name']) ?> <span class="text-stone">· <?= e($s['email']) ?></span></span>
        <?= badge($s['role'], $s['role'] === 'ADMIN' ? 'gold' : 'neutral') ?>
        <?php if ($s['id'] !== $me['id']): ?>
          <?= post_button('/admin/staff/' . e($s['id']) . '/role', 'Make ' . ($s['role'] === 'ADMIN' ? 'editor' : 'admin'), A_BTN2, ['role' => $s['role'] === 'ADMIN' ? 'EDITOR' : 'ADMIN']) ?>
          <?= post_button('/admin/staff/' . e($s['id']) . '/role', 'Revoke access', 'text-red-700', ['role' => 'CUSTOMER'], 'Revoke dashboard access for this person?') ?>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ul>
</div>
<form method="post" action="/admin/staff" class="<?= A_CARD ?> grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
  <?= csrf_field() ?>
  <?= field('Name', '<input name="name" required class="' . A_INP . '">') ?>
  <?= field('Email', '<input name="email" type="email" required class="' . A_INP . '">') ?>
  <?= field('Password (min 8)', '<input name="password" type="password" minlength="8" required class="' . A_INP . '" autocomplete="new-password">') ?>
  <?= field('Role', '<select name="role" class="' . A_INP . '"><option>EDITOR</option><option>ADMIN</option></select>') ?>
  <button class="<?= A_BTN ?>">Add / update staff</button>
</form>
