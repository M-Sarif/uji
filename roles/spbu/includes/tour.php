<?php
/**
 * SPBU - penyusun konfigurasi tutorial terpandu untuk layar yang sedang dibuka.
 * Dipakai core/layout_bottom.php lewat hook spbu_tour_config().
 * Data langkah: roles/spbu/includes/tour_data.php.
 *
 * Letak: roles/spbu/includes/tour.php
 */

/**
 * @return array{steps:array, subKey:?string, journey:?array, startDelay:int, epoch:?string,
 *               persist:bool, screenOrder:array, screenLabels:array, restartEveryVisit:bool}
 */
function spbu_tour_config(string $screen): array
{
    // Soal 6 ("Periksa kesesuaian SPP...") pada wizard checklist punya
    // tutorial tersendiri (CHECKLIST_SOAL6_TOUR_STEPS) yang menyorot
    // sub-langkah verifikasi Produk lalu Segel lewat pop up -- lihat
    // catatan panjang di roles/spbu/includes/tour_data.php. $tourSubKey memisahkan
    // status "sudah ditonton"-nya dari tutorial umum layar 'checklist'
    // (langkah 1-15 lainnya), supaya tetap tampil pertama kali soal ini
    // dicapai walau tutorial umum sudah pernah ditonton.
    $isChecklistSoal6 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 6;
    // Soal 7 ("Isi form Metode Pengukuran...") mencakup DUA layar: daftar
    // LO di 'checklist' (step 7) dan form pengukurannya sendiri di layar
    // terpisah 'claim_loss' -- makanya subKey-nya sama ('checklist_soal7')
    // untuk KEDUANYA, supaya progres tutorialnya tetap nyambung dan
    // dilacak sebagai SATU rangkaian tutorial yang sama walau berpindah
    // layar (lihat catatan panjang di roles/spbu/includes/tour_data.php).
    $isChecklistSoal7 = ($screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 7)
        || $screen === 'claim_loss';
    // Soal 8 (Test Report): hanya satu langkah -- setelah 5 detik tanpa aksi,
    // tutorial muncul menyorot "Selanjutnya" (tidak berpindah halaman sendiri).
    $isChecklistSoal8 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 8;
    // Layar Konfirmasi LO (di luar soal checklist) dan Daftar LO berstatus
    // "Draft" (siap dikirim) masing-masing punya tutorial sendiri.
    $isChecklistSoal15 = $screen === 'konfirmasi_lo';
    // Rating AMT: langkah 2 (AMT 2, tombol "Kirim") punya tutorial sendiri.
    $isRatingAmt2 = $screen === 'rating' && (int) ($_SESSION['rating_step'] ?? 1) >= 2;
    $isLoKirim = $screen === 'lo_list' && !empty(array_filter($_SESSION['lo_draft'] ?? []));
    $tourSteps  = $isChecklistSoal6 ? CHECKLIST_SOAL6_TOUR_STEPS
        : ($isChecklistSoal7 ? CHECKLIST_SOAL7_TOUR_STEPS
        : ($isChecklistSoal8 ? CHECKLIST_SOAL8_TOUR_STEPS
        : ($isChecklistSoal15 ? CHECKLIST_SOAL15_TOUR_STEPS
        : ($isLoKirim ? LO_KIRIM_TOUR_STEPS
        : ($isRatingAmt2 ? RATING_AMT2_TOUR_STEPS
        : (SPBU_TOUR_STEPS[$screen] ?? []))))));
    $tourSubKey = $isChecklistSoal6 ? 'checklist_soal6'
        : ($isChecklistSoal7 ? 'checklist_soal7'
        : ($isChecklistSoal8 ? 'checklist_soal8'
        : ($isChecklistSoal15 ? 'checklist_soal15'
        : ($isLoKirim ? 'lo_list_kirim'
        : ($isRatingAmt2 ? 'rating_amt2' : null)))));

    return [
        'steps'        => $tourSteps,
        'subKey'       => $tourSubKey,
        'journey'      => null,    // hanya dipakai mesin tutorial AMT
        'startDelay'   => 0,       // hanya dipakai mesin tutorial AMT
        'epoch'        => null,    // hanya dipakai mesin tutorial AMT
        'persist'      => true,
        'screenOrder'  => SPBU_TOUR_SCREEN_ORDER,
        'screenLabels' => SPBU_TOUR_SCREEN_LABELS,
        // Layar simulasi bertimer: tutorial selalu mulai dari langkah pertama
        // tiap layar dibuka (lihat tutorial.js -> restartEveryVisit).
        'restartEveryVisit' => in_array($screen, ['notifikasi', 'qr_code', 'rating'], true),
    ];
}