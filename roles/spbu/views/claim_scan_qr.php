<?php
$scanError = (string) ($_SESSION['claim_scan_error'] ?? '');
$simQr     = spbu_claim_sim_qr();
unset($_SESSION['claim_scan_error']);
?>
<div class="scan-stage">
    <div class="scan-frame" id="scanFrame" data-sim-qr="<?php echo h($simQr); ?>">
        <video id="scanVideo" class="scan-video" autoplay playsinline muted></video>
        <div class="scan-sim" id="scanSim" hidden>
            <div class="scan-sim-scene"><div class="scan-sim-card" id="scanSimCard"></div></div>
            <div class="scan-sim-laser"></div>
            <div class="scan-sim-status" id="scanSimStatus" aria-live="polite">Memindai…</div>
        </div>
        <i class="scan-corner tl"></i><i class="scan-corner tr"></i>
        <i class="scan-corner bl"></i><i class="scan-corner br"></i>
    </div>
    <div class="scan-sim-info" id="scanSimInfo" hidden>
        <p class="scan-sim-note">Kode QR akan kadaluwarsa dalam waktu...</p>
        <p class="scan-sim-timer" id="scanSimTimer">03:00</p>
        <div class="scan-sim-expired" id="scanSimExpired" hidden>
            <p class="scan-sim-expired-title">Kode QR kedaluwarsa</p>
            <p class="scan-sim-expired-text">Waktu pemindaian sudah habis.</p>
            <a class="scan-sim-retry" href="index.php?screen=claim_scan_qr">Coba Lagi</a>
        </div>
    </div>
    <p class="scan-msg<?php echo $scanError !== '' ? ' is-error' : ''; ?>" id="scanMsg" role="status" aria-live="polite"><?php echo h($scanError !== '' ? $scanError : 'Arahkan kamera ke QR Code AMT.'); ?></p>
</div>

<form method="post" action="index.php" id="qrForm" hidden>
    <input type="hidden" name="action" value="save_qr_amt">
    <input type="hidden" name="qr" id="qrValue" value="">
</form>
<input type="file" id="galleryInput" accept="image/*" hidden>
<canvas id="scanCanvas" hidden></canvas>

<script src="<?php echo SPBU_URL; ?>/js/qrcode.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/qrcode.js'); ?>"></script>
<script src="<?php echo SPBU_URL; ?>/js/jsQR.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/jsQR.js'); ?>"></script>
<script src="<?php echo SPBU_URL; ?>/js/claim-scan.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/claim-scan.js'); ?>"></script>