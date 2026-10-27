<?php

// Cadangan opsional (ikon AMT sebagai data URI). Tidak wajib ada.
if (is_file(__DIR__ . '/avatar_amt.php')) {
    require_once __DIR__ . '/avatar_amt.php';
}


const WORK_LAT = -2.723788;
const WORK_LNG = 114.261609;
const WORK_PLACE_NAME = 'Pertamina Fuel Terminal Pulang Pisau'; // nama tempat di Google Maps (pin bawaan)
const WORK_RADIUS_M = 500;          // batas jarak absen dari titik kerja (meter)
// Simulasi lokasi: peluang posisi awal user "melenceng" di luar radius (0 = selalu
// di dalam, 1 = selalu di luar). "Perbarui Lokasi" selalu memindahkan ke dalam radius.
const WORK_SIM_OUTSIDE_CHANCE = 0.5;

// Pilihan aktivitas saat Mulai Kerja
const WORK_ACTIVITIES = ['Hadir', 'Sakit', 'Izin', 'Cuti', 'Alpa', 'Dinas Luar'];

// Menu yang hanya aktif saat timer berjalan (setelah Mulai Kerja).
// Saat ini hanya Mulai Ritase. PTI & Selesai Ritase SENGAJA belum aktif (lihat
// WORK_LOCKED_MENUS) sampai layarnya dibuat.
const WORK_GATED_SCREENS = ['checkin'];

// Menu yang SELALU terkunci walau timer sudah berjalan (belum ada layarnya).
// Selesai Ritase sudah punya layar: kuncinya diatur amt_out_unlocked() (roles/amt/includes/amt_out.php).
const WORK_LOCKED_MENUS = [];

// Pilihan aktivitas pada form Mulai Ritase (default sama dengan Mulai Kerja)
const CHECKIN_ACTIVITIES = ['Tugas Rutin', 'Tugas Lembur'];

// Judul header untuk layar tambahan (tidak ada di data.php)
const WORK_SCREENS = [
    'start_end'  => 'Absensi Kerja',
    'start_work' => 'Mulai Kerja',
    'end_work'   => 'Selesai Kerja',
    'checkin'    => 'Mulai Ritase',
    'checkout'   => 'Selesai Ritase',
    'amt_pti'      => 'Pre-Trip Inspection',
    'amt_pti_form' => 'Form Inspeksi',
    'amt_pti_hasil' => 'Hasil Inspeksi',
    'amt_shipments' => 'Shipments',
    'amt_shipment_detail' => 'Detail Order',
];

/* Jarak (meter) antara dua koordinat - rumus haversine */
function work_distance_m(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $r = 6371000;
    $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($a)));
}

/* Apakah lokasi dari form (POST lat/lng) berada dalam radius titik kerja? */
function work_post_in_range(): bool {
    if (!isset($_POST['lat'], $_POST['lng']) || !is_numeric($_POST['lat']) || !is_numeric($_POST['lng'])) {
        return false;
    }
    return work_distance_m((float) $_POST['lat'], (float) $_POST['lng'], WORK_LAT, WORK_LNG) <= WORK_RADIUS_M;
}

function work_data(): array {
    return $_SESSION['work'] ?? [];
}

/* ------------------------------------------------------------
 * MODE BEBAS (tutorial dimatikan)
 * Mesin tutorial (assets/js/tutorial-amt.js) menyimpan status "Lewati / selesai" di
 * localStorage dan MENYALINNYA ke cookie ini, supaya server tahu pengguna tidak sedang
 * memakai mode tutorial. Pada mode bebas pengguna boleh langsung membuka menu Shipments
 * tanpa Mulai Kerja -> Mulai Ritase -> PTI lebih dulu. Urutan langkah DI DALAM satu pengiriman
 * (Tiba -> Checklist -> Verifikasi -> Surat Jalan -> Rating -> Selesai) tetap dijaga.
 * Saat tutorial MENYALA, alur lengkap tetap wajib seperti sebelumnya.
 * ------------------------------------------------------------ */
const AMT_TOUR_OFF_COOKIE = 'onefis_amt_tour_off';

/** Tutorial sedang dimatikan (pengguna menekan "Lewati" / sudah memakainya sampai selesai)? */
function amt_tour_is_off(): bool {
    return ($_COOKIE[AMT_TOUR_OFF_COOKIE] ?? '') === '1';
}

/* Tutorial SUDAH DISELESAIKAN (Selesai Kerja berhasil)? Cookie ini disalin dari localStorage oleh tutorial-amt.js.
 * Selama menyala, sesi berikutnya TIDAK memakai mode tutorial lagi: mode bebas bertahan walau pengguna kembali ke
 * layar pilih peran. Baru padam saat pengguna mengetuk ikon profil di beranda AMT (tour_reset) atau tombol "?". */
const AMT_TOUR_FIN_COOKIE = 'onefis_amt_tour_finished';

function amt_tour_is_finished(): bool {
    return ($_COOKIE[AMT_TOUR_FIN_COOKIE] ?? '') === '1';
}

/** Hapus penanda "tutorial sudah selesai" di server. */
function amt_tour_finished_forget(): void {
    unset($_COOKIE[AMT_TOUR_FIN_COOKIE]);
    if (!headers_sent()) {
        setcookie(AMT_TOUR_FIN_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
    }
}

/**
 * Hapus penanda mode bebas di server (dipanggil saat kembali ke layar pilih peran).
 * Bila tutorial sudah DISELESAIKAN, penanda dipertahankan (sesi berikutnya tanpa mode tutorial),
 * kecuali $force = true (reset lewat ikon profil).
 */
function amt_tour_off_forget(bool $force = false): void {
    if (!$force && amt_tour_is_finished()) {
        return;
    }
    unset($_COOKIE[AMT_TOUR_OFF_COOKIE]);
    if (!headers_sent()) {
        setcookie(AMT_TOUR_OFF_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
    }
}

/** Layar/aksi alur Shipment boleh dipakai: jam kerja berjalan ATAU tutorial dimatikan (mode bebas). */
function amt_work_ok(): bool {
    return work_is_running() || amt_tour_is_off();
}

function work_is_running(): bool {
    $w = work_data();
    return !empty($w['started_at']) && empty($w['ended_at']);
}

/* Timestamp mulai, hanya saat timer berjalan (0 = timer berhenti) */
function work_started_at(): int {
    return work_is_running() ? (int) $_SESSION['work']['started_at'] : 0;
}

/* ------------------------------------------------------------
 * JAM KERJA vs JAM KERJA AKTIF (beranda AMT)
 *
 *  - Jam Kerja        : mulai berjalan saat "Mulai Kerja", terus berjalan sampai "Selesai Kerja".
 *  - Jam Kerja Aktif  : mulai berjalan saat "Mulai Ritase" (Mulai Ritase). Saat "Selesai Ritase"
 *                       (Selesai Ritase) jam ini DIJEDA (angka terakhir tersimpan). Saat "Mulai Ritase"
 *                       berikutnya, jam MELANJUTKAN dari angka terakhir.
 *  - Kedua jam kembali ke 00:00:00 saat "Selesai Kerja" (dan saat "Mulai Kerja" baru).
 *
 * State di $_SESSION['work']:
 *   'active_acc'   => detik yang sudah terkumpul dari sesi aktif yang SUDAH dijeda
 *   'active_since' => timestamp sesi aktif yang SEDANG berjalan (0 = dijeda / belum mulai)
 * Detik aktif sekarang = active_acc + (active_since > 0 ? sekarang - active_since : 0)
 * ------------------------------------------------------------ */

/* Detik jam aktif yang sudah terkumpul (tidak termasuk sesi yang sedang berjalan) */
function work_active_acc(): int {
    return work_is_running() ? max(0, (int) ($_SESSION['work']['active_acc'] ?? 0)) : 0;
}

/* Timestamp sesi aktif yang sedang berjalan (0 = dijeda / belum Mulai Ritase / kerja belum berjalan) */
function work_active_since(): int {
    return work_is_running() ? max(0, (int) ($_SESSION['work']['active_since'] ?? 0)) : 0;
}

/* Jam Kerja Aktif sedang berjalan (bukan dijeda)? */
function work_active_is_running(): bool {
    return work_active_since() > 0;
}

/* Total detik Jam Kerja Aktif saat ini (0 bila kerja belum mulai / sudah selesai) */
function work_active_seconds(): int {
    $since = work_active_since();
    return work_active_acc() + ($since > 0 ? max(0, time() - $since) : 0);
}

/* Mulai Ritase (Mulai Ritase): jam aktif berjalan / MELANJUTKAN. Bila sudah berjalan, tidak diulang dari awal. */
function work_active_resume(): void {
    if (!work_is_running()) {
        return;
    }
    if (empty($_SESSION['work']['active_since'])) {
        $_SESSION['work']['active_since'] = time();
    }
    if (!isset($_SESSION['work']['active_acc'])) {
        $_SESSION['work']['active_acc'] = 0;
    }
}

/* Selesai Ritase (Selesai Ritase): jam aktif DIJEDA, detik yang sudah berjalan disimpan ke akumulasi. */
function work_active_pause(): void {
    if (!work_is_running()) {
        return;
    }
    $since = (int) ($_SESSION['work']['active_since'] ?? 0);
    if ($since > 0) {
        $_SESSION['work']['active_acc'] = (int) ($_SESSION['work']['active_acc'] ?? 0) + max(0, time() - $since);
    }
    $_SESSION['work']['active_since'] = 0;
}

/* Selesai Kerja (Selesai Kerja): kedua jam kembali ke 0. Nilai akhir disimpan sebagai arsip (tidak ditampilkan). */
function work_active_stop(): void {
    if (!isset($_SESSION['work'])) {
        return;
    }
    work_active_pause();
    $_SESSION['work']['active_total'] = (int) ($_SESSION['work']['active_acc'] ?? 0);
    $_SESSION['work']['active_acc'] = 0;
}

/* Format detik -> "HH:MM:SS" */
function work_fmt_duration(int $sec): string {
    $sec = max(0, $sec);
    return sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
}

/* Riwayat Ritase (hari/ritase ini). Tiap item:
 * ['at' => timestamp, 'activity' => 'Hadir', 'photo' => dataURL|''] */
function work_checkins(): array {
    $c = $_SESSION['work']['checkins'] ?? [];
    return is_array($c) ? array_values($c) : [];
}

function work_fmt(?int $ts): string {
    return $ts ? date('d/m/Y H:i:s', $ts) : '-';
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

/* Ikon AMT: dipakai dari roles/amt/assets/img/avatar/ (nama file di bawah). Kalau file itu
 * tidak ada, dipakai ikon tertanam (avatar_amt.php) bila tersedia, atau SVG
 * cadangan. Tidak akan menyebabkan error walau file-nya tidak ada. */
const WORK_AVATAR_FILE = 'avatar-checkin.png';

function work_avatar_url(): ?string {
    if (WORK_AVATAR_FILE !== '' && is_file(AMT_ASSET_DIR . '/img/avatar/' . WORK_AVATAR_FILE)) {
        return AMT_URL . '/img/avatar/' . rawurlencode(WORK_AVATAR_FILE);
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

/* CSS halaman Absensi Kerja (roles/amt/assets/css/work.css) */
function work_styles(): void
{
    $v = (int) @filemtime(AMT_ASSET_DIR . '/css/work.css');
    echo '<link rel="stylesheet" href="' . AMT_URL . '/css/work.css?v=' . $v . '">' . "\n";
}