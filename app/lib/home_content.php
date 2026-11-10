<?php
/** Default homepage content shared by database/seed.php and the one-off migrations (so the live server gets it too). */
function winter_teaser_data(): array
{
    return [
        'eyebrowAr' => 'مجموعة قادمة', 'eyebrowEn' => 'The Coming Collection',
        'titleAr' => 'عبايات الشتاء', 'titleEn' => 'Winter Abayas',
        'yearAr' => '٢٠٢٦ — ٢٠٢٧', 'yearEn' => '2026 — 2027',
        'soonAr' => 'قريباً', 'soonEn' => 'COMING SOON',
        'textAr' => "دفءٌ يليق بفخامتكِ…\nأقمشةٌ دافئة، وتطريزٌ يلمع كضوء الشتاء، تُنسج الآن في مشغلنا.",
        'textEn' => "Warmth worthy of your elegance…\nRich fabrics and embroidery that gleams like winter light, being crafted now in our atelier.",
        'media' => ['url' => '/brand/Gemini_Generated_Image_h6ldfch6ldfch6ld.jpeg', 'kind' => 'image', 'altAr' => 'لمحة من مجموعة الشتاء', 'altEn' => 'A glimpse of the winter collection'],
        'launchDate' => '',
        'notifyAr' => 'كوني أول من يعرف بموعد الإطلاق', 'notifyEn' => 'Be the first to know when it launches',
        'notifyBtnAr' => 'أعلميني', 'notifyBtnEn' => 'Notify me',
    ];
}

function home_shipping_data(): array
{
    return ['titleAr' => 'التوصيل متوفر', 'titleEn' => 'We Deliver To', 'items' => [
        ['titleAr' => 'اليمن', 'titleEn' => 'Yemen', 'textAr' => '٣–٧ أيام عمل', 'textEn' => '3–7 business days'],
        ['titleAr' => 'دول الخليج', 'titleEn' => 'The Gulf', 'textAr' => '٣–٧ أيام عمل', 'textEn' => '3–7 business days'],
        ['titleAr' => 'جميع أنحاء العالم', 'titleEn' => 'Worldwide', 'textAr' => '٧–١٥ يوم عمل', 'textEn' => '7–15 business days'],
    ]];
}

function home_faq_data(): array
{
    return ['eyebrowAr' => 'لديكِ سؤال؟', 'eyebrowEn' => 'Questions?', 'titleAr' => 'الأسئلة الشائعة', 'titleEn' => 'Frequently Asked Questions', 'items' => [
        ['qAr' => 'هل توصلون خارج اليمن؟', 'qEn' => 'Do you deliver outside Yemen?',
         'aAr' => 'نعم، التوصيل متوفر داخل اليمن ولجميع دول الخليج ولكل أنحاء العالم.', 'aEn' => 'Yes. We deliver across Yemen, to all Gulf countries and worldwide.'],
        ['qAr' => 'كم يستغرق التوصيل؟', 'qEn' => 'How long does delivery take?',
         'aAr' => 'تُجهَّز القطع الجاهزة خلال ١–٣ أيام عمل، ثم تصل خلال ٣–٧ أيام عمل لليمن والخليج، و٧–١٥ يوم عمل لباقي دول العالم.',
         'aEn' => 'Ready-to-wear pieces are prepared within 1–3 business days, then arrive within 3–7 business days in Yemen and the Gulf, and 7–15 business days elsewhere.'],
        ['qAr' => 'كيف أطلب العباية؟', 'qEn' => 'How do I order?',
         'aAr' => 'اختاري المقاس وأضيفي العباية إلى الحقيبة ثم أكملي الطلب، أو اطلبيها مباشرة عبر واتساب، ونتواصل معكِ لتأكيد الطلب.',
         'aEn' => 'Choose your size, add the abaya to your bag and check out, or order directly on WhatsApp. We will contact you to confirm.'],
        ['qAr' => 'هل يمكن تفصيل العباية حسب مقاسي؟', 'qEn' => 'Can my abaya be made to measure?',
         'aAr' => 'نعم، تُجهَّز القطع المفصّلة حسب المقاس خلال ٧–١٤ يوم عمل. أرسلي مقاساتكِ عبر واتساب.',
         'aEn' => 'Yes. Made-to-measure pieces are prepared within 7–14 business days. Send us your measurements on WhatsApp.'],
        ['qAr' => 'هل يمكن الاستبدال أو الإرجاع؟', 'qEn' => 'Can I exchange or return?',
         'aAr' => "الاستبدال خلال ٧ أيام من الاستلام، وإرجاع القطع الجاهزة خلال ٣ أيام، بشرط أن تكون غير ملبوسة وبحالتها الأصلية.\nالتفاصيل في صفحة «الاستبدال والاسترجاع».",
         'aEn' => "Exchanges within 7 days of delivery and returns of ready-to-wear pieces within 3 days, if unworn and in original condition.\nFull details on the Exchanges & Returns page."],
        ['qAr' => 'كيف أتابع طلبي؟', 'qEn' => 'How do I track my order?',
         'aAr' => 'بعد شحن الطلب نرسل لكِ رقم التتبّع عبر واتساب أو البريد، ويمكنكِ متابعته من صفحة «تتبع الطلب».',
         'aEn' => 'Once your order ships we send the tracking number by WhatsApp or email, and you can follow it on the Track Order page.'],
        ['qAr' => 'كيف أتواصل معكم؟', 'qEn' => 'How can I contact you?',
         'aAr' => "واتساب: ‎+967781519343\nالبريد: alkawkabahabaya@gmail.com\nإنستغرام: @alkawkabah_abaya",
         'aEn' => "WhatsApp: +967781519343\nEmail: alkawkabahabaya@gmail.com\nInstagram: @alkawkabah_abaya"],
    ]];
}
