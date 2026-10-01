<?php
/**
 * Layar: ?screen=amt_spbu&id=...  (role AMT)  -- "SPBU <kode>"
 * Aktifitas di SPBU (5 langkah) + Order List per Nomor LO. Header judul = "SPBU <kode>".
 *
 * Pengiriman berjalan : "Tiba di Lokasi" aktif (kartu biru), langkah lain redup, "Selesai" nonaktif.
 * Pengiriman selesai  : semua langkah hijau, status order terverifikasi, tanpa tombol "Selesai".
 * Layar tujuan tiap langkah (Tiba di Lokasi, dst) belum dibuat: kartunya belum membuka layar lain.
 */
require_once __DIR__ . '/../../includes/amt/amt_ship_data.php';

$s = amt_ship_current();
if ($s === null) {
    echo '<section class="amts-screen"><p class="amts-empty">Shipment tidak ditemukan.</p></section>';
    return;
}
$active = $s['status'] === 'sedang';
$steps  = amt_spbu_steps();
$total  = count($steps);
$doneN  = $active ? 0 : $total;      // jumlah langkah selesai
$progress = $total > 1 ? min($doneN, $total - 1) / ($total - 1) * 100 : 0;
?>

<section class="amts-screen amts-spbu-screen">
    <div class="amts-act-card" data-tour="spbu-activity">
        <h2 class="amts-h2">Aktifitas di SPBU</h2>
        <div class="amts-act">
            <span class="amts-act__line" aria-hidden="true"></span>
            <span class="amts-act__line amts-act__line--done" style="height:<?= (float) $progress ?>%;" aria-hidden="true"></span>

            <?php foreach ($steps as $i => $st):
                $state = $i < $doneN ? 'done' : ($i === $doneN ? 'active' : 'pending'); ?>
                <div class="amts-act__item is-<?= $state ?>"
                     <?= $state === 'active' ? 'data-tour="spbu-step-active"' : '' ?>>
                    <span class="amts-act__node" aria-hidden="true">
                        <?php if ($state === 'done'): ?>
                            <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        <?php endif; ?>
                    </span>
                    <div class="amts-act__row">
                        <img class="amts-act__icon" src="<?= amt_e($st['icon']) ?>" alt="">
                        <span class="amts-act__label"><?= amt_e($st['label']) ?></span>
                        <?php if ($state === 'active'): ?>
                            <svg class="amts-act__chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <h2 class="amts-h2 amts-h2--list">Order List</h2>
    <?php foreach ($s['products'] as $p): ?>
        <article class="amts-card amts-order">
            <div class="amts-order__row">
                <span class="amts-order__k">Nomor LO</span>
                <span class="amts-order__v"><b><?= amt_e($p['lo']) ?></b></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Order</span>
                <span class="amts-order__v"><span class="amts-box"><?= amt_e($p['name']) ?></span></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Status Order</span>
                <span class="amts-order__v"><span class="amts-pill <?= $active ? 'is-warn' : 'is-ok' ?>"><?= $active ? 'Belum Diverifikasi' : 'Sudah Diverifikasi' ?></span></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Status Surat Jalan</span>
                <span class="amts-order__v"><span class="amts-pill <?= $active ? 'is-warn' : 'is-ok' ?>"><?= $active ? 'Belum Ditambahkan' : 'Sudah Ditambahkan' ?></span></span>
            </div>
            <button type="button" class="amts-btn amts-btn--outline">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                Lihat Test Report
            </button>
        </article>
    <?php endforeach; ?>
</section>

<?php if ($active): ?>
<div class="amts-footer">
    <button type="button" class="amts-btn amts-btn--solid" disabled>
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4H6a2 2 0 00-2 2v13a2 2 0 002 2h9a2 2 0 002-2v-2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 12h4M9 16h4"/></svg>
        Selesai
    </button>
</div>
<?php endif; ?>