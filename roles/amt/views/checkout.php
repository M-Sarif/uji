<?php
/* Layar Check-Out AMT (menu Check-Out di beranda AMT).
 * Isi: Riwayat Check-In (daftar Check-In ritase ini) + Form Check-Out
 * (lokasi, aktivitas, scan segel bekas, foto Check-Out, tombol Kirim Check-Out) -> _work_form.php.
 * Hanya bisa dibuka setelah order selesai dan tutorial segel ditutup (dijaga amt_out_guard()). */
work_styles();
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/checkin.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/checkin.css') ?>">
<link rel="stylesheet" href="<?= AMT_URL ?>/css/checkout.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/checkout.css') ?>">
<?php
$checkins = work_checkins();
?>
<div class="ci-page co-page">

  <!-- Pengingat: scan segel dilakukan di AVM (bukan di aplikasi) sebelum Check-Out -->
  <div class="ci-info co-info">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    <span>Pastikan segel bekas sudah discan di AVM sebelum Check-Out.</span>
  </div>

  <!-- Riwayat Check-In -->
  <section class="co-hist">
    <h2 class="co-h">Riwayat Check-In</h2>
    <?php if (!$checkins): ?>
      <div class="co-hist-empty">Belum ada Check-In.</div>
    <?php else: ?>
      <?php foreach ($checkins as $i => $c): ?>
        <div class="co-hist-card">
          <span class="co-hist-time"><?= h(date('d/m/y H:i', (int) $c['at'])) ?> WIB</span>
          <strong class="co-hist-n">Check-In <?= h(amt_out_ordinal($i + 1)) ?></strong>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Form Check-Out -->
  <div class="co-form">
    <?php $mode = 'checkout'; require __DIR__ . '/_work_form.php'; ?>
  </div>

</div><!-- /.co-page -->