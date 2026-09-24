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

$roleIcons = [
    'spbu' => 'assets/fuel-barrel.png',
    'amt'  => 'assets/empty-delivery-truck.png',
];
?>
<div class="role-wrap">

    <div class="role-brand">
        <img src="assets/logo-onefis.svg" alt="OneFIS">
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