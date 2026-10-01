<?php
/**
 * Checklist Pra Bongkar BBM (peran AMT).
 *
 * Alur layar:
 *   amt_spbu  ->  Tiba di Lokasi (dikonfirmasi)  ->  "Isi Checklist"  ->  amt_checklist (14 langkah)
 *             <-  Selesai (kembali ke amt_spbu, langkah "Isi Checklist" hijau, "Verifikasi Order" aktif)
 *
 * Tipe langkah (kunci 'type'):
 *   spbu_task  Tugas Petugas SPBU, diverifikasi AMT        -> 1 pilihan Ya/Tidak (tanpa foto)
 *   self       Verifikasi mandiri tugas AMT                 -> 1 pilihan Ya/Tidak + foto (opsional)
 *   qr         Tugas AMT, diverifikasi SPBU lewat QR Code   -> tombol Generate / Regenerate QR Code
 *   dual       Mandiri + verifikasi tugas role lawan        -> 2 pilihan Ya/Tidak + foto (opsional)
 *
 * State disimpan di $_SESSION['amt_spbu']['pbk'][<id pengiriman>], jadi ikut terhapus
 * oleh amt_spbu_reset() (Start Work / End Work / kembali ke pilih peran).
 *
 * Dimuat dari includes/amt/amt.php (setelah amt_ship_data.php).
 */

const AMT_PBK_SCREEN    = 'amt_checklist';
const AMT_PBK_QR_TTL    = 180;        // detik: QR Code berlaku 3 menit sejak dibuat
const AMT_PBK_QR_STEP   = 7;          // langkah yang membuat QR Code
const AMT_PBK_PHOTO_MAX = 1500000;    // batas ukuran foto (dataURL) per langkah, dalam karakter

/**
 * Daftar langkah (urutan = nomor langkah 1..14).
 * Langkah 11 tidak ada di screenshot yang dikirim, jadi teksnya memakai langkah 11 checklist SPBU
 * dan diperlakukan sebagai 'dual' (sama seperti langkah 11 di checklist SPBU). Ubah di sini bila berbeda.
 */
function amt_pbk_steps(): array
{
    return [
        1  => ['type' => 'spbu_task', 'text' => 'Pastikan tersedianya volume ruang kosong dalam tangki.'],
        2  => ['type' => 'self',      'text' => 'Tempatkan mobil tangki pada posisi pembongkaran yang benar.'],
        3  => ['type' => 'self',      'text' => 'Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.'],
        4  => ['type' => 'self',      'text' => 'Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.'],
        5  => ['type' => 'self',      'text' => 'Pasang kabel arde dan yakinkan terpasang dengan benar.'],
        6  => ['type' => 'spbu_task', 'text' => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.'],
        7  => ['type' => 'qr',        'text' => 'Persiapkan alat ukur, buka tutup manhole atas mobil tangki BBM, periksa jenis dan volume BBM dari IJK bout-nya, dan pastikan sertifikat tera sesuai dengan ijk bout aktual di mobil tangki dan ditutup kembali.'],
        8  => ['type' => 'spbu_task', 'text' => 'Pemeriksaan Sampel BBM Ambil sampel BBM dari kerangan pada box bottom loader menggunakan wadah berbahan logam.Ukur dan cocokkan dengan SG yang tercantum di dokumen pengiriman serta lakukan pemeriksaan free water. Laporkan jika ada selisih mencolok.'],
        9  => ['type' => 'spbu_task', 'text' => 'Pasang selang bongkar pada inlet pipa tangki (filling point), pastikan kesesuaian tangki penerima dengan produk yang akan dibongkar, kemudian pada outlet mobil tangki (gunakan quick coupling).'],
        10 => ['type' => 'spbu_task', 'text' => 'Lakukan pembongkaran dengan membuka kerangan sedikit demi sedikit. Pastikan tidak ada kebocoran pada selang maupun sambungan/coupling. (Khusus Pertashop): Pastikan tidak menggunakan pompa Alcon berbahan bakar bensin serta wajib menggunakan pompa yang telah memiliki izin tipe dari Metrologi dengan surat tera yang masih aktif sebagai pompa transfer BBM.'],
        11 => ['type' => 'dual',      'text' => 'Selesai melakukan bongkar, pastikan: Muatan BBM di mobil tangki benar-benar telah habis dan lakukan pengukuran volume BBM di dalam tangki penerima. Pastikan manhole atas tertutup sempurna.'],
        12 => ['type' => 'spbu_task', 'text' => 'Tutup kerangan, lepas selang bongkar dimulai dari mobil tangki dan tutup kembali lubang pengisian dari mobil tangki serta dipastikan tidak ada genangan BBM.'],
        13 => ['type' => 'self',      'text' => 'Lepas kabel arde, kembalikan alat pemadam ke tempat semula dan Pastikan segel bekas dibawa kembali dan diserahkan ke Terminal.'],
        14 => ['type' => 'dual',      'text' => 'Selesaikan proses administrasi dan dokumen wajib ditandatangani bersama.'],
    ];
}

function amt_pbk_total(): int
{
    return count(amt_pbk_steps());
}

/** Teks petunjuk di dalam kotak jawaban, per tipe langkah. */
function amt_pbk_hint(string $type): string
{
    return [
        'spbu_task' => 'Tugas untuk Petugas SPBU dan diverifikasi oleh AMT.',
        'self'      => 'Verifikasi mandiri tugas Anda.',
        'qr'        => 'Tugas AMT dan diverifikasi oleh SPBU',
        'dual'      => 'Verifikasi pelaksanaan tugas Anda (Mandiri)',
    ][$type] ?? '';
}

/** Apakah tipe langkah ini punya kotak foto (opsional)? */
function amt_pbk_has_photo(string $type): bool
{
    return $type === 'self' || $type === 'dual';
}

/* ------------------------------------------------------------
 * State per pengiriman
 * ------------------------------------------------------------ */

function amt_pbk_state(string $id): array
{
    $st = $_SESSION['amt_spbu']['pbk'][$id] ?? [];
    return $st + ['a' => [], 'b' => [], 'photo' => [], 'qr' => null, 'done' => false];
}

function amt_pbk_save(string $id, array $st): void
{
    $_SESSION['amt_spbu']['pbk'][$id] = $st;
}

function amt_pbk_done(string $id): bool
{
    return !empty($_SESSION['amt_spbu']['pbk'][$id]['done']);
}

/** Jawaban 'ya' | 'tidak' | null. $slot: 'a' (utama / mandiri) atau 'b' (role lawan, hanya tipe dual). */
function amt_pbk_answer(string $id, int $step, string $slot = 'a'): ?string
{
    $v = amt_pbk_state($id)[$slot][$step] ?? null;
    return ($v === 'ya' || $v === 'tidak') ? $v : null;
}

function amt_pbk_photo(string $id, int $step): string
{
    return (string) (amt_pbk_state($id)['photo'][$step] ?? '');
}

/** QR Code terakhir: ['token','at','exp','valid'] atau null bila belum pernah dibuat. */
function amt_pbk_qr(string $id): ?array
{
    $qr = amt_pbk_state($id)['qr'];
    if (!is_array($qr) || empty($qr['token'])) {
        return null;
    }
    $qr['valid'] = (int) $qr['exp'] > time();
    return $qr;
}

/** Isi yang dibaca pemindai: tidak memuat data sensitif, hanya penanda pengiriman + token sekali pakai. */
function amt_pbk_qr_payload(array $s, array $qr): string
{
    return implode('|', ['ONEFIS-PBK', $s['id'], $s['spbu'], $qr['token'], $qr['exp']]);
}

function amt_pbk_step_complete(string $id, int $step): bool
{
    $def = amt_pbk_steps()[$step] ?? null;
    if ($def === null) {
        return false;
    }
    if ($def['type'] === 'qr') {
        return amt_pbk_qr($id) !== null;
    }
    if ($def['type'] === 'dual') {
        return amt_pbk_answer($id, $step, 'a') !== null && amt_pbk_answer($id, $step, 'b') !== null;
    }
    return amt_pbk_answer($id, $step, 'a') !== null;
}

/** Langkah pertama yang belum lengkap; total + 1 bila semuanya sudah lengkap. */
function amt_pbk_first_incomplete(string $id): int
{
    $total = amt_pbk_total();
    for ($i = 1; $i <= $total; $i++) {
        if (!amt_pbk_step_complete($id, $i)) {
            return $i;
        }
    }
    return $total + 1;
}

function amt_pbk_url(array $s, ?int $step = null): string
{
    $q = ['screen' => AMT_PBK_SCREEN, 'id' => $s['id']];
    if ($step !== null) {
        $q['step'] = $step;
    }
    return '?' . http_build_query($q);
}

/** Pengiriman yang boleh mengisi checklist: sedang berjalan DAN "Tiba di Lokasi" sudah dikonfirmasi. */
function amt_pbk_ship_ready(?array $s): bool
{
    return $s !== null && $s['status'] === 'sedang' && amt_spbu_arrived($s);
}

/* ------------------------------------------------------------
 * Penjagaan akses layar (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_pbk_guard(string $screen): void
{
    if ($screen !== AMT_PBK_SCREEN) {
        return;
    }
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_current();
    if ($s === null) {
        go_to('amt_shipments');
    }
    if (!amt_pbk_ship_ready($s)) {
        go_to('amt_spbu', ['id' => $s['id']]);       // belum tiba / sudah selesai
    }
    if (amt_pbk_done($s['id'])) {
        go_to('amt_spbu', ['id' => $s['id']]);       // checklist sudah dikirim
    }

    $total = amt_pbk_total();
    $first = min(amt_pbk_first_incomplete($s['id']), $total);
    $want  = isset($_GET['step']) ? (int) $_GET['step'] : $first;
    $want  = max(1, min($total, $want));

    // Tidak boleh melompat melewati langkah yang belum diisi
    if ($want > $first || !isset($_GET['step'])) {
        go_to(AMT_PBK_SCREEN, ['id' => $s['id'], 'step' => min($want, $first)]);
    }
}

/* ------------------------------------------------------------
 * Proses form (POST, aksi "submit_pbk") - pola Post/Redirect/Get
 * ------------------------------------------------------------ */
function amt_pbk_handle_post(): void
{
    if (!work_is_running()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_find(amt_ship_post_id() ?: null);
    if ($s === null || $s['status'] !== 'sedang') {
        go_to('amt_shipments');
    }
    $id = $s['id'];
    if (!amt_spbu_arrived($s) || amt_pbk_done($id)) {
        go_to('amt_spbu', ['id' => $id]);
    }

    $steps = amt_pbk_steps();
    $total = count($steps);
    $step  = isset($_POST['step']) ? (int) $_POST['step'] : 1;
    if (!isset($steps[$step])) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => 1]);
    }
    $type = $steps[$step]['type'];
    $st   = amt_pbk_state($id);

    // Jawaban Ya/Tidak ('a' = utama/mandiri, 'b' = role lawan hanya untuk tipe dual)
    foreach (($type === 'dual' ? ['a', 'b'] : ['a']) as $slot) {
        $v = $_POST[$slot] ?? '';
        if ($type !== 'qr' && ($v === 'ya' || $v === 'tidak')) {
            $st[$slot][$step] = $v;
        }
    }

    // Foto bukti (opsional). Hanya gambar dengan ukuran wajar; field kosong = tetap pakai foto lama.
    if (amt_pbk_has_photo($type)) {
        $photo = (string) ($_POST['photo_data'] ?? '');
        if ($photo !== ''
            && preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$#', $photo)
            && strlen($photo) <= AMT_PBK_PHOTO_MAX
        ) {
            $st['photo'][$step] = $photo;
        }
    }

    $nav = (string) ($_POST['nav'] ?? 'next');

    // Langkah QR: "Generate QR Code" / "Regenerate QR Code"
    if ($type === 'qr' && $nav === 'qr') {
        $now = time();
        $st['qr'] = ['token' => bin2hex(random_bytes(8)), 'at' => $now, 'exp' => $now + AMT_PBK_QR_TTL];
        amt_pbk_save($id, $st);
        unset($_SESSION['amt_spbu']['pbk_error']);
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step]);
    }

    amt_pbk_save($id, $st);

    if ($nav === 'prev') {
        unset($_SESSION['amt_spbu']['pbk_error']);
        if ($step <= 1) {
            go_to('amt_spbu', ['id' => $id]);
        }
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step - 1]);
    }

    // "Selanjutnya" / "Selesai": langkah ini harus lengkap
    if (!amt_pbk_step_complete($id, $step)) {
        $_SESSION['amt_spbu']['pbk_error'] = $step;
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step]);
    }
    unset($_SESSION['amt_spbu']['pbk_error']);

    if ($step < $total) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step + 1]);
    }

    // Langkah terakhir: pastikan semua langkah lengkap, lalu tandai selesai
    $first = amt_pbk_first_incomplete($id);
    if ($first <= $total) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $first]);
    }
    $st = amt_pbk_state($id);
    $st['done'] = time();
    amt_pbk_save($id, $st);
    $_SESSION['flash_success'] = [
        'title' => 'Checklist Terkirim',
        'body'  => 'Checklist Pra Bongkar SPBU ' . $s['spbu'] . ' berhasil disimpan.',
    ];
    go_to('amt_spbu', ['id' => $id]);
}