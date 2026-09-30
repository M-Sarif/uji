<?php
/**
 * Beranda peran AMT (Awak Mobil Tangki).
 * Isi: kartu Waktu Kerja, menu cepat (7 menu), dan panel "Pengiriman Aktif".
 *
 * 'key'   : dipakai untuk penguncian (amt_menu_locked) dan penanda tutorial.
 *           Check-In aktif setelah Start Work (timer berjalan); PTI aktif setelah
 *           Check-In + tutorial DCU (amt_pti_unlocked); Check-Out belum aktif
 *           (WORK_LOCKED_MENUS di includes/amt/work.php).
 * 'href'  => '#'  : layar tujuan belum tersedia.
 */
$running = work_is_running();

$amtMenus = [
    ['key' => 'start_end',   'label' => 'Start / End',  'icon' => 'assets/start-end.png',            'tone' => 'slate',  'href' => '?screen=start_end'],
    ['key' => 'checkin',     'label' => 'Check-In',     'icon' => 'assets/Check-In.png',             'tone' => 'green',  'href' => '?screen=checkin'],
    ['key' => 'pti',         'label' => 'PTI',          'icon' => 'assets/PTI.png',                  'tone' => 'peach',  'href' => '?screen=amt_pti'],
    ['key' => 'shipments',   'label' => 'Shipments',    'icon' => 'assets/empty-delivery-truck.png', 'tone' => 'blue',   'href' => '#'],
    ['key' => 'checkout',    'label' => 'Check-Out',    'icon' => 'assets/Check-Out.png',            'tone' => 'rose',   'href' => '#'],
    ['key' => 'performance', 'label' => 'Performance',  'icon' => 'assets/performance.png',          'tone' => 'violet', 'href' => '#'],
    ['key' => 'safire',      'label' => 'SAFIRE',       'icon' => 'assets/safire_icon.png',          'tone' => 'sky',    'href' => '#'],
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
            <?php $locked = amt_menu_locked($m['key']); ?>
            <?php if ($locked): ?>
                <span class="amt-menu-item is-disabled" aria-disabled="true" data-tour="amt-menu-<?php echo h($m['key']); ?>"<?php echo $m['key'] === 'pti' ? ' data-amt-pti data-href="' . h($m['href']) . '"' : ''; ?>>
            <?php else: ?>
                <a href="<?php echo h($m['href']); ?>" class="amt-menu-item" data-tour="amt-menu-<?php echo h($m['key']); ?>"<?php echo $m['key'] === 'pti' ? ' data-amt-pti' : ''; ?>>
            <?php endif; ?>
                <span class="amt-menu-tile tone-<?php echo h($m['tone']); ?>">
                    <img src="<?php echo h($m['icon']); ?>" alt="">
                </span>
                <span class="amt-menu-label"><?php echo h($m['label']); ?></span>
            <?php echo $locked ? '</span>' : '</a>'; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Pengiriman aktif (kosong) -->
    <section class="amt-active">
        <img src="assets/empty-delivery-truck.png" alt="Ilustrasi pengiriman">
        <h3>Pengiriman Aktif Belum Tersedia</h3>
        <p>Silahkan check-in untuk memulai pengiriman</p>
    </section>

</div>