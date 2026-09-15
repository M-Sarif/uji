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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OneFIS<?php echo $headerTitle !== '' ? ' - ' . h($headerTitle) : ''; ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="phone">

    <!-- Status bar palsu -->
    <div class="statusbar">
        <span>10:58</span>
        <div class="island"></div>
        <div class="icons">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 22h2V10H2v12zm5 0h2V6H7v16zm5 0h2V2h-2v20zm5 0h2v-8h-2v8zm5 0h2v-4h-2v4z"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3a4.24 4.24 0 00-6 0zm-4-4l2 2a7.07 7.07 0 0110 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="18" height="10" rx="2"/><line x1="22" y1="10" x2="22" y2="14"/></svg>
        </div>
    </div>

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
        <div class="tutorial<?php echo $screen !== 'dashboard' ? ' simple-offset' : ''; ?>">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <p><?php echo h($tutorialText); ?></p>
        </div>
    <?php endif; ?>

    <!-- Konten utama -->
    <div class="content">
