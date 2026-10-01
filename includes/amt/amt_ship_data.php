<?php
/**
 * Data & fungsi bantu "Shipments" peran AMT.
 *
 * Alur layar:
 *   amt_home  ->  amt_shipments        (daftar pengiriman, filter status)
 *             ->  amt_shipment_detail  (Detail Order: info pengiriman + rute)
 *             ->  amt_spbu             (Aktifitas di SPBU + Order List)
 *
 * Data di bawah adalah CONTOH (sesuai desain). Ganti isi amt_ship_all() dengan sumber data asli
 * (database / API) bila sudah tersedia; view tidak perlu diubah selama bentuk array-nya sama.
 */

/** Status pengiriman -> teks, kelas chip. */
const AMT_SHIP_STATUS = [
    'sedang'  => ['label' => 'Sedang Dikirim',  'class' => 'is-going'],
    'selesai' => ['label' => 'Selesai Dikirim', 'class' => 'is-done'],
];

/** Langkah aktifitas di SPBU (urutan sesuai desain). 'icon' = file pilihan; bila belum ada dipakai 'fallback'. */
function amt_spbu_steps(): array
{
    $dir = 'assets/amt/Asset/ship/';
    $pick = function (string $file, string $fallback) use ($dir): string {
        return is_file(__DIR__ . '/../../' . $dir . $file) ? $dir . $file : $fallback;
    };
    return [
        ['key' => 'tiba',      'label' => 'Tiba di Lokasi',       'icon' => $pick('step-tiba.png',       'assets/step-arrive.png')],
        ['key' => 'checklist', 'label' => 'Isi Checklist',        'icon' => $pick('step-checklist.png',  'assets/step-checklist.png')],
        ['key' => 'verifikasi','label' => 'Verifikasi Order',     'icon' => $pick('step-verifikasi.png', 'assets/step-surat-jalan.png')],
        ['key' => 'surat',     'label' => 'Foto Surat Jalan',     'icon' => $pick('step-surat.png',      'assets/step-surat-jalan.png')],
        ['key' => 'rating',    'label' => 'Rating Petugas SPBU',  'icon' => $pick('step-rating.png',     'assets/step-rate-spbu.png')],
    ];
}

/**
 * Semua pengiriman AMT (terbaru di atas).
 * Nomor polisi & kapasitas mengikuti shipment PTI supaya konsisten di semua layar.
 */
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

    return [
        array_merge($base, [
            'id'        => 'ONEFIS-1790826685639-WNKJL0',
            'status'    => 'sedang',
            'detail_no' => '45788827',
            'tanggal_iso' => '2026-10-01',
            'spbu'      => '65748002',
            'products'  => $products('8144837464', '8144837463'),
        ]),
        array_merge($base, [
            'id'        => 'ONEFIS-1790817625119-FO2U3L',
            'status'    => 'selesai',
            'detail_no' => '45788811',
            'tanggal_iso' => '2026-10-01',
            'spbu'      => '65748001',
            'products'  => $products('8144837402', '8144837401'),
        ]),
        array_merge($base, [
            'id'        => 'ONEFIS-1790557633198-K3P9TD',
            'status'    => 'selesai',
            'tanggal'   => '28/9/2026',
            'detail_no' => '45788790',
            'tanggal_iso' => '2026-09-28',
            'spbu'      => '65748003',
            'products'  => $products('8144837311', '8144837310'),
        ]),
    ];
}

/** Cari pengiriman berdasarkan id; tanpa id -> pengiriman yang sedang berjalan (atau yang pertama). */
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

/** id pengiriman dari URL (?id=...), hanya karakter aman. */
function amt_ship_request_id(): string
{
    $id = isset($_GET['id']) ? (string) $_GET['id'] : '';
    return preg_match('/^[A-Za-z0-9-]{1,64}$/', $id) ? $id : '';
}

/** Pengiriman yang sedang dibuka layar saat ini (null bila id tidak dikenal). */
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