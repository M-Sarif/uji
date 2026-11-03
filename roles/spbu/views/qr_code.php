<?php

$checked = array_filter($_SESSION['lo_checked'] ?? []);
$loIds   = !empty($checked) ? array_keys($checked) : array_keys(LO_LIST);

$sudahVerifikasi = (int) ($_SESSION['activity_done'] ?? 0) >= 3;

if ($sudahVerifikasi): ?>
<div class="done-wrap">
    <div class="done-icon">
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1>Verifikasi Telah Dilakukan</h1>
    <p>Order ini sudah diverifikasi oleh AMT. Kode konfirmasi tidak perlu ditampilkan lagi.</p>
    <a href="index.php?screen=shipment" class="btn-primary" style="margin-top:auto;">Kembali ke Detail Order</a>
</div>
<?php return; endif; ?>
<?php
$qrTtl = 180;
$now   = time();
$qrNow = $_SESSION['verif_qr'] ?? null;
if (!is_array($qrNow) || (int) ($qrNow['exp'] ?? 0) <= $now) {
    $qrNow = ['token' => bin2hex(random_bytes(8)), 'exp' => $now + $qrTtl];
    $_SESSION['verif_qr'] = $qrNow;
}
$remaining = max(0, (int) $qrNow['exp'] - $now);
$payload   = 'ONEFIS-VERIF|' . implode(',', $loIds) . '|' . $qrNow['token'] . '|' . $qrNow['exp'];
?>
<div class="content-pad verif-body">

    <h2 class="section-title verif-heading">LO yang di Serahkan</h2>

    <div class="verif-lo-list">
        <?php foreach ($loIds as $id): ?>
            <div class="verif-lo-row">
                <span class="verif-lo-id">LO : <?php echo h($id); ?></span>
                <span class="verif-lo-pill"><?php echo h(LO_LIST[$id]['produk'] ?? ''); ?> (<?php echo h(str_replace(' ', '', LO_LIST[$id]['qty'] ?? '')); ?>)</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="qr-box">
        <h2>Kode QR Kamu Sudah Dibuat</h2>
        <p class="desc">Silahkan AMT Pindai Kode QR untuk verifikasi order</p>

        <div class="qr-visual" id="verifQr" data-qr="<?php echo h($payload); ?>" role="img" aria-label="Kode QR verifikasi order untuk AMT"></div>
        <p class="qr-expired" id="verifQrExpired" hidden>Kode QR sudah kedaluwarsa. <a href="index.php?screen=qr_code">Buat ulang</a></p>

        <p class="qr-note">Kode QR akan kadaluwarsa dalam waktu...</p>
        <p class="qr-timer" id="verifTimer"><?php echo h(sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60)); ?></p>
    </div>

</div>

<div class="modal-backdrop modal-center" id="verifBerhasilModal" hidden>
    <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="verifBerhasilJudul">
        <h2 id="verifBerhasilJudul">Berhasil Melakukan Verifikasi</h2>
        <p>Silakan berikan penilaian terhadap pelayanan dari AMT yang bertugas.</p>
        <a href="index.php?screen=rating" class="btn-primary" id="btnBeriPenilaian">Beri Penilaian</a>
    </div>
</div>

<script src="<?php echo SPBU_URL; ?>/js/qrcode.js?v=<?php echo (int) @filemtime(SPBU_ASSET_DIR . '/js/qrcode.js'); ?>"></script>
<script>
(function () {
    var qrEl    = document.getElementById('verifQr');
    var expired = document.getElementById('verifQrExpired');
    var timer   = document.getElementById('verifTimer');
    var modal   = document.getElementById('verifBerhasilModal');
    var end     = Date.now() + <?php echo (int) $remaining; ?> * 1000;

    if (window.qrcode) {
        var q = window.qrcode(0, 'M');
        q.addData(qrEl.getAttribute('data-qr'));
        q.make();
        qrEl.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function show(s) { timer.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); }
    function tick() {
        var left = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        show(left);
        if (left > 0) { return false; }
        qrEl.hidden = true;
        expired.hidden = false;
        return true;
    }
    if (!tick()) {
        var iv = setInterval(function () { if (tick()) { clearInterval(iv); } }, 250);
    }

    setTimeout(function () {
        modal.hidden = false;
        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }, 5000);
})();
</script>