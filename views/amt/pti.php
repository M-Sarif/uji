<?php
/**
 * Layar: ?screen=amt_pti  (role AMT)  -- "Pre-Trip Inspection"
 *
 * Ringkasan sebelum inspeksi: nomor polisi MT, kapasitas tangki, status PTI,
 * dan tombol "Isi Inspeksi". Form 11 langkah ada di pti_form.php
 * (?screen=amt_pti_form&step=1..11) dan dibuka lewat tombol ini.
 *
 * Judul header "Pre-Trip Inspection" diatur di includes/amt/work.php (WORK_SCREENS).
 * Atribut data-tour dipakai tutorial AMT (includes/amt/tutorial.php).
 */
require_once __DIR__ . '/../../includes/amt/amt_pti_functions.php';

$shipment = amt_pti_shipment();
$done     = amt_pti_is_done();
$result   = $done ? (amt_pti_state()['result'] ?? null) : null;
$flash    = isset($_SESSION['amt_pti_flash']) ? $_SESSION['amt_pti_flash'] : '';
unset($_SESSION['amt_pti_flash']);
$sent     = $flash !== '' && $done;   // baru saja mengirim inspeksi -> tampilkan pemberitahuan
$isGo     = $result === 'GO';
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

            <div class="pti-status">
                <div class="pti-label">Status PTI</div>
                <?php if ($done): ?>
                    <span class="pti-badge pti-badge--done">Sudah Inspeksi<?= $result ? ' · ' . amt_e($result) : '' ?></span>
                <?php else: ?>
                    <span class="pti-badge pti-badge--pending">Belum Inspeksi</span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= amt_e(amt_pti_url('amt_pti')) ?>">
                <input type="hidden" name="pti_action" value="start">
                <?php if ($done): ?>
                    <button type="button" class="pti-btn pti-btn--primary is-locked" aria-disabled="true">Inspeksi Selesai</button>
                <?php else: ?>
                    <button type="submit" class="pti-btn pti-btn--primary" data-tour="pti-start">Isi Inspeksi</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php if ($sent): ?>
<!-- Pemberitahuan setelah inspeksi terkirim (tutorial AMT menunggu sampai ini ditutup). -->
<div class="pti-notif is-open" id="ptiNotif" role="alertdialog" aria-modal="true"
     aria-labelledby="ptiNotifTitle" aria-describedby="ptiNotifText">
    <div class="pti-notif__card">
        <span class="pti-notif__icon pti-notif__icon--<?= $isGo ? 'go' : 'nogo' ?>" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        </span>
        <h2 class="pti-notif__title" id="ptiNotifTitle">Inspeksi Berhasil Dikirim</h2>
        <p class="pti-notif__result pti-notif__result--<?= $isGo ? 'go' : 'nogo' ?>">Hasil Inspeksi: <?= amt_e($result) ?></p>
        <p class="pti-notif__text" id="ptiNotifText">
            <?= $isGo
                ? 'Mobil tangki dalam keadaan layak beroperasi. Status PTI Anda sekarang “Sudah Inspeksi”.'
                : 'Mobil tangki belum layak beroperasi. Laporkan ke pengawas sebelum berangkat.' ?>
        </p>
        <button type="button" class="pti-btn pti-btn--primary" id="ptiNotifOk">OK</button>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById('ptiNotif');
    var ok  = document.getElementById('ptiNotifOk');
    if (!box || !ok) return;
    function close() {
        box.classList.remove('is-open');
        document.removeEventListener('keydown', onKey);
        if (window.OneFISTour && window.OneFISTour.rescan) window.OneFISTour.rescan();   // lanjutkan tutorial
    }
    function onKey(e) { if (e.key === 'Escape') close(); }
    ok.addEventListener('click', close);
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', onKey);
    ok.focus();
})();
</script>
<?php endif; ?>