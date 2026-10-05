<?php
/**
 * Fungsi PTI + alur tutorial setelah Check-In untuk role AMT.
 * State disimpan di $_SESSION (sama seperti pola versi PHP native lainnya).
 */

require_once __DIR__ . '/amt_pti_data.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function amt_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function amt_pti_url($screen, array $params = [])
{
    return '?' . http_build_query(array_merge(['screen' => $screen], $params));
}

function amt_pti_redirect($screen, array $params = [])
{
    header('Location: ' . amt_pti_url($screen, $params));
    exit;
}

/* ---------- Shipment ---------- */

/**
 * Shipment aktif untuk AMT. Kembalikan null kalau belum ada shipment.
 * GANTI dengan sumber data asli. Untuk uji tampilan kosong:
 *   $_SESSION['amt_has_shipment'] = false;
 */
function amt_pti_shipment()
{
    if (isset($_SESSION['amt_has_shipment']) && !$_SESSION['amt_has_shipment']) {
        return null;
    }
    return isset($_SESSION['amt_shipment'])
        ? $_SESSION['amt_shipment']
        : ['nomor_polisi' => 'B9796SFU', 'kapasitas' => '10.000 L'];
}

/* ---------- Alur Check-In -> tutorial DCU -> PTI aktif -> tutorial PTI ---------- */

function amt_flow_defaults()
{
    return ['checkin' => false, 'dcu_seen' => false, 'dcu_seen_at' => 0, 'pti_unlocked' => false, 'pti_hint_seen' => false, 'pti_tutorial_seen' => false, 'pti_done' => false];
}

function amt_flow_get($key)
{
    $flow = isset($_SESSION['amt_flow']) ? $_SESSION['amt_flow'] : amt_flow_defaults();
    return !empty($flow[$key]);
}

function amt_flow_set($key)
{
    if (!isset($_SESSION['amt_flow'])) {
        $_SESSION['amt_flow'] = amt_flow_defaults();
    }
    $_SESSION['amt_flow'][$key] = true;
}

/** PANGGIL saat Check-In berhasil (di handler Check-In AMT). Memulai alur tutorial DCU.
 *  Kalau PTI sudah pernah aktif di shift ini, statusnya dipertahankan (tidak terkunci lagi). */
function amt_flow_checkin_success()
{
    $prev = isset($_SESSION['amt_flow']) ? $_SESSION['amt_flow'] : amt_flow_defaults();
    $_SESSION['amt_flow'] = amt_flow_defaults();
    $_SESSION['amt_flow']['checkin'] = true;
    foreach (['pti_unlocked', 'pti_hint_seen'] as $keep) {
        if (!empty($prev[$keep])) {
            $_SESSION['amt_flow'][$keep] = true;
        }
    }
}

/** Waktu (timestamp) Check-In terakhir, 0 bila belum ada. */
function amt_last_checkin_at()
{
    $list = work_checkins();
    if (!$list) {
        return 0;
    }
    $last = end($list);
    return (int) (isset($last['at']) ? $last['at'] : 0);
}

/**
 * Sisa detik sampai PTI aktif otomatis (failsafe), 0 bila sudah lewat / belum Check-In.
 * Dipakai JS supaya tombol PTI menyala tanpa perlu memuat ulang halaman.
 */
function amt_pti_failsafe_remaining()
{
    $at = amt_last_checkin_at();
    return $at > 0 ? max(0, $at + AMT_PTI_FAILSAFE_SECONDS - time()) : 0;
}

/**
 * PTI aktif kalau (sedang bekerja) DAN sudah Check-In DAN salah satu:
 *   1. sudah aktif sebelumnya (pti_unlocked), atau
 *   2. tutorial DCU sudah ditutup (Mengerti/Lewati) lebih dari (AMT_PTI_UNLOCK_DELAY - 1 detik), atau
 *   3. FAILSAFE: sudah AMT_PTI_FAILSAFE_SECONDS detik sejak Check-In terakhir.
 * Failsafe ini yang mencegah PTI "menunggu selamanya" bila konfirmasi tutorial
 * tidak sampai ke server.
 */
function amt_pti_unlocked()
{
    if (!work_is_running() || amt_last_checkin_at() === 0) {
        return false;
    }
    if (amt_flow_get('pti_unlocked')) {
        return true;
    }
    $at = isset($_SESSION['amt_flow']['dcu_seen_at']) ? (float) $_SESSION['amt_flow']['dcu_seen_at'] : 0;
    if ($at > 0 && microtime(true) >= $at + (AMT_PTI_UNLOCK_DELAY / 1000) - 1) {
        return true;
    }
    return amt_pti_failsafe_remaining() === 0;
}

/**
 * Atribut untuk tile PTI di dashboard (render dari server, supaya tidak berkedip).
 * Pasang di ELEMEN TERLUAR tile PTI (ikon + labelnya):
 *   <a href="?screen=amt_pti" data-amt-pti <?= amt_pti_icon_attrs() ?>> ... </a>
 */
function amt_pti_icon_attrs()
{
    return amt_pti_unlocked()
        ? 'data-amt-unlocked="true"'
        : 'data-amt-unlocked="false" aria-disabled="true"';
}

/** Markup modal tutorial (tampilannya diatur amt-pti.css, perilakunya assets/amt-flow.js). */
function amt_tutorial_modal($id, array $t)
{
    $id = amt_e($id);
    ?>
    <div class="amt-modal" id="<?= $id ?>" role="dialog" aria-modal="true" aria-labelledby="<?= $id ?>Title">
        <div class="amt-modal__card">
            <p class="amt-modal__eyebrow"><?= amt_e($t['eyebrow']) ?></p>
            <h2 class="amt-modal__title" id="<?= $id ?>Title"><?= amt_e($t['title']) ?></h2>
            <p class="amt-modal__body"><?= amt_e($t['body']) ?></p>
            <button type="button" class="amt-modal__ok" data-amt-ok>Mengerti</button>
            <button type="button" class="amt-modal__skip" data-amt-skip>Lewati</button>
        </div>
    </div>
    <?php
}

/** Petunjuk yang menyorot satu elemen (posisi diatur assets/amt-flow.js). */
function amt_tutorial_spot($id, array $t)
{
    $id = amt_e($id);
    ?>
    <div class="amt-spot" id="<?= $id ?>" role="dialog" aria-labelledby="<?= $id ?>Title">
        <div class="amt-spot__hole"></div>
        <div class="amt-spot__card">
            <span class="amt-spot__arrow"></span>
            <p class="amt-modal__eyebrow"><?= amt_e($t['eyebrow']) ?></p>
            <h2 class="amt-modal__title" id="<?= $id ?>Title"><?= amt_e($t['title']) ?></h2>
            <p class="amt-modal__body"><?= amt_e($t['body']) ?></p>
            <button type="button" class="amt-modal__ok" data-amt-ok>Mengerti</button>
            <button type="button" class="amt-modal__skip" data-amt-skip>Lewati</button>
        </div>
    </div>
    <?php
}

/* ---------- State PTI ---------- */

function amt_pti_state()
{
    if (!isset($_SESSION['amt_pti'])) {
        $_SESSION['amt_pti'] = ['status' => 'belum', 'answers' => [], 'note' => '', 'result' => null, 'done_at' => null, 'redo' => false, 'submitted' => null];
    }
    return $_SESSION['amt_pti'];
}

function amt_pti_is_done()
{
    $state = amt_pti_state();
    return $state['status'] === 'sudah';
}

/** Boleh mengisi form? Ya bila belum pernah kirim, atau sedang "Isi Inspeksi Lagi". */
function amt_pti_can_fill()
{
    $state = amt_pti_state();
    return !amt_pti_is_done() || !empty($state['redo']);
}

/** Sedang mengisi inspeksi ulang (setelah "Isi Inspeksi Lagi")? */
function amt_pti_is_redo()
{
    $state = amt_pti_state();
    return amt_pti_is_done() && !empty($state['redo']);
}

/**
 * Salinan inspeksi yang TERAKHIR DIKIRIM (dipakai layar "Lihat Hasil Inspeksi").
 * @return array{answers:array, note:string, result:string, done_at:string}|null
 */
function amt_pti_submitted()
{
    $state = amt_pti_state();
    return (isset($state['submitted']) && is_array($state['submitted'])) ? $state['submitted'] : null;
}

/** Item "Tidak layak" dari sekumpulan jawaban [langkah][kunci] => 'layak'|'tidak'. */
function amt_pti_failed_from(array $answers)
{
    $failed = [];
    foreach (amt_pti_steps() as $no => $data) {
        foreach ($data['items'] as $item) {
            if (isset($answers[$no][$item['key']]) && $answers[$no][$item['key']] === AMT_PTI_FAIL) {
                $failed[] = ['step' => $no, 'title' => $data['title'], 'label' => $item['label']];
            }
        }
    }
    return $failed;
}

function amt_pti_answer($step, $key)
{
    $state = amt_pti_state();
    return isset($state['answers'][$step][$key]) ? $state['answers'][$step][$key] : null;
}

/** Catatan pemeriksaan (langkah 11), sudah dipangkas. */
function amt_pti_note()
{
    $state = amt_pti_state();
    return isset($state['note']) ? (string) $state['note'] : '';
}

/** Bersihkan catatan dari form: buang spasi berlebih, batasi panjang. */
function amt_pti_clean_note($raw)
{
    $note = trim((string) $raw);
    $note = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $note);
    if (function_exists('mb_substr')) {
        return mb_substr($note, 0, AMT_PTI_NOTE_MAX);
    }
    return substr($note, 0, AMT_PTI_NOTE_MAX);
}

function amt_pti_note_ok()
{
    $len = function_exists('mb_strlen') ? mb_strlen(amt_pti_note()) : strlen(amt_pti_note());
    return $len >= AMT_PTI_NOTE_MIN;
}

/**
 * Item yang dijawab "Tidak layak" pada langkah 1-10.
 * @return array<int, array{step:int, title:string, label:string}>
 */
function amt_pti_failed_items()
{
    $state = amt_pti_state();
    return amt_pti_failed_from(isset($state['answers']) && is_array($state['answers']) ? $state['answers'] : []);
}

/** Hasil inspeksi: 'GO' bila semua item layak, 'NO GO' bila ada yang tidak layak. */
function amt_pti_result()
{
    return amt_pti_failed_items() ? 'NO GO' : 'GO';
}

function amt_pti_step_complete($step)
{
    $steps = amt_pti_steps();
    if (!isset($steps[$step])) {
        return false;
    }
    // Langkah terakhir: hanya butuh Catatan (semua checklist sebelumnya sudah dicek di first_incomplete)
    if ($step === AMT_PTI_STATEMENT_STEP) {
        return amt_pti_note_ok();
    }
    foreach ($steps[$step]['items'] as $item) {
        if (!in_array(amt_pti_answer($step, $item['key']), ['layak', 'tidak'], true)) {
            return false;
        }
    }
    return true;
}

/** Nomor langkah pertama yang belum lengkap (TOTAL + 1 kalau semua lengkap). */
function amt_pti_first_incomplete()
{
    for ($i = 1; $i <= AMT_PTI_TOTAL; $i++) {
        if (!amt_pti_step_complete($i)) {
            return $i;
        }
    }
    return AMT_PTI_TOTAL + 1;
}

/** Panggil saat Start Work / shift baru supaya PTI dan alur tutorial mulai dari awal. */
function amt_pti_reset()
{
    unset($_SESSION['amt_pti'], $_SESSION['amt_pti_error'], $_SESSION['amt_pti_flash'], $_SESSION['amt_flow']);
}

/* ---------- Router: ack, guard, POST ---------- */

/**
 * Panggil dari index.php SEBELUM ada output (sebelum layout_top.php),
 * setelah $screen divalidasi:  amt_pti_bootstrap($screen);
 */
function amt_pti_bootstrap($screen)
{
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $action = ($method === 'POST' && isset($_POST['pti_action'])) ? $_POST['pti_action'] : '';

    // Uji alur tanpa handler Check-In: ?screen=<dashboard>&amt_flow_demo=1
    if (AMT_FLOW_DEMO && $method === 'GET' && isset($_GET['amt_flow_demo'])) {
        amt_pti_reset();
        amt_flow_checkin_success();
        amt_pti_redirect($screen);
    }

    // Catat tutorial/aktivasi yang sudah terjadi (dikirim JS lewat fetch, tanpa pindah halaman).
    if ($action === 'flow_ack') {
        $key = isset($_POST['key']) ? $_POST['key'] : '';
        if (in_array($key, ['dcu_seen', 'pti_unlocked', 'pti_hint_seen', 'pti_tutorial_seen'], true)) {
            amt_flow_set($key);
            if ($key === 'dcu_seen') {
                $_SESSION['amt_flow']['dcu_seen_at'] = microtime(true);
            }
        }
        http_response_code(204);
        exit;
    }

    $isPtiScreen = in_array($screen, ['amt_pti', 'amt_pti_form', 'amt_pti_hasil'], true);

    // PTI belum aktif -> kembali ke dashboard.
    if ($isPtiScreen && AMT_PTI_GUARD && !amt_pti_unlocked()) {
        amt_pti_redirect(AMT_DASHBOARD_SCREEN);
    }

    // Layar "Lihat Hasil Inspeksi": hanya ada bila inspeksi sudah pernah dikirim.
    if ($screen === 'amt_pti_hasil') {
        if (!amt_pti_shipment() || !amt_pti_is_done() || amt_pti_submitted() === null) {
            amt_pti_redirect('amt_pti');
        }
        return;
    }

    if ($screen === 'amt_pti_form' && $method !== 'POST') {
        if (!amt_pti_shipment() || !amt_pti_can_fill()) {
            amt_pti_redirect('amt_pti');
        }
        $step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
        $step = max(1, min(AMT_PTI_TOTAL, $step));
        $maxStep = min(amt_pti_first_incomplete(), AMT_PTI_TOTAL);
        if ($step > $maxStep) {
            amt_pti_redirect('amt_pti_form', ['step' => $maxStep]);
        }
        return;
    }

    if ($method !== 'POST' || !$isPtiScreen) {
        return;
    }

    if ($action === 'start') {
        if (!amt_pti_shipment() || !amt_pti_can_fill()) {
            amt_pti_redirect('amt_pti');
        }
        amt_pti_redirect('amt_pti_form', ['step' => min(amt_pti_first_incomplete(), AMT_PTI_TOTAL)]);
    }

    // "Isi Inspeksi Lagi": buat draf baru (hasil yang sudah terkirim tetap tersimpan sampai
    // inspeksi baru dikirim). Bila draf sudah ada, lanjutkan dari langkah yang belum lengkap.
    if ($action === 'restart') {
        if (!amt_pti_shipment() || !amt_pti_is_done()) {
            amt_pti_redirect('amt_pti');
        }
        if (empty($_SESSION['amt_pti']['redo'])) {
            $_SESSION['amt_pti']['redo']    = true;
            $_SESSION['amt_pti']['answers'] = [];
            $_SESSION['amt_pti']['note']    = '';
        }
        unset($_SESSION['amt_pti_error']);
        amt_pti_redirect('amt_pti_form', ['step' => min(amt_pti_first_incomplete(), AMT_PTI_TOTAL)]);
    }

    if ($action !== 'step') {
        return;
    }

    if (!amt_pti_shipment() || !amt_pti_can_fill()) {
        amt_pti_redirect('amt_pti');
    }

    $steps = amt_pti_steps();
    $step = isset($_POST['step']) ? (int) $_POST['step'] : 1;
    if (!isset($steps[$step])) {
        amt_pti_redirect('amt_pti');
    }

    amt_pti_state();
    $posted = (isset($_POST['a']) && is_array($_POST['a'])) ? $_POST['a'] : [];
    foreach ($steps[$step]['items'] as $item) {
        $value = isset($posted[$item['key']]) ? $posted[$item['key']] : '';
        if (in_array($value, ['layak', 'tidak'], true)) {
            $_SESSION['amt_pti']['answers'][$step][$item['key']] = $value;
        }
    }

    if ($step === AMT_PTI_STATEMENT_STEP) {
        $_SESSION['amt_pti']['note'] = amt_pti_clean_note(isset($_POST['catatan']) ? $_POST['catatan'] : '');
    }

    $nav = isset($_POST['nav']) ? $_POST['nav'] : 'next';

    if ($nav === 'prev') {
        unset($_SESSION['amt_pti_error']);
        amt_pti_redirect('amt_pti_form', ['step' => max(1, $step - 1)]);
    }

    if (!amt_pti_step_complete($step)) {
        $_SESSION['amt_pti_error'] = $step;
        amt_pti_redirect('amt_pti_form', ['step' => $step]);
    }
    unset($_SESSION['amt_pti_error']);

    if ($step < AMT_PTI_TOTAL) {
        amt_pti_redirect('amt_pti_form', ['step' => $step + 1]);
    }

    // Langkah terakhir: pastikan semua langkah lengkap, lalu tandai selesai.
    $first = amt_pti_first_incomplete();
    if ($first <= AMT_PTI_TOTAL) {
        amt_pti_redirect('amt_pti_form', ['step' => $first]);
    }

    $result = amt_pti_result();
    $_SESSION['amt_pti']['submitted'] = [
        'answers' => $_SESSION['amt_pti']['answers'],
        'note'    => amt_pti_note(),
        'result'  => $result,
        'done_at' => date('c'),
    ];
    $_SESSION['amt_pti']['status']  = 'sudah';
    $_SESSION['amt_pti']['redo']    = false;
    $_SESSION['amt_pti']['done_at'] = date('c');
    $_SESSION['amt_pti']['result']  = $result;

    // Setelah "Ya, kirim hasil inspeksi": buka halaman awal PTI (status "Sudah Inspeksi").
    // Di sana tutorial mengarahkan AMT kembali ke Beranda, baru tutorial segel muncul
    // (lihat amt_tour_pti_done_pack() di tutorial.php; 'pti_done' berlaku untuk siklus ini).
    amt_flow_set('pti_done');
    amt_pti_redirect('amt_pti');
}