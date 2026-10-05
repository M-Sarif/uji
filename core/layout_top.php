<?php
/**
 * Bagian atas layout (dipakai oleh index.php).
 * Variabel yang harus tersedia: $screen, $headerTitle, $prevScreen
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<title>OneFIS<?php echo $headerTitle !== '' ? ' - ' . h($headerTitle) : ''; ?></title>
<?php
// CSS bersama (core) -> CSS global peran aktif (mis. gaya tutorial) -> CSS khusus layar.
// Peran aktif = peran yang dipilih; sebelum memilih dipakai DEFAULT_TOUR_ROLE.
$cssRole  = current_role() ?? DEFAULT_TOUR_ROLE;
$cssHrefs = array_merge(
    [CORE_URL . '/css/base.css', CORE_URL . '/css/components.css'],
    (array) role_call($cssRole, 'global_css'),
    $screen === 'role_select' ? [CORE_URL . '/css/role.css'] : (array) role_call(screen_owner($screen), 'screen_css', $screen)
);
foreach ($cssHrefs as $cssHref): ?>
<link rel="stylesheet" href="<?php echo h($cssHref); ?>?v=<?php echo (int) @filemtime(APP_ROOT . '/' . $cssHref); ?>">
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

    <?php
    // Header: layar pilih peran tanpa header; peran boleh punya header sendiri untuk
    // berandanya (hook <peran>_header_partial); selain itu tombol back + judul.
    $owner         = screen_owner($screen);
    $headerPartial = $screen === 'role_select' ? null : role_call($owner, 'header_partial', $screen);
    ?>
    <?php if ($screen === 'role_select'): ?>
        <!-- Layar awal (pilih peran): tanpa header -->
    <?php elseif ($headerPartial !== null): ?>
        <?php require $headerPartial; // header beranda milik peran ?>
    <?php else: ?>
        <!-- Header layar lain: tombol back + judul -->
        <?php
        $hs = role_call($owner, 'header_style', $screen) ?? ['class' => '', 'arrow' => false, 'spacer' => false];
        ?>
        <div class="app-header-simple<?php echo $hs['class']; ?>">
            <?php if ($prevScreen): ?>
                <a class="back-btn" href="index.php?screen=<?php echo h($prevScreen); ?>" aria-label="Kembali">
                    <?php if ($hs['arrow']): ?>
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7l-7 7 7 7"/></svg>
                    <?php else: ?>
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <span class="back-btn" style="visibility:hidden;"></span>
            <?php endif; ?>
            <h1><?php echo h($headerTitle); ?></h1>
            <?php if ($hs['spacer']): ?>
                <span class="back-btn" style="visibility:hidden;"></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Tutorial: sekarang berupa guided tour bersorot (spotlight) yang
         menunjuk elemen asli di halaman, lihat roles/spbu/assets/js/tutorial.js +
         core/layout_bottom.php. Kotak petunjuk statis lama sudah
         digantikan supaya urutannya selalu mengikuti dokumen panduan
         Aktifitas di SPBU dan tidak lagi kosong di sebagian layar. -->

    <!-- Konten utama -->
    <div class="content<?php echo (string) role_call($owner, 'content_class', $screen); ?>">