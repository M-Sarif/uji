<?php
/**
 * Layar: ?screen=amt_rating&id=...  (role AMT)  -- "Beri Penilaian"
 * Letak: roles/amt/views/amt_rating.php
 *
 * Nama petugas SPBU (wajib) + 5 pertanyaan bintang (wajib) + Keterangan Lainnya (opsional).
 * Tombol "Kirim" aktif bila nama terisi dan semua bintang sudah dipilih -> POST submit_rating
 * (amt_rating_handle_post() di includes/amt_rating.php) -> kembali ke halaman SPBU / Detail Order.
 */
$s = amt_ship_current();
if ($s === null) {
    echo '<section class="rt"><p class="rt-empty">Shipment tidak ditemukan.</p></section>';
    return;
}
$questions = amt_rating_questions();
$star = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.5.95-4.7 4.6 1.1 6.45L12 17.3l-5.8 3.1 1.1-6.45-4.7-4.6 6.5-.95L12 2.5z"/></svg>';
$v = function (string $f): int { return (int) @filemtime(AMT_ASSET_DIR . '/' . $f); };
?>
<form class="rt" id="rtForm" method="post" action="<?= amt_e(amt_rating_url($s)) ?>" autocomplete="off">
    <input type="hidden" name="action" value="submit_rating">
    <input type="hidden" name="id" value="<?= amt_e($s['id']) ?>">

    <div class="rt-body">
        <h2 class="rt-title">Bagaimana pelayanan kami?</h2>
        <p class="rt-sub">Beri penilaian untuk petugas SPBU</p>

        <label class="rt-field" for="rtOfficer">Nama Petugas SPBU <b class="rt-req">*</b></label>
        <input class="rt-input" id="rtOfficer" name="officer" type="text" maxlength="<?= (int) AMT_RATING_NAME_MAX ?>"
               placeholder="Masukkan nama petugas SPBU" autocomplete="off">

        <?php foreach ($questions as $key => $q): ?>
            <section class="rt-card" data-q="<?= amt_e($key) ?>">
                <h3 class="rt-q"><?= amt_e($q['q']) ?><?php if ($q['req']): ?><b class="rt-req">*</b><?php endif; ?></h3>
                <p class="rt-tag"><?= amt_e($q['tag']) ?></p>

                <div class="rt-stars" role="radiogroup" aria-label="<?= amt_e($q['tag']) ?>">
                    <?php for ($n = 5; $n >= 1; $n--): $rid = 'rt_' . $key . '_' . $n; ?>
                        <input class="rt-star-in" type="radio" id="<?= amt_e($rid) ?>" name="rating[<?= amt_e($key) ?>]" value="<?= $n ?>">
                        <label class="rt-star" for="<?= amt_e($rid) ?>" title="<?= $n ?> bintang"><?= $star ?></label>
                    <?php endfor; ?>
                </div>

                <p class="rt-hint">Ketuk bintang untuk memberi penilaian.</p>
                <div class="rt-feed" hidden>
                    <p class="rt-feed-title"></p>
                    <p class="rt-hint"><?= amt_e(AMT_RATING_THANKS) ?></p>
                </div>
            </section>
        <?php endforeach; ?>

        <label class="rt-field rt-field--note" for="rtNote">Keterangan Lainnya <span class="rt-opt">(optional)</span></label>
        <p class="rt-note-sub">Kritik dan saran akan sangat berharga untuk pelayanan yang lebih baik lagi.</p>
        <textarea class="rt-input rt-textarea" id="rtNote" name="note" rows="3" maxlength="<?= (int) AMT_RATING_NOTE_MAX ?>"
                  placeholder="Contoh: Petugas SPBU sangat membantu."></textarea>
    </div>

    <div class="rt-footer">
        <button type="submit" class="rt-send" id="rtSend" disabled>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            <span>Kirim</span>
        </button>
    </div>
</form>

<script>
window.AMT_RATING_TITLES = <?= json_encode(AMT_RATING_TITLES, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= AMT_URL ?>/js/amt-rating.js?v=<?= $v('js/amt-rating.js') ?>"></script>