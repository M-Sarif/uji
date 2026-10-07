<?php
/* Layar End Work AMT (Start / End -> End Work).
 * Isi: kartu info "Akhiri Pekerjaan Hari Ini" + kartu "Total Waktu Kerja Hari Ini" (jam berjalan),
 * lalu Form Verifikasi End Work (lokasi, aktivitas mengikuti Start Work, foto End Work) -> _work_form.php (mode 'end').
 * Tombol "Akhiri Pekerjaan" menempel di bawah layar dan membuka dialog konfirmasi (Ya / Batal). */
work_styles();
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/endwork.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/endwork.css') ?>">
<?php
$ewStart   = (int) (work_data()['started_at'] ?? 0);
$ewElapsed = $ewStart > 0 ? max(0, time() - $ewStart) : 0;
$ewText    = sprintf('%02d : %02d : %02d', intdiv($ewElapsed, 3600), intdiv($ewElapsed % 3600, 60), $ewElapsed % 60);
?>
<div class="ew-page">

  <!-- Info + total waktu kerja -->
  <div class="ew-card" id="ew-top">
    <div class="ew-info">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
      <div>
        <b>Akhiri Pekerjaan Hari Ini</b>
        <p>Silakan ambil foto selfie dan pastikan lokasi Anda sudah sesuai sebelum mengakhiri pekerjaan.</p>
      </div>
    </div>
    <div class="ew-timer" id="ew-timer">
      <small>Total Waktu Kerja Hari Ini</small>
      <span class="ew-time" id="ew-time"><?= h($ewText) ?></span>
    </div>
  </div>

  <?php $mode = 'end'; require __DIR__ . '/_work_form.php'; ?>

</div><!-- /.ew-page -->

<script>
(function () {
  var START = <?= $ewStart ?>;                 // waktu Start Work (detik, dari server)
  var out = document.getElementById('ew-time');
  if (!START || !out) return;
  function pad(n) { return String(n).padStart(2, '0'); }
  function tick() {
    var s = Math.max(0, Math.floor(Date.now() / 1000) - START);
    out.textContent = pad(Math.floor(s / 3600)) + ' : ' + pad(Math.floor(s % 3600 / 60)) + ' : ' + pad(s % 60);
  }
  tick();
  setInterval(tick, 1000);
})();
</script>