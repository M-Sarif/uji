<?php
/**
 * Layar "Permintaan Verifikasi".
 * Tampil setelah pengguna menekan "Lihat Notifikasi" pada pop up
 * "Permintaan Verifikasi Order" di halaman Notifikasi (views/notifikasi.php).
 *
 * 5 detik setelah halaman ini terbuka, muncul pop up "Berhasil Melakukan
 * Verifikasi". Menekan "Beri Penilaian" akan membawa pengguna ke halaman
 * Rating AMT.
 */

$checked = array_filter($_SESSION['lo_checked'] ?? []);
$loIds   = !empty($checked) ? array_keys($checked) : array_keys(LO_LIST);

// Kode konfirmasi untuk AMT + simulasi pola kotak QR Code-nya
$kodeKonfirmasi = $_SESSION['kode_konfirmasi'] ?? '131200';
$qrSize         = 21;
$qrMatrix       = generate_qr_matrix($kodeKonfirmasi, $qrSize);
?>
<div class="content-pad verif-body">

    <h2 class="section-title" style="text-align:center;margin-bottom:1rem;">LO yang di Serahkan</h2>

    <div class="card verif-lo-card">
        <?php foreach ($loIds as $id): ?>
            <div class="verif-lo-row">
                <span class="verif-lo-id">LO : <?php echo h($id); ?></span>
                <span class="chip chip-done"><?php echo h(LO_LIST[$id]['produk'] ?? ''); ?> (<?php echo h(str_replace(' ', '', LO_LIST[$id]['qty'] ?? '')); ?>)</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="qr-box" style="margin-top:1.25rem;">
        <h2>Kode Konfirmasi Untuk AMT</h2>
        <p class="desc">Silakan berikan kode konfirmasi ini ke AMT untuk verifikasi order.</p>

        <div class="qr-visual">
            <svg viewBox="0 0 <?php echo (int) $qrSize; ?> <?php echo (int) $qrSize; ?>" xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="<?php echo (int) $qrSize; ?>" height="<?php echo (int) $qrSize; ?>" fill="#ffffff"></rect>
                <?php foreach ($qrMatrix as $y => $row): ?>
                    <?php foreach ($row as $x => $filled): ?>
                        <?php if ($filled): ?>
                            <rect x="<?php echo (int) $x; ?>" y="<?php echo (int) $y; ?>" width="1" height="1" fill="#1e293b"></rect>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </svg>
        </div>

        <div class="qr-alt">
            <p class="label">Atau gunakan kode</p>
            <div class="qr-code-text"><?php echo h($kodeKonfirmasi); ?></div>
        </div>
    </div>

</div>

<!-- Pop up: Berhasil Melakukan Verifikasi (muncul otomatis setelah 5 detik) -->
<div class="modal-backdrop" id="verifBerhasilModal" hidden>
    <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="verifBerhasilJudul">
        <h2 id="verifBerhasilJudul">Berhasil Melakukan Verifikasi</h2>
        <p>Silakan berikan penilaian terhadap pelayanan dari AMT yang bertugas.</p>
        <a href="index.php?screen=rating" class="btn-primary" id="btnBeriPenilaian">Beri Penilaian</a>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('verifBerhasilModal');
    setTimeout(function () {
        modal.hidden = false;
    }, 5000);
})();
</script>