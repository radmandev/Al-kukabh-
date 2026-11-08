<?php
/** @var string $l @var array $t @var ?string $next @var array $errors @var array $old */
$msg = fn (?string $k) => $k ? '<p role="alert" class="text-sm text-red-700">' . e($t[$k] ?? $k) . '</p>' : '';
$f = fn (string $name, string $label, string $attrs = '') => '<label class="block"><span class="eyebrow text-stone">' . e($label) . '</span><input name="' . $name . '" class="field" ' . $attrs . '></label>';
$oldv = fn (string $k) => e(is_string($old[$k] ?? null) ? $old[$k] : '');
?>
<div class="mx-auto grid max-w-5xl gap-16 px-4 py-16 md:grid-cols-2 md:py-24">
  <form method="post" action="/<?= $l ?>/login" class="space-y-6">
    <h1 class="display text-4xl"><?= e($t['login']) ?></h1>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next ?? '') ?>">
    <?= $f('email', $t['email'], 'type="email" required autocomplete="email" dir="ltr"' . (isset($errors['login']) ? ' value="' . $oldv('email') . '"' : '')) ?>
    <?= $f('password', $t['password'], 'type="password" required autocomplete="current-password"') ?>
    <?= $msg($errors['login'] ?? null) ?>
    <button class="btn btn-dark w-full"><?= e($t['login']) ?></button>
  </form>
  <form method="post" action="/<?= $l ?>/register" class="space-y-6">
    <h2 class="display text-4xl"><?= e($t['register']) ?></h2>
    <?= csrf_field() ?>
    <?php $keep = isset($errors['register']); ?>
    <?= $f('name', $t['name'], 'required autocomplete="name"' . ($keep ? ' value="' . $oldv('name') . '"' : '')) ?>
    <?= $f('email', $t['email'], 'type="email" required autocomplete="email" dir="ltr"' . ($keep ? ' value="' . $oldv('email') . '"' : '')) ?>
    <?= $f('phone', $t['phone'], 'type="tel" autocomplete="tel" dir="ltr"' . ($keep ? ' value="' . $oldv('phone') . '"' : '')) ?>
    <?= $f('password', $t['password'], 'type="password" required minlength="8" autocomplete="new-password"') ?>
    <?= $msg($errors['register'] ?? null) ?>
    <button class="btn w-full"><?= e($t['register']) ?></button>
  </form>
</div>
