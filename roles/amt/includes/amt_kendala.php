<?php
/**
 * Laporkan Kendala (peran AMT).
 * Letak: roles/amt/includes/amt_kendala.php
 *
 * Alur layar:
 *   Beranda AMT (panel "Rute Pengiriman") atau Detail Order (pengiriman "Sedang Dikirim")
 *     -> tombol "Laporkan Kendala"  ->  amt_kendala (form)
 *     -> "Kirim Laporan Kendala" (POST submit_kendala)  ->  beranda AMT + notifikasi sukses
 *
 * Isi form (semua wajib):
 *   - Jenis Kendala (daftar AMT_KENDALA_TYPES)
 *   - Estimasi Kendala: "Ya, Bisa Diprediksi" (+ Estimasi Waktu Kendala Selesai, jam:menit)
 *                       atau "Tidak Bisa Diprediksi" (tanpa estimasi waktu)
 *   - Foto Bukti (kamera layar penuh, bisa ganti kamera depan/belakang)
 *   - Justifikasi AMT (keterangan / kronologi)
 *
 * Laporan disimpan di $_SESSION['amt_kendala'] (daftar; ikut terhapus oleh amt_kendala_reset() saat
 * layar pilih peran dibuka). Foto TIDAK disimpan di sesi (hanya penanda), karena ini layar simulasi.
 *
 * Dimuat dari roles/amt/module.php (setelah amt_rating.php).
 */

const AMT_KENDALA_SCREEN   = 'amt_kendala';
const AMT_KENDALA_NOTE_MAX = 500;       // panjang maksimal Justifikasi AMT
const AMT_KENDALA_PHOTO_MAX = 6000000;  // batas ukuran data foto (karakter dataURL)
const AMT_KENDALA_MAX_HOURS = 99;       // jam pada pemilih durasi: 00..99
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

/** URL layar Laporkan Kendala. $from = 'detail' bila dibuka dari Detail Order (tombol back kembali ke sana). */
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

/** Laporan yang sudah dikirim pada sesi ini. */
function amt_kendala_all(): array
{
    $r = $_SESSION['amt_kendala'] ?? [];
    return is_array($r) ? $r : [];
}

function amt_kendala_reset(): void
{
    unset($_SESSION['amt_kendala']);
}

/** Pengiriman yang sedang berjalan (syarat membuat laporan), atau null. */
function amt_kendala_ship(): ?array
{
    if (!work_is_running()) {
        return null;
    }
    $s = amt_ship_find(amt_ship_request_id() ?: (amt_ship_post_id() ?: null));
    return ($s !== null && $s['status'] === 'sedang') ? $s : null;
}

/** "01 Jam 15 Menit" */
function amt_kendala_duration_text(int $minutes): string
{
    return sprintf('%02d Jam %02d Menit', intdiv($minutes, 60), $minutes % 60);
}

/* ------------------------------------------------------------
 * Penjagaan akses layar (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_kendala_guard(string $screen): void
{
    if ($screen !== AMT_KENDALA_SCREEN) {
        return;
    }
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    if (amt_kendala_ship() === null) {
        go_to(amt_home_screen());   // tidak ada pengiriman yang sedang berjalan
    }
}

/* ------------------------------------------------------------
 * Proses form (POST) - pola Post/Redirect/Get
 * ------------------------------------------------------------ */

/** "Kirim Laporan Kendala". Field: id, jenis, bisa (ya|tidak), est_h, est_m, photo_data, note. */
function amt_kendala_handle_post(): void
{
    $s = amt_kendala_ship();
    if ($s === null) {
        go_to(amt_home_screen());
    }
    $back = ['id' => $s['id']];

    $jenis = (string) ($_POST['jenis'] ?? '');
    if (!isset(AMT_KENDALA_TYPES[$jenis])) {
        go_to(AMT_KENDALA_SCREEN, $back);            // jenis kendala wajib
    }

    $bisa = (string) ($_POST['bisa'] ?? '');
    if ($bisa !== 'ya' && $bisa !== 'tidak') {
        go_to(AMT_KENDALA_SCREEN, $back);
    }
    $minutes = null;
    if ($bisa === 'ya') {                            // estimasi waktu hanya untuk "Ya, Bisa Diprediksi"
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
        go_to(AMT_KENDALA_SCREEN, $back);            // foto bukti wajib
    }

    $note = preg_replace('/\s+/u', ' ', trim((string) ($_POST['note'] ?? '')));
    $note = mb_substr($note, 0, AMT_KENDALA_NOTE_MAX);
    if ($note === '') {
        go_to(AMT_KENDALA_SCREEN, $back);            // justifikasi wajib
    }

    $_SESSION['amt_kendala'][] = [
        'ship'    => $s['id'],
        'jenis'   => $jenis,
        'bisa'    => $bisa,
        'minutes' => $minutes,
        'note'    => $note,
        'photo'   => true,
        'at'      => time(),
    ];
    $_SESSION['flash_success'] = [
        'title' => 'Laporan Kendala Berhasil Dibuat',
        'body'  => 'Laporan "' . AMT_KENDALA_TYPES[$jenis] . '" sudah dikirim. Silakan menunggu konfirmasi dari Terminal.',
    ];
    go_to(amt_home_screen());
}