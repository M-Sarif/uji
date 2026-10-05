<?php
/**
 * Layar: ?screen=amt_shipment_detail&id=...  (role AMT)  -- "Detail Order"
 * Informasi pengiriman + Rute Pengiriman. Kartu abu-abu SPBU membuka amt_spbu.
 * data-tour dipakai tutorial AMT (roles/amt/includes/tutorial.php).
 */
require_once AMT_INC_DIR . '/amt_ship_data.php';

$s = amt_ship_current();
if ($s === null) {
    echo '<section class="amts-screen"><p class="amts-empty">Shipment tidak ditemukan.</p></section>';
    return;
}
$active = $s['status'] === 'sedang';
?>

<section class="amts-screen amts-detail">
    <div class="amts-info">
        <h2 class="amts-h2">Informasi Pengiriman</h2>
        <div class="amts-grid">
            <div>
                <div class="amts-label">Shipment Number</div>
                <div class="amts-value"><?= amt_e($s['detail_no']) ?></div>
            </div>
            <div>
                <div class="amts-label">Status Pengiriman</div>
                <?= amt_ship_chip($s['status']) ?>
            </div>
            <div>
                <div class="amts-label">Tanggal Pengiriman</div>
                <div class="amts-value"><?= amt_e($s['tanggal_iso']) ?></div>
            </div>
            <div>
                <div class="amts-label">Total Order</div>
                <div class="amts-value"><?= amt_e($s['total']) ?></div>
            </div>
            <div>
                <div class="amts-label">Nomor Polisi MT</div>
                <div class="amts-value"><?= amt_e($s['mt']) ?></div>
            </div>
            <div>
                <div class="amts-label">Kapasitas Tangki</div>
                <div class="amts-value"><?= amt_e($s['kapasitas']) ?></div>
            </div>
        </div>
    </div>

    <div class="amts-route">
        <h2 class="amts-h2">Rute Pengiriman</h2>
        <p class="amts-note">Pengiriman akan selesai secara otomatis setelah Anda menekan tombol Selesai di SPBU terakhir.</p>

        <div class="amts-timeline">
            <span class="amts-line" aria-hidden="true"></span>

            <div class="amts-stop">
                <span class="amts-node">a</span>
                <div class="amts-stop__body">
                    <div class="amts-origin"><?= amt_e($s['origin']) ?></div>
                    <div class="amts-km"><?= amt_e($s['km']) ?> km KM</div>
                </div>
            </div>

            <div class="amts-stop">
                <span class="amts-node">1</span>
                <div class="amts-stop__body">
                    <a href="<?= amt_e(amt_ship_url('amt_spbu', $s)) ?>" class="amts-spbu" data-tour="ship-spbu-card">
                        <div class="amts-spbu__top">
                            <span class="amts-spbu__name">SPBU <?= amt_e($s['spbu']) ?></span>
                            <span class="amts-spbu__status <?= $active ? 'is-going' : 'is-done' ?>"><?= $active ? 'Sedang Dikirim' : 'Selesai Dikirim' ?></span>
                        </div>
                        <div class="amts-tags">
                            <?php foreach ($s['products'] as $p): ?>
                                <span class="amts-tag"><?= amt_e($p['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </a>
                    <div class="amts-km"><?= amt_e($s['km']) ?> km KM</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($active): ?>
<div class="amts-footer">
    <button type="button" class="amts-btn amts-btn--danger">Laporkan Kendala</button>
</div>
<?php endif; ?>