<?php

$ratings = $_SESSION['ratings'];
$error   = $_SESSION['rating_error'] ?? '';
unset($_SESSION['rating_error']);

$amtSubjects    = array_filter(ARRIVAL_SUBJECTS, function ($key) {
    return $key !== 'mt_ok';
}, ARRAY_FILTER_USE_KEY);
$amtKeysOrdered = array_keys($amtSubjects);

$step          = $_SESSION['rating_step'] ?? 1;
$totalStep     = count($amtKeysOrdered);
$currentAmtKey = $amtKeysOrdered[$step - 1];
$currentAmt    = $amtSubjects[$currentAmtKey];
$isLastStep    = $step >= $totalStep;

$starTitles = [
    1 => 'Sangat Kurang',
    2 => 'Kurang',
    3 => 'Cukup',
    4 => 'Baik',
    5 => 'Luar Biasa!',
];
$starBody = 'Terima kasih! Penilaian ini sangat berarti bagi kami.';

$reviewText = (string) ($ratings[$currentAmtKey]['review'] ?? '');

function render_stars(string $amtKey, string $catKey, int $selected): void
{
    $group = $amtKey . '_' . $catKey;
    echo '<div class="stars"><div class="stars-inner">';
    for ($v = 5; $v >= 1; $v--) {
        $id = $group . '_' . $v;
        printf(
            '<input class="star-input" type="radio" id="%1$s" name="rating[%2$s][%3$s]" value="%4$d" data-group="%5$s"%6$s>' .
            '<label class="star-label" for="%1$s"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg></label>',
            h($id),
            h($amtKey),
            h($catKey),
            $v,
            h($group),
            $selected === $v ? ' checked' : ''
        );
    }
    echo '</div></div>';
}
?>
<form method="post" action="index.php" id="ratingForm">
<input type="hidden" name="action" value="<?php echo $isLastStep ? 'submit_rating' : 'save_rating_step'; ?>">

<div class="content-pad" style="padding-bottom:1rem;">

    <div class="card rating-card" data-rating-card="<?php echo h($currentAmtKey); ?>_overall">
        <p class="rating-title">Bagaimana pelayanan kami?</p>
        <p class="rating-desc">Beri penilaian untuk AMT ini</p>

        <div class="rating-person">
            <div class="avatar-circle rating-avatar">
                <img src="<?php echo h($currentAmt['photo']); ?>" alt="<?php echo h($currentAmt['name']); ?>">
            </div>
            <p class="person-name"><?php echo h($currentAmt['name']); ?></p>
            <p class="person-sub"><?php echo h($currentAmt['sub']); ?></p>
        </div>

        <?php render_stars($currentAmtKey, 'overall', (int) ($ratings[$currentAmtKey]['overall'] ?? 0)); ?>

        <div class="rating-feedback" data-feedback="<?php echo h($currentAmtKey); ?>_overall"<?php echo empty($ratings[$currentAmtKey]['overall']) ? ' hidden' : ''; ?>>
            <p class="rating-feedback-title"><?php echo h($starTitles[(int) ($ratings[$currentAmtKey]['overall'] ?? 0)] ?? ''); ?></p>
            <p class="rating-feedback-body"><?php echo h($starBody); ?></p>
        </div>
    </div>

    <?php foreach (RATING_CATEGORIES as $catKey => $cat): ?>
        <div class="card rating-card" data-rating-card="<?php echo h($currentAmtKey); ?>_<?php echo h($catKey); ?>">
            <p class="rating-title"><?php echo h($cat['title']); ?></p>
            <p class="rating-desc"><?php echo h($cat['desc']); ?></p>

            <?php render_stars($currentAmtKey, $catKey, (int) ($ratings[$currentAmtKey][$catKey] ?? 0)); ?>

            <div class="rating-feedback" data-feedback="<?php echo h($currentAmtKey); ?>_<?php echo h($catKey); ?>"<?php echo empty($ratings[$currentAmtKey][$catKey]) ? ' hidden' : ''; ?>>
                <p class="rating-feedback-title"><?php echo h($starTitles[(int) ($ratings[$currentAmtKey][$catKey] ?? 0)] ?? ''); ?></p>
                <p class="rating-feedback-body"><?php echo h($starBody); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card rating-card rating-review">
        <p class="rating-title">Keterangan Lainnya (opsional)</p>
        <p class="rating-desc">Kritik dan saran akan sangat berharga untuk pelayanan yang lebih baik lagi.</p>
        <textarea class="review-box" name="review" rows="3" maxlength="500" placeholder="Contoh: AMT sangat membantu dan tepat waktu." aria-label="Keterangan lainnya untuk <?php echo h($currentAmt['sub']); ?>"><?php echo h($reviewText); ?></textarea>
    </div>

    <?php if ($error): ?>
        <p class="error-text"><?php echo h($error); ?></p>
    <?php endif; ?>

</div>

<?php if ($isLastStep): ?>
<div class="wizard-nav" id="ratingNavSingle">
    <span class="btn-wiz is-disabled" style="flex:1 1 0;">Kirim</span>
</div>
<div class="wizard-nav" id="ratingNavPair" hidden>
    <a href="index.php?screen=rating&amp;step=<?php echo (int) ($step - 1); ?>" class="btn-wiz">
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H5M11 6l-6 6 6 6"/></svg>
        Sebelumnya
    </a>
    <button type="submit" class="btn-primary" id="btnRatingLanjut">Kirim</button>
</div>
<?php else: ?>
<div class="wizard-nav" id="ratingNavStep1">
    <button type="submit" class="btn-wiz" id="btnRatingLanjut" disabled>Selanjutnya</button>
</div>
<?php endif; ?>
</form>

<script>
(function () {
    var form       = document.getElementById('ratingForm');
    var amtKey     = <?php echo json_encode($currentAmtKey); ?>;
    var catKeys    = <?php echo json_encode(array_merge(['overall'], array_keys(RATING_CATEGORIES))); ?>;
    var groups     = catKeys.map(function (catKey) { return amtKey + '_' + catKey; });
    var titles     = <?php echo json_encode($starTitles); ?>;
    var body       = <?php echo json_encode($starBody); ?>;
    var navSingle  = document.getElementById('ratingNavSingle');
    var navPair    = document.getElementById('ratingNavPair');
    var btnLanjut  = document.getElementById('btnRatingLanjut');

    function checkComplete() {
        var complete = groups.every(function (group) {
            return form.querySelector('[data-group="' + group + '"]:checked') !== null;
        });
        if (navSingle && navPair) {

            navSingle.hidden = complete;
            navPair.hidden   = !complete;
        } else if (btnLanjut) {

            btnLanjut.disabled = !complete;
        }

        var rated = 0;
        groups.forEach(function (group) {
            var card  = form.querySelector('[data-rating-card="' + group + '"]');
            var isSet = form.querySelector('[data-group="' + group + '"]:checked') !== null;
            if (card) { card.classList.toggle('is-rated', isSet); }
            if (isSet) { rated++; }
        });
        form.setAttribute('data-rated', String(rated));
        if (complete) { form.setAttribute('data-complete', '1'); }
        else { form.removeAttribute('data-complete'); }

        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }

    form.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') { return; }
        var group     = e.target.getAttribute('data-group');
        var val       = e.target.value;
        var feedback  = form.querySelector('[data-feedback="' + group + '"]');
        if (feedback) {
            feedback.querySelector('.rating-feedback-title').textContent = titles[val] || '';
            feedback.querySelector('.rating-feedback-body').textContent  = body;
            feedback.hidden = false;
        }
        checkComplete();
    });

    form.addEventListener('submit', function () {
        if (btnLanjut) {
            btnLanjut.disabled = true;
            btnLanjut.classList.add('is-loading');
        }
    });

    checkComplete();
})();
</script>