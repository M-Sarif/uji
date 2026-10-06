<?php
/**
 * Rating Petugas SPBU + Selesaikan Order (peran AMT) - langkah ke-5 "Aktifitas di SPBU".
 * Letak: roles/amt/includes/amt_rating.php
 *
 * Alur layar:
 *   amt_spbu (Foto Surat Jalan sudah tersimpan)  ->  "Rating Petugas SPBU" aktif  ->  amt_rating (Beri Penilaian)
 *   amt_rating  ->  "Kirim" (POST submit_rating)  ->  kembali ke amt_spbu: semua langkah hijau, tombol "Selesai" aktif
 *   amt_spbu    ->  "Selesai"  ->  pop up "Menyelesaikan Order"  ->  "Kirim" (POST finish_order)  ->  beranda AMT
 *
 * State disimpan di $_SESSION['amt_spbu'] (ikut terhapus oleh amt_spbu_reset() saat Start/End Work):
 *   ['rating'][<id pengiriman>]   = ['officer' => nama, 'scores' => [kunci => 1..5], 'note' => teks, 'at' => unix]
 *   ['finished'][<id pengiriman>] = unix  -> pengiriman dianggap "Selesai Dikirim" (lihat amt_ship_all())
 *
 * Dimuat dari roles/amt/module.php (setelah amt_sj.php).
 */

const AMT_RATING_SCREEN   = 'amt_rating';
const AMT_RATING_NOTE_MAX = 500;   // panjang maksimal "Ulasan dan Komentar"
const AMT_RATING_NAME_MAX = 100;   // panjang maksimal nama petugas SPBU

/** Teks apresiasi per jumlah bintang (tampil begitu bintang diketuk). */
const AMT_RATING_TITLES = [1 => 'Sangat Kurang', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Luar Biasa!'];
const AMT_RATING_THANKS = 'Terima kasih! Penilaian ini sangat berarti bagi kami.';

/** Pertanyaan penilaian (urutan = urutan kartu di layar). 'tag' = label kecil di bawah pertanyaan. */
function amt_rating_questions(): array
{
    return [
        'safety'      => ['q' => 'Apakah pembongkaran BBM dilakukan oleh petugas khusus pembongkaran dan menggunakan APD dengan benar?', 'tag' => 'Safety Petugas Bongkar', 'req' => true],
        'sarfas'      => ['q' => 'Apakah sarana dan fasilitas SPBU/Pertashop safety untuk dilakukan proses pembongkaran BBM?',          'tag' => 'Sarfas',                 'req' => true],
        'komunikasi'  => ['q' => 'Apakah SPBU/Pertashop transparan dalam pengukuran bersama volume BBM sebelum pembongkaran?',        'tag' => 'Komunikasi',             'req' => true],
        'operasional' => ['q' => 'Apakah SPBU/Pertashop melakukan stop penjualan pada tangki BBM yang sedang dilakukan proses pembongkaran?', 'tag' => 'Operasional',     'req' => true],
        'layanan'     => ['q' => 'Waktu tunggu bongkar',                                                                                   'tag' => 'Aspek Layanan',          'req' => true],
    ];
}

/* ------------------------------------------------------------
 * State
 * ------------------------------------------------------------ */

function amt_rating_state(string $id): array
{
    $st = $_SESSION['amt_spbu']['rating'][$id] ?? [];
    return (is_array($st) ? $st : []) + ['officer' => '', 'scores' => [], 'note' => '', 'at' => 0];
}

/** Rating pengiriman ini sudah dikirim? */
function amt_rating_done(string $id): bool
{
    return amt_rating_state($id)['at'] > 0;
}

/** Order pengiriman ini sudah diselesaikan (tombol "Selesai" -> "Kirim")? */
function amt_order_finished(string $id): bool
{
    return !empty($_SESSION['amt_spbu']['finished'][$id]);
}

function amt_rating_url(array $s): string
{
    return '?' . http_build_query(['screen' => AMT_RATING_SCREEN, 'id' => $s['id']]);
}

/** Boleh memberi rating: berjalan, sudah tiba, checklist + verifikasi + surat jalan selesai. */
function amt_rating_ship_ready(?array $s): bool
{
    return amt_sj_ship_ready($s) && amt_sj_done($s['id']);
}

/* ------------------------------------------------------------
 * Penjagaan akses layar (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_rating_guard(string $screen): void
{
    if ($screen !== AMT_RATING_SCREEN) {
        return;
    }
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_current();
    if ($s === null) {
        go_to('amt_shipments');
    }
    if (!amt_rating_ship_ready($s) || amt_rating_done($s['id'])) {
        go_to('amt_spbu', ['id' => $s['id']]);   // belum waktunya / sudah terkirim
    }
}

/* ------------------------------------------------------------
 * Proses form (POST) - pola Post/Redirect/Get
 * ------------------------------------------------------------ */

/** "Kirim" di layar Beri Penilaian. Field: id, officer, rating[<kunci>] = 1..5, note (opsional). */
function amt_rating_handle_post(): void
{
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_find(amt_ship_post_id() ?: null);
    if ($s === null || $s['status'] !== 'sedang') {
        go_to('amt_shipments');
    }
    $id   = $s['id'];
    $back = ['id' => $id];
    if (!amt_rating_ship_ready($s) || amt_rating_done($id)) {
        go_to('amt_spbu', $back);
    }

    $officer = trim((string) ($_POST['officer'] ?? ''));
    $officer = preg_replace('/\s+/u', ' ', $officer);
    if ($officer === '' || mb_strlen($officer) > AMT_RATING_NAME_MAX) {
        go_to(AMT_RATING_SCREEN, $back);          // nama petugas wajib
    }

    $in     = (isset($_POST['rating']) && is_array($_POST['rating'])) ? $_POST['rating'] : [];
    $scores = [];
    foreach (array_keys(amt_rating_questions()) as $key) {
        $v = $in[$key] ?? null;
        if (!is_string($v) || !preg_match('/^[1-5]$/', $v)) {
            go_to(AMT_RATING_SCREEN, $back);      // semua pertanyaan wajib dinilai
        }
        $scores[$key] = (int) $v;
    }

    $note = trim((string) ($_POST['note'] ?? ''));
    $note = mb_substr($note, 0, AMT_RATING_NOTE_MAX);

    $_SESSION['amt_spbu']['rating'][$id] = [
        'officer' => $officer,
        'scores'  => $scores,
        'note'    => $note,
        'at'      => time(),
    ];
    go_to('amt_spbu', $back);                     // halaman Detail Order: tombol "Selesai" sudah aktif
}

/** "Kirim" di pop up "Menyelesaikan Order": pengiriman selesai -> kembali ke beranda AMT. */
function amt_order_finish_post(): void
{
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_find(amt_ship_post_id() ?: null);
    if ($s === null || $s['status'] !== 'sedang') {
        go_to('amt_shipments');
    }
    $id = $s['id'];
    // Hanya bila semua langkah (termasuk rating) sudah selesai
    if (!amt_rating_ship_ready($s) || !amt_rating_done($id)) {
        go_to('amt_spbu', ['id' => $id]);
    }
    $_SESSION['amt_spbu']['finished'][$id] = time();
    $_SESSION['flash_success'] = [
        'title' => 'Order Selesai',
        'body'  => 'Pengiriman ke SPBU ' . $s['spbu'] . ' sudah diselesaikan.',
    ];
    go_to(amt_home_screen());
}