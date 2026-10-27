<?php
/**
 * Pembuatan akun admin PERTAMA. Halaman ini hanya bisa dipakai selama tabel admins masih kosong;
 * setelah satu admin dibuat, halaman ini menolak semua permintaan.   Letak: admin/setup.php
 */
require __DIR__ . '/../app/admin_auth.php';
admin_boot();

if (admin_exists()) {
    http_response_code(404);
    admin_header('Tidak ditemukan');
    echo '<section class="card card-narrow"><h1>Setup sudah selesai</h1><p class="muted">Akun admin sudah dibuat. <a href="login.php">Masuk</a></p></section>';
    admin_footer();
    exit;
}

$error = '';
$username = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = post_str('username', 50);
    $pass1 = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $pass2 = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';

    if (!csrf_verify()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username 3-50 karakter: huruf, angka, titik, garis bawah, atau strip.';
    } elseif (strlen($pass1) < 10 || strlen($pass1) > 200) {
        $error = 'Password minimal 10 karakter.';
    } elseif (!preg_match('/[A-Za-z]/', $pass1) || !preg_match('/\d/', $pass1)) {
        $error = 'Password harus mengandung huruf dan angka.';
    } elseif (!hash_equals($pass1, $pass2)) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        // INSERT ... WHERE NOT EXISTS: hanya berhasil bila belum ada admin (aman dari dua permintaan bersamaan).
        $st = db()->prepare(
            'INSERT INTO admins (username, password_hash, created_at)
             SELECT :u, :h, :t FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM admins)'
        );
        $st->execute([':u' => $username, ':h' => password_hash($pass1, PASSWORD_DEFAULT), ':t' => date('Y-m-d H:i:s')]);
        if ($st->rowCount() === 1) {
            admin_flash_set('ok', 'Akun admin dibuat. Silakan masuk.');
            admin_redirect('login.php');
        }
        admin_redirect('login.php');
    }
}

admin_header('Setup Admin');
?>
<section class="card card-narrow">
    <h1>Buat Akun Admin</h1>
    <p class="muted">Dilakukan sekali saja. Setelah akun dibuat, halaman ini otomatis tertutup.</p>
    <?php if ($error !== ''): ?><div class="alert alert-err" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <form method="post" action="setup.php" autocomplete="off">
        <?php echo csrf_field(); ?>
        <label class="field"><span>Username</span>
            <input type="text" name="username" value="<?php echo e($username); ?>" maxlength="50" required autofocus autocomplete="username">
        </label>
        <label class="field"><span>Password (min. 10 karakter, huruf + angka)</span>
            <input type="password" name="password" minlength="10" maxlength="200" required autocomplete="new-password">
        </label>
        <label class="field"><span>Ulangi password</span>
            <input type="password" name="password2" minlength="10" maxlength="200" required autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Buat Admin</button>
    </form>
</section>
<?php admin_footer();