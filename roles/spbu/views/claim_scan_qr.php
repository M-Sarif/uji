<?php
/**
 * SPBU - layar "Scan QR AMT": kamera pemindai QR Code yang ditunjukkan AMT
 * (QR Code Claim Loss dari langkah 7 checklist AMT). Dibuka dari tombol
 * "Scan Sekarang" di halaman Upload Dokumen Klaim (claim_upload).
 *
 * - Kamera belakang dibaca terus-menerus oleh roles/spbu/assets/js/claim-scan.js
 *   (pustaka jsQR). Begitu QR terbaca, isinya dikirim ke server (aksi save_qr_amt).
 * - Ikon galeri (kanan atas header) = pilih gambar QR dari galeri bila kamera
 *   tidak bisa dipakai.
 * - QR yang salah / kedaluwarsa ditolak server; pesannya tampil di bawah kotak scan.
 *
 * Letak: roles/spbu/views/claim_scan_qr.php
 */
$scanError = (string) ($_SESSION['claim_scan_error'] ?? '');
unset($_SESSION['claim_scan_error']);
?>
<div class="scan-stage">
    <div class="scan-frame" id="scanFrame">
        <video id="scanVideo" class="scan-video" autoplay playsinline muted></video>
        <i class="scan-corner tl"></i><i class="scan-corner tr"></i>
        <i class="scan-corner bl"></i><i class="scan-corner br"></i>
    </div>
    <p class="scan-msg<?php echo $scanError !== '' ? ' is-error' : ''; ?>" id="scanMsg" role="status" aria-live="polite"><?php echo h($scanError !== '' ? $scanError : 'Arahkan kamera ke QR Code AMT.'); ?></p>
</div>

<!-- Form tersembunyi: dikirim otomatis oleh claim-scan.js begitu QR terbaca -->
<form method="post" action="index.php" id="qrForm" hidden>
    <input type="hidden" name="action" value="save_qr_amt">
    <input type="hidden" name="qr" id="qrValue" value="">
</form>
<input type="file" id="galleryInput" accept="image/*" hidden>
<canvas id="scanCanvas" hidden></canvas>

<script src="<?php echo SPBU_URL; ?>/js/jsQR.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/jsQR.js'); ?>"></script>
<script src="<?php echo SPBU_URL; ?>/js/claim-scan.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/claim-scan.js'); ?>"></script>