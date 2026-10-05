<?php
/**
 * Layar awal aplikasi: pilih peran.
 *  - SPBU -> masuk ke alur aplikasi yang berjalan (dashboard -> ... -> done)
 *  - AMT  -> masuk ke halaman AMT
 *
 * Pilihan disimpan di $_SESSION['role'] lewat aksi POST "select_role"
 * (ditangani di index.php).
 */
$activeRole = current_role();

// Gambar kartu tiap peran diambil dari peran itu sendiri (hook <peran>_card_image()).
$roleIcons = [];
foreach (role_keys() as $roleKey) {
    $roleIcons[$roleKey] = (string) role_call($roleKey, 'card_image');
}
?>
<div class="role-wrap">

    <div class="role-brand">
        <img src="<?php echo CORE_URL; ?>/img/logo-onefis.svg" alt="OneFIS">
    </div>

    <div class="role-head">
        <h1>Masuk Sebagai</h1>
        <p>Pilih peran Anda untuk melanjutkan.</p>
    </div>

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
                </span>
                <svg class="role-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        <?php endforeach; ?>
    </form>

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
                Object.keys(sessionStorage).forEach(function (k) {
                    if (k.indexOf('onefis_amt_tour_v2_skip:') === 0) { sessionStorage.removeItem(k); }
                });
            } catch (e) {}
        });
    }
})();
</script>