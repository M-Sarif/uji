<?php
/**
 * OneFIS - autentikasi & kerangka halaman ADMIN.
 * Dipanggil oleh admin/*.php:   require __DIR__ . '/../app/admin_auth.php';  admin_boot();
 *
 * Letak: app/admin_auth.php
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/** Siapkan error handler, header, dan sesi khusus admin (cookie terpisah dari sesi pengguna). */
function admin_boot(): void
{
    app_boot_errors();
    app_send_headers(true);
    app_session_start('ONEFIS_ADMIN', app_cookie_path(0), 'Strict', (int) app_config('admin_idle_timeout'));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        app_require_same_origin();
    }
}

function admin_redirect(string $file, array $params = []): void
{
    header('Location: ' . $file . ($params ? '?' . http_build_query($params) : ''));
    exit;
}

function admin_exists(): bool
{
    return (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
}

/** Admin yang sedang login (dicek ulang ke database), atau null. */
function admin_current(): ?array
{
    $id = $_SESSION['admin_id'] ?? null;
    if (!is_int($id)) {
        return null;
    }
    $st = db()->prepare('SELECT id, username FROM admins WHERE id = :id');
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if ($row === false) {
        unset($_SESSION['admin_id']);
        return null;
    }
    return ['id' => (int) $row['id'], 'username' => (string) $row['username']];
}

/** Wajib login admin; bila belum -> ke login.php (atau setup.php bila belum ada admin sama sekali). */
function admin_require(): array
{
    $admin = admin_current();
    if ($admin === null) {
        admin_redirect(admin_exists() ? 'login.php' : 'setup.php');
    }
    return $admin;
}

/** Cek username + password. Mengembalikan id admin, atau null bila salah. */
function admin_check_password(string $username, string $password): ?int
{
    $st = db()->prepare('SELECT id, password_hash FROM admins WHERE username = :u');
    $st->execute([':u' => $username]);
    $row = $st->fetch();

    // password_verify SELALU dijalankan (juga saat username tidak ada) supaya waktu respons tidak membocorkan
    // apakah username tersebut terdaftar.
    $hash = $row !== false ? (string) $row['password_hash'] : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $ok   = password_verify($password, $hash) && $row !== false;
    if (!$ok) {
        return null;
    }
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admins SET password_hash = :h WHERE id = :id')
            ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => (int) $row['id']]);
    }
    return (int) $row['id'];
}

function admin_start_session(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['_csrf']    = bin2hex(random_bytes(32));
    db()->prepare('UPDATE admins SET last_login_at = :t WHERE id = :id')
        ->execute([':t' => date('Y-m-d H:i:s'), ':id' => $adminId]);
}

/* ------------------------------------------------------------
 * Kerangka halaman
 * ------------------------------------------------------------ */

function admin_flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function admin_header(string $title, ?array $admin = null, string $active = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo e($title); ?> - OneFIS Admin</title>
<link rel="stylesheet" href="assets/admin.css?v=<?php echo (int) @filemtime(__DIR__ . '/../admin/assets/admin.css'); ?>">
<script src="assets/admin.js?v=<?php echo (int) @filemtime(__DIR__ . '/../admin/assets/admin.js'); ?>" defer></script>
</head>
<body>
<?php if ($admin !== null): ?>
<header class="topbar">
    <div class="topbar-in">
        <span class="brand">OneFIS <small>Admin</small></span>
        <nav class="nav">
            <a href="index.php"<?php echo $active === 'tokens' ? ' class="is-on"' : ''; ?>>Token</a>
            <a href="logs.php"<?php echo $active === 'logs' ? ' class="is-on"' : ''; ?>>Riwayat Login</a>
        </nav>
        <form method="post" action="logout.php" class="logout">
            <?php echo csrf_field(); ?>
            <span class="who"><?php echo e($admin['username']); ?></span>
            <button type="submit" class="btn btn-ghost">Keluar</button>
        </form>
    </div>
</header>
<?php endif; ?>
<main class="wrap">
<?php
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (is_array($flash)) {
        echo '<div class="alert alert-' . ($flash['type'] === 'ok' ? 'ok' : 'err') . '" role="status">' . e($flash['msg']) . '</div>';
    }
}

function admin_footer(): void
{
    echo "</main>\n</body>\n</html>";
}