<?php

const AMT_KENDALA_SCREEN   = 'amt_kendala';
const AMT_KENDALA_NOTE_MAX = 500;
const AMT_KENDALA_PHOTO_MAX = 6000000;
const AMT_KENDALA_MAX_HOURS = 99;
const AMT_KENDALA_TYPES = [
    'mogok'        => 'Mogok',
    'ban'          => 'Ban Bocor/Pecah',
    'mesin'        => 'Mesin Bermasalah',
    'bbm_habis'    => 'Bahan Bakar Habis',
    'kecelakaan'   => 'Kecelakaan',
    'stuffle_tank' => 'Stuffle Tank',
    'force_major'  => 'Force Major(Kejadian Tak Terduga)',
    'lainnya'      => 'Lainnya',
];

const AMT_KENDALA_ROUTE_TYPE = 'force_major';

function amt_kendala_route_stops(array $s): array
{
    $names = [];
    foreach ($s['products'] as $p) {
        $names[] = (string) $p['name'];
    }
    return [
        ['id' => 'r1', 'spbu' => (string) $s['spbu'], 'km' => '2,4', 'products' => $names],
        ['id' => 'r2', 'spbu' => '2438203', 'km' => '3,6', 'products' => ['PERTAMAX,BULK 8.000 L']],
    ];
}

function amt_kendala_route_parse(array $s, string $raw): ?array
{
    if ($raw === '') {
        return null;
    }
    $byId = [];
    foreach (amt_kendala_route_stops($s) as $stop) {
        $byId[$stop['id']] = $stop['spbu'];
    }
    $ids = explode(',', $raw);
    $sorted = $ids;
    $expect = array_keys($byId);
    sort($sorted);
    sort($expect);
    if ($sorted !== $expect) {
        return null;
    }
    $route = [];
    foreach ($ids as $id) {
        $route[] = $byId[$id];
    }
    $original = array_values($byId);
    return $route === $original ? null : $route;
}

function amt_kendala_url(?array $s = null, string $from = ''): string
{
    $q = ['screen' => AMT_KENDALA_SCREEN];
    if ($s !== null) {
        $q['id'] = $s['id'];
    }
    if ($from === 'detail') {
        $q['from'] = 'detail';
    }
    return '?' . http_build_query($q);
}

function amt_kendala_all(): array
{
    $r = $_SESSION['amt_kendala'] ?? [];
    return is_array($r) ? $r : [];
}

function amt_kendala_reset(): void
{
    unset($_SESSION['amt_kendala']);
}

function amt_kendala_ship(): ?array
{
    if (!amt_work_ok()) {
        return null;
    }
    $s = amt_ship_find(amt_ship_request_id() ?: (amt_ship_post_id() ?: null));
    return ($s !== null && $s['status'] === 'sedang') ? $s : null;
}

function amt_kendala_duration_text(int $minutes): string
{
    return sprintf('%02d Jam %02d Menit', intdiv($minutes, 60), $minutes % 60);
}

function amt_kendala_guard(string $screen): void
{
    if ($screen !== AMT_KENDALA_SCREEN) {
        return;
    }
    if (!amt_work_ok()) {
        go_to(amt_home_screen());
    }
    if (amt_kendala_ship() === null) {
        go_to(amt_home_screen());
    }
}

function amt_kendala_handle_post(): void
{
    $s = amt_kendala_ship();
    if ($s === null) {
        go_to(amt_home_screen());
    }
    $back = ['id' => $s['id']];

    $jenis = (string) ($_POST['jenis'] ?? '');
    if (!isset(AMT_KENDALA_TYPES[$jenis])) {
        go_to(AMT_KENDALA_SCREEN, $back);
    }

    $bisa = (string) ($_POST['bisa'] ?? '');
    if ($bisa !== 'ya' && $bisa !== 'tidak') {
        go_to(AMT_KENDALA_SCREEN, $back);
    }
    $minutes = null;
    if ($bisa === 'ya') {
        $h = $_POST['est_h'] ?? null;
        $m = $_POST['est_m'] ?? null;
        if (!is_string($h) || !ctype_digit($h) || !is_string($m) || !ctype_digit($m)
            || (int) $h > AMT_KENDALA_MAX_HOURS || (int) $m > 59 || ((int) $h * 60 + (int) $m) <= 0) {
            go_to(AMT_KENDALA_SCREEN, $back);
        }
        $minutes = (int) $h * 60 + (int) $m;
    }

    $photo = (string) ($_POST['photo_data'] ?? '');
    if (strncmp($photo, 'data:image/', 11) !== 0 || strlen($photo) > AMT_KENDALA_PHOTO_MAX) {
        go_to(AMT_KENDALA_SCREEN, $back);
    }

    $note = preg_replace('/\s+/u', ' ', trim((string) ($_POST['note'] ?? '')));
    $note = mb_substr($note, 0, AMT_KENDALA_NOTE_MAX);
    if ($note === '') {
        go_to(AMT_KENDALA_SCREEN, $back);
    }

    $route = null;
    if ($jenis === AMT_KENDALA_ROUTE_TYPE) {
        $route = amt_kendala_route_parse($s, (string) ($_POST['route'] ?? ''));
    }

    $_SESSION['amt_kendala'][] = [
        'ship'    => $s['id'],
        'jenis'   => $jenis,
        'bisa'    => $bisa,
        'minutes' => $minutes,
        'note'    => $note,
        'photo'   => true,
        'rute'    => $route,
        'at'      => time(),
    ];
    $_SESSION['flash_success'] = [
        'title' => 'Laporan Kendala Berhasil Dibuat',
        'body'  => 'Laporan "' . AMT_KENDALA_TYPES[$jenis] . '" sudah dikirim.' . ($route !== null ? ' Rute pengiriman diperbarui.' : '') . ' Silakan menunggu konfirmasi dari Terminal.',
    ];
    go_to(amt_home_screen());
}