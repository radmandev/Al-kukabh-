<?php
// Storefront pages & actions. Each function handles one route (see app/routes.php).

require_once APP . '/views/sections.php';

function store_page(string $l, string $content, array $meta = [], int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo view('store/layout', ['l' => $l, 'content' => $content, 'meta' => $meta]);
    exit;
}

function store_not_found(string $l): never
{
    store_page($l, view('store/not_found'), ['title' => '404', 'noindex' => true], 404);
}

/** "/" and any un-prefixed path → "/ar/..." (or the visitor's saved language) */
function store_redirect_locale(string $path): never
{
    $lang = ($_COOKIE['lang'] ?? '') === 'en' ? 'en' : 'ar';
    $qs = ($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect("/$lang" . ($path === '/' ? '' : $path) . $qs, 307);
}

/**
 * Homepage rule: photos everywhere, videos only in the "See it in motion" showcase.
 * Drops video slides / mobile videos from the hero and video items from other blocks (blocks left empty are skipped).
 */
function home_without_video(string $type, string $json): ?string
{
    $d = (array) jdecode($json, []);
    if ($type === 'campaign' && ($d['layout'] ?? '') === 'showcase') return $json;   // the one place videos belong
    $isVid = fn ($m) => is_array($m) && ($m['kind'] ?? '') === 'video';
    if ($type === 'hero') {
        $slides = [];
        foreach ((array) ($d['slides'] ?? []) as $sl) {
            if (!is_array($sl) || $isVid($sl['media'] ?? null)) continue;
            if ($isVid($sl['mobileMedia'] ?? null)) unset($sl['mobileMedia']);
            $slides[] = $sl;
        }
        if (!$slides) return null;
        $d['slides'] = $slides;
    }
    foreach (['items', 'gallery'] as $k) {
        if (!isset($d[$k]) || !is_array($d[$k])) continue;
        $d[$k] = array_values(array_filter($d[$k], fn ($it) => !(is_array($it) && $isVid($it['media'] ?? null))));
        if (!$d[$k] && in_array($type, ['campaign', 'occasions'], true)) return null;
    }
    if ($isVid($d['media'] ?? null)) unset($d['media']);
    return jencode($d);
}

/** True when the file behind a media URL is really there (uploads can go missing on a server after a redeploy). */
function media_file_exists(string $url): bool
{
    if (str_starts_with($url, '/media/')) return is_file(upload_dir() . '/' . basename(parse_url($url, PHP_URL_PATH)));
    if (str_starts_with($url, '/brand/')) return is_file(ROOT . '/public' . rawurldecode(parse_url($url, PHP_URL_PATH)));
    return true;
}

/**
 * Every video for the showcase, in upload order: the media library's videos whose files exist;
 * if none are available, the brand videos shipped with the site in public/brand/videos (each one that has a .poster.jpg).
 */
function all_video_items(): array
{
    $vids = array_values(array_filter(rows("SELECT * FROM media WHERE kind = 'video' ORDER BY createdAt"), fn ($v) => media_file_exists($v['url'])));
    if ($vids) {
        $posters = media_map(array_column($vids, 'posterId'));
        return array_map(fn ($v) => ['media' => ['id' => $v['id'], 'url' => $v['url'], 'kind' => 'video', 'altAr' => $v['altAr'], 'altEn' => $v['altEn']]
            + (isset($posters[$v['posterId'] ?? '']) ? ['poster' => $posters[$v['posterId']]['url']] : [])], $vids);
    }
    $items = [];
    foreach (glob(ROOT . '/public/brand/videos/*.poster.jpg') ?: [] as $poster) {
        $mp4 = substr($poster, 0, -strlen('.poster.jpg')) . '.mp4';
        if (!is_file($mp4)) continue;
        $url = fn (string $f) => '/brand/videos/' . rawurlencode(basename($f));
        $items[] = ['media' => ['url' => $url($mp4), 'kind' => 'video', 'poster' => '/brand/videos/' . basename($poster), 'altAr' => 'عباية الكوكبة', 'altEn' => 'Al Kawkaba abaya']];
    }
    return $items;
}

/**
 * The "See it in motion" showcase always carries ALL videos: the ones arranged in the dashboard first (with their captions),
 * then any other video in the media library. If no showcase is published, a default one is placed after New Arrivals.
 */
function motion_showcase(?array $section): ?string
{
    $d = $section ? (array) jdecode($section['data'], []) : [
        'eyebrowAr' => 'شاهديها بالحركة', 'eyebrowEn' => 'See It in Motion', 'titleAr' => 'انسيابٌ يُرى قبل أن يُلمس', 'titleEn' => 'A Drape You Can See',
        'textAr' => 'التفاصيل الحقيقية تظهر حين تتحرك العباية؛ في انسياب القماش، ولمعة النقشة، وحركة الأكمام.',
        'textEn' => 'True detail shows when an abaya moves — in the fall of the fabric, the gleam of the pattern, the sway of the sleeves.',
        'layout' => 'showcase', 'items' => [],
    ];
    $items = array_values(array_filter((array) ($d['items'] ?? []), fn ($it) => is_array($it) && (($it['media']['kind'] ?? '') === 'video') && media_file_exists((string) ($it['media']['url'] ?? ''))));
    $have = array_column(array_column($items, 'media'), 'url');
    foreach (all_video_items() as $it) if (!in_array($it['media']['url'], $have, true)) $items[] = $it;
    if (!$items) return null;
    $d['layout'] = 'showcase';
    $d['items'] = $items;
    return jencode($d);
}

function store_home(string $l): never
{
    $s = settings();
    $secs = rows('SELECT * FROM sections WHERE published = 1 ORDER BY sort');
    $showcase = null;
    foreach ($secs as $x) {
        if ($x['type'] === 'campaign' && (jdecode($x['data'], [])['layout'] ?? '') === 'showcase') { $showcase = $x; break; }
    }
    if ($showcase) {
        $secs = array_values(array_filter($secs, fn ($x) => $x['id'] !== $showcase['id']));
        $entry = $showcase;
    } else {
        // no published showcase in this database: use the stored draft's texts (or the defaults)
        $draft = row("SELECT * FROM sections WHERE type = 'campaign' AND data LIKE ? ORDER BY sort LIMIT 1", ['%"showcase"%']);
        $entry = ['id' => 'motion', 'type' => 'campaign', 'data' => $draft['data'] ?? '{}', 'motion' => true];
    }
    // "See it in motion" always comes right after New Arrivals (or at the end if there is none)
    $at = array_search('products', array_column($secs, 'type'), true);
    array_splice($secs, $at === false ? count($secs) : $at + 1, 0, [$entry]);
    $html = '';
    foreach ($secs as $i => $x) {
        $isShowcase = !empty($x['motion']) || ($showcase && $x['id'] === $showcase['id']);
        $data = $isShowcase ? motion_showcase(!empty($x['motion']) && $x['data'] === '{}' ? null : $x) : home_without_video($x['type'], $x['data']);
        if ($data === null) continue;
        $sec = render_section($x['type'], $data, $l, $s['currency']);
        // every block after the first fades up as it scrolls into view (store.js)
        $html .= $i === 0 ? $sec : '<div data-reveal>' . $sec . '</div>';
    }
    store_page($l, $html, ['isHome' => true, 'path' => '']);
}

function store_shop(string $l): never
{
    $t = dict($l);
    $s = settings();
    $cats = category_list();
    $q = array_map(fn ($k) => query($k), ['q' => 'q', 'category' => 'category', 'tag' => 'tag', 'availability' => 'availability', 'size' => 'size', 'sort' => 'sort']);

    $where = '';
    $params = [];
    if ($q['q'] !== null) {
        $where .= ' AND (p.nameAr LIKE ? OR p.nameEn LIKE ? OR p.sku LIKE ? OR p.descAr LIKE ? OR p.descEn LIKE ?)';
        array_push($params, ...array_fill(0, 5, like($q['q'])));
    }
    if ($q['category'] !== null) { $where .= ' AND p.categoryId = (SELECT id FROM categories WHERE slug = ?)'; $params[] = $q['category']; }
    $flags = ['new' => 'isNew', 'limited' => 'isLimited', 'ready' => 'isReady', 'featured' => 'isFeatured'];
    if (isset($flags[$q['tag'] ?? ''])) $where .= " AND p.{$flags[$q['tag']]} = 1";
    if ($q['availability'] !== null) { $where .= ' AND p.availability = ?'; $params[] = $q['availability']; }
    if ($q['size'] !== null) { $where .= ' AND p.sizes LIKE ?'; $params[] = like('"' . $q['size'] . '"'); }
    $order = match ($q['sort']) { 'priceAsc' => 'p.price ASC', 'priceDesc' => 'p.price DESC', default => 'p.createdAt DESC' };
    $ps = find_products($where, $params, $order);

    $allSizes = [];
    foreach (rows('SELECT sizes FROM products WHERE published = 1 ORDER BY createdAt, id') as $r) {
        foreach ((array) jdecode($r['sizes'], []) as $x) if (is_string($x)) $allSizes[$x] = true;
    }
    $cat = current(array_filter($cats, fn ($c) => $c['slug'] === $q['category'])) ?: null;
    $title = $q['q'] !== null ? "“{$q['q']}”" : ($cat ? L($cat, 'name', $l)
        : match ($q['tag']) { 'new' => $t['newArrivals'], 'limited' => $t['limited'], 'ready' => $t['ready'], default => $t['shop'] });

    store_page($l, view('store/shop', compact('l', 't', 's', 'cats', 'q', 'ps', 'title') + ['allSizes' => array_keys($allSizes)]), ['title' => $t['shop']]);
}

function store_product(string $l, string $slug): never
{
    $p = row('SELECT * FROM products WHERE slug = ? AND published = 1', [$slug]);
    if (!$p) store_not_found($l);
    [$p] = with_gallery([$p]);
    $s = settings();
    $t = dict($l);
    $posters = media_map(array_column($p['gallery'], 'posterId'));
    $collection = $p['collectionId'] ? row('SELECT * FROM collections WHERE id = ?', [$p['collectionId']]) : null;
    $near = [];
    $nearParams = [$p['id']];
    if ($p['collectionId']) { $near[] = 'p.collectionId = ?'; $nearParams[] = $p['collectionId']; }
    if ($p['categoryId']) { $near[] = 'p.categoryId = ?'; $nearParams[] = $p['categoryId']; }
    $related = find_products('AND p.id <> ?' . ($near ? ' AND (' . implode(' OR ', $near) . ')' : ''), $nearParams, 'p.createdAt DESC', 4);

    $name = L($p, 'name', $l);
    $images = array_values(array_filter($p['gallery'], fn ($m) => $m['kind'] === 'image'));
    $site = rtrim((string) config('site_url', ''), '/');
    $ld = [
        '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $name, 'sku' => $p['sku'], 'description' => L($p, 'desc', $l),
        'image' => array_map(fn ($m) => $site . $m['url'], $images), 'brand' => ['@type' => 'Brand', 'name' => L($s['brand'], 'name', $l)],
        'offers' => ['@type' => 'Offer', 'price' => $p['salePrice'] ?? $p['price'], 'priceCurrency' => $s['currency'],
            'availability' => 'https://schema.org/' . match ($p['availability']) { 'out_of_stock' => 'OutOfStock', 'made_to_order' => 'PreOrder', default => 'InStock' }],
    ];
    store_page($l, view('store/product', compact('l', 't', 's', 'p', 'posters', 'collection', 'related', 'name')), [
        'title' => $p['seoTitle'] ?: $name,
        'description' => $p['seoDesc'] ?: mb_substr(L($p, 'desc', $l), 0, 160),
        'image' => $images[0]['url'] ?? null,
        'canonical' => "/$l/product/" . rawurlencode($p['slug']),
        'path' => '/product/' . rawurlencode($p['slug']),
        'jsonld' => $ld,
    ]);
}

function store_collections(string $l): never
{
    $t = dict($l);
    $cols = rows('SELECT * FROM collections WHERE published = 1 ORDER BY sort');
    $media = media_map(array_column($cols, 'coverId'));
    store_page($l, view('store/collections', compact('l', 't', 'cols', 'media')), ['title' => $t['collections']]);
}

function store_collection(string $l, string $slug): never
{
    $c = row('SELECT * FROM collections WHERE slug = ? AND published = 1', [$slug]);
    if (!$c) store_not_found($l);
    $ps = find_products('AND p.collectionId = ?', [$c['id']]);
    $media = media_map([$c['coverId'], $c['coverMobileId']]);
    store_page($l, view('store/collection', ['l' => $l, 'c' => $c, 'ps' => $ps, 'media' => $media, 'currency' => settings()['currency']]), [
        'title' => L($c, 'name', $l), 'description' => L($c, 'desc', $l), 'path' => '/collections/' . rawurlencode($c['slug']),
    ]);
}

/** Reuses the story / campaign / values blocks managed in Admin → Homepage (even if hidden on the homepage). */
function store_story(string $l): never
{
    $s = settings();
    $t = dict($l);
    // Our Story page = the designer's story (text + her photo) followed by "Our Promise" — nothing else.
    $html = '';
    foreach (['story', 'values'] as $type) {
        $x = row('SELECT * FROM sections WHERE type = ? ORDER BY published DESC, sort LIMIT 1', [$type]);
        if (!$x) continue;
        $d = (array) jdecode($x['data'], []);
        unset($d['gallery']); // the homepage detail strip doesn't belong here
        $html .= render_section($type, jencode($d), $l, $s['currency']);
    }
    store_page($l, $html, ['title' => $t['story']]);
}

function store_policy(string $l, string $slug): never
{
    $map = ['shipping' => 'shippingPolicy', 'returns' => 'returns', 'privacy' => 'privacy', 'terms' => 'terms'];
    if (!isset($map[$slug])) store_not_found($l);
    $t = dict($l);
    $body = L(settings()['policies'], $slug, $l) ?: '—';
    $html = '<article class="mx-auto max-w-2xl px-6 py-20 md:py-28"><h1 class="display text-center text-4xl md:text-5xl">' . e($t[$map[$slug]]) . '</h1>'
        . '<div class="mt-12 whitespace-pre-line text-sm leading-8 text-stone">' . e($body) . '</div></article>';
    store_page($l, $html, ['title' => $t[$map[$slug]]]);
}

function store_track(string $l): never
{
    $n = query('n');
    $e = query('e');
    $notFound = false;
    if ($n && $e) {
        $o = row('SELECT number, email FROM orders WHERE number = ?', [strtoupper(trim($n))]);
        if ($o && $o['email'] === strtolower(trim($e))) redirect("/$l/order/{$o['number']}?e=" . rawurlencode($o['email']));
        $notFound = true;
    }
    store_page($l, view('store/track', compact('l', 'n', 'e', 'notFound')), ['title' => dict($l)['trackOrder'], 'noindex' => true]);
}

function store_wishlist(string $l): never
{
    $t = dict($l);
    $html = '<div class="mx-auto max-w-[1600px] px-4 pb-24 md:px-10"><h1 class="display py-14 text-center text-4xl md:py-20 md:text-6xl">' . e($t['wishlist']) . '</h1>'
        . '<div data-wishlist class="grid grid-cols-2 gap-x-3 gap-y-12 md:gap-x-6 lg:grid-cols-4"></div>'
        . '<div data-wishlist-empty hidden class="py-20 text-center"><p class="text-stone">' . e($t['emptyWishlist']) . '</p><a href="/' . $l . '/shop" class="btn btn-dark mt-8">' . e($t['continueShopping']) . '</a></div></div>';
    store_page($l, $html, ['title' => $t['wishlist'], 'noindex' => true]);
}

/* ---------- product fragments for the browser (wishlist, recently viewed, quick view) ---------- */

function store_cards(string $l): never
{
    $ids = array_slice(array_filter(explode(',', query('ids') ?? '')), 0, 24);
    $currency = settings()['currency'];
    header('Content-Type: text/html; charset=utf-8');
    foreach (products_by_ids($ids) as $p) echo product_card($p, $l, $currency);
    exit;
}

function store_quickview(string $l, string $id): never
{
    $p = row('SELECT * FROM products WHERE id = ? AND published = 1', [$id]);
    if (!$p) not_found_plain();
    [$p] = with_gallery([$p]);
    header('Content-Type: text/html; charset=utf-8');
    echo view('store/quickview', ['l' => $l, 'p' => $p, 'currency' => settings()['currency']]);
    exit;
}

/* ---------- checkout & orders ---------- */

function store_checkout(string $l): never
{
    $s = settings();
    $u = current_user();
    $countries = array_values(array_filter(array_map('trim', explode(',', (string) $s['shipping']['countries']))));
    store_page($l, view('store/checkout', [
        'l' => $l, 't' => dict($l), 'methods' => payment_methods($s), 'countries' => $countries, 'user' => $u,
        'shipping' => ['flat' => (float) $s['shipping']['flat'], 'freeOver' => (float) $s['shipping']['freeOver']],
        'bankDetails' => $l === 'ar' ? $s['payments']['bankDetailsAr'] : $s['payments']['bankDetailsEn'],
    ]), ['title' => 'Checkout', 'noindex' => true]);
}

function store_check_discount(string $l): never
{
    $b = json_body();
    json_out(compute_discount(substr((string) ($b['code'] ?? ''), 0, 40), (float) ($b['subtotal'] ?? 0)));
}

/** POST JSON {name,email,phone,country,city,address,notes,payment,code,lines[]} → {url} | {error} */
function store_place_order(string $l): never
{
    $b = json_body();
    $str = fn (string $k, int $min, int $max) => is_string($b[$k] ?? null) && mb_strlen(trim($b[$k])) >= $min && mb_strlen(trim($b[$k])) <= $max ? trim($b[$k]) : null;
    $d = [
        'name' => $str('name', 2, 100), 'phone' => $str('phone', 6, 30), 'country' => $str('country', 2, 60),
        'city' => $str('city', 2, 80), 'address' => $str('address', 4, 300),
        'email' => filter_var(trim((string) ($b['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: null,
    ];
    $notes = is_string($b['notes'] ?? null) ? $b['notes'] : '';
    $payment = is_string($b['payment'] ?? null) ? $b['payment'] : '';
    $code = is_string($b['code'] ?? null) ? substr($b['code'], 0, 40) : null;
    $lines = is_array($b['lines'] ?? null) ? array_values($b['lines']) : [];
    $linesOk = $lines && count($lines) <= 30 && !array_filter($lines, fn ($x) => !is_array($x) || !is_string($x['productId'] ?? null)
        || !is_string($x['size'] ?? null) || mb_strlen($x['size']) > 20 || !is_string($x['color'] ?? null) || mb_strlen($x['color']) > 40
        || !is_int($x['qty'] ?? null) || $x['qty'] < 1 || $x['qty'] > 10);
    if (in_array(null, $d, true) || mb_strlen($notes) > 500 || !$linesOk) json_out(['error' => 'required']);

    $s = settings();
    if (!in_array($payment, array_column(payment_methods($s), 'id'), true)) json_out(['error' => 'required']);

    $ids = array_values(array_unique(array_column($lines, 'productId')));
    $products = array_column(with_gallery(rows('SELECT * FROM products WHERE published = 1 AND id IN (' . in_list($ids) . ')', $ids)), null, 'id');
    $items = [];
    foreach ($lines as $x) {
        $pr = $products[$x['productId']] ?? null;
        if (!$pr || $pr['availability'] === 'out_of_stock') json_out(['error' => 'outOfStock']);
        if ($pr['sizes'] && !in_array($x['size'], $pr['sizes'], true)) json_out(['error' => 'selectSize']);
        if ($pr['availability'] === 'in_stock' && $pr['stock'] < $x['qty']) json_out(['error' => 'outOfStock']);
        $items[] = [
            'productId' => $pr['id'], 'sku' => $pr['sku'], 'slug' => $pr['slug'], 'nameAr' => $pr['nameAr'], 'nameEn' => $pr['nameEn'],
            'image' => $pr['gallery'][0]['url'] ?? null, 'size' => $x['size'], 'color' => $x['color'], 'qty' => $x['qty'],
            'price' => $pr['salePrice'] ?? $pr['price'],
        ];
    }
    $subtotal = round(array_sum(array_map(fn ($x) => $x['price'] * $x['qty'], $items)), 2);
    $disc = compute_discount($code, $subtotal);
    if ($disc === null) json_out(['error' => 'invalidCode']);
    $shipping = shipping_cost($subtotal - $disc['amount'], $s);
    $total = round($subtotal - $disc['amount'] + $shipping, 2);
    $user = current_user();
    $number = order_number();
    $email = strtolower($d['email']);

    try {
        tx(function () use ($items, $products, $disc, $number, $user, $email, $d, $notes, $subtotal, $shipping, $total, $s, $payment) {
            foreach ($items as $it) {
                if ($products[$it['productId']]['availability'] !== 'in_stock') continue;
                $n = q('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$it['qty'], $it['productId'], $it['qty']])->rowCount();
                if ($n === 0) throw new RuntimeException('stock');
            }
            if ($disc['code']) q('UPDATE discounts SET used = used + 1 WHERE code = ?', [$disc['code']]);
            insert('orders', [
                'id' => new_id(), 'number' => $number, 'userId' => $user['id'] ?? null, 'email' => $email, 'name' => $d['name'], 'phone' => $d['phone'],
                'country' => $d['country'], 'city' => $d['city'], 'address' => $d['address'], 'notes' => $notes, 'items' => jencode($items),
                'subtotal' => $subtotal, 'discount' => $disc['amount'], 'discountCode' => $disc['code'], 'shipping' => $shipping, 'total' => $total,
                'currency' => $s['currency'], 'paymentMethod' => $payment, 'history' => jencode([['at' => date('c'), 'note' => 'Order placed']]),
                'createdAt' => now(),
            ]);
        });
    } catch (Throwable) {
        json_out(['error' => 'outOfStock']);
    }
    if ($payment === 'card') {
        $url = create_checkout(['number' => $number, 'total' => $total, 'currency' => $s['currency']], $s);
        if ($url) json_out(['url' => $url]);
    }
    // wa=1: the confirmation page hands the order to WhatsApp right away (once, just after ordering)
    json_out(['url' => "/$l/order/$number?e=" . rawurlencode($email) . (wa_number() !== '' ? '&wa=1' : '')]);
}

function store_order(string $l, string $number): never
{
    $o = row('SELECT * FROM orders WHERE number = ?', [$number]);
    $u = current_user();
    // only the buyer (by email link or account) may view
    if (!$o || !($o['email'] === strtolower((string) query('e')) || ($u && $o['userId'] === $u['id']))) store_not_found($l);
    $s = settings();
    $t = dict($l);
    $bank = $l === 'ar' ? $s['payments']['bankDetailsAr'] : $s['payments']['bankDetailsEn'];
    $html = '<div class="mx-auto max-w-2xl px-4 py-16 md:py-24">'
        . '<p class="eyebrow text-center text-gold">' . e($t['orderNumber'] . ' ' . $o['number']) . '</p>'
        . '<h1 class="display mt-4 text-center text-3xl md:text-5xl">' . e($t['orderPlaced']) . '</h1>'
        . ($o['paymentMethod'] === 'bank' && $o['paymentStatus'] === 'pending' && $bank ? '<p class="mt-8 whitespace-pre-line bg-sand p-6 text-sm leading-7">' . e($bank) . '</p>' : '')
        . (($wa = wa_link(wa_order_message($o, $l))) !== ''
            ? '<div class="mt-10 text-center">' . (query('wa') ? '<p class="mb-4 text-sm text-stone">' . e($t['waRedirect']) . '</p>' : '')
              . '<a href="' . e($wa) . '" data-wa-order' . (query('wa') ? ' data-wa-auto' : '') . ' class="btn border-[#1f9e55] bg-[#1f9e55] text-white hover:bg-[#1a8a4a]">' . wa_icon('h-5 w-5') . '<span>' . e($t['sendWhatsapp']) . '</span></a></div>'
            : '')
        . '<div class="mt-12">' . order_summary($o, $l) . '</div>'
        . '<div class="mt-12 text-center"><a href="/' . $l . '/shop" class="btn btn-dark">' . e($t['continueShopping']) . '</a></div></div>';
    store_page($l, $html, ['title' => $t['orderNumber'], 'noindex' => true]);
}

/* ---------- account ---------- */

function store_account(string $l, array $errors = []): never
{
    $t = dict($l);
    $u = current_user();
    if (!$u) {
        store_page($l, view('store/auth', ['l' => $l, 't' => $t, 'next' => safe_next(query('next') ?? input('next')), 'errors' => $errors, 'old' => $_POST]), ['title' => $t['account'], 'noindex' => true]);
    }
    $orders = rows('SELECT * FROM orders WHERE userId = ? OR email = ? ORDER BY createdAt DESC', [$u['id'], $u['email']]);
    store_page($l, view('store/account', compact('l', 't', 'u', 'orders')), ['title' => $t['account'], 'noindex' => true]);
}

function store_login(string $l): never
{
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(trim(input('email')))]);
    if (!$u || !check_pw(input('password'), $u['passwordHash'])) store_account($l, ['login' => 'invalidLogin']);
    start_session($u['id']);
    redirect(safe_next(input('next')) ?? "/$l/account");
}

function store_register(string $l): never
{
    $name = trim(input('name'));
    $email = strtolower(trim(input('email')));
    $phone = trim(input('phone'));
    $pw = input('password');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8 || strlen($pw) > 100 || mb_strlen($phone) > 30) {
        store_account($l, ['register' => 'required']);
    }
    if (val('SELECT 1 FROM users WHERE email = ?', [$email])) store_account($l, ['register' => 'emailTaken']);
    $id = new_id();
    insert('users', ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone ?: null, 'passwordHash' => hash_pw(ascii_digits($pw)), 'role' => 'CUSTOMER', 'createdAt' => now()]);
    start_session($id);
    redirect("/$l/account");
}

function store_logout(string $l): never
{
    end_session();
    redirect("/$l");
}

function store_subscribe(string $l): never
{
    $email = strtolower(trim(input('email')));
    $ok = (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    if ($ok) q('INSERT IGNORE INTO subscribers (id, email, createdAt) VALUES (?, ?, ?)', [new_id(), $email, now()]);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_out(['ok' => $ok]);
    redirect("/$l?subscribed=1#newsletter");
}
