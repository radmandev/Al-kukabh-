<?php
/** @var string $l @var array $t @var array $methods @var array $countries @var ?array $user @var array $shipping @var string $bankDetails */
$input = fn (string $name, string $label, string $attrs = '') => '<label class="block"><span class="eyebrow text-stone">' . e($label) . '</span><input name="' . $name . '" required class="field" ' . $attrs . '></label>';
?>
<div data-checkout="<?= attr_json(['shipping' => $shipping]) ?>">
  <form data-checkout-form hidden class="mx-auto grid max-w-[1300px] gap-12 px-4 py-12 md:grid-cols-[1.3fr_1fr] md:px-10 md:py-20 lg:gap-24">
    <div class="space-y-12">
      <h1 class="display text-4xl md:text-5xl"><?= e($t['checkout']) ?></h1>
      <?php if (!$user): ?><p class="text-sm text-stone"><a href="/<?= $l ?>/account?next=/<?= $l ?>/checkout" class="link-u text-ink"><?= e($t['login']) ?></a> <?= e($t['guest']) ?></p><?php endif ?>
      <fieldset class="space-y-6"><legend class="tracked mb-6 text-xs"><?= e($t['contactInfo']) ?></legend>
        <?= $input('name', $t['name'], 'value="' . e($user['name'] ?? '') . '" autocomplete="name"') ?>
        <div class="grid gap-6 sm:grid-cols-2">
          <?= $input('email', $t['email'], 'type="email" value="' . e($user['email'] ?? '') . '" autocomplete="email" dir="ltr"') ?>
          <?= $input('phone', $t['phone'], 'type="tel" value="' . e($user['phone'] ?? '') . '" autocomplete="tel" dir="ltr"') ?>
        </div>
      </fieldset>
      <fieldset class="space-y-6"><legend class="tracked mb-6 text-xs"><?= e($t['shippingAddress']) ?></legend>
        <div class="grid gap-6 sm:grid-cols-2">
          <label class="block"><span class="eyebrow text-stone"><?= e($t['country']) ?></span>
            <select name="country" required class="field"><?php foreach ($countries as $c): ?><option value="<?= e($c) ?>"><?= e(region_name($c, $l)) ?></option><?php endforeach ?></select></label>
          <?= $input('city', $t['city'], 'autocomplete="address-level2"') ?>
        </div>
        <?= $input('address', $t['address'], 'autocomplete="street-address"') ?>
        <label class="block"><span class="eyebrow text-stone"><?= e($t['notes']) ?></span><textarea name="notes" rows="2" class="field"></textarea></label>
      </fieldset>
      <fieldset><legend class="tracked mb-6 text-xs"><?= e($t['payment']) ?></legend>
        <div class="divide-y divide-line border border-line">
          <?php foreach ($methods as $i => $m): ?>
            <label class="flex cursor-pointer items-center gap-4 p-5">
              <input type="radio" name="pay" value="<?= e($m['id']) ?>"<?= $i === 0 ? ' checked' : '' ?> class="accent-ink">
              <span class="text-sm"><?= e($l === 'ar' ? $m['labelAr'] : $m['labelEn']) ?></span>
            </label>
          <?php endforeach ?>
        </div>
        <?php if ($bankDetails): ?><p data-co-bank <?= ($methods[0]['id'] ?? '') === 'bank' ? '' : 'hidden' ?> class="mt-4 whitespace-pre-line bg-sand p-5 text-sm leading-7"><?= e($bankDetails) ?></p><?php endif ?>
      </fieldset>
    </div>

    <aside class="h-fit space-y-6 bg-sand p-6 md:sticky md:top-32 md:p-10">
      <ul data-co-lines class="space-y-5"></ul>
      <div class="flex gap-3 border-t border-line pt-6">
        <input data-co-code placeholder="<?= e($t['discountCode']) ?>" aria-label="<?= e($t['discountCode']) ?>" class="field flex-1 uppercase">
        <button type="button" data-co-apply class="tracked text-xs"><?= e($t['apply']) ?></button>
      </div>
      <p data-co-code-err hidden class="text-sm text-red-700"><?= e($t['invalidCode']) ?></p>
      <dl class="space-y-3 border-t border-line pt-6 text-sm">
        <div class="flex justify-between"><dt><?= e($t['subtotal']) ?></dt><dd data-co-subtotal></dd></div>
        <div data-co-disc-row hidden class="flex justify-between text-gold"><dt><?= e($t['discount']) ?> (<span data-co-disc-code></span>)</dt><dd>−<span data-co-disc></span></dd></div>
        <div class="flex justify-between"><dt><?= e($t['shipping']) ?></dt><dd data-co-ship></dd></div>
        <div class="display flex justify-between border-t border-line pt-4 text-2xl"><dt><?= e($t['total']) ?></dt><dd data-co-total></dd></div>
      </dl>
      <p data-co-err role="alert" hidden class="text-sm text-red-700"></p>
      <button data-co-submit class="btn btn-dark w-full"<?= $methods ? '' : ' disabled' ?>><?= e($t['placeOrder']) ?></button>
    </aside>
  </form>
  <div data-checkout-empty hidden class="py-40 text-center"><p class="display text-3xl"><?= e($t['emptyBag']) ?></p><a href="/<?= $l ?>/shop" class="btn btn-dark mt-8"><?= e($t['continueShopping']) ?></a></div>
</div>
