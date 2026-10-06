<?php
/**
 * Layar: ?screen=amt_verifikasi_qr  (Pindai Kode QR - pemindaian disimulasikan otomatis, kamera tidak dihidupkan)
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
    var scanMs = <?= (int) AMT_VERIF_QR_SCAN_MS ?>;           // tanpa tutorial: 3 detik
    var scanTourMs = <?= (int) AMT_VERIF_QR_SCAN_TOUR_MS ?>;  // mode tutorial: 4 detik setelah "Mengerti"
    var root = document.getElementById('vqr');
    var timer = document.getElementById('vqrTimer');
    var status = document.getElementById('vqrStatus');
    var expired = document.getElementById('vqrExpired');
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
        expired.hidden = false;
    }
    function scan() {
        if (finished) return;
        finished = true;
        root.classList.add('is-found');
        status.textContent = 'Kode QR terbaca ✓';
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

    /* Pemindaian otomatis (simulasi) - tidak ada tombol; waktunya bergantung pada mode tutorial:
     *  - mode tutorial : 4 detik SETELAH pengguna mengetuk "Mengerti" di kartu tutorial,
     *  - tanpa tutorial: 3 detik setelah layar ini dibuka (yaitu setelah tombol "Pindai Kode QR" diketuk). */
    var armed = false;
    function arm(ms) {
        if (armed || finished) return;
        armed = true;
        setTimeout(scan, ms);
    }
    function decide() {
        var tour = window.OneFISTour;
        if (!(tour && tour.isActive && tour.isActive())) { arm(scanMs); return; }   // tutorial tidak tampil
        document.addEventListener('amt:tour-done', function (e) {
            if (!e.detail || e.detail.key !== 'amt_verif_qr') return;
            // "Mengerti" -> 4 detik. "Lewati" (tutorial ditutup tanpa Mengerti) -> sama seperti tanpa tutorial.
            arm(e.detail.skipped ? scanMs : scanTourMs);
        });
    }
    // Mesin tutorial dimuat di akhir halaman (core/layout_bottom.php), jadi tunggu halaman selesai dimuat.
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', decide); } else { decide(); }
})();
</script>