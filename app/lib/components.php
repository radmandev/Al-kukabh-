<?php
// Reusable HTML pieces shared by several pages. Each returns a string.

/** Thin-stroke icon set (1.25px) to match the fine gold linework of the logo. */
function icon(string $name, string $class = 'h-5 w-5', bool $filled = false): string
{
    static $P = [
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35',
        'home' => 'M3.5 10.5 12 3.5l8.5 7M5.5 9v11.5h4.75v-6.25h3.5v6.25h4.75V9',
        'instagram' => 'M7 2.5h10A4.5 4.5 0 0 1 21.5 7v10a4.5 4.5 0 0 1-4.5 4.5H7A4.5 4.5 0 0 1 2.5 17V7A4.5 4.5 0 0 1 7 2.5Zm5 5.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm5.25-1.75h.01',
        'user' => 'M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm-8 9c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5',
        'bag' => 'M5 8h14l-1 13H6L5 8Zm4 0V6a3 3 0 0 1 6 0v2',
        'heart' => 'M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z',
        'menu' => 'M3 7h18M3 12h18M3 17h18',
        'close' => 'M5 5l14 14M19 5 5 19',
        'plus' => 'M12 5v14M5 12h14',
        'minus' => 'M5 12h14',
        'chevron' => 'm9 6 6 6-6 6',
        'arrow' => 'M4 12h16m-6-6 6 6-6 6',
        'expand' => 'M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5',
        'play' => 'M8 5v14l11-7L8 5Z',
        'truck' => 'M3 6h11v10H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'check' => 'm5 12 5 5 9-10',
    ];
    return '<svg viewBox="0 0 24 24" class="' . e($class) . '" fill="' . ($filled ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . ($P[$name] ?? '') . '"/></svg>';
}

/**
 * An uploaded image or a muted autoplay video, filling its `relative` parent.
 * $m = ['url', 'kind', 'poster'?, 'alt'?] or null (renders a sand placeholder).
 */
function media_el(?array $m, string $sizes = '100vw', bool $priority = false, string $class = '', bool $controls = false, string $fit = 'cover'): string
{
    if (!$m || empty($m['url'])) return '<div class="absolute inset-0 bg-sand ' . e($class) . '"></div>';
    $fitCls = $fit === 'contain' ? 'object-contain' : 'object-cover';
    if (($m['kind'] ?? '') === 'video') {
        return '<video class="absolute inset-0 h-full w-full ' . $fitCls . ' ' . e($class) . '" src="' . e($m['url']) . '"'
            . (!empty($m['poster']) ? ' poster="' . e(img_url($m['poster'], 1920)) . '"' : '')
            . ' muted loop playsinline' . ($controls ? ' controls' : ' autoplay') . ' preload="' . ($priority ? 'auto' : 'metadata') . '"'
            . (!empty($m['alt']) ? ' aria-label="' . e($m['alt']) . '"' : '') . '></video>';
    }
    return img($m['url'], (string) ($m['alt'] ?? ''), "$fitCls $class", $sizes, $priority);
}

/** Desktop + optional mobile art direction. */
function responsive_media(?array $desktop, ?array $mobile, string $sizes = '100vw', bool $priority = false, string $class = ''): string
{
    if (empty($mobile['url'])) return media_el($desktop, $sizes, $priority, $class);
    return '<div class="absolute inset-0 hidden md:block">' . media_el($desktop, $sizes, $priority, $class) . '</div>'
        . '<div class="absolute inset-0 md:hidden">' . media_el($mobile, $sizes, $priority, $class) . '</div>';
}

/** Section media refs ({url, kind, poster, altAr, altEn}) → media_el input. */
function mref(mixed $r, string $l): ?array
{
    return is_array($r) && !empty($r['url'])
        ? ['url' => $r['url'], 'kind' => $r['kind'] ?? 'image', 'poster' => $r['poster'] ?? null, 'alt' => L($r, 'alt', $l)]
        : null;
}

function price_html(array $p, string $l, string $currency, string $class = ''): string
{
    if (!(float) $p['price']) return ''; // price 0 = not set yet in admin
    $sale = $p['salePrice'];
    if ($sale === null) return '<span class="flex flex-wrap items-baseline gap-2 ' . e($class) . '"><span>' . e(money($p['price'], $currency, $l)) . '</span></span>';
    $off = (int) round(100 - $sale / $p['price'] * 100);
    // discounted pieces stand out: sale price and the % off in red
    return '<span class="flex flex-wrap items-baseline gap-2 ' . e($class) . '"><span class="font-bold text-red-700">' . e(money($sale, $currency, $l)) . '</span>'
        . '<s class="text-stone text-[.9em]" aria-label="was">' . e(money($p['price'], $currency, $l)) . '</s>'
        . ($off > 0 ? '<span class="rounded-full bg-red-700 px-2 py-0.5 text-[.75em] font-bold text-white" dir="ltr">-' . $off . '%</span>' : '') . '</span>';
}

function product_card(array $p, string $l, string $currency, bool $priority = false, bool $showDelivery = false): string
{
    $t = dict($l);
    $imgs = array_values(array_filter($p['gallery'], fn ($m) => $m['kind'] === 'image'));
    $a = $imgs[0] ?? null;
    $b = $imgs[1] ?? null;
    $name = L($p, 'name', $l);
    $href = "/$l/product/" . rawurlencode($p['slug']);
    $sizes = '(min-width:1024px) 25vw, 50vw';
    ob_start(); ?>
<div class="group relative">
  <div class="relative aspect-[3/4] overflow-hidden bg-sand">
    <a href="<?= e($href) ?>" class="absolute inset-0" aria-label="<?= e($name) ?>">
      <?php if ($a): ?><?= img($a['url'], L($a, 'alt', $l) ?: $name, 'object-cover transition duration-[1.2s] ease-[var(--ease-lux)] ' . ($b ? 'group-hover:opacity-0' : 'group-hover:scale-[1.03]'), $sizes, $priority) ?><?php endif ?>
      <?php if ($b): ?><?= img($b['url'], '', 'object-cover opacity-0 transition duration-[1.2s] ease-[var(--ease-lux)] group-hover:scale-[1.03] group-hover:opacity-100', $sizes) ?><?php endif ?>
    </a>
    <div class="pointer-events-none absolute start-3 top-3 flex flex-col items-start gap-1.5">
      <?php if ($p['isNew']): ?><span class="eyebrow bg-ivory/90 px-2 py-1 text-[.58rem]"><?= e($t['new']) ?></span><?php endif ?>
      <?php if ($p['isLimited']): ?><span class="eyebrow bg-ink px-2 py-1 text-[.58rem] text-gold-soft"><?= e($t['limitedLabel']) ?></span><?php endif ?>
      <?php if ($p['availability'] === 'out_of_stock'): ?><span class="eyebrow bg-ivory/90 px-2 py-1 text-[.58rem] text-stone"><?= e($t['outOfStock']) ?></span><?php endif ?>
    </div>
    <button type="button" data-wish="<?= e($p['id']) ?>" aria-pressed="false" aria-label="<?= e($t['addToWishlist']) ?>" class="absolute end-3 top-3 p-1"><?= icon('heart', 'h-5 w-5') ?></button>
    <button type="button" data-qv="<?= e($p['id']) ?>" class="tracked absolute inset-x-3 bottom-3 hidden translate-y-2 bg-ivory/95 py-3 text-[.62rem] opacity-0 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100 focus:opacity-100 md:block"><?= e($t['quickView']) ?></button>
  </div>
  <div class="pt-4 text-center">
    <a href="<?= e($href) ?>" class="display block text-lg leading-snug md:text-xl"><?= e($name) ?></a>
    <?= price_html($p, $l, $currency, 'mt-1 justify-center text-sm text-ink/80') ?>
    <?php if ($showDelivery): ?>
      <p class="mt-2 flex items-center justify-center gap-1.5 text-xs text-stone"><?= icon('truck', 'h-4 w-4') ?><?= e(L($p, 'delivery', $l) ?: ($p['availability'] === 'in_stock' ? $t['ready'] : $t['madeToOrder'])) ?></p>
    <?php endif ?>
  </div>
</div>
<?php return (string) ob_get_clean();
}

function product_grid(array $ps, string $l, string $currency): string
{
    if (!$ps) return '<p class="display py-32 text-center text-2xl text-stone">' . e(dict($l)['noResults']) . '</p>';
    $out = '<div class="grid grid-cols-2 gap-x-3 gap-y-12 md:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">';
    foreach ($ps as $i => $p) $out .= product_card($p, $l, $currency, $i < 4);
    return $out . '</div>';
}

/** WhatsApp brand glyph (filled). */
function wa_icon(string $class = 'h-6 w-6'): string
{
    return '<svg viewBox="0 0 24 24" class="' . e($class) . '" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.47-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.78h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41Z"/></svg>';
}

/** Digits-only WhatsApp number from settings ('' when not set). */
function wa_number(): string
{
    return preg_replace('/\D/', '', (string) (settings()['contact']['whatsapp'] ?? ''));
}

/** wa.me deep link (opens the app on phones, WhatsApp Web on desktop), optionally with a prefilled message. */
function wa_link(string $text = ''): string
{
    $n = wa_number();
    return $n === '' ? '' : 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/** Absolute URL for links inside WhatsApp messages (site_url in config, else the current host). */
function abs_url(string $path): string
{
    $site = rtrim((string) config('site_url', ''), '/');
    if ($site === '' || str_contains($site, 'localhost')) {
        $site = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    return $site . $path;
}

/** The order as a WhatsApp message: pieces (with product link → photo preview), size, colour, qty, and the customer's details. */
function wa_order_message(array $o, string $l): string
{
    $ar = $l === 'ar';
    $f = fn (float $v) => money($v, $o['currency'], $l);
    $lines = [$ar ? '✨ طلب جديد من موقع الكوكبة للعبايات' : '✨ New order from Al Kawkaba Abayas', ($ar ? 'رقم الطلب: ' : 'Order: ') . $o['number'], ''];
    foreach (jdecode($o['items'], []) as $i => $x) {
        $lines[] = ($i + 1) . ') ' . ($ar ? $x['nameAr'] : $x['nameEn']);
        $lines[] = ($ar ? 'المقاس: ' : 'Size: ') . ($x['size'] ?: '—') . ' | ' . ($ar ? 'اللون: ' : 'Colour: ') . ($x['color'] ?: '—') . ' | ' . ($ar ? 'الكمية: ' : 'Qty: ') . $x['qty'];
        if ((float) $x['price'] > 0) $lines[] = ($ar ? 'السعر: ' : 'Price: ') . $f($x['price'] * $x['qty']);
        $lines[] = ($ar ? 'العباية: ' : 'Abaya: ') . abs_url('/' . $l . '/product/' . rawurlencode($x['slug']));
        if (!empty($x['image'])) $lines[] = ($ar ? 'الصورة: ' : 'Photo: ') . abs_url($x['image']);
        $lines[] = '';
    }
    if ((float) $o['total'] > 0) $lines[] = ($ar ? 'الإجمالي: ' : 'Total: ') . $f($o['total']);
    $lines[] = '';
    $lines[] = $ar ? '👤 بيانات العميلة' : '👤 Customer';
    $lines[] = ($ar ? 'الاسم: ' : 'Name: ') . $o['name'];
    $lines[] = ($ar ? 'الجوال: ' : 'Phone: ') . $o['phone'];
    $lines[] = ($ar ? 'البريد: ' : 'Email: ') . $o['email'];
    $lines[] = ($ar ? 'العنوان: ' : 'Address: ') . $o['address'] . '، ' . $o['city'] . '، ' . $o['country'];
    $pm = array_column(payment_methods(settings()), null, 'id')[$o['paymentMethod']] ?? null;
    $lines[] = ($ar ? 'الدفع: ' : 'Payment: ') . ($pm ? L($pm, 'label', $l) : $o['paymentMethod']);
    if (trim((string) $o['notes']) !== '') $lines[] = ($ar ? 'ملاحظات: ' : 'Notes: ') . $o['notes'];
    return implode("\n", $lines);
}

/** Size / colour / qty selection + Add to bag, Buy now, Wishlist (behaviour in store.js). */
function add_to_bag(array $p, string $l, bool $compact = false): string
{
    $t = dict($l);
    $card = to_card($p);
    $sold = $p['availability'] === 'out_of_stock';
    $color0 = $p['colors'] ? L($p['colors'][0], 'name', $l) : '';
    $size0 = count($p['sizes']) === 1 ? $p['sizes'][0] : '';
    ob_start(); ?>
<div class="space-y-6" data-atb="<?= attr_json($card) ?>" data-size="<?= e($size0) ?>" data-color="<?= e($color0) ?>">
  <?php if ($p['colors']): ?>
  <fieldset>
    <legend class="eyebrow mb-3 text-stone"><?= e($t['color']) ?>: <span class="text-ink" data-color-name><?= e($color0) ?></span></legend>
    <div class="flex gap-3">
      <?php foreach ($p['colors'] as $c): $n = L($c, 'name', $l); $on = $n === $color0; ?>
        <button type="button" data-pick-color="<?= e($n) ?>" aria-label="<?= e($n) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>" class="h-8 w-8 rounded-full border-2 <?= $on ? 'border-ink' : 'border-transparent' ?> p-0.5"><span class="block h-full w-full rounded-full ring-1 ring-line" style="background: <?= e(preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($c['hex'] ?? '')) ? $c['hex'] : '#000000') ?>"></span></button>
      <?php endforeach ?>
    </div>
  </fieldset>
  <?php endif ?>
  <?php if ($p['sizes']): ?>
  <fieldset>
    <legend class="eyebrow mb-3 text-stone"><?= e($t['size']) ?></legend>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($p['sizes'] as $s): $on = $s === $size0; ?>
        <button type="button" data-pick-size="<?= e($s) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>" class="min-w-12 border px-3 py-2.5 text-sm transition <?= $on ? 'border-ink bg-ink text-ivory' : 'border-line hover:border-ink' ?>"><?= e($s) ?></button>
      <?php endforeach ?>
    </div>
    <p role="alert" data-atb-err class="mt-2 text-sm text-red-700" hidden></p>
  </fieldset>
  <?php endif ?>
  <?php if (!$compact): ?>
  <div class="flex items-center gap-4">
    <span class="eyebrow text-stone"><?= e($t['qty']) ?></span>
    <div class="flex items-center border border-line">
      <button type="button" class="p-3" data-qty-step="-1" aria-label="-"><?= icon('minus', 'h-3 w-3') ?></button>
      <span class="w-8 text-center" aria-live="polite" data-qty>1</span>
      <button type="button" class="p-3" data-qty-step="1" aria-label="+"><?= icon('plus', 'h-3 w-3') ?></button>
    </div>
  </div>
  <?php endif ?>
  <div class="flex gap-2">
    <button type="button" <?= $sold ? 'disabled' : '' ?> data-atb-add class="btn btn-dark flex-1"><?= e($sold ? $t['outOfStock'] : $t['addToBag']) ?></button>
    <button type="button" data-wish="<?= e($p['id']) ?>" aria-pressed="false" aria-label="<?= e($t['addToWishlist']) ?>" class="border border-line px-4 hover:border-ink"><?= icon('heart') ?></button>
  </div>
  <?php if (!$compact && !$sold): ?><button type="button" data-atb-buy class="btn w-full"><?= e($t['buyNow']) ?></button><?php endif ?>
  <?php if (!$compact && !$sold && wa_number() !== ''): ?>
    <button type="button" data-atb-wa class="btn w-full border-[#1f9e55] text-[#1f7a45] hover:bg-[#1f9e55] hover:text-white"><?= wa_icon('h-5 w-5') ?><span><?= e($t['orderWhatsapp']) ?></span></button>
  <?php endif ?>
</div>
<?php return (string) ob_get_clean();
}

/** Customer-facing order summary (confirmation, tracking, account). */
function order_summary(array $o, string $l): string
{
    $t = dict($l);
    $items = (array) jdecode($o['items'], []);
    $f = fn ($v) => e(money($v, $o['currency'], $l));
    ob_start(); ?>
<div class="space-y-8">
  <div class="grid grid-cols-2 gap-6 border-y border-line py-6 text-sm sm:grid-cols-3">
    <div><p class="eyebrow text-stone"><?= e($t['orderNumber']) ?></p><p class="mt-1"><?= e($o['number']) ?></p></div>
    <div><p class="eyebrow text-stone"><?= e($t['status']) ?></p><p class="mt-1"><?= e(status_label($o['status'], $l)) ?></p></div>
    <div><p class="eyebrow text-stone"><?= e($t['shipping']) ?></p><p class="mt-1"><?= e(status_label($o['shipStatus'], $l)) ?><?php if ($o['tracking']): ?><span class="block text-stone" dir="ltr"><?= e($o['tracking']) ?></span><?php endif ?></p></div>
  </div>
  <ul class="divide-y divide-line">
    <?php foreach ($items as $x): ?>
      <li class="flex gap-4 py-4 text-sm">
        <div class="relative aspect-[3/4] w-16 bg-sand"><?= !empty($x['image']) ? img($x['image'], '', 'object-cover', '64px') : '' ?></div>
        <div class="flex-1"><p><?= e($l === 'ar' ? $x['nameAr'] : $x['nameEn']) ?></p><p class="text-stone"><?= e(implode(' · ', array_filter([$x['size'] ?? '', $x['color'] ?? '', '× ' . $x['qty']]))) ?></p></div>
        <p><?= $f($x['price'] * $x['qty']) ?></p>
      </li>
    <?php endforeach ?>
  </ul>
  <dl class="ms-auto max-w-xs space-y-2 text-sm">
    <div class="flex justify-between"><dt><?= e($t['subtotal']) ?></dt><dd><?= $f($o['subtotal']) ?></dd></div>
    <?php if ((float) $o['discount'] > 0): ?><div class="flex justify-between"><dt><?= e($t['discount']) ?></dt><dd>−<?= $f($o['discount']) ?></dd></div><?php endif ?>
    <div class="flex justify-between"><dt><?= e($t['shipping']) ?></dt><dd><?= (float) $o['shipping'] ? $f($o['shipping']) : e($t['free']) ?></dd></div>
    <div class="display flex justify-between border-t border-line pt-3 text-xl"><dt><?= e($t['total']) ?></dt><dd><?= $f($o['total']) ?></dd></div>
  </dl>
</div>
<?php return (string) ob_get_clean();
}

/* ---------- admin primitives ---------- */

const A_CARD = 'rounded-md border border-black/10 bg-white p-5 md:p-6';
const A_INP = 'w-full rounded border border-black/15 bg-white px-3 py-2 text-sm outline-none focus:border-ink';
const A_BTN = 'inline-flex items-center gap-2 rounded bg-ink px-4 py-2 text-sm text-ivory hover:bg-onyx disabled:opacity-50';
const A_BTN2 = 'inline-flex items-center gap-2 rounded border border-black/15 bg-white px-3 py-1.5 text-sm hover:border-ink disabled:opacity-50';
const A_DANGER = 'text-sm text-red-700 hover:underline';

function page_head(string $title, string $actions = ''): string
{
    return '<div class="mb-8 flex flex-wrap items-center justify-between gap-4"><h1 class="display text-3xl md:text-4xl">' . e($title) . '</h1><div class="flex flex-wrap gap-2">' . $actions . '</div></div>';
}

function field(string $label, string $control, string $hint = '', string $class = ''): string
{
    return '<label class="block ' . e($class) . '"><span class="mb-1 block text-xs font-medium text-stone">' . e($label) . '</span>' . $control
        . ($hint ? '<span class="mt-1 block text-xs text-stone/70">' . e($hint) . '</span>' : '') . '</label>';
}

function badge(string $text, string $tone = 'neutral'): string
{
    $c = [
        'neutral' => 'bg-black/5 text-stone', 'good' => 'bg-emerald-50 text-emerald-800', 'warn' => 'bg-amber-50 text-amber-800',
        'bad' => 'bg-red-50 text-red-800', 'gold' => 'bg-ink text-gold-soft',
    ][$tone] ?? 'bg-black/5 text-stone';
    return '<span class="inline-block rounded px-2 py-0.5 text-[.7rem] ' . $c . '">' . e($text) . '</span>';
}

function tone_for(string $s): string
{
    if (in_array($s, ['paid', 'completed', 'delivered', 'confirmed'], true)) return 'good';
    if (in_array($s, ['cancelled', 'failed', 'refunded', 'returned'], true)) return 'bad';
    if (in_array($s, ['pending', 'unfulfilled'], true)) return 'warn';
    return 'neutral';
}

/** Small preview of a media row (image or video). */
function thumb(array $m, string $class = ''): string
{
    $inner = ($m['kind'] ?? '') === 'video'
        ? '<video src="' . e($m['url']) . '" muted preload="metadata" class="absolute inset-0 h-full w-full object-cover"></video><span class="absolute bottom-1 start-1 rounded bg-black/70 px-1.5 text-[.6rem] text-white">VIDEO</span>'
        : img($m['url'], '', 'object-cover', '200px');
    return '<div class="relative overflow-hidden bg-black/5 ' . e($class) . '">' . $inner . '</div>';
}

/** <form method=post> wrapper with CSRF token for a single-button admin action. */
function post_button(string $action, string $label, string $class, array $fields = [], string $confirm = ''): string
{
    $h = '';
    foreach ($fields as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return '<form method="post" action="' . e($action) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field() . $h
        . '<button class="' . e($class) . '">' . $label . '</button></form>';
}

/** Rows with ▲/▼ that persist their order via POST /admin/reorder (admin.js). $rows = [[id, html], ...] */
function sortable_rows(string $model, array $rows): string
{
    $h = '<ul data-sortable="' . e($model) . '" class="divide-y divide-black/5 rounded-md border border-black/10 bg-white">';
    foreach ($rows as [$id, $html]) {
        $h .= '<li data-id="' . e($id) . '" class="flex items-center gap-3 p-3"><div class="flex flex-col text-xs leading-none text-stone">'
            . '<button type="button" data-move="-1" class="p-1 disabled:opacity-20" aria-label="Move up">▲</button>'
            . '<button type="button" data-move="1" class="p-1 disabled:opacity-20" aria-label="Move down">▼</button>'
            . '</div><div class="min-w-0 flex-1">' . $html . '</div></li>';
    }
    return $h . '</ul>';
}
