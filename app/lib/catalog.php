<?php
// Products are loaded as flat rows, then decorated with their ordered gallery.

/** Normalise DB types (DECIMAL → float, TINYINT → bool, JSON text → arrays). */
function shape_product(array $p): array
{
    $p['price'] = (float) $p['price'];
    $p['salePrice'] = $p['salePrice'] === null ? null : (float) $p['salePrice'];
    $p['stock'] = (int) $p['stock'];
    foreach (['isNew', 'isFeatured', 'isLimited', 'isReady', 'published'] as $k) $p[$k] = (bool) $p[$k];
    $p['sizes'] = array_values(array_filter((array) jdecode($p['sizes'], []), 'is_string'));
    $p['colors'] = array_values(array_filter((array) jdecode($p['colors'], []), 'is_array'));
    $p['gallery'] ??= [];
    return $p;
}

/** Attach ordered media (gallery) to each product. */
function with_gallery(array $ps): array
{
    if (!$ps) return [];
    $ids = array_column($ps, 'id');
    $by = [];
    foreach (rows('SELECT pm.productId, m.* FROM product_media pm JOIN media m ON m.id = pm.mediaId WHERE pm.productId IN (' . in_list($ids) . ') ORDER BY pm.sort', $ids) as $m) {
        $by[$m['productId']][] = $m;
    }
    return array_map(function ($p) use ($by) {
        $p['gallery'] = $by[$p['id']] ?? [];
        return shape_product($p);
    }, $ps);
}

/**
 * Published products. $where is extra SQL (prefixed "AND"), $order an ORDER BY clause.
 * find_products('AND p.isNew = 1', [], 'p.createdAt DESC', 8)
 */
function find_products(string $where = '', array $params = [], string $order = 'p.createdAt DESC', ?int $limit = null): array
{
    $sql = "SELECT p.* FROM products p WHERE p.published = 1 $where ORDER BY $order" . ($limit ? ' LIMIT ' . (int) $limit : '');
    return with_gallery(rows($sql, $params));
}

/** Products in the given id order. */
function products_by_ids(array $ids): array
{
    $ids = array_values(array_filter($ids, 'is_string'));
    if (!$ids) return [];
    $ps = array_column(find_products('AND p.id IN (' . in_list($ids) . ')', $ids), null, 'id');
    return array_values(array_filter(array_map(fn ($id) => $ps[$id] ?? null, $ids)));
}

/** media id → url, for video posters and covers. */
function media_map(array $ids): array
{
    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) return [];
    $out = [];
    foreach (rows('SELECT * FROM media WHERE id IN (' . in_list($ids) . ')', $ids) as $m) $out[$m['id']] = $m;
    return $out;
}

/** The data the browser needs for quick view, bag lines and wishlist. */
function to_card(array $p): array
{
    return [
        'id' => $p['id'], 'slug' => $p['slug'], 'nameAr' => $p['nameAr'], 'nameEn' => $p['nameEn'],
        'price' => $p['price'], 'salePrice' => $p['salePrice'], 'availability' => $p['availability'],
        'sizes' => $p['sizes'], 'colors' => $p['colors'],
        'image' => first_image($p),
    ];
}

function first_image(array $p): ?string
{
    foreach ($p['gallery'] as $m) if ($m['kind'] === 'image') return $m['url'];
    return null;
}

function category_list(): array
{
    return rows('SELECT * FROM categories ORDER BY sort');
}
