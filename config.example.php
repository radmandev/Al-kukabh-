<?php
// Copy this file to config.php and fill it in. config.php is git-ignored.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'alkawkaba',
        'user' => 'root',
        'pass' => '',
    ],

    // Long random string (32+ chars) used to sign login cookies. Generate one with:
    //   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    'secret' => '',

    // Public URL of the store, no trailing slash (used for SEO / social links).
    'site_url' => 'http://localhost:8000',

    // Where uploaded images & videos are stored. Keep it outside public/ and on a persistent disk.
    'upload_dir' => __DIR__ . '/uploads',

    'timezone' => 'Asia/Riyadh',

    // true while developing: shows PHP errors in the browser. Set false in production.
    'debug' => false,

    // Used by `php database/seed.php` to create the first admin account.
    'admin_email' => 'admin@alkawkaba.com',
    'admin_password' => 'ChangeMe123!',
];
