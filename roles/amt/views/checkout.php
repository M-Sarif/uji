<?php
/* Layar Check-Out AMT (menu Check-Out di beranda AMT).
 * Isi: Riwayat Check-In (daftar Check-In ritase ini) + Form Check-Out
 * (lokasi, aktivitas, foto Check-Out, tombol Kirim Check-Out) -> _work_form.php (mode 'checkout').
 * Tampilan: tanpa kotak info; bila belum ada Check-In tampil "Belum ada riwayat check-in hari ini";
 * tombol kirim menempel di bawah layar dan terkunci ("Lakukan Check-In Terlebih Dahulu") bila tidak ada Check-In.
 * Hanya bisa dibuka setelah order selesai dan tutorial segel ditutup (dijaga amt_out_guard()). */
work_styles();
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/checkout.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/checkout.css') ?>">
<?php
$checkins = work_checkins();
?>
<div class="co-page">

  <!-- Riwayat Check-In -->
  <section class="co-hist">
    <h2 class="co-h">Riwayat Check-In</h2>
    <div id="co-hist-body">
      <?php if (!$checkins): ?>
        <div class="co-hist-empty">Belum ada riwayat check-in hari ini</div>
      <?php else: ?>
        <?php foreach ($checkins as $i => $c): ?>
          <div class="co-hist-card">
            <span class="co-hist-time"><?= h(date('d/m/y H:i', (int) $c['at'])) ?> WIB</span>
            <strong class="co-hist-n">Check-In <?= h(amt_out_ordinal($i + 1)) ?></strong>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Form Check-Out -->
  <div class="co-form">
    <?php $mode = 'checkout'; require __DIR__ . '/_work_form.php'; ?>
  </div>

</div><!-- /.co-page -->