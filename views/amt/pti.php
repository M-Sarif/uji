<?php
/**
 * Layar: ?screen=amt_pti  (role AMT)  -- "Pre-Trip Inspection"
 *
 * Halaman awal PTI: nomor polisi MT, kapasitas tangki, status PTI.
 *  - Belum inspeksi : tombol "Isi Inspeksi" -> form 11 langkah (pti_form.php).
 *  - Sudah inspeksi : tombol "Lihat Hasil Inspeksi" (pti_hasil.php) dan
 *                     "Isi Inspeksi Lagi" (mengisi ulang form).
 * Halaman ini juga tujuan setelah "Ya, kirim hasil inspeksi" pada popup konfirmasi.
 *
 * Judul header "Pre-Trip Inspection" diatur di includes/amt/work.php (WORK_SCREENS).
 * Atribut data-tour dipakai tutorial AMT (includes/amt/tutorial.php).
 */
require_once __DIR__ . '/../../includes/amt/amt_pti_functions.php';

$shipment = amt_pti_shipment();
$done     = amt_pti_is_done();
$redo     = amt_pti_is_redo();   // sedang mengisi inspeksi ulang (draf belum dikirim)
?>

<section class="pti-screen" data-pti-done="<?= $done ? '1' : '0' ?>">
    <?php if ($shipment === null): ?>
        <div class="pti-empty">
            <img src="assets/empty-delivery-truck.png" alt="" class="pti-empty__img">
            <h2 class="pti-empty__title">Belum ada shipment</h2>
            <p class="pti-empty__text">Inspeksi tersedia setelah ada shipment untuk Anda.</p>
        </div>
    <?php else: ?>
        <div class="pti-card" data-tour="pti-card">
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

            <div class="pti-status" data-tour="pti-status">
                <div class="pti-label">Status PTI</div>
                <?php if ($done): ?>
                    <span class="pti-badge pti-badge--done">Sudah Inspeksi</span>
                <?php else: ?>
                    <span class="pti-badge pti-badge--pending">Belum Inspeksi</span>
                <?php endif; ?>
            </div>

            <?php if ($done): ?>
                <div class="pti-actions">
                    <a href="<?= amt_e(amt_pti_url('amt_pti_hasil')) ?>" class="pti-btn pti-btn--result" data-tour="pti-lihat">
                        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4M3 7h2M3 12h2M3 17h2"/></svg>
                        <span>Lihat Hasil Inspeksi</span>
                        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 12.3l2.5 2.5 4.5-4.8"/></svg>
                    </a>

                    <form method="post" action="<?= amt_e(amt_pti_url('amt_pti')) ?>">
                        <input type="hidden" name="pti_action" value="restart">
                        <button type="submit" class="pti-btn pti-btn--again" data-tour="pti-ulang">
                            <?= $redo ? 'Lanjutkan Isi Inspeksi' : 'Isi Inspeksi Lagi' ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <form method="post" action="<?= amt_e(amt_pti_url('amt_pti')) ?>">
                    <input type="hidden" name="pti_action" value="start">
                    <button type="submit" class="pti-btn pti-btn--primary" data-tour="pti-start">Isi Inspeksi</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>