<?php
/**
 * Layar: ?screen=amt_checklist&id=...&step=1..14  (role AMT)  -- "Checklist Pra Bongkar BBM AMT"
 *
 * Satu langkah per halaman (pola sama dengan form PTI). Data langkah, state, guard akses, dan
 * penyimpanan jawaban ada di includes/amt/amt_pbk.php (dipanggil dari amt.php / index.php).
 * Logika tampilan (tombol Ya/Tidak, foto, QR Code) ada di assets/amt/js/amt-checklist.js.
 */
$s = amt_ship_current();
if ($s === null || !amt_pbk_ship_ready($s)) {
    echo '<section class="pbk"><p class="pbk-empty">Checklist tidak tersedia.</p></section>';
    return;
}

$id     = $s['id'];
$steps  = amt_pbk_steps();
$total  = count($steps);
$step   = max(1, min($total, (int) ($_GET['step'] ?? 1)));
$def    = $steps[$step];
$type   = $def['type'];
$isLast = $step === $total;

$ansA    = amt_pbk_answer($id, $step, 'a');
$ansB    = amt_pbk_answer($id, $step, 'b');
$photo   = amt_pbk_photo($id, $step);
$qr      = amt_pbk_qr($id);
$complete = amt_pbk_step_complete($id, $step);

$showError = isset($_SESSION['amt_spbu']['pbk_error']) && (int) $_SESSION['amt_spbu']['pbk_error'] === $step;
unset($_SESSION['amt_spbu']['pbk_error']);

$counter = str_pad((string) $step, 2, '0', STR_PAD_LEFT) . '/' . $total;
$v = function (string $file): int {
    return (int) @filemtime(AMT_ASSET_DIR . '/' . $file);
};

/** Dua tombol jawaban (Tidak / Ya). $label = teks tombol Ya ("Ya, Dilakukan" / "Ya, dilakukan"). */
$answerGroup = function (string $name, ?string $current, string $label) {
    ?>
    <div class="pbk-answers" role="radiogroup" data-pbk-group="<?= amt_e($name) ?>">
        <label class="pbk-ans pbk-ans--no">
            <input type="radio" name="<?= amt_e($name) ?>" value="tidak" <?= $current === 'tidak' ? 'checked' : '' ?>>
            <span>Tidak Dilakukan</span>
        </label>
        <label class="pbk-ans pbk-ans--yes">
            <input type="radio" name="<?= amt_e($name) ?>" value="ya" <?= $current === 'ya' ? 'checked' : '' ?>>
            <span><?= amt_e($label) ?></span>
        </label>
    </div>
    <?php
};

/** Kotak "Foto sebagai bukti (opsional)". */
$photoBox = function (string $photo) {
    ?>
    <hr class="pbk-sep">
    <p class="pbk-hint">Foto sebagai bukti (opsional)</p>
    <div class="pbk-photo<?= $photo !== '' ? ' is-taken' : '' ?>" data-pbk-photo>
        <button type="button" class="pbk-shot" data-pbk-shoot>
            <span class="pbk-shot__t">Ambil Foto</span>
            <span class="pbk-shot__s">Tap untuk membuka kamera</span>
        </button>
        <div class="pbk-thumb">
            <img alt="Foto bukti" <?= $photo !== '' ? 'src="' . amt_e($photo) . '"' : '' ?> data-pbk-thumb>
            <button type="button" class="pbk-redo" data-pbk-shoot>Ambil Ulang</button>
        </div>
    </div>
    <?php
};
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/camera.css?v=<?= $v('css/camera.css') ?>">
<link rel="stylesheet" href="<?= AMT_URL ?>/css/amt-checklist.css?v=<?= $v('css/amt-checklist.css') ?>">

<form method="post" action="<?= amt_e(amt_pbk_url($s, $step)) ?>" class="pbk" id="pbkForm"
      data-type="<?= amt_e($type) ?>" data-step="<?= $step ?>" data-total="<?= $total ?>" novalidate>
    <input type="hidden" name="action" value="submit_pbk">
    <input type="hidden" name="id" value="<?= amt_e($id) ?>">
    <input type="hidden" name="step" value="<?= $step ?>">
    <input type="hidden" name="photo_data" id="pbkPhotoData" value="">
    <!-- Tombol default untuk tombol Enter: selalu "Selanjutnya", bukan "Sebelumnya". -->
    <button type="submit" name="nav" value="next" class="pbk-hidden-submit" tabindex="-1" aria-hidden="true"></button>

    <div class="pbk-scroll">
        <div class="pbk-top">
            <div class="pbk-progress" role="progressbar" aria-valuemin="1" aria-valuemax="<?= $total ?>" aria-valuenow="<?= $step ?>">
                <?php for ($i = 1; $i <= $total; $i++): ?>
                    <span class="pbk-progress__seg<?= $i < $step ? ' is-done' : ($i === $step ? ' is-now' : '') ?>"></span>
                <?php endfor; ?>
            </div>
            <span class="pbk-counter"><?= amt_e($counter) ?></span>
        </div>

        <div class="pbk-card" data-tour="pbk-card">
            <p class="pbk-q"><?= amt_e($def['text']) ?><span class="pbk-req" aria-hidden="true"> *</span></p>

            <div class="pbk-box">
                <p class="pbk-hint"><?= amt_e(amt_pbk_hint($type)) ?></p>

<?php if ($type === 'spbu_task'): ?>
                <?php $answerGroup('a', $ansA, 'Ya, Dilakukan'); ?>

<?php elseif ($type === 'self'): ?>
                <?php $answerGroup('a', $ansA, 'Ya, Dilakukan'); ?>
                <?php $photoBox($photo); ?>

<?php elseif ($type === 'dual'): ?>
                <?php $answerGroup('a', $ansA, 'Ya, dilakukan'); ?>
                <?php $photoBox($photo); ?>
                <hr class="pbk-sep">
                <p class="pbk-hint">Verifikasi Tugas Role Lawan</p>
                <?php $answerGroup('b', $ansB, 'Ya, dilakukan'); ?>

<?php elseif ($type === 'qr'): ?>
                <?php if ($qr === null): ?>
                    <button type="submit" name="nav" value="qr" class="pbk-qrbtn" id="pbkGen">Generate QR Code</button>
                <?php else: ?>
                    <div class="pbk-qrcard" id="pbkQrCard" data-exp="<?= (int) $qr['exp'] ?>" data-now="<?= time() ?>">
                        <div class="pbk-qrframe">
                            <div class="pbk-qr" data-pbk-qr="<?= amt_e(amt_pbk_qr_payload($s, $qr)) ?>" role="img" aria-label="QR Code verifikasi untuk SPBU"></div>
                            <p class="pbk-qrexpired" hidden>QR Code sudah kedaluwarsa. Ketuk "Regenerate QR Code".</p>
                        </div>
                        <p class="pbk-qrexp">Berlaku sampai: <span id="pbkQrExp"><?= amt_e(date('d/m/Y, H.i.s', (int) $qr['exp'])) ?></span></p>
                    </div>
                    <button type="submit" name="nav" value="qr" class="pbk-qrbtn" id="pbkGen">Regenerate QR Code</button>
                <?php endif; ?>
<?php endif; ?>
            </div>
        </div>

        <p class="pbk-error" id="pbkError" <?= $showError ? '' : 'hidden' ?> role="alert">
            <?= $type === 'qr'
                ? 'Buat QR Code terlebih dahulu sebelum lanjut.'
                : 'Pilih jawaban untuk semua pertanyaan bertanda * sebelum lanjut.' ?>
        </p>
    </div>

    <div class="pbk-dock">
        <?php if ($qr !== null && $step >= AMT_PBK_QR_STEP): ?>
            <button type="button" class="pbk-qrfab" id="pbkQrFab" aria-haspopup="dialog">QR Code</button>
        <?php endif; ?>
        <div class="pbk-nav">
            <button type="submit" name="nav" value="prev" class="pbk-btn pbk-btn--ghost">Sebelumnya</button>
            <button type="submit" name="nav" value="next" id="pbkNext"
                    class="pbk-btn <?= $isLast ? 'pbk-btn--solid' : 'pbk-btn--ghost' ?><?= $complete ? '' : ' is-locked' ?>"
                    aria-disabled="<?= $complete ? 'false' : 'true' ?>">
                <?= $isLast ? 'Selesai' : 'Selanjutnya' ?>
            </button>
        </div>
    </div>
</form>

<?php if ($qr !== null && $step >= AMT_PBK_QR_STEP): ?>
<!-- Pop up QR Code (tombol "QR Code" melayang): ditampilkan ke Petugas SPBU untuk dipindai -->
<div class="pbk-modal" id="pbkQrModal" hidden>
    <div class="pbk-modal__card" role="dialog" aria-modal="true" aria-labelledby="pbkQrTitle">
        <h2 class="pbk-modal__title" id="pbkQrTitle">QR Code Verifikasi</h2>
        <div class="pbk-qrframe">
            <div class="pbk-qr" data-pbk-qr="<?= amt_e(amt_pbk_qr_payload($s, $qr)) ?>" data-pbk-exp="<?= (int) $qr['exp'] ?>" role="img" aria-label="QR Code verifikasi untuk SPBU"></div>
            <p class="pbk-qrexpired" hidden>QR Code sudah kedaluwarsa.</p>
        </div>
        <p class="pbk-qrexp">Berlaku sampai: <?= amt_e(date('d/m/Y, H.i.s', (int) $qr['exp'])) ?></p>
        <p class="pbk-modal__note" hidden id="pbkQrRegen">Buat ulang di <a href="<?= amt_e(amt_pbk_url($s, AMT_PBK_QR_STEP)) ?>">langkah <?= AMT_PBK_QR_STEP ?></a>.</p>
        <button type="button" class="pbk-btn pbk-btn--ghost" id="pbkQrClose">Tutup</button>
    </div>
</div>
<?php endif; ?>

<script src="<?= AMT_URL ?>/js/camera.js?v=<?= $v('js/camera.js') ?>"></script>
<?php if ($type === 'qr' || $qr !== null): ?>
<script src="<?= AMT_URL ?>/js/qrcode.js?v=<?= $v('js/qrcode.js') ?>"></script>
<?php endif; ?>
<script src="<?= AMT_URL ?>/js/amt-checklist.js?v=<?= $v('js/amt-checklist.js') ?>"></script>