<?php
require __DIR__ . '/../app/admin_auth.php';
admin_boot();

if (!admin_exists()) {
    admin_redirect('setup.php');
}
if (admin_current() !== null) {
    admin_redirect('index.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = post_str('username', 50);
    $password = isset($_POST['password']) && is_string($_POST['password']) ? substr($_POST['password'], 0, 200) : '';

    if (!csrf_verify()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (login_is_locked('admin')) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . (int) app_config('lockout_minutes') . ' menit.';
    } else {
        $id = $username !== '' && $password !== '' ? admin_check_password($username, $password) : null;
        if ($id !== null) {
            login_record('admin', true, null, null, $username);
            admin_start_session($id);
            admin_redirect('index.php');
        }
        login_record('admin', false, null, null, $username);
        $error = 'Username atau password salah.';
    }
}

admin_header('Masuk');
?>
<section class="card card-narrow">
    <h1>Masuk Admin</h1>
    <p class="muted">Khusus pengelola OneFIS.</p>
    <?php if ($error !== ''): ?><div class="alert alert-err" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <form method="post" action="<?php echo e(admin_url('login')); ?>" autocomplete="off">
        <?php echo csrf_field(); ?>
        <label class="field"><span>Username</span>
            <input type="text" name="username" maxlength="50" required autofocus autocomplete="username">
        </label>
        <label class="field"><span>Password</span>
            <input type="password" name="password" maxlength="200" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>
</section>
<?php admin_footer();