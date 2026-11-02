<?php
define('SPBU_DIR', __DIR__);
define('SPBU_INC_DIR', __DIR__ . '/includes');
define('SPBU_VIEW_DIR', __DIR__ . '/views');
define('SPBU_ASSET_DIR', __DIR__ . '/assets');
const SPBU_URL = 'roles/spbu/assets';

require_once SPBU_INC_DIR . '/data.php';
require_once SPBU_INC_DIR . '/screens.php';
require_once SPBU_INC_DIR . '/tour_data.php';
require_once SPBU_INC_DIR . '/functions.php';
require_once SPBU_INC_DIR . '/actions.php';
require_once SPBU_INC_DIR . '/tour.php';

function spbu_card_image(): string
{
    return SPBU_URL . '/img/ilustrasi/fuel-barrel.png';
}

function spbu_screens(): array
{
    return array_keys(SPBU_HEADER_TITLES);
}

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

function spbu_screen_css(string $screen): array
{
    $urls = [];
    foreach (SPBU_SCREEN_CSS[$screen] ?? [] as $name) {
        $urls[] = SPBU_URL . '/css/' . $name . '.css';
    }
    return $urls;
}

function spbu_header_partial(string $screen): ?string
{
    if ($screen === 'claim_scan_qr') {
        return SPBU_VIEW_DIR . '/partials/header_scan_qr.php';
    }
    return $screen === 'dashboard' ? SPBU_VIEW_DIR . '/partials/header_dashboard.php' : null;
}

function spbu_header_style(string $screen): array
{

    $centered = in_array($screen, ['claim_loss', 'claim_upload', 'verification', 'konfirmasi_lo'], true);

    return ['class' => $centered ? ' centered' : '', 'arrow' => in_array($screen, ['verification', 'konfirmasi_lo', 'claim_upload'], true), 'spacer' => $centered];
}

function spbu_content_class(string $screen): string
{
    $hasWizardNav = in_array($screen, ['checklist', 'claim_loss', 'rating', 'konfirmasi_lo', 'claim_upload'], true);
    $isFlexCol    = $screen === 'lo_list';
    $isScan       = $screen === 'claim_scan_qr';
    return ($hasWizardNav ? ' content-with-nav' : '') . ($isFlexCol ? ' content-flex-col' : '') . ($isScan ? ' content-scan' : '');
}

function spbu_global_css(): array
{
    return [SPBU_URL . '/css/tutorial.css'];
}

function spbu_tour_assets(): array
{
    return ['engine' => SPBU_URL . '/js/tutorial.js', 'css' => []];
}

function spbu_on_role_select_screen(): void
{
    unset($_SESSION['spbu_active_card_off']);
    spbu_tour_bump();
    spbu_tour_fresh_bump();
    unset($_SESSION['spbu_tour_finished']);
}

function spbu_on_role_change(): void
{
    unset($_SESSION['spbu_active_card_off']);
    spbu_reset_flow_state();
}

function spbu_on_home(string $screen): bool
{
    if ($screen !== 'dashboard') {
        return false;
    }

    if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
        $_SESSION['spbu_tour_finished'] = true;
        $_SESSION['spbu_active_card_off'] = true;
    }
    spbu_reset_flow_state();
    return true;
}

function spbu_accepts_post(string $action): bool
{
    return in_array($action, SPBU_POST_ACTIONS, true);
}

function spbu_guard(string $screen): void
{
    if (in_array($screen, ['claim_upload', 'claim_scan_qr'], true) && empty(spbu_claim_lo_ids())) {
        go_to('checklist', ['step' => 7]);
    }
}