<?php
$step     = $_SESSION['checklist_step'];
$stepData = CHECKLIST_STEPS[$step];
$prevStep = $step - 1;
$nextStep = $step + 1;

// LO yang sedang dikerjakan pada wizard ini = LO yang dicentang pengguna
// di halaman Daftar LO. Kalau karena suatu hal belum ada yang dicentang,
// jatuhkan ke seluruh daftar supaya halaman tetap punya data untuk ditampilkan.
$activeLoIds = array_keys(array_filter($_SESSION['lo_checked']));
if (empty($activeLoIds)) {
    $activeLoIds = array_keys(LO_LIST);
}
?>
<div class="progress-row">
    <?php for ($i = 1; $i <= 15; $i++): ?>
        <div class="progress-seg<?php echo $i <= $step ? ' filled' : ''; ?>"></div>
    <?php endfor; ?>
    <span class="progress-count"><?php echo str_pad((string) $step, 2, '0', STR_PAD_LEFT); ?>/15</span>
</div>

<div class="checklist-card">
    <h2><?php echo h($stepData['text']); ?><span class="req">*</span></h2>

    <?php switch ($stepData['type']):
        case 'action': ?>
            <div class="subtle-box">
                <p class="hint">Tugas AMT dan diverifikasi oleh SPBU</p>
                <div class="row">
                    <button type="button" class="btn-choice red">Tidak Dilakukan</button>
                    <button type="button" class="btn-choice blue">Ya, dilakukan</button>
                </div>
            </div>
        <?php break;

        case 'photo': ?>
            <div class="subtle-box">
                <p class="hint">Foto pelaksanaan tugas Anda sebagai bukti.</p>
                <label class="photo-drop" style="cursor:pointer;">
                    <input type="file" accept="image/*" capture="environment" style="display:none;">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Ambil Foto Bukti</span>
                </label>
            </div>
        <?php break;

        case 'form_spp': ?>
            <?php foreach ($activeLoIds as $i => $loId):
                $lo     = LO_LIST[$loId];
                $filled = $i === 0; // LO pertama dianggap sudah dicek AMT lebih dulu
            ?>
            <div class="spp-item<?php echo $filled ? ' filled' : ''; ?>">
                <p class="code"><?php echo h($loId); ?></p>
                <p class="name"><?php echo h($lo['order']); ?></p>
                <span class="status<?php echo $filled ? '' : ' pending'; ?>"><?php echo $filled ? 'Sudah Terisi' : 'Belum Terisi'; ?></span>
            </div>
            <?php endforeach; ?>
        <?php break;

        case 'form_ukur': ?>
            <div class="measure-box">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <div>
                    <h3>Pilih Metode Pengukuran</h3>
                    <p>Pilih antara IJKBOUT atau Flow Meter untuk mengisi form data LO.</p>
                    <div class="row">
                        <button type="button" class="btn-choice blue" style="border-color:#bfdbfe;">IJKBOUT</button>
                        <button type="button" class="btn-choice blue" style="border-color:#bfdbfe;">Flow Meter</button>
                    </div>
                </div>
            </div>
        <?php break;

        case 'konfirmasi_lo': ?>
            <p style="font-size:13px;color:#64748b;font-weight:500;margin-bottom:16px;">Tentukan status bongkar untuk LO yang dipilih</p>
            <?php foreach ($activeLoIds as $loId): $lo = LO_LIST[$loId]; ?>
            <div class="card" style="box-shadow:none;">
                <div class="lrow" style="display:flex;font-size:13px;margin-bottom:8px;"><span style="width:80px;color:#94a3b8;font-weight:500;">No. LO</span><span style="font-weight:600;color:#334155;">: <?php echo h($loId); ?></span></div>
                <div class="lrow" style="display:flex;font-size:13px;margin-bottom:20px;"><span style="width:80px;color:#94a3b8;font-weight:500;">Order</span><span style="font-weight:600;color:#334155;">: <?php echo h($lo['order']); ?></span></div>
                <div class="row">
                    <button type="button" class="btn-outline" style="flex:1;">Tidak Jadi</button>
                    <button type="button" class="btn-choice blue" style="padding:12px;">Sudah Dibongkar</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php break;
    endswitch; ?>
</div>

<div class="wizard-nav">
    <?php if ($step > 1): ?>
        <a href="index.php?screen=checklist&step=<?php echo $prevStep; ?>" class="btn-prev">Sebelumnya</a>
    <?php else: ?>
        <button class="btn-prev" disabled>Sebelumnya</button>
    <?php endif; ?>

    <?php if ($step < 15): ?>
        <a href="index.php?screen=checklist&step=<?php echo $nextStep; ?>" class="btn-primary btn-next">Selanjutnya</a>
    <?php else: ?>
        <a href="index.php?screen=qr_code" class="btn-primary btn-next">Kirim Checklist</a>
    <?php endif; ?>
</div>