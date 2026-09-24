<?php
/* SNIPPET untuk dashboard AMT: ganti blok "Waktu Kerja" dan grid menu
 * di file view dashboard AMT kamu dengan isi file ini.
 * Pertahankan ikon/markup asli tiap menu (di sini disederhanakan). */
work_styles();
$running = work_is_running();
?>

<!-- Waktu Kerja -->
<div class="work-timer-box">
  <div>Waktu Kerja</div>
  <div id="work-timer" data-start="<?= work_started_at() ?>">00:00:00</div>
</div>

<!-- Menu -->
<div class="menu-grid">
  <a class="menu-item" href="?screen=start_end">Start / End</a>

  <?php foreach (['checkin' => 'Check-In', 'pti' => 'PTI'] as $screen => $label): ?>
    <?php if ($running): ?>
      <a class="menu-item" href="?screen=<?= $screen ?>"><?= $label ?></a>
    <?php else: ?>
      <span class="menu-item is-disabled" aria-disabled="true"><?= $label ?></span>
    <?php endif; ?>
  <?php endforeach; ?>

  <a class="menu-item" href="?screen=shipments_list">Shipments</a>

  <?php if ($running): ?>
    <a class="menu-item" href="?screen=checkout">Check-Out</a>
  <?php else: ?>
    <span class="menu-item is-disabled" aria-disabled="true">Check-Out</span>
  <?php endif; ?>

  <a class="menu-item" href="?screen=performance">Performance</a>
  <a class="menu-item" href="?screen=safire">SAFIRE</a>
</div>

<script>
(function () {
  var el = document.getElementById('work-timer');
  var start = parseInt(el.dataset.start, 10);
  if (!start) return;
  function pad(n) { return String(n).padStart(2, '0'); }
  function tick() {
    var s = Math.max(0, Math.floor(Date.now() / 1000) - start);
    el.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
  }
  tick(); setInterval(tick, 1000);
})();
</script>