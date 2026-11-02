<?php
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
            <?php foreach ($ids as $id):
                $lo    = amt_verif_find_lo($id);
                $order = $lo !== null ? $lo['order'] : AMT_VERIF_ORDER_TYPE;
            ?>
                <div class="vok-group">
                    <div class="vok-row"><span>Nomor LO</span><strong><?= amt_e($id) ?></strong></div>
                    <div class="vok-row"><span>Order</span><span class="vok-pill"><?= amt_e($order) ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <a class="vok-btn" href="<?= amt_e(amt_verif_url(AMT_VERIF_RETURN_SCREEN)) ?>">Oke</a>
</section>