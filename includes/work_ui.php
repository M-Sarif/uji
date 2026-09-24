<?php
/* Dipanggil dari index.php hanya di beranda AMT.
 * Bekerja dengan mencari menu berdasarkan teks labelnya, jadi tidak
 * bergantung pada markup/class dashboard asli:
 *  - "Start / End"  -> selalu bisa diklik, membuka halaman Start/End Work
 *  - "Check-In", "PTI", "Check-Out" -> nonaktif (abu-abu, tidak bisa diklik)
 *    selama timer belum berjalan
 *  - timer "00:00:00" berjalan dari waktu Start Work di server */
?>
<script>
(function () {
  var RUNNING = <?= work_is_running() ? 'true' : 'false' ?>;
  var START   = <?= work_started_at() ?>;
  var START_URL = '?screen=start_end';

  function norm(s) { return (s || '').replace(/\s+/g, ' ').trim().toLowerCase(); }

  // Cari tile menu (ikon + label) dari teks labelnya
  function findTile(label) {
    var els = document.querySelectorAll('body *');
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (el.children.length === 0 && norm(el.textContent) === label) {
        var t = el.parentElement;
        while (t && t.children.length < 2 && t.parentElement) t = t.parentElement;
        return t;
      }
    }
    return null;
  }

  var startTile = findTile('start / end');
  var gated = [];
  ['check-in', 'pti', 'check-out'].forEach(function (l) {
    var t = findTile(l);
    if (t) gated.push(t);
  });

  // Start / End: paksa bisa diklik
  if (startTile) {
    startTile.style.cursor = 'pointer';
    startTile.style.pointerEvents = 'auto';
    startTile.querySelectorAll('*').forEach(function (c) { c.style.pointerEvents = 'auto'; });
  }

  // Check-In / PTI / Check-Out: tampil nonaktif saat timer belum jalan
  if (!RUNNING) {
    gated.forEach(function (t) {
      t.style.opacity = '.45';
      t.style.filter = 'grayscale(1)';
      t.style.cursor = 'not-allowed';
      t.setAttribute('aria-disabled', 'true');
      t.querySelectorAll('a[href]').forEach(function (a) { a.removeAttribute('href'); });
    });
  }

  function inside(el, x, y) {
    var r = el.getBoundingClientRect();
    return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
  }

  // Penangan klik berbasis posisi, jadi tetap jalan walau ada overlay/elemen
  // lain yang menutupi tile.
  document.addEventListener('click', function (e) {
    if (startTile && inside(startTile, e.clientX, e.clientY)) {
      e.preventDefault(); e.stopPropagation();
      window.location.href = START_URL;
      return;
    }
    if (!RUNNING) {
      for (var i = 0; i < gated.length; i++) {
        if (inside(gated[i], e.clientX, e.clientY)) {
          e.preventDefault(); e.stopPropagation();
          return;
        }
      }
    }
  }, true);

  // Timer
  if (START) {
    var timer = null;
    document.querySelectorAll('body *').forEach(function (el) {
      if (!timer && el.children.length === 0 && /^\d\d:\d\d:\d\d$/.test(el.textContent.trim())) timer = el;
    });
    if (timer) {
      var pad = function (n) { return String(n).padStart(2, '0'); };
      var tick = function () {
        var s = Math.max(0, Math.floor(Date.now() / 1000) - START);
        timer.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
      };
      tick(); setInterval(tick, 1000);
    }
  }
})();
</script>