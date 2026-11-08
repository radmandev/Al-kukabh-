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
$house = brand('videos/WhatsApp Image 2026-11-08 at 7.48.19 PM.jpeg', 'الكوكبة للعبايات', 'Al Kawkaba Abayas');
$poster1 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.54 PM.poster.jpg', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim');
$poster2 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.55 PM.poster.jpg', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim');
$vid1 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.54 PM.mp4', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim', $poster1);
$vid2 = brand('videos/WhatsApp Video 2026-11-07 at 4.00.55 PM.mp4', 'عباية بنقشة الشماغ', 'Abaya with keffiyeh-pattern trim', $poster2);

// 5. catalog
$cats = [];
foreach ([['occasion', 'عبايات المناسبات', 'Occasion'], ['everyday', 'عبايات يومية', 'Everyday'], ['wedding', 'الأعراس', 'Weddings'], ['evening', 'السهرات', 'Evening']] as $i => [$slug, $ar, $en]) {
    insert('categories', ['id' => $cats[$slug] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'sort' => $i]);
}

$cols = [];
foreach ([
    ['layali', 'ليالي', 'Layali', 'عبايات السهرة بتطريز يدوي وأقمشة فاخرة.', 'Evening abayas with hand embroidery and rich fabrics.', $seq1],
    ['noor', 'مجموعة نور', 'The Noor Collection', 'قصّات كلاسيكية بلمسات ذهبية خافتة، مستوحاة من ضوء الفجر.', 'Classic silhouettes touched with soft gold, inspired by first light.', $classic],
    ['essentials', 'الأساسيات', 'Essentials', 'قطع يومية بخامات ناعمة وتفصيل متقن.', 'Everyday pieces in soft fabrics, impeccably tailored.', $satin],
    ['atelier', 'إصدارات الأتيليه', 'Atelier Editions', 'قطع محدودة العدد تُصنع يدوياً في مشغلنا.', 'Limited pieces, handmade in our atelier.', $check1],
] as $i => [$slug, $ar, $en, $dAr, $dEn, $cover]) {
    insert('collections', ['id' => $cols[$slug] = new_id(), 'slug' => $slug, 'nameAr' => $ar, 'nameEn' => $en, 'descAr' => $dAr, 'descEn' => $dEn, 'coverId' => $cover['id'], 'sort' => $i, 'published' => 1, 'createdAt' => now()]);
}

// Names, prices and texts are starting points — edit them in Admin → Products.
$care = ['careAr' => 'تنظيف جاف فقط. يُكوى على حرارة منخفضة.', 'careEn' => 'Dry clean only. Iron on low heat.'];
$products = [
    [
        'slug' => 'diya-abaya', 'nameAr' => 'عباية ضياء', 'nameEn' => 'Diya Abaya', 'price' => 890, 'salePrice' => null,
        'descAr' => 'عباية سوداء كلاسيكية بياقة وأكمام مطرزة يدوياً بالخرز والترتر، تُغلق بمشبك ذهبي.',
        'descEn' => 'A classic black abaya with a lapel and cuffs hand-embroidered in beads and sequins, closed with a gold clasp.',
        'detailsAr' => "ياقة مطرزة بالخرز والترتر\nأكمام مطرزة\nمشبك ذهبي", 'detailsEn' => "Beaded, sequinned lapel\nEmbroidered cuffs\nGold clasp fastening",
        'gallery' => [$classic, $lapel, $cuff, $clasp], 'collection' => 'noor', 'category' => 'occasion',
        'isNew' => true, 'isFeatured' => false, 'isLimited' => false, 'isReady' => true,
        'deliveryAr' => 'تُشحن خلال 24 ساعة', 'deliveryEn' => 'Ships within 24 hours',
    ],
    [
        'slug' => 'lulu-abaya', 'nameAr' => 'عباية لؤلؤ', 'nameEn' => 'Lulu Abaya', 'price' => 1480, 'salePrice' => 1190,
        'descAr' => 'عباية سهرة سوداء تتلألأ بتطريز الترتر على الصدر والأكمام.',
        'descEn' => 'A black evening abaya that glimmers with sequin embroidery across the front and sleeves.',
        'detailsAr' => "تطريز ترتر على الصدر\nأكمام مطرزة\nقصة واسعة", 'detailsEn' => "Sequin embroidery across the front\nEmbroidered sleeves\nRelaxed fit",
        'gallery' => [$seq1, $seq2], 'collection' => 'layali', 'category' => 'evening',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => false, 'isReady' => false,
    ],
    [
        'slug' => 'yasmin-abaya', 'nameAr' => 'عباية ياسمين', 'nameEn' => 'Yasmin Abaya', 'price' => 1250, 'salePrice' => null,
        'descAr' => 'عباية سوداء انسيابية تزيّن أطرافها وأكمامها دانتيل ناعم.',
        'descEn' => 'A flowing black abaya finished with delicate lace at the hem and sleeves.',
        'detailsAr' => "دانتيل على الأطراف\nأكمام دانتيل\nقصة انسيابية", 'detailsEn' => "Lace hem\nLace sleeves\nFlowing cut",
        'gallery' => [$lace1, $lace2, $lace3], 'collection' => 'layali', 'category' => 'wedding',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => false, 'isReady' => true,
        'deliveryAr' => 'تُشحن خلال 24 ساعة', 'deliveryEn' => 'Ships within 24 hours',
    ],
    [
        'slug' => 'sahar-abaya', 'nameAr' => 'عباية سَحَر', 'nameEn' => 'Sahar Abaya', 'price' => 1890, 'salePrice' => null,
        'descAr' => 'عباية سوداء بياقة وأكمام بنقشة الشماغ، تصميم يجمع الأصالة والأناقة العصرية.',
        'descEn' => 'A black abaya with keffiyeh-pattern lapels and cuffs — heritage detail with a modern line.',
        'detailsAr' => "ياقة بنقشة الشماغ\nأكمام بنقشة الشماغ\nإصدار محدود", 'detailsEn' => "Keffiyeh-pattern lapels\nKeffiyeh-pattern cuffs\nLimited edition",
        'gallery' => [$check1, $check2, $check3, $vid1, $vid2], 'collection' => 'atelier', 'category' => 'occasion',
        'isNew' => true, 'isFeatured' => true, 'isLimited' => true, 'isReady' => false, 'stock' => 5,
    ],
    [
        'slug' => 'misk-abaya', 'nameAr' => 'عباية مِسك', 'nameEn' => 'Misk Abaya', 'price' => 720, 'salePrice' => null,
        'descAr' => 'عباية من الساتان الأسود بقصة واسعة وأكمام مطرزة.',
        'descEn' => 'A black satin abaya with a relaxed cut and embroidered sleeves.',
        'fabricAr' => 'ساتان', 'fabricEn' => 'Satin',
        'detailsAr' => "قماش ساتان لامع\nأكمام مطرزة\nقصة واسعة", 'detailsEn' => "Lustrous satin\nEmbroidered sleeves\nRelaxed fit",
        'gallery' => [$satin], 'collection' => 'essentials', 'category' => 'everyday',
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
    // desktop: three portrait photos side by side, sliding one step at a time; mobile: one full-screen photo or video per slide
    ['hero', ['slides' => [
        ['media' => ref($seq1), 'mobileMedia' => ref($vid1, $poster1), 'eyebrowAr' => 'المجموعة الجديدة', 'eyebrowEn' => 'New Collection', 'headingAr' => 'أناقة تُروى بهدوء', 'headingEn' => 'Elegance, Quietly Told', 'textAr' => 'قطع خالدة صُممت لتليق بكِ في كل لحظة.', 'textEn' => 'Timeless pieces composed for every moment of yours.', 'ctaAr' => 'اكتشفي المجموعة', 'ctaEn' => 'Discover the Collection', 'link' => '/ar/collections/layali'],
        ['media' => ref($lace1)],
        ['media' => ref($check1), 'mobileMedia' => ref($vid2, $poster2), 'eyebrowAr' => 'إصدارات الأتيليه', 'eyebrowEn' => 'Atelier Editions', 'headingAr' => 'أصالةٌ بلمسة عصرية', 'headingEn' => 'Heritage, Modern Line', 'ctaAr' => 'تسوقي الآن', 'ctaEn' => 'Shop Now', 'link' => '/ar/collections/atelier'],
        ['media' => ref($classic)],
        ['media' => ref($lace2)],
        ['media' => ref($satin)],
    ]]],
    ['products', ['titleAr' => 'وصل حديثاً', 'titleEn' => 'New Arrivals', 'source' => 'new', 'limit' => 8, 'ctaAr' => 'عرض الكل', 'ctaEn' => 'View All', 'link' => '/ar/shop?tag=new']],
    ['collections', ['titleAr' => 'المجموعات', 'titleEn' => 'The Collections', 'collectionIds' => []]],
    ['occasions', ['titleAr' => 'تسوقي حسب المناسبة', 'titleEn' => 'Shop by Occasion', 'items' => [
        ['media' => ref($seq2), 'labelAr' => 'السهرات والمناسبات', 'labelEn' => 'Evening & Events', 'link' => '/ar/shop?category=evening'],
        ['media' => ref($satin), 'labelAr' => 'إطلالات يومية', 'labelEn' => 'Daily Wear', 'link' => '/ar/shop?category=everyday'],
        ['media' => ref($lace3), 'labelAr' => 'الأعراس', 'labelEn' => 'Weddings', 'link' => '/ar/shop?category=wedding'],
        ['media' => ref($vid1, $poster1), 'labelAr' => 'قطع حصرية', 'labelEn' => 'Exclusive Pieces', 'link' => '/ar/shop?tag=limited'],
    ]]],
    ['ready', ['titleAr' => 'جاهزة للارتداء', 'titleEn' => 'Ready to Wear', 'textAr' => 'قطع متوفرة تُشحن إليكِ فوراً.', 'textEn' => 'Available now, shipped to you right away.', 'limit' => 4, 'ctaAr' => 'تسوقي الجاهز', 'ctaEn' => 'Shop Ready to Wear', 'link' => '/ar/shop?tag=ready']],
    ['story', ['eyebrowAr' => 'الأتيليه', 'eyebrowEn' => 'The Atelier', 'titleAr' => 'حيث تولد كل قطعة', 'titleEn' => 'Where Every Piece Begins',
        'textAr' => 'في مشغلنا، تمر كل عباية بأيدٍ خبيرة — من اختيار القماش إلى آخر غرزة تطريز. نؤمن أن الفخامة الحقيقية تكمن في التفاصيل التي لا تُرى.',
        'textEn' => "In our atelier, every abaya passes through expert hands — from selecting the fabric to the final embroidered stitch. We believe true luxury lives in the details you don't see.",
        'media' => ref($house),
        'gallery' => [
            ['media' => ref($check3), 'captionAr' => 'الأقمشة', 'captionEn' => 'Fabric'],
            ['media' => ref($lapel), 'captionAr' => 'التطريز', 'captionEn' => 'Embroidery'],
            ['media' => ref($cuff), 'captionAr' => 'التفصيل', 'captionEn' => 'Tailoring'],
            ['media' => ref($clasp), 'captionAr' => 'اللمسات الأخيرة', 'captionEn' => 'Finishing'],
        ],
        'ctaAr' => 'قصتنا', 'ctaEn' => 'Our Story', 'link' => '/ar/story']],
    ['campaign', ['eyebrowAr' => 'حملة الموسم', 'eyebrowEn' => 'Seasonal Campaign', 'titleAr' => 'ضوء الكوكبة', 'titleEn' => 'Light of Al Kawkaba', 'layout' => 'split',
        'items' => [['media' => ref($vid2, $poster2)], ['media' => ref($check2)]], 'ctaAr' => 'اكتشفي', 'ctaEn' => 'Discover', 'link' => '/ar/collections/atelier']],
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
echo 'Seeded ' . count($products) . ' products, ' . count($cols) . ' collections, ' . count($sections) . " sections from public/brand. Admin: $email\n";
