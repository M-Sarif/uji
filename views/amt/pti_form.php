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

$isStatement = $step === AMT_PTI_STATEMENT_STEP;
$hasImage = !empty($data['image']) && is_file(__DIR__ . '/../../' . $data['image']);
$result   = amt_pti_result();
$failed   = amt_pti_failed_items();
$note     = amt_pti_note();
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

<?php if ($isStatement): ?>
        <div class="pti-result pti-result--<?= $result === 'GO' ? 'go' : 'nogo' ?>" role="status" data-tour="pti-result">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <?php if ($result === 'GO'): ?>
                    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 12.5l2.7 2.7L16 9.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <?php else: ?>
                    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 9l6 6M15 9l-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <?php endif; ?>
            </svg>
            <div>
                <strong>Hasil Inspeksi adalah <?= amt_e($result) ?></strong>
                <span><?= $result === 'GO'
                    ? 'Mobil tangki dalam keadaan layak beroperasi.'
                    : 'Mobil tangki belum layak beroperasi. Laporkan ke pengawas sebelum berangkat.' ?></span>
            </div>
        </div>

        <?php if ($failed): ?>
            <ul class="pti-failed" aria-label="Item yang tidak layak">
                <?php foreach ($failed as $f): ?>
                    <li><b><?= amt_e($f['label']) ?></b> <span>(<?= amt_e($f['title']) ?>)</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="pti-statement">
            <?php foreach (amt_pti_statement_lines() as $line): ?>
                <p><?= amt_e($line) ?></p>
            <?php endforeach; ?>
        </div>

        <label class="pti-note" data-tour="pti-note">
            <span class="pti-item__label">Catatan<span class="pti-req" aria-hidden="true">*</span></span>
            <textarea name="catatan" id="ptiNote" rows="5" maxlength="<?= AMT_PTI_NOTE_MAX ?>"
                      placeholder="Masukkan catatan terkait pemeriksaan (Misalnya: MT dalam kondisi baik.)"><?= amt_e($note) ?></textarea>
        </label>
<?php else: ?>
        <div class="pti-photo" data-tour="pti-photo">
            <?php if ($hasImage): ?>
                <img src="<?= amt_e($data['image']) ?>" alt="Foto acuan <?= amt_e($data['title']) ?>">
            <?php else: ?>
                <div class="pti-photo__empty">Foto acuan belum tersedia</div>
            <?php endif; ?>
        </div>

        <div class="pti-items" data-tour="pti-items">
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
        </div>
<?php endif; ?>

        <p class="pti-error" id="ptiError" <?= $showError ? '' : 'hidden' ?> role="alert">
            <?= $isStatement
                ? 'Isi Catatan terlebih dahulu sebelum mengirim inspeksi.'
                : 'Pilih Layak atau Tidak layak untuk semua item bertanda * sebelum lanjut.' ?>
        </p>
    </div>

    <div class="pti-nav">
        <button type="submit" name="nav" value="prev" class="pti-btn pti-btn--ghost"
                <?= $isFirst ? 'disabled' : '' ?>>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M15 5l-7 7 7 7M8 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Sebelumnya
        </button>
        <?php if ($isLast): ?>
        <button type="submit" name="nav" value="next"
                class="pti-btn pti-btn--send pti-next<?= $complete ? '' : ' is-locked' ?>"
                aria-disabled="<?= $complete ? 'false' : 'true' ?>">
            Kirim
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4 12l16-8-6 16-3-7-7-1z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
        </button>
        <?php else: ?>
        <button type="submit" name="nav" value="next"
                class="pti-btn pti-btn--outline pti-next<?= $complete ? '' : ' is-locked' ?>"
                aria-disabled="<?= $complete ? 'false' : 'true' ?>">
            Selanjutnya
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 5l7 7-7 7M4 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isLast): ?>
<!-- Popup konfirmasi kirim (hanya di langkah terakhir). Dibuka lewat tombol "Kirim". -->
<div class="pti-confirm" id="ptiConfirm" role="alertdialog" aria-modal="true"
     aria-labelledby="ptiConfirmTitle" aria-describedby="ptiConfirmText">
    <div class="pti-confirm__card" data-tour="pti-confirm">
        <h2 class="pti-confirm__title" id="ptiConfirmTitle">Kirim Hasil Inspeksi</h2>
        <p class="pti-confirm__text" id="ptiConfirmText">Apakah kamu yakin ingin mengirim hasil inspeksi?</p>
        <p class="pti-confirm__note">Note : Data yang telah dikirim tidak dapat diubah kembali.</p>
        <button type="button" class="pti-confirm__yes" id="ptiConfirmYes">Ya, kirim hasil inspeksi</button>
        <button type="button" class="pti-confirm__no" id="ptiConfirmNo">Batal</button>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var form = document.getElementById('ptiForm');
    if (!form) return;
    var groups = parseInt(form.getAttribute('data-groups'), 10);
    var nexts = form.querySelectorAll('button[value="next"]');
    var err = document.getElementById('ptiError');

    var note = document.getElementById('ptiNote');

    function allAnswered() {
        if (note) return note.value.trim().length >= <?= (int) AMT_PTI_NOTE_MIN ?>;
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

    /* ----- Popup konfirmasi (hanya ada di langkah terakhir) ----- */
    var box    = document.getElementById('ptiConfirm');
    var yes    = document.getElementById('ptiConfirmYes');
    var no     = document.getElementById('ptiConfirmNo');
    var sendBtn = form.querySelector('.pti-btn--send');
    var lastNav = 'next';        // tombol yang terakhir ditekan (untuk peramban tanpa e.submitter)
    var confirmed = false;

    function openConfirm() {
        if (!box) return;
        box.classList.add('is-open');
        document.addEventListener('keydown', onKey);
        if (no) no.focus();      // fokus ke pilihan aman ("Tidak"), bukan ke "Ya"
    }
    function closeConfirm() {
        if (!box) return;
        box.classList.remove('is-open');
        document.removeEventListener('keydown', onKey);
        if (sendBtn) sendBtn.focus();
        if (window.OneFISTour && window.OneFISTour.rescan) window.OneFISTour.rescan();
    }
    function onKey(e) {
        if (e.key === 'Escape') { closeConfirm(); return; }
        if (e.key === 'Tab' && yes && no) {          // fokus tetap di dalam popup
            var first = no, last = yes;
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }
    function sendNow() {
        if (confirmed) return;                        // cegah kirim ganda
        confirmed = true;
        yes.disabled = true; no.disabled = true;
        yes.textContent = 'Mengirim…';
        var nav = document.createElement('input');    // form.submit() tidak membawa nama tombol
        nav.type = 'hidden'; nav.name = 'nav'; nav.value = 'next';
        form.appendChild(nav);
        form.submit();
    }

    if (box) {
        form.addEventListener('click', function (e) {
            var b = e.target.closest ? e.target.closest('button[name="nav"]') : null;
            if (b) lastNav = b.value;
        }, true);

        // "Kirim" (atau Enter) -> jangan langsung kirim, tanya dulu.
        form.addEventListener('submit', function (e) {
            if (confirmed) return;
            var nav = (e.submitter && e.submitter.value) || lastNav;
            if (nav === 'prev') return;               // "Sebelumnya" tetap langsung
            e.preventDefault();
            if (!allAnswered()) { if (err) err.hidden = false; return; }
            openConfirm();
        });
        if (yes) yes.addEventListener('click', sendNow);
        if (no)  no.addEventListener('click', closeConfirm);
        box.addEventListener('click', function (e) { if (e.target === box) closeConfirm(); });
    }

    form.addEventListener('change', sync);
    form.addEventListener('input', sync);
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