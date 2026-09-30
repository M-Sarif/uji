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
require_once __DIR__ . '/tutorial.php'; // tutorial terpandu (guided tour) peran AMT

// Layar milik AMT yang tidak terdaftar di HEADER_TITLES (data.php)
const AMT_EXTRA_SCREENS = ['start_end', 'start_work', 'end_work', 'checkin'];

// Aksi form (POST) milik AMT
const AMT_POST_ACTIONS = ['submit_start_work', 'submit_end_work', 'submit_checkin'];

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
    return amt_owns_screen($screen)
        ? AMT_VIEW_DIR . '/' . $screen . '.php'
        : AMT_APP_ROOT . '/views/' . $screen . '.php';
}

/** Judul header untuk layar Start/End Work */
function amt_screen_title(string $screen): string
{
    return WORK_SCREENS[$screen] ?? '';
}

/** Tujuan tombol back untuk layar Start/End Work */
function amt_prev_screen(string $screen): ?string
{
    return [
        'start_end'  => amt_home_screen(),
        'start_work' => 'start_end',
        'end_work'   => 'start_end',
        'checkin'    => amt_home_screen(),
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
            $_SESSION['flash_success'] = [
                'title' => 'Check-In Berhasil',
                'body'  => 'Check-In ke-' . count($_SESSION['work']['checkins']) . ' tercatat di Riwayat Check-In.',
            ];
            go_to(amt_home_screen());
            break;
    }
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