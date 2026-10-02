<?php
/* ============================================================
 * OneFIS - AMT - titik masuk semua kode peran AMT.
 *
 * Seluruh kode peran AMT berada di folder "amt":
 *   includes/amt/  amt.php (file ini), work.php, work_ui.php, header.php, tutorial.php
 *   views/amt/     amt_home, start_end, start_work, end_work, checkin, _work_form
 *   assets/amt/    css/ (amt, work, camera, checkin), js/ (camera), Asset/ (gambar)
 *
 * index.php hanya memanggil fungsi-fungsi di bawah ini.
 * Dimuat SETELAH includes/data.php dan includes/functions.php.
 * ============================================================ */

require_once __DIR__ . '/work.php';
require_once __DIR__ . '/amt_pti_functions.php'; // PTI + aktivasi setelah Check-In
require_once __DIR__ . '/amt_ship_data.php';      // Shipments: daftar, Detail Order, SPBU
require_once __DIR__ . '/amt_pbk.php';            // Checklist Pra Bongkar BBM AMT (14 langkah + QR Code)
require_once __DIR__ . '/tutorial.php'; // tutorial terpandu (guided tour) peran AMT

// Layar milik AMT yang tidak terdaftar di HEADER_TITLES (data.php)
const AMT_EXTRA_SCREENS = ['start_end', 'start_work', 'end_work', 'checkin', 'amt_pti', 'amt_pti_form', 'amt_pti_hasil',
    'amt_shipments', 'amt_shipment_detail', 'amt_spbu', 'amt_checklist'];

// Aksi form (POST) milik AMT
const AMT_POST_ACTIONS = ['submit_start_work', 'submit_end_work', 'submit_checkin', 'submit_spbu_arrive', 'submit_pbk'];

/** Beranda AMT (mis. 'amt_home') */
function amt_home_screen(): string
{
    return ROLES['amt']['home'] ?? 'dashboard';
}

/** Apakah $screen dirender dari folder AMT? */
function amt_owns_screen(string $screen): bool
{
    return in_array($screen, AMT_SCREENS, true) || in_array($screen, AMT_EXTRA_SCREENS, true);
}

/** Path file view untuk sebuah layar (folder AMT atau views/ bawaan) */
function amt_view_file(string $screen): string
{
    // Layar PTI: nama layar 'amt_pti' / 'amt_pti_form' -> file pti.php / pti_form.php
    $file = ['amt_pti' => 'pti', 'amt_pti_form' => 'pti_form', 'amt_pti_hasil' => 'pti_hasil',
             'amt_shipments' => 'shipments', 'amt_shipment_detail' => 'shipment_detail', 'amt_spbu' => 'spbu',
             'amt_checklist' => 'pra_bongkar'][$screen] ?? $screen;
    return amt_owns_screen($screen)
        ? AMT_VIEW_DIR . '/' . $file . '.php'
        : AMT_APP_ROOT . '/views/' . $screen . '.php';
}

/** Judul header untuk layar Start/End Work */
function amt_screen_title(string $screen): string
{
    // Layar SPBU: judul mengikuti kode SPBU pengiriman yang dibuka ("SPBU 65748002")
    if ($screen === 'amt_spbu') {
        $s = amt_ship_current();
        return $s ? 'SPBU ' . $s['spbu'] : 'SPBU';
    }
    if ($screen === 'amt_checklist') {
        return 'Checklist Pra Bongkar BBM AMT';
    }
    return WORK_SCREENS[$screen] ?? '';
}

/** Tujuan tombol back untuk layar Start/End Work */
function amt_prev_screen(string $screen): ?string
{
    // Tombol back membawa id pengiriman supaya kembali ke pengiriman yang sama
    $id = amt_ship_request_id();
    if ($screen === 'amt_spbu') {
        return 'amt_shipment_detail' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    // Tombol back checklist kembali ke Aktifitas di SPBU pengiriman yang sama
    if ($screen === 'amt_checklist') {
        return 'amt_spbu' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    return [
        'start_end'  => amt_home_screen(),
        'start_work' => 'start_end',
        'end_work'   => 'start_end',
        'checkin'    => amt_home_screen(),
        'amt_pti'      => amt_home_screen(),
        'amt_pti_form' => 'amt_pti',
        'amt_pti_hasil' => 'amt_pti',
        'amt_shipments' => amt_home_screen(),
        'amt_shipment_detail' => 'amt_shipments',
    ][$screen] ?? null;
}

/** Apakah menu beranda AMT terkunci? (key = 'checkin' | 'pti' | 'checkout' | ...)
 *  - menu di WORK_LOCKED_MENUS selalu terkunci (belum tersedia)
 *  - menu di WORK_GATED_SCREENS terkunci selama timer belum berjalan */
function amt_menu_locked(string $key): bool
{
    if (in_array($key, WORK_LOCKED_MENUS, true)) {
        return true;
    }
    // PTI aktif setelah Check-In + tutorial DCU dibaca (atau otomatis, lihat amt_pti_unlocked)
    if ($key === 'pti') {
        return !amt_pti_unlocked();
    }
    return in_array($key, WORK_GATED_SCREENS, true) && !work_is_running();
}

/** Penjagaan akses (GET): menu terkunci saat timer belum jalan,
 *  Start Work hanya saat timer belum jalan, End Work hanya saat jalan. */
function amt_guard_work(string $screen): void
{
    if (in_array($screen, WORK_GATED_SCREENS, true) && !work_is_running()) {
        go_to(amt_home_screen());
    }
    if ($screen === 'start_work' && work_is_running()) {
        go_to('start_end');
    }
    if ($screen === 'end_work' && !work_is_running()) {
        go_to('start_end');
    }
    amt_pbk_guard($screen);   // Checklist Pra Bongkar: hanya setelah "Tiba di Lokasi"
}

/* ------------------------------------------------------------
 * "Epoch" tutorial AMT: penanda siklus. Setiap kali siklus baru dimulai
 * (kembali ke index, Start Work, End Work) epoch berganti; JS
 * (assets/amt/js/tutorial-amt.js) membandingkannya dengan yang tersimpan
 * di browser dan otomatis menghapus catatan "tutorial sudah selesai /
 * dilewati" bila berbeda. Jadi tutorial selalu muncul lagi di siklus baru.
 * ------------------------------------------------------------ */
function amt_tour_epoch(): string
{
    if (empty($_SESSION['amt_tour_epoch'])) {
        $_SESSION['amt_tour_epoch'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['amt_tour_epoch'];
}

function amt_tour_bump(): void
{
    $_SESSION['amt_tour_epoch'] = bin2hex(random_bytes(6));
}

/** Mulai ulang SEMUA state AMT: timer kerja, Check-In, PTI, alur DCU, dan tutorial. */
function amt_reset_all(): void
{
    unset($_SESSION['work'], $_SESSION['flash_success']);
    amt_pti_reset();
    amt_spbu_reset();   // status "Tiba di Lokasi" ikut direset
    amt_tour_bump();
}

/** Proses form Start Work / End Work (pola Post/Redirect/Get) */
function amt_handle_post(string $action): void
{
    switch ($action) {
        case 'submit_start_work':
            // Aktivitas + foto wajib, lalu timer berjalan
            $akt = (string) ($_POST['aktivitas'] ?? '');
            if (work_is_running()
                || !work_post_in_range()
                || !in_array($akt, WORK_ACTIVITIES, true)
                || ($_POST['photo'] ?? '') !== '1'
            ) {
                go_to('start_work');
            }
            amt_pti_reset(); // shift baru: PTI & tutorial mulai dari awal
            amt_spbu_reset();
            amt_tour_bump();
            $_SESSION['work'] = [
                'started_at'  => time(),
                'ended_at'    => null,
                'activity'    => $akt,
                'start_photo' => true,
                'end_photo'   => false,
            ];
            go_to(amt_home_screen());
            break;

        case 'submit_end_work':
            // Aktivitas mengikuti Start Work (tidak bisa diubah)
            if (!work_is_running() || !work_post_in_range() || ($_POST['photo'] ?? '') !== '1') {
                go_to('end_work');
            }
            amt_pti_reset();
            amt_spbu_reset();
            amt_tour_bump();
            $_SESSION['work']['ended_at'] = time();
            $_SESSION['work']['end_photo'] = true;
            go_to(amt_home_screen());
            break;

        case 'submit_checkin':
            // Check-In hanya boleh saat timer berjalan; aktivitas + foto wajib
            $akt = (string) ($_POST['aktivitas'] ?? '');
            if (!work_is_running()
                || !work_post_in_range()
                || !in_array($akt, CHECKIN_ACTIVITIES, true)
                || ($_POST['photo'] ?? '') !== '1'
            ) {
                go_to('checkin');
            }
            // Foto disimpan (dataURL) supaya bisa dilihat lagi lewat "Lihat Foto"
            // di tab Riwayat Check-In. Hanya gambar yang ukurannya wajar.
            $photo = (string) ($_POST['photo_data'] ?? '');
            if (!preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$#', $photo)
                || strlen($photo) > 3000000
            ) {
                $photo = '';
            }
            $_SESSION['work']['checkins'][] = [
                'at'       => time(),
                'activity' => $akt,
                'photo'    => $photo,
            ];
            amt_flow_checkin_success(); // mulai alur: tutorial DCU -> PTI aktif
            $_SESSION['flash_success'] = [
                'title' => 'Check-In Berhasil',
                'body'  => 'Check-In ke-' . count($_SESSION['work']['checkins']) . ' tercatat di Riwayat Check-In.',
            ];
            go_to(amt_home_screen());
            break;

        case 'submit_spbu_arrive':
            // "Ya, pengiriman telah tiba": hanya untuk pengiriman yang sedang berjalan,
            // saat timer kerja berjalan, dan posisi berada dalam radius SPBU.
            if (!work_is_running()) {
                go_to(amt_home_screen());
            }
            $s = amt_ship_find(amt_ship_post_id() ?: null);
            if ($s === null || $s['status'] !== 'sedang') {
                go_to('amt_shipments');
            }
            $back = ['id' => $s['id']];
            if (amt_spbu_arrived($s)) {
                go_to('amt_spbu', $back);
            }
            if (!isset($_POST['lat'], $_POST['lng'])
                || !is_numeric($_POST['lat']) || !is_numeric($_POST['lng'])
                || !amt_spbu_in_range($s, (float) $_POST['lat'], (float) $_POST['lng'])
            ) {
                go_to('amt_spbu', $back);   // lokasi belum sesuai: tidak dicatat
            }
            amt_spbu_mark_arrived($s, (float) $_POST['lat'], (float) $_POST['lng']);
            $_SESSION['flash_success'] = [
                'title' => 'Tiba di Lokasi Tercatat',
                'body'  => 'Kedatangan di SPBU ' . $s['spbu'] . ' sudah dicatat.',
            ];
            go_to('amt_spbu', $back);
            break;

        case 'submit_pbk':
            amt_pbk_handle_post();   // lihat includes/amt/amt_pbk.php
            break;
    }
}

/**
 * Header layar AMT (selain beranda): judul DI TENGAH + tombol back berupa panah tipis "←",
 * seperti aplikasi asli. Untuk mengembalikan gaya lama (judul di kiri) pada layar tertentu,
 * kembalikan false untuk layar itu di sini.
 */
function amt_header_centered(string $screen): bool
{
    return current_role() === 'amt' && $screen !== amt_home_screen();
}

/** Nama file CSS AMT -> path/URL. SCREEN_CSS memakai awalan "amt/" untuk CSS di folder ini. */
function amt_css_href(string $cssFile): ?string
{
    return strpos($cssFile, 'amt/') === 0 ? AMT_URL . '/css/' . substr($cssFile, 4) . '.css' : null;
}

/** Skrip beranda AMT (menu Start/End, kunci menu, timer) - dipanggil setelah view dirender */
function amt_after_view(string $screen): void
{
    // Peran dibaca dari session (bukan variabel $role di index.php, yang bisa
    // tertimpa oleh perulangan di view role_select).
    if (current_role() === 'amt' && $screen === amt_home_screen()) {
        require AMT_INC_DIR . '/work_ui.php';
    }
}