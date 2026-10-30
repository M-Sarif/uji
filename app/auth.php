<?php

const TOKEN_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
const TOKEN_MIN_LENGTH = 6;
const TOKEN_MAX_LENGTH = 32;
const TOKEN_LENGTH   = 16;
const AUTH_ROLES     = ['amt', 'spbu'];

function token_pepper(): string
{
    static $pepper = null;
    if ($pepper !== null) {
        return $pepper;
    }
    $file = APP_DIR . '/secret.key';
    if (is_file($file)) {
        $pepper = trim((string) file_get_contents($file));
    }
    if (!is_string($pepper) || strlen($pepper) < 32) {
        $pepper = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $pepper, LOCK_EX) === false) {
            throw new RuntimeException('app/secret.key tidak bisa dibuat (periksa izin folder app).');
        }
        @chmod($file, 0600);
    }
    return $pepper;
}

function token_normalize(string $raw): string
{
    return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($raw));
}

function token_generate(): string
{
    $out = '';
    $max = strlen(TOKEN_ALPHABET) - 1;
    for ($i = 0; $i < TOKEN_LENGTH; $i++) {
        $out .= TOKEN_ALPHABET[random_int(0, $max)];
    }
    return $out;
}

function token_format(string $normalized): string
{
    return strlen($normalized) === TOKEN_LENGTH ? implode('-', str_split($normalized, 4)) : $normalized;
}

function token_enc_key(): string
{
    return hash_hmac('sha256', 'onefis-token-copy-v1', token_pepper(), true);
}

function token_encrypt(string $plain): ?string
{
    if (!function_exists('openssl_encrypt')) {
        return null;
    }
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($plain, 'aes-256-gcm', token_enc_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return $ct === false ? null : base64_encode($iv . $tag . $ct);
}

function token_decrypt(string $blob): ?string
{
    $raw = base64_decode($blob, true);
    if ($raw === false || strlen($raw) < 29 || !function_exists('openssl_decrypt')) {
        return null;
    }
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', token_enc_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? null : $plain;
}

function token_copy_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    if (!function_exists('openssl_encrypt') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
        return $ready = false;
    }
    try {
        $pdo->query('SELECT token_enc FROM access_tokens LIMIT 1')->fetchAll();
        return $ready = true;
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec('ALTER TABLE access_tokens ADD COLUMN token_enc VARCHAR(255) NULL');
        return $ready = true;
    } catch (Throwable $e) {
        error_log('Kolom token_enc tidak bisa dibuat: ' . $e->getMessage());
        return $ready = false;
    }
}

function token_hash(string $normalized): string
{
    return hash_hmac('sha256', $normalized, token_pepper());
}

function auth_try_token(string $role, string $rawToken): ?int
{
    if (!in_array($role, AUTH_ROLES, true)) {
        return null;
    }
    $normalized = token_normalize($rawToken);
    if (strlen($normalized) < TOKEN_MIN_LENGTH || strlen($normalized) > TOKEN_MAX_LENGTH) {
        return null;
    }
    $hash = token_hash($normalized);
    $now  = date('Y-m-d H:i:s');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $up = $pdo->prepare(
            'UPDATE access_tokens
                SET used_count = used_count + 1, last_used_at = :used_at
              WHERE token_hash = :hash
                AND role = :role
                AND is_active = 1
                AND (expires_at IS NULL OR expires_at > :now)
                AND (max_uses = 0 OR used_count < max_uses)'
        );
        $up->execute([':used_at' => $now, ':hash' => $hash, ':role' => $role, ':now' => $now]);

        $id = null;
        if ($up->rowCount() === 1) {
            $sel = $pdo->prepare('SELECT id FROM access_tokens WHERE token_hash = :hash');
            $sel->execute([':hash' => $hash]);
            $found = $sel->fetchColumn();
            $id = $found !== false ? (int) $found : null;
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function auth_start_session(int $tokenId, string $role): void
{
    session_regenerate_id(true);
    $_SESSION['auth']  = ['token_id' => $tokenId, 'role' => $role, 'at' => time()];
    $_SESSION['role']  = $role;
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['pending_role']);
}

function auth_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function auth_role(): ?string
{
    $a = $_SESSION['auth'] ?? null;
    return (is_array($a) && isset($a['role']) && in_array($a['role'], AUTH_ROLES, true)) ? $a['role'] : null;
}

function auth_enforce(): void
{
    $auth = $_SESSION['auth'] ?? null;

    if (!is_array($auth)) {
        $_SESSION['role'] = null;
        return;
    }

    $valid = isset($auth['token_id'], $auth['role'])
        && in_array($auth['role'], AUTH_ROLES, true)
        && ($_SESSION['role'] ?? null) === $auth['role'];

    if ($valid) {
        $st = db()->prepare('SELECT role, is_active, expires_at FROM access_tokens WHERE id = :id');
        $st->execute([':id' => (int) $auth['token_id']]);
        $row = $st->fetch();
        $valid = $row !== false
            && $row['role'] === $auth['role']
            && (int) $row['is_active'] === 1
            && ($row['expires_at'] === null || strtotime((string) $row['expires_at']) > time());
    }

    if (!$valid) {
        auth_logout();
        $_SESSION['role']        = null;
        $_SESSION['auth_notice'] = 'Sesi Anda berakhir karena token tidak berlaku lagi. Masukkan token baru.';
    }
}