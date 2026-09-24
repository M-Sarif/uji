<?php
/**
 * Beranda peran AMT (Awak Mobil Tangki).
 * Isi: kartu Waktu Kerja, menu cepat (7 menu), dan panel "Pengiriman Aktif".
 */
$amtMenus = [
    ['label' => 'Start / End',  'icon' => 'assets/start-end.png',              'tone' => 'slate',  'href' => '#'],
    ['label' => 'Check-In',     'icon' => 'assets/Check-In.png',               'tone' => 'green',  'href' => '#'],
    ['label' => 'PTI',          'icon' => 'assets/PTI.png',                    'tone' => 'peach',  'href' => '#'],
    ['label' => 'Shipments',    'icon' => 'assets/empty-delivery-truck.png',   'tone' => 'blue',   'href' => '#'],
    ['label' => 'Check-Out',    'icon' => 'assets/Check-Out.png',              'tone' => 'rose',   'href' => '#'],
    ['label' => 'Performance',  'icon' => 'assets/performance.png',            'tone' => 'violet', 'href' => '#'],
    ['label' => 'SAFIRE',       'icon' => 'assets/safire_icon.png',            'tone' => 'sky',    'href' => '#'],
];
?>
<div class="amt-home">

    <!-- Waktu kerja -->
    <div class="amt-worktime">
        <span class="amt-worktime-label">Waktu Kerja</span>
        <span class="amt-worktime-value" id="amtWorktime">00:00:00</span>
    </div>

    <!-- Menu cepat -->
    <nav class="amt-menu" aria-label="Menu AMT">
        <?php foreach ($amtMenus as $m): ?>
            <a href="<?php echo h($m['href']); ?>" class="amt-menu-item">
                <span class="amt-menu-tile tone-<?php echo h($m['tone']); ?>">
                    <img src="<?php echo h($m['icon']); ?>" alt="">
                </span>
                <span class="amt-menu-label"><?php echo h($m['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Pengiriman aktif (kosong) -->
    <section class="amt-active">
        <img src="assets/empty-delivery-truck.png" alt="Ilustrasi pengiriman">
        <h3>Pengiriman Aktif Belum Tersedia</h3>
        <p>Silahkan check-in untuk memulai pengiriman</p>
    </section>

</div>