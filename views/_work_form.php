<?php
/* Dipakai oleh views/start_work.php dan views/end_work.php.
 * Butuh variabel $mode = 'start' | 'end'. */
work_styles();

$isStart   = ($mode === 'start');
$label     = $isStart ? 'Start' : 'End';
$w         = work_data();
$activity  = $w['activity'] ?? '';        // End Work: mengikuti pilihan saat Start Work
$mapSrc    = 'https://maps.google.com/maps?q=' . WORK_LAT . ',' . WORK_LNG . '&hl=id&z=17&output=embed';
$coordText = WORK_LAT . ', ' . WORK_LNG;
?>
<form method="post" action="?screen=<?= $isStart ? 'start_work' : 'end_work' ?>" id="wk-form" class="wk-wrap">
  <input type="hidden" name="action" value="<?= $isStart ? 'submit_start_work' : 'submit_end_work' ?>">
  <input type="hidden" name="photo" id="wk-photo" value="0">

  <p class="wk-title">Form Verifikasi Work <?= $label ?></p>

  <!-- Lokasi -->
  <div class="wk-head">
    <span class="wk-label" style="margin:0">Lokasi Anda</span>
    <a href="#" id="wk-refresh">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
      Perbarui Lokasi
    </a>
  </div>
  <iframe id="wk-map" class="wk-map" data-src="<?= htmlspecialchars($mapSrc, ENT_QUOTES) ?>"
          src="<?= htmlspecialchars($mapSrc, ENT_QUOTES) ?>" loading="lazy" allowfullscreen
          referrerpolicy="no-referrer-when-downgrade" title="Lokasi Anda"></iframe>
  <div class="wk-coord"><?= $coordText ?></div>

  <!-- Aktivitas -->
  <span class="wk-label">Aktivitas</span>
  <?php if ($isStart): ?>
    <select name="aktivitas" id="wk-akt" class="wk-select" required>
      <option value="">Pilih Aktivitas</option>
      <?php foreach (WORK_ACTIVITIES as $a): ?>
        <option value="<?= htmlspecialchars($a, ENT_QUOTES) ?>"><?= htmlspecialchars($a) ?></option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <select id="wk-akt" class="wk-select" disabled>
      <option><?= htmlspecialchars($activity !== '' ? $activity : '-') ?></option>
    </select>
    <div class="wk-hint">Mengikuti aktivitas yang dipilih saat Start Work.</div>
  <?php endif; ?>

  <!-- Foto verifikasi -->
  <span class="wk-label">Foto Verifikasi<span style="color:#dc2626"> *</span> <span style="color:#2563eb">&#9432;</span></span>
  <div class="wk-photo" id="wk-box">
    <!-- Sebelum diambil: ikon AMT + tombol Ambil Foto -->
    <div class="wk-idle">
      <?= work_avatar_html(120) ?>
      <b>Selfie untuk Verifikasi <?= $label ?> Work</b>
      <p>Arahkan wajah anda ke kamera depan handphone</p>
      <button type="button" id="wk-take">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Ambil Foto
      </button>
    </div>
    <!-- Setelah diambil: ikon hilang, tampil status terverifikasi -->
    <div class="wk-done">
      <div class="wk-ok">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      </div>
      <b>Foto Terverifikasi</b>
      <p id="wk-done-time"></p>
      <button type="button" class="wk-redo" id="wk-redo">Ambil Ulang</button>
    </div>
  </div>

  <button type="submit" class="wk-submit" id="wk-submit" disabled>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
    Kirim
  </button>

  <!-- Simulasi kamera (tidak membuka kamera sungguhan) -->
  <div class="wk-cam" id="wk-cam">
    <div id="wk-cam-loading" style="display:none;flex-direction:column;align-items:center;gap:10px">
      <div class="wk-spin"></div>
      <small id="wk-cam-loading-text">Membuka kamera...</small>
    </div>
    <div id="wk-cam-live" style="display:none;flex-direction:column;align-items:center;gap:14px">
      <small>Kamera Depan</small>
      <div class="wk-cam-view">
        <?= work_avatar_html(130) ?>
        <div class="wk-oval"></div>
        <div class="wk-flash" id="wk-flash"></div>
      </div>
      <small>Posisikan wajah di dalam bingkai</small>
      <button type="button" class="wk-shutter" id="wk-shutter" aria-label="Ambil foto"></button>
      <button type="button" class="wk-cancel" id="wk-cancel">Batal</button>
    </div>
  </div>
</form>

<script>
(function () {
  var needActivity = <?= $isStart ? 'true' : 'false' ?>;
  var wrap   = document.getElementById('wk-form');
  var sel    = document.getElementById('wk-akt');
  var photo  = document.getElementById('wk-photo');
  var box    = document.getElementById('wk-box');
  var submit = document.getElementById('wk-submit');
  var cam    = document.getElementById('wk-cam');
  var loading = document.getElementById('wk-cam-loading');
  var loadingText = document.getElementById('wk-cam-loading-text');
  var live   = document.getElementById('wk-cam-live');
  var flash  = document.getElementById('wk-flash');
  var busy   = false;

  function refresh() {
    submit.disabled = !(photo.value === '1' && (!needActivity || sel.value !== ''));
  }
  if (needActivity) sel.addEventListener('change', refresh);

  function show(el, on) { el.style.display = on ? 'flex' : 'none'; }

  // Buka "kamera": spinner sebentar, lalu tampilan viewfinder
  function openCamera() {
    if (busy) return;
    busy = true;
    wrap.scrollIntoView({ block: 'start' });
    cam.classList.add('open');
    loadingText.textContent = 'Membuka kamera...';
    show(loading, true); show(live, false);
    setTimeout(function () { show(loading, false); show(live, true); busy = false; }, 900);
  }

  function closeCamera() {
    cam.classList.remove('open');
    show(loading, false); show(live, false);
    busy = false;
  }

  // Jepret: kilat putih -> "Memverifikasi..." -> ikon hilang, foto terverifikasi
  function capture() {
    if (busy) return;
    busy = true;
    flash.classList.add('fire');
    setTimeout(function () { flash.classList.remove('fire'); }, 120);
    setTimeout(function () {
      show(live, false);
      loadingText.textContent = 'Memverifikasi foto...';
      show(loading, true);
    }, 350);
    setTimeout(function () {
      closeCamera();
      photo.value = '1';
      box.classList.add('taken');
      document.getElementById('wk-done-time').textContent =
        new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
      refresh();
    }, 1300);
  }

  document.getElementById('wk-take').addEventListener('click', openCamera);
  document.getElementById('wk-redo').addEventListener('click', openCamera);
  document.getElementById('wk-shutter').addEventListener('click', capture);
  document.getElementById('wk-cancel').addEventListener('click', closeCamera);

  // Lokasi tetap; "Perbarui Lokasi" hanya memuat ulang peta
  document.getElementById('wk-refresh').addEventListener('click', function (e) {
    e.preventDefault();
    var f = document.getElementById('wk-map');
    f.src = f.getAttribute('data-src') + '&t=' + Date.now();
  });

  refresh();
})();
</script>