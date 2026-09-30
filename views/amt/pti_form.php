<?php
/**
 * Layar: ?screen=amt_pti_form&step=1..11  (role AMT)
 * Guard dan penyimpanan jawaban ada di amt_pti_bootstrap() (dipanggil index.php).
 * Layar ini punya progress bar sendiri, jadi header bawaan sebaiknya disembunyikan.
 */
require_once __DIR__ . '/../../includes/amt/amt_pti_functions.php';

$steps = amt_pti_steps();
$step  = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$step  = max(1, min(AMT_PTI_TOTAL, $step));

$data     = $steps[$step];
$isFirst  = $step === 1;
$isLast   = $step === AMT_PTI_TOTAL;
$complete = amt_pti_step_complete($step);

$showError = isset($_SESSION['amt_pti_error']) && (int) $_SESSION['amt_pti_error'] === $step;
unset($_SESSION['amt_pti_error']);

$hasImage = is_file(__DIR__ . '/../../' . $data['image']);
$counter  = str_pad((string) $step, 2, '0', STR_PAD_LEFT) . '/' . AMT_PTI_TOTAL;
?>

<form method="post" action="<?= amt_e(amt_pti_url('amt_pti_form', ['step' => $step])) ?>"
      class="pti-form" id="ptiForm" data-groups="<?= count($data['items']) ?>" novalidate>
    <input type="hidden" name="pti_action" value="step">
    <input type="hidden" name="step" value="<?= $step ?>">
    <!-- Tombol default untuk tombol Enter: selalu "Selanjutnya", bukan "Sebelumnya". -->
    <button type="submit" name="nav" value="next" class="pti-hidden-submit" tabindex="-1" aria-hidden="true"></button>

    <div class="pti-scroll">
        <div class="pti-top">
            <div class="pti-progress" role="progressbar" aria-valuemin="1"
                 aria-valuemax="<?= AMT_PTI_TOTAL ?>" aria-valuenow="<?= $step ?>">
                <?php for ($i = 1; $i <= AMT_PTI_TOTAL; $i++): ?>
                    <span class="pti-progress__seg<?= $i <= $step ? ' is-on' : '' ?>"></span>
                <?php endfor; ?>
            </div>
            <span class="pti-counter"><?= amt_e($counter) ?></span>
        </div>

        <h2 class="pti-title"><?= $step ?>. <?= amt_e($data['title']) ?></h2>

        <div class="pti-photo">
            <?php if ($hasImage): ?>
                <img src="<?= amt_e($data['image']) ?>" alt="Foto acuan <?= amt_e($data['title']) ?>">
            <?php else: ?>
                <div class="pti-photo__empty">Foto acuan belum tersedia</div>
            <?php endif; ?>
        </div>

        <?php foreach ($data['items'] as $item):
            $name    = 'a[' . $item['key'] . ']';
            $current = amt_pti_answer($step, $item['key']);
        ?>
            <fieldset class="pti-item">
                <legend class="pti-item__label"><?= amt_e($item['label']) ?><span class="pti-req" aria-hidden="true">*</span></legend>
                <p class="pti-item__hint"><?= amt_e($item['hint']) ?></p>
                <div class="pti-options">
                    <label class="pti-option">
                        <input type="radio" name="<?= amt_e($name) ?>" value="tidak" <?= $current === 'tidak' ? 'checked' : '' ?>>
                        <span>Tidak layak</span>
                    </label>
                    <label class="pti-option">
                        <input type="radio" name="<?= amt_e($name) ?>" value="layak" <?= $current === 'layak' ? 'checked' : '' ?>>
                        <span>Layak</span>
                    </label>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <p class="pti-error" id="ptiError" <?= $showError ? '' : 'hidden' ?> role="alert">
            Pilih Layak atau Tidak layak untuk semua item bertanda * sebelum lanjut.
        </p>
    </div>

    <div class="pti-nav">
        <button type="submit" name="nav" value="prev" class="pti-btn pti-btn--ghost"
                <?= $isFirst ? 'disabled' : '' ?>>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M15 5l-7 7 7 7M8 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Sebelumnya
        </button>
        <button type="submit" name="nav" value="next"
                class="pti-btn pti-btn--outline pti-next<?= $complete ? '' : ' is-locked' ?>"
                aria-disabled="<?= $complete ? 'false' : 'true' ?>">
            <?= $isLast ? 'Kirim Inspeksi' : 'Selanjutnya' ?>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 5l7 7-7 7M4 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('ptiForm');
    if (!form) return;
    var groups = parseInt(form.getAttribute('data-groups'), 10);
    var nexts = form.querySelectorAll('button[value="next"]');
    var err = document.getElementById('ptiError');

    function allAnswered() {
        var seen = {};
        var checked = form.querySelectorAll('input[type="radio"]:checked');
        for (var i = 0; i < checked.length; i++) seen[checked[i].name] = true;
        return Object.keys(seen).length === groups;
    }

    function sync() {
        var ok = allAnswered();
        for (var i = 0; i < nexts.length; i++) {
            nexts[i].classList.toggle('is-locked', !ok);
            nexts[i].setAttribute('aria-disabled', ok ? 'false' : 'true');
        }
        if (ok && err) err.hidden = true;
    }

    form.addEventListener('change', sync);
    form.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('button[value="next"]') : null;
        if (btn && !allAnswered()) {
            e.preventDefault();
            if (err) err.hidden = false;
        }
    });
    sync();
})();
</script>