<?php
// Homepage / story-page blocks. Data shapes are defined in app/lib/sections.php.

function sec_list(array $d, string $k): array
{
    return isset($d[$k]) && is_array($d[$k]) ? array_values(array_filter($d[$k], 'is_array')) : [];
}

/** gold rule + star: the "kawkaba" mark under every title */
function ornament(bool $center = true): string
{
    return '<div aria-hidden="true" class="mt-5 flex items-center gap-3 text-gold ' . ($center ? 'justify-center' : '') . '">'
        . '<span class="h-px w-14 bg-gradient-to-l from-gold/70 to-transparent"></span>'
        . '<svg viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z"/></svg>'
        . '<span class="h-px w-14 bg-gradient-to-r from-gold/70 to-transparent"></span></div>';
}

function sec_head(array $d, string $l, bool $center = true, bool $light = false): string
{
    $eyebrow = L($d, 'eyebrow', $l);
    $title = L($d, 'title', $l);
    $text = L($d, 'text', $l);
    if ($eyebrow === '' && $title === '' && $text === '') return '';
    return '<div class="' . ($center ? 'mx-auto text-center ' : '') . 'max-w-2xl px-6 md:px-10">'
        . ($eyebrow !== '' ? '<p class="eyebrow ' . ($light ? 'text-gold-soft' : 'text-gold') . '">' . e($eyebrow) . '</p>' : '')
        . ($title !== '' ? '<h2 class="display mt-3 text-4xl md:text-5xl">' . e($title) . '</h2>' . ornament($center) : '')
        . ($text !== '' ? '<p class="mt-5 whitespace-pre-line text-sm leading-7 md:text-base md:leading-8 ' . ($light ? 'text-ivory/70' : 'text-stone') . '">' . e($text) . '</p>' : '')
        . '</div>';
}

function sec_cta(array $d, string $l, bool $light = false): string
{
    $c = L($d, 'cta', $l);
    if ($c === '' || empty($d['link'])) return '';
    return '<div class="mt-10 text-center"><a href="' . e(localize((string) $d['link'], $l)) . '" class="btn ' . ($light ? 'btn-ghost' : 'btn-dark') . '"><span>' . e($c) . '</span></a></div>';
}

function render_section(string $type, string $data, string $l, string $currency): string
{
    $d = (array) jdecode($data, []);
    return match ($type) {
        'hero' => sec_hero($d, $l),
        'collections' => sec_collections($d, $l),
        'products' => sec_products($d, $l, $currency),
        'occasions' => sec_occasions($d, $l),
        'ready' => sec_ready($d, $l, $currency),
        'story' => sec_story($d, $l),
        'campaign' => sec_campaign($d, $l),
        'values' => sec_values($d, $l),
        'text' => sec_text($d, $l),
        default => '',
    };
}

/* ---------- hero slideshow (behaviour in store.js → hero()) ---------- */

function sec_hero(array $d, string $l): string
{
    $slides = array_map(fn ($s) => [
        'desktop' => mref($s['media'] ?? null, $l), 'mobile' => mref($s['mobileMedia'] ?? null, $l),
        'eyebrow' => L($s, 'eyebrow', $l), 'heading' => L($s, 'heading', $l), 'text' => L($s, 'text', $l),
        'cta' => L($s, 'cta', $l), 'link' => localize((string) ($s['link'] ?? ''), $l),
    ], sec_list($d, 'slides'));
    $n = count($slides);
    if (!$n) return '';
    $isVid = fn (?array $m) => ($m['kind'] ?? '') === 'video';
    // desktop: 3 portrait photos side by side, sliding one step at a time (seamless loop)
    $strip = $n >= 3 && !array_filter($slides, fn ($s) => $isVid($s['desktop']));
    $meta = array_map(fn ($s) => ['d' => $isVid($s['desktop']), 'm' => $s['mobile'] ? $isVid($s['mobile']) : null], $slides);

    $video = fn (array $m, int $j) => '<video src="' . e($m['url']) . '"' . (!empty($m['poster']) ? ' poster="' . e(img_url($m['poster'], 1920)) . '"' : '')
        . ' muted playsinline preload="' . ($j === 0 ? 'auto' : 'metadata') . '"' . ($j === 0 ? ' autoplay' : '') . ($n === 1 ? ' loop' : '')
        . ' class="absolute inset-0 h-full w-full object-cover object-[center_20%]"' . (!empty($m['alt']) ? ' aria-label="' . e($m['alt']) . '"' : '') . '></video>';

    ob_start(); ?>
<section class="relative h-[100svh] min-h-[560px] overflow-hidden bg-ink text-ivory" aria-roledescription="carousel" data-hero="<?= attr_json(['slides' => $meta, 'strip' => $strip]) ?>">
  <?php if ($strip): ?>
    <div class="absolute inset-0 hidden md:block" dir="ltr">
      <div data-hero-track class="flex h-full transition-transform duration-[1.2s] ease-[var(--ease-lux)]">
        <?php foreach ([...$slides, ...array_slice($slides, 0, 3)] as $k => $s): ?>
          <div class="relative h-full w-1/3 shrink-0 border-x border-black/20"><?= media_el($s['desktop'], '34vw', $k < 3) ?></div>
        <?php endforeach ?>
      </div>
    </div>
  <?php endif ?>
  <?php foreach ($slides as $j => $s): ?>
    <div data-slide="<?= $j ?>" class="absolute inset-0 transition-opacity duration-[1.6s] ease-[var(--ease-lux)] <?= $j === 0 ? 'opacity-100' : 'pointer-events-none opacity-0' ?>"<?= $j ? ' aria-hidden="true"' : '' ?>>
      <?php
      // one layer per viewport when a mobile variant exists; on desktop the strip replaces the slide layers
      $layers = $s['mobile']
          ? ($strip ? [[$s['mobile'], 'md:hidden']] : [[$s['desktop'], 'hidden md:block'], [$s['mobile'], 'md:hidden']])
          : [[$s['desktop'], $strip ? 'md:hidden' : '']];
      foreach ($layers as [$m, $vis]):
          if ($isVid($m)): ?>
            <div class="absolute inset-0 <?= $vis ?>"><?= $video($m, $j) ?></div>
          <?php else: ?>
            <div data-kb class="absolute inset-0 <?= $vis ?> <?= $j === 0 ? 'kenburns' : '' ?>"><?= media_el($m, '100vw', $j === 0, 'object-[center_30%]') ?></div>
          <?php endif;
      endforeach ?>
      <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-black/20"></div>
      <div data-caption class="absolute inset-x-0 bottom-0 mx-auto max-w-4xl px-6 pb-20 text-center md:pb-28"<?= $j ? ' hidden' : '' ?>>
        <?php if ($s['eyebrow'] !== ''): ?><p class="eyebrow reveal text-gold-soft"><?= e($s['eyebrow']) ?></p><?php endif ?>
        <?php if ($s['heading'] !== ''): ?><<?= $j === 0 ? 'h1' : 'h2' ?> class="display reveal mt-4 text-5xl [animation-delay:.15s] md:text-7xl"><?= e($s['heading']) ?></<?= $j === 0 ? 'h1' : 'h2' ?>><?php endif ?>
        <?php if ($s['text'] !== ''): ?><p class="reveal mx-auto mt-5 max-w-lg text-sm leading-7 text-ivory/80 [animation-delay:.3s] md:text-base"><?= e($s['text']) ?></p><?php endif ?>
        <?php if ($s['cta'] !== '' && $s['link'] !== ''): ?><a href="<?= e($s['link']) ?>" class="btn btn-ghost reveal mt-9 [animation-delay:.45s]"><span><?= e($s['cta']) ?></span></a><?php endif ?>
      </div>
    </div>
  <?php endforeach ?>
  <?php if ($n > 1): ?>
    <div class="absolute inset-x-0 bottom-8 flex items-center justify-center gap-1.5 px-4 md:gap-3">
      <?php foreach ($slides as $j => $_): ?>
        <button type="button" data-dot="<?= $j ?>" aria-label="<?= $j + 1 ?>" aria-current="<?= $j === 0 ? 'true' : 'false' ?>" class="h-6 w-6 py-3 md:w-10">
          <span class="block h-px w-full transition <?= $j === 0 ? 'bg-ivory' : 'bg-ivory/35' ?>"></span>
        </button>
      <?php endforeach ?>
      <button type="button" data-hero-pause aria-label="Pause" class="ms-2 opacity-70 hover:opacity-100">
        <svg data-icon-pause viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
        <span data-icon-play hidden><?= icon('play', 'h-3.5 w-3.5', true) ?></span>
      </button>
    </div>
  <?php endif ?>
</section>
<?php return (string) ob_get_clean();
}

/* ---------- product rail ---------- */

function rail(string $label, string $items): string
{
    return '<div class="relative"><div data-rail role="region" aria-label="' . e($label) . '" class="no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 md:gap-6 md:scroll-px-10 md:px-10">'
        . $items . '</div><div class="mt-8 hidden justify-center gap-2 md:flex">'
        . '<button type="button" data-rail-step="-1" class="border border-line p-3 hover:border-ink" aria-label="Previous">' . icon('arrow', 'h-4 w-4 rotate-180 rtl:rotate-0') . '</button>'
        . '<button type="button" data-rail-step="1" class="border border-line p-3 hover:border-ink" aria-label="Next">' . icon('arrow', 'h-4 w-4 rtl:rotate-180') . '</button>'
        . '</div></div>';
}

function sec_products(array $d, string $l, string $currency): string
{
    $take = (int) ($d['limit'] ?? 0) ?: 8;
    $src = (string) ($d['source'] ?? 'new');
    $flags = ['new' => 'isNew', 'featured' => 'isFeatured', 'limited' => 'isLimited', 'ready' => 'isReady'];
    $ps = $src === 'manual'
        ? products_by_ids(is_array($d['productIds'] ?? null) ? $d['productIds'] : [])
        : find_products(isset($flags[$src]) ? "AND p.{$flags[$src]} = 1" : '', [], 'p.createdAt DESC', $take);
    if (!$ps) return '';
    $items = '';
    foreach ($ps as $i => $p) $items .= '<div class="w-[72%] shrink-0 snap-start sm:w-[42%] lg:w-[calc(25%-1.15rem)]">' . product_card($p, $l, $currency, $i < 2) . '</div>';
    return '<section class="py-24 md:py-36">' . sec_head($d, $l) . '<div class="mt-12">' . rail(L($d, 'title', $l), $items) . '</div>' . sec_cta($d, $l) . '</section>';
}

/* ---------- collections (editorial) ---------- */

function sec_collections(array $d, string $l): string
{
    $t = dict($l);
    $ids = array_values(array_filter(is_array($d['collectionIds'] ?? null) ? $d['collectionIds'] : [], 'is_string'));
    $cols = $ids
        ? rows('SELECT * FROM collections WHERE published = 1 AND id IN (' . in_list($ids) . ')', $ids)
        : rows('SELECT * FROM collections WHERE published = 1 ORDER BY sort');
    if ($ids) usort($cols, fn ($a, $b) => array_search($a['id'], $ids) <=> array_search($b['id'], $ids));
    if (!$cols) return '';
    $media = media_map([...array_column($cols, 'coverId'), ...array_column($cols, 'coverMobileId')]);
    $get = fn (?string $id) => isset($media[$id ?? '']) ? ['url' => $media[$id]['url'], 'kind' => $media[$id]['kind']] : null;

    $tile = function (array $c, bool $big) use ($l, $t, $get) {
        $desc = L($c, 'desc', $l);
        $mob = $big ? $get($c['coverMobileId']) : null;
        $m = $mob
            ? '<div class="absolute inset-0 hidden md:block">' . media_el($get($c['coverId'])) . '</div><div class="absolute inset-0 md:hidden">' . media_el($mob) . '</div>'
            : media_el($get($c['coverId']), $big ? '100vw' : '50vw');
        return '<a href="/' . $l . '/collections/' . e(rawurlencode($c['slug'])) . '" class="group relative block overflow-hidden bg-ink text-ivory">'
            . '<div class="relative ' . ($big ? 'aspect-[4/5] md:aspect-[21/9]' : 'aspect-[4/5]') . '">'
            . '<div class="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-[1.04]">' . $m . '</div>'
            . '<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>'
            . '<div class="absolute inset-x-0 bottom-0 p-8 text-center md:p-12">'
            . '<h3 class="display ' . ($big ? 'text-4xl md:text-6xl' : 'text-3xl md:text-4xl') . '">' . e(L($c, 'name', $l)) . '</h3>'
            . ($desc !== '' ? '<p class="mx-auto mt-3 line-clamp-2 max-w-md text-sm text-ivory/75">' . e($desc) . '</p>' : '')
            . '<span class="eyebrow link-u mt-5 inline-block">' . e($t['shopNow']) . '</span></div></div></a>';
    };
    $first = array_shift($cols);
    $rest = '';
    foreach ($cols as $c) $rest .= $tile($c, false);
    return '<section class="py-24 md:py-36">' . sec_head($d, $l) . '<div class="mt-12 space-y-4 px-4 md:space-y-6 md:px-10">' . $tile($first, true)
        . ($cols ? '<div class="grid gap-4 md:gap-6 ' . (count($cols) % 3 === 0 ? 'md:grid-cols-3' : 'md:grid-cols-2') . '">' . $rest . '</div>' : '')
        . '</div></section>';
}

function sec_occasions(array $d, string $l): string
{
    $items = sec_list($d, 'items');
    if (!$items) return '';
    $h = '';
    foreach ($items as $it) {
        $h .= '<a href="' . e(localize((string) (($it['link'] ?? '') ?: '#'), $l)) . '" class="group relative aspect-[2/3] w-[70%] shrink-0 snap-start overflow-hidden bg-ink text-ivory md:w-auto">'
            . '<div class="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-105">' . media_el(mref($it['media'] ?? null, $l), '(min-width:768px) 25vw, 70vw') . '</div>'
            . '<div class="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent"></div>'
            . '<p class="display absolute inset-x-0 bottom-8 text-center text-2xl md:text-3xl">' . e(L($it, 'label', $l)) . '</p></a>';
    }
    return '<section class="py-24 md:py-36">' . sec_head($d, $l)
        . '<div class="no-scrollbar mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 md:grid md:grid-cols-[repeat(auto-fit,minmax(0,1fr))] md:gap-6 md:px-10">' . $h . '</div></section>';
}

function sec_ready(array $d, string $l, string $currency): string
{
    $ps = find_products("AND p.isReady = 1 AND p.availability <> 'out_of_stock'", [], 'p.createdAt DESC', (int) ($d['limit'] ?? 0) ?: 4);
    if (!$ps) return '';
    $h = '';
    foreach ($ps as $p) $h .= product_card($p, $l, $currency, false, true);
    return '<section class="bg-sand py-24 md:py-36">' . sec_head($d, $l)
        . '<div class="mt-12 grid grid-cols-2 gap-x-4 gap-y-10 px-4 md:gap-x-6 md:px-10 lg:grid-cols-4">' . $h . '</div>' . sec_cta($d, $l) . '</section>';
}

function sec_story(array $d, string $l): string
{
    $gallery = sec_list($d, 'gallery');
    $g = '';
    foreach ($gallery as $it) {
        $cap = L($it, 'caption', $l);
        $g .= '<figure><div class="relative aspect-square overflow-hidden">' . media_el(mref($it['media'] ?? null, $l), '25vw') . '</div>'
            . ($cap !== '' ? '<figcaption class="eyebrow mt-3 text-ivory/60">' . e($cap) . '</figcaption>' : '') . '</figure>';
    }
    return '<section class="bg-ink py-20 text-ivory md:py-32">'
        . '<div class="mx-auto grid max-w-[1400px] items-center gap-12 px-4 md:grid-cols-2 md:gap-20 md:px-10">'
        . '<div class="relative aspect-[4/5] overflow-hidden">' . media_el(mref($d['media'] ?? null, $l), '50vw') . '</div>'
        . '<div>' . sec_head($d, $l, false, true) . '<div class="px-6 md:px-10 [&>div]:text-start">' . sec_cta($d, $l, true) . '</div></div></div>'
        . ($g ? '<div class="mx-auto mt-16 grid max-w-[1400px] grid-cols-2 gap-4 px-4 md:mt-24 md:grid-cols-4 md:gap-6 md:px-10">' . $g . '</div>' : '')
        . '</section>';
}

function sec_campaign(array $d, string $l): string
{
    $items = sec_list($d, 'items');
    $layout = (string) ($d['layout'] ?? 'full');
    if ($layout === 'full' && $items) {
        return '<section class="relative h-[90svh] min-h-[520px] overflow-hidden bg-ink text-ivory">' . media_el(mref($items[0]['media'] ?? null, $l))
            . '<div class="absolute inset-0 bg-black/30"></div><div class="absolute inset-x-0 bottom-0 pb-20">' . sec_head($d, $l, true, true) . sec_cta($d, $l, true) . '</div></section>';
    }
    if ($layout === 'showcase' && $items) {
        // rotating video showcase (behaviour in store.js → showcase): centre piece plays, neighbours wait smaller & dimmed
        $h = $dots = '';
        foreach ($items as $i => $it) {
            $m = mref($it['media'] ?? null, $l);
            $cap = L($it, 'caption', $l);
            $inner = ($m['kind'] ?? '') === 'video'
                ? '<video data-show-video class="absolute inset-0 h-full w-full object-cover" src="' . e($m['url']) . '"' . (!empty($m['poster']) ? ' poster="' . e(img_url($m['poster'], 720)) . '"' : '')
                    . ' muted playsinline preload="' . ($i === 0 ? 'auto' : 'metadata') . '"' . (!empty($m['alt']) ? ' aria-label="' . e($m['alt']) . '"' : '') . '></video>'
                : media_el($m, '330px');
            $h .= '<figure data-show-item class="absolute left-1/2 top-0 w-[58vw] max-w-[320px] cursor-pointer transition-[transform,opacity,filter,outline-color] duration-[1.1s] ease-[var(--ease-lux)] md:w-[300px]"'
                . ' style="transform:translateX(-50%);opacity:' . ($i === 0 ? '1' : '0') . ';outline:1px solid transparent;outline-offset:10px">'
                . '<div class="relative aspect-[9/16] overflow-hidden bg-ink ring-1 ring-gold/40 shadow-[0_40px_70px_-25px_rgba(0,0,0,.55)]">' . $inner
                . '<div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent"></div></div>'
                . ($cap !== '' ? '<figcaption data-show-cap class="eyebrow mt-5 text-center text-gold transition-opacity duration-700" style="opacity:' . ($i === 0 ? '1' : '0') . '">' . e($cap) . '</figcaption>' : '')
                . '</figure>';
            $dots .= '<button type="button" data-show-dot="' . $i . '" aria-label="' . ($i + 1) . '" class="h-6 w-8 py-3"><span class="block h-px w-full bg-gold/30 transition"></span></button>';
        }
        $arrow = fn (int $dir, string $label, string $rot) => '<button type="button" data-show-step="' . $dir . '" aria-label="' . $label . '" class="grid h-11 w-11 place-items-center rounded-full border border-gold/40 text-gold transition hover:bg-gold hover:text-ivory">' . icon('arrow', 'h-4 w-4 ' . $rot) . '</button>';
        return '<section class="overflow-hidden py-24 md:py-36" data-showcase>' . sec_head($d, $l)
            . '<div class="relative mx-auto mt-14 h-[calc(58vw*16/9+3.5rem)] max-h-[640px] md:mt-16 md:h-[600px]" dir="ltr">' . $h . '</div>'
            . '<div class="mt-8 flex items-center justify-center gap-5" dir="ltr">' . $arrow(-1, 'Previous', 'rotate-180') . '<div class="flex gap-1">' . $dots . '</div>' . $arrow(1, 'Next', '') . '</div>'
            . sec_cta($d, $l) . '</section>';
    }
    if ($layout === 'mosaic' && $items) {
        // editorial mosaic on black: one tall hero image + close-ups (desktop: 2x2 block + 2-column squares)
        $h = '';
        foreach ($items as $i => $it) {
            $cap = L($it, 'caption', $l);
            $h .= '<figure class="group relative overflow-hidden bg-onyx ' . ($i === 0 ? 'col-span-2 aspect-[4/5] md:row-span-2 md:aspect-auto' : 'aspect-square') . '">'
                . '<div class="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-105">' . media_el(mref($it['media'] ?? null, $l), $i === 0 ? '50vw' : '25vw') . '</div>'
                . ($cap !== '' ? '<figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-4 pb-4 pt-12 md:px-5 md:pb-5"><span class="eyebrow text-gold-soft">' . e($cap) . '</span></figcaption>' : '')
                . '</figure>';
        }
        return '<section class="bg-ink py-24 text-ivory md:py-36">' . sec_head($d, $l, true, true)
            . '<div class="mx-auto mt-14 grid max-w-[1400px] grid-cols-2 gap-2 px-4 md:mt-16 md:grid-cols-4 md:gap-3 md:px-10">' . $h . '</div>' . sec_cta($d, $l, true) . '</section>';
    }
    // portrait phone videos keep their full 9:16 frame
    $allVideo = $items && !array_filter($items, fn ($it) => (($it['media']['kind'] ?? '') !== 'video'));
    $h = '';
    foreach ($items as $i => $it) {
        $cap = L($it, 'caption', $l);
        $h .= '<figure class="' . ($layout === 'grid' && $i === 0 ? 'col-span-2 row-span-2 md:col-span-1' : '') . '">'
            . '<div class="relative overflow-hidden ' . ($allVideo ? 'aspect-[9/16]' : ($layout === 'split' ? 'aspect-[4/5]' : 'aspect-[3/4]')) . '">' . media_el(mref($it['media'] ?? null, $l), '50vw') . '</div>'
            . ($cap !== '' ? '<figcaption class="eyebrow mt-3 text-stone">' . e($cap) . '</figcaption>' : '') . '</figure>';
    }
    return '<section class="py-24 md:py-36">' . sec_head($d, $l)
        . '<div class="mt-12 grid gap-3 px-4 md:gap-6 md:px-10 ' . ($allVideo ? 'mx-auto max-w-4xl ' : '') . ($layout === 'split' ? 'grid-cols-2' : 'grid-cols-2 md:grid-cols-3') . '">' . $h . '</div>' . sec_cta($d, $l) . '</section>';
}

function sec_values(array $d, string $l): string
{
    $h = '';
    foreach (sec_list($d, 'items') as $i => $it) {
        $h .= '<div class="bg-onyx px-3 py-8 text-center md:px-8 md:py-12"><span class="display text-3xl text-gold-soft">' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '</span>'
            . '<h3 class="display mt-3 text-xl md:mt-4 md:text-2xl">' . e(L($it, 'title', $l)) . '</h3>'
            . '<p class="mx-auto mt-2 max-w-xs text-xs leading-6 text-ivory/60 md:mt-3 md:text-sm md:leading-7">' . e(L($it, 'text', $l)) . '</p></div>';
    }
    return '<section class="border-y border-gold/40 bg-onyx py-24 text-ivory md:py-32">' . sec_head($d, $l, true, true)
        . '<div class="mx-auto mt-12 grid max-w-[1200px] grid-cols-2 gap-px bg-gold/20 md:mt-16 lg:grid-cols-3">' . $h . '</div></section>';
}

function sec_text(array $d, string $l): string
{
    $eyebrow = L($d, 'eyebrow', $l);
    $title = L($d, 'title', $l);
    $text = L($d, 'text', $l);
    return '<section class="mx-auto max-w-3xl px-6 pb-4 pt-24 text-center md:pb-8 md:pt-36">'
        . ($eyebrow !== '' ? '<p class="eyebrow text-gold">' . e($eyebrow) . '</p>' : '')
        . ($title !== '' ? '<h2 class="display mt-5 text-4xl leading-tight md:text-6xl">' . e($title) . '</h2>' : '')
        . ornament()
        . ($text !== '' ? '<p class="mx-auto mt-8 max-w-xl whitespace-pre-line text-base leading-8 text-stone md:text-lg md:leading-9">' . e($text) . '</p>' : '')
        . sec_cta($d, $l) . '</section>';
}
