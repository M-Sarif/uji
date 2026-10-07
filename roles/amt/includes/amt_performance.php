<?php
/**
 * Data & fungsi bantu layar "Performance" peran AMT.
 *
 * Layar : amt_performance  (menu "Performance" di beranda AMT)
 * View  : roles/amt/views/performance.php
 * CSS   : roles/amt/assets/css/amt-performance.css
 * JS    : roles/amt/assets/js/amt-performance.js
 *
 * Layar ini TIDAK memakai tutorial terpandu. Tombol "?" hanya membuka pop up informasi
 * (isinya mengikuti USERGUIDE ONEFIS AMT Ver 1.3.4, bagian "F. Performansi").
 *
 * Data di amt_perf_all() adalah CONTOH (sesuai desain). Ganti isinya dengan sumber data asli
 * (database / API) bila sudah ada; view tidak perlu diubah selama bentuk array-nya sama:
 *   ['tanggal' => 'Y-m-d', 'total' => KL, 'km' => float, 'accident' => int,
 *    'produk' => ['pertalite' => KL, 'pertamax' => KL, ...]]   (satu baris = satu shipment)
 */

/** Tab rentang waktu: kunci (?tab=) => label */
const AMT_PERF_TABS = [
    '7'  => '7 Hari Terakhir',
    '30' => '1 Bulan',
];

/** Produk BBM: urutan tampil + warna kartu. 'logo' = nama file opsional di img/produk/ */
const AMT_PERF_PRODUCTS = [
    'pertalite' => ['label' => 'Pertalite',       'bg' => '#e3f8c8', 'bd' => '#c8ec9a', 'logo' => 'pertalite.png'],
    'pertamax'  => ['label' => 'Pertamax',        'bg' => '#cfe0ff', 'bd' => '#b3cdfb', 'logo' => 'pertamax.png'],
    'biosolar'  => ['label' => 'Biosolar',        'bg' => '#ffd0d6', 'bd' => '#fbb4be', 'logo' => 'biosolar.png'],
    'dexlite'   => ['label' => 'Dexlite',         'bg' => '#e5e7eb', 'bd' => '#d1d5db', 'logo' => 'dexlite.png'],
    'lainnya'   => ['label' => 'Produk Lainnya',  'bg' => '#fdf0c2', 'bd' => '#f8e08c', 'logo' => ''],
];

const AMT_PERF_MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
    'September', 'Oktober', 'November', 'Desember'];
const AMT_PERF_MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/** Isi pop up "?" (mengikuti panduan v1.3.4 bagian Performansi) dan pop up "i" pada grafik. */
const AMT_PERF_HELP = [
    'title' => 'Informasi Performance',
    'items' => [
        'Menu Performansi terbaru sedang dalam progres development.',
        'Nantinya fitur ini menampilkan estimasi upah pokok dan rincian upah performansi AMT.',
        'Data yang tampil saat ini masih berupa contoh.',
    ],
];
const AMT_PERF_CHART_INFO = [
    'title' => 'Detail Volume Dikirim',
    'items' => [
        'Grafik menampilkan volume BBM (dalam KL) yang dikirim pada tiap tanggal di periode yang dipilih.',
    ],
];

/** Semua shipment AMT (CONTOH). Nilai total: 40 KL, 4 shipment, 247,4 KM, 0 accident. */
function amt_perf_all(): array
{
    return [
        ['tanggal' => '2026-10-01', 'total' => 10.0, 'km' => 60.1, 'accident' => 0, 'produk' => ['pertalite' => 10.0]],
        ['tanggal' => '2026-10-01', 'total' => 10.0, 'km' => 67.3, 'accident' => 0, 'produk' => ['pertalite' => 5.0, 'pertamax' => 5.0]],
        ['tanggal' => '2026-10-02', 'total' => 10.0, 'km' => 60.0, 'accident' => 0, 'produk' => []],
        ['tanggal' => '2026-10-06', 'total' => 10.0, 'km' => 60.0, 'accident' => 0, 'produk' => []],
    ];
}

/** Tab aktif dari ?tab= ('7' bawaan) */
function amt_perf_tab(): string
{
    $t = (string) ($_GET['tab'] ?? '7');
    return isset(AMT_PERF_TABS[$t]) ? $t : '7';
}

/** Daftar bulan untuk pilihan Periode: bulan ini + 11 bulan sebelumnya ('Y-m' => 'Oktober 2026') */
function amt_perf_month_options(): array
{
    $opts = [];
    $y = (int) date('Y');
    $m = (int) date('n');
    for ($i = 0; $i < 12; $i++) {
        $opts[sprintf('%04d-%02d', $y, $m)] = AMT_PERF_MONTHS[$m - 1] . ' ' . $y;
        if (--$m < 1) { $m = 12; $y--; }
    }
    return $opts;
}

/** Periode aktif ('Y-m') dari ?periode=; di tab 7 hari selalu bulan ini (pilihan dikunci) */
function amt_perf_period(string $tab): string
{
    $now = date('Y-m');
    if ($tab !== '30') {
        return $now;
    }
    $p = (string) ($_GET['periode'] ?? $now);
    return isset(amt_perf_month_options()[$p]) ? $p : $now;
}

/** Shipment yang masuk rentang waktu tab + periode */
function amt_perf_filter(string $tab, string $period): array
{
    $from = null;
    $to   = null;
    if ($tab === '7') {
        $to   = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-6 days'));
    }
    return array_values(array_filter(amt_perf_all(), function (array $s) use ($tab, $period, $from, $to): bool {
        if ($tab === '7') {
            return $s['tanggal'] >= $from && $s['tanggal'] <= $to;
        }
        return substr($s['tanggal'], 0, 7) === $period;
    }));
}

/** Ringkasan + rincian produk + titik grafik untuk satu rentang waktu */
function amt_perf_data(string $tab, string $period): array
{
    $rows  = amt_perf_filter($tab, $period);
    $total = 0.0;
    $km    = 0.0;
    $acc   = 0;
    $byDay = [];
    $prod  = array_fill_keys(array_keys(AMT_PERF_PRODUCTS), 0.0);

    foreach ($rows as $r) {
        $total += (float) $r['total'];
        $km    += (float) $r['km'];
        $acc   += (int) $r['accident'];
        $byDay[$r['tanggal']] = ($byDay[$r['tanggal']] ?? 0.0) + (float) $r['total'];
        foreach ((array) $r['produk'] as $k => $v) {
            $k = isset($prod[$k]) ? $k : 'lainnya';
            $prod[$k] += (float) $v;
        }
    }
    ksort($byDay);

    $points = [];
    foreach ($byDay as $date => $vol) {
        $points[] = ['label' => amt_perf_day_label($date), 'value' => $vol];
    }

    $products = [];
    foreach (AMT_PERF_PRODUCTS as $key => $def) {
        $products[] = $def + [
            'key'     => $key,
            'volume'  => $prod[$key],
            'percent' => $total > 0 ? (int) round($prod[$key] / $total * 100) : 0,
        ];
    }

    return [
        'total'     => $total,
        'shipments' => count($rows),
        'km'        => $km,
        'accident'  => $acc,
        'points'    => $points,
        'products'  => $products,
    ];
}

/** '2026-10-01' -> '01 Okt' */
function amt_perf_day_label(string $date): string
{
    $ts = strtotime($date);
    return date('d', $ts) . ' ' . AMT_PERF_MONTHS_SHORT[(int) date('n', $ts) - 1];
}

/** Angka KL: bulat tanpa desimal, selain itu 1 desimal koma ('40', '12,5') */
function amt_perf_num(float $v): string
{
    return abs($v - round($v)) < 0.05 ? (string) (int) round($v) : number_format($v, 1, ',', '.');
}

/** KM dengan 1 desimal titik, seperti desain ('247.4') */
function amt_perf_km(float $v): string
{
    return number_format($v, 1, '.', '');
}

/** URL logo produk bila file-nya ada di roles/amt/assets/img/produk/, selain itu null (dipakai tulisan) */
function amt_perf_logo_url(string $file): ?string
{
    if ($file === '' || !is_file(AMT_ASSET_DIR . '/img/produk/' . $file)) {
        return null;
    }
    return AMT_URL . '/img/produk/' . rawurlencode($file);
}

/** Link tab/periode (tetap di layar Performance) */
function amt_perf_url(string $tab, ?string $period = null): string
{
    $q = ['screen' => 'amt_performance', 'tab' => $tab];
    if ($period !== null && $tab === '30') {
        $q['periode'] = $period;
    }
    return 'index.php?' . http_build_query($q);
}

/** Grafik area (SVG inline). Sumbu Y 0..50 KL (naik per 10 KL bila data lebih besar). */
function amt_perf_chart_svg(array $points): string
{
    $W = 320; $H = 176;
    $left = 46; $right = 14; $top = 12; $bottom = 28;
    $pw = $W - $left - $right;
    $ph = $H - $top - $bottom;

    $maxVal = 0.0;
    foreach ($points as $p) { $maxVal = max($maxVal, $p['value']); }
    $axisMax = max(50, (int) (ceil($maxVal / 10) * 10));
    $step    = $axisMax <= 50 ? 10 : (int) (ceil($axisMax / 5 / 10) * 10);
    $axisMax = (int) (ceil($axisMax / $step) * $step);

    $o = '<svg class="amtp-svg" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="Grafik detail volume dikirim" preserveAspectRatio="xMidYMid meet">';
    $o .= '<defs><linearGradient id="amtpFill" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0" stop-color="#3b82f6" stop-opacity=".38"/><stop offset="1" stop-color="#3b82f6" stop-opacity=".06"/>'
        . '</linearGradient></defs>';

    // garis bantu + label sumbu Y
    for ($v = 0; $v <= $axisMax; $v += $step) {
        $y = $top + $ph - ($v / $axisMax) * $ph;
        $o .= '<line x1="' . $left . '" y1="' . round($y, 1) . '" x2="' . ($W - $right) . '" y2="' . round($y, 1) . '" stroke="#e5e9f0" stroke-width="1" stroke-dasharray="3 3"/>';
        $o .= '<text x="' . ($left - 6) . '" y="' . round($y + 2.8, 1) . '" text-anchor="end" font-size="8" fill="#94a3b8">' . $v . ' KL</text>';
    }

    $n = count($points);
    if ($n === 0) {
        $o .= '<text x="' . ($left + $pw / 2) . '" y="' . ($top + $ph / 2) . '" text-anchor="middle" font-size="10" fill="#94a3b8">Belum ada data pada periode ini</text>';
        return $o . '</svg>';
    }

    $xy = [];
    foreach ($points as $i => $p) {
        $x = $n === 1 ? $left + $pw / 2 : $left + 6 + ($pw - 12) * $i / ($n - 1);
        $y = $top + $ph - ($p['value'] / $axisMax) * $ph;
        $xy[] = [round($x, 1), round($y, 1), $p['label']];
    }
    $base = $top + $ph;
    $line = [];
    foreach ($xy as $pt) { $line[] = $pt[0] . ',' . $pt[1]; }

    if ($n > 1) {
        $o .= '<polygon points="' . $xy[0][0] . ',' . $base . ' ' . implode(' ', $line) . ' ' . $xy[$n - 1][0] . ',' . $base . '" fill="url(#amtpFill)"/>';
        $o .= '<polyline points="' . implode(' ', $line) . '" fill="none" stroke="#3b82f6" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>';
    }
    foreach ($xy as $pt) {
        $o .= '<circle cx="' . $pt[0] . '" cy="' . $pt[1] . '" r="2.4" fill="#3b82f6"/>';
        $o .= '<text x="' . $pt[0] . '" y="' . ($H - 10) . '" text-anchor="middle" font-size="8" fill="#94a3b8">' . h($pt[2]) . '</text>';
    }
    return $o . '</svg>';
}