<?php
// Login sessions = HMAC-signed cookie (no server session files; survives shared-host session GC).

const SESSION_COOKIE = 'kw_session';
const SESSION_TTL = 60 * 60 * 24 * 30; // 30 days
const STAFF = ['ADMIN', 'EDITOR'];

function auth_secret(): string
{
    $s = (string) config('secret', '');
    if (strlen($s) < 32) {
        http_response_code(500);
        exit("config.php: 'secret' must be set to a random string of 32+ characters.");
    }
    return $s;
}

function b64url(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64url_decode(string $s): string { return (string) base64_decode(strtr($s, '-_', '+/')); }

function hash_pw(string $p): string { return password_hash($p, PASSWORD_BCRYPT, ['cost' => 12]); }
function check_pw(string $p, string $hash): bool { return password_verify(ascii_digits($p), $hash); }

function start_session(string $userId): void
{
    $payload = b64url(jencode(['sub' => $userId, 'exp' => time() + SESSION_TTL]));
    $sig = b64url(hash_hmac('sha256', $payload, auth_secret(), true));
    set_cookie(SESSION_COOKIE, "$payload.$sig", SESSION_TTL);
}

function end_session(): void
{
    set_cookie(SESSION_COOKIE, '', -1);
}

/** The signed-in user (id, email, name, phone, role) or null. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) return $user;
    $user = null;
    $tok = $_COOKIE[SESSION_COOKIE] ?? '';
    if (!is_string($tok) || substr_count($tok, '.') !== 1) return null;
    [$payload, $sig] = explode('.', $tok);
    if (!hash_equals(b64url(hash_hmac('sha256', $payload, auth_secret(), true)), $sig)) return null;
    $d = json_decode(b64url_decode($payload), true);
    if (!is_array($d) || ($d['exp'] ?? 0) < time()) return null;
    return $user = row('SELECT id, email, name, phone, role FROM users WHERE id = ?', [(string) $d['sub']]);
}

/** Gate for admin pages & actions. ADMIN-only areas pass ['ADMIN']. */
function require_staff(array $roles = STAFF): array
{
    $u = current_user();
    if (!$u || !in_array($u['role'], $roles, true)) {
        if (str_starts_with(request_path(), '/admin/api') || !empty($_SERVER['HTTP_X_CSRF'])) json_out(['error' => 'Unauthorized'], 401);
        redirect('/admin/login');
    }
    return $u;
}
