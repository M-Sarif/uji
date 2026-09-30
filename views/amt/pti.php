<?php
/**
 * Layar: ?screen=amt_pti  (role AMT)
 * Judul header "Pre-Trip Inspection" diatur lewat includes/data.php.
 */
require_once __DIR__ . '/../../includes/amt/amt_pti_functions.php';

$shipment = amt_pti_shipment();
$done     = amt_pti_is_done();
$flash    = isset($_SESSION['amt_pti_flash']) ? $_SESSION['amt_pti_flash'] : '';
unset($_SESSION['amt_pti_flash']);
?>
<link rel="stylesheet" href="assets/amt-pti.css">

<section class="pti-screen">
    <?php if ($flash !== ''): ?>
        <p class="pti-flash" role="status"><?= amt_e($flash) ?></p>
    <?php endif; ?>

    <?php if ($shipment === null): ?>
        <div class="pti-empty">
            <img src="assets/empty-delivery-truck.png" alt="" class="pti-empty__img">
            <h2 class="pti-empty__title">Belum ada shipment</h2>
            <p class="pti-empty__text">Inspeksi tersedia setelah ada shipment untuk Anda.</p>
        </div>
    <?php else: ?>
        <div class="pti-card">
            <div class="pti-grid">
                <div>
                    <div class="pti-label">Nomor Polisi MT</div>
                    <div class="pti-value"><?= amt_e($shipment['nomor_polisi']) ?></div>
                </div>
                <div>
                    <div class="pti-label">Kapasitas Tangki</div>
                    <div class="pti-value"><?= amt_e($shipment['kapasitas']) ?></div>
                </div>
            </div>

            <div class="pti-status">
                <div class="pti-label">Status PTI</div>
                <?php if ($done): ?>
                    <span class="pti-badge pti-badge--done">Sudah Inspeksi</span>
                <?php else: ?>
                    <span class="pti-badge pti-badge--pending">Belum Inspeksi</span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= amt_e(amt_pti_url('amt_pti')) ?>">
                <input type="hidden" name="pti_action" value="start">
                <?php if ($done): ?>
                    <button type="button" class="pti-btn pti-btn--primary is-locked" aria-disabled="true">Inspeksi Selesai</button>
                <?php else: ?>
                    <button type="submit" class="pti-btn pti-btn--primary">Isi Inspeksi</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</section>