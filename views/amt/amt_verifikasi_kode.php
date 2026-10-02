<?php
/**
 * Layar: ?screen=amt_verifikasi_kode  (Kode Konfirmasi - SIMULASI: angka apa saja diterima)
 * Letak: views/amt/amt_verifikasi_kode.php
 */
$remaining = amt_verif_remaining('code');
$retryUrl  = amt_verif_url('amt_verifikasi_kode', ['restart' => 1]);
$hasError  = isset($_GET['err']);
$len       = (int) AMT_VERIF_CODE_LENGTH;
?>

<section class="vkode" id="vkode">
    <div class="vkode-card">
        <h2 class="vkode-title">Kode Konfirmasi</h2>
        <p class="vkode-sub">Silahkan AMT masukkan kode konfirmasi yang telah dikirim ke SPBU</p>

        <form method="get" action="index.php" id="vkodeForm" autocomplete="off">
            <input type="hidden" name="screen" value="amt_verifikasi_kode">
            <?php if (amt_verif_ship_id() !== ''): ?><input type="hidden" name="id" value="<?= amt_verif_e(amt_verif_ship_id()) ?>"><?php endif; ?>
            <input type="hidden" name="kode" id="vkodeValue" value="">
            <div class="vkode-boxes" id="vkodeBoxes">
                <?php for ($i = 1; $i <= $len; $i++): ?>
                    <input class="vkode-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                           autocomplete="one-time-code" aria-label="Digit <?= $i ?> dari <?= $len ?>">
                <?php endfor; ?>
            </div>
        </form>

        <p class="vkode-error" id="vkodeError" role="alert" <?= $hasError ? '' : 'hidden' ?>>Kode tidak valid. Masukkan <?= $len ?> angka.</p>

        <p class="vkode-note">Kode Konfirmasi akan kadaluwarsa dalam waktu...</p>
        <p class="vkode-timer" id="vkodeTimer"><?= sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) ?></p>

        <div class="vkode-expired" id="vkodeExpired" hidden>
            <p>Kode kedaluwarsa.</p>
            <a class="vkode-retry" href="<?= amt_verif_e($retryUrl) ?>">Kirim ulang kode</a>
        </div>
    </div>

    <?php if (AMT_VERIF_SIM_NOTE): ?>
        <p class="vkode-sim">Mode simulasi: angka apa saja diterima.</p>
    <?php endif; ?>
</section>

<script>
(function () {
    var remaining = <?= (int) $remaining ?>;
    var len = <?= $len ?>;
    var form = document.getElementById('vkodeForm');
    var value = document.getElementById('vkodeValue');
    var boxes = [].slice.call(document.querySelectorAll('#vkodeBoxes .vkode-box'));
    var timer = document.getElementById('vkodeTimer');
    var expired = document.getElementById('vkodeExpired');
    var err = document.getElementById('vkodeError');
    var end = Date.now() + remaining * 1000;
    var locked = false;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function show(s) { timer.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); }
    function code() { return boxes.map(function (b) { return b.value; }).join(''); }

    function lock() {
        locked = true;
        boxes.forEach(function (b) { b.disabled = true; });
        expired.hidden = false;
    }
    function trySubmit() {
        var c = code();
        if (locked || c.length !== len || !/^[0-9]+$/.test(c)) return;
        locked = true;
        boxes.forEach(function (b) { b.readOnly = true; });
        value.value = c;
        form.submit();
    }
    function fill(from, digits) {
        for (var i = 0; i < digits.length && from + i < len; i++) boxes[from + i].value = digits.charAt(i);
        var next = Math.min(from + digits.length, len - 1);
        boxes[next].focus();
        trySubmit();
    }

    boxes.forEach(function (box, i) {
        box.addEventListener('input', function () {
            if (err) err.hidden = true;
            var digits = box.value.replace(/[^0-9]/g, '');
            box.value = '';
            if (digits) fill(i, digits);
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); e.preventDefault(); }
            if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
            if (e.key === 'ArrowRight' && i < len - 1) boxes[i + 1].focus();
        });
        box.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            if (!text) return;
            e.preventDefault();
            fill(i, text);
        });
        box.addEventListener('focus', function () { box.select(); });
    });

    show(remaining);
    if (remaining <= 0) { lock(); return; }
    boxes[0].focus();
    setInterval(function () {
        if (locked) return;
        var left = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        show(left);
        if (left <= 0) lock();
    }, 250);
})();
</script>