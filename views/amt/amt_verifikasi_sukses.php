<?php
/**
 * Layar: ?screen=amt_verifikasi_sukses  (Order Berhasil Diverifikasi)
 * Letak: views/amt/amt_verifikasi_sukses.php
 */

$state = amt_verif_state();
$ids   = isset($state['last']['ids']) ? $state['last']['ids'] : [];
?>

<section class="vok">
    <div class="vok-body">
        <div class="vok-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="34" height="34"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h2 class="vok-title">Order Berhasil<br>Diverifikasi</h2>
        <div class="vok-line"></div>

        <div class="vok-card">
            <?php foreach ($ids as $id): ?>
                <div class="vok-row"><span>Nomor LO</span><strong><?= amt_verif_e($id) ?></strong></div>
            <?php endforeach; ?>
            <div class="vok-row"><span>Order</span><span class="verif-pill"><?= amt_verif_e(AMT_VERIF_ORDER_TYPE) ?></span></div>
        </div>
    </div>

    <a class="vok-btn" href="<?= amt_verif_e(amt_verif_url(AMT_VERIF_RETURN_SCREEN)) ?>">Oke</a>
</section>