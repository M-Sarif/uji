<?php
/**
 * OneFIS - keamanan dasar (dipakai index.php dan semua halaman admin).
 *
 *   - penanganan error: pesan teknis TIDAK ditampilkan ke browser, hanya dicatat di app/logs/
 *   - header keamanan HTTP
 *   - sesi aman (HttpOnly, SameSite, mode strict, batas diam)
 *   - proteksi CSRF (token + pemeriksaan asal permintaan)
 *   - helper escape output, alamat IP klien, pembatas percobaan login
 *
 * Letak: app/security.php
 */

if (!defined('APP_DIR')) {
    define('APP_DIR', __DIR__);
}

// Zona waktu yang sama dengan aplikasi (core/bootstrap.php) supaya masa berlaku token konsisten.
if (date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('Asia/Jakarta');
}

/* ------------------------------------------------------------
 * Konfigurasi
 * ------------------------------------------------------------ */

/** Baca app/config.php. $key = 'db' / 'user_idle_timeout' / dst. (null = seluruh array) */
function app_config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $file = APP_DIR . '/config.php';
        if (!is_file($file)) {
            app_fail(500, 'Aplikasi belum dikonfigurasi. Salin app/config.example.php menjadi app/config.php lalu isi pengaturannya.');
        }
        $cfg = require $file;
        if (!is_array($cfg)) {
            app_fail(500, 'File app/config.php tidak valid.');
        }
    }
    return $key === null ? $cfg : ($cfg[$key] ?? null);
}

/* ------------------------------------------------------------
 * Error: jangan bocorkan detail (jalur file, nama user DB, dsb.) ke pengunjung
 * ------------------------------------------------------------ */

function app_boot_errors(): void
{
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('html_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');

    $dir = APP_DIR . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    ini_set('error_log', $dir . '/php-error.log');

    set_exception_handler(function (Throwable $e): void {
        // Hanya pesan + lokasi; stack trace sengaja tidak dicatat (bisa memuat argumen sensitif).
        error_log(get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        app_fail(500);
    });
}

/** Hentikan permintaan dengan halaman error sederhana (tanpa detail teknis). */
function app_fail(int $code = 500, ?string $message = null): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    $text = $message ?? ($code === 403 ? 'Permintaan ditolak.' : 'Terjadi kesalahan pada server. Silakan coba lagi nanti.');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>OneFIS</title></head><body style="font-family:system-ui,sans-serif;background:#f8fafc;color:#1e293b;'
       . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:1.5rem">'
       . '<div style="max-width:26rem;text-align:center"><h1 style="font-size:1.25rem;margin:0 0 .5rem">OneFIS</h1>'
       . '<p style="margin:0;color:#475569;line-height:1.5">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
    exit;
}

/* ------------------------------------------------------------
 * Header keamanan HTTP
 * ------------------------------------------------------------ */

function app_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/**
 * @param bool $strict true = halaman admin (tanpa skrip/gaya inline, tidak boleh di-iframe).
 *                     false = aplikasi AMT/SPBU (memakai skrip inline, jadi CSP lebih longgar).
 */
function app_send_headers(bool $strict = false): void
{
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cross-Origin-Opener-Policy: same-origin');
    // Aplikasi memakai kamera (foto verifikasi) dan lokasi (Check-In), jadi diizinkan untuk situs sendiri saja.
    header('Permissions-Policy: camera=(self), geolocation=(self), microphone=(), payment=(), usb=()');
    header('Cache-Control: no-store');   // halaman berisi data sesi: jangan disimpan cache/riwayat bersama
    if (app_is_https()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    if ($strict) {
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
             . "object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    } else {
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    }
}

/* ------------------------------------------------------------
 * Sesi
 * ------------------------------------------------------------ */

/** Path cookie = folder skrip yang berjalan (naik $levelsUp tingkat), mis. "/onefis" atau "/". */
function app_cookie_path(int $levelsUp = 0): string
{
    $path = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $path = dirname($path);
    for ($i = 0; $i < $levelsUp; $i++) {
        $path = dirname($path);
    }
    $path = str_replace('\\', '/', $path);
    return ($path === '' || $path === '.') ? '/' : $path;
}

function app_session_start(string $name, string $path, string $sameSite, int $idleSeconds): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');      // id sesi buatan penyerang ditolak
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) max($idleSeconds, 1440));

    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $path,
        'secure'   => app_is_https(),
        'httponly' => true,
        'samesite' => $sameSite,
    ]);
    session_start();

    $now = time();
    if (isset($_SESSION['_last_seen']) && ($now - (int) $_SESSION['_last_seen']) > $idleSeconds) {
        $_SESSION = [];                 // terlalu lama diam -> sesi dimulai dari awal
        session_regenerate_id(true);
    }
    $_SESSION['_last_seen'] = $now;
}

/* ------------------------------------------------------------
 * Output & input
 * ------------------------------------------------------------ */

/** Escape output HTML (anti XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Alamat IP klien. Hanya REMOTE_ADDR yang dipercaya (X-Forwarded-For mudah dipalsukan). */
function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** Potong string maksimal $maxLen karakter (UTF-8 aman; tetap jalan walau ekstensi mbstring mati). */
function str_cut(string $value, int $maxLen): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
    return (string) (preg_match('/^.{0,' . $maxLen . '}/us', $value, $m) ? $m[0] : substr($value, 0, $maxLen));
}

/** Ambil nilai POST berupa string (bukan array), dipotong spasi, dibatasi panjangnya. */
function post_str(string $key, int $maxLen = 255): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? str_cut(trim($v), $maxLen) : '';
}

/* ------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $sent);
}

/**
 * Semua permintaan POST harus berasal dari situs ini sendiri (Origin / Sec-Fetch-Site / Referer).
 * Dipakai di index.php supaya seluruh form AMT & SPBU yang sudah ada ikut terlindungi
 * dari CSRF tanpa perlu mengubah satu per satu.
 */
function app_require_same_origin(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    $hostOf = static function (string $url): string {
        $p = parse_url($url);
        if (!is_array($p) || empty($p['host'])) {
            return '';
        }
        return strtolower($p['host']) . (isset($p['port']) ? ':' . $p['port'] : '');
    };

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '' && $origin !== 'null') {
        if ($hostOf($origin) === $host) {
            return;
        }
        app_fail(403);
    }
    $site = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    if ($site !== '') {
        if ($site === 'same-origin' || $site === 'none') {
            return;
        }
        app_fail(403);
    }
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '' && $hostOf($referer) === $host) {
        return;
    }
    app_fail(403);
}

/* ------------------------------------------------------------
 * Pembatas percobaan login (anti brute force)
 * ------------------------------------------------------------ */

/** Sudah terlalu banyak percobaan gagal dari IP ini? $scope = 'user' (token) atau 'admin' (password). */
function login_is_locked(string $scope): bool
{
    $since = date('Y-m-d H:i:s', time() - ((int) app_config('lockout_minutes')) * 60);
    $st = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE scope = :scope AND ip = :ip AND success = 0 AND attempted_at >= :since'
    );
    $st->execute([':scope' => $scope, ':ip' => client_ip(), ':since' => $since]);
    return (int) $st->fetchColumn() >= (int) app_config('max_failed_attempts');
}

function login_record(string $scope, bool $success, ?string $role = null, ?int $tokenId = null, ?string $username = null): void
{
    $pdo = db();
    $st  = $pdo->prepare(
        'INSERT INTO login_attempts (scope, ip, success, role, token_id, username, attempted_at)
         VALUES (:scope, :ip, :ok, :role, :tid, :uname, :at)'
    );
    $st->execute([
        ':scope' => $scope,
        ':ip'    => client_ip(),
        ':ok'    => $success ? 1 : 0,
        ':role'  => $role,
        ':tid'   => $tokenId,
        ':uname' => $username !== null ? substr($username, 0, 50) : null,
        ':at'    => date('Y-m-d H:i:s'),
    ]);

    if ($success) {   // login berhasil -> hitungan gagal IP ini dimulai dari nol
        $pdo->prepare('DELETE FROM login_attempts WHERE scope = :scope AND ip = :ip AND success = 0')
            ->execute([':scope' => $scope, ':ip' => client_ip()]);
    }
    if (random_int(1, 100) === 1) {   // bersihkan catatan lama (> 60 hari) sesekali
        $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :old')
            ->execute([':old' => date('Y-m-d H:i:s', time() - 60 * 86400)]);
    }
}