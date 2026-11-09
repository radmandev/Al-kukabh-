<?php
/**
 * Storefront shell.
 * @var string $l       locale
 * @var string $content page HTML
 * @var array  $meta    title, description, noindex, image, path (for hreflang), isHome, jsonld
 */
$s = settings();
$t = dict($l);
$other = $l === 'ar' ? 'en' : 'ar';
$brandName = L($s['brand'], 'name', $l);
$title = !empty($meta['title']) ? $meta['title'] . ' — ' . $brandName : L($s['seo'], 'title', $l);
$desc = $meta['description'] ?? L($s['seo'], 'desc', $l);
$site = rtrim((string) config('site_url', ''), '/');
$path = $meta['path'] ?? substr(request_path(), 3);               // path after /ar or /en
$qs = ($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
$ogImage = $s['seo']['ogImage'] ?: $s['brand']['logoUrl'];
if (!empty($meta['image'])) $ogImage = $meta['image'];
$isHome = !empty($meta['isHome']);
$user = current_user();
$cols = rows('SELECT * FROM collections WHERE published = 1 ORDER BY sort LIMIT 4');
$covers = media_map(array_column($cols, 'coverId'));
$logo = $s['brand']['logoUrl'];
$nav = [
    ["/$l/shop", $t['shop'], false],
    ["/$l/shop?tag=new", $t['newArrivals'], false],
    ["/$l/collections", $t['collections'], true],
    ["/$l/shop?category=occasion", $t['occasion'], false],
    ["/$l/shop?category=everyday", $t['everyday'], false],
    ["/$l/shop?tag=limited", $t['limited'], false],
    ["/$l/story", $t['story'], false],
];
$solid = 'border-gold/50 bg-onyx/95 shadow-[0_8px_30px_rgba(0,0,0,.35)] backdrop-blur';
$clear = 'border-transparent bg-origin-border bg-gradient-to-b from-black/65 via-black/30 to-transparent';
$navLink = 'tracked link-u whitespace-nowrap text-[.68rem] font-bold transition-colors hover:text-ivory';
?><!doctype html>
<html lang="<?= $l ?>" dir="<?= dir_for($l) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex"><?php endif ?>
<link rel="icon" href="<?= e(img_url($logo, 128)) ?>">
<link rel="alternate" hreflang="ar" href="<?= e("$site/ar$path") ?>">
<link rel="alternate" hreflang="en" href="<?= e("$site/en$path") ?>">
<?php if (!empty($meta['canonical'])): ?><link rel="canonical" href="<?= e($site . $meta['canonical']) ?>"><?php endif ?>
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:site_name" content="<?= e($brandName) ?>">
<meta property="og:locale" content="<?= $l === 'ar' ? 'ar_SA' : 'en_US' ?>">
<meta property="og:image" content="<?= e((str_starts_with($ogImage, '/') ? $site : '') . $ogImage) ?>">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Almarai:wght@300;400;700&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400&family=Jost:wght@300;400;500;700&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=<?= @filemtime(ROOT . '/public/assets/app.css') ?>">
<?php if (!empty($meta['jsonld'])): ?><script type="application/ld+json"><?= json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script><?php endif ?>
</head>
<body class="min-h-screen">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:z-[100] focus:bg-ink focus:p-3 focus:text-ivory">Skip to content</a>

<header data-header data-home="<?= $isHome ? '1' : '' ?>" data-solid="<?= e($solid) ?>" data-clear="<?= e($clear) ?>"
  class="<?= $isHome ? 'fixed' : 'sticky' ?> inset-x-0 top-0 z-50 border-b text-gold-soft transition-[background-color,border-color,box-shadow] duration-700 <?= $isHome ? $clear : $solid ?>">
  <div class="mx-auto grid h-20 max-w-[1600px] grid-cols-[1fr_auto_1fr] items-center px-4 md:h-24 md:px-10">
    <div class="flex items-center gap-4 xl:gap-7">
      <button type="button" class="xl:hidden" data-drawer-open aria-label="<?= e($t['menu']) ?>"><?= icon('menu') ?></button>
      <button type="button" data-search-open aria-label="<?= e($t['search']) ?>" class="flex items-center gap-2">
        <?= icon('search') ?><span class="tracked hidden text-[.68rem] font-bold md:inline xl:hidden"><?= e($t['search']) ?></span>
      </button>
      <nav class="hidden items-center gap-6 xl:flex 2xl:gap-8" aria-label="Main">
        <?php foreach (array_slice($nav, 0, 4) as [$href, $label, $mega]): ?>
          <a href="<?= e($href) ?>" data-nav<?= $mega ? ' data-mega' : '' ?> class="<?= $navLink ?>"><?= e($label) ?></a>
        <?php endforeach ?>
      </nav>
    </div>
    <a href="/<?= $l ?>" class="flex items-center px-6 xl:px-10" aria-label="<?= e($brandName) ?>">
      <?= img_fixed($logo, 84, $brandName, 'h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-gold-soft shadow-[0_0_28px_rgba(214,189,138,.45)] transition-transform duration-500 hover:scale-105 md:h-[84px] md:w-[84px]') ?>
    </a>
    <div class="flex items-center justify-end gap-4 md:gap-5">
      <nav class="me-4 hidden items-center gap-6 xl:flex 2xl:gap-8" aria-label="More">
        <?php foreach (array_slice($nav, 4) as [$href, $label, $mega]): ?>
          <a href="<?= e($href) ?>" data-nav class="<?= $navLink ?>"><?= e($label) ?></a>
        <?php endforeach ?>
      </nav>
      <a href="<?= e("/$other$path$qs") ?>" data-lang="<?= $other ?>" class="tracked text-[.68rem] font-bold"><?= $other === 'ar' ? 'عربي' : 'EN' ?></a>
      <a href="/<?= $l ?>/account" aria-label="<?= e($t['account']) ?>" class="hidden sm:block"><?= icon('user') ?></a>
      <a href="/<?= $l ?>/wishlist" aria-label="<?= e($t['wishlist']) ?>" class="relative hidden sm:block">
        <?= icon('heart') ?><span data-wish-dot hidden class="absolute -end-1.5 -top-1 h-1.5 w-1.5 rounded-full bg-gold-soft"></span>
      </a>
      <button type="button" data-bag-open aria-label="<?= e($t['bag']) ?>" class="relative">
        <?= icon('bag') ?>
        <span data-bag-count hidden class="absolute -end-2 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-gold-soft px-1 text-[.6rem] font-medium text-ink"></span>
      </button>
    </div>
  </div>
  <?php if ($cols): ?>
  <div data-mega-panel hidden class="absolute inset-x-0 top-full border-b border-line bg-ivory text-ink reveal">
    <div class="mx-auto grid max-w-[1400px] grid-cols-4 gap-6 px-10 py-10">
      <?php foreach ($cols as $c): $cv = $covers[$c['coverId'] ?? ''] ?? null; ?>
        <a href="/<?= $l ?>/collections/<?= e(rawurlencode($c['slug'])) ?>" class="group">
          <div class="relative aspect-[4/5] overflow-hidden bg-sand">
            <?= $cv && $cv['kind'] === 'image' ? img($cv['url'], '', 'object-cover transition duration-[1.4s] ease-[var(--ease-lux)] group-hover:scale-105', '25vw') : '' ?>
          </div>
          <p class="display mt-4 text-xl"><?= e(L($c, 'name', $l)) ?></p>
          <p class="eyebrow mt-1 text-stone"><?= e($t['discover']) ?></p>
        </a>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>
</header>

<!-- mobile drawer -->
<div data-drawer class="pointer-events-none fixed inset-0 z-[60] xl:hidden" aria-hidden="true">
  <div data-drawer-close data-drawer-overlay class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-500"></div>
  <div data-drawer-panel data-closed="<?= $l === 'ar' ? 'translate-x-full' : '-translate-x-full' ?>" class="absolute inset-y-0 start-0 flex w-[86%] max-w-sm flex-col bg-ivory transition-transform duration-500 ease-[var(--ease-lux)] <?= $l === 'ar' ? 'translate-x-full' : '-translate-x-full' ?>">
    <div class="flex h-20 items-center justify-between border-b border-gold/40 bg-onyx px-5 text-gold-soft">
      <?= img_fixed($logo, 48, $brandName, 'h-12 w-12 rounded-full object-cover ring-2 ring-gold-soft') ?>
      <button type="button" data-drawer-close aria-label="<?= e($t['close']) ?>"><?= icon('close') ?></button>
    </div>
    <nav class="flex-1 overflow-y-auto px-5 py-6">
      <?php foreach ($nav as [$href, $label]): ?>
        <a href="<?= e($href) ?>" class="display flex items-center justify-between border-b border-line py-4 text-2xl"><?= e($label) ?><?= icon('chevron', 'h-4 w-4 opacity-40 rtl:rotate-180') ?></a>
      <?php endforeach ?>
      <?php if ($cols): ?>
        <div class="no-scrollbar -mx-5 mt-6 flex gap-3 overflow-x-auto px-5">
          <?php foreach ($cols as $c): $cv = $covers[$c['coverId'] ?? ''] ?? null; ?>
            <a href="/<?= $l ?>/collections/<?= e(rawurlencode($c['slug'])) ?>" class="w-36 shrink-0">
              <div class="relative aspect-[3/4] bg-sand"><?= $cv && $cv['kind'] === 'image' ? img($cv['url'], '', 'object-cover', '144px') : '' ?></div>
              <p class="mt-2 text-sm"><?= e(L($c, 'name', $l)) ?></p>
            </a>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </nav>
    <div class="grid grid-cols-2 border-t border-line text-center text-sm">
      <a href="/<?= $l ?>/account" class="border-e border-line py-4"><?= e($user ? $t['account'] : $t['login']) ?></a>
      <a href="/<?= $l ?>/wishlist" class="py-4"><?= e($t['wishlist']) ?></a>
    </div>
  </div>
</div>

<!-- search overlay -->
<div data-search hidden class="fixed inset-0 z-[70] bg-ivory/98 reveal" role="dialog" aria-label="<?= e($t['search']) ?>">
  <button type="button" data-search-close class="absolute end-5 top-5" aria-label="<?= e($t['close']) ?>"><?= icon('close', 'h-6 w-6') ?></button>
  <form action="/<?= $l ?>/shop" class="mx-auto mt-[22vh] max-w-2xl px-6">
    <p class="eyebrow mb-6 text-stone"><?= e($t['search']) ?></p>
    <input name="q" placeholder="<?= e($t['searchPlaceholder']) ?>" class="display w-full border-b border-ink bg-transparent pb-4 text-3xl outline-none md:text-5xl">
  </form>
</div>

<main id="main"><?= $content ?></main>

<?= view('store/footer', ['l' => $l, 's' => $s]) ?>

<!-- bag drawer (contents rendered by store.js from localStorage) -->
<div data-bag class="pointer-events-none fixed inset-0 z-[80]" aria-hidden="true">
  <div data-bag-close data-bag-overlay class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-500"></div>
  <aside data-bag-panel data-closed="<?= $l === 'ar' ? '-translate-x-full' : 'translate-x-full' ?>" role="dialog" aria-label="<?= e($t['bag']) ?>" class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-ivory transition-transform duration-500 ease-[var(--ease-lux)] <?= $l === 'ar' ? '-translate-x-full' : 'translate-x-full' ?>">
    <div class="flex h-16 items-center justify-between border-b border-line px-6">
      <p class="tracked text-xs"><?= e($t['bag']) ?> (<span data-bag-lines>0</span>)</p>
      <button type="button" data-bag-close aria-label="<?= e($t['close']) ?>"><?= icon('close') ?></button>
    </div>
    <div data-bag-body class="flex min-h-0 flex-1 flex-col"></div>
  </aside>
</div>

<?php if (wa_number() !== ''): ?>
<!-- floating WhatsApp: straight to a chat with the store -->
<a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="<?= e($t['chatWhatsapp']) ?>" title="<?= e($t['chatWhatsapp']) ?>" data-wa-fab
   class="fixed bottom-5 start-5 z-30 grid h-14 w-14 place-items-center rounded-full bg-[#25d366] text-white shadow-[0_10px_30px_-8px_rgba(0,0,0,.45)] ring-2 ring-white/70 transition hover:scale-105 md:bottom-7 md:start-7"><?= wa_icon('h-7 w-7') ?></a>
<?php endif ?>
<script>window.KW = <?= json_encode(['l' => $l, 'currency' => $s['currency'], 't' => $t, 'wa' => wa_number()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="/assets/store.js?v=<?= @filemtime(ROOT . '/public/assets/store.js') ?>" defer></script>
</body>
</html>
