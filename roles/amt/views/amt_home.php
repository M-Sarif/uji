<?php
/**
 * Beranda peran AMT (Awak Mobil Tangki).
 * Isi: kartu Waktu Kerja, menu cepat (7 menu), dan panel "Pengiriman Aktif".
 *
 * 'key'   : dipakai untuk penguncian (amt_menu_locked) dan penanda tutorial.
 *           Check-In aktif setelah Start Work (timer berjalan); PTI aktif setelah
 *           Check-In + tutorial DCU (amt_pti_unlocked); Check-Out belum aktif
 *           (WORK_LOCKED_MENUS di roles/amt/includes/work.php).
 * 'href'  => '#'  : layar tujuan belum tersedia.
 */
$running = work_is_running();

$amtMenus = [
    ['key' => 'start_end',   'label' => 'Start / End',  'icon' => AMT_URL . '/img/menu/start-end.png',            'tone' => 'slate',  'href' => '?screen=start_end'],
    ['key' => 'checkin',     'label' => 'Check-In',     'icon' => AMT_URL . '/img/menu/Check-In.png',             'tone' => 'green',  'href' => '?screen=checkin'],
    ['key' => 'pti',         'label' => 'PTI',          'icon' => AMT_URL . '/img/menu/PTI.png',                  'tone' => 'peach',  'href' => '?screen=amt_pti'],
    ['key' => 'shipments',   'label' => 'Shipments',    'icon' => AMT_URL . '/img/ilustrasi/empty-delivery-truck.png', 'tone' => 'blue',   'href' => '?screen=amt_shipments'],
    ['key' => 'checkout',    'label' => 'Check-Out',    'icon' => AMT_URL . '/img/menu/Check-Out.png',            'tone' => 'rose',   'href' => '?screen=checkout'],
    ['key' => 'performance', 'label' => 'Performance',  'icon' => AMT_URL . '/img/menu/performance.png',          'tone' => 'violet', 'href' => '?screen=amt_performance'],
    ['key' => 'safire',      'label' => 'SAFIRE',       'icon' => AMT_URL . '/img/menu/safire_icon.png',          'tone' => 'sky',    'href' => '#'],
];
?>
<div class="amt-home" data-dcu-seen="<?php echo amt_flow_get('dcu_seen') ? '1' : '0'; ?>" data-seal-seen="<?php echo amt_out_seal_seen() ? '1' : '0'; ?>">

    <!-- Waktu kerja -->
    <div class="amt-worktime">
        <span class="amt-worktime-label">Waktu Kerja</span>
        <span class="amt-worktime-value" id="amtWorktime">00:00:00</span>
    </div>

    <!-- Menu cepat -->
    <nav class="amt-menu" aria-label="Menu AMT">
        <?php foreach ($amtMenus as $m): ?>
            <?php $locked = amt_menu_locked($m['key']); ?>
            <?php if ($locked): ?>
                <span class="amt-menu-item is-disabled" aria-disabled="true" data-tour="amt-menu-<?php echo h($m['key']); ?>"<?php echo $m['key'] === 'pti' ? ' data-amt-pti data-href="' . h($m['href']) . '"' : ''; ?><?php echo $m['key'] === 'checkout' ? ' data-amt-out data-href="' . h($m['href']) . '"' : ''; ?>>
            <?php else: ?>
                <a href="<?php echo h($m['href']); ?>" class="amt-menu-item" data-tour="amt-menu-<?php echo h($m['key']); ?>"<?php echo $m['key'] === 'pti' ? ' data-amt-pti' : ''; ?><?php echo $m['key'] === 'checkout' ? ' data-amt-out' : ''; ?>>
            <?php endif; ?>
                <span class="amt-menu-tile tone-<?php echo h($m['tone']); ?>">
                    <img src="<?php echo h($m['icon']); ?>" alt="">
                </span>
                <span class="amt-menu-label"><?php echo h($m['label']); ?></span>
            <?php echo $locked ? '</span>' : '</a>'; ?>
        <?php endforeach; ?>
    </nav>

<?php
    /* Rute Pengiriman: muncul di beranda setelah PTI selesai (hasil bukan NO GO) selama ada pengiriman
     * yang belum diselesaikan. Data dari amt_ship_find() (roles/amt/includes/amt_ship_data.php).
     * Kartu SPBU membuka "Aktifitas di SPBU". Selain kondisi itu tampil panel kosong seperti biasa. */
    $routeShip = amt_home_route_ship();
?>
    <?php if ($routeShip !== null): ?>
    <!-- Rute pengiriman (setelah PTI selesai) -->
    <section class="amt-route" data-tour="home-route" aria-labelledby="amtrTitle">
        <h2 class="amtr-title" id="amtrTitle">Rute Pengiriman</h2>

        <p class="amtr-note">Pengiriman akan diselesaikan secara otomatis setelah Anda menekan tombol Selesai di SPBU terakhir.</p>

        <div class="amtr-order">
            <span class="amtr-order-label">Urutan Pengiriman</span>
            <strong class="amtr-order-value">Pengiriman menuju ke SPBU <?php echo amt_e($routeShip['spbu']); ?></strong>
        </div>

        <div class="amtr-timeline">
            <span class="amtr-line" aria-hidden="true"></span>

            <div class="amtr-stop">
                <span class="amtr-node amtr-node-depot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7.5" cy="17.5" r="1.8" fill="#fff"/><circle cx="17.5" cy="17.5" r="1.8" fill="#fff"/></svg>
                </span>
                <div class="amtr-stop-body">
                    <div class="amtr-depot"><?php echo amt_e($routeShip['origin']); ?></div>
                    <div class="amtr-km"><?php echo amt_e($routeShip['km']); ?> km</div>
                </div>
            </div>

            <div class="amtr-stop">
                <span class="amtr-node" aria-hidden="true">1</span>
                <div class="amtr-stop-body">
                    <a class="amtr-spbu" href="<?php echo amt_e(amt_ship_url('amt_spbu', $routeShip)); ?>" data-tour="home-route-spbu">
                        <div class="amtr-spbu-top">
                            <div>
                                <div class="amtr-spbu-name">SPBU <?php echo amt_e($routeShip['spbu']); ?></div>
                                <?php if (!empty($routeShip['nama'])): ?>
                                    <div class="amtr-spbu-sub"><?php echo amt_e($routeShip['nama']); ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="amtr-chip">Belum Selesai</span>
                        </div>
                        <div class="amtr-tags">
                            <?php foreach ($routeShip['products'] as $p): ?>
                                <span class="amtr-tag"><?php echo amt_e($p['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="amtr-footer">
        <button type="button" class="amtr-report" data-amtr-report data-tour="home-route-report">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0zM12 9v4M12 17h.01"/></svg>
            Laporkan Kendala
        </button>
    </div>
    <?php else: ?>
    <!-- Pengiriman aktif (kosong) -->
    <section class="amt-active">
        <img src="<?php echo AMT_URL; ?>/img/ilustrasi/empty-delivery-truck.png" alt="Ilustrasi pengiriman">
        <h3>Pengiriman Aktif Belum Tersedia</h3>
        <p>Silahkan check-in untuk memulai pengiriman</p>
    </section>
    <?php endif; ?>

</div>