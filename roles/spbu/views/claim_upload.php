<?php
$docs      = spbu_claim_docs();
$dipstick  = $docs['dipstick'];
$qr        = $docs['qr'];
$ready     = spbu_claim_ready();

$claimError = (string) ($_SESSION['claim_error'] ?? '');
unset($_SESSION['claim_error']);

$fmtSize = static function (int $bytes): string {
    if ($bytes >= 1048576) { return number_format($bytes / 1048576, 1, ',', '.') . ' MB'; }
    return max(1, (int) round($bytes / 1024)) . ' KB';
};
?>
<div class="cu-body">
    <div class="cu-pad">
        <h2 class="cu-title">Upload Dokumen Klaim</h2>
        <p class="cu-sub">Lengkapi dokumen klaim di bawah ini.</p>

        <?php if ($claimError !== ''): ?>
        <div class="cu-alert" role="alert"><?php echo h($claimError); ?></div>
        <?php endif; ?>

        <div class="cu-field">
            <span class="cu-label" id="lblDipstick">Sertifikat Dipstick <span class="req">*</span></span>
            <form method="post" action="index.php" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="upload_dipstick">
                <input type="file" id="dipstickInput" name="dipstick" class="cu-file"
                       accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       data-max="<?php echo (int) CLAIM_UPLOAD_MAX_BYTES; ?>">
                <div class="cu-row<?php echo $dipstick ? ' is-filled' : ''; ?>" data-tour="cu-dipstick" aria-labelledby="lblDipstick">
                    <label for="dipstickInput" class="cu-action" id="btnUpload"><?php echo $dipstick ? 'Ganti' : 'Upload'; ?></label>
                    <span class="cu-value<?php echo $dipstick ? ' filled' : ''; ?>" id="dipstickText">
                        <?php if ($dipstick): ?>
                            <span class="cu-name"><?php echo h($dipstick['name']); ?></span>
                            <span class="cu-meta"><?php echo h($fmtSize((int) $dipstick['size'])); ?></span>
                        <?php else: ?>
                            Belum pilih dokumen
                        <?php endif; ?>
                    </span>
                    <?php if ($dipstick): ?>
                    <button type="submit" form="hapusForm" class="cu-clear" aria-label="Hapus dokumen">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <noscript><button type="submit" class="cu-noscript">Kirim dokumen</button></noscript>
            </form>
            <?php if ($dipstick): ?>
            <form method="post" action="index.php" id="hapusForm"><input type="hidden" name="action" value="hapus_dipstick"></form>
            <?php endif; ?>
            <p class="cu-hint" id="dipstickHint" hidden></p>
        </div>

        <div class="cu-field">
            <span class="cu-label" id="lblQr">QR Code AMT <span class="req">*</span></span>
            <div class="cu-row<?php echo $qr ? ' is-filled' : ''; ?>" data-tour="cu-qr" aria-labelledby="lblQr">
                <a href="index.php?screen=claim_scan_qr" class="cu-action" id="btnScan"><?php echo $qr ? 'Scan Ulang' : 'Scan Sekarang'; ?></a>
                <span class="cu-value<?php echo $qr ? ' filled' : ''; ?>">
                    <?php if ($qr): ?>
                        <span class="cu-name">QR AMT terscan</span>
                        <span class="cu-meta">Kode &hellip;<?php echo h(strtoupper(substr((string) $qr['token'], -6))); ?></span>
                    <?php else: ?>
                        Belum scan
                    <?php endif; ?>
                </span>
                <?php if ($qr): ?>
                <span class="cu-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M7.5 12.5l3 3 6-6.5" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form method="post" action="index.php" class="cu-footer" id="kirimKlaimForm">
    <input type="hidden" name="action" value="kirim_klaim">
    <div class="wizard-nav cu-nav">
        <button type="submit" class="cu-submit" id="btnKirimKlaim"<?php echo $ready ? '' : ' disabled'; ?>>Kirim Klaim</button>
    </div>
</form>

<script>
(function () {
    var input = document.getElementById('dipstickInput');
    var form  = document.getElementById('uploadForm');
    var hint  = document.getElementById('dipstickHint');
    if (!input || !form) { return; }

    input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f) { return; }
        var max = parseInt(input.getAttribute('data-max') || '0', 10);
        if (max && f.size > max) {
            hint.textContent = 'Ukuran dokumen terlalu besar (maksimal 5 MB).';
            hint.hidden = false;
            hint.classList.add('is-error');
            input.value = '';
            return;
        }
        var btn = document.getElementById('btnUpload');
        if (btn) { btn.textContent = 'Mengupload…'; }
        form.submit();
    });
})();
</script>