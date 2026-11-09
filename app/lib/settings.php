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
        // countries: '*' = ship worldwide (or a comma-separated list of ISO codes)
        'shipping' => ['flat' => 30, 'freeOver' => 500, 'countries' => '*'],
        'payments' => [
            'cod' => true, 'bank' => true, 'bankDetailsAr' => '', 'bankDetailsEn' => '',
            // card gateway is a setting + adapter slot in app/lib/payments.php; wire the provider when chosen.
            'gateway' => 'none',
        ],
        'policies' => ['shippingAr' => 'نشحن إلى جميع أنحاء العالم 🌍

أينما كنتِ، تصلكِ عباية الكوكبة مغلّفة بعناية تليق بها.

مدة التجهيز
• القطع الجاهزة: تُجهَّز وتُشحن خلال ١–٣ أيام عمل.
• القطع المفصّلة حسب الطلب أو حسب مقاسكِ: تُجهَّز خلال ٧–١٤ يوم عمل.

مدة التوصيل (بعد الشحن)
• اليمن ودول الخليج: ٣–٧ أيام عمل.
• باقي الدول العربية: ٥–١٠ أيام عمل.
• أوروبا وأمريكا وباقي دول العالم: ٧–١٥ يوم عمل.
قد تختلف المدة قليلاً بحسب المدينة وإجراءات الجمارك.

رسوم الشحن
• تظهر رسوم الشحن كاملة قبل تأكيد الطلب، دون أي رسوم مخفية.
• الشحن مجاني للطلبات التي تتجاوز ٥٠٠ ر.س.

تتبّع الطلب
• بعد شحن طلبكِ نرسل لكِ رقم التتبّع عبر واتساب أو البريد الإلكتروني.
• يمكنكِ متابعة طلبكِ في أي وقت من صفحة «تتبع الطلب».

الجمارك والضرائب
• قد تفرض بعض الدول رسوماً جمركية أو ضرائب عند الاستلام، وتكون على المستلِمة وفق أنظمة بلدها.

العنوان والاستلام
• يرجى التأكد من صحة العنوان ورقم الجوال عند الطلب، فنحن غير مسؤولين عن التأخير الناتج عن بيانات غير صحيحة.
• في حال تعذّر التسليم يتواصل معكِ فريقنا لترتيب موعد جديد.

لأي استفسار عن الشحن تواصلي معنا عبر واتساب: ‎+967781519343
أو البريد: alkawkabahabaya@gmail.com', 'shippingEn' => 'We ship worldwide 🌍

Wherever you are, your Al Kawkaba abaya arrives carefully wrapped, as it deserves.

Processing time
• Ready-to-wear pieces: prepared and shipped within 1–3 business days.
• Made-to-order or made-to-measure pieces: prepared within 7–14 business days.

Delivery time (after dispatch)
• Yemen and the Gulf: 3–7 business days.
• Other Arab countries: 5–10 business days.
• Europe, the Americas and the rest of the world: 7–15 business days.
Times may vary slightly by city and customs clearance.

Shipping fees
• The full shipping fee is shown before you confirm your order — no hidden charges.
• Free shipping on orders over SAR 500.

Order tracking
• Once your order ships, we send you the tracking number by WhatsApp or email.
• You can follow your order any time from the “Track Order” page.

Customs and taxes
• Some countries charge customs duties or taxes on delivery; these are paid by the recipient according to local regulations.

Address and delivery
• Please make sure your address and phone number are correct; we are not responsible for delays caused by incorrect details.
• If delivery is not possible, our team will contact you to arrange a new time.

Questions about shipping? WhatsApp us on +967781519343
or email alkawkabahabaya@gmail.com', 'returnsAr' => '', 'returnsEn' => '', 'privacyAr' => '', 'privacyEn' => '', 'termsAr' => '', 'termsEn' => ''],
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
