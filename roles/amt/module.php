<?php

define('AMT_APP_ROOT', APP_ROOT);
define('AMT_DIR', __DIR__);
define('AMT_INC_DIR', __DIR__ . '/includes');
define('AMT_VIEW_DIR', __DIR__ . '/views');
define('AMT_ASSET_DIR', __DIR__ . '/assets');
const AMT_URL = 'roles/amt/assets';

require_once AMT_INC_DIR . '/work.php';
require_once AMT_INC_DIR . '/amt_pti_functions.php';
require_once AMT_INC_DIR . '/amt_ship_data.php';
require_once AMT_INC_DIR . '/amt_pbk.php';
require_once AMT_INC_DIR . '/amt_verif_functions.php';
require_once AMT_INC_DIR . '/amt_sj.php';
require_once AMT_INC_DIR . '/amt_rating.php';
require_once AMT_INC_DIR . '/amt_kendala.php';
require_once AMT_INC_DIR . '/amt_out.php';
require_once AMT_INC_DIR . '/amt_performance.php';
require_once AMT_INC_DIR . '/tutorial.php';

const AMT_EXTRA_SCREENS = ['start_end', 'start_work', 'end_work', 'checkin', 'amt_pti', 'amt_pti_form', 'amt_pti_hasil',
    'amt_shipments', 'amt_shipment_detail', 'amt_spbu', 'amt_checklist_lo', 'amt_checklist',
    'amt_verifikasi', 'amt_verifikasi_qr', 'amt_verifikasi_kode', 'amt_verifikasi_sukses',
    'amt_surat_jalan', 'amt_rating', 'checkout', 'amt_performance', 'amt_kendala'];

const AMT_POST_ACTIONS = ['submit_start_work', 'submit_end_work', 'submit_checkin', 'submit_spbu_arrive', 'submit_pbk', 'submit_sj', 'submit_rating', 'finish_order', 'submit_checkout', 'submit_kendala'];

function amt_home_screen(): string
{
    return ROLES['amt']['home'] ?? 'dashboard';
}

function amt_card_image(): string
{
    return AMT_URL . '/img/ilustrasi/empty-delivery-truck.png';
}

function amt_screens(): array
{
    return array_merge(['amt_home'], AMT_EXTRA_SCREENS);
}

function amt_view_file(string $screen): string
{
    $file = ['amt_pti' => 'pti', 'amt_pti_form' => 'pti_form', 'amt_pti_hasil' => 'pti_hasil',
             'amt_shipments' => 'shipments', 'amt_shipment_detail' => 'shipment_detail', 'amt_spbu' => 'spbu',
             'amt_checklist_lo' => 'pra_bongkar_lo', 'amt_checklist' => 'pra_bongkar',
             'amt_surat_jalan' => 'surat_jalan', 'amt_performance' => 'performance',
             'amt_kendala' => 'kendala'][$screen] ?? $screen;
    return AMT_VIEW_DIR . '/' . $file . '.php';
}

function amt_screen_title(string $screen): string
{
    if ($screen === 'amt_home') {
        return 'Halaman AMT';
    }
    if ($screen === 'amt_spbu') {
        $s = amt_ship_current();
        return $s ? 'SPBU ' . $s['spbu'] : 'SPBU';
    }
    if ($screen === 'amt_checklist_lo') {
        return 'Checklist Pra-Pembongkaran';
    }
    if ($screen === 'amt_checklist') {
        return 'Checklist Pra Bongkar BBM AMT';
    }
    if ($screen === 'amt_verifikasi_qr') {
        return 'Pindai Kode QR';
    }
    if ($screen === 'amt_surat_jalan') {
        return 'Surat Jalan';
    }
    if ($screen === 'amt_performance') {
        return 'Performance';
    }
    if ($screen === 'amt_rating') {
        return 'Beri Penilaian';
    }
    if ($screen === 'amt_kendala') {
        return 'Laporkan Kendala';
    }
    if (in_array($screen, AMT_VERIF_SCREENS, true)) {
        return 'Verifikasi Order';
    }
    return WORK_SCREENS[$screen] ?? '';
}

function amt_prev_screen(string $screen): ?string
{
    if ($screen === 'amt_home') {
        return 'role_select';
    }
    $id = amt_ship_request_id();
    if ($screen === 'amt_spbu') {
        return 'amt_shipment_detail' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    if ($screen === 'amt_checklist_lo') {
        return 'amt_spbu' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    if ($screen === 'amt_checklist') {
        return 'amt_checklist_lo' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
    if ($screen === 'amt_kendala') {
        return (($_GET['from'] ?? '') === 'detail' && $id !== '') ? 'amt_shipment_detail&id=' . rawurlencode($id) : amt_home_screen();
    }
    if ($screen === 'amt_surat_jalan' || $screen === 'amt_rating') {
        return 'amt_spbu' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    }
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
        'checkout'   => amt_home_screen(),
        'amt_pti'      => amt_home_screen(),
        'amt_pti_form' => 'amt_pti',
        'amt_pti_hasil' => 'amt_pti',
        'amt_shipments' => amt_home_screen(),
        'amt_shipment_detail' => 'amt_shipments',
        'amt_performance' => amt_home_screen(),
    ][$screen] ?? null;
}

function amt_menu_locked(string $key): bool
{
    if (in_array($key, WORK_LOCKED_MENUS, true)) {
        return true;
    }
    if ($key === 'checkout') {
        return !amt_out_unlocked();
    }
    if ($key === 'pti') {
        return !amt_pti_unlocked();
    }
    return in_array($key, WORK_GATED_SCREENS, true) && !work_is_running();
}

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
    amt_pbk_guard($screen);
    amt_sj_guard($screen);
    amt_rating_guard($screen);
    amt_kendala_guard($screen);
    amt_out_guard($screen);
}

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

function amt_tour_fresh(): string
{
    if (empty($_SESSION['amt_tour_fresh'])) {
        $_SESSION['amt_tour_fresh'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['amt_tour_fresh'];
}

function amt_tour_fresh_bump(bool $hard = false): void
{
    $_SESSION['amt_tour_fresh'] = ($hard ? 'r' : 'n') . bin2hex(random_bytes(6));
}

function amt_tour_hard_reset(): void
{
    amt_tour_off_forget(true);
    amt_tour_finished_forget();
    amt_reset_all();
    amt_tour_fresh_bump(true);
}

function amt_reset_all(): void
{
    unset($_SESSION['work'], $_SESSION['flash_success']);
    amt_pti_reset();
    amt_spbu_reset();
    amt_verif_reset();
    amt_out_reset();
    amt_kendala_reset();
    amt_tour_bump();
}

function amt_wants_json(): bool
{
    return stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
}

function amt_work_success(string $title, string $body, ?string $button = null): void
{
    if (amt_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'ok'     => true,
            'title'  => $title,
            'body'   => $body,
            'button' => $button,
            'next'   => app_screen_url(amt_home_screen()),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_SESSION['flash_success'] = ['title' => $title, 'body' => $body];
    go_to(amt_home_screen());
}

function amt_handle_post(string $action): void
{
    switch ($action) {
        case 'submit_start_work':
            $akt = (string) ($_POST['aktivitas'] ?? '');
            if (work_is_running()
                || !work_post_in_range()
                || !in_array($akt, WORK_ACTIVITIES, true)
                || ($_POST['photo'] ?? '') !== '1'
            ) {
                go_to('start_work');
            }
            amt_pti_reset();
            amt_spbu_reset();
            amt_verif_reset();
            amt_out_reset();
            amt_kendala_reset();
            amt_tour_bump();
            $_SESSION['work'] = [
                'started_at'  => time(),
                'ended_at'    => null,
                'activity'    => $akt,
                'start_photo' => true,
                'end_photo'   => false,
                'active_acc'   => 0,
                'active_since' => 0,
            ];
            amt_work_success('Berhasil Mulai Kerja!', 'Anda dapat menekan Mulai Ritase sekarang.');
            break;

        case 'submit_end_work':
            if (!work_is_running() || !work_post_in_range() || ($_POST['photo'] ?? '') !== '1') {
                go_to('end_work');
            }
            amt_pti_reset();
            amt_spbu_reset();
            amt_verif_reset();
            amt_out_reset();
            amt_kendala_reset();
            amt_tour_bump();
            work_active_stop();
            $_SESSION['work']['ended_at'] = time();
            $_SESSION['work']['end_photo'] = true;
            amt_work_success('Berhasil Selesai Kerja!', 'Jam kerja Anda sudah berakhir. Terima kasih atas kerja keras Anda hari ini.');
            break;

        case 'submit_checkin':
            $akt = (string) ($_POST['aktivitas'] ?? '');
            if (!work_is_running()
                || !work_post_in_range()
                || !in_array($akt, CHECKIN_ACTIVITIES, true)
                || ($_POST['photo'] ?? '') !== '1'
            ) {
                go_to('checkin');
            }
            $photo = (string) ($_POST['photo_data'] ?? '');
            if (!preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$#', $photo)
                || strlen($photo) > 3000000
            ) {
                $photo = '';
            }
            amt_out_clear_after();
            $_SESSION['work']['checkins'][] = [
                'at'       => time(),
                'activity' => $akt,
                'photo'    => $photo,
            ];
            work_active_resume();
            amt_flow_checkin_success();
            $nCheckin = count($_SESSION['work']['checkins']);
            amt_work_success(
                'Berhasil Mulai Ritase ' . ucfirst(amt_out_ordinal($nCheckin)),
                'Silakan lakukan pre-trip MT yang akan dikendarai untuk pengiriman.'
            );
            break;

        case 'submit_spbu_arrive':
            if (!amt_work_ok()) {
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
                go_to('amt_spbu', $back);
            }
            amt_spbu_mark_arrived($s, (float) $_POST['lat'], (float) $_POST['lng']);
            $_SESSION['flash_success'] = [
                'title' => 'Tiba di Lokasi Tercatat',
                'body'  => 'Kedatangan di SPBU ' . $s['spbu'] . ' sudah dicatat.',
            ];
            go_to('amt_spbu', $back);
            break;

        case 'submit_pbk':
            amt_pbk_handle_post();
            break;

        case 'submit_sj':
            amt_sj_handle_post();
            break;

        case 'submit_rating':
            amt_rating_handle_post();
            break;

        case 'finish_order':
            amt_order_finish_post();
            break;

        case 'submit_checkout':
            amt_out_handle_post();
            break;

        case 'submit_kendala':
            amt_kendala_handle_post();
            break;
    }
}

function amt_header_style(string $screen): array
{
    return ['class' => ' centered amt-centered', 'arrow' => true, 'spacer' => true];
}

function amt_header_partial(string $screen): ?string
{
    return $screen === amt_home_screen() ? AMT_VIEW_DIR . '/partials/header_home.php' : null;
}

function amt_content_class(string $screen): string
{
    return $screen === 'amt_home' ? ' content-amt' : '';
}

function amt_screen_css(string $screen): array
{
    $map = [
        'amt_home'              => [CORE_URL . '/css/role.css', AMT_URL . '/css/amt.css', AMT_URL . '/css/checkout.css', AMT_URL . '/css/amt-route.css'],
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
        'amt_surat_jalan'       => [AMT_URL . '/css/amt-sj.css'],
        'amt_rating'            => [AMT_URL . '/css/amt-rating.css'],
        'amt_kendala'           => [AMT_URL . '/css/camera.css', AMT_URL . '/css/amt-kendala.css'],
        'amt_performance'       => [AMT_URL . '/css/amt-performance.css'],
    ];
    return $map[$screen] ?? [];
}

function amt_global_css(): array
{
    return [AMT_URL . '/css/amt-header.css'];
}

function amt_tour_assets(): array
{
    return ['engine' => AMT_URL . '/js/tutorial-amt.js', 'css' => [AMT_URL . '/css/tutorial-amt.css']];
}

function amt_after_view(string $screen): void
{
    if ($screen === amt_home_screen()) {
        require AMT_INC_DIR . '/work_ui.php';
    }
}

function amt_accepts_post(string $action): bool
{
    return in_array($action, AMT_POST_ACTIONS, true) || isset($_POST['pti_action']) || isset($_POST['out_action']);
}

function amt_on_role_select_screen(): void
{
    amt_tour_off_forget();
    amt_reset_all();
    amt_tour_fresh_bump();
}

function amt_on_role_change(): void
{
    unset($_SESSION['work'], $_SESSION['amt_out']);
}

function amt_bootstrap(string $screen): void
{
    if ($screen === amt_home_screen() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['tour_reset'])) {
        amt_tour_hard_reset();
        go_to(amt_home_screen());
    }
    amt_out_bootstrap($screen);
    amt_pti_bootstrap($screen);
    amt_verif_bootstrap($screen);
}

function amt_guard(string $screen): void
{
    amt_guard_work($screen);
}

function amt_on_home(string $screen): bool
{
    return $screen === amt_home_screen();
}