<?php
/**
 * SPBU - header layar "Scan QR AMT": latar abu-abu gelap, panah kembali di kiri,
 * judul di tengah, dan ikon galeri di kanan (pilih gambar QR Code dari galeri).
 * Dipakai lewat hook spbu_header_partial() di roles/spbu/module.php.
 *
 * Letak: roles/spbu/views/partials/header_scan_qr.php
 */
?>
<div class="scan-header">
    <a class="scan-back" href="index.php?screen=claim_upload" aria-label="Kembali">
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7l-7 7 7 7"/></svg>
    </a>
    <h1>Scan QR AMT</h1>
    <button type="button" class="scan-gallery" id="scanGallery" aria-label="Pilih gambar QR Code dari galeri">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-5-5L5 21"/></svg>
    </button>
</div>