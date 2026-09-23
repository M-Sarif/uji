<?php
/**
 * Layar "Beri Penilaian".
 * Tampil setelah pop up "Berhasil Melakukan Verifikasi" di halaman
 * Permintaan Verifikasi (views/qr_code.php) -> tombol "Beri Penilaian".
 *
 * Susunan (persis video referensi):
 *  1. Kartu teratas: foto + nama AMT, bintang penilaian keseluruhan.
 *  2. Kartu per kategori (RATING_CATEGORIES): judul, pertanyaan, bintang.
 *     Begitu bintang dipilih, muncul teks apresiasi ("Luar Biasa!" dst).
 *  3. Kartu "Keterangan Lainnya (opsional)".
 *  4. Navigasi bawah: selama belum semua kategori dinilai hanya tombol
 *     "Selanjutnya" (non-aktif) yang tampil; setelah SEMUA bintang
 *     (termasuk penilaian keseluruhan) terisi, berganti jadi
 *     "Sebelumnya" + "Telah Kirim".
 */

$ratings = $_SESSION['ratings'];
$error   = $_SESSION['rating_error'] ?? '';
unset($_SESSION['rating_error']);

$amt = ARRIVAL_SUBJECTS['amt_ok'];

// Teks apresiasi berdasarkan jumlah bintang yang dipilih
$starTitles = [
    1 => 'Sangat Kurang',
    2 => 'Kurang',
    3 => 'Cukup',
    4 => 'Baik',
    5 => 'Luar Biasa!',
];
$starBody = 'Terima kasih! Penilaian ini sangat berarti bagi kami.';

/** Render 5 tombol bintang (radio) untuk satu kelompok penilaian */
function render_stars(string $key, int $selected): void
{
    echo '<div class="stars"><div class="stars-inner">';
    for ($v = 5; $v >= 1; $v--) {
        $id = $key . '_' . $v;
        printf(
            '<input class="star-input" type="radio" id="%1$s" name="rating[%2$s]" value="%3$d" data-group="%2$s"%4$s>' .
            '<label class="star-label" for="%1$s"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg></label>',
            h($id),
            h($key),
            $v,
            $selected === $v ? ' checked' : ''
        );
    }
    echo '</div></div>';
}
?>
<form method="post" action="index.php" id="ratingForm">
<input type="hidden" name="action" value="submit_rating">

<div class="content-pad" style="padding-bottom:1rem;">

    <!-- Kartu penilaian keseluruhan AMT -->
    <div class="card rating-card" data-rating-card="overall">
        <div class="person-row" style="justify-content:center;flex-direction:column;align-items:center;margin-bottom:0.75rem;">
            <div class="avatar-circle" style="width:3.5rem;height:3.5rem;margin-bottom:0.5rem;">
                <img src="<?php echo h($amt['photo']); ?>" alt="<?php echo h($amt['name']); ?>">
            </div>
            <p class="person-name" style="text-align:center;"><?php echo h($amt['name']); ?></p>
            <p class="person-sub" style="text-align:center;"><?php echo h($amt['sub']); ?></p>
        </div>

        <?php render_stars('overall', (int) ($ratings['overall'] ?? 0)); ?>

        <div class="rating-feedback" data-feedback="overall"<?php echo empty($ratings['overall']) ? ' hidden' : ''; ?>>
            <p class="rating-feedback-title"><?php echo h($starTitles[(int) ($ratings['overall'] ?? 0)] ?? ''); ?></p>
            <p class="rating-feedback-body"><?php echo h($starBody); ?></p>
        </div>
    </div>

    <!-- Kartu per kategori -->
    <?php foreach (RATING_CATEGORIES as $key => $cat): ?>
        <div class="card rating-card" data-rating-card="<?php echo h($key); ?>">
            <p class="rating-title"><?php echo h($cat['title']); ?></p>
            <p class="rating-desc"><?php echo h($cat['desc']); ?></p>

            <?php render_stars($key, (int) ($ratings[$key] ?? 0)); ?>

            <div class="rating-feedback" data-feedback="<?php echo h($key); ?>"<?php echo empty($ratings[$key]) ? ' hidden' : ''; ?>>
                <p class="rating-feedback-title"><?php echo h($starTitles[(int) ($ratings[$key] ?? 0)] ?? ''); ?></p>
                <p class="rating-feedback-body"><?php echo h($starBody); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Keterangan lainnya -->
    <div class="card rating-card">
        <p class="rating-title" style="margin-bottom:0.75rem;">Keterangan Lainnya (opsional)</p>
        <p class="rating-desc" style="text-align:left;margin-bottom:0.625rem;">Kritik dan saran akan sangat berharga untuk pelayanan yang lebih baik lagi.</p>
        <textarea class="review-box" name="review" rows="3" placeholder="Contoh: AMT sangat membantu dan tepat waktu."><?php echo h($ratings['review'] ?? ''); ?></textarea>
    </div>

    <?php if ($error): ?>
        <p class="error-text"><?php echo h($error); ?></p>
    <?php endif; ?>

</div>

<!-- Navigasi bawah: satu tombol "Selanjutnya" (nonaktif) selama belum semua
     kategori dinilai, berganti jadi "Sebelumnya" + "Telah Kirim" setelah lengkap. -->
<div class="wizard-nav" id="ratingNavSingle">
    <span class="btn-wiz is-disabled" style="flex:1 1 0;">Selanjutnya</span>
</div>
<div class="wizard-nav" id="ratingNavPair" hidden>
    <a href="index.php?screen=<?php echo h(PREV_SCREEN['rating']); ?>" class="btn-wiz">
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H5M11 6l-6 6 6 6"/></svg>
        Sebelumnya
    </a>
    <button type="submit" class="btn-primary" id="btnTelahKirim">Telah Kirim</button>
</div>
</form>

<script>
(function () {
    var form       = document.getElementById('ratingForm');
    var groups     = <?php echo json_encode(array_merge(['overall'], array_keys(RATING_CATEGORIES))); ?>;
    var titles     = <?php echo json_encode($starTitles); ?>;
    var body       = <?php echo json_encode($starBody); ?>;
    var navSingle  = document.getElementById('ratingNavSingle');
    var navPair    = document.getElementById('ratingNavPair');
    var btnKirim   = document.getElementById('btnTelahKirim');

    function checkComplete() {
        var complete = groups.every(function (key) {
            return form.querySelector('input[name="rating[' + key + ']"]:checked') !== null;
        });
        navSingle.hidden = complete;
        navPair.hidden   = !complete;
    }

    form.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') { return; }
        var key       = e.target.getAttribute('data-group');
        var val       = e.target.value;
        var feedback  = form.querySelector('[data-feedback="' + key + '"]');
        if (feedback) {
            feedback.querySelector('.rating-feedback-title').textContent = titles[val] || '';
            feedback.querySelector('.rating-feedback-body').textContent  = body;
            feedback.hidden = false;
        }
        checkComplete();
    });

    form.addEventListener('submit', function () {
        if (btnKirim) {
            btnKirim.disabled = true;
            btnKirim.classList.add('is-loading');
        }
    });

    checkComplete();
})();
</script>