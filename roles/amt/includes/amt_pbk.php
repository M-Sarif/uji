<?php
/**
 * Checklist Pra Bongkar BBM (peran AMT).
 *
 * Alur layar:
 *   amt_spbu  ->  Tiba di Lokasi (dikonfirmasi)  ->  "Isi Checklist"  ->  amt_checklist_lo (Daftar LO)
 *   amt_checklist_lo  ->  pilih LO  ->  "Mulai Checklist"  ->  amt_checklist (14 langkah)
 *   amt_checklist     ->  Selesai  ->  kembali ke amt_checklist_lo (LO berstatus "Draft")
 *   amt_checklist_lo  ->  "Kirim"  ->  pop up "Ya, Kirim"  ->  LO "Sudah Diisi"
 *   Semua LO terkirim ->  kembali ke amt_spbu ("Isi Checklist" hijau, "Verifikasi Order" aktif)
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
 * Dimuat dari roles/amt/includes/amt.php (setelah amt_ship_data.php).
 */

const AMT_PBK_SCREEN    = 'amt_checklist';      // wizard 14 langkah
const AMT_PBK_LO_SCREEN = 'amt_checklist_lo';   // Daftar LO (pilih LO, Mulai Checklist, Kirim)
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
    // lo  : [nomor LO => 'draft' | 'done']   (tidak ada = "Belum Diisi")
    // sel : [nomor LO => true]               LO yang dicentang di Daftar LO
    // run : wizard sedang dikerjakan (setelah "Mulai Checklist", sebelum "Selesai")
    return $st + ['a' => [], 'b' => [], 'photo' => [], 'qr' => null, 'lo' => [], 'sel' => [], 'run' => false];
}

function amt_pbk_save(string $id, array $st): void
{
    $_SESSION['amt_spbu']['pbk'][$id] = $st;
}

/** Nomor LO milik pengiriman (urutan sama dengan Order List di layar SPBU). */
function amt_pbk_lo_ids(array $s): array
{
    return array_values(array_map(function ($p) { return (string) $p['lo']; }, $s['products'] ?? []));
}

/** Status checklist satu LO: 'belum' | 'draft' | 'done'. */
function amt_pbk_lo_status(string $id, string $lo): string
{
    $v = amt_pbk_state($id)['lo'][$lo] ?? '';
    return ($v === 'draft' || $v === 'done') ? $v : 'belum';
}

/** LO yang dicentang di Daftar LO (hanya yang valid & belum terkirim). */
function amt_pbk_selected(array $s): array
{
    $sel = amt_pbk_state($s['id'])['sel'];
    $out = [];
    foreach (amt_pbk_lo_ids($s) as $lo) {
        if (!empty($sel[$lo]) && amt_pbk_lo_status($s['id'], $lo) !== 'done') {
            $out[] = $lo;
        }
    }
    return $out;
}

/** Wizard 14 langkah sedang dikerjakan? */
function amt_pbk_running(string $id): bool
{
    return !empty(amt_pbk_state($id)['run']);
}

/** Checklist dianggap selesai bila SEMUA LO pengiriman ini sudah dikirim ("Sudah Diisi"). */
function amt_pbk_done(string $id): bool
{
    $s = amt_ship_find($id);
    if ($s === null) {
        return false;
    }
    $los = amt_pbk_lo_ids($s);
    if (!$los) {
        return false;
    }
    foreach ($los as $lo) {
        if (amt_pbk_lo_status($id, $lo) !== 'done') {
            return false;
        }
    }
    return true;
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

/** Teks "Berlaku sampai" di pop up QR Code, mis. 02/10/2026, 08.56.20 WIB */
function amt_pbk_qr_exp_text(array $qr): string
{
    $dt = (new DateTime('@' . (int) $qr['exp']))->setTimezone(new DateTimeZone('Asia/Jakarta'));
    return $dt->format('d/m/Y, H.i.s') . ' WIB';
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

/** Daftar LO ("Checklist Pra-Pembongkaran"): pintu masuk dari langkah "Isi Checklist". */
function amt_pbk_lo_url(array $s): string
{
    return '?' . http_build_query(['screen' => AMT_PBK_LO_SCREEN, 'id' => $s['id']]);
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
    if ($screen !== AMT_PBK_SCREEN && $screen !== AMT_PBK_LO_SCREEN) {
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
        go_to('amt_spbu', ['id' => $s['id']]);       // semua LO sudah dikirim
    }

    // Daftar LO: boleh dibuka kapan saja setelah tiba di lokasi
    if ($screen === AMT_PBK_LO_SCREEN) {
        return;
    }

    // Wizard 14 langkah: hanya setelah "Mulai Checklist" di Daftar LO
    if (!amt_pbk_running($s['id'])) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $s['id']]);
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
 *
 * Field 'phase' menentukan tahap:
 *   start  -> "Mulai Checklist" di Daftar LO (lo[] = LO yang dicentang)
 *   send   -> "Ya, Kirim" di pop up Kirim Checklist (lo[] = LO yang dicentang)
 *   (kosong) -> navigasi wizard 14 langkah
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

    $phase = (string) ($_POST['phase'] ?? '');
    if ($phase === 'start') {
        amt_pbk_post_start($s);
    }
    if ($phase === 'send') {
        amt_pbk_post_send($s);
    }

    // Wizard hanya boleh diproses saat sedang berjalan
    if (!amt_pbk_running($id)) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
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

    // "Generate QR Code" (langkah 7) / "Regenerate" di pop up QR Code (langkah 7 dst).
    // Setelah dibuat, pop up QR Code langsung dibuka (?qr=1).
    if ($nav === 'qr' && $step >= AMT_PBK_QR_STEP) {
        $now = time();
        $st['qr'] = ['token' => bin2hex(random_bytes(12)), 'at' => $now, 'exp' => $now + AMT_PBK_QR_TTL];
        amt_pbk_save($id, $st);
        unset($_SESSION['amt_spbu']['pbk_error']);
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step, 'qr' => 1]);
    }

    amt_pbk_save($id, $st);

    if ($nav === 'prev') {
        unset($_SESSION['amt_spbu']['pbk_error']);
        if ($step <= 1) {
            go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);     // kembali ke Daftar LO
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

    // Langkah terakhir: pastikan semua langkah lengkap, lalu LO yang dipilih menjadi "Draft"
    $first = amt_pbk_first_incomplete($id);
    if ($first <= $total) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $first]);
    }
    $st = amt_pbk_state($id);
    foreach (amt_pbk_selected($s) as $lo) {
        $st['lo'][$lo] = 'draft';
    }
    // Jawaban wizard dikosongkan supaya Mulai Checklist berikutnya (LO lain) mulai dari langkah 1
    $st['a'] = $st['b'] = $st['photo'] = [];
    $st['qr']  = null;
    $st['run'] = false;
    amt_pbk_save($id, $st);
    go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
}

/** LO yang dicentang di form Daftar LO (lo[]), hanya yang valid dan belum terkirim. */
function amt_pbk_post_los(array $s): array
{
    $picked = array_map('strval', (array) ($_POST['lo'] ?? []));
    $out = [];
    foreach (amt_pbk_lo_ids($s) as $lo) {
        if (in_array($lo, $picked, true) && amt_pbk_lo_status($s['id'], $lo) !== 'done') {
            $out[] = $lo;
        }
    }
    return $out;
}

/** "Mulai Checklist": simpan LO yang dicentang lalu buka wizard (langkah pertama yang belum lengkap). */
function amt_pbk_post_start(array $s): void
{
    $id  = $s['id'];
    $los = amt_pbk_post_los($s);
    if (!$los) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);      // tidak ada LO yang dipilih
    }
    $st = amt_pbk_state($id);

    // Pilihan LO sama dan wizard belum selesai -> lanjutkan jawaban yang sudah ada.
    // Pilihan berbeda (atau mengulang LO "Draft") -> mulai dari awal.
    $same = $st['run'] && amt_pbk_selected($s) === $los;
    $st['sel'] = array_fill_keys($los, true);
    if (!$same) {
        $st['a'] = $st['b'] = $st['photo'] = [];
        $st['qr'] = null;
    }
    $st['run'] = true;
    amt_pbk_save($id, $st);
    unset($_SESSION['amt_spbu']['pbk_error']);

    $first = min(amt_pbk_first_incomplete($id), amt_pbk_total());
    go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $first]);
}

/** "Ya, Kirim": LO berstatus "Draft" yang dicentang menjadi "Sudah Diisi". */
function amt_pbk_post_send(array $s): void
{
    $id = $s['id'];
    $st = amt_pbk_state($id);
    $sent = 0;
    foreach (amt_pbk_post_los($s) as $lo) {
        if (($st['lo'][$lo] ?? '') === 'draft') {
            $st['lo'][$lo] = 'done';
            $sent++;
        }
    }
    if ($sent === 0) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);      // tidak ada draft yang bisa dikirim
    }
    $st['sel'] = [];
    amt_pbk_save($id, $st);

    $_SESSION['flash_success'] = [
        'title' => 'Checklist Terkirim',
        'body'  => 'Checklist Pra Bongkar SPBU ' . $s['spbu'] . ' berhasil dikirim.',
    ];
    // Semua LO terkirim -> kembali ke Aktifitas di SPBU; masih ada LO lain -> tetap di Daftar LO
    go_to(amt_pbk_done($id) ? 'amt_spbu' : AMT_PBK_LO_SCREEN, ['id' => $id]);
}