<?php
// Creates the tables (if missing) and loads the demo catalog + homepage + admin user.
// Safe to re-run: wipes catalog/content (NOT orders/users) and recreates demo data.
// Usage: php database/seed.php
if (PHP_SAPI !== 'cli') exit("Run from the command line: php database/seed.php\n");

require dirname(__DIR__) . '/app/bootstrap.php';

// 1. schema (CREATE TABLE IF NOT EXISTS — harmless when already imported)
foreach (preg_split('/;\s*\n/', (string) file_get_contents(__DIR__ . '/schema.sql')) as $stmt) {
    $stmt = trim(preg_replace('/^--.*$/m', '', $stmt));
    if ($stmt !== '') db()->exec($stmt);
}

$dir = upload_dir();
if (!is_dir($dir)) mkdir($dir, 0775, true);

const TONES = [['#18181b', '#2a2a2f'], ['#e9e2d4', '#d8cdb8'], ['#3b3530', '#5a5047'], ['#f3efe7', '#e2d9c8'], ['#1f2421', '#353d38'], ['#6b5d4f', '#8a7a68']];

/** Brand-toned placeholder: gradient + gold line-art abaya silhouette (replace via Admin → Media). */
function placeholder(int $w, int $h, int $tone, string $label): array
{
    [$a, $b] = TONES[$tone % count(TONES)];
    $gold = $tone % 2 === 0 ? '#d6bd8a' : '#a8844c';
    $s = min($w, $h) / 100;
    $cx = $w / 2;
    $top = $h * 0.14;
    $tx = $cx - 50 * $s * 0.8;
    $sc = $s * 0.8;
    $fs = round($h / 40);
    $ty = $h - 60;
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
  <defs><radialGradient id="g" cx="50%" cy="35%" r="75%"><stop offset="0" stop-color="$b"/><stop offset="1" stop-color="$a"/></radialGradient></defs>
  <rect width="100%" height="100%" fill="url(#g)"/>
  <g transform="translate($tx,$top) scale($sc)" fill="none" stroke="$gold" stroke-width="0.35" stroke-linecap="round" stroke-linejoin="round" opacity=".9">
    <path d="M50 4c-4 0-7 3.5-7 8 0 3 1 5 2.5 6.5"/><path d="M50 4c4 0 7 3.5 7 8 0 3-1 5-2.5 6.5"/>
    <path d="M45 18c-3 2-6 6-7 12l-9 60c8 3 16 4 21 4s13-1 21-4l-9-60c-1-6-4-10-7-12"/>
    <path d="M43 34c-4 16-8 34-13 54"/><path d="M57 34c3 18 6 36 12 52"/><path d="M50 22v72"/>
  </g>
  <text x="50%" y="$ty" text-anchor="middle" font-family="Georgia,serif" font-size="$fs" letter-spacing="6" fill="$gold" opacity=".75">$label</text>
</svg>
SVG;
    $name = uuid4() . '.svg';
    file_put_contents(upload_dir() . "/$name", $svg);
    $m = [
        'id' => new_id(), 'url' => "/media/$name", 'kind' => 'image', 'mime' => 'image/svg+xml', 'width' => $w, 'height' => $h,
        'size' => strlen($svg), 'altAr' => 'صورة توضيحية', 'altEn' => 'Placeholder image', 'posterId' => null, 'createdAt' => now(),
    ];
    insert('media', $m);
    return $m;
}
function ref(array $m): array { return ['id' => $m['id'], 'url' => $m['url'], 'kind' => $m['kind'], 'altAr' => $m['altAr'], 'altEn' => $m['altEn']]; }

// 2. admin account
$email = strtolower((string) config('admin_email', 'admin@alkawkaba.com'));
q("INSERT IGNORE INTO users (id, email, name, passwordHash, role, createdAt) VALUES (?, ?, 'Store Owner', ?, 'ADMIN', ?)",
    [new_id(), $email, hash_pw((string) config('admin_password', 'ChangeMe123!')), now()]);

// 3. wipe content (and the files of wiped media)
foreach (rows('SELECT url FROM media') as $m) @unlink($dir . '/' . basename($m['url']));
foreach (['sections', 'product_media', 'products', 'collections', 'categories', 'media'] as $t) q("DELETE FROM `$t`");

// 4. catalog
$cats = [];
foreach ([['occasion', 'عبايات المناسبات', 'Occasion'], ['everyday', 'عبايات يومية', 'Everyday'], ['wedding', 'الأعراس', 'Weddings'], ['evening', 'السهرات', 'Evening']] as $i => [$slug, $ar, $en]) {
    insert('categories', ['id' => $cats[$slug] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'sort' => $i]);
}

$cols = [];
foreach ([
    ['noor', 'مجموعة نور', 'The Noor Collection', 'قصّات منسابة بلمسات ذهبية خافتة، مستوحاة من ضوء الفجر.', 'Fluid silhouettes touched with soft gold, inspired by first light.'],
    ['layali', 'ليالي', 'Layali', 'عبايات السهرة بتطريز يدوي وأقمشة فاخرة.', 'Evening abayas with hand embroidery and rich fabrics.'],
    ['essentials', 'الأساسيات', 'Essentials', 'قطع يومية بخامات ناعمة وتفصيل متقن.', 'Everyday pieces in soft fabrics, impeccably tailored.'],
    ['atelier', 'إصدارات الأتيليه', 'Atelier Editions', 'قطع محدودة العدد تُصنع يدوياً في مشغلنا.', 'Limited pieces, handmade in our atelier.'],
] as $i => [$slug, $ar, $en, $dAr, $dEn]) {
    $cover = placeholder(2000, 1300, $i * 2, strtoupper($en));
    insert('collections', ['id' => $cols[] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'descAr' => $dAr, 'descEn' => $dEn, 'coverId' => $cover['id'], 'sort' => $i, 'published' => 1, 'createdAt' => now()]);
}

$names = [
    ['عباية ضياء', 'Diya Abaya'], ['عباية سَحَر', 'Sahar Abaya'], ['عباية لؤلؤ', 'Lulu Abaya'], ['عباية مِسك', 'Misk Abaya'],
    ['عباية ريم', 'Reem Abaya'], ['عباية غيم', 'Ghaym Abaya'], ['عباية جُمان', 'Juman Abaya'], ['عباية ياسمين', 'Yasmin Abaya'],
    ['عباية الكوكبة الذهبية', 'Golden Kawkaba Abaya'], ['عباية شروق', 'Shurooq Abaya'], ['عباية نسيم', 'Naseem Abaya'], ['عباية ليان', 'Layan Abaya'],
];
$prices = [890, 1250, 650, 1480, 720, 980, 1890, 560, 2400, 840, 690, 1150];
$catOrder = ['occasion', 'everyday', 'evening', 'wedding'];
foreach ($names as $i => [$ar, $en]) {
    $imgs = [placeholder(1200, 1600, $i, strtoupper($en)), placeholder(1200, 1600, $i + 1, 'DETAIL'), placeholder(1200, 1600, $i + 3, 'BACK')];
    $pid = new_id();
    insert('products', [
        'id' => $pid, 'slug' => strtolower(preg_replace('/\s+/', '-', $en)), 'sku' => sprintf('KW-%03d', $i + 1), 'nameAr' => $ar, 'nameEn' => $en,
        'descAr' => 'عباية مصممة بعناية من قماش فاخر ينساب بخفة، بقصة واسعة أنيقة وتفاصيل يدوية دقيقة على الأكمام.',
        'descEn' => 'A carefully composed abaya in a fluid luxury fabric, with a relaxed elegant cut and fine hand-finished details at the sleeves.',
        'fabricAr' => 'كريب ياباني فاخر · بطانة حرير', 'fabricEn' => 'Premium Japanese crepe · silk lining',
        'detailsAr' => "قصة واسعة\nتطريز يدوي على الأكمام\nتأتي مع طرحة مطابقة", 'detailsEn' => "Relaxed fit\nHand embroidery at the cuffs\nMatching shayla included",
        'careAr' => 'تنظيف جاف فقط. يُكوى على حرارة منخفضة.', 'careEn' => 'Dry clean only. Iron on low heat.',
        'deliveryAr' => $i % 3 === 0 ? 'تُشحن خلال 24 ساعة' : '', 'deliveryEn' => $i % 3 === 0 ? 'Ships within 24 hours' : '',
        'price' => $prices[$i], 'salePrice' => $i === 3 ? 1190 : ($i === 7 ? 450 : null),
        'sizes' => jencode(['52', '54', '56', '58', '60']),
        'colors' => jencode($i % 2 ? [['nameAr' => 'أسود', 'nameEn' => 'Black', 'hex' => '#0e0e10'], ['nameAr' => 'رملي', 'nameEn' => 'Sand', 'hex' => '#d8cdb8']] : [['nameAr' => 'أسود', 'nameEn' => 'Black', 'hex' => '#0e0e10']]),
        'stock' => $i === 5 ? 2 : 12, 'availability' => $i === 6 || $i === 8 ? 'made_to_order' : 'in_stock',
        'isNew' => $i < 6, 'isFeatured' => $i % 3 === 1, 'isLimited' => $i === 6 || $i === 8, 'isReady' => $i % 3 === 0, 'published' => true,
        'seoTitle' => '', 'seoDesc' => '', 'categoryId' => $cats[$catOrder[$i % 4]], 'collectionId' => $cols[$i % 4],
        // spaced one second apart so "newest first" keeps the original order
        'createdAt' => date('Y-m-d H:i:s', time() - (count($names) - $i)), 'updatedAt' => now(),
    ]);
    foreach ($imgs as $sort => $m) insert('product_media', ['productId' => $pid, 'mediaId' => $m['id'], 'sort' => $sort]);
}

// 5. homepage
$hero1 = placeholder(2400, 1350, 0, 'CAMPAIGN · SS27');
$hero1m = placeholder(1080, 1920, 0, 'CAMPAIGN');
$hero2 = placeholder(2400, 1350, 2, 'LAYALI');
$occ = [];
foreach (['EVENING', 'DAILY', 'WEDDINGS', 'EXCLUSIVE'] as $i => $label) $occ[] = placeholder(1000, 1500, $i * 2 + 1, $label);
$atelier = placeholder(1400, 1750, 4, 'THE ATELIER');
$det = [];
foreach (['FABRIC', 'EMBROIDERY', 'TAILORING', 'FINISHING'] as $i => $label) $det[] = placeholder(900, 900, $i + 1, $label);
$camp = placeholder(2400, 1400, 2, 'EDITORIAL');

$sections = [
    ['hero', ['slides' => [
        ['media' => ref($hero1), 'mobileMedia' => ref($hero1m), 'eyebrowAr' => 'المجموعة الجديدة', 'eyebrowEn' => 'New Collection', 'headingAr' => 'أناقة تُروى بهدوء', 'headingEn' => 'Elegance, Quietly Told', 'textAr' => 'قطع خالدة صُممت لتليق بكِ في كل لحظة.', 'textEn' => 'Timeless pieces composed for every moment of yours.', 'ctaAr' => 'اكتشفي المجموعة', 'ctaEn' => 'Discover the Collection', 'link' => '/ar/collections/noor'],
        ['media' => ref($hero2), 'eyebrowAr' => 'ليالي', 'eyebrowEn' => 'Layali', 'headingAr' => 'لسهراتكِ الاستثنائية', 'headingEn' => 'For Extraordinary Evenings', 'ctaAr' => 'تسوقي الآن', 'ctaEn' => 'Shop Now', 'link' => '/ar/collections/layali'],
    ]]],
    ['products', ['titleAr' => 'وصل حديثاً', 'titleEn' => 'New Arrivals', 'source' => 'new', 'limit' => 8, 'ctaAr' => 'عرض الكل', 'ctaEn' => 'View All', 'link' => '/ar/shop?tag=new']],
    ['collections', ['titleAr' => 'المجموعات', 'titleEn' => 'The Collections', 'collectionIds' => []]],
    ['occasions', ['titleAr' => 'تسوقي حسب المناسبة', 'titleEn' => 'Shop by Occasion', 'items' => [
        ['media' => ref($occ[0]), 'labelAr' => 'السهرات والمناسبات', 'labelEn' => 'Evening & Events', 'link' => '/ar/shop?category=evening'],
        ['media' => ref($occ[1]), 'labelAr' => 'إطلالات يومية', 'labelEn' => 'Daily Wear', 'link' => '/ar/shop?category=everyday'],
        ['media' => ref($occ[2]), 'labelAr' => 'الأعراس', 'labelEn' => 'Weddings', 'link' => '/ar/shop?category=wedding'],
        ['media' => ref($occ[3]), 'labelAr' => 'قطع حصرية', 'labelEn' => 'Exclusive Pieces', 'link' => '/ar/shop?tag=limited'],
    ]]],
    ['ready', ['titleAr' => 'جاهزة للارتداء', 'titleEn' => 'Ready to Wear', 'textAr' => 'قطع متوفرة تُشحن إليكِ فوراً.', 'textEn' => 'Available now, shipped to you right away.', 'limit' => 4, 'ctaAr' => 'تسوقي الجاهز', 'ctaEn' => 'Shop Ready to Wear', 'link' => '/ar/shop?tag=ready']],
    ['story', ['eyebrowAr' => 'الأتيليه', 'eyebrowEn' => 'The Atelier', 'titleAr' => 'حيث تولد كل قطعة', 'titleEn' => 'Where Every Piece Begins',
        'textAr' => 'في مشغلنا، تمر كل عباية بأيدٍ خبيرة — من اختيار القماش إلى آخر غرزة تطريز. نؤمن أن الفخامة الحقيقية تكمن في التفاصيل التي لا تُرى.',
        'textEn' => "In our atelier, every abaya passes through expert hands — from selecting the fabric to the final embroidered stitch. We believe true luxury lives in the details you don't see.",
        'media' => ref($atelier),
        'gallery' => array_map(fn ($m, $i) => ['media' => ref($m), 'captionAr' => ['الأقمشة', 'التطريز', 'التفصيل', 'اللمسات الأخيرة'][$i], 'captionEn' => ['Fabric', 'Embroidery', 'Tailoring', 'Finishing'][$i]], $det, array_keys($det)),
        'ctaAr' => 'قصتنا', 'ctaEn' => 'Our Story', 'link' => '/ar/story']],
    ['campaign', ['eyebrowAr' => 'حملة الموسم', 'eyebrowEn' => 'Seasonal Campaign', 'titleAr' => 'ضوء الكوكبة', 'titleEn' => 'Light of Al Kawkaba', 'layout' => 'full', 'items' => [['media' => ref($camp)]], 'ctaAr' => 'اكتشفي', 'ctaEn' => 'Discover', 'link' => '/ar/collections']],
    ['values', ['titleAr' => 'وعدنا لكِ', 'titleEn' => 'Our Promise', 'items' => [
        ['titleAr' => 'حِرفة متقنة', 'titleEn' => 'Premium Craftsmanship', 'textAr' => 'كل قطعة تُصنع بأيدٍ خبيرة.', 'textEn' => 'Every piece made by expert hands.'],
        ['titleAr' => 'تصاميم حصرية', 'titleEn' => 'Exclusive Designs', 'textAr' => 'تصاميم أصلية لا تتكرر.', 'textEn' => 'Original designs, never repeated.'],
        ['titleAr' => 'أقمشة فاخرة', 'titleEn' => 'Quality Fabrics', 'textAr' => 'خامات مختارة من أرقى المصادر.', 'textEn' => 'Fabrics sourced from the finest mills.'],
        ['titleAr' => 'تفصيل أنيق', 'titleEn' => 'Elegant Tailoring', 'textAr' => 'قصّات مدروسة تمنحكِ الراحة والأناقة.', 'textEn' => 'Considered cuts for comfort and grace.'],
        ['titleAr' => 'تسوق آمن', 'titleEn' => 'Secure Shopping', 'textAr' => 'دفع آمن وخصوصية تامة.', 'textEn' => 'Secure payment, complete privacy.'],
        ['titleAr' => 'خدمة وتوصيل', 'titleEn' => 'Delivery & Care', 'textAr' => 'توصيل سريع وخدمة عملاء بانتظاركِ.', 'textEn' => 'Fast delivery and attentive client care.'],
    ]]],
];
foreach ($sections as $i => [$type, $data]) insert('sections', ['id' => new_id(), 'type' => $type, 'sort' => $i, 'published' => 1, 'data' => jencode($data)]);

q("INSERT IGNORE INTO discounts (id, code, type, value) VALUES (?, 'WELCOME10', 'percent', 10)", [new_id()]);
echo 'Seeded ' . count($names) . ' products, ' . count($cols) . ' collections, ' . count($sections) . " sections. Admin: $email\n";
