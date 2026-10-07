<?php
/**
 * Layar "Performance" AMT: ringkasan volume BBM yang dikirim.
 * Tab (7 Hari Terakhir / 1 Bulan) + Periode -> grafik Detail Volume Dikirim -> rincian produk -> Ringkasan.
 * Tanpa tutorial terpandu: tombol "?" hanya membuka pop up informasi (lihat amt-performance.js).
 * Data & fungsi: roles/amt/includes/amt_performance.php
 */
$tab      = amt_perf_tab();
$period   = amt_perf_period($tab);
$data     = amt_perf_data($tab, $period);
$months   = amt_perf_month_options();
$is30     = $tab === '30';
$subtitle = $is30 ? 'Dalam 1 bulan' : 'Dalam 7 hari terakhir';
?>
<div class="amtp-screen">

    <!-- Tab rentang waktu -->
    <nav class="amtp-tabs" aria-label="Rentang waktu">
        <?php foreach (AMT_PERF_TABS as $key => $label): ?>
            <a href="<?php echo h(amt_perf_url((string) $key)); ?>"
               class="amtp-tab<?php echo $tab === (string) $key ? ' is-active' : ''; ?>"
               <?php echo $tab === (string) $key ? 'aria-current="page"' : ''; ?>><?php echo h($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <!-- Periode (hanya bisa diubah di tab 1 Bulan) -->
    <form class="amtp-period" method="get" action="index.php">
        <input type="hidden" name="screen" value="amt_performance">
        <input type="hidden" name="tab" value="<?php echo h($tab); ?>">
        <label class="amtp-period-label" for="amtpPeriode">Periode</label>
        <div class="amtp-select<?php echo $is30 ? '' : ' is-disabled'; ?>">
            <select id="amtpPeriode" name="periode" <?php echo $is30 ? '' : 'disabled'; ?> data-amtp-period>
                <?php foreach ($months as $val => $label): ?>
                    <option value="<?php echo h($val); ?>"<?php echo $val === $period ? ' selected' : ''; ?>><?php echo h($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($is30): ?>
                <a class="amtp-select-clear" href="<?php echo h(amt_perf_url('30')); ?>" aria-label="Kembali ke bulan ini">&times;</a>
            <?php endif; ?>
            <span class="amtp-select-caret" aria-hidden="true"></span>
        </div>
        <noscript><button type="submit" class="amtp-noscript">Terapkan</button></noscript>
    </form>

    <!-- Grafik -->
    <section class="amtp-card amtp-chart" aria-labelledby="amtpChartTitle">
        <div class="amtp-chart-head">
            <h2 id="amtpChartTitle">Detail Volume Dikirim</h2>
            <button type="button" class="amtp-info-btn" data-amtp-open="chart" aria-label="Informasi Detail Volume Dikirim">i</button>
        </div>
        <?php echo amt_perf_chart_svg($data['points']); ?>
    </section>

    <!-- Total + rincian produk -->
    <section class="amtp-card amtp-products" aria-label="Rincian volume per produk">
        <div class="amtp-row amtp-row-total">
            <div class="amtp-row-name">
                <strong>Total Volume Dikirim</strong>
                <small>*<?php echo h($subtitle); ?></small>
            </div>
            <div class="amtp-row-val"><b><?php echo h(amt_perf_num($data['total'])); ?></b><span class="u">KL</span><i class="sep"></i><b><?php echo $data['total'] > 0 ? 100 : 0; ?></b><span class="u">%</span></div>
        </div>

        <?php foreach ($data['products'] as $p): ?>
            <?php $logo = amt_perf_logo_url($p['logo']); ?>
            <div class="amtp-row amtp-prod amtp-prod-<?php echo h($p['key']); ?>" style="background:<?php echo h($p['bg']); ?>;border-color:<?php echo h($p['bd']); ?>">
                <div class="amtp-row-name">
                    <?php if ($logo !== null): ?>
                        <img class="amtp-logo" src="<?php echo h($logo); ?>" alt="<?php echo h($p['label']); ?>">
                    <?php else: ?>
                        <span class="amtp-wordmark wm-<?php echo h($p['key']); ?>"><?php echo h($p['label']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="amtp-row-val"><b><?php echo h(amt_perf_num($p['volume'])); ?></b><span class="u">KL</span><i class="sep"></i><b><?php echo (int) $p['percent']; ?></b><span class="u">%</span></div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Ringkasan -->
    <section class="amtp-card amtp-summary" aria-labelledby="amtpSumTitle">
        <h2 id="amtpSumTitle">Ringkasan</h2>
        <div class="amtp-sum-grid">
            <div class="amtp-sum-box"><span>Total Volume Dikirim</span><b><?php echo h(amt_perf_num($data['total'])); ?> <em>KL</em></b></div>
            <div class="amtp-sum-box"><span>Shipment Dikirim</span><b><?php echo (int) $data['shipments']; ?></b></div>
            <div class="amtp-sum-box"><span>Total KM Ditempuh</span><b><?php echo h(amt_perf_km($data['km'])); ?> <em>KM</em></b></div>
            <div class="amtp-sum-box"><span>Total Accident</span><b><?php echo (int) $data['accident']; ?> <em>Kejadian</em></b></div>
        </div>
    </section>

    <!-- Tombol "?" : hanya pop up informasi (bukan tutorial) -->
    <button type="button" class="amtt-fab amtp-help-fab" data-amtp-open="help" aria-label="Informasi halaman Performance">?</button>

    <!-- Pop up informasi (dipindah JS ke dalam .app-container supaya menutup layar aplikasi) -->
    <?php foreach (['help' => AMT_PERF_HELP, 'chart' => AMT_PERF_CHART_INFO] as $id => $info): ?>
        <div class="amtp-modal" id="amtpModal-<?php echo h($id); ?>" role="dialog" aria-modal="true" aria-labelledby="amtpModalTitle-<?php echo h($id); ?>" hidden>
            <div class="amtp-modal-backdrop" data-amtp-close></div>
            <div class="amtp-modal-box">
                <h3 id="amtpModalTitle-<?php echo h($id); ?>"><?php echo h($info['title']); ?></h3>
                <ul>
                    <?php foreach ($info['items'] as $it): ?>
                        <li><?php echo h($it); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="amtp-modal-ok" data-amtp-close>Mengerti</button>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="<?php echo AMT_URL; ?>/js/amt-performance.js?v=<?php echo (int) @filemtime(AMT_ASSET_DIR . '/js/amt-performance.js'); ?>"></script>
</div>