<?php
/**
 * Layar awal aplikasi: pilih peran.
 *  - SPBU -> wajib memasukkan token SPBU, lalu masuk ke alur aplikasi (dashboard -> ... -> done)
 *  - AMT  -> wajib memasukkan token AMT, lalu masuk ke halaman AMT
 *
 * Memilih peran lewat aksi POST "select_role" (ditangani index.php) membuka layar token_login.
 * Bila pengguna SUDAH masuk dengan token untuk peran itu, ia langsung diteruskan ke berandanya.
 */
$activeRole = current_role();

// Gambar kartu tiap peran diambil dari peran itu sendiri (hook <peran>_card_image()).
$roleIcons = [];
foreach (role_keys() as $roleKey) {
    $roleIcons[$roleKey] = (string) role_call($roleKey, 'card_image');
}

// Pesan sekali-tampil (mis. sesi berakhir karena token dinonaktifkan admin)
$authNotice = $_SESSION['auth_notice'] ?? '';
unset($_SESSION['auth_notice']);
?>
<div class="role-wrap">

    <div class="role-brand">
        <img src="<?php echo CORE_URL; ?>/img/logo-onefis.svg" alt="OneFIS">
    </div>

    <div class="role-head">
        <h1>Masuk Sebagai</h1>
        <p>Pilih peran Anda, lalu masukkan token yang diberikan admin.</p>
    </div>

    <?php if ($authNotice !== ''): ?>
        <div class="auth-alert" role="alert"><?php echo h((string) $authNotice); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php" class="role-list">
        <input type="hidden" name="action" value="select_role">

        <?php foreach (ROLES as $key => $role): ?>
            <button type="submit" name="role" value="<?php echo h($key); ?>"
                    class="role-card<?php echo $activeRole === $key ? ' is-active' : ''; ?>">
                <span class="role-icon<?php echo $key === 'amt' ? ' amber' : ''; ?>">
                    <img src="<?php echo h($roleIcons[$key] ?? ''); ?>" alt="">
                </span>
                <span class="role-text">
                    <strong><?php echo h($role['label']); ?></strong>
                    <span><?php echo h($role['desc']); ?></span>
                    <?php if ($activeRole === $key): ?>
                        <em class="role-state">Sedang masuk &middot; ketuk untuk melanjutkan</em>
                    <?php endif; ?>
                </span>
                <svg class="role-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        <?php endforeach; ?>
    </form>

    <?php if ($activeRole !== null): ?>
        <form method="post" action="index.php" class="role-logout">
            <input type="hidden" name="action" value="logout">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-logout">Keluar</button>
        </form>
    <?php endif; ?>

</div>

<script>
/* Memilih peran -> tutorial peran itu otomatis aktif dari Beranda:
   catatan tutorial (sudah selesai / dilewati) dihapus dulu sebelum masuk. */
(function () {
    var spbu = document.querySelector('.role-card[value="spbu"]');
    if (spbu) {
        spbu.addEventListener('click', function () {
            if (window.OneFISTour) { window.OneFISTour.reset(); }
        });
    }

    // AMT: hapus catatan tutorial AMT langsung dari penyimpanan browser
    // (tidak bergantung pada mesin tutorial peran yang sedang aktif).
    var amt = document.querySelector('.role-card[value="amt"]');
    if (amt) {
        amt.addEventListener('click', function () {
            try {
                localStorage.removeItem('onefis_amt_tour_v2_done');
                localStorage.removeItem('onefis_amt_tour_v2_off');   // status Lewati/selesai dari kunjungan sebelumnya
                Object.keys(sessionStorage).forEach(function (k) {
                    if (k.indexOf('onefis_amt_tour_v2_skip:') === 0) { sessionStorage.removeItem(k); }
                });
            } catch (e) {}
        });
    }
})();
</script>