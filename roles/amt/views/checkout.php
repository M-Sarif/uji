<?php
/* Layar Selesai Ritase AMT (menu Selesai Ritase di beranda AMT).
 * Isi: Riwayat Ritase (daftar Mulai Ritase ritase ini) + Form Selesai Ritase
 * (lokasi, aktivitas, foto Selesai Ritase, tombol Kirim Selesai Ritase) -> _work_form.php (mode 'checkout').
 * Tampilan: tanpa kotak info; bila belum ada Mulai Ritase tampil "Belum ada riwayat ritase hari ini";
 * tombol kirim menempel di bawah layar dan terkunci ("Tekan Mulai Ritase Terlebih Dahulu") bila tidak ada Mulai Ritase.
 * Hanya bisa dibuka setelah order selesai dan tutorial segel ditutup (dijaga amt_out_guard()). */
work_styles();
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/checkout.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/checkout.css') ?>">
<?php
$checkins = work_checkins();
?>
<div class="co-page">

  <!-- Riwayat Ritase -->
  <section class="co-hist">
    <h2 class="co-h">Riwayat Ritase</h2>
    <div id="co-hist-body">
      <?php if (!$checkins): ?>
        <div class="co-hist-empty">Belum ada riwayat ritase hari ini</div>
      <?php else: ?>
        <?php foreach ($checkins as $i => $c): ?>
          <div class="co-hist-card">
            <span class="co-hist-time"><?= h(date('d/m/y H:i', (int) $c['at'])) ?> WIB</span>
            <strong class="co-hist-n">Mulai Ritase <?= h(amt_out_ordinal($i + 1)) ?></strong>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Form Selesai Ritase -->
  <div class="co-form">
    <?php $mode = 'checkout'; require __DIR__ . '/_work_form.php'; ?>
  </div>

</div><!-- /.co-page -->