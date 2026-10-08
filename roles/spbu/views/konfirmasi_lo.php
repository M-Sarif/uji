<?php
/**
 * Layar "Konfirmasi LO" -- berdiri sendiri, BUKAN salah satu soal checklist
 * (tidak ada bar progres / kartu pertanyaan). Dibuka dari tombol "Selanjutnya"
 * pada soal terakhir (soal 14). Tampilan mengikuti sistem asli:
 *   Konfirmasi Status LO -> kartu per LO -> [Tidak Jadi Bongkar] [Sudah Dibongkar]
 *   footer: Sebelumnya | Konfirmasi LO
 *
 * Letak: roles/spbu/views/konfirmasi_lo.php
 */

// LO yang dikerjakan = LO yang dicentang di Daftar LO (jatuh ke semua LO bila kosong)
$activeLoIds = array_keys(array_filter($_SESSION['lo_checked']));
if (empty($activeLoIds)) {
    $activeLoIds = array_keys(LO_LIST);
}

$baseUrl = 'index.php?screen=konfirmasi_lo';

// Tombol "Konfirmasi LO" baru aktif kalau SEMUA LO sudah dipilih statusnya
$semuaTerpilih = true;
$konfirmasiDoneCount = 0;
foreach ($activeLoIds as $kId) {
    if (!empty($_SESSION['lo_bongkar'][$kId])) {
        $konfirmasiDoneCount++;
    } else {
        $semuaTerpilih = false;
    }
}

$arrowLeft  = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H5M11 6l-6 6 6 6"/></svg>';
$arrowRight = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15M13 6l6 6-6 6"/></svg>';
?>
<div class="konfirmasi-page" data-tour="konfirmasi-lo-card">
    <h2 class="konfirmasi-title">Konfirmasi Status LO</h2>
    <p class="konfirmasi-sub">Tentukan status bongkar untuk LO yang dipilih.</p>

    <div data-tour="konfirmasi-lo-group" data-tour-done-count="<?php echo (int) $konfirmasiDoneCount; ?>" data-all-done="<?php echo $semuaTerpilih ? '1' : '0'; ?>">
    <?php foreach ($activeLoIds as $loId):
        $lo     = LO_LIST[$loId];
        $status = $_SESSION['lo_bongkar'][$loId] ?? null; ?>
        <div class="konfirmasi-card<?php echo $status ? ' is-set' : ''; ?>">
            <div class="lo-detail">
                <div class="lrow"><span class="label">Nomor LO</span><span class="val"><span class="colon">:</span><?php echo h($loId); ?></span></div>
                <div class="lrow"><span class="label">Order</span><span class="val"><span class="colon">:</span><span class="order-input"><?php echo h($lo['order']); ?></span></span></div>
            </div>
            <div class="konfirmasi-actions">
                <a href="<?php echo $baseUrl; ?>&lo=<?php echo urlencode((string) $loId); ?>&status=batal"
                   class="konfirmasi-btn<?php echo $status === 'batal' ? ' is-selected' : ''; ?>">Tidak Jadi Bongkar</a>
                <a href="<?php echo $baseUrl; ?>&lo=<?php echo urlencode((string) $loId); ?>&status=dibongkar"
                   class="konfirmasi-btn<?php echo $status === 'dibongkar' ? ' is-selected' : ''; ?>">Sudah Dibongkar</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<div class="wizard-nav konfirmasi-nav">
    <a href="index.php?screen=checklist&step=<?php echo count(CHECKLIST_STEPS); ?>" class="btn-wiz">
        <?php echo $arrowLeft; ?>
        Sebelumnya
    </a>
    <?php if ($semuaTerpilih): ?>
        <a href="index.php?screen=lo_list&selesai=1" class="btn-wiz" data-tour="wizard-next">
            Konfirmasi LO
            <?php echo $arrowRight; ?>
        </a>
    <?php else: ?>
        <span class="btn-wiz is-disabled">
            Konfirmasi LO
            <?php echo $arrowRight; ?>
        </span>
    <?php endif; ?>
</div>