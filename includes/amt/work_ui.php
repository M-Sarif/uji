<?php
/* Dipanggil dari index.php hanya di beranda AMT.
 * Menu (Start/End aktif; Check-In, PTI, Check-Out terkunci saat timer belum
 * jalan) sudah dirender oleh views/amt/amt_home.php.
 * File ini hanya menjalankan timer "Waktu Kerja" dari waktu Start Work di server. */
?>
<script>
(function () {
  var START = <?= work_started_at() ?>;   // 0 = timer berhenti
  var timer = document.getElementById('amtWorktime');
  if (!START || !timer) return;

  function pad(n) { return String(n).padStart(2, '0'); }
  function tick() {
    var s = Math.max(0, Math.floor(Date.now() / 1000) - START);
    timer.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
  }
  tick();
  setInterval(tick, 1000);
})();
</script>