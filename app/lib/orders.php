<?php
/**
 * ['amount' => 0, 'code' => null] for no code, the applied discount for a valid code,
 * or null when the code is invalid / expired / used up / below the minimum.
 */
function compute_discount(?string $code, float $subtotal): ?array
{
    $code = strtoupper(trim((string) $code));
    if ($code === '') return ['amount' => 0.0, 'code' => null];
    $d = row('SELECT * FROM discounts WHERE code = ?', [$code]);
    $valid = $d && $d['active'] && $subtotal >= (float) $d['minTotal']
        && (!$d['expiresAt'] || strtotime($d['expiresAt']) > time())
        && ($d['maxUses'] === null || (int) $d['used'] < (int) $d['maxUses']);
    if (!$valid) return null;
    $amount = $d['type'] === 'percent' ? $subtotal * (float) $d['value'] / 100 : (float) $d['value'];
    return ['amount' => min(round($amount, 2), $subtotal), 'code' => $d['code']];
}

function order_number(): string
{
    $ms = (int) floor(microtime(true) * 1000);
    return 'KW' . substr(strtoupper(base_convert((string) $ms, 10, 36)), -6) . random_int(10, 99);
}
