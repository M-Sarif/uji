<?php
$checked      = $_SESSION['lo_checked'];
$draft        = $_SESSION['lo_draft'];
$done         = $_SESSION['lo_done'];
$loIds        = array_keys(LO_LIST);
$checkedCount = count(array_filter($checked));
$allChecked   = $checkedCount > 0 && $checkedCount === count($loIds);
$anyChecked   = $checkedCount > 0;

// Tombol "Kirim" baru boleh ditekan kalau ADA LO yang dicentang dan
// wizard checklist-nya sudah dituntaskan ("Draft"), tapi belum dikirim.
$readyToSubmit = false;
foreach ($checked as $id => $isChecked) {
    if ($isChecked && !empty($draft[$id]) && empty($done[$id])) {
        $readyToSubmit = true;
        break;
    }
}

// Ikon centang: kotak biru + tanda centang putih tebal (gaya di .lo-checkbox, verification.css)
$checkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
?>
<form method="post" action="index.php" id="loListForm" style="display:flex;flex-direction:column;flex:1 1 auto;">
<input type="hidden" name="action" value="kirim_checklist">
<div class="content-pad" style="display:flex;flex-direction:column;flex:1 1 auto;">
    <p style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:2px;">Daftar LO</p>
    <p style="font-size:12px;color:#64748b;margin-bottom:20px;">Pilih LO untuk mengisi checklist</p>

    <div data-tour="daftar-lo">
    <a href="index.php?screen=lo_list&toggle_all=1" class="lo-select-all">
        <span class="lo-checkbox<?php echo $allChecked ? ' checked' : ''; ?>">
            <?php if ($allChecked) echo $checkIcon; ?>
        </span>
        <span>Pilih Semua</span>
    </a>

    <?php foreach (LO_LIST as $id => $lo):
        $isChecked = !empty($checked[$id]);
        $isDraft   = !empty($draft[$id]);
        $isDone    = !empty($done[$id]);

        if ($isDone) {
            $statusClass = 'done';
            $statusText  = 'Sudah Diisi';
        } elseif ($isDraft) {
            $statusClass = 'wait';
            $statusText  = 'Draft';
        } else {
            $statusClass = 'wait';
            $statusText  = 'Belum Diisi';
        }
    ?>
    <a href="index.php?screen=lo_list&toggle=<?php echo urlencode($id); ?>" class="lo-card<?php echo $isChecked ? ' selected' : ''; ?>" style="margin-bottom:1rem;">
        <div class="lo-checkbox<?php echo $isChecked ? ' checked' : ''; ?>">
            <?php if ($isChecked) echo $checkIcon; ?>
        </div>
        <div class="lo-detail">
            <div class="lrow"><span class="label">Nomor LO</span><span class="val">: <?php echo h($id); ?></span></div>
            <div class="lrow"><span class="label">Order</span><span class="val"><span class="colon">:</span><span class="order-input"><?php echo h($lo['order']); ?></span></span></div>
            <div class="lrow"><span class="label">Status Checklist</span><span class="val <?php echo $statusClass; ?>">: <?php echo h($statusText); ?></span></div>
        </div>
    </a>
    <?php endforeach; ?>
    </div>

    <div class="row" style="margin-top:auto;padding-top:24px;">
        <?php if ($anyChecked): ?>
            <a href="index.php?screen=checklist&step=1" class="btn-outline" style="flex:1;" data-tour="mulai-checklist">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Mulai Checklist
            </a>
        <?php else: ?>
            <button type="button" class="btn-outline" style="flex:1;" disabled>Mulai Checklist</button>
        <?php endif; ?>

        <button type="button" id="btnKirimChecklist" class="btn-primary" style="flex:1;" <?php echo $readyToSubmit ? '' : 'disabled'; ?>>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            Kirim
        </button>
    </div>
</div>

<!-- Pop up konfirmasi sebelum checklist dikirim (dialog di tengah layar, sama seperti sistem asli) -->
<div class="modal-backdrop confirm-backdrop" id="kirimModal" hidden>
    <div class="modal-sheet confirm-sheet" role="dialog" aria-modal="true" aria-labelledby="kirimJudul">
        <h2 id="kirimJudul">Kirim Checklist</h2>
        <p class="confirm-text">Pastikan data yang diisi sudah benar. Apakah Anda yakin ingin mengirim checklist ini?</p>
        <p class="confirm-note">Note : Data yang tersimpan tidak dapat diubah kembali setelah dikirim.</p>
        <button type="submit" class="btn-primary confirm-btn-yes" id="btnYaKirim">Ya, Kirim</button>
        <button type="button" class="btn-outline modal-close confirm-btn-no" id="btnBatalKirim">Batal</button>
    </div>
</div>
</form>

<script>
(function () {
    var btnOpen  = document.getElementById('btnKirimChecklist');
    var btnBatal = document.getElementById('btnBatalKirim');
    var modal    = document.getElementById('kirimModal');
    if (!btnOpen || !modal) { return; }

    function bukaModal() {
        modal.hidden = false;
        document.getElementById('btnYaKirim').focus();
    }
    function tutupModal() {
        modal.hidden = true;
        btnOpen.focus();
    }

    btnOpen.addEventListener('click', function () {
        if (!btnOpen.disabled) { bukaModal(); }
    });
    btnBatal.addEventListener('click', tutupModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) { tutupModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) { tutupModal(); }
    });
})();
</script>