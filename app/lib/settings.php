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

لأي استفسار عن الشحن تواصل معنا عبر واتساب: ‎+967781519343
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
or email alkawkabahabaya@gmail.com', 'returnsAr' => 'رضاكِ يهمّنا 🤍

نحرص أن تصلكِ كل عباية كما تخيّلتِها، وإن لم تكن كذلك فنحن هنا لمساعدتكِ.

الاستبدال
• يمكنكِ استبدال العباية بمقاس آخر أو بقطعة أخرى خلال ٧ أيام من تاريخ الاستلام.

الاسترجاع
• يمكنكِ استرجاع القطع الجاهزة خلال ٣ أيام من تاريخ الاستلام.
• يُعاد المبلغ بنفس طريقة الدفع خلال ٧ أيام عمل من استلامنا للقطعة وفحصها.

شروط الاستبدال والاسترجاع
• أن تكون العباية غير ملبوسة وغير مغسولة، وبحالتها الأصلية.
• أن تكون مع البطاقات والتغليف الأصلي والطرحة إن وُجدت.
• إرفاق رقم الطلب.

قطع لا يمكن استرجاعها
• القطع المفصّلة حسب الطلب أو حسب مقاسكِ الخاص.
• القطع المعدّلة بناءً على طلبكِ (تقصير، تضييق، إضافات).
• قطع العروض والتخفيضات النهائية، إلا إذا كان بها عيب.

العيوب والأخطاء
• إذا وصلتكِ القطعة بعيب في الصناعة أو بمقاس/تصميم غير الذي طلبتِه، نستبدلها لكِ أو نعيد المبلغ كاملاً، ونتحمّل نحن رسوم الشحن.
• يرجى إبلاغنا خلال ٤٨ ساعة من الاستلام مع صور واضحة للقطعة.

رسوم الشحن
• في حالات الاستبدال أو الاسترجاع لغير العيوب، تتحمّل العميلة رسوم الشحن.

كيف تطلبين الاستبدال أو الاسترجاع؟
١. تواصل معنا عبر واتساب ‎+967781519343 أو البريد alkawkabahabaya@gmail.com
٢. أرسلي رقم الطلب وسبب الاستبدال أو الاسترجاع وصورة للقطعة.
٣. نرسل لكِ تفاصيل إعادة القطعة، ونتابع معكِ حتى إتمام الطلب.', 'returnsEn' => 'Your satisfaction matters to us 🤍

We want every abaya to arrive exactly as you imagined it — and if it doesn\'t, we\'re here to help.

Exchanges
• You can exchange your abaya for another size or piece within 7 days of delivery.

Returns
• Ready-to-wear pieces can be returned within 3 days of delivery.
• Refunds are made to the original payment method within 7 business days of receiving and inspecting the piece.

Conditions for exchanges and returns
• The abaya must be unworn, unwashed and in its original condition.
• It must include its tags, original packaging and matching shayla, if any.
• Please include your order number.

Non-returnable items
• Made-to-order or made-to-measure pieces.
• Pieces altered at your request (shortening, taking in, additions).
• Final-sale items, unless they are defective.

Defects and errors
• If your piece arrives with a manufacturing defect, or in a size/design other than the one you ordered, we will exchange it or refund it in full and cover the shipping.
• Please let us know within 48 hours of delivery with clear photos of the piece.

Shipping fees
• For exchanges or returns that are not due to a defect, shipping is paid by the customer.

How to request an exchange or return
1. Contact us on WhatsApp +967781519343 or at alkawkabahabaya@gmail.com
2. Send your order number, the reason and a photo of the piece.
3. We\'ll send you the return details and follow up with you until it\'s complete.', 'privacyAr' => 'خصوصيتكِ أمانة لدينا 🔒

نلتزم في الكوكبة للعبايات بحماية بياناتكِ الشخصية واستخدامها فقط لخدمتكِ.

البيانات التي نجمعها
• الاسم، رقم الجوال، البريد الإلكتروني، وعنوان الشحن عند إتمام الطلب أو إنشاء حساب.
• تفاصيل طلباتكِ (القطع، المقاسات، طريقة الدفع).
• بريدكِ الإلكتروني إذا اشتركتِ في النشرة البريدية.

كيف نستخدم بياناتكِ
• لتجهيز طلبكِ وشحنه والتواصل معكِ بخصوصه، بما في ذلك عبر واتساب.
• لإرسال رقم تتبّع الشحنة وتحديثات الطلب.
• لإرسال جديد المجموعات والعروض فقط إذا اشتركتِ في النشرة البريدية.

مشاركة البيانات
• لا نبيع بياناتكِ ولا نؤجرها لأي جهة.
• نشارك فقط ما يلزم (الاسم والعنوان ورقم الجوال) مع شركة الشحن لتوصيل طلبكِ.

الدفع
• لا نحتفظ ببيانات بطاقات الدفع على موقعنا.

ملفات تعريف الارتباط والتخزين
• نستخدم ملفات تعريف ارتباط ضرورية فقط: لتسجيل الدخول، وحفظ اللغة التي اخترتِها، وحماية النماذج من الاستخدام غير المصرّح. لا نستخدم ملفات تتبّع إعلانية.
• تُحفظ حقيبة التسوق والمفضلة على جهازكِ لتسهيل التسوق.

حماية البيانات
• نحمي بياناتكِ باتصال آمن وكلمات مرور مشفّرة، ونحصر الوصول إليها على فريق المتجر.

حقوقكِ
• يمكنكِ طلب الاطلاع على بياناتكِ أو تعديلها أو حذفها في أي وقت.
• يمكنكِ إلغاء الاشتراك في النشرة البريدية متى شئتِ.

للتواصل بخصوص الخصوصية: alkawkabahabaya@gmail.com
أو واتساب: ‎+967781519343

قد نحدّث هذه السياسة من وقت لآخر، وتُنشر أي تغييرات على هذه الصفحة.', 'privacyEn' => 'Your privacy is in safe hands 🔒

At Al Kawkaba Abayas we protect your personal data and use it only to serve you.

What we collect
• Your name, phone number, email and shipping address when you order or create an account.
• Your order details (pieces, sizes, payment method).
• Your email if you subscribe to our newsletter.

How we use it
• To prepare and ship your order and to contact you about it, including on WhatsApp.
• To send you the tracking number and order updates.
• To send news about collections and offers — only if you subscribed to the newsletter.

Sharing
• We never sell or rent your data.
• We share only what is needed (name, address, phone) with the courier to deliver your order.

Payments
• We do not store card details on our website.

Cookies and storage
• We use essential cookies only: to keep you signed in, remember your language and protect our forms. We do not use advertising trackers.
• Your bag and wishlist are saved on your own device to make shopping easier.

Security
• Your data is protected with a secure connection and encrypted passwords, and only the store team can access it.

Your rights
• You can ask to see, correct or delete your data at any time.
• You can unsubscribe from the newsletter whenever you like.

Privacy questions: alkawkabahabaya@gmail.com
or WhatsApp: +967781519343

We may update this policy from time to time; any changes will be posted on this page.', 'termsAr' => '', 'termsEn' => ''],
        'migrations' => [],   // one-off data updates already applied to this database (see app/lib/migrations.php)
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
