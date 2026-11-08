<?php
// Admin dashboard (/admin). Every handler checks require_staff(); settings & staff are ADMIN-only.

function admin_page(string $title, string $content, array $u): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo view('admin/layout', ['title' => $title, 'content' => $content, 'u' => $u]);
    exit;
}

function admin_back(string $url, string $flash = 'saved'): never
{
    redirect($url . (str_contains($url, '?') ? '&' : '?') . $flash . '=1');
}

/* ---------- auth ---------- */

function admin_login_page(?string $error = null): never
{
    $firstRun = !val("SELECT 1 FROM users WHERE role IN ('ADMIN', 'EDITOR') LIMIT 1");
    header('Content-Type: text/html; charset=utf-8');
    echo view('admin/login', ['error' => $error, 'firstRun' => $firstRun]);
    exit;
}

function admin_login(): never
{
    // first run: no staff yet → the form creates the first ADMIN account
    if (!val("SELECT 1 FROM users WHERE role IN ('ADMIN', 'EDITOR') LIMIT 1")) {
        $email = strtolower(trim(input('email')));
        $name = trim(input('name')) ?: 'Store Owner';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen(input('password')) < 8) admin_login_page('Enter a valid email and a password of at least 8 characters');
        $id = val('SELECT id FROM users WHERE email = ?', [$email]) ?: new_id();
        q('INSERT INTO users (id, email, name, passwordHash, role, createdAt) VALUES (?, ?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE role = VALUES(role), passwordHash = VALUES(passwordHash), name = VALUES(name)',
          [$id, $email, $name, hash_pw(ascii_digits(input('password'))), 'ADMIN', now()]);
        start_session($id);
        redirect('/admin');
    }
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(trim(input('email')))]);
    if (!$u || $u['role'] === 'CUSTOMER' || !check_pw(input('password'), $u['passwordHash'])) admin_login_page('Invalid email or password');
    start_session($u['id']);
    redirect('/admin');
}

function admin_logout(): never
{
    end_session();
    redirect('/admin/login');
}

/* ---------- overview ---------- */

function admin_overview(): never
{
    $u = require_staff();
    $since = date('Y-m-d H:i:s', time() - 30 * 86400);
    $data = [
        's' => settings(),
        'recent' => rows('SELECT * FROM orders ORDER BY createdAt DESC LIMIT 8'),
        'month' => row("SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS n FROM orders WHERE createdAt >= ? AND status <> 'cancelled'", [$since]),
        'pending' => (int) val("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
        'products' => (int) val('SELECT COUNT(*) FROM products WHERE published = 1'),
        'low' => rows("SELECT id, nameEn, stock FROM products WHERE availability = 'in_stock' AND stock <= 3 AND published = 1"),
        'customers' => (int) val("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'"),
        'subs' => (int) val('SELECT COUNT(*) FROM subscribers'),
    ];
    admin_page('Overview', view('admin/overview', $data), $u);
}

/* ---------- products ---------- */

function admin_products(): never
{
    $u = require_staff();
    $q = query('q');
    $where = $q ? 'WHERE p.nameEn LIKE ? OR p.nameAr LIKE ? OR p.sku LIKE ?' : '';
    $ps = with_gallery(rows("SELECT p.*, c.nameEn AS collectionName FROM products p LEFT JOIN collections c ON c.id = p.collectionId $where ORDER BY p.createdAt DESC", $q ? array_fill(0, 3, like($q)) : []));
    admin_page('Products', view('admin/products', ['ps' => $ps, 'q' => $q, 's' => settings()]), $u);
}

function product_blank(): array
{
    return [
        'id' => null, 'nameAr' => '', 'nameEn' => '', 'slug' => '', 'sku' => '', 'descAr' => '', 'descEn' => '', 'fabricAr' => '', 'fabricEn' => '',
        'detailsAr' => '', 'detailsEn' => '', 'careAr' => '', 'careEn' => '', 'deliveryAr' => '', 'deliveryEn' => '', 'price' => 0, 'salePrice' => null,
        'stock' => 0, 'availability' => 'in_stock', 'sizes' => ['52', '54', '56', '58', '60'], 'colors' => [['nameAr' => 'أسود', 'nameEn' => 'Black', 'hex' => '#0e0e10']],
        'isNew' => true, 'isFeatured' => false, 'isLimited' => false, 'isReady' => false, 'published' => true, 'seoTitle' => '', 'seoDesc' => '',
        'categoryId' => null, 'collectionId' => null, 'gallery' => [],
    ];
}

function admin_product_edit(string $id, ?array $p = null, ?string $error = null): never
{
    $u = require_staff();
    if (!$p) {
        if ($id === 'new') $p = product_blank();
        else {
            $row = row('SELECT * FROM products WHERE id = ?', [$id]);
            if (!$row) admin_not_found($u);
            [$p] = with_gallery([$row]);
        }
    }
    admin_page($id === 'new' ? 'New product' : $p['nameEn'], view('admin/product_form', [
        'p' => $p, 'isNew' => $id === 'new', 'error' => $error,
        'categories' => rows('SELECT id, nameEn FROM categories ORDER BY sort'),
        'collections' => rows('SELECT id, nameEn FROM collections ORDER BY sort'),
    ]), $u);
}

function admin_product_save(string $id): never
{
    require_staff();
    $str = fn (string $k) => trim(input($k));
    $p = ['id' => $id === 'new' ? null : $id];
    foreach (['nameAr', 'nameEn', 'slug', 'sku', 'descAr', 'descEn', 'fabricAr', 'fabricEn', 'detailsAr', 'detailsEn', 'careAr', 'careEn', 'deliveryAr', 'deliveryEn', 'seoTitle', 'seoDesc'] as $k) $p[$k] = $str($k);
    $p['price'] = max(0, (float) input('price', '0'));
    $p['salePrice'] = input('salePrice') === '' ? null : max(0, (float) input('salePrice'));
    $p['stock'] = max(0, (int) input('stock', '0'));
    $p['availability'] = in_array(input('availability'), ['in_stock', 'made_to_order', 'out_of_stock'], true) ? input('availability') : 'in_stock';
    $p['sizes'] = array_values(array_filter(array_map('trim', explode(',', input('sizes'))), 'strlen'));
    $p['colors'] = [];
    foreach ((array) ($_POST['colors_hex'] ?? []) as $i => $hex) {
        $p['colors'][] = ['nameAr' => trim((string) ($_POST['colors_ar'][$i] ?? '')), 'nameEn' => trim((string) ($_POST['colors_en'][$i] ?? '')), 'hex' => (string) $hex];
    }
    foreach (['isNew', 'isFeatured', 'isLimited', 'isReady', 'published'] as $k) $p[$k] = isset($_POST[$k]);
    $p['categoryId'] = input('categoryId') ?: null;
    $p['collectionId'] = input('collectionId') ?: null;
    $mediaIds = array_values(array_filter((array) jdecode(input('media'), []), 'is_string'));
    $byId = array_column($mediaIds ? rows('SELECT * FROM media WHERE id IN (' . in_list($mediaIds) . ')', $mediaIds) : [], null, 'id');
    $p['gallery'] = array_values(array_filter(array_map(fn ($m) => $byId[$m] ?? null, $mediaIds)));

    $error = null;
    if ($p['nameAr'] === '' || $p['nameEn'] === '') $error = 'Arabic and English names are required';
    elseif ($p['sku'] === '') $error = 'SKU is required';
    elseif (array_filter($p['colors'], fn ($c) => !preg_match('/^#[0-9a-fA-F]{6}$/', $c['hex']))) $error = 'Colour codes must look like #000000';
    elseif ($p['salePrice'] !== null && $p['salePrice'] >= $p['price']) $error = 'Discount price must be lower than the price';
    if ($error) admin_product_edit($id, $p, $error);

    $slug = slugify($p['slug'] ?: $p['nameEn']);
    $data = $p;
    unset($data['id'], $data['gallery']);
    $data['slug'] = $slug;
    $data['sizes'] = jencode($p['sizes']);
    $data['colors'] = jencode($p['colors']);
    try {
        $pid = tx(function () use ($p, $data) {
            $pid = $p['id'];
            if ($pid) update('products', $data, 'id = ?', [$pid]);
            else insert('products', ['id' => $pid = new_id(), 'createdAt' => now()] + $data);
            q('DELETE FROM product_media WHERE productId = ?', [$pid]);
            foreach ($p['gallery'] as $sort => $m) insert('product_media', ['productId' => $pid, 'mediaId' => $m['id'], 'sort' => $sort]);
            return $pid;
        });
    } catch (PDOException $e) {
        admin_product_edit($id, $p, $e->getCode() === '23000' ? 'Slug or SKU already used by another product' : 'Could not save product');
    }
    admin_back("/admin/products/$pid");
}

function admin_product_delete(string $id): never
{
    require_staff();
    q('DELETE FROM products WHERE id = ?', [$id]);
    redirect('/admin/products');
}

/* ---------- collections & categories ---------- */

function admin_collections(): never
{
    $u = require_staff();
    $cols = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.collectionId = c.id) AS n FROM collections c ORDER BY c.sort');
    admin_page('Collections', view('admin/collections', ['cols' => $cols, 'covers' => media_map(array_column($cols, 'coverId'))]), $u);
}

function admin_collection_edit(string $id, ?array $c = null, ?string $error = null): never
{
    $u = require_staff();
    $products = rows('SELECT id, nameEn, sku, collectionId FROM products ORDER BY createdAt DESC');
    if (!$c) {
        if ($id === 'new') $c = ['id' => null, 'nameAr' => '', 'nameEn' => '', 'slug' => '', 'descAr' => '', 'descEn' => '', 'coverId' => null, 'coverMobileId' => null, 'published' => 1, 'productIds' => []];
        else {
            $c = row('SELECT * FROM collections WHERE id = ?', [$id]);
            if (!$c) admin_not_found($u);
            $c['productIds'] = array_column(array_filter($products, fn ($p) => $p['collectionId'] === $c['id']), 'id');
        }
    }
    admin_page($id === 'new' ? 'New collection' : $c['nameEn'], view('admin/collection_form', [
        'c' => $c, 'error' => $error, 'media' => media_map([$c['coverId'], $c['coverMobileId']]),
        'products' => array_map(fn ($p) => ['id' => $p['id'], 'label' => $p['nameEn'], 'sub' => $p['sku']], $products),
    ]), $u);
}

function admin_collection_save(string $id): never
{
    require_staff();
    $c = [
        'id' => $id === 'new' ? null : $id, 'nameAr' => trim(input('nameAr')), 'nameEn' => trim(input('nameEn')), 'slug' => trim(input('slug')),
        'descAr' => input('descAr'), 'descEn' => input('descEn'), 'coverId' => input('coverId') ?: null, 'coverMobileId' => input('coverMobileId') ?: null,
        'published' => isset($_POST['published']), 'productIds' => array_values(array_filter((array) jdecode(input('productIds'), []), 'is_string')),
    ];
    if ($c['nameAr'] === '' || $c['nameEn'] === '') admin_collection_edit($id, $c, 'Arabic and English names are required');
    $data = ['nameAr' => $c['nameAr'], 'nameEn' => $c['nameEn'], 'slug' => slugify($c['slug'] ?: $c['nameEn']), 'descAr' => $c['descAr'], 'descEn' => $c['descEn'],
        'coverId' => $c['coverId'], 'coverMobileId' => $c['coverMobileId'], 'published' => $c['published']];
    try {
        $cid = tx(function () use ($c, $data) {
            $cid = $c['id'];
            if ($cid) update('collections', $data, 'id = ?', [$cid]);
            else insert('collections', ['id' => $cid = new_id(), 'sort' => (int) val('SELECT COUNT(*) FROM collections'), 'createdAt' => now()] + $data);
            $ids = $c['productIds'];
            q('UPDATE products SET collectionId = NULL WHERE collectionId = ?' . ($ids ? ' AND id NOT IN (' . in_list($ids) . ')' : ''), [$cid, ...$ids]);
            if ($ids) q('UPDATE products SET collectionId = ? WHERE id IN (' . in_list($ids) . ')', [$cid, ...$ids]);
            return $cid;
        });
    } catch (PDOException) {
        admin_collection_edit($id, $c, 'Slug already used');
    }
    admin_back("/admin/collections/$cid");
}

function admin_collection_delete(string $id): never
{
    require_staff();
    q('DELETE FROM collections WHERE id = ?', [$id]);
    redirect('/admin/collections');
}

function admin_categories(): never
{
    $u = require_staff();
    $cats = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.categoryId = c.id) AS n FROM categories c ORDER BY c.sort');
    admin_page('Categories', view('admin/categories', ['cats' => $cats]), $u);
}

function admin_category_save(): never
{
    require_staff();
    $nameAr = trim(input('nameAr'));
    $nameEn = trim(input('nameEn'));
    if ($nameAr !== '' && $nameEn !== '') {
        $data = ['nameAr' => $nameAr, 'nameEn' => $nameEn, 'slug' => slugify(input('slug') ?: $nameEn)];
        try {
            if ($id = input('id')) update('categories', $data, 'id = ?', [$id]);
            else insert('categories', ['id' => new_id(), 'sort' => (int) val('SELECT COUNT(*) FROM categories')] + $data);
        } catch (PDOException) {
            admin_back('/admin/categories', 'taken');
        }
    }
    redirect('/admin/categories');
}

function admin_category_delete(string $id): never
{
    require_staff();
    q('DELETE FROM categories WHERE id = ?', [$id]);
    redirect('/admin/categories');
}

/** Generic reorder for collections, categories and sections. POST JSON {model, ids[]} */
function admin_reorder(): never
{
    require_staff();
    $b = json_body();
    $table = ['collection' => 'collections', 'category' => 'categories', 'section' => 'sections'][$b['model'] ?? ''] ?? null;
    if (!$table || !is_array($b['ids'] ?? null)) json_out(['error' => 'Bad request'], 400);
    tx(function () use ($table, $b) {
        foreach (array_values($b['ids']) as $sort => $id) q("UPDATE `$table` SET sort = ? WHERE id = ?", [$sort, (string) $id]);
    });
    json_out(['ok' => true]);
}

/* ---------- homepage sections ---------- */

function admin_homepage(): never
{
    $u = require_staff();
    admin_page('Homepage', view('admin/homepage', ['secs' => rows('SELECT * FROM sections ORDER BY sort'), 'types' => section_types()]), $u);
}

function admin_section_add(): never
{
    require_staff();
    $type = input('type');
    if (!isset(section_types()[$type])) redirect('/admin/homepage');
    $id = new_id();
    insert('sections', ['id' => $id, 'type' => $type, 'sort' => (int) val('SELECT COUNT(*) FROM sections'), 'published' => false, 'data' => '{}']);
    redirect("/admin/homepage/$id");
}

function admin_section_edit(string $id): never
{
    $u = require_staff();
    $s = row('SELECT * FROM sections WHERE id = ?', [$id]);
    $types = section_types();
    if (!$s || !isset($types[$s['type']])) admin_not_found($u);
    admin_page('Edit section', view('admin/section_form', [
        's' => $s, 'def' => $types[$s['type']],
        'products' => array_map(fn ($p) => ['id' => $p['id'], 'label' => $p['nameEn'], 'sub' => $p['sku']], rows('SELECT id, nameEn, sku FROM products ORDER BY createdAt DESC')),
        'collections' => array_map(fn ($c) => ['id' => $c['id'], 'label' => $c['nameEn']], rows('SELECT id, nameEn FROM collections ORDER BY sort')),
    ]), $u);
}

function admin_section_save(string $id): never
{
    require_staff();
    $data = json_decode(input('data'), true);
    if (!is_array($data)) $data = [];
    update('sections', ['data' => jencode((object) $data), 'published' => isset($_POST['published'])], 'id = ?', [$id]);
    admin_back("/admin/homepage/$id");
}

function admin_section_action(string $id, string $action): never
{
    require_staff();
    if ($action === 'toggle') q('UPDATE sections SET published = 1 - published WHERE id = ?', [$id]);
    if ($action === 'delete') q('DELETE FROM sections WHERE id = ?', [$id]);
    if ($action === 'duplicate' && ($s = row('SELECT * FROM sections WHERE id = ?', [$id]))) {
        insert('sections', ['id' => new_id(), 'type' => $s['type'], 'data' => $s['data'], 'published' => false, 'sort' => (int) $s['sort'] + 1]);
    }
    redirect('/admin/homepage');
}

/* ---------- media ---------- */

function admin_media(): never
{
    $u = require_staff();
    $items = rows('SELECT m.*, (SELECT COUNT(*) FROM product_media pm WHERE pm.mediaId = m.id) AS used FROM media m ORDER BY m.createdAt DESC');
    admin_page('Media library', view('admin/media', ['items' => $items]), $u);
}

function admin_api_media_list(): never
{
    require_staff();
    $kind = query('kind');
    json_out($kind ? rows('SELECT * FROM media WHERE kind = ? ORDER BY createdAt DESC LIMIT 500', [$kind]) : rows('SELECT * FROM media ORDER BY createdAt DESC LIMIT 500'));
}

function admin_api_media_upload(): never
{
    require_staff();
    $f = $_FILES['file'] ?? null;
    $files = [];
    if ($f && is_array($f['name'])) {
        foreach ($f['name'] as $i => $name) $files[] = ['name' => $name, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    } elseif ($f) $files[] = $f;
    if (!$files && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) json_out(['media' => [], 'errors' => ['Upload too large for the server (raise post_max_size / upload_max_filesize)']], 400);
    $out = $errors = [];
    foreach ($files as $file) {
        try { $out[] = save_upload($file); } catch (Throwable $e) { $errors[] = $file['name'] . ': ' . $e->getMessage(); }
    }
    json_out(['media' => $out, 'errors' => $errors], $out ? 200 : 400);
}

/** POST JSON {altAr?, altEn?, posterId?} */
function admin_api_media_update(string $id): never
{
    require_staff();
    $b = json_body();
    $data = array_intersect_key($b, array_flip(['altAr', 'altEn', 'posterId']));
    foreach ($data as $k => $v) $data[$k] = $v === null ? null : mb_substr((string) $v, 0, 255);
    if ($data) update('media', $data, 'id = ?', [$id]);
    json_out(['ok' => true]);
}

function admin_api_media_delete(string $id): never
{
    require_staff();
    delete_media($id);
    json_out(['ok' => true]);
}

/* ---------- orders ---------- */

const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];
const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];
const SHIP_STATUSES = ['unfulfilled', 'shipped', 'delivered', 'returned'];

function admin_orders(): never
{
    $u = require_staff();
    $status = query('status');
    $q = query('q');
    $where = [];
    $params = [];
    if ($status) { $where[] = 'status = ?'; $params[] = $status; }
    if ($q) {
        $where[] = '(number LIKE ? OR email LIKE ? OR name LIKE ? OR phone LIKE ?)';
        array_push($params, like(strtoupper($q)), like(strtolower($q)), like($q), like($q));
    }
    $orders = rows('SELECT * FROM orders' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY createdAt DESC LIMIT 200', $params);
    admin_page('Orders', view('admin/orders', compact('orders', 'status', 'q')), $u);
}

function admin_order(string $id): never
{
    $u = require_staff();
    $o = row('SELECT * FROM orders WHERE id = ?', [$id]);
    if (!$o) admin_not_found($u);
    $customer = $o['userId'] ? row('SELECT id FROM users WHERE id = ?', [$o['userId']]) : null;
    admin_page('Order ' . $o['number'], view('admin/order', ['o' => $o, 'customer' => $customer]), $u);
}

function admin_order_update(string $id): never
{
    $u = require_staff();
    $o = row('SELECT * FROM orders WHERE id = ?', [$id]);
    if (!$o) admin_not_found($u);
    $d = [
        'status' => in_array(input('status'), ORDER_STATUSES, true) ? input('status') : $o['status'],
        'paymentStatus' => in_array(input('paymentStatus'), PAYMENT_STATUSES, true) ? input('paymentStatus') : $o['paymentStatus'],
        'shipStatus' => in_array(input('shipStatus'), SHIP_STATUSES, true) ? input('shipStatus') : $o['shipStatus'],
        'tracking' => mb_substr(trim(input('tracking')), 0, 200),
    ];
    $changes = [];
    foreach ($d as $k => $v) if ($o[$k] !== $v) $changes[] = "$k: " . ($o[$k] ?: '—') . ' → ' . ($v ?: '—');
    $note = implode(' · ', array_filter([...$changes, mb_substr(trim(input('note')), 0, 500)]));
    $history = (array) jdecode($o['history'], []);
    if ($note !== '') $history[] = ['at' => date('c'), 'note' => $note, 'by' => $u['name']];
    tx(function () use ($o, $d, $history, $id) {
        // restock when an order is cancelled
        if ($d['status'] === 'cancelled' && $o['status'] !== 'cancelled') {
            foreach ((array) jdecode($o['items'], []) as $it) {
                q("UPDATE products SET stock = stock + ? WHERE id = ? AND availability = 'in_stock'", [(int) $it['qty'], (string) $it['productId']]);
            }
        }
        update('orders', $d + ['history' => jencode($history)], 'id = ?', [$id]);
    });
    admin_back("/admin/orders/$id");
}

/* ---------- customers ---------- */

function admin_customers(): never
{
    $u = require_staff();
    $q = query('q');
    $users = rows("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.userId = u.id) AS n FROM users u WHERE u.role = 'CUSTOMER'"
        . ($q ? ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)' : '') . ' ORDER BY u.createdAt DESC', $q ? [like($q), like(strtolower($q)), like($q)] : []);
    // guest buyers (no account), grouped by email
    $guests = rows('SELECT email, name, COUNT(*) AS n, SUM(total) AS spent FROM orders WHERE userId IS NULL GROUP BY email, name ORDER BY n DESC LIMIT 100');
    admin_page('Customers', view('admin/customers', compact('users', 'guests', 'q')), $u);
}

function admin_customer(string $id): never
{
    $u = require_staff();
    $c = row('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$c) admin_not_found($u);
    $orders = rows('SELECT * FROM orders WHERE userId = ? OR email = ? ORDER BY createdAt DESC', [$c['id'], $c['email']]);
    admin_page($c['name'], view('admin/customer', ['c' => $c, 'orders' => $orders]), $u);
}

/* ---------- discounts ---------- */

function admin_discounts(): never
{
    $u = require_staff();
    admin_page('Discount codes', view('admin/discounts', ['ds' => rows('SELECT * FROM discounts ORDER BY code')]), $u);
}

function admin_discount_save(): never
{
    require_staff();
    $code = strtoupper(trim(input('code')));
    $type = input('type') === 'fixed' ? 'fixed' : 'percent';
    $value = (float) input('value');
    $min = max(0, (float) input('minTotal', '0'));
    $maxUses = input('maxUses') !== '' ? max(1, (int) input('maxUses')) : null;
    $expires = preg_match('/^\d{4}-\d{2}-\d{2}$/', input('expiresAt')) ? input('expiresAt') . ' 00:00:00' : null;
    if (strlen($code) < 2 || $value <= 0) admin_back('/admin/discounts', 'invalid');
    q('INSERT INTO discounts (id, code, type, value, minTotal, maxUses, expiresAt) VALUES (?, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE type = VALUES(type), value = VALUES(value), minTotal = VALUES(minTotal), maxUses = VALUES(maxUses), expiresAt = VALUES(expiresAt)',
      [new_id(), $code, $type, $value, $min, $maxUses, $expires]);
    redirect('/admin/discounts');
}

function admin_discount_action(string $id, string $action): never
{
    require_staff();
    if ($action === 'toggle') q('UPDATE discounts SET active = 1 - active WHERE id = ?', [$id]);
    if ($action === 'delete') q('DELETE FROM discounts WHERE id = ?', [$id]);
    redirect('/admin/discounts');
}

/* ---------- settings & staff (ADMIN only) ---------- */

const SETTINGS_TABS = [
    'brand' => ['Brand & logo', ''], 'contact' => ['Contact', ''], 'social' => ['Social media', 'Full profile URLs, e.g. https://instagram.com/yourbrand'],
    'currency' => ['Currency', 'ISO code shown on all prices, e.g. SAR, AED, KWD, YER, USD'],
    'shipping' => ['Shipping', 'Flat rate per order; free above the threshold (0 = never free). Countries: comma-separated ISO codes.'],
    'payments' => ['Payments', 'Card gateway activates once a provider is connected (see README).'], 'policies' => ['Store policies', ''], 'seo' => ['SEO', ''],
];

function admin_settings(): never
{
    $u = require_staff(['ADMIN']);
    $tab = isset(SETTINGS_TABS[query('tab') ?? '']) ? query('tab') : 'brand';
    admin_page('Settings', view('admin/settings', ['tab' => $tab, 's' => settings()]), $u);
}

function admin_settings_save(): never
{
    require_staff(['ADMIN']);
    $tab = input('tab');
    $defaults = default_settings();
    if (!isset(SETTINGS_TABS[$tab])) redirect('/admin/settings');
    $cast = fn (mixed $def, string $k) => match (true) {
        is_bool($def) => isset($_POST[$k]),
        is_int($def), is_float($def) => (float) input($k, '0'),
        default => input($k),
    };
    if (is_array($defaults[$tab])) {
        $value = [];
        foreach ($defaults[$tab] as $k => $def) $value[$k] = $cast($def, $k);
    } else {
        $value = $tab === 'currency' ? strtoupper(trim(input('currency'))) : input($tab);
    }
    save_setting($tab, $value);
    admin_back('/admin/settings?tab=' . $tab);
}

function admin_staff(): never
{
    $u = require_staff(['ADMIN']);
    admin_page('Staff & roles', view('admin/staff', ['me' => $u, 'staff' => rows("SELECT * FROM users WHERE role IN ('ADMIN', 'EDITOR') ORDER BY createdAt")]), $u);
}

function admin_staff_save(): never
{
    require_staff(['ADMIN']);
    $name = trim(input('name'));
    $email = strtolower(trim(input('email')));
    $role = input('role') === 'ADMIN' ? 'ADMIN' : 'EDITOR';
    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen(input('password')) < 8) admin_back('/admin/staff', 'invalid');
    q('INSERT INTO users (id, email, name, passwordHash, role, createdAt) VALUES (?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), passwordHash = VALUES(passwordHash)',
      [new_id(), $email, $name, hash_pw(ascii_digits(input('password'))), $role, now()]);
    redirect('/admin/staff');
}

function admin_staff_role(string $id): never
{
    $me = require_staff(['ADMIN']);
    $role = input('role');
    if ($id !== $me['id'] && in_array($role, ['ADMIN', 'EDITOR', 'CUSTOMER'], true)) q('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
    redirect('/admin/staff');
}

function admin_not_found(array $u): never
{
    http_response_code(404);
    admin_page('Not found', '<p class="text-stone">This item no longer exists.</p>', $u);
}
