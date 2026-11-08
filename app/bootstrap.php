<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);

if (!is_file(ROOT . '/config.php')) {
    http_response_code(500);
    exit('Missing config.php — copy config.example.php to config.php and fill it in.');
}
$GLOBALS['__config'] = require ROOT . '/config.php';

/** config('db.host') — dot-path lookup into config.php */
function config(string $key, mixed $default = null): mixed
{
    $v = $GLOBALS['__config'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
date_default_timezone_set((string) config('timezone', 'UTC'));
mb_internal_encoding('UTF-8');

require APP . '/lib/helpers.php';
require APP . '/lib/db.php';
require APP . '/lib/i18n.php';
require APP . '/lib/auth.php';
require APP . '/lib/settings.php';
require APP . '/lib/catalog.php';
require APP . '/lib/orders.php';
require APP . '/lib/payments.php';
require APP . '/lib/sections.php';
require APP . '/lib/storage.php';
require APP . '/lib/components.php';
