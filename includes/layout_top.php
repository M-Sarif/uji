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
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/components.css">
<?php foreach (SCREEN_CSS[$screen] ?? [] as $cssFile): ?>
<link rel="stylesheet" href="assets/css/<?php echo h($cssFile); ?>.css">
<?php endforeach; ?>
</head>
<body>

<div class="app-container">

    <?php if ($screen === 'dashboard'): ?>
        <!-- Header dashboard -->
        <div class="app-header">
            <div class="logo">
                <img src="assets/logo-onefis.svg" alt="OneFIS">
            </div>
            <div class="header-right">
                <div class="bell">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="badge">6</div>
            </div>
        </div>
    <?php else: ?>
        <!-- Header layar lain: tombol back + judul -->
        <div class="app-header-simple">
            <?php if ($prevScreen): ?>
                <a class="back-btn" href="index.php?screen=<?php echo h($prevScreen); ?>">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
            <?php else: ?>
                <span class="back-btn" style="visibility:hidden;"></span>
            <?php endif; ?>
            <h1><?php echo h($headerTitle); ?></h1>
        </div>
    <?php endif; ?>

    <!-- Tutorial mengambang -->
    <?php if ($tutorialText !== '' && $screen !== 'dashboard'): ?>
        <div class="tutorial<?php echo $screen !== 'dashboard' ? ' simple-offset' : ''; ?>" id="tutorialBox" data-screen="<?php echo h($screen); ?>">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <p><?php echo h($tutorialText); ?></p>
            <button type="button" class="tutorial-close" id="tutorialClose" aria-label="Sembunyikan petunjuk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    <?php endif; ?>

    <!-- Konten utama -->
    <div class="content">