<?php
/**
 * Layar: ?screen=amt_verifikasi_qr  (Pindai Kode QR - SIMULASI, kamera tidak dihidupkan)
 * Letak: roles/amt/views/amt_verifikasi_qr.php
 */
$remaining = amt_verif_remaining('qr');
$scanUrl   = amt_verif_url('amt_verifikasi_qr', ['scan' => 1]);
$retryUrl  = amt_verif_url('amt_verifikasi_qr', ['restart' => 1]);
$payload   = 'ONEFIS-VERIF|' . (amt_verif_ship_id() ?: 'SIM') . '|' . implode(',', amt_verif_selected());
?>

<section class="vqr" id="vqr">
    <div class="vqr-inner">
        <!-- Jendela kamera (simulasi): kode QR yang sedang dipindai tampil di dalamnya -->
        <div class="vqr-frame" id="vqrFrame" role="img" aria-label="Simulasi pemindaian kode QR">
            <div class="vqr-scene">
                <div class="vqr-card" id="vqrCard" data-vqr-payload="<?= amt_e($payload) ?>"></div>
            </div>
            <span class="vqr-corner is-tl"></span><span class="vqr-corner is-tr"></span>
            <span class="vqr-corner is-bl"></span><span class="vqr-corner is-br"></span>
            <div class="vqr-laser" id="vqrLaser"></div>
            <div class="vqr-status" id="vqrStatus" aria-live="polite">Memindai…</div>
        </div>

        <p class="vqr-note">Kode QR akan kadaluwarsa dalam waktu...</p>
        <p class="vqr-timer" id="vqrTimer"><?= sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) ?></p>

        <button type="button" class="vqr-btn" id="vqrNow">Simulasikan Scan</button>
        <?php if (AMT_VERIF_SIM_NOTE): ?>
            <p class="vqr-sim">Mode simulasi: kamera tidak dinyalakan.</p>
        <?php endif; ?>

        <div class="vqr-expired" id="vqrExpired" hidden>
            <p class="vqr-expired__title">Kode QR kedaluwarsa</p>
            <p class="vqr-expired__text">Waktu pemindaian sudah habis.</p>
            <a class="vqr-btn is-solid" href="<?= amt_e($retryUrl) ?>">Coba Lagi</a>
        </div>
    </div>
</section>

<script src="<?= AMT_URL ?>/js/qrcode.js?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/js/qrcode.js') ?>"></script>

<script>
(function () {
    var remaining = <?= (int) $remaining ?>;
    var scanUrl = <?= json_encode($scanUrl) ?>;
    var autoMs = <?= (int) AMT_VERIF_QR_AUTOSCAN_MS ?>;
    var root = document.getElementById('vqr');
    var timer = document.getElementById('vqrTimer');
    var status = document.getElementById('vqrStatus');
    var expired = document.getElementById('vqrExpired');
    var btn = document.getElementById('vqrNow');
    var frame = document.getElementById('vqrFrame');
    var end = Date.now() + remaining * 1000;
    var finished = false;

    /* Gambar kode QR simulasi di dalam jendela kamera */
    (function drawQr() {
        var card = document.getElementById('vqrCard');
        if (!card || !window.qrcode) return;
        var q = window.qrcode(0, 'M');
        q.addData(card.getAttribute('data-vqr-payload') || 'ONEFIS-VERIF');
        q.make();
        card.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    })();

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function show(s) { timer.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); }

    function expire() {
        finished = true;
        root.classList.add('is-expired');
        status.textContent = 'Waktu habis';
        btn.disabled = true;
        expired.hidden = false;
    }
    function scan() {
        if (finished) return;
        finished = true;
        root.classList.add('is-found');
        status.textContent = 'Kode QR terbaca ✓';
        btn.disabled = true;
        setTimeout(function () { window.location.href = scanUrl; }, 700);
    }
    function tick() {
        if (finished) return;
        var left = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        show(left);
        if (left <= 0) expire();
    }

    show(remaining);
    if (remaining <= 0) { expire(); return; }
    setInterval(tick, 250);
    btn.addEventListener('click', scan);
    frame.addEventListener('click', scan);
    if (autoMs > 0) setTimeout(scan, autoMs);
})();
</script>