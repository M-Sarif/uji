<?php
/**
 * OneFIS - koneksi database (PDO).
 *
 *   - Seluruh akses data memakai PREPARED STATEMENT asli (ATTR_EMULATE_PREPARES = false),
 *     sehingga input pengguna tidak pernah digabung ke teks SQL -> kebal SQL injection.
 *   - Kredensial dibaca dari app/config.php (terkunci dari browser) dan tidak pernah dicetak ke HTML/JS.
 *   - Bila koneksi gagal, pengunjung hanya melihat pesan umum; detail masuk app/logs/ saja.
 *
 * Letak: app/db.php
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c   = app_config('db');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        (string) ($c['host'] ?? '127.0.0.1'),
        (int) ($c['port'] ?? 3306),
        (string) ($c['name'] ?? ''),
        (string) ($c['charset'] ?? 'utf8mb4')
    );

    try {
        $pdo = new PDO($dsn, (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    } catch (PDOException $e) {
        // Jangan teruskan $e: pesannya bisa memuat nama user / host database.
        error_log('Koneksi database gagal (kode ' . $e->getCode() . ')');
        throw new RuntimeException('Database tidak tersedia');
    }
    return $pdo;
}