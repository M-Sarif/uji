<?php
/* ============================================================
 * OneFIS - peran SPBU - pintu masuk semua kode peran SPBU.
 *
 * SELURUH kode peran SPBU berada di folder "roles/spbu/":
 *   includes/data.php        data domain (LO, segel, checklist, rating, aktifitas)
 *   includes/screens.php     daftar layar, judul header, tombol back, css per layar
 *   includes/tour_data.php   data tutorial terpandu (guided tour)
 *   includes/tour.php        penyusun konfigurasi tutorial per layar
 *   includes/functions.php   state session, checklist, QR simulasi, claim loss
 *   includes/actions.php     proses form POST + aksi GET
 *   views/                   satu file per layar SPBU (+ partials/header_dashboard.php)
 *   assets/                  css/, js/ (tutorial.js), img/
 *
 * Mengubah SPBU = cukup buka folder ini. Tidak ada kode AMT di sini.
 * File ini juga mendefinisikan "hook" yang dipanggil core (lihat core/roles.php).
 * ============================================================ */

define('SPBU_DIR', __DIR__);
define('SPBU_INC_DIR', __DIR__ . '/includes');
define('SPBU_VIEW_DIR', __DIR__ . '/views');
define('SPBU_ASSET_DIR', __DIR__ . '/assets');
const SPBU_URL = 'roles/spbu/assets';       // dipakai di href/src

require_once SPBU_INC_DIR . '/data.php';
require_once SPBU_INC_DIR . '/screens.php';
require_once SPBU_INC_DIR . '/tour_data.php';
require_once SPBU_INC_DIR . '/functions.php';
require_once SPBU_INC_DIR . '/actions.php';
require_once SPBU_INC_DIR . '/tour.php';

/* ------------------------------------------------------------
 * Hook untuk core (core/roles.php)
 * ------------------------------------------------------------ */

/** Gambar kartu SPBU di layar pilih peran */
function spbu_card_image(): string
{
    return SPBU_URL . '/img/ilustrasi/fuel-barrel.png';
}

/** Layar milik SPBU */
function spbu_screens(): array
{
    return array_keys(SPBU_HEADER_TITLES);
}

/** Path file view sebuah layar SPBU */
function spbu_view_file(string $screen): string
{
    return SPBU_VIEW_DIR . '/' . $screen . '.php';
}

function spbu_screen_title(string $screen): string
{
    return SPBU_HEADER_TITLES[$screen] ?? '';
}

function spbu_tutorial_text(string $screen): string
{
    return SPBU_TUTORIAL_TEXTS[$screen] ?? '';
}

function spbu_prev_screen(string $screen): ?string
{
    return SPBU_PREV_SCREEN[$screen] ?? null;
}

function spbu_next_screen(string $screen): string
{
    return SPBU_NEXT_SCREEN[$screen] ?? 'dashboard';
}

/** URL css layar SPBU (roles/spbu/assets/css/<nama>.css) */
function spbu_screen_css(string $screen): array
{
    $urls = [];
    foreach (SPBU_SCREEN_CSS[$screen] ?? [] as $name) {
        $urls[] = SPBU_URL . '/css/' . $name . '.css';
    }
    return $urls;
}

/** Layar dashboard memakai header khusus (logo + tombol ganti peran + lonceng) */
function spbu_header_partial(string $screen): ?string
{
    return $screen === 'dashboard' ? SPBU_VIEW_DIR . '/partials/header_dashboard.php' : null;
}

/** Header layar lain: tombol back + judul. Ajukan Claim Loss: judul di tengah. */
function spbu_header_style(string $screen): array
{
    $centered = $screen === 'claim_loss';
    return ['class' => $centered ? ' centered' : '', 'arrow' => false, 'spacer' => $centered];
}

function spbu_content_class(string $screen): string
{
    $hasWizardNav = in_array($screen, ['checklist', 'claim_loss', 'rating'], true);
    $isFlexCol    = $screen === 'lo_list';
    return ($hasWizardNav ? ' content-with-nav' : '') . ($isFlexCol ? ' content-flex-col' : '');
}

/** CSS global SPBU yang dimuat di <head> semua halaman (gaya tutorial terpandu) */
function spbu_global_css(): array
{
    return [SPBU_URL . '/css/tutorial.css'];
}

/** Mesin tutorial SPBU */
function spbu_tour_assets(): array
{
    return ['engine' => SPBU_URL . '/js/tutorial.js', 'css' => []];
}

/** Peran lain dipilih -> alur SPBU dimulai dari awal supaya state tidak bocor antar peran */
function spbu_on_role_change(): void
{
    spbu_reset_flow_state();
}

/** Setiap kali pengguna kembali ke dashboard, progres alur order/checklist/verifikasi dimulai dari awal lagi. */
function spbu_on_home(string $screen): bool
{
    if ($screen !== 'dashboard') {
        return false;
    }
    spbu_reset_flow_state();
    return true;
}

function spbu_accepts_post(string $action): bool
{
    return in_array($action, SPBU_POST_ACTIONS, true);
}
