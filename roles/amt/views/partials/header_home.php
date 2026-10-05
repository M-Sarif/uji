<?php
/* Header beranda AMT: logo, notifikasi (titik merah), avatar.
 * Dipanggil dari core/layout_top.php lewat hook amt_header_partial() saat $screen === 'amt_home'. */
?>
<!-- Header beranda AMT: logo, notifikasi (titik merah), avatar -->
<div class="app-header">
    <div class="logo">
        <img src="<?php echo CORE_URL; ?>/img/logo-onefis.svg" alt="OneFIS">
    </div>
    <div class="header-right">
        <div class="bell">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span class="bell-dot"></span>
        </div>
        <a class="badge" href="index.php?screen=role_select" aria-label="Ganti peran">R</a>
    </div>
</div>