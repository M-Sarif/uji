<?php

const AMT_SHIP_STATUS = [
    'sedang'  => ['label' => 'Sedang Dikirim',  'class' => 'is-going'],
    'selesai' => ['label' => 'Selesai Dikirim', 'class' => 'is-done'],
];

function amt_spbu_steps(): array
{
    $dir = AMT_URL . '/img/ship/';
    $pick = function (string $file, string $fallback) use ($dir): string {
        return is_file(APP_ROOT . '/' . $dir . $file) ? $dir . $file : $fallback;
    };
    return [
        ['key' => 'tiba',      'label' => 'Tiba di Lokasi',       'icon' => $pick('step-tiba.png',       AMT_URL . '/img/langkah/step-arrive.png')],
        ['key' => 'checklist', 'label' => 'Isi Checklist',        'icon' => $pick('step-checklist.png',  AMT_URL . '/img/langkah/step-checklist.png')],
        ['key' => 'verifikasi','label' => 'Verifikasi Order',     'icon' => $pick('step-verifikasi.png', AMT_URL . '/img/langkah/step-verification.png')],
        ['key' => 'surat',     'label' => 'Foto Surat Jalan',     'icon' => $pick('step-surat.png',      AMT_URL . '/img/langkah/step-surat-jalan.png')],
        ['key' => 'rating',    'label' => 'Rating Petugas SPBU',  'icon' => $pick('step-rating.png',     AMT_URL . '/img/langkah/step-rate-spbu.png')],
    ];
}

function amt_ship_all(): array
{
    $mt = function_exists('amt_pti_shipment') && amt_pti_shipment()
        ? amt_pti_shipment()
        : ['nomor_polisi' => 'B9796SFU', 'kapasitas' => '10.000 L'];

    $base = [
        'tanggal'   => '1/10/2026',
        'total'     => '10.000 L',
        'mt'        => $mt['nomor_polisi'],
        'kapasitas' => $mt['kapasitas'],
        'origin'    => 'FT. PULANG PISAU',
        'km'        => '17.0',
    ];

    $products = function (string $lo1, string $lo2): array {
        return [
            ['lo' => $lo1, 'name' => 'PERTALITE 5.000 L'],
            ['lo' => $lo2, 'name' => 'PERTAMAX,BULK 5.000 L'],
        ];
    };

    $list = [
        array_merge($base, [
            'id'        => 'ONEFIS-1790826685639-WNKJL0',
            'status'    => 'sedang',
            'detail_no' => '45788827',
            'tanggal_iso' => '2026-10-01',
            'spbu'      => '62.938.839',
            'lat'       => -2.754705,
            'lng'       => 114.257301,
            'plus'      => '67W4+4W7',
            'products'  => $products('8144837464', '8144837463'),
        ]),
        array_merge($base, [
            'id'        => 'ONEFIS-1790817625119-FO2U3L',
            'status'    => 'selesai',
            'detail_no' => '45788811',
            'tanggal_iso' => '2026-10-01',
            'spbu'      => '65748001',
            'lat'       => -2.739100,
            'lng'       => 114.270200,
            'plus'      => '7727+4P8',
            'products'  => $products('8144837402', '8144837401'),
        ]),
        array_merge($base, [
            'id'        => 'ONEFIS-1790557633198-K3P9TD',
            'status'    => 'selesai',
            'tanggal'   => '28/9/2026',
            'detail_no' => '45788790',
            'tanggal_iso' => '2026-09-28',
            'spbu'      => '65748003',
            'lat'       => -2.734000,
            'lng'       => 114.264000,
            'plus'      => '7725+MH3',
            'products'  => $products('8144837311', '8144837310'),
        ]),
    ];

    foreach ($list as &$row) {
        if (!empty($_SESSION['amt_spbu']['finished'][$row['id']])) {
            $row['status'] = 'selesai';
        }
    }
    unset($row);

    return $list;
}

function amt_ship_find(?string $id = null): ?array
{
    $all = amt_ship_all();
    foreach ($all as $s) {
        if ($id !== null && $id !== '' && $s['id'] === $id) {
            return $s;
        }
    }
    if ($id === null || $id === '') {
        foreach ($all as $s) {
            if ($s['status'] === 'sedang') {
                return $s;
            }
        }
        return $all[0] ?? null;
    }
    return null;
}

function amt_home_route_ship(): ?array
{
    if (amt_out_order_done()) {
        return null;
    }
    if (!work_is_running()
        || amt_last_checkin_at() === 0
        || !amt_pti_is_done()
        || (amt_pti_state()['result'] ?? null) === 'NO GO') {
        return null;
    }
    $s = amt_ship_find();
    return ($s !== null && $s['status'] === 'sedang') ? $s : null;
}

function amt_home_route_done_ship(): ?array
{
    if (!amt_out_order_done()) {
        return null;
    }
    $f = $_SESSION['amt_spbu']['finished'] ?? [];
    if (!is_array($f) || !$f) {
        return null;
    }
    arsort($f);
    $ids = array_keys($f);
    $s   = amt_ship_find((string) $ids[0]);
    return ($s !== null && $s['status'] === 'selesai') ? $s : null;
}

function amt_ship_request_id(): string
{
    $id = isset($_GET['id']) ? (string) $_GET['id'] : '';
    return preg_match('/^[A-Za-z0-9-]{1,64}$/', $id) ? $id : '';
}

function amt_ship_current(): ?array
{
    return amt_ship_find(amt_ship_request_id());
}

function amt_ship_url(string $screen, array $s): string
{
    return '?' . http_build_query(['screen' => $screen, 'id' => $s['id']]);
}

function amt_ship_chip(string $status): string
{
    $st = AMT_SHIP_STATUS[$status] ?? AMT_SHIP_STATUS['selesai'];
    return '<span class="amts-chip ' . amt_e($st['class']) . '">' . amt_e($st['label']) . '</span>';
}

function amt_spbu_place(array $s): string
{
    $p = trim((string) ($s['place'] ?? ''));
    if ($p !== '') {
        return $p;
    }

    $no = trim((string) ($s['spbu'] ?? ''));
    return $no !== '' ? 'SPBU ' . $no : 'SPBU Pertamina';
}

const AMT_SPBU_RADIUS_M = 200;

function amt_spbu_arrived(array $s): bool
{
    return !empty($_SESSION['amt_spbu']['arrived'][$s['id']]);
}

function amt_spbu_mark_arrived(array $s, float $lat, float $lng): void
{
    $_SESSION['amt_spbu']['arrived'][$s['id']] = ['at' => time(), 'lat' => $lat, 'lng' => $lng];
}

function amt_spbu_reset(): void
{
    unset($_SESSION['amt_spbu']);
}

function amt_spbu_in_range(array $s, float $lat, float $lng): bool
{
    return work_distance_m($lat, $lng, (float) $s['lat'], (float) $s['lng']) <= AMT_SPBU_RADIUS_M;
}

function amt_ship_post_id(): string
{
    $id = isset($_POST['id']) ? (string) $_POST['id'] : '';
    return preg_match('/^[A-Za-z0-9-]{1,64}$/', $id) ? $id : '';
}