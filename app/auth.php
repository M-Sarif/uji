<?php
/**
 * OneFIS - autentikasi pengguna lewat TOKEN (peran AMT / SPBU).
 *
 *   - Token dibuat admin (admin/index.php) dan terikat ke SATU peran.
 *   - Di database hanya tersimpan HMAC-SHA256 dari token (bukan token aslinya), jadi bocornya
 *     isi database tidak membocorkan token yang masih berlaku.
 *   - Token asli hanya tampil sekali saat dibuat.
 *   - Tiap permintaan, sesi dicek ulang ke database: token yang dinonaktifkan / dihapus /
 *     kedaluwarsa langsung mengeluarkan penggunanya.
 *
 * Letak: app/auth.php
 */

const TOKEN_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // 32 karakter, tanpa I O 0 1 yang membingungkan
const TOKEN_LENGTH   = 16;                                    // 32^16 = 80 bit
const AUTH_ROLES     = ['amt', 'spbu'];

/** Kunci rahasia (pepper) untuk hash token. Dibuat otomatis sekali di app/secret.key. */
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

/** Token mentah (diketik pengguna, boleh huruf kecil / tanda hubung / spasi) -> bentuk baku. */
function token_normalize(string $raw): string
{
    return (string) preg_replace('/[^' . TOKEN_ALPHABET . ']/', '', strtoupper($raw));
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

/** "ABCD1234EFGH5678" -> "ABCD-1234-EFGH-5678" */
function token_format(string $normalized): string
{
    return implode('-', str_split($normalized, 4));
}

function token_hash(string $normalized): string
{
    return hash_hmac('sha256', $normalized, token_pepper());
}

/**
 * Coba masuk dengan token untuk peran tertentu.
 * Satu pernyataan UPDATE atomik sekaligus memeriksa: token cocok, peran cocok, aktif, belum
 * kedaluwarsa, dan kuota pemakaian belum habis -> tidak ada celah balapan antar permintaan.
 *
 * @return int|null id token bila berhasil, null bila ditolak (alasan sengaja tidak dibedakan)
 */
function auth_try_token(string $role, string $rawToken): ?int
{
    if (!in_array($role, AUTH_ROLES, true)) {
        return null;
    }
    $normalized = token_normalize($rawToken);
    if (strlen($normalized) !== TOKEN_LENGTH) {
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

/** Tandai sesi sebagai sudah login (token valid). Id sesi diganti untuk mencegah session fixation. */
function auth_start_session(int $tokenId, string $role): void
{
    session_regenerate_id(true);
    $_SESSION['auth']  = ['token_id' => $tokenId, 'role' => $role, 'at' => time()];
    $_SESSION['role']  = $role;
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['pending_role']);
}

/** Keluar: seluruh data sesi (state AMT/SPBU juga) dihapus. */
function auth_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Peran yang sudah terbukti lewat token (null = belum login). */
function auth_role(): ?string
{
    $a = $_SESSION['auth'] ?? null;
    return (is_array($a) && isset($a['role']) && in_array($a['role'], AUTH_ROLES, true)) ? $a['role'] : null;
}

/**
 * Dipanggil index.php di setiap permintaan, sebelum peran dipakai.
 *  1. Peran di sesi tanpa bukti token -> dibuang (tidak ada jalan masuk selain lewat token).
 *  2. Token di sesi dicek ulang ke database (masih aktif? belum kedaluwarsa? perannya sama?).
 */
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