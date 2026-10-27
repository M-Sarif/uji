<?php
/**
 * Layar input TOKEN. Dibuka setelah pengguna memilih peran (AMT / SPBU) di layar pilih peran.
 * Token dibuat admin (admin/index.php). Form dikirim ke index.php dengan aksi "login_token".
 *
 * Letak: core/views/token_login.php
 */
$pendingRole = $_SESSION['pending_role'] ?? '';
$roleLabel   = isset(ROLES[$pendingRole]) ? ROLES[$pendingRole]['label'] : '';
$authError   = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);
?>
<div class="role-wrap">

    <div class="role-brand">
        <img src="<?php echo CORE_URL; ?>/img/logo-onefis.svg" alt="OneFIS">
    </div>

    <div class="role-head">
        <h1>Masuk sebagai <?php echo h($roleLabel); ?></h1>
        <p>Masukkan token <?php echo h($roleLabel); ?> yang diberikan admin.</p>
    </div>

    <?php if ($authError !== ''): ?>
        <div class="auth-alert" role="alert"><?php echo h((string) $authError); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php" class="token-form" autocomplete="off">
        <input type="hidden" name="action" value="login_token">
        <?php echo csrf_field(); ?>

        <label for="tokenInput" class="token-label">Token <?php echo h($roleLabel); ?></label>
        <input type="text" id="tokenInput" name="token" class="token-input"
               placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="40" required autofocus
               autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" inputmode="text">

        <button type="submit" class="btn-token">Masuk</button>
        <a class="token-back" href="index.php?screen=role_select">&larr; Ganti peran</a>
    </form>

</div>

<script>
/* Format otomatis: huruf besar, tanda hubung tiap 4 karakter (hanya tampilan; server tetap memeriksa ulang). */
(function () {
    var input = document.getElementById('tokenInput');
    if (!input) { return; }
    input.addEventListener('input', function () {
        var raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 16);
        input.value = raw.replace(/(.{4})(?=.)/g, '$1-');
    });
})();
</script>