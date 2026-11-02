<?php

const AMT_PBK_SCREEN    = 'amt_checklist';
const AMT_PBK_LO_SCREEN = 'amt_checklist_lo';
const AMT_PBK_QR_TTL    = 180;
const AMT_PBK_QR_STEP   = 7;
const AMT_PBK_PHOTO_MAX = 1500000;

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

function amt_pbk_hint(string $type): string
{
    return [
        'spbu_task' => 'Tugas untuk Petugas SPBU dan diverifikasi oleh AMT.',
        'self'      => 'Verifikasi mandiri tugas Anda.',
        'qr'        => 'Tugas AMT dan diverifikasi oleh SPBU',
        'dual'      => 'Verifikasi pelaksanaan tugas Anda (Mandiri)',
    ][$type] ?? '';
}

function amt_pbk_has_photo(string $type): bool
{
    return $type === 'self' || $type === 'dual';
}

function amt_pbk_state(string $id): array
{
    $st = $_SESSION['amt_spbu']['pbk'][$id] ?? [];

    return $st + ['a' => [], 'b' => [], 'photo' => [], 'qr' => null, 'lo' => [], 'sel' => [], 'run' => false];
}

function amt_pbk_save(string $id, array $st): void
{
    $_SESSION['amt_spbu']['pbk'][$id] = $st;
}

function amt_pbk_lo_ids(array $s): array
{
    return array_values(array_map(function ($p) { return (string) $p['lo']; }, $s['products'] ?? []));
}

function amt_pbk_lo_status(string $id, string $lo): string
{
    $v = amt_pbk_state($id)['lo'][$lo] ?? '';
    return ($v === 'draft' || $v === 'done') ? $v : 'belum';
}

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

function amt_pbk_running(string $id): bool
{
    return !empty(amt_pbk_state($id)['run']);
}

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

function amt_pbk_answer(string $id, int $step, string $slot = 'a'): ?string
{
    $v = amt_pbk_state($id)[$slot][$step] ?? null;
    return ($v === 'ya' || $v === 'tidak') ? $v : null;
}

function amt_pbk_photo(string $id, int $step): string
{
    return (string) (amt_pbk_state($id)['photo'][$step] ?? '');
}

function amt_pbk_qr(string $id): ?array
{
    $qr = amt_pbk_state($id)['qr'];
    if (!is_array($qr) || empty($qr['token'])) {
        return null;
    }
    $qr['valid'] = (int) $qr['exp'] > time();
    return $qr;
}

function amt_pbk_qr_exp_text(array $qr): string
{
    $dt = (new DateTime('@' . (int) $qr['exp']))->setTimezone(new DateTimeZone('Asia/Jakarta'));
    return $dt->format('d/m/Y, H.i.s') . ' WIB';
}

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

function amt_pbk_lo_url(array $s): string
{
    return '?' . http_build_query(['screen' => AMT_PBK_LO_SCREEN, 'id' => $s['id']]);
}

function amt_pbk_ship_ready(?array $s): bool
{
    return $s !== null && $s['status'] === 'sedang' && amt_spbu_arrived($s);
}

function amt_pbk_guard(string $screen): void
{
    if ($screen !== AMT_PBK_SCREEN && $screen !== AMT_PBK_LO_SCREEN) {
        return;
    }
    if (!amt_work_ok()) {
        go_to(amt_home_screen());
    }
    $s = amt_ship_current();
    if ($s === null) {
        go_to('amt_shipments');
    }
    if (!amt_pbk_ship_ready($s)) {
        go_to('amt_spbu', ['id' => $s['id']]);
    }
    if (amt_pbk_done($s['id'])) {
        go_to('amt_spbu', ['id' => $s['id']]);
    }

    if ($screen === AMT_PBK_LO_SCREEN) {
        return;
    }

    if (!amt_pbk_running($s['id'])) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $s['id']]);
    }

    $total = amt_pbk_total();
    $first = min(amt_pbk_first_incomplete($s['id']), $total);
    $want  = isset($_GET['step']) ? (int) $_GET['step'] : $first;
    $want  = max(1, min($total, $want));

    if ($want > $first || !isset($_GET['step'])) {
        go_to(AMT_PBK_SCREEN, ['id' => $s['id'], 'step' => min($want, $first)]);
    }
}

function amt_pbk_handle_post(): void
{
    if (!amt_work_ok()) {
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

    foreach (($type === 'dual' ? ['a', 'b'] : ['a']) as $slot) {
        $v = $_POST[$slot] ?? '';
        if ($type !== 'qr' && ($v === 'ya' || $v === 'tidak')) {
            $st[$slot][$step] = $v;
        }
    }

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
            go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
        }
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step - 1]);
    }

    if (!amt_pbk_step_complete($id, $step)) {
        $_SESSION['amt_spbu']['pbk_error'] = $step;
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step]);
    }
    unset($_SESSION['amt_spbu']['pbk_error']);

    if ($step < $total) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $step + 1]);
    }

    $first = amt_pbk_first_incomplete($id);
    if ($first <= $total) {
        go_to(AMT_PBK_SCREEN, ['id' => $id, 'step' => $first]);
    }
    $st = amt_pbk_state($id);
    foreach (amt_pbk_selected($s) as $lo) {
        $st['lo'][$lo] = 'draft';
    }

    $st['a'] = $st['b'] = $st['photo'] = [];
    $st['qr']  = null;
    $st['run'] = false;
    amt_pbk_save($id, $st);
    $_SESSION['amt_spbu']['pbk_saved'] = [
        'title' => 'Checklist disimpan',
        'body'  => 'Checklist AMT tersimpan. Lanjutkan dengan tombol Kirim pada daftar LO.',
    ];
    go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
}

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

function amt_pbk_post_start(array $s): void
{
    $id  = $s['id'];
    $los = amt_pbk_post_los($s);
    if (!$los) {
        go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
    }
    $st = amt_pbk_state($id);

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
        go_to(AMT_PBK_LO_SCREEN, ['id' => $id]);
    }
    $st['sel'] = [];
    amt_pbk_save($id, $st);

    $_SESSION['flash_success'] = [
        'title' => 'Checklist Terkirim',
        'body'  => 'Checklist Pra Bongkar SPBU ' . $s['spbu'] . ' berhasil dikirim.',
    ];

    go_to(amt_pbk_done($id) ? 'amt_spbu' : AMT_PBK_LO_SCREEN, ['id' => $id]);
}