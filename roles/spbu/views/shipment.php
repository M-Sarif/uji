<?php
/**
 * Layar "Detail Order" tab Aktifitas.
 * Timeline aktifitas di SPBU mengikuti progres session:
 *   $_SESSION['activity_done'] = jumlah langkah yang sudah selesai (0-4)
 *   - langkah selesai      -> node hijau, tetap bisa diklik (lihat ulang)
 *   - langkah berjalan     -> kartu biru + tanda panah, bisa diklik
 *   - langkah belum waktunya -> redup & tidak bisa diklik
 */

$done  = (int) ($_SESSION['activity_done'] ?? 0);

// "Tiba di Lokasi" dikunci 5 detik sejak Detail Order pertama kali dibuka
// (tampil redup & tidak bisa diklik), lalu otomatis aktif. Waktu mulai
// disimpan di session supaya reload halaman tidak mengulang hitungan.
$arriveLockSeconds = 5;
if ($done === 0 && empty($_SESSION['arrive_unlock_at'])) {
    $_SESSION['arrive_unlock_at'] = microtime(true) + $arriveLockSeconds;
}
$lockRemaining = 0.0;
if ($done === 0) {
    $lockRemaining = max(0.0, (float) $_SESSION['arrive_unlock_at'] - microtime(true));
}
$steps = ACTIVITY_STEPS;
$total = count($steps);

// Tinggi garis hijau: dari node pertama sampai node terakhir yang selesai
$progress = $total > 1 ? min($done, $total - 1) / ($total - 1) * 100 : 0;

// Teks ringkas status di bawah judul kartu
$subtitles = [
    0 => 'Menunggu mobil tangki sampai ke lokasi.',
    1 => 'Mobil tangki & AMT terverifikasi. Lanjutkan isi checklist.',
    2 => 'Checklist pra-pembongkaran selesai. Lanjutkan verifikasi order.',
    3 => 'Verifikasi order selesai. Berikan rating untuk petugas AMT.',
    4 => 'Seluruh aktifitas di SPBU telah selesai.',
];
?>
<div class="tabs">
    <div class="tab active">Aktifitas</div>
    <a href="index.php?screen=track_order" class="tab">Pengiriman</a>
</div>

<div class="content-pad">

    <?php
    // Semua aktifitas selesai: tidak ada langkah "aktif" lagi, jadi kartu
    // ini yang disorot tutorial (label dinamis 'Semua Selesai').
    $allDoneAttr = ($done >= $total) ? ' data-tour="activity-active" data-tour-label="Semua Selesai"' : '';
    ?>
    <div class="card activity-card"<?php echo $allDoneAttr; ?>>
        <h2 class="section-title">Aktifitas di SPBU</h2>
        <p class="section-sub"><?php echo h($subtitles[$done] ?? $subtitles[0]); ?></p>

        <div class="activity-timeline">
            <div class="activity-line"></div>
            <div class="activity-line progress" style="height:<?php echo (float) $progress; ?>%;"></div>

            <?php foreach (array_values($steps) as $i => $step): ?>
                <?php
                    if ($i < $done) {
                        $state = 'done';
                    } elseif ($i === $done) {
                        $state = 'active';
                    } else {
                        $state = 'pending';
                    }
                    $locked = ($i === 0 && $state === 'active' && $lockRemaining > 0);
                    if ($locked) {
                        $state = 'pending locked';
                    }
                    $clickable = (strpos($state, 'pending') === false);
                    $tag       = $clickable ? 'a' : 'div';
                    $hrefAttr  = $clickable ? ' href="' . h($step['href']) . '"' : '';
                ?>
                <?php $tourAttr = ($state === 'active') ? ' data-tour="activity-active" data-tour-label="' . h($step['label']) . '"' : ''; ?>
                <<?php echo $tag; ?><?php echo $hrefAttr; ?><?php echo $locked ? ' id="arriveItem" data-href="' . h($step['href']) . '" aria-disabled="true"' : ''; ?><?php echo $tourAttr; ?> class="activity-item <?php echo $state; ?><?php echo $clickable ? ' clickable' : ''; ?>">
                    <span class="activity-node">
                        <?php if ($state === 'done'): ?>
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?php endif; ?>
                    </span>
                    <div class="activity-row">
                        <div class="activity-icon">
                            <img src="<?php echo h($step['icon']); ?>" alt="<?php echo h($step['label']); ?>">
                        </div>
                        <span class="activity-label"><?php echo h($step['label']); ?></span>
                        <?php if ($state === 'active' || $locked): ?>
                            <svg class="activity-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                </<?php echo $tag; ?>>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    // LO yang tampil = semua LO pada order ini. Status mengikuti progres:
    // "Belum Diverifikasi" sampai langkah Verifikasi Order selesai.
    $sudahVerif = ($done >= 3);
    ?>
    <h2 class="section-title" style="margin: 1.25rem 0 0.75rem 0.25rem;">Rincian Order</h2>

    <?php foreach (LO_LIST as $loNo => $lo): ?>
    <div class="card rincian-card">
        <div class="rincian-grid">
            <div class="rincian-col">
                <span class="rincian-label">Nomor LO</span>
                <span class="rincian-value"><?php echo h($loNo); ?></span>
            </div>
            <div class="rincian-col">
                <span class="rincian-label">Status</span>
                <span class="rincian-status <?php echo $sudahVerif ? 'ok' : 'wait'; ?>"><?php echo $sudahVerif ? 'Sudah Diverifikasi' : 'Belum Diverifikasi'; ?></span>
            </div>
            <div class="rincian-col">
                <span class="rincian-label">Nama Produk</span>
                <span class="rincian-value"><?php echo h($lo['produk']); ?></span>
            </div>
            <div class="rincian-col">
                <span class="rincian-label">Jumlah Order</span>
                <span class="rincian-value"><?php echo h($lo['qty']); ?></span>
            </div>
        </div>
        <button type="button" class="rincian-report" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/></svg>
            Lihat Test Report
        </button>
    </div>
    <?php endforeach; ?>

    <h2 class="section-title" style="margin: 1.25rem 0 0.75rem 0.25rem;">Informasi Pengiriman</h2>

    <div class="card info-kirim-card">
        <div class="info-kirim-row">
            <span class="info-kirim-label">Nomor Shipment</span>
            <span class="info-kirim-value"><?php echo h(SHIPMENT_INFO['shipment_no']); ?></span>
        </div>
        <div class="info-kirim-row">
            <span class="info-kirim-label">Nomor SPBU</span>
            <span class="info-kirim-value"><?php echo h(SHIPMENT_INFO['spbu_no']); ?></span>
        </div>
        <div class="info-kirim-row">
            <span class="info-kirim-label">Nomor Polisi MT</span>
            <span class="info-kirim-value"><?php echo h(ARRIVAL_SUBJECTS['mt_ok']['name']); ?></span>
        </div>
        <div class="info-kirim-row">
            <span class="info-kirim-label">Kapasitas Tangki</span>
            <span class="info-kirim-value"><?php echo h(SHIPMENT_INFO['kapasitas']); ?></span>
        </div>
        <div class="info-kirim-row">
            <span class="info-kirim-label">AMT</span>
            <span class="info-kirim-value"><?php echo h(ARRIVAL_SUBJECTS['amt_ok']['name']); ?> (AMT 1)</span>
            <span class="info-kirim-value"><?php echo h(ARRIVAL_SUBJECTS['amt2_ok']['name']); ?> (AMT 2)</span>
        </div>
    </div>

</div>

<?php if ($done < $total): ?>
<!-- Tombol "Selesai" hanya tampil (nonaktif) selama aktifitas belum tuntas.
     Setelah Rating AMT selesai tidak ada aktifitas lagi, jadi tombol dihilangkan. -->
<div class="sticky-footer">
    <button type="button" class="btn-primary" disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 4H6a2 2 0 00-2 2v13a2 2 0 002 2h9a2 2 0 002-2v-2"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 4a2 2 0 002 2h1a2 2 0 002-2 2 2 0 00-2-2h-1a2 2 0 00-2 2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h4M9 16h4"/>
        </svg>
        Selesai
    </button>
</div>
<?php endif; ?>

<?php if ($lockRemaining > 0): ?>
<script>
// Setelah 5 detik, ubah "Tiba di Lokasi" dari redup/terkunci jadi aktif & bisa diklik
(function () {
    var el = document.getElementById('arriveItem');
    if (!el) { return; }
    setTimeout(function () {
        var a = document.createElement('a');
        a.href = el.getAttribute('data-href');
        a.className = 'activity-item active clickable';
        a.setAttribute('data-tour', 'activity-active');
        a.setAttribute('data-tour-label', <?php echo json_encode(ACTIVITY_STEPS['arrive']['label']); ?>);
        a.innerHTML = el.innerHTML;
        el.parentNode.replaceChild(a, el);
        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }, <?php echo (int) ceil($lockRemaining * 1000); ?>);
})();
</script>
<?php endif; ?>