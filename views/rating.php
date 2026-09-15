<?php
$ratings = $_SESSION['ratings'];
$error   = $_SESSION['rating_error'] ?? '';
unset($_SESSION['rating_error']);
?>
<form method="post" action="index.php" class="content-pad" style="padding-bottom:8px;">
    <input type="hidden" name="action" value="submit_rating">

    <div class="card" style="margin-bottom:16px;">
        <div class="person-row" style="margin-bottom:0;">
            <div class="avatar-circle" style="width:40px;height:40px;">
                <img src="https://ui-avatars.com/api/?name=Farhan&background=0D8ABC&color=fff" alt="Avatar">
            </div>
            <div>
                <p class="person-name small">MOHAMMAD FARHAN</p>
                <p class="person-sub">AMT 1</p>
            </div>
        </div>
    </div>

    <?php foreach (RATING_CATEGORIES as $key => $cat): ?>
        <div class="card" style="margin-bottom:12px;">
            <p class="rating-title"><?php echo h($cat['title']); ?></p>
            <p class="rating-desc"><?php echo h($cat['desc']); ?></p>
            <div class="stars">
                <div class="stars-inner">
                    <?php for ($v = 5; $v >= 1; $v--): ?>
                        <input
                            class="star-input"
                            type="radio"
                            id="<?php echo $key . '_' . $v; ?>"
                            name="rating[<?php echo h($key); ?>]"
                            value="<?php echo $v; ?>"
                            <?php echo ((int) ($ratings[$key] ?? 0)) === $v ? 'checked' : ''; ?>
                        >
                        <label class="star-label" for="<?php echo $key . '_' . $v; ?>">
                            <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card" style="margin-bottom:16px;">
        <p class="rating-title" style="margin-bottom:12px;">Ulasan (Opsional)</p>
        <textarea class="review-box" name="review" rows="3" placeholder="Contoh: Pelayanan sangat memuaskan"><?php echo h($ratings['review'] ?? ''); ?></textarea>
    </div>

    <?php if ($error): ?>
        <p class="error-text"><?php echo h($error); ?></p>
    <?php endif; ?>

    <div class="wizard-nav" style="position:sticky;bottom:0;margin:16px -16px -16px -16px;">
        <button type="submit" class="btn-primary btn-next">Kirim Penilaian</button>
    </div>
</form>
