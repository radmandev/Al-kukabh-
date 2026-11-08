<?php
// Payment methods offered at checkout, driven by settings()['payments'].
// Card gateway = one adapter slot. When a provider is chosen (Tap, Moyasar, Stripe, HyperPay...),
// implement create_checkout() for it and return its hosted-payment URL; the order is already saved as "pending".

function payment_methods(array $s): array
{
    $m = [];
    if (($s['payments']['gateway'] ?? 'none') !== 'none') $m[] = ['id' => 'card', 'labelAr' => 'بطاقة ائتمان / مدى / Apple Pay', 'labelEn' => 'Card / Apple Pay'];
    if (!empty($s['payments']['cod'])) $m[] = ['id' => 'cod', 'labelAr' => 'الدفع عند الاستلام', 'labelEn' => 'Cash on delivery'];
    if (!empty($s['payments']['bank'])) $m[] = ['id' => 'bank', 'labelAr' => 'تحويل بنكي', 'labelEn' => 'Bank transfer'];
    return $m;
}

/** Return the provider's hosted checkout URL, or null to fall through to the order page. */
function create_checkout(array $order, array $s): ?string
{
    if (($s['payments']['gateway'] ?? 'none') === 'none') return null;
    throw new RuntimeException('Payment gateway "' . $s['payments']['gateway'] . '" is not connected yet');
}

function shipping_cost(float $subtotal, array $s): float
{
    $free = (float) $s['shipping']['freeOver'];
    return $free > 0 && $subtotal >= $free ? 0.0 : (float) $s['shipping']['flat'];
}
