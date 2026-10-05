<?php
/**
 * Foto Surat Jalan (peran AMT) - langkah ke-4 "Aktifitas di SPBU".
 * Letak: roles/amt/includes/amt_sj.php
 *
 * Alur layar:
 *   amt_spbu  ->  Verifikasi Order selesai  ->  "Foto Surat Jalan" aktif  ->  amt_surat_jalan
 *   amt_surat_jalan  ->  tiap Nomor LO: "Ambil Foto" (kamera belakang) -> Foto -> Simpan Foto / Ambil Ulang
 *                    ->  semua LO punya foto  ->  tombol "Simpan Foto" aktif  ->  POST submit_sj
 *   submit_sj  ->  foto tersimpan, kembali ke amt_spbu ("Foto Surat Jalan" hijau, Status Surat Jalan "Sudah Ditambahkan")
 *
 * State disimpan di $_SESSION['amt_spbu']['sj'][<id pengiriman>], jadi ikut terhapus oleh
 * amt_spbu_reset() (Start Work / End Work / kembali ke pilih peran).
 *
 * Dimuat dari roles/amt/module.php (setelah amt_verif_functions.php).
 */

const AMT_SJ_SCREEN    = 'amt_surat_jalan';
const AMT_SJ_PHOTO_MAX = 3000000;   // batas ukuran satu foto (dataURL) dalam karakter
const AMT_SJ_MAX_SIDE  = 1600;      // sisi terpanjang hasil foto (px), dikirim ke camera.js

/* ------------------------------------------------------------
 * State
 * ------------------------------------------------------------ */

function amt_sj_state(string $id): array
{
    $st = $_SESSION['amt_spbu']['sj'][$id] ?? [];
    // done   : foto surat jalan sudah disimpan
    // photos : [nomor LO => dataURL]
    // at     : waktu simpan (unix)
    return (is_array($st) ? $st : []) + ['done' => false, 'photos' => [], 'at' => 0];
}

/** Semua foto surat jalan pengiriman ini sudah disimpan? */
function amt_sj_done(string $id): bool
{
    return !empty(amt_sj_state($id)['done']);
}

/** Foto surat jalan satu LO sudah disimpan? */
function amt_sj_lo_done(string $id, string $lo): bool
{
    $st = amt_sj_state($id);
    return !empty($st['done']) && !empty($st['photos'][$lo]);
}

/** dataURL foto surat jalan satu LO ('' bila belum ada). */
function amt_sj_photo(string $id, string $lo): string
{
    $p = amt_sj_state($id)['photos'][$lo] ?? '';
    return is_string($p) ? $p : '';
}

function amt_sj_url(array $s): string
{
    return '?' . http_build_query(['screen' => AMT_SJ_SCREEN, 'id' => $s['id']]);
}

/** Pengiriman boleh mengisi surat jalan: berjalan, sudah tiba, checklist terkirim, SEMUA LO terverifikasi. */
function amt_sj_ship_ready(?array $s): bool
{
    return $s !== null
        && $s['status'] === 'sedang'
        && amt_spbu_arrived($s)
        && amt_pbk_done($s['id'])
        && amt_verif_is_done($s);
}

/* ------------------------------------------------------------
 * Penjagaan akses layar (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_sj_guard(string $screen): void
{
    if ($screen !== AMT_SJ_SCREEN) {
        return;
    }
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_current();
    if ($s === null) {
        go_to('amt_shipments');
    }
    if (!amt_sj_ship_ready($s) || amt_sj_done($s['id'])) {
        go_to('amt_spbu', ['id' => $s['id']]);   // belum waktunya / sudah tersimpan
    }
}

/* ------------------------------------------------------------
 * Proses form (POST, aksi "submit_sj") - pola Post/Redirect/Get
 * Field: id (pengiriman), photo[<nomor LO>] = dataURL gambar (wajib untuk SETIAP LO)
 * ------------------------------------------------------------ */

/** dataURL gambar yang sah (jpeg/png/webp, base64 valid, ukuran wajar)? */
function amt_sj_valid_photo($data): bool
{
    if (!is_string($data) || $data === '' || strlen($data) > AMT_SJ_PHOTO_MAX) {
        return false;
    }
    if (!preg_match('#^data:image/(jpeg|png|webp);base64,#', substr($data, 0, 40))) {
        return false;
    }
    $b64 = substr($data, strpos($data, ',') + 1);
    return $b64 !== '' && base64_decode($b64, true) !== false;
}

function amt_sj_handle_post(): void
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
    if (!amt_sj_ship_ready($s) || amt_sj_done($id)) {
        go_to('amt_spbu', $back);
    }

    $in     = (isset($_POST['photo']) && is_array($_POST['photo'])) ? $_POST['photo'] : [];
    $photos = [];
    foreach (amt_pbk_lo_ids($s) as $lo) {
        $data = $in[$lo] ?? null;
        if (!amt_sj_valid_photo($data)) {
            go_to(AMT_SJ_SCREEN, $back);          // ada LO tanpa foto yang sah: tidak disimpan
        }
        $photos[$lo] = $data;
    }

    $_SESSION['amt_spbu']['sj'][$id] = ['done' => true, 'photos' => $photos, 'at' => time()];
    $_SESSION['flash_success'] = [
        'title' => 'Foto Surat Jalan Tersimpan',
        'body'  => 'Surat jalan untuk ' . count($photos) . ' LO sudah ditambahkan.',
    ];
    go_to('amt_spbu', $back);
}