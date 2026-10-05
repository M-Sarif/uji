<?php
/* ============================================================
 * OneFIS - peran AMT - pintu masuk semua kode peran AMT.
 *
 * SELURUH kode peran AMT berada di folder "roles/amt/":
 *   module.php             file ini: daftar layar, hook untuk core, router AMT
 *   includes/work.php      jam kerja (Start/End Work), lokasi, avatar
 *   includes/amt_pti_*.php Pre-Trip Inspection (PTI) + aktivasi setelah Check-In
 *   includes/amt_ship_data.php  Shipments: daftar, Detail Order, "Tiba di Lokasi"
 *   includes/amt_pbk.php   Checklist Pra-Pembongkaran (Daftar LO, 14 langkah, QR)
 *   includes/amt_verif_*.php    Verifikasi Order (Daftar LO, QR, Kode, Berhasil)
 *   includes/tutorial.php  tutorial terpandu (guided tour) AMT
 *   includes/work_ui.php   skrip beranda AMT (timer, kunci menu PTI)
 *   views/                 satu file per layar AMT (+ partials/header_home.php)
 *   assets/                css/, js/, img/
 *
 * Mengubah AMT = cukup buka folder ini. Tidak ada kode SPBU di sini.
 * Fungsi-fungsi hook di bawah dipanggil core (lihat core/roles.php).
 * ============================================================ */

define('AMT_APP_ROOT', APP_ROOT);                  // <root> aplikasi
define('AMT_DIR', __DIR__);
define('AMT_INC_DIR', __DIR__ . '/includes');
define('AMT_VIEW_DIR', __DIR__ . '/views');
define('AMT_ASSET_DIR', __DIR__ . '/assets');
const AMT_URL = 'roles/amt/assets';                // dipakai di href/src

require_once AMT_INC_DIR . '/work.php';
require_once AMT_INC_DIR . '/amt_pti_functions.php'; // PTI + aktivasi setelah Check-In
require_once AMT_INC_DIR . '/amt_ship_data.php';      // Shipments: daftar, Detail Order, SPBU
require_once AMT_INC_DIR . '/amt_pbk.php';            // Checklist Pra-Pembongkaran AMT (Daftar LO, 14 langkah + QR Code)
require_once AMT_INC_DIR . '/amt_verif_functions.php'; // Verifikasi Order (Daftar LO, QR, Kode, Berhasil)
require_once AMT_INC_DIR . '/tutorial.php'; // tutorial terpandu (guided tour) peran AMT

// Layar milik AMT selain beranda (amt_home)
const AMT_EXTRA_SCREENS = ['start_end', 'start_work', 'end_work', 'checkin', 'amt_pti', 'amt_pti_form', 'amt_pti_hasil',
    'amt_shipments', 'amt_shipment_detail', 'amt_spbu', 'amt_checklist_lo', 'amt_checklist',
    'amt_verifikasi', 'amt_verifikasi_qr', 'amt_verifikasi_kode', 'amt_verifikasi_sukses'];

// Aksi form (POST) milik AMT
const AMT_POST_ACTIONS = ['submit_start_work', 'submit_end_work', 'submit_checkin', 'submit_spbu_arrive', 'submit_pbk'];

/** Beranda AMT (mis. 'amt_home') */
function amt_home_screen(): string
{
    return ROLES['amt']['home'] ?? 'dashboard';
}

/** Gambar kartu AMT di layar pilih peran */
function amt_card_image(): string
{
    return AMT_URL . '/img/ilustrasi/empty-delivery-truck.png';
}

/** Semua layar milik AMT (hook core: amt_screens) */
function amt_screens(): array
{
    return array_merge(['amt_home'], AMT_EXTRA_SCREENS);
}

/** Path file view untuk sebuah layar AMT (hook core: amt_view_file) */
function amt_view_file(string $screen): string
{
    // Layar PTI: nama layar 'amt_pti' / 'amt_pti_form' -> file pti.php / pti_form.php
    $file = ['amt_pti' => 'pti', 'amt_pti_form' => 'pti_form', 'amt_pti_hasil' => 'pti_hasil',
             'amt_shipments' => 'shipments', 'amt_shipment_detail' => 'shipment_detail', 'amt_spbu' => 'spbu',
             'amt_checklist_lo' => 'pra_bongkar_lo', 'amt_checklist' => 'pra_bongkar'][$screen] ?? $screen;
    return AMT_VIEW_DIR . '/' . $file . '.php';
}

/** Judul header untuk layar Start/End Work */
function amt_screen_title(string $screen): string
{
    if ($screen === 'amt_home') {
        return 'Halaman AMT';
    }
    // Layar SPBU: judul mengikuti kode SPBU pengiriman yang dibuka ("SPBU 65748002")
    if ($screen === 'amt_spbu') {
        $s = amt_ship_current();
        return $s ? 'SPBU ' . $s['spbu'] : 'SPBU';
    }
    if ($screen === 'amt_checklist_lo') {
        return 'Checklist Pra-Pembongkaran';      // Daftar LO
    }
    if ($screen === 'amt_checklist') {
        return 'Checklist Pra Bongkar BBM AMT';   // wizard 14 langkah
    }
    if ($screen === 'amt_verifikasi_qr') {
        return 'Pindai Kode QR';
    }
    if (in_array($screen, AMT_VERIF_SCREENS, true)) {
        return 'Verifikasi Order';
    }
    return WORK_SCREENS[$screen] ?? '';
}

/** Tujuan tombol back untuk layar Start/End Work */
function amt_prev_screen(string $screen): ?string
{
    if ($screen === 'amt_home') {
        return 'role_select';
    }
    // Tombol back membawa id pengiriman supaya kembali ke pengiriman yang sama
    $id = amt_ship_request_id();
    if ($screen === 'amt_spbu') {
        return 'amt_shipment_detail' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    // Daftar LO kembali ke Aktifitas di SPBU; wizard 14 langkah kembali ke Daftar LO
    if ($screen === 'amt_checklist_lo') {
        return 'amt_spbu' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    if ($screen === 'amt_checklist') {
        return 'amt_checklist_lo' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    // Verifikasi Order: Daftar LO & Berhasil -> kembali ke SPBU; QR / Kode -> kembali ke Daftar LO
    if (in_array($screen, AMT_VERIF_SCREENS, true)) {
        $vid = amt_verif_ship_id();
        $q   = $vid !== '' ? '&id=' . rawurlencode($vid) : '';
        $toList = $screen === 'amt_verifikasi_qr' || $screen === 'amt_verifikasi_kode';
        return ($toList ? 'amt_verifikasi' : AMT_VERIF_RETURN_SCREEN) . $q;
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
    amt_pbk_guard($screen);   // Daftar LO + Checklist Pra Bongkar: hanya setelah "Tiba di Lokasi"
}

/* ------------------------------------------------------------
 * "Epoch" tutorial AMT: penanda siklus. Setiap kali siklus baru dimulai
 * (kembali ke index, Start Work, End Work) epoch berganti; JS
 * (roles/amt/assets/js/tutorial-amt.js) membandingkannya dengan yang tersimpan
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
    amt_verif_reset();  // Verifikasi Order ikut direset
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
            amt_verif_reset();
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
            amt_verif_reset();
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
            amt_pbk_handle_post();   // lihat roles/amt/includes/amt_pbk.php
            break;
    }
}

/**
 * Header layar AMT (selain beranda): judul DI TENGAH + tombol back berupa panah tipis "←",
 * seperti aplikasi asli. Untuk mengembalikan gaya lama (judul di kiri) pada layar tertentu,
 * kembalikan class kosong untuk layar itu di sini. Gayanya: assets/css/amt-header.css.
 */
function amt_header_style(string $screen): array
{
    return ['class' => ' centered amt-centered', 'arrow' => true, 'spacer' => true];
}

/** Beranda AMT memakai header sendiri (logo, lonceng, tombol ganti peran) */
function amt_header_partial(string $screen): ?string
{
    return $screen === amt_home_screen() ? AMT_VIEW_DIR . '/partials/header_home.php' : null;
}

function amt_content_class(string $screen): string
{
    return $screen === 'amt_home' ? ' content-amt' : '';
}

/** CSS layar AMT. Beranda AMT juga memakai role.css (core). */
function amt_screen_css(string $screen): array
{
    $map = [
        'amt_home'              => [CORE_URL . '/css/role.css', AMT_URL . '/css/amt.css'],
        'amt_pti'               => [AMT_URL . '/css/amt-pti.css'],
        'amt_pti_form'          => [AMT_URL . '/css/amt-pti.css'],
        'amt_pti_hasil'         => [AMT_URL . '/css/amt-pti.css'],
        'amt_shipments'         => [AMT_URL . '/css/amt-ship.css'],
        'amt_shipment_detail'   => [AMT_URL . '/css/amt-ship.css'],
        'amt_spbu'              => [AMT_URL . '/css/amt-ship.css'],
        'amt_verifikasi'        => [AMT_URL . '/css/amt-verif.css'],
        'amt_verifikasi_qr'     => [AMT_URL . '/css/amt-verif.css'],
        'amt_verifikasi_kode'   => [AMT_URL . '/css/amt-verif.css'],
        'amt_verifikasi_sukses' => [AMT_URL . '/css/amt-verif.css'],
    ];
    return $map[$screen] ?? [];
}

/** CSS global AMT di <head> (gaya header layar AMT) */
function amt_global_css(): array
{
    return [AMT_URL . '/css/amt-header.css'];
}

/** Mesin + gaya tutorial AMT (terpisah dari tutorial SPBU) */
function amt_tour_assets(): array
{
    return ['engine' => AMT_URL . '/js/tutorial-amt.js', 'css' => [AMT_URL . '/css/tutorial-amt.css']];
}

/** Skrip beranda AMT (menu Start/End, kunci menu, timer) - dipanggil setelah view dirender */
function amt_after_view(string $screen): void
{
    if ($screen === amt_home_screen()) {
        require AMT_INC_DIR . '/work_ui.php';
    }
}

/* ------------------------------------------------------------
 * Hook alur untuk core (core/roles.php)
 * ------------------------------------------------------------ */

/** Aksi POST milik AMT: aksi form + 'pti_action' (form PTI & ack tutorial, dikirim tanpa field 'action') */
function amt_accepts_post(string $action): bool
{
    return in_array($action, AMT_POST_ACTIONS, true) || isset($_POST['pti_action']);
}

/** Layar pilih peran dibuka => AMT dimulai dari awal: timer, Check-In, PTI, tutorial di-restart. */
function amt_on_role_select_screen(): void
{
    amt_reset_all();
}

/** Pengguna pindah ke peran lain: jam kerja AMT dihapus supaya state tidak bocor antar peran. */
function amt_on_role_change(): void
{
    unset($_SESSION['work']);
}

/** Penjagaan sebelum ada output: PTI (ack tutorial, akses, simpan jawaban) + Verifikasi Order */
function amt_bootstrap(string $screen): void
{
    amt_pti_bootstrap($screen);
    amt_verif_bootstrap($screen);
}

/** Penjagaan akses layar (GET) */
function amt_guard(string $screen): void
{
    amt_guard_work($screen);
}

/** Beranda AMT dibuka: tidak ada progres alur AMT yang di-reset, tapi tutorial dianggap "baru". */
function amt_on_home(string $screen): bool
{
    return $screen === amt_home_screen();
}
