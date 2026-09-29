<?php
/* Fitur jam kerja AMT (Start Work / End Work).
 * State di $_SESSION['work'] = [
 *   'started_at' => timestamp, 'ended_at' => timestamp|null,
 *   'activity'   => 'Hadir' dst, 'start_photo' => bool, 'end_photo' => bool ] */

// Cadangan opsional (ikon AMT sebagai data URI). Tidak wajib ada.
if (is_file(__DIR__ . '/avatar_amt.php')) {
    require_once __DIR__ . '/avatar_amt.php';
}

// Lokasi folder AMT
//   includes/amt/  logika PHP     views/amt/  tampilan PHP
//   assets/amt/    css, js, gambar
if (!defined('AMT_APP_ROOT')) {
    define('AMT_APP_ROOT', dirname(__DIR__, 2));            // <root> aplikasi
    define('AMT_INC_DIR', AMT_APP_ROOT . '/includes/amt');
    define('AMT_VIEW_DIR', AMT_APP_ROOT . '/views/amt');
    define('AMT_ASSET_DIR', AMT_APP_ROOT . '/assets/amt');
    define('AMT_URL', 'assets/amt');                        // dipakai di href/src
}

if (date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('Asia/Jakarta'); // WIB
}

// Lokasi tetap untuk verifikasi
const WORK_LAT = -2.723962;
const WORK_LNG = 114.261573;

// Pilihan aktivitas saat Start Work
const WORK_ACTIVITIES = ['Hadir', 'Sakit', 'Izin', 'Cuti', 'Alpa', 'Dinas Luar'];

// Menu yang hanya aktif saat timer berjalan (setelah Start Work).
// Saat ini hanya Check-In. PTI & Check-Out SENGAJA belum aktif (lihat
// WORK_LOCKED_MENUS) sampai layarnya dibuat.
const WORK_GATED_SCREENS = ['checkin'];

// Menu yang masih terkunci walau timer sudah berjalan.
// Hapus 'pti' / 'checkout' dari daftar ini saat layarnya sudah siap.
const WORK_LOCKED_MENUS = ['pti', 'checkout'];

// Pilihan aktivitas pada form Check-In (default sama dengan Start Work)
const CHECKIN_ACTIVITIES = WORK_ACTIVITIES;

// Judul header untuk layar tambahan (tidak ada di data.php)
const WORK_SCREENS = [
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'end_work'   => 'End Work',
    'checkin'    => 'Check-In',
];

function work_data(): array {
    return $_SESSION['work'] ?? [];
}

function work_is_running(): bool {
    $w = work_data();
    return !empty($w['started_at']) && empty($w['ended_at']);
}

/* Timestamp mulai, hanya saat timer berjalan (0 = timer berhenti) */
function work_started_at(): int {
    return work_is_running() ? (int) $_SESSION['work']['started_at'] : 0;
}

/* Riwayat Check-In (hari/ritase ini). Tiap item:
 * ['at' => timestamp, 'activity' => 'Hadir', 'photo' => dataURL|''] */
function work_checkins(): array {
    $c = $_SESSION['work']['checkins'] ?? [];
    return is_array($c) ? array_values($c) : [];
}

function work_fmt(?int $ts): string {
    return $ts ? date('d/m/Y H:i:s', $ts) : '-';
}

/* reset_flow_state() bawaan tidak boleh menghapus status kerja,
 * jadi disimpan dulu lalu dikembalikan. */
function reset_flow_keep_work(): void {
    $work = $_SESSION['work'] ?? null;
    reset_flow_state();
    if ($work !== null) {
        $_SESSION['work'] = $work;
    }
}

/* Ikon/avatar AMT (SVG inline, tanpa kamera) */
function work_avatar_svg(int $size = 120): string {
    return '<svg width="' . $size . '" height="' . round($size * 1.08) . '" viewBox="0 0 120 130" xmlns="http://www.w3.org/2000/svg" aria-label="AMT">'
        . '<ellipse cx="60" cy="124" rx="34" ry="5" fill="#000" opacity=".08"/>'
        . '<path d="M22 122c0-26 15-42 38-42s38 16 38 42z" fill="#2f5fa8"/>'
        . '<path d="M44 84l16 18 16-18" fill="#e9eef7"/>'
        . '<rect x="53" y="70" width="14" height="14" rx="6" fill="#f2b98f"/>'
        . '<circle cx="60" cy="52" r="24" fill="#f6c7a0"/>'
        . '<path d="M34 50c0-20 11-32 26-32s26 12 26 32z" fill="#fff" stroke="#d8dee9" stroke-width="2"/>'
        . '<rect x="30" y="47" width="60" height="7" rx="3.5" fill="#e8edf5"/>'
        . '<rect x="56" y="17" width="8" height="30" rx="4" fill="#dc2626"/>'
        . '<circle cx="51" cy="58" r="2.6" fill="#2b2b2b"/><circle cx="69" cy="58" r="2.6" fill="#2b2b2b"/>'
        . '<path d="M52 68q8 7 16 0" stroke="#a5583a" stroke-width="2.4" fill="none" stroke-linecap="round"/>'
        . '<rect x="86" y="70" width="16" height="28" rx="3" fill="#1f2937"/>'
        . '<rect x="88" y="73" width="12" height="20" rx="1.5" fill="#86efac"/>'
        . '<circle cx="93" cy="82" r="3" fill="#166534"/>'
        . '<path d="M78 100c2-8 6-12 10-12" stroke="#2f5fa8" stroke-width="9" fill="none" stroke-linecap="round"/>'
        . '</svg>';
}

/* Ikon AMT: dipakai dari folder assets/ (nama file di bawah). Kalau file itu
 * tidak ada, dipakai ikon tertanam (avatar_amt.php) bila tersedia, atau SVG
 * cadangan. Tidak akan menyebabkan error walau file-nya tidak ada. */
const WORK_AVATAR_FILE = 'Halaman Check-In_Form Check-In.png';

function work_avatar_url(): ?string {
    if (WORK_AVATAR_FILE !== '' && is_file(AMT_APP_ROOT . '/assets/' . WORK_AVATAR_FILE)) {
        return 'assets/' . rawurlencode(WORK_AVATAR_FILE);
    }
    return defined('WORK_AVATAR_DATA') ? WORK_AVATAR_DATA : null;
}

function work_avatar_html(int $size = 120): string {
    $url = work_avatar_url();
    if ($url === null) {
        return work_avatar_svg($size);
    }
    return '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="AMT" style="width:' . $size . 'px;height:auto;max-height:' . round($size * 1.2) . 'px;object-fit:contain;display:block;margin:0 auto">';
}

/* CSS halaman Start/End Work (assets/amt/css/work.css) */
function work_styles(): void
{
    $v = (int) @filemtime(AMT_ASSET_DIR . '/css/work.css');
    echo '<link rel="stylesheet" href="' . AMT_URL . '/css/work.css?v=' . $v . '">' . "\n";
}