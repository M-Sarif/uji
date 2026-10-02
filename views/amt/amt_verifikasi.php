<?php
/**
 * Layar: ?screen=amt_verifikasi  (Verifikasi Order - Daftar LO + Metode Verifikasi)
 * Letak: views/amt/amt_verifikasi.php
 */

$los      = amt_verif_lo_list();
$selected = amt_verif_selected();
$hasPick  = count($selected) > 0;
$allDone  = amt_verif_is_done();
?>

<section class="verif">
    <div class="verif-head">
        <h2 class="verif-title">Daftar LO</h2>
        <a class="verif-refresh" href="<?= amt_verif_e(amt_verif_url('amt_verifikasi')) ?>">
            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3M4 13a8 8 0 0 0 14.9 3M4 4v4h4M20 20v-4h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Refresh
        </a>
    </div>
    <p class="verif-hint">Hanya LO yang sudah isi form bongkar yang bisa untuk verifikasi.</p>

    <?php foreach ($los as $lo):
        $eligible = amt_verif_is_eligible($lo);
        $verified = amt_verif_is_verified($lo['id']);
        $checked  = in_array($lo['id'], $selected, true);
        $classes  = 'verif-lo' . ($checked ? ' is-selected' : '') . (($eligible && !$verified) ? '' : ' is-disabled');
    ?>
        <div class="<?= $classes ?>">
            <dl class="verif-rows">
                <div><dt>Nomor LO</dt><dd class="verif-strong"><?= amt_verif_e($lo['id']) ?></dd></div>
                <div><dt>Order</dt><dd><span class="verif-pill"><?= amt_verif_e($lo['order']) ?></span></dd></div>
                <div><dt>Form Bongkar</dt><dd>
                    <span class="verif-badge <?= $eligible ? 'is-ok' : 'is-wait' ?>"><?= amt_verif_e($lo['form_bongkar']) ?></span>
                </dd></div>
                <?php if ($verified): ?>
                    <div><dt>Verifikasi</dt><dd><span class="verif-badge is-ok">Terverifikasi</span></dd></div>
                <?php endif; ?>
            </dl>

            <?php if ($eligible && !$verified): ?>
                <a class="verif-check<?= $checked ? ' is-on' : '' ?>"
                   href="<?= amt_verif_e(amt_verif_url('amt_verifikasi', ['toggle' => $lo['id']])) ?>"
                   role="checkbox" aria-checked="<?= $checked ? 'true' : 'false' ?>"
                   aria-label="Pilih LO <?= amt_verif_e($lo['id']) ?>">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($allDone): ?>
        <p class="verif-note">Semua LO sudah diverifikasi.</p>
        <a class="verif-method is-on" href="<?= amt_verif_e(amt_verif_url(AMT_VERIF_RETURN_SCREEN)) ?>">Kembali ke Shipment</a>
    <?php else: ?>
        <h2 class="verif-title verif-title--gap">Metode Verifikasi</h2>
        <p class="verif-hint">Pilih metode untuk verifikasi order</p>

        <?php if ($hasPick): ?>
            <a class="verif-method is-on" href="<?= amt_verif_e(amt_verif_url('amt_verifikasi_qr', ['restart' => 1])) ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M8 12h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Pindai Kode QR
            </a>
            <a class="verif-method is-on" href="<?= amt_verif_e(amt_verif_url('amt_verifikasi_kode', ['restart' => 1])) ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Kode Konfirmasi
            </a>
        <?php else: ?>
            <span class="verif-method" aria-disabled="true">Pindai Kode QR</span>
            <span class="verif-method" aria-disabled="true">Kode Konfirmasi</span>
            <p class="verif-note">Centang LO di atas untuk memilih metode.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
// Seluruh kartu LO bisa disentuh untuk mencentang (sama dengan menyentuh kotak centang).
(function () {
    var cards = document.querySelectorAll('.verif-lo');
    for (var i = 0; i < cards.length; i++) {
        (function (card) {
            var a = card.querySelector('.verif-check');
            if (!a) return;
            card.style.cursor = 'pointer';
            card.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('a')) return;
                window.location.href = a.getAttribute('href');
            });
        })(cards[i]);
    }
})();
</script>