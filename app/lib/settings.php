<?php
function default_settings(): array
{
    return [
        'brand' => [
            'nameAr' => 'الكوكبة', 'nameEn' => 'Al Kawkaba',
            'taglineAr' => 'للعبايات', 'taglineEn' => 'Abayas',
            'aboutAr' => 'دار عبايات فاخرة تحتفي بالأناقة الهادئة والحِرفة المتقنة، حيث تُصاغ كل قطعة بعناية لتليق بكِ.',
            'aboutEn' => 'A house of luxury abayas celebrating quiet elegance and masterful craft — every piece composed with care, made for you.',
            'logoUrl' => '/brand/logo.jpg',
        ],
        'contact' => ['email' => 'alkawkabahabaya@gmail.com', 'phone' => '', 'whatsapp' => '+967781519343', 'addressAr' => '', 'addressEn' => ''],
        'social' => ['instagram' => 'https://www.instagram.com/alkawkabah_abaya', 'tiktok' => '', 'snapchat' => '', 'x' => ''],
        'currency' => 'SAR',
        'shipping' => ['flat' => 30, 'freeOver' => 500, 'countries' => 'SA,AE,KW,QA,BH,OM'],
        'payments' => [
            'cod' => true, 'bank' => true, 'bankDetailsAr' => '', 'bankDetailsEn' => '',
            // card gateway is a setting + adapter slot in app/lib/payments.php; wire the provider when chosen.
            'gateway' => 'none',
        ],
        'policies' => ['shippingAr' => '', 'shippingEn' => '', 'returnsAr' => '', 'returnsEn' => '', 'privacyAr' => '', 'privacyEn' => '', 'termsAr' => '', 'termsEn' => ''],
        'seo' => ['titleAr' => 'الكوكبة للعبايات', 'titleEn' => 'Al Kawkaba Abayas', 'descAr' => 'عبايات فاخرة مصممة بإتقان.', 'descEn' => 'Luxury abayas, masterfully crafted.', 'ogImage' => ''],
    ];
}

/** Defaults merged with what the admin saved (once per request). */
function settings(): array
{
    static $s;
    if ($s) return $s;
    $s = default_settings();
    foreach (rows('SELECT `key`, `value` FROM settings') as $r) {
        if (!array_key_exists($r['key'], $s)) continue;
        $v = json_decode($r['value'], true);
        $s[$r['key']] = is_array($s[$r['key']]) && is_array($v) ? array_merge($s[$r['key']], $v) : $v;
    }
    return $s;
}

function save_setting(string $key, mixed $value): void
{
    q('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, jencode($value)]);
}
