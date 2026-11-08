<?php
// Local-disk media storage, served via /media/*. Swap save_upload()/delete_media() for S3/R2 if hosting needs it.

const UPLOAD_MAX = 200 * 1024 * 1024; // 200MB (videos)
const UPLOAD_TYPES = [
    'image/jpeg' => ['jpg', 'image'], 'image/png' => ['png', 'image'], 'image/webp' => ['webp', 'image'],
    'image/avif' => ['avif', 'image'], 'image/gif' => ['gif', 'image'],
    'video/mp4' => ['mp4', 'video'], 'video/webm' => ['webm', 'video'], 'video/quicktime' => ['mov', 'video'],
];
const SERVE_TYPES = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif',
    'svg' => 'image/svg+xml', 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
];

function upload_dir(): string
{
    return rtrim((string) config('upload_dir', ROOT . '/uploads'), '/');
}

function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function upload_error(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large for the server (raise upload_max_filesize / post_max_size)',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted',
        UPLOAD_ERR_NO_FILE => 'No file received',
        default => "Upload failed (code $code)",
    };
}

/** Load a JPEG/PNG/WEBP/AVIF with GD, upright (EXIF) and capped at $maxW. */
function gd_load(string $path, string $mime, int $maxW): ?GdImage
{
    $im = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : false,
        default => false,
    };
    if (!$im) return null;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        if (in_array($o, [3, 4], true)) $im = imagerotate($im, 180, 0);
        if (in_array($o, [5, 6], true)) $im = imagerotate($im, -90, 0);
        if (in_array($o, [7, 8], true)) $im = imagerotate($im, 90, 0);
        if (in_array($o, [2, 4, 5, 7], true)) imageflip($im, IMG_FLIP_HORIZONTAL);
    }
    if (!imageistruecolor($im)) imagepalettetotruecolor($im);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $w = imagesx($im);
    if ($w > $maxW) {
        $h = max(1, (int) round(imagesy($im) * $maxW / $w));
        $out = imagecreatetruecolor($maxW, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $im, 0, 0, 0, 0, $maxW, $h, $w, imagesy($im));
        $im = $out;
    }
    return $im;
}

/**
 * Store one entry of $_FILES. Images are validated, stripped of EXIF/GPS and capped at 2800px;
 * JPG/PNG are converted to WebP. Returns the new media row.
 */
function save_upload(array $f): array
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error((int) $f['error']));
    if ($f['size'] > UPLOAD_MAX) throw new RuntimeException('File too large (max 200MB)');
    return store_media_file($f['tmp_name'], true);
}

/**
 * Process a file into the media library. $uploaded = it came from $_FILES (moved), otherwise it is copied
 * (used by database/seed.php to import public/brand). $extra overrides columns such as altAr / altEn / posterId.
 */
function store_media_file(string $src, bool $uploaded = false, array $extra = []): array
{
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($src);
    $t = UPLOAD_TYPES[$mime] ?? null;
    if (!$t) throw new RuntimeException('Unsupported file type: ' . ($mime ?: 'unknown'));
    [$ext, $kind] = $t;
    $dir = upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Upload folder is not writable');
    $width = $height = null;
    $keep = function (string $dest) use ($src, $uploaded) {
        ($uploaded ? move_uploaded_file($src, $dest) : copy($src, $dest)) || throw new RuntimeException('Could not store file');
    };

    if ($kind === 'image') {
        if (!@getimagesize($src)) throw new RuntimeException('Not a valid image');
        $animatedWebp = $mime === 'image/webp' && str_contains((string) file_get_contents($src, false, null, 0, 4096), 'ANIM');
        $reencode = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && !$animatedWebp && function_exists('imagewebp');
        if ($reencode) {
            @ini_set('memory_limit', '512M');
            $im = gd_load($src, $mime, 2800);
            if (!$im) throw new RuntimeException('Could not read image');
            $ext = 'webp';
            $mime = 'image/webp';
            $name = uuid4() . '.webp';
            imagewebp($im, "$dir/$name", 88);
        } else {
            $name = uuid4() . ".$ext";
            $keep("$dir/$name");
        }
        [$width, $height] = @getimagesize("$dir/$name") ?: [null, null];
    } else {
        $name = uuid4() . ".$ext";
        $keep("$dir/$name");
    }

    $m = [
        'id' => new_id(), 'url' => "/media/$name", 'kind' => $kind, 'mime' => $mime,
        'width' => $width, 'height' => $height, 'size' => (int) filesize("$dir/$name"),
        'altAr' => '', 'altEn' => '', 'posterId' => null, 'createdAt' => now(),
    ];
    $m = array_merge($m, array_intersect_key($extra, array_flip(['altAr', 'altEn', 'posterId'])));
    insert('media', $m);
    return $m;
}

function delete_media(string $id): void
{
    $m = row('SELECT * FROM media WHERE id = ?', [$id]);
    if (!$m) return;
    q('DELETE FROM media WHERE id = ?', [$id]);
    $name = basename($m['url']);
    @unlink(upload_dir() . '/' . $name);
    foreach (glob(upload_dir() . '/.cache/' . md5('media/' . $name) . '-*') ?: [] as $c) @unlink($c);
}

/** Streams a file with HTTP Range support (needed for video seeking on iOS Safari). */
function send_file(string $file, string $type): never
{
    $size = filesize($file);
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');
    if ($type === 'image/svg+xml') header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
    $start = 0;
    $end = $size - 1;
    if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m)) {
        $start = $m[1] !== '' ? (int) $m[1] : $size - (int) $m[2];
        $end = $m[1] !== '' && $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
        if ($start >= $size || $start > $end || $start < 0) {
            http_response_code(416);
            header("Content-Range: bytes */$size");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$size");
    }
    header('Content-Length: ' . ($end - $start + 1));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;
    $fp = fopen($file, 'rb');
    fseek($fp, $start);
    $left = $end - $start + 1;
    while ($left > 0 && !feof($fp)) {
        $chunk = fread($fp, min(1 << 16, $left));
        echo $chunk;
        $left -= strlen($chunk);
        flush();
    }
    fclose($fp);
    exit;
}

/** GET /media/{name} */
function serve_media(string $name): never
{
    $name = basename($name);
    $file = upload_dir() . '/' . $name;
    if ($name === '' || $name[0] === '.' || !is_file($file)) not_found_plain();
    send_file($file, SERVE_TYPES[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? 'application/octet-stream');
}

/** GET /img/{w}/{media|brand}/{path} — resized WebP, generated once and cached in uploads/.cache */
function serve_resized(int $w, string $root, string $path): never
{
    if (!in_array($w, IMG_WIDTHS, true)) not_found_plain();
    $base = $root === 'media' ? upload_dir() : ROOT . '/public/brand';
    $src = realpath($base . '/' . $path);
    if (!$src || !str_starts_with($src, realpath($base) . DIRECTORY_SEPARATOR) || !is_file($src)) not_found_plain();
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    $type = SERVE_TYPES[$ext] ?? '';
    $info = @getimagesize($src);
    // GIFs (may be animated), unknown types and images already small enough are sent as-is
    if (!$info || $ext === 'gif' || !function_exists('imagewebp') || $info[0] <= $w) send_file($src, $type ?: 'application/octet-stream');

    $dir = upload_dir() . '/.cache';
    $cache = $dir . '/' . md5($root . '/' . $path) . "-$w.webp";
    if (!is_file($cache) || filemtime($cache) < filemtime($src)) {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @ini_set('memory_limit', '512M');
        $im = gd_load($src, $info['mime'], $w);
        if (!$im) send_file($src, $type);
        $tmp = $cache . '.' . getmypid();
        imagewebp($im, $tmp, 78);
        rename($tmp, $cache);
    }
    send_file($cache, 'image/webp');
}

function not_found_plain(): never
{
    http_response_code(404);
    header('Content-Type: text/plain');
    exit('Not found');
}
