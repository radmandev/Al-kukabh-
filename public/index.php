<?php
// Front controller: every request that isn't a real file in public/ ends up here.

// `php -S` dev server: let it serve real files (CSS, JS, brand images) directly
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($file) && !str_ends_with($file, '.php')) return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/controllers/store.php';
require APP . '/controllers/admin.php';
require APP . '/lib/migrations.php';
run_migrations(); // one-off data updates (no-op once applied)

$path = request_path();
$method = $_SERVER['REQUEST_METHOD'] === 'HEAD' ? 'GET' : $_SERVER['REQUEST_METHOD'];

try {
    $allowed = false;
    foreach (require APP . '/routes.php' as [$verb, $pattern, $handler]) {
        if (!preg_match($pattern, $path, $m)) continue;
        $allowed = true;
        if ($verb !== $method) continue;
        if ($method === 'POST') csrf_check(); // forms send _csrf, fetch()/XHR send an X-CSRF header
        $handler(...array_slice($m, 1));
        exit;
    }
    if ($allowed) {
        http_response_code(405);
        exit('Method not allowed');
    }
    // un-prefixed storefront paths → /ar/... or /en/...
    if (!preg_match('#^/(ar|en|admin|media|img|assets|brand)(/|$)#', $path) && !preg_match('#^/(favicon|robots|sitemap)#', $path)) {
        store_redirect_locale($path);
    }
    if (preg_match('#^/(ar|en)(/|$)#', $path, $m)) store_not_found($m[1]);
    not_found_plain();
} catch (Throwable $e) {
    error_log((string) $e);
    if (!headers_sent()) http_response_code(500);
    echo config('debug') ? '<pre>' . e((string) $e) . '</pre>' : 'Something went wrong. Please try again.';
}
