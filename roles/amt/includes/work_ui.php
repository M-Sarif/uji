<?php
/* Dipanggil dari index.php hanya di beranda AMT.
 * Menu (Mulai / Selesai Kerja aktif; Mulai Ritase, PTI, Selesai Ritase terkunci saat timer belum
 * jalan) sudah dirender oleh roles/amt/views/amt_home.php.
 * File ini menjalankan dua timer: "Jam Kerja" (sejak Mulai Kerja) dan "Jam Kerja Aktif" (sejak Mulai Ritase, dijeda saat Selesai Ritase). */
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

  var dcuHandled = false;   // pemberitahuan datang dua kali (Mengerti, lalu alur selesai): proses sekali saja
  document.addEventListener('amt:tour-done', function (e) {
    if (dcuHandled || !e.detail || String(e.detail.key).indexOf('amt_home_dcu_') !== 0) return;
    dcuHandled = true;
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
/* Aktivasi menu Selesai Ritase di beranda tanpa muat ulang:
 *  - tutorial "scan segel di AVM" ditutup (Mengerti / Lewati / hilang sendiri setelah 20 detik)
 *    -> lapor ke server, menu Selesai Ritase menyala sesaat kemudian
 *  - failsafe: menyala sendiri setelah sisa waktu habis, walau tutorial tidak sempat ditutup */
(function () {
  var tile = document.querySelector('[data-amt-out]');
  if (!tile || tile.tagName === 'A') return;          // tidak ada / sudah aktif dari server
  var href = tile.getAttribute('data-href') || '?screen=checkout';
  var done = false;

  function ack(key) {
    try {
      var b = new URLSearchParams();
      b.set('out_action', 'seal_ack'); b.set('key', key);
      fetch(window.location.href, { method: 'POST', body: b, credentials: 'same-origin', keepalive: true });
    } catch (e) {}
  }
  function unlock() {
    if (done) return; done = true;
    var a = document.createElement('a');
    a.className = tile.className.replace(/\bis-disabled\b/, '').trim();
    a.setAttribute('href', href);
    a.setAttribute('data-tour', tile.getAttribute('data-tour'));
    a.setAttribute('data-amt-out', '');
    while (tile.firstChild) a.appendChild(tile.firstChild);
    tile.parentNode.replaceChild(a, tile);
    ack('unlocked');
  }

  var sealHandled = false;   // pemberitahuan bisa datang dua kali (Mengerti, lalu alur selesai): proses sekali saja
  document.addEventListener('amt:tour-done', function (e) {
    if (sealHandled || !e.detail || String(e.detail.key).indexOf('amt_home_seal_') !== 0) return;
    sealHandled = true;
    ack('seal_seen');
    setTimeout(unlock, <?= (int) AMT_OUT_UNLOCK_DELAY ?>);
  });

  <?php if (amt_out_order_done()): ?>
  setTimeout(unlock, <?= (int) amt_out_failsafe_remaining() ?> * 1000 + 300);
  <?php endif; ?>
})();
</script>
<script>
/* Dua jam di beranda AMT:
 *  - Jam Kerja        : berjalan sejak "Mulai Kerja" sampai "Selesai Kerja" (lalu kembali 00:00:00).
 *  - Jam Kerja Aktif  : berjalan sejak "Mulai Ritase"; DIJEDA saat "Selesai Ritase" dan MELANJUTKAN
 *                       dari angka terakhir saat "Mulai Ritase" berikutnya. Kembali 00:00:00 saat "Selesai Kerja".
 * Waktu dihitung dari jam SERVER (SERVER_NOW), bukan jam HP, supaya angka tetap akurat. */
(function () {
  var START      = <?= work_started_at() ?>;        // 0 = kerja belum mulai / sudah selesai
  var ACT_ACC    = <?= work_active_acc() ?>;        // detik jam aktif yang sudah terkumpul (sesi yang sudah dijeda)
  var ACT_SINCE  = <?= work_active_since() ?>;      // 0 = Jam Kerja Aktif dijeda / belum mulai
  var SERVER_NOW = <?= time() ?>;                   // jam server saat halaman dibuat
  var LOADED_AT  = Date.now();

  var elWork   = document.getElementById('amtWorktime');
  var elActive = document.getElementById('amtActiveTime');

  function pad(n) { return String(n).padStart(2, '0'); }
  function fmt(s) {
    s = Math.max(0, Math.floor(s));
    return pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
  }
  function nowServer() { return SERVER_NOW + (Date.now() - LOADED_AT) / 1000; }

  function tick() {
    var now = nowServer();
    if (elWork) {
      elWork.textContent = START ? fmt(now - START) : '00:00:00';
      elWork.classList.toggle('is-on', !!START);
    }
    if (elActive) {
      var sec = START ? ACT_ACC + (ACT_SINCE ? now - ACT_SINCE : 0) : 0;
      elActive.textContent = fmt(sec);
      elActive.classList.toggle('is-on', !!(START && ACT_SINCE));
    }
  }

  if (!START) { tick(); return; }   // kerja belum mulai: kedua jam 00:00:00, tidak perlu berdetak
  tick();
  setInterval(tick, 1000);
})();
</script>