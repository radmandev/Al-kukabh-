<?php
// Copy this file and fill it in:
// - on the server: save it as "alkawkaba-config.php" in the folder ABOVE the project
//   (e.g. next to public_html). Deployments never touch that folder, so it is never deleted,
//   and the default upload_dir below then keeps uploaded images there too.
// - locally: config.php inside the project is fine (it is git-ignored).
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

    // Where uploaded images & videos are stored: next to this config file. Keep it out of the
    // deployed project folder, or a deployment can wipe every uploaded photo.
    'upload_dir' => __DIR__ . '/uploads',

    'timezone' => 'Asia/Riyadh',

    // true while developing: shows PHP errors in the browser. Set false in production.
    'debug' => false,

    // Used by `php database/seed.php` to create the first admin account.
    'admin_email' => 'admin@alkawkaba.com',
    'admin_password' => 'ChangeMe123!',
];
