<?php
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
            <?php if (amt_verif_ship_id() !== ''): ?><input type="hidden" name="id" value="<?= amt_e(amt_verif_ship_id()) ?>"><?php endif; ?>
            <input type="hidden" name="kode" id="vkodeValue" value="">
            <div class="vkode-boxes" id="vkodeBoxes" role="group" aria-label="Kode konfirmasi <?= $len ?> angka">
                <?php for ($i = 1; $i <= $len; $i++): ?>
                    <span class="vkode-box" aria-label="Digit <?= $i ?> dari <?= $len ?>"></span>
                <?php endfor; ?>
            </div>
        </form>

        <p class="vkode-error" id="vkodeError" role="alert" <?= $hasError ? '' : 'hidden' ?>>Kode tidak valid. Masukkan <?= $len ?> angka.</p>

        <p class="vkode-note">Kode Konfirmasi akan kadaluwarsa dalam waktu...</p>
        <p class="vkode-timer" id="vkodeTimer"><?= sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) ?></p>

        <div class="vkode-expired" id="vkodeExpired" hidden>
            <p>Kode kedaluwarsa.</p>
            <a class="vkode-retry" href="<?= amt_e($retryUrl) ?>">Kirim ulang kode</a>
        </div>
    </div>

    <div class="vkode-pad" id="vkodeKeypad" role="group" aria-label="Tombol angka">
        <?php foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $k): ?>
            <button type="button" class="vkode-key" data-key="<?= $k ?>"><?= $k ?></button>
        <?php endforeach; ?>
        <span class="vkode-key vkode-key--blank" aria-hidden="true"></span>
        <button type="button" class="vkode-key" data-key="0">0</button>
        <button type="button" class="vkode-key vkode-key--del" data-key="del" aria-label="Hapus angka terakhir">
            <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M9 5h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9l-6-7 6-7zM12.5 10l4 4M16.5 10l-4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</section>

<script>
(function () {
    var remaining = <?= (int) $remaining ?>;
    var len = <?= $len ?>;
    var form = document.getElementById('vkodeForm');
    var value = document.getElementById('vkodeValue');
    var boxes = [].slice.call(document.querySelectorAll('#vkodeBoxes .vkode-box'));
    var pad = document.getElementById('vkodeKeypad');
    var keys = [].slice.call(pad.querySelectorAll('button'));
    var timer = document.getElementById('vkodeTimer');
    var expired = document.getElementById('vkodeExpired');
    var err = document.getElementById('vkodeError');
    var end = Date.now() + remaining * 1000;
    var digits = '';
    var locked = false;

    function pad2(n) { return (n < 10 ? '0' : '') + n; }
    function show(s) { timer.textContent = pad2(Math.floor(s / 60)) + ':' + pad2(s % 60); }

    function render() {
        boxes.forEach(function (b, i) {
            b.textContent = i < digits.length ? digits.charAt(i) : '';
            b.classList.toggle('is-filled', i < digits.length);
            b.classList.toggle('is-active', !locked && i === Math.min(digits.length, len - 1));
        });
    }
    function disableKeys() {
        keys.forEach(function (k) { k.disabled = true; });
    }
    function lock() {
        locked = true;
        disableKeys();
        expired.hidden = false;
        render();
    }
    function submit() {
        locked = true;
        disableKeys();
        value.value = digits;
        render();
        form.submit();
    }
    function add(d) {
        if (locked || digits.length >= len) return;
        if (err) err.hidden = true;
        digits += d;
        render();
        if (digits.length === len) submit();
    }
    function del() {
        if (locked || !digits) return;
        if (err) err.hidden = true;
        digits = digits.slice(0, -1);
        render();
    }

    pad.addEventListener('click', function (e) {
        var k = e.target.closest ? e.target.closest('[data-key]') : null;
        if (!k) return;
        var key = k.getAttribute('data-key');
        if (key === 'del') del(); else add(key);
    });
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (/^[0-9]$/.test(e.key)) { add(e.key); e.preventDefault(); }
        else if (e.key === 'Backspace') { del(); e.preventDefault(); }
    });

    show(remaining);
    render();
    if (remaining <= 0) { lock(); return; }
    setInterval(function () {
        if (locked) return;
        var left = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        show(left);
        if (left <= 0) lock();
    }, 250);
})();
</script>