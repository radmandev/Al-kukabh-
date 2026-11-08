<?php
/** @var string $l @var array $s */
$t = dict($l);
$social = array_filter($s['social']);
$col = function (string $title, array $links): string {
    $h = '<div><p class="eyebrow mb-5 text-gold-soft">' . e($title) . '</p><ul class="space-y-3 text-sm text-ivory/70">';
    foreach ($links as [$href, $label]) $h .= '<li><a href="' . e($href) . '" class="link-u hover:text-ivory">' . e($label) . '</a></li>';
    return $h . '</ul></div>';
};
$address = L($s['contact'], 'address', $l);
?>
<footer class="bg-ink text-ivory">
  <div class="border-b border-white/10" id="newsletter">
    <div class="mx-auto max-w-3xl px-6 py-20 text-center">
      <p class="display text-3xl md:text-4xl"><?= e($t['newsletter']) ?></p>
      <p class="mx-auto mt-4 max-w-md text-sm text-ivory/60"><?= e($t['newsletterText']) ?></p>
      <?php if (isset($_GET['subscribed'])): ?>
        <p class="mt-8 text-gold-soft"><?= e($t['subscribed']) ?></p>
      <?php else: ?>
        <form method="post" action="/<?= $l ?>/subscribe" data-newsletter class="mx-auto mt-8 flex max-w-md border-b border-ivory/40">
          <?= csrf_field() ?>
          <label for="nl" class="sr-only"><?= e($t['email']) ?></label>
          <input id="nl" name="email" type="email" required placeholder="<?= e($t['email']) ?>" class="flex-1 bg-transparent py-3 text-sm outline-none placeholder:text-ivory/40">
          <button class="tracked text-[.68rem] text-gold-soft"><?= e($t['subscribe']) ?></button>
        </form>
      <?php endif ?>
    </div>
  </div>
  <div class="mx-auto grid max-w-[1400px] gap-12 px-6 py-16 md:grid-cols-[1.4fr_1fr_1fr_1fr] md:px-10">
    <div>
      <?= img_fixed($s['brand']['logoUrl'], 96, L($s['brand'], 'name', $l), 'h-24 w-24 rounded-full object-cover') ?>
      <p class="mt-6 max-w-xs text-sm leading-7 text-ivory/60"><?= e(L($s['brand'], 'about', $l)) ?></p>
    </div>
    <?= $col($t['shop'], [["/$l/shop?tag=new", $t['newArrivals']], ["/$l/collections", $t['collections']], ["/$l/shop?category=occasion", $t['occasion']], ["/$l/shop?category=everyday", $t['everyday']], ["/$l/shop?tag=limited", $t['limited']]]) ?>
    <?= $col($t['customerService'], [["/$l/track", $t['trackOrder']], ["/$l/policies/shipping", $t['shippingPolicy']], ["/$l/policies/returns", $t['returns']], ["/$l/policies/privacy", $t['privacy']], ["/$l/policies/terms", $t['terms']]]) ?>
    <div>
      <p class="eyebrow mb-5 text-gold-soft"><?= e($t['contact']) ?></p>
      <ul class="space-y-3 text-sm text-ivory/70">
        <?php if ($s['contact']['email']): ?><li><a href="mailto:<?= e($s['contact']['email']) ?>" class="link-u"><?= e($s['contact']['email']) ?></a></li><?php endif ?>
        <?php if ($s['contact']['phone']): ?><li dir="ltr" class="text-start"><a href="tel:<?= e($s['contact']['phone']) ?>" class="link-u"><?= e($s['contact']['phone']) ?></a></li><?php endif ?>
        <?php if ($s['contact']['whatsapp']): ?><li><a href="https://wa.me/<?= e(preg_replace('/\D/', '', $s['contact']['whatsapp'])) ?>" class="link-u">WhatsApp</a></li><?php endif ?>
        <?php if ($address): ?><li><?= e($address) ?></li><?php endif ?>
      </ul>
      <?php if ($social): ?>
        <p class="eyebrow mb-4 mt-8 text-gold-soft"><?= e($t['followUs']) ?></p>
        <div class="flex flex-wrap gap-4 text-sm capitalize text-ivory/70">
          <?php foreach ($social as $k => $v): ?><a href="<?= e($v) ?>" target="_blank" rel="noopener" class="link-u"><?= e($k) ?></a><?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </div>
  <p class="border-t border-white/10 py-6 text-center text-xs text-ivory/40">© <?= date('Y') ?> <?= e(L($s['brand'], 'name', $l)) ?> — <?= e($t['rights']) ?></p>
</footer>
