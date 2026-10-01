<?php
/**
 * Layar: ?screen=amt_shipments  (role AMT)  -- "Shipments"
 * Daftar pengiriman AMT + filter status. Tombol "Lihat Detail Order" membuka amt_shipment_detail.
 * data-tour dipakai tutorial AMT (includes/amt/tutorial.php).
 */
require_once __DIR__ . '/../../includes/amt/amt_ship_data.php';

$ships = amt_ship_all();
?>

<section class="amts-screen">
    <label class="amts-filter-label" for="amtsStatus">Status Shipments</label>
    <div class="amts-select">
        <select id="amtsStatus" aria-label="Filter status shipments">
            <option value="">Semua Status</option>
            <?php foreach (AMT_SHIP_STATUS as $key => $st): ?>
                <option value="<?= amt_e($key) ?>"><?= amt_e($st['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="amts-list" id="amtsList">
        <?php foreach ($ships as $s): $active = $s['status'] === 'sedang'; ?>
            <article class="amts-card" data-status="<?= amt_e($s['status']) ?>">
                <div class="amts-grid">
                    <div>
                        <div class="amts-label">Shipment Number</div>
                        <div class="amts-value amts-break"><?= amt_e($s['id']) ?></div>
                    </div>
                    <div>
                        <div class="amts-label">Status Pengiriman</div>
                        <?= amt_ship_chip($s['status']) ?>
                    </div>
                    <div>
                        <div class="amts-label">Tanggal Pengiriman</div>
                        <div class="amts-value"><?= amt_e($s['tanggal']) ?></div>
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
                <a href="<?= amt_e(amt_ship_url('amt_shipment_detail', $s)) ?>" class="amts-btn amts-btn--outline"
                   <?= $active ? 'data-tour="ship-detail-active"' : '' ?>>
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                    Lihat Detail Order
                </a>
            </article>
        <?php endforeach; ?>
        <p class="amts-empty" id="amtsEmpty" hidden>Tidak ada shipment dengan status ini.</p>
    </div>
</section>

<script>
/* Filter status di sisi browser (tanpa muat ulang). Tutorial dipindai ulang setelah daftar berubah. */
(function () {
    var sel = document.getElementById('amtsStatus');
    var cards = document.querySelectorAll('#amtsList .amts-card');
    var empty = document.getElementById('amtsEmpty');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var v = sel.value, shown = 0;
        for (var i = 0; i < cards.length; i++) {
            var ok = !v || cards[i].getAttribute('data-status') === v;
            cards[i].hidden = !ok;
            if (ok) shown++;
        }
        if (empty) empty.hidden = shown !== 0;
        if (window.OneFISTour && window.OneFISTour.rescan) window.OneFISTour.rescan();
    });
})();
</script>