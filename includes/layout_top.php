<?php
/**
 * Bagian atas layout (dipakai oleh index.php).
 * Variabel yang harus tersedia: $screen, $headerTitle, $tutorialText, $prevScreen
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<title>OneFIS<?php echo $headerTitle !== '' ? ' - ' . h($headerTitle) : ''; ?></title>
<link rel="stylesheet" href="assets/css/base.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/base.css'); ?>">
<link rel="stylesheet" href="assets/css/components.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/components.css'); ?>">
<link rel="stylesheet" href="assets/css/tutorial.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/tutorial.css'); ?>">
<?php foreach (SCREEN_CSS[$screen] ?? [] as $cssFile): ?>
<link rel="stylesheet" href="assets/css/<?php echo h($cssFile); ?>.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/' . $cssFile . '.css'); ?>">
<?php endforeach; ?>
</head>
<body>

<?php
// Notifikasi sukses sekali-tampil (flash message) hasil aksi di layar
// sebelumnya (mis. "Konfirmasi LO" di step 15 checklist, atau "Ya, Kirim"
// di Daftar LO). Diambil lalu langsung dihapus dari session supaya tidak
// muncul lagi saat halaman dibuka ulang.
//
// Bentuknya bisa string (toast satu baris) atau array ['title' =>, 'body' =>]
// (toast dua baris, judul tebal + keterangan).
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$flashTitle = null;
$flashBody  = null;
if (is_array($flashSuccess)) {
    $flashTitle = $flashSuccess['title'] ?? null;
    $flashBody  = $flashSuccess['body'] ?? null;
} elseif (is_string($flashSuccess) && $flashSuccess !== '') {
    $flashBody = $flashSuccess;
}
?>
<?php if ($flashBody !== null): ?>
<div class="toast-success<?php echo $flashTitle !== null ? ' has-title' : ''; ?>" id="toastSuccess" role="status" aria-live="polite" style="position:fixed;z-index:999;top:1.25rem;right:1.25rem;left:1.25rem;background:#16a34a;color:#fff;">
    <span class="toast-success-icon">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </span>
    <span class="toast-success-text">
        <?php if ($flashTitle !== null): ?>
            <strong class="toast-success-title"><?php echo h($flashTitle); ?></strong>
        <?php endif; ?>
        <span class="toast-success-body"><?php echo h($flashBody); ?></span>
    </span>
</div>
<?php endif; ?>

<div class="app-container">

    <?php if ($screen === 'role_select'): ?>
        <!-- Layar awal (pilih peran): tanpa header -->
    <?php elseif ($screen === 'dashboard'): ?>
        <!-- Header dashboard -->
        <div class="app-header">
            <div class="logo">
                <img src="assets/logo-onefis.svg" alt="OneFIS">
            </div>
            <div class="header-right">
                <a class="role-switch" href="index.php?screen=role_select" aria-label="Ganti peran">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                    <?php echo h(ROLES['spbu']['label']); ?>
                </a>
                <div class="bell">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="badge">6</div>
            </div>
        </div>
    <?php elseif ($screen === 'amt_home'): ?>
        <!-- Header beranda AMT: logo, notifikasi (titik merah), avatar -->
        <div class="app-header">
            <div class="logo">
                <img src="assets/logo-onefis.svg" alt="OneFIS">
            </div>
            <div class="header-right">
                <div class="bell">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="bell-dot"></span>
                </div>
                <a class="badge" href="index.php?screen=role_select" aria-label="Ganti peran">S</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Header layar lain: tombol back + judul -->
        <div class="app-header-simple<?php echo $screen === 'claim_loss' ? ' centered' : ''; ?>">
            <?php if ($prevScreen): ?>
                <a class="back-btn" href="index.php?screen=<?php echo h($prevScreen); ?>">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
            <?php else: ?>
                <span class="back-btn" style="visibility:hidden;"></span>
            <?php endif; ?>
            <h1><?php echo h($headerTitle); ?></h1>
            <?php if ($screen === 'claim_loss'): ?>
                <span class="back-btn" style="visibility:hidden;"></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Tutorial: sekarang berupa guided tour bersorot (spotlight) yang
         menunjuk elemen asli di halaman, lihat assets/js/tutorial.js +
         includes/layout_bottom.php. Kotak petunjuk statis lama sudah
         digantikan supaya urutannya selalu mengikuti dokumen panduan
         Aktifitas di SPBU dan tidak lagi kosong di sebagian layar. -->

    <!-- Konten utama -->
    <?php $hasWizardNav = in_array($screen, ['checklist', 'claim_loss', 'rating'], true); ?>
    <?php $isFlexCol    = $screen === 'lo_list'; ?>
    <div class="content<?php echo $screen === 'amt_home' ? ' content-amt' : ''; ?><?php echo $hasWizardNav ? ' content-with-nav' : ''; ?><?php echo $isFlexCol ? ' content-flex-col' : ''; ?>">