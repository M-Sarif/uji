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

    <div class="card activity-card">
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

    <h2 class="section-title" style="margin: 1.25rem 0 0.75rem 0.25rem;">Order List</h2>

    <div class="card order-list-card">
        <div class="order-row">
            <span class="label">Nomor LO</span>
            <span class="value">8119038717</span>
        </div>
        <?php if ($done >= $total): ?>
            <div class="order-row">
                <span class="label">Order</span>
                <span class="value-chip ok">
                    PERTALITE 6000 L
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="order-row">
                <span class="label">Status Order</span>
                <span class="value-chip ok">Sudah Diverifikasi</span>
            </div>
        <?php else: ?>
            <div class="order-row">
                <span class="label">Produk</span>
                <span class="value-chip">PERTALITE 6000 L</span>
            </div>
        <?php endif; ?>
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