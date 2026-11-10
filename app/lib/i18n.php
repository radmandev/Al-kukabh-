<?php
const LOCALES = ['ar', 'en'];

function is_locale(string $l): bool { return in_array($l, LOCALES, true); }
function dir_for(string $l): string { return $l === 'ar' ? 'rtl' : 'ltr'; }

/** Localized field with fallback to the other language: L($p, 'name', 'ar') → $p['nameAr'] */
function L(array $o, string $field, string $l): string
{
    $a = $o[$field . ($l === 'ar' ? 'Ar' : 'En')] ?? '';
    $b = $o[$field . ($l === 'ar' ? 'En' : 'Ar')] ?? '';
    return (string) ($a !== '' && $a !== null ? $a : $b);
}

/** CMS links: "/shop" or "/ar/shop" → current locale; external links untouched. */
function localize(string $link, string $l): string
{
    if (!str_starts_with($link, '/') || str_starts_with($link, '//') || preg_match('#^/(admin|media|api)\b#', $link)) return $link;
    $rest = preg_replace('#^/(ar|en)(?=/|$|\?)#', '', $link);
    $out = rtrim("/$l$rest", '/');
    return $out === '' ? "/$l" : $out;
}

/** The storefront dictionary. t('shop') or $t['shop'] */
function dict(string $l): array
{
    static $d;
    $d ??= [
        'ar' => [
            'shop' => 'جميع العبايات', 'newArrivals' => 'وصل حديثاً', 'collections' => 'المجموعات',
            'occasion' => 'عبايات المناسبات', 'everyday' => 'عبايات يومية', 'limited' => 'إصدارات محدودة',
            'story' => 'قصتنا', 'search' => 'بحث', 'account' => 'حسابي', 'bag' => 'الحقيبة', 'wishlist' => 'المفضلة',
            'shopNow' => 'تسوقي الآن', 'discover' => 'اكتشفي', 'viewAll' => 'عرض الكل', 'quickView' => 'نظرة سريعة',
            'new' => 'جديد', 'limitedLabel' => 'إصدار محدود', 'ready' => 'جاهزة للشحن', 'madeToOrder' => 'تفصيل حسب الطلب',
            'outOfStock' => 'نفدت الكمية', 'inStock' => 'متوفرة', 'size' => 'المقاس', 'color' => 'اللون', 'qty' => 'الكمية',
            'addToBag' => 'أضيفي إلى الحقيبة', 'buyNow' => 'اشتري الآن', 'orderWhatsapp' => 'اطلبي عبر واتساب', 'chatWhatsapp' => 'تواصل معنا عبر واتساب', 'sendWhatsapp' => 'أرسلي الطلب عبر واتساب', 'waRedirect' => 'نفتح واتساب الآن لإرسال تفاصيل طلبكِ إلينا…', 'addToWishlist' => 'أضيفي إلى المفضلة',
            'description' => 'الوصف', 'fabric' => 'القماش', 'details' => 'التفاصيل', 'care' => 'العناية', 'delivery' => 'التوصيل',
            'related' => 'قد يعجبك أيضاً', 'recent' => 'شاهدتِ مؤخراً', 'emptyBag' => 'حقيبتك فارغة',
            'subtotal' => 'المجموع الفرعي', 'shipping' => 'الشحن', 'discount' => 'الخصم', 'total' => 'الإجمالي',
            'checkout' => 'إتمام الطلب', 'continueShopping' => 'متابعة التسوق', 'remove' => 'إزالة',
            'discountCode' => 'كود الخصم', 'apply' => 'تطبيق', 'placeOrder' => 'تأكيد الطلب',
            'contactInfo' => 'معلومات التواصل', 'shippingAddress' => 'عنوان الشحن', 'payment' => 'طريقة الدفع',
            'name' => 'الاسم الكامل', 'email' => 'البريد الإلكتروني', 'phone' => 'رقم الجوال', 'country' => 'الدولة',
            'city' => 'المدينة', 'address' => 'العنوان', 'notes' => 'ملاحظات', 'password' => 'كلمة المرور',
            'login' => 'تسجيل الدخول', 'register' => 'إنشاء حساب', 'logout' => 'تسجيل الخروج', 'myOrders' => 'طلباتي',
            'orderPlaced' => 'شكراً لكِ، تم استلام طلبك', 'orderNumber' => 'رقم الطلب', 'trackOrder' => 'تتبع الطلب',
            'track' => 'تتبع', 'status' => 'الحالة', 'sortBy' => 'ترتيب حسب', 'filter' => 'تصفية', 'all' => 'الكل',
            'newest' => 'الأحدث', 'priceAsc' => 'السعر: من الأقل', 'priceDesc' => 'السعر: من الأعلى',
            'category' => 'الفئة', 'availability' => 'التوفر', 'results' => 'منتج', 'noResults' => 'لا توجد نتائج',
            'newsletter' => 'انضمي إلى عالم الكوكبة', 'newsletterText' => 'كوني أول من يعرف عن المجموعات الجديدة والإصدارات الحصرية.',
            'subscribe' => 'اشتراك', 'subscribed' => 'شكراً لاشتراكك', 'customerService' => 'خدمة العملاء',
            'shippingPolicy' => 'سياسة الشحن', 'returns' => 'الاستبدال والإرجاع', 'privacy' => 'سياسة الخصوصية',
            'terms' => 'الشروط والأحكام', 'contact' => 'تواصل معنا', 'followUs' => 'تابعنا', 'rights' => 'جميع الحقوق محفوظة',
            'menu' => 'القائمة', 'close' => 'إغلاق', 'searchPlaceholder' => 'ابحثي عن عباية...', 'guest' => 'أو أكملي كضيفة',
            'haveAccount' => 'لديكِ حساب؟', 'noAccount' => 'ليس لديكِ حساب؟', 'invalidLogin' => 'البريد أو كلمة المرور غير صحيحة',
            'emailTaken' => 'البريد مستخدم مسبقاً', 'required' => 'هذا الحقل مطلوب', 'invalidCode' => 'كود غير صالح',
            'selectSize' => 'يرجى اختيار المقاس', 'added' => 'تمت الإضافة إلى الحقيبة', 'free' => 'مجاني',
            'emptyWishlist' => 'لا توجد قطع في المفضلة', 'orderNotFound' => 'لم يتم العثور على الطلب',
            'was' => 'السعر السابق', 'prev' => 'السابق', 'next' => 'التالي',
        ],
        'en' => [
            'shop' => 'All Abayas', 'newArrivals' => 'New Arrivals', 'collections' => 'Collections',
            'occasion' => 'Occasion Abayas', 'everyday' => 'Everyday Abayas', 'limited' => 'Limited Edition',
            'story' => 'Our Story', 'search' => 'Search', 'account' => 'Account', 'bag' => 'Bag', 'wishlist' => 'Wishlist',
            'shopNow' => 'Shop Now', 'discover' => 'Discover', 'viewAll' => 'View All', 'quickView' => 'Quick View',
            'new' => 'New', 'limitedLabel' => 'Limited Edition', 'ready' => 'Ready to Ship', 'madeToOrder' => 'Made to Order',
            'outOfStock' => 'Sold Out', 'inStock' => 'In Stock', 'size' => 'Size', 'color' => 'Colour', 'qty' => 'Quantity',
            'addToBag' => 'Add to Bag', 'buyNow' => 'Buy Now', 'orderWhatsapp' => 'Order via WhatsApp', 'chatWhatsapp' => 'Chat with us on WhatsApp', 'sendWhatsapp' => 'Send order on WhatsApp', 'waRedirect' => 'Opening WhatsApp to send us your order details…', 'addToWishlist' => 'Add to Wishlist',
            'description' => 'Description', 'fabric' => 'Fabric', 'details' => 'Details', 'care' => 'Care', 'delivery' => 'Delivery',
            'related' => 'You May Also Like', 'recent' => 'Recently Viewed', 'emptyBag' => 'Your bag is empty',
            'subtotal' => 'Subtotal', 'shipping' => 'Shipping', 'discount' => 'Discount', 'total' => 'Total',
            'checkout' => 'Checkout', 'continueShopping' => 'Continue Shopping', 'remove' => 'Remove',
            'discountCode' => 'Discount code', 'apply' => 'Apply', 'placeOrder' => 'Place Order',
            'contactInfo' => 'Contact', 'shippingAddress' => 'Shipping Address', 'payment' => 'Payment',
            'name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'country' => 'Country',
            'city' => 'City', 'address' => 'Address', 'notes' => 'Notes', 'password' => 'Password',
            'login' => 'Sign In', 'register' => 'Create Account', 'logout' => 'Sign Out', 'myOrders' => 'My Orders',
            'orderPlaced' => 'Thank you, your order has been received', 'orderNumber' => 'Order number', 'trackOrder' => 'Track Order',
            'track' => 'Track', 'status' => 'Status', 'sortBy' => 'Sort by', 'filter' => 'Filter', 'all' => 'All',
            'newest' => 'Newest', 'priceAsc' => 'Price: Low to High', 'priceDesc' => 'Price: High to Low',
            'category' => 'Category', 'availability' => 'Availability', 'results' => 'pieces', 'noResults' => 'No results',
            'newsletter' => 'Join the Al Kawkaba world', 'newsletterText' => 'Be the first to discover new collections and exclusive releases.',
            'subscribe' => 'Subscribe', 'subscribed' => 'Thank you for subscribing', 'customerService' => 'Client Services',
            'shippingPolicy' => 'Shipping Policy', 'returns' => 'Exchanges & Returns', 'privacy' => 'Privacy Policy',
            'terms' => 'Terms & Conditions', 'contact' => 'Contact Us', 'followUs' => 'Follow Us', 'rights' => 'All rights reserved',
            'menu' => 'Menu', 'close' => 'Close', 'searchPlaceholder' => 'Search abayas...', 'guest' => 'or continue as guest',
            'haveAccount' => 'Already have an account?', 'noAccount' => 'New here?', 'invalidLogin' => 'Invalid email or password',
            'emailTaken' => 'Email already registered', 'required' => 'Required', 'invalidCode' => 'Invalid code',
            'selectSize' => 'Please select a size', 'added' => 'Added to bag', 'free' => 'Free',
            'emptyWishlist' => 'Your wishlist is empty', 'orderNotFound' => 'Order not found',
            'was' => 'Was', 'prev' => 'Previous', 'next' => 'Next',
        ],
    ];
    return $d[$l === 'en' ? 'en' : 'ar'];
}

/** Order / shipping status labels shown to customers. */
function status_label(string $s, string $l): string
{
    $map = [
        'pending' => ['قيد المراجعة', 'Pending'], 'confirmed' => ['مؤكد', 'Confirmed'], 'processing' => ['قيد التجهيز', 'Being prepared'],
        'cancelled' => ['ملغي', 'Cancelled'], 'completed' => ['مكتمل', 'Completed'],
        'unfulfilled' => ['لم يُشحن بعد', 'Not shipped yet'], 'shipped' => ['تم الشحن', 'Shipped'], 'delivered' => ['تم التوصيل', 'Delivered'], 'returned' => ['مُرتجع', 'Returned'],
    ];
    return $map[$s][$l === 'ar' ? 0 : 1] ?? $s;
}

function region_name(string $code, string $l): string
{
    if (class_exists('Locale')) {
        $n = Locale::getDisplayRegion('-' . $code, $l);
        if ($n && $n !== $code) return $n;
    }
    return $code;
}
