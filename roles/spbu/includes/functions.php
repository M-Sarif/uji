<?php
function spbu_init_session(): void
{
    if (!isset($_SESSION['order'])) {
        $_SESSION['order'] = [
            'no_spbu'      => '3210829 - PT Lorem Ipsum',
            'ship_to'      => '1234567',
            'terminal'     => 'Depot Plumpang',
            'jenis'        => 'Reguler',
            'tanggal'      => '25/05/2026',
            'produk_added' => false,
            'produk'       => 'PERTALITE',
            'qty'          => 8000,
        ];
    }
    if (!isset($_SESSION['lo_checked'])) {

        $_SESSION['lo_checked'] = [];
    }
    if (!isset($_SESSION['lo_draft'])) {

        $_SESSION['lo_draft'] = [];
    }
    if (!isset($_SESSION['lo_done'])) {

        $_SESSION['lo_done'] = [];
    }
    if (!isset($_SESSION['checklist_step'])) {
        $_SESSION['checklist_step'] = 1;
    }
    if (!isset($_SESSION['checklist_answers'])) {

        $_SESSION['checklist_answers'] = [];
    }
    if (!isset($_SESSION['checklist_answers_lawan'])) {

        $_SESSION['checklist_answers_lawan'] = [];
    }
    if (!isset($_SESSION['spp_produk'])) {

        $_SESSION['spp_produk'] = [];
    }
    if (!isset($_SESSION['spp_segel'])) {

        $_SESSION['spp_segel'] = [];
    }
    if (!isset($_SESSION['lo_form'])) {

        $_SESSION['lo_form'] = [];
    }
    if (!isset($_SESSION['lo_form_draft'])) {

        $_SESSION['lo_form_draft'] = [];
    }
    if (!isset($_SESSION['claim_submit'])) {

        $_SESSION['claim_submit'] = ['lo_ids' => []];
    }
    if (!isset($_SESSION['claim_docs'])) {

        $_SESSION['claim_docs'] = ['dipstick' => null, 'qr' => null];
    }
    if (!isset($_SESSION['lo_bongkar'])) {

        $_SESSION['lo_bongkar'] = [];
    }
    if (!isset($_SESSION['ratings'])) {

        $blank = [
            'overall'     => 0,
            'safety'      => 0,
            'sarfas'      => 0,
            'komunikasi'  => 0,
            'operasional' => 0,
            'layanan'     => 0,
            'review'      => '',
        ];
        $_SESSION['ratings'] = [
            'amt_ok'  => $blank,
            'amt2_ok' => $blank,
        ];
    }
    if (!isset($_SESSION['rating_step'])) {

        $_SESSION['rating_step'] = 1;
    }
    if (!isset($_SESSION['mt_ok'])) {
        $_SESSION['mt_ok'] = null;
    }
    if (!isset($_SESSION['amt_ok'])) {
        $_SESSION['amt_ok'] = null;
    }
    if (!isset($_SESSION['amt2_ok'])) {
        $_SESSION['amt2_ok'] = null;
    }
    if (!isset($_SESSION['arrival_time'])) {
        $_SESSION['arrival_time'] = null;
    }
    if (!isset($_SESSION['activity_done'])) {

        $_SESSION['activity_done'] = 0;
    }
}

function spbu_active_shipment_visible(): bool
{
    return empty($_SESSION['spbu_active_card_off']);
}

function set_activity_done(int $jumlah): void
{
    $current = (int) ($_SESSION['activity_done'] ?? 0);
    $_SESSION['activity_done'] = max($current, min($jumlah, count(ACTIVITY_STEPS)));
}

function spbu_reset_flow_state(): void
{
    spbu_claim_remove_dipstick();
    unset(
        $_SESSION['order'],
        $_SESSION['lo_checked'],
        $_SESSION['lo_draft'],
        $_SESSION['lo_done'],
        $_SESSION['checklist_step'],
        $_SESSION['checklist_answers'],
        $_SESSION['checklist_answers_lawan'],
        $_SESSION['spp_produk'],
        $_SESSION['spp_segel'],
        $_SESSION['lo_form'],
        $_SESSION['lo_form_draft'],
        $_SESSION['claim_submit'],
        $_SESSION['claim_docs'],
        $_SESSION['claim_error'],
        $_SESSION['claim_scan_error'],
        $_SESSION['lo_bongkar'],
        $_SESSION['ratings'],
        $_SESSION['rating_step'],
        $_SESSION['mt_ok'],
        $_SESSION['amt_ok'],
        $_SESSION['amt2_ok'],
        $_SESSION['arrival_time'],
        $_SESSION['arrival_error'],
        $_SESSION['activity_done'],
        $_SESSION['arrive_unlock_at']
    );
    spbu_tour_bump();
    spbu_init_session();
}

function spbu_tour_epoch(): string
{
    if (empty($_SESSION['spbu_tour_epoch'])) {
        $_SESSION['spbu_tour_epoch'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['spbu_tour_epoch'];
}

function spbu_tour_bump(): void
{
    $_SESSION['spbu_tour_epoch'] = bin2hex(random_bytes(6));
}

function spbu_tour_fresh(): string
{
    if (empty($_SESSION['spbu_tour_fresh'])) {
        $_SESSION['spbu_tour_fresh'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['spbu_tour_fresh'];
}

function spbu_tour_fresh_bump(): void
{
    $_SESSION['spbu_tour_fresh'] = bin2hex(random_bytes(6));
}

function checklist_answer(int $step): ?string
{
    return $_SESSION['checklist_answers'][$step] ?? null;
}

function checklist_answer_lawan(int $step): ?string
{
    return $_SESSION['checklist_answers_lawan'][$step] ?? null;
}

function checklist_step_done(int $step, array $activeLoIds): bool
{
    $type = CHECKLIST_STEPS[$step]['type'] ?? '';

    switch ($type) {
        case 'self_action_photo':
        case 'action':
        case 'photo':
            return checklist_answer($step) !== null;

        case 'dual_verif':
            return checklist_answer($step) !== null
                && checklist_answer_lawan($step) !== null;

        case 'form_spp':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['spp_produk'][$loId])) {
                    return false;
                }
            }
            foreach (SEGEL_LIST as $segel) {
                if (empty($_SESSION['spp_segel'][$segel])) {
                    return false;
                }
            }
            return true;

        case 'form_ukur':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['lo_form'][$loId])) {
                    return false;
                }
            }
            return true;

        case 'test_report':
            return true;

        case 'konfirmasi_lo':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['lo_bongkar'][$loId])) {
                    return false;
                }
            }
            return true;

        default:
            return true;
    }
}

function generate_qr_matrix(string $code, int $size = 21): array
{
    $seed = 0;
    foreach (str_split($code) as $ch) {
        $seed += ord($ch);
    }

    $grid = [];
    for ($y = 0; $y < $size; $y++) {
        $row = [];
        for ($x = 0; $x < $size; $x++) {
            $row[] = ((($x * 31 + $y * 17 + $seed * 7 + $x * $y) % 5) < 2);
        }
        $grid[] = $row;
    }

    $drawFinder = function (array &$grid, int $ox, int $oy): void {
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $border = ($x === 0 || $x === 6 || $y === 0 || $y === 6);
                $inner  = ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4);
                $grid[$oy + $y][$ox + $x] = $border || $inner;
            }
        }

        $size = count($grid);
        for ($y = -1; $y <= 7; $y++) {
            for ($x = -1; $x <= 7; $x++) {
                $isBorderRing = ($y === -1 || $y === 7 || $x === -1 || $x === 7);
                $gy = $oy + $y;
                $gx = $ox + $x;
                if ($isBorderRing && $gy >= 0 && $gy < $size && $gx >= 0 && $gx < $size) {
                    $grid[$gy][$gx] = false;
                }
            }
        }
    };

    $drawFinder($grid, 0, 0);
    $drawFinder($grid, $size - 7, 0);
    $drawFinder($grid, 0, $size - 7);

    return $grid;
}

function hitung_vcf(float $densityObs, float $suhuObs): float
{
    if ($densityObs <= 0.0) {

        return 1.0;
    }

    $densityKgM3 = $densityObs < 10.0 ? $densityObs * 1000.0 : $densityObs;

    $alpha15 = 341.0957 / ($densityKgM3 ** 2);
    $deltaT  = $suhuObs - 15.0;

    return exp(-$alpha15 * $deltaT * (1 + 0.8 * $alpha15 * $deltaT));
}

function hitung_claim_loss(string $metode, array $nilai, string $loId): float
{
    if ($metode === 'flowmeter') {
        $qtyOrder    = (float) preg_replace('/[^0-9]/', '', LO_LIST[$loId]['qty'] ?? '0');
        $volumeMeter = (float) ($nilai['volume_meter'] ?? 0);

        return max(0.0, $qtyOrder - $volumeMeter);
    }

    $kompartemen = (int) ($nilai['kompartemen'] ?? 0);
    $literPerMm  = COMPARTMENT_TERA_RATE[$kompartemen] ?? COMPARTMENT_TERA_RATE['default'];
    $selisihMm   = abs(($nilai['level_spp'] ?? 0) - ($nilai['level_sebelum_bongkar'] ?? 0));

    return (float) ($selisihMm * $literPerMm);
}

function format_liter_asli(float $nilai): string
{
    $str = json_encode($nilai);
    if ($str === false) {
        return (string) $nilai;
    }

    return substr($str, -2) === '.0' ? substr($str, 0, -2) : $str;
}

const CLAIM_UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const CLAIM_UPLOAD_EXT       = ['pdf', 'jpg', 'jpeg', 'png'];
const CLAIM_QR_PREFIX        = 'ONEFIS-PBK';

function spbu_claim_upload_dir(): string
{
    return APP_ROOT . '/uploads/claim';
}

function spbu_claim_start(array $loIds): void
{
    $valid = [];
    foreach ($loIds as $id) {
        $id = (string) $id;
        if (array_key_exists($id, LO_LIST) && !in_array($id, $valid, true)) {
            $valid[] = $id;
        }
    }
    sort($valid);

    $current = (array) ($_SESSION['claim_submit']['lo_ids'] ?? []);
    sort($current);
    if ($current !== $valid) {
        spbu_claim_remove_dipstick();
        $_SESSION['claim_docs'] = ['dipstick' => null, 'qr' => null];
    }
    $_SESSION['claim_submit'] = ['lo_ids' => $valid];
}

function spbu_claim_lo_ids(): array
{
    return array_values((array) ($_SESSION['claim_submit']['lo_ids'] ?? []));
}

function spbu_claim_docs(): array
{
    $d = $_SESSION['claim_docs'] ?? [];
    return ['dipstick' => $d['dipstick'] ?? null, 'qr' => $d['qr'] ?? null];
}

function spbu_claim_ready(): bool
{
    $d = spbu_claim_docs();
    return $d['dipstick'] !== null && $d['qr'] !== null;
}

function spbu_claim_remove_dipstick(): void
{
    $file = $_SESSION['claim_docs']['dipstick']['file'] ?? null;
    if (is_string($file) && $file !== '' && preg_match('/^dipstick_[a-f0-9]{16}\.(pdf|jpe?g|png)$/', $file)) {
        $path = spbu_claim_upload_dir() . '/' . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    if (isset($_SESSION['claim_docs'])) {
        $_SESSION['claim_docs']['dipstick'] = null;
    }
}

function spbu_claim_store_upload($file): ?string
{
    if (!is_array($file) || !isset($file['error'])) {
        return 'Dokumen tidak terbaca. Silakan coba lagi.';
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return 'Pilih dokumen terlebih dahulu.';
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran dokumen terlalu besar (maksimal 5 MB).';
        default:
            return 'Dokumen gagal diupload. Silakan coba lagi.';
    }
    if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] <= 0) {
        return 'Dokumen tidak valid.';
    }
    if ((int) $file['size'] > CLAIM_UPLOAD_MAX_BYTES) {
        return 'Ukuran dokumen terlalu besar (maksimal 5 MB).';
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, CLAIM_UPLOAD_EXT, true)) {
        return 'Format dokumen harus PDF, JPG, atau PNG.';
    }

    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return 'Format dokumen harus PDF, JPG, atau PNG.';
        }
    }

    $dir = spbu_claim_upload_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return 'Folder penyimpanan tidak bisa dibuat. Periksa izin folder "uploads".';
    }

    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Require all denied\n</FilesMatch>\n");
    }

    $stored = 'dipstick_' . bin2hex(random_bytes(8)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
        return 'Dokumen gagal disimpan. Periksa izin folder "uploads".';
    }

    spbu_claim_remove_dipstick();
    $name = preg_replace('/[^\w.\- ()]+/u', '_', basename((string) $file['name']));
    $_SESSION['claim_docs']['dipstick'] = [
        'name' => (string) $name,
        'file' => $stored,
        'size' => (int) $file['size'],
    ];
    return null;
}

function spbu_claim_parse_qr(string $raw): array
{
    $raw   = trim($raw);
    $parts = explode('|', $raw);
    if ($raw === '' || strlen($raw) > 300 || count($parts) !== 5 || $parts[0] !== CLAIM_QR_PREFIX
        || !preg_match('/^[a-f0-9]{24}$/', $parts[3]) || !ctype_digit($parts[4])) {
        return ['ok' => false, 'error' => 'QR Code bukan QR Code AMT dari OneFIS. Silakan scan ulang.', 'data' => null];
    }
    if ((int) $parts[4] <= time()) {
        return ['ok' => false, 'error' => 'QR Code AMT sudah kedaluwarsa. Minta AMT menekan "Regenerate", lalu scan ulang.', 'data' => null];
    }
    return ['ok' => true, 'error' => '', 'data' => [
        'shipment' => $parts[1],
        'spbu'     => $parts[2],
        'token'    => $parts[3],
        'exp'      => (int) $parts[4],
        'scan_at'  => time(),
    ]];
}