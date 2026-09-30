<?php
/* Dipanggil dari index.php hanya di beranda AMT.
 * Menu (Start/End aktif; Check-In, PTI, Check-Out terkunci saat timer belum
 * jalan) sudah dirender oleh views/amt/amt_home.php.
 * File ini hanya menjalankan timer "Waktu Kerja" dari waktu Start Work di server. */
?>
<script>
/* Aktivasi menu PTI di beranda tanpa muat ulang:
 *  - tutorial DCU ditutup (Mengerti / Lewati)  -> lapor ke server, PTI menyala 2 detik kemudian
 *  - failsafe: PTI menyala sendiri setelah sisa waktu habis, walau tutorial tidak ditutup */
(function () {
  var tile = document.querySelector('[data-amt-pti]');
  if (!tile || tile.tagName === 'A') return;          // tidak ada / sudah aktif dari server
  var href = tile.getAttribute('data-href') || '?screen=amt_pti';
  var done = false;

  function ack(key) {
    try {
      var b = new URLSearchParams();
      b.set('pti_action', 'flow_ack'); b.set('key', key);
      fetch(window.location.href, { method: 'POST', body: b, credentials: 'same-origin', keepalive: true });
    } catch (e) {}
  }
  function unlock() {
    if (done) return; done = true;
    var a = document.createElement('a');
    a.className = tile.className.replace(/\bis-disabled\b/, '').trim();
    a.setAttribute('href', href);
    a.setAttribute('data-tour', tile.getAttribute('data-tour'));
    a.setAttribute('data-amt-pti', '');
    while (tile.firstChild) a.appendChild(tile.firstChild);
    tile.parentNode.replaceChild(a, tile);
    ack('pti_unlocked');
  }

  document.addEventListener('amt:tour-done', function (e) {
    if (!e.detail || String(e.detail.key).indexOf('amt_home_dcu_') !== 0) return;
    ack('dcu_seen');
    setTimeout(unlock, <?= (int) AMT_PTI_UNLOCK_DELAY ?>);
  });

  var left = <?= (int) amt_pti_failsafe_remaining() ?>;
  <?php if (amt_last_checkin_at() > 0 && work_is_running()): ?>
  setTimeout(unlock, left * 1000 + 300);
  <?php endif; ?>
})();
</script>
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