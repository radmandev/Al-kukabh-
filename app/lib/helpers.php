<?php
// Small shared helpers: escaping, JSON, formatting, requests/responses, CSRF, images.

/** HTML-escape for text and attribute values. */
function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** JSON for embedding in an HTML attribute (already escaped). */
function attr_json(mixed $v): string
{
    return e(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function jdecode(?string $s, mixed $fallback = []): mixed
{
    if ($s === null || $s === '') return $fallback;
    $v = json_decode($s, true);
    return $v === null && json_last_error() !== JSON_ERROR_NONE ? $fallback : $v;
}

function jencode(mixed $v): string
{
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function new_id(): string
{
    return 'c' . bin2hex(random_bytes(12));
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Same output as Intl.NumberFormat(l === "ar" ? "ar-SA-u-nu-latn" : "en", { style: "currency" }) */
function money(mixed $v, string $currency, string $l): string
{
    $v = (float) $v;
    $frac = fmod($v, 1.0) != 0.0 ? 2 : 0;
    if (class_exists('NumberFormatter')) {
        static $cache = [];
        $key = "$l|$frac";
        if (!isset($cache[$key])) {
            $f = new NumberFormatter($l === 'ar' ? 'ar_SA@numbers=latn' : 'en', NumberFormatter::CURRENCY);
            $f->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $frac);
            $cache[$key] = $f;
        }
        $out = $cache[$key]->formatCurrency($v, $currency);
        if ($out !== false) return $out;
    }
    return $currency . ' ' . number_format($v, $frac);
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    return trim((string) $s, '-');
}

/** Arabic-Indic digits → ASCII (people type passwords on Arabic keyboards). */
function ascii_digits(string $s): string
{
    return strtr($s, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
}

/** Customer-facing short date in the visitor's language. */
function local_date(string $dt, string $l): string
{
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter($l === 'ar' ? 'ar' : 'en_US', IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        $f->setPattern($l === 'ar' ? 'd‏/M‏/y' : 'M/d/y');
        $out = $f->format(new DateTime($dt));
        if ($out !== false) return $out;
    }
    return date('d/m/Y', strtotime($dt));
}

/** Admin date formats (en-GB style). */
function admin_date(?string $dt): string { return $dt ? date('d/m/Y', strtotime($dt)) : '—'; }
function admin_datetime(string $dt): string { return date('j M Y, H:i', strtotime($dt)); }
function admin_datetime_full(string $dt): string { return date('d/m/Y, H:i:s', strtotime($dt)); }

/* ---------- request / response ---------- */

function request_path(): string
{
    $p = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    return $p === '/' ? '/' : rtrim($p, '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $_GET[$k] ?? $default;
    return is_string($v) ? $v : $default;
}

function query(string $k): ?string
{
    $v = $_GET[$k] ?? null;
    return is_string($v) && $v !== '' ? $v : null;
}

/** Decoded JSON request body (fetch() calls from the browser). */
function json_body(): array
{
    static $body;
    return $body ??= (array) (json_decode((string) file_get_contents('php://input'), true) ?: []);
}

function redirect(string $url, int $code = 303): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function set_cookie(string $name, string $value, int $maxAge, bool $httpOnly = true): void
{
    setcookie($name, $value, [
        'expires' => $maxAge > 0 ? time() + $maxAge : time() - 3600,
        'path' => '/', 'httponly' => $httpOnly, 'samesite' => 'Lax', 'secure' => is_https(),
    ]);
    if ($maxAge > 0) $_COOKIE[$name] = $value; else unset($_COOKIE[$name]);
}

/** Only allow same-site relative redirects ("/ar/checkout", never "//evil.com"). */
function safe_next(?string $next): ?string
{
    return $next && str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\') ? $next : null;
}

/* ---------- CSRF (double-submit cookie) ---------- */

function csrf_token(): string
{
    $t = $_COOKIE['kw_csrf'] ?? '';
    if (!preg_match('/^[a-f0-9]{40}$/', $t)) {
        $t = bin2hex(random_bytes(20));
        set_cookie('kw_csrf', $t, 60 * 60 * 24 * 30);
    }
    return $t;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Rejects POSTs without a matching token (forms send _csrf, fetch() sends X-CSRF). */
function csrf_check(): void
{
    $cookie = $_COOKIE['kw_csrf'] ?? '';
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    if ($cookie === '' || !is_string($sent) || !hash_equals($cookie, $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------- views ---------- */

/** Render app/views/{name}.php with $vars in scope and return the HTML. */
function view(string $__view, array $__vars = []): string
{
    // the parameters have odd names so page variables like $name or $vars are never shadowed
    extract($__vars);
    ob_start();
    require APP . "/views/$__view.php";
    return (string) ob_get_clean();
}

/* ---------- images ---------- */

const IMG_WIDTHS = [128, 256, 384, 640, 828, 1080, 1440, 1920, 2560];

/** Local raster images get a resized URL (/img/{w}/...); anything else is returned unchanged. */
function img_url(string $url, int $w): string
{
    if (!preg_match('#^/(media|brand)/[^?]+\.(jpe?g|png|webp|avif|gif)$#i', $url)) return $url;
    // encode each path segment: brand files have spaces, and srcset splits on whitespace
    return '/img/' . $w . implode('/', array_map(fn ($s) => rawurlencode(rawurldecode($s)), explode('/', $url)));
}

function img_srcset(string $url, int $max = 2560): string
{
    if (img_url($url, 640) === $url) return '';
    $out = [];
    foreach (IMG_WIDTHS as $w) if ($w <= $max) $out[] = e(img_url($url, $w)) . " {$w}w";
    return implode(', ', $out);
}

/**
 * Responsive <img>. $fill = absolutely positioned to cover its `relative` parent (like next/image fill).
 */
function img(string $url, string $alt = '', string $class = '', string $sizes = '100vw', bool $priority = false, bool $fill = true, string $style = ''): string
{
    $srcset = img_srcset($url);
    $cls = trim(($fill ? 'absolute inset-0 h-full w-full ' : '') . $class);
    return '<img src="' . e(img_url($url, 1080)) . '"' . ($srcset ? ' srcset="' . $srcset . '" sizes="' . e($sizes) . '"' : '')
        . ' alt="' . e($alt) . '" class="' . e($cls) . '"' . ($style ? ' style="' . e($style) . '"' : '')
        . ($priority ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
}

/** Fixed-size <img> (logos, thumbnails) with a 2x source. */
function img_fixed(string $url, int $w, string $alt = '', string $class = ''): string
{
    $w1 = 128; $w2 = 128;
    foreach (IMG_WIDTHS as $x) { if ($x >= $w) { $w1 = $x; break; } }
    foreach (IMG_WIDTHS as $x) { if ($x >= $w * 2) { $w2 = $x; break; } }
    $src = img_url($url, $w1);
    $set = $src === $url ? '' : ' srcset="' . e($src) . ' 1x, ' . e(img_url($url, $w2)) . ' 2x"';
    return '<img src="' . e($src) . '"' . $set . ' alt="' . e($alt) . '" width="' . $w . '" height="' . $w . '" class="' . e($class) . '" decoding="async">';
}
