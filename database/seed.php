<?php
// Creates the tables (if missing) and loads the store with the brand photos & videos from public/brand:
// products, collections, homepage sections and the admin user.
// Safe to re-run: wipes catalog/content (NOT orders/users/settings) and recreates it.
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

/** Import a file from public/brand into the media library (JPEGs become WebP, like admin uploads). */
function brand(string $file, string $altAr, string $altEn, ?array $poster = null): array
{
    $path = ROOT . '/public/brand/' . $file;
    if (!is_file($path)) throw new RuntimeException("Missing brand file: public/brand/$file");
    return store_media_file($path, false, ['altAr' => $altAr, 'altEn' => $altEn, 'posterId' => $poster['id'] ?? null]);
}

/** Import one rectangle of a brand photo (used to split the detail collage into separate close-ups). */
function brand_crop(string $file, array $rect, string $altAr, string $altEn): array
{
    $src = imagecreatefromjpeg(ROOT . '/public/brand/' . $file);
    $crop = imagecrop($src, ['x' => $rect[0], 'y' => $rect[1], 'width' => $rect[2], 'height' => $rect[3]]);
    $tmp = tempnam(sys_get_temp_dir(), 'kw') . '.jpg';
    imagejpeg($crop, $tmp, 95);
    try {
        return store_media_file($tmp, false, ['altAr' => $altAr, 'altEn' => $altEn]);
    } finally {
        @unlink($tmp);
    }
}

/** Split a 2×2 detail collage into its four close-ups; $inset (fraction) trims the frame/gutters. */
function brand_quads(string $file, float $inset, array $alts): array
{
    [$w, $h] = getimagesize(ROOT . '/public/brand/' . $file);
    $qw = intdiv($w, 2); $qh = intdiv($h, 2);
    $ix = (int) round($w * $inset); $iy = (int) round($h * $inset);
    $out = [];
    foreach ([[0, 0], [1, 0], [0, 1], [1, 1]] as $k => [$cx, $cy]) {
        [$ar, $en] = $alts[$k];
        $out[] = brand_crop($file, [$cx * $qw + $ix, $cy * $qh + $iy, $qw - 2 * $ix, $qh - 2 * $iy], $ar, $en);
    }
    return $out;
}

/** Section media field: what the homepage editor stores ({id, url, kind, poster?, alt}). */
function ref(array $m, ?array $poster = null): array
{
    return ['id' => $m['id'], 'url' => $m['url'], 'kind' => $m['kind'], 'altAr' => $m['altAr'], 'altEn' => $m['altEn']] + ($poster ? ['poster' => $poster['url']] : []);
}

// 2. admin account
$email = strtolower((string) config('admin_email', 'admin@alkawkaba.com'));
q("INSERT IGNORE INTO users (id, email, name, passwordHash, role, createdAt) VALUES (?, ?, 'Store Owner', ?, 'ADMIN', ?)",
    [new_id(), $email, hash_pw((string) config('admin_password', 'ChangeMe123!')), now()]);

// 3. wipe content (and the files of wiped media)
foreach (rows('SELECT url FROM media') as $m) @unlink($dir . '/' . basename($m['url']));
foreach (['sections', 'product_media', 'products', 'collections', 'categories', 'media'] as $t) q("DELETE FROM `$t`");

// 4. brand media
echo "Importing brand photos & videos…\n";
$classic = brand('Gemini_Generated_Image_1qj5s01qj5s01qj5.jpeg', 'عباية سوداء بياقة وأكمام مطرزة', 'Black abaya with an embroidered lapel and cuffs');
$collage = 'Gemini_Generated_Image_gqjq5igqjq5igqjq.jpeg'; // 2×2 close-ups of the classic abaya
$lapel = brand_crop($collage, [32, 32, 380, 540], 'تطريز الياقة بالخرز والترتر', 'Lapel embroidered with beads and sequins');
$clasp = brand_crop($collage, [475, 32, 380, 540], 'مشبك ذهبي', 'Gold clasp');
$cuff = brand_crop($collage, [32, 625, 380, 540], 'كم مطرز بالخرز', 'Beaded embroidered cuff');
$seq1 = brand('Gemini_Generated_Image_fp6yfofp6yfofp6y.jpeg', 'عباية سهرة مطرزة بالترتر', 'Evening abaya with sequin embroidery');
$seq2 = brand('Gemini_Generated_Image_j8aynxj8aynxj8ay (1).jpeg', 'عباية سهرة مطرزة بالترتر', 'Evening abaya with sequin embroidery');
$lace1 = brand('Gemini_Generated_Image_lvb0axlvb0axlvb0.jpeg', 'عباية سوداء بأطراف دانتيل', 'Black abaya with lace hem and sleeves');
$lace2 = brand('Gemini_Generated_Image_ia4u6xia4u6xia4u.jpeg', 'عباية سوداء بأطراف دانتيل', 'Black abaya with lace hem and sleeves');
$lace3 = brand('Gemini_Generated_Image_jczr69jczr69jczr.jpeg', 'عباية سوداء بأطراف دانتيل', 'Black abaya with lace hem and sleeves');
$check1 = brand('WhatsApp Image 2026-11-07 at 5.44.07 PM.jpeg', 'عباية سوداء بحواف بنقشة الشماغ', 'Black abaya with keffiyeh-pattern trim');
$check2 = brand('WhatsApp Image 2026-11-07 at 2.43.24 PM.jpeg', 'ياقة وأكمام بنقشة الشماغ', 'Keffiyeh-pattern lapel and cuffs');
$check3 = brand('WhatsApp Image 2026-11-07 at 2.42.57 PM.jpeg', 'قماش العباية بحافة الشماغ', 'Abaya fabric with keffiyeh-pattern edge');
$satin = brand('WhatsApp Image 2026-11-07 at 2.44.00 PM (1).jpeg', 'عباية ساتان بأكمام مطرزة', 'Satin abaya with embroidered sleeves');
$najma = brand('Gemini_Generated_Image_h6ldfch6ldfch6ld.jpeg', 'عباية سوداء بألواح مطرزة بالترتر', 'Black abaya with sequin-embroidered panels');
$najmaQ = brand_quads('videos/Gemini_Generated_Image_lrkfublrkfublrkf.jpeg', 0.035, [
    ['ألواح أمامية مطرزة بالترتر', 'Sequin-embroidered front panels'], ['كم مطرز بالترتر', 'Sequin-embroidered sleeve'],
    ['تطريز يلمع مع الحركة', 'Embroidery that catches the light'], ['حافة سفلية مطرزة', 'Embroidered hem'],
]);
$luluQ = brand_quads('videos/WhatsApp Image 2026-11-06 at 5.10.35 PM.jpeg', 0.012, [
    ['وردة مطرزة بالخرز والترتر', 'Beaded, sequinned flower'], ['أكمام شيفون مطرزة', 'Embroidered chiffon sleeve'],
    ['ورود على الصدر', 'Flowers across the front'], ['تطريز على الكتف', 'Embroidery at the shoulder'],
]);
$laceP = brand('videos/Opulent black lace abaya portrait.png', 'عباية سوداء بأكمام دانتيل مطرزة', 'Black abaya with embroidered lace sleeves');
$laceQ = brand_quads('Gemini_Generated_Image_u8cfceu8cfceu8cf.jpeg', 0.01, [
    ['دانتيل على الأكمام', 'Lace at the sleeves'], ['شريط دانتيل أمامي', 'Lace front panel'],
    ['حافة الكم المخرّمة', 'Scalloped lace cuff'], ['حافة سفلية من الدانتيل', 'Lace hem'],
]);
$house = brand('videos/WhatsApp Image 2026-11-08 at 7.48.19 PM.jpeg', 'الكوكبة للعبايات', 'Al Kawkaba Abayas');
$poster1 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.54 PM.poster.jpg', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim');
$poster2 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.55 PM.poster.jpg', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim');
$vid1 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.54 PM.mp4', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim', $poster1);
$vid2 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.55 PM.mp4', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim', $poster2);
// 9:16 clips for the "See it in motion" showcase (fast-start copies of the WhatsApp originals)
$motion = [];
foreach ([
    ['kawkaba-satin-sparkle', 'عباية ساتان بأكمام لامعة', 'Satin abaya with sparkling sleeves', 'الساتان اللامع', 'Lustrous Satin'],
    ['kawkaba-keffiyeh-sleeves', 'عباية بأكمام نقشة الشماغ', 'Abaya with keffiyeh-pattern sleeves', 'أكمام الشماغ', 'Keffiyeh Sleeves'],
    ['kawkaba-chevron-satin', 'عباية ساتان بشريط متعرج', 'Satin abaya with chevron trim', 'الشريط المتعرج', 'Chevron Trim'],
] as [$file, $altAr, $altEn, $capAr, $capEn]) {
    $p = brand("videos/$file.poster.jpg", $altAr, $altEn);
    $motion[] = ['media' => ref(brand("videos/$file.mp4", $altAr, $altEn, $p), $p), 'captionAr' => $capAr, 'captionEn' => $capEn];
}

// 5. catalog
$cats = [];
foreach ([['occasion', 'عبايات المناسبات', 'Occasion'], ['everyday', 'عبايات يومية', 'Everyday'], ['wedding', 'الأعراس', 'Weddings'], ['evening', 'السهرات', 'Evening']] as $i => [$slug, $ar, $en]) {
    insert('categories', ['id' => $cats[$slug] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'sort' => $i]);
}

$cols = [];
foreach ([
    // one collection per fabric
    ['satin', 'عبايات الساتان', 'Satin Abayas', 'لمعةٌ ناعمة وانسيابٌ فاخر، لإطلالاتٍ تلفت الأنظار بهدوء.', 'A soft sheen and a luxurious drape, for looks that turn heads quietly.', $najma],
    ['crepe', 'عبايات الكريب', 'Crepe Abayas', 'خامةٌ مطفية راقية تُبرز التطريز اليدوي وتفاصيل الخرز.', 'A refined matte fabric that lets hand embroidery and beadwork shine.', $classic],
    ['lace', 'عبايات الدانتيل', 'Lace Abayas', 'دانتيلٌ رقيق بلمسةٍ أنثوية، لمناسباتكِ الخاصة.', 'Delicate lace with a feminine touch, for your special occasions.', $laceP],
] as $i => [$slug, $ar, $en, $dAr, $dEn, $cover]) {
    insert('collections', ['id' => $cols[$slug] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'descAr' => $dAr, 'descEn' => $dEn, 'coverId' => $cover['id'], 'sort' => $i, 'published' => 1, 'createdAt' => now()]);
}

// Names, prices and texts are starting points — edit them in Admin → Products.
$care = ['careAr' => 'تنظيف جاف فقط. يُكوى على حرارة منخفضة.', 'careEn' => 'Dry clean only. Iron on low heat.'];
$products = [
    [
        'slug' => 'satin-sequin-panel-abaya', 'nameAr' => 'عباية الكوكبة الساتان · ألواح الترتر', 'nameEn' => 'Al Kawkaba Satin Abaya · Sequin Panels', 'fabricAr' => 'ساتان', 'fabricEn' => 'Satin', 'price' => 1650, 'salePrice' => null,
        'descAr' => 'عباية سهرة سوداء تمتد على واجهتها وأكمامها وحافتها ألواحٌ مطرزة يدوياً بالترتر، تلمع مع كل خطوة كنجمة في ليل.',
        'descEn' => 'A black evening abaya with hand-embroidered sequin panels down the front, sleeves and hem — it catches the light like a star at night.',
        'detailsAr' => "ألواح أمامية مطرزة بالترتر\nأكمام واسعة مطرزة\nحافة سفلية مطرزة", 'detailsEn' => "Sequin-embroidered front panels\nWide embroidered sleeves\nEmbroidered hem",
        'gallery' => [$najma, ...$najmaQ], 'collection' => 'satin', 'category' => 'evening',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => true, 'isReady' => false, 'stock' => 6,
    ],
    [
        'slug' => 'crepe-embroidered-lapel-abaya', 'nameAr' => 'عباية الكوكبة الكريب · الياقة المطرزة', 'nameEn' => 'Al Kawkaba Crepe Abaya · Embroidered Lapel', 'fabricAr' => 'كريب', 'fabricEn' => 'Crepe', 'price' => 890, 'salePrice' => null,
        'descAr' => 'عباية سوداء كلاسيكية بياقة وأكمام مطرزة يدوياً بالخرز والترتر، تُغلق بمشبك ذهبي.',
        'descEn' => 'A classic black abaya with a lapel and cuffs hand-embroidered in beads and sequins, closed with a gold clasp.',
        'detailsAr' => "ياقة مطرزة بالخرز والترتر\nأكمام مطرزة\nمشبك ذهبي", 'detailsEn' => "Beaded, sequinned lapel\nEmbroidered cuffs\nGold clasp fastening",
        'gallery' => [$classic, $lapel, $cuff, $clasp], 'collection' => 'crepe', 'category' => 'occasion',
        'isNew' => true, 'isFeatured' => false, 'isLimited' => false, 'isReady' => true,
        'deliveryAr' => 'تُشحن خلال 24 ساعة', 'deliveryEn' => 'Ships within 24 hours',
    ],
    [
        'slug' => 'crepe-beaded-flower-abaya', 'nameAr' => 'عباية الكوكبة الكريب · ورود الخرز', 'nameEn' => 'Al Kawkaba Crepe Abaya · Beaded Flowers', 'fabricAr' => 'كريب', 'fabricEn' => 'Crepe', 'price' => 1480, 'salePrice' => 1190,
        'descAr' => 'عباية سهرة سوداء تتلألأ بتطريز الترتر على الصدر والأكمام.',
        'descEn' => 'A black evening abaya that glimmers with sequin embroidery across the front and sleeves.',
        'detailsAr' => "تطريز ترتر على الصدر\nأكمام مطرزة\nقصة واسعة", 'detailsEn' => "Sequin embroidery across the front\nEmbroidered sleeves\nRelaxed fit",
        'gallery' => [$seq1, $seq2, ...$luluQ], 'collection' => 'crepe', 'category' => 'evening',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => false, 'isReady' => false,
    ],
    [
        'slug' => 'lace-abaya', 'nameAr' => 'عباية الكوكبة الدانتيل', 'nameEn' => 'Al Kawkaba Lace Abaya', 'fabricAr' => 'دانتيل', 'fabricEn' => 'Lace', 'price' => 1250, 'salePrice' => null,
        'descAr' => 'عباية سوداء انسيابية تزيّن أطرافها وأكمامها دانتيل ناعم.',
        'descEn' => 'A flowing black abaya finished with delicate lace at the hem and sleeves.',
        'detailsAr' => "دانتيل على الأطراف\nأكمام دانتيل\nقصة انسيابية", 'detailsEn' => "Lace hem\nLace sleeves\nFlowing cut",
        'gallery' => [$lace1, $laceP, $lace2, $lace3, ...$laceQ], 'collection' => 'lace', 'category' => 'wedding',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => false, 'isReady' => true,
        'deliveryAr' => 'تُشحن خلال 24 ساعة', 'deliveryEn' => 'Ships within 24 hours',
    ],
    [
        'slug' => 'satin-keffiyeh-abaya', 'nameAr' => 'عباية الكوكبة الساتان · نقشة الشماغ', 'nameEn' => 'Al Kawkaba Satin Abaya · Keffiyeh Trim', 'fabricAr' => 'ساتان', 'fabricEn' => 'Satin', 'price' => 1890, 'salePrice' => null,
        'descAr' => 'عباية سوداء بياقة وأكمام بنقشة الشماغ، تصميم يجمع الأصالة والأناقة العصرية.',
        'descEn' => 'A black abaya with keffiyeh-pattern lapels and cuffs — heritage detail with a modern line.',
        'detailsAr' => "ياقة بنقشة الشماغ\nأكمام بنقشة الشماغ\nإصدار محدود", 'detailsEn' => "Keffiyeh-pattern lapels\nKeffiyeh-pattern cuffs\nLimited edition",
        'gallery' => [$check1, $check2, $check3, $vid1, $vid2], 'collection' => 'satin', 'category' => 'occasion',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => true, 'isReady' => false, 'stock' => 5,
    ],
    [
        'slug' => 'satin-embroidered-sleeve-abaya', 'nameAr' => 'عباية الكوكبة الساتان · الأكمام المطرزة', 'nameEn' => 'Al Kawkaba Satin Abaya · Embroidered Sleeves', 'price' => 720, 'salePrice' => null,
        'descAr' => 'عباية من الساتان الأسود بقصة واسعة وأكمام مطرزة.',
        'descEn' => 'A black satin abaya with a relaxed cut and embroidered sleeves.',
        'fabricAr' => 'ساتان', 'fabricEn' => 'Satin',
        'detailsAr' => "قماش ساتان لامع\nأكمام مطرزة\nقصة واسعة", 'detailsEn' => "Lustrous satin\nEmbroidered sleeves\nRelaxed fit",
        'gallery' => [$satin], 'collection' => 'satin', 'category' => 'everyday',
        'isNew' => true, 'isFeatured' => false, 'isLimited' => false, 'isReady' => true,
    ],
];
foreach ($products as $i => $p) {
    $pid = new_id();
    insert('products', [
        'id' => $pid, 'slug' => $p['slug'], 'sku' => sprintf('KW-%03d', $i + 1), 'nameAr' => $p['nameAr'], 'nameEn' => $p['nameEn'],
        'descAr' => $p['descAr'], 'descEn' => $p['descEn'], 'fabricAr' => $p['fabricAr'] ?? '', 'fabricEn' => $p['fabricEn'] ?? '',
        'detailsAr' => $p['detailsAr'], 'detailsEn' => $p['detailsEn'], 'careAr' => $care['careAr'], 'careEn' => $care['careEn'],
        'deliveryAr' => $p['deliveryAr'] ?? '', 'deliveryEn' => $p['deliveryEn'] ?? '',
        'price' => $p['price'], 'salePrice' => $p['salePrice'],
        'sizes' => jencode(['52', '54', '56', '58', '60']),
        'colors' => jencode([['nameAr' => 'أسود', 'nameEn' => 'Black', 'hex' => '#0e0e10']]),
        'stock' => $p['stock'] ?? 12, 'availability' => 'in_stock',
        'isNew' => $p['isNew'], 'isFeatured' => $p['isFeatured'], 'isLimited' => $p['isLimited'], 'isReady' => $p['isReady'], 'published' => true,
        'seoTitle' => '', 'seoDesc' => '', 'categoryId' => $cats[$p['category']], 'collectionId' => $cols[$p['collection']],
        // spaced one second apart so "newest first" lists them in this order
        'createdAt' => date('Y-m-d H:i:s', time() - $i), 'updatedAt' => now(),
    ]);
    foreach ($p['gallery'] as $sort => $m) insert('product_media', ['productId' => $pid, 'mediaId' => $m['id'], 'sort' => $sort]);
}

// 6. homepage
$sections = [
    // homepage slideshow = photos only. Desktop: three portrait photos side by side, sliding one step at a time; mobile: one full-screen photo per slide
    ['hero', ['slides' => [
        ['media' => ref($seq1), 'eyebrowAr' => 'المجموعة الجديدة', 'eyebrowEn' => 'New Collection', 'headingAr' => 'أناقة تُروى بهدوء', 'headingEn' => 'Elegance, Quietly Told', 'textAr' => 'قطع خالدة صُممت لتليق بكِ في كل لحظة.', 'textEn' => 'Timeless pieces composed for every moment of yours.', 'ctaAr' => 'اكتشفي المجموعة', 'ctaEn' => 'Discover the Collection', 'link' => '/ar/collections/crepe'],
        ['media' => ref($najma)],
        ['media' => ref($laceP)],
        ['media' => ref($check1), 'eyebrowAr' => 'إصدار خاص', 'eyebrowEn' => 'Special Edition', 'headingAr' => 'أصالةٌ بلمسة عصرية', 'headingEn' => 'Heritage, Modern Line', 'ctaAr' => 'تسوقي الآن', 'ctaEn' => 'Shop Now', 'link' => '/ar/collections/satin'],
        ['media' => ref($classic)],
        ['media' => ref($lace2)],
        ['media' => ref($satin)],
    ]]],
    ['text', ['eyebrowAr' => 'الكوكبة للعبايات', 'eyebrowEn' => 'Al Kawkaba Abayas', 'titleAr' => 'فخامةٌ هادئة، تُصاغ بعناية', 'titleEn' => 'Quiet Luxury, Composed with Care',
        'textAr' => 'في الكوكبة، نؤمن أن العباية ليست مجرد قطعة، بل حضورٌ يعكس ذوقكِ. نختار أقمشتنا بعناية، ونطرّز تفاصيلنا يدوياً، لتصلكِ كل عباية كما تستحقين.',
        'textEn' => 'At Al Kawkaba, an abaya is more than a garment — it is a presence that reflects your taste. We choose our fabrics with care and finish every detail by hand, so each piece arrives as you deserve.',
        'ctaAr' => 'قصتنا', 'ctaEn' => 'Our Story', 'link' => '/ar/story']],
    ['products', ['titleAr' => 'وصل حديثاً', 'titleEn' => 'New Arrivals', 'source' => 'new', 'limit' => 8, 'ctaAr' => 'عرض الكل', 'ctaEn' => 'View All', 'link' => '/ar/shop?tag=new']],
    // editorial mosaic: one tall look + close-ups of the handwork
    ['campaign', ['eyebrowAr' => 'صُنعت باليد', 'eyebrowEn' => 'Made by Hand', 'titleAr' => 'فنّ التفاصيل', 'titleEn' => 'The Art of Detail',
        'textAr' => 'خرزةٌ بعد خرزة، وغرزةٌ بعد غرزة… هنا تُصنع الفخامة التي تُرى عن قرب.', 'textEn' => 'Bead by bead, stitch by stitch — this is the luxury you see up close.',
        'layout' => 'mosaic', 'items' => [
            ['media' => ref($najma), 'captionAr' => 'ألواح الترتر', 'captionEn' => 'Sequin Panels'],
            ['media' => ref($luluQ[0]), 'captionAr' => 'ورود الخرز', 'captionEn' => 'Beaded Flowers'],
            ['media' => ref($lapel), 'captionAr' => 'الياقة المطرزة', 'captionEn' => 'The Lapel'],
            ['media' => ref($laceQ[2]), 'captionAr' => 'الدانتيل', 'captionEn' => 'Lace'],
            ['media' => ref($clasp), 'captionAr' => 'المشبك الذهبي', 'captionEn' => 'The Gold Clasp'],
            ['media' => ref($najmaQ[1]), 'captionAr' => 'الترتر', 'captionEn' => 'Sequins'],
            ['media' => ref($check2), 'captionAr' => 'نقشة الشماغ', 'captionEn' => 'Keffiyeh Pattern'],
            ['media' => ref($laceQ[3]), 'captionAr' => 'الحافة المخرّمة', 'captionEn' => 'Scalloped Hem'],
            ['media' => ref($luluQ[1]), 'captionAr' => 'أكمام الشيفون', 'captionEn' => 'Chiffon Sleeves'],
        ], 'ctaAr' => 'اكتشفي القطع', 'ctaEn' => 'Discover the Pieces', 'link' => '/ar/shop']],
    // video showcase — kept as a hidden draft: the homepage shows photos only (publish from Admin → Homepage if wanted)
    ['campaign', ['eyebrowAr' => 'شاهديها بالحركة', 'eyebrowEn' => 'See It in Motion', 'titleAr' => 'انسيابٌ يُرى قبل أن يُلمس', 'titleEn' => 'A Drape You Can See',
        'textAr' => 'التفاصيل الحقيقية تظهر حين تتحرك العباية؛ في انسياب القماش، ولمعة النقشة، وحركة الأكمام.', 'textEn' => 'True detail shows when an abaya moves — in the fall of the fabric, the gleam of the pattern, the sway of the sleeves.',
        'layout' => 'showcase', 'items' => [
            ['media' => ref($vid1, $poster1), 'captionAr' => 'نقشة الشماغ', 'captionEn' => 'Keffiyeh Trim'],
            $motion[0],
            $motion[1],
            ['media' => ref($vid2, $poster2), 'captionAr' => 'الياقة والأكمام', 'captionEn' => 'Lapel & Cuffs'],
            $motion[2],
        ],
        'ctaAr' => 'تسوقي عباية الشماغ', 'ctaEn' => 'Shop the Keffiyeh Abaya', 'link' => '/ar/product/satin-keffiyeh-abaya'], false],
    ['collections', ['titleAr' => 'المجموعات', 'titleEn' => 'The Collections', 'collectionIds' => []]],
    ['story', ['eyebrowAr' => 'قصتنا', 'eyebrowEn' => 'Our Story', 'titleAr' => 'من قريةٍ صغيرة… إلى حلمٍ يُرتدى', 'titleEn' => 'From a Small Village to a Dream You Can Wear',
        'textAr' => "من قريةٍ ريفيةٍ صغيرة بدأت الحكاية، هناك حيث نسجت مصممتنا أحلامها من خيوط الشمس.\n\nبإبرةٍ وخيط، ويدين لا تعرفان المستحيل، صنعت أول فستانٍ لها… فلفت الأنظار، وكان بداية الطريق.\n\nآمنت بموهبتها، فدرست تصميم الأزياء ونالت درجة البكالوريوس، ومضت تبتكر تصاميم عصرية تحمل بصمتها الخاصة.\n\nلم تُمنح الشهرة، بل صنعتها بيديها؛ قطعةً بعد قطعة، وثقةً بعد ثقة، حتى صار لها جمهورٌ ينتظر جديدها.\n\nعشر سنواتٍ من التصميم والتنفيذ، من الخطأ والتعلّم، من السهر والشغف…\nوفي كل عبايةٍ من الكوكبة، تجدين شيئاً من تلك الرحلة.",
        'textEn' => "The story began in a small country village, where our designer wove her dreams from threads of sunlight.\n\nWith a needle, a thread and hands that refused the impossible, she made her very first dress — and every eye turned to it. It was the beginning of the road.\n\nBelieving in her gift, she earned a bachelor's degree in fashion design and went on to create modern designs that carry her own signature.\n\nFame was never handed to her; she built it herself — piece by piece, trust by trust — until she had an audience waiting for every new creation.\n\nTen years of designing and making, of mistakes and learning, of late nights and passion…\nand in every Al Kawkaba abaya, you will find a piece of that journey.",
        'media' => ref($house),
        'gallery' => [
            ['media' => ref($check3), 'captionAr' => 'الأقمشة', 'captionEn' => 'Fabric'],
            ['media' => ref($najmaQ[0]), 'captionAr' => 'التطريز', 'captionEn' => 'Embroidery'],
            ['media' => ref($cuff), 'captionAr' => 'التفصيل', 'captionEn' => 'Tailoring'],
            ['media' => ref($laceQ[1]), 'captionAr' => 'اللمسات الأخيرة', 'captionEn' => 'Finishing'],
        ],
        'ctaAr' => 'اكتشفي تصاميمها', 'ctaEn' => 'Discover Her Designs', 'link' => '/ar/shop']],
    // kept as drafts (they repeat the pieces above) — publish from Admin → Homepage if wanted
    ['occasions', ['titleAr' => 'تسوقي حسب المناسبة', 'titleEn' => 'Shop by Occasion', 'items' => [
        ['media' => ref($seq2), 'labelAr' => 'السهرات والمناسبات', 'labelEn' => 'Evening & Events', 'link' => '/ar/shop?category=evening'],
        ['media' => ref($satin), 'labelAr' => 'إطلالات يومية', 'labelEn' => 'Daily Wear', 'link' => '/ar/shop?category=everyday'],
        ['media' => ref($lace3), 'labelAr' => 'الأعراس', 'labelEn' => 'Weddings', 'link' => '/ar/shop?category=wedding'],
        ['media' => ref($najma), 'labelAr' => 'قطع حصرية', 'labelEn' => 'Exclusive Pieces', 'link' => '/ar/shop?tag=limited'],
    ]], false],
    ['ready', ['titleAr' => 'جاهزة للارتداء', 'titleEn' => 'Ready to Wear', 'textAr' => 'قطع متوفرة تُشحن إليكِ فوراً.', 'textEn' => 'Available now, shipped to you right away.', 'limit' => 4, 'ctaAr' => 'تسوقي الجاهز', 'ctaEn' => 'Shop Ready to Wear', 'link' => '/ar/shop?tag=ready'], false],
    ['values', ['titleAr' => 'وعدنا لكِ', 'titleEn' => 'Our Promise', 'items' => [
        ['titleAr' => 'حِرفة متقنة', 'titleEn' => 'Premium Craftsmanship', 'textAr' => 'كل قطعة تُصنع بأيدٍ خبيرة.', 'textEn' => 'Every piece made by expert hands.'],
        ['titleAr' => 'تصاميم حصرية', 'titleEn' => 'Exclusive Designs', 'textAr' => 'تصاميم أصلية لا تتكرر.', 'textEn' => 'Original designs, never repeated.'],
        ['titleAr' => 'أقمشة فاخرة', 'titleEn' => 'Quality Fabrics', 'textAr' => 'خامات مختارة من أرقى المصادر.', 'textEn' => 'Fabrics sourced from the finest mills.'],
        ['titleAr' => 'تفصيل أنيق', 'titleEn' => 'Elegant Tailoring', 'textAr' => 'قصّات مدروسة تمنحكِ الراحة والأناقة.', 'textEn' => 'Considered cuts for comfort and grace.'],
        ['titleAr' => 'تسوق آمن', 'titleEn' => 'Secure Shopping', 'textAr' => 'دفع آمن وخصوصية تامة.', 'textEn' => 'Secure payment, complete privacy.'],
        ['titleAr' => 'خدمة وتوصيل', 'titleEn' => 'Delivery & Care', 'textAr' => 'توصيل سريع وخدمة عملاء بانتظاركِ.', 'textEn' => 'Fast delivery and attentive client care.'],
    ]]],
];
foreach ($sections as $i => $sec) insert('sections', ['id' => new_id(), 'type' => $sec[0], 'sort' => $i, 'published' => (int) ($sec[2] ?? true), 'data' => jencode($sec[1])]);

q("INSERT IGNORE INTO discounts (id, code, type, value) VALUES (?, 'WELCOME10', 'percent', 10)", [new_id()]);
echo 'Seeded ' . count($products) . ' products, ' . count($cols) . ' collections, ' . count($sections) . " sections from public/brand. Admin: $email\n";
