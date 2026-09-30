<?php
/* Dipakai oleh views/amt/start_work.php, end_work.php, dan checkin.php.
 * Butuh variabel $mode = 'start' | 'end' | 'checkin'. */
work_styles();

$isEnd     = ($mode === 'end');
$isCheckin = ($mode === 'checkin');
$needAct   = !$isEnd;                       // Start Work & Check-In: aktivitas wajib dipilih
$label     = $isCheckin ? 'Check-In' : ($isEnd ? 'End Work' : 'Start Work');
$screenKey = $isCheckin ? 'checkin' : ($isEnd ? 'end_work' : 'start_work');
$postAct   = $isCheckin ? 'submit_checkin' : ($isEnd ? 'submit_end_work' : 'submit_start_work');
$actList   = $isCheckin ? CHECKIN_ACTIVITIES : WORK_ACTIVITIES;
$w         = work_data();
$activity  = $w['activity'] ?? '';        // End Work: mengikuti pilihan saat Start Work
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/camera.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/camera.css') ?>">
<form method="post" action="?screen=<?= $screenKey ?>" id="wk-form" class="wk-wrap"
      data-loc="0" data-act="<?= $needAct ? '0' : '1' ?>" data-photo="0">
  <input type="hidden" name="action" value="<?= $postAct ?>">
  <input type="hidden" name="photo" id="wk-photo" value="0">
  <input type="hidden" name="photo_data" id="wk-photo-data" value="">
  <input type="hidden" name="lat" id="wk-lat" value="">
  <input type="hidden" name="lng" id="wk-lng" value="">

  <p class="wk-title">Form Verifikasi <?= $isCheckin ? 'Check-In' : 'Work ' . ($isEnd ? 'End' : 'Start') ?></p>

  <!-- Lokasi: peta Google Maps + status jangkauan -->
  <div id="wk-loc" data-inrange="0">
  <div class="wk-head">
    <span class="wk-label" id="wk-loc-title" style="margin:0">Lokasi Anda</span>
    <a href="#" id="wk-refresh">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
      Perbarui Lokasi
    </a>
  </div>
  <iframe id="wk-map" class="wk-map" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="Lokasi Anda"></iframe>
  <div class="wk-geo" id="wk-geo" role="status">Mencari lokasi...</div>
  <div class="wk-coord" id="wk-coord"></div>
  </div>

  <!-- Aktivitas -->
  <div id="wk-act-box">
  <span class="wk-label">Aktivitas<?php if ($needAct): ?><span style="color:#dc2626">*</span><?php endif; ?></span>
  <?php if ($needAct): ?>
    <select name="aktivitas" id="wk-akt" class="wk-select" required>
      <option value="">Pilih aktivitas</option>
      <?php foreach ($actList as $a): ?>
        <option value="<?= htmlspecialchars($a, ENT_QUOTES) ?>"><?= htmlspecialchars($a) ?></option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <select id="wk-akt" class="wk-select" disabled>
      <option><?= htmlspecialchars($activity !== '' ? $activity : '-') ?></option>
    </select>
    <div class="wk-hint">Mengikuti aktivitas yang dipilih saat Start Work.</div>
  <?php endif; ?>
  </div>

  <!-- Foto verifikasi -->
  <div id="wk-photo-box">
  <span class="wk-label">Foto Verifikasi<span style="color:#dc2626"> *</span> <span style="color:#2563eb">&#9432;</span></span>
  <div class="wk-photo" id="wk-box">
    <!-- Sebelum diambil: ikon AMT + tombol Ambil Foto -->
    <div class="wk-idle">
      <?= work_avatar_html(120) ?>
      <b>Selfie untuk Verifikasi <?= $label ?></b>
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
  </div>

  <button type="submit" class="wk-submit" id="wk-submit" disabled>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
    Kirim
  </button>

</form>

<script src="<?= AMT_URL ?>/js/camera.js?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/js/camera.js') ?>"></script>
<script>
(function () {
  var needActivity = <?= $needAct ? 'true' : 'false' ?>;
  var sel    = document.getElementById('wk-akt');
  var photo  = document.getElementById('wk-photo');
  var photoData = document.getElementById('wk-photo-data');
  var box    = document.getElementById('wk-box');
  var submit = document.getElementById('wk-submit');

  var form = document.getElementById('wk-form');
  var inRange = false;
  // Tombol Kirim aktif bila lokasi, aktivitas, dan foto sudah lengkap.
  // Status tiap bagian juga ditulis ke atribut data-* pada form; tutorial AMT
  // (assets/amt/js/tutorial-amt.js) membaca atribut ini untuk tahu langkah mana
  // yang sudah selesai.
  function refresh() {
    var actOk   = !needActivity || sel.value !== '';
    var photoOk = photo.value === '1';
    submit.disabled = !(inRange && photoOk && actOk);
    form.setAttribute('data-loc',   inRange ? '1' : '0');
    form.setAttribute('data-act',   actOk   ? '1' : '0');
    form.setAttribute('data-photo', photoOk ? '1' : '0');
    document.dispatchEvent(new CustomEvent('amt:state'));
  }
  if (needActivity) sel.addEventListener('change', refresh);

  // Kamera layar penuh "Foto Verifikasi" (assets/amt/js/camera.js)
  var cam = AmtCamera.create({
    facing: 'user',
    title: 'Foto Verifikasi',
    onSave: function (dataUrl) {
      photo.value = '1';
      photoData.value = dataUrl;
      box.classList.add('taken');
      document.getElementById('wk-done-time').textContent =
        new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
      refresh();
    }
  });

  document.getElementById('wk-take').addEventListener('click', function () { cam.open(); });
  document.getElementById('wk-redo').addEventListener('click', function () { cam.open(); });

  // ---- Lokasi (simulasi) + peta Google Maps + batas jangkauan ----
  var BASE = { lat: <?= WORK_LAT ?>, lng: <?= WORK_LNG ?> }, RADIUS = <?= WORK_RADIUS_M ?>;
  var PLACE = <?= json_encode(WORK_PLACE_NAME) ?>;
  var OUT_CHANCE = <?= (float) WORK_SIM_OUTSIDE_CHANCE ?>;
  var mapEl = document.getElementById('wk-map'), geo = document.getElementById('wk-geo');
  var latEl = document.getElementById('wk-lat'), lngEl = document.getElementById('wk-lng');

  function distance(a, b) {               // haversine, meter
    var R = 6371000, rad = Math.PI / 180;
    var dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
    var h = Math.pow(Math.sin(dLat / 2), 2) +
            Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.pow(Math.sin(dLng / 2), 2);
    return 2 * R * Math.asin(Math.sqrt(h));
  }
  function pointAt(meters) {              // titik acak pada jarak tertentu dari titik kerja
    var ang = Math.random() * 2 * Math.PI;
    return {
      lat: BASE.lat + (meters * Math.cos(ang)) / 111320,
      lng: BASE.lng + (meters * Math.sin(ang)) / (111320 * Math.cos(BASE.lat * Math.PI / 180))
    };
  }
  function setPos(p) {
    var d = Math.round(distance(p, BASE));
    inRange = d <= RADIUS;
    latEl.value = p.lat.toFixed(6);
    lngEl.value = p.lng.toFixed(6);
    // Pin Google Maps selalu berada tepat di koordinat posisi user saat ini
    // Sesuai titik kerja -> tampilkan pin bawaan Google Maps untuk terminal (nama + kartu tempat).
    // Belum sesuai -> pin biasa di koordinat user, jadi terlihat jelas berada di tempat lain.
    mapEl.src = inRange
      ? 'https://maps.google.com/maps?q=' + encodeURIComponent(PLACE) +
        '&ll=' + BASE.lat + ',' + BASE.lng + '&hl=id&z=17&output=embed&t=' + Date.now()
      : 'https://maps.google.com/maps?q=' + p.lat.toFixed(6) + ',' + p.lng.toFixed(6) +
        '&hl=id&z=16&output=embed&t=' + Date.now();
    document.getElementById('wk-coord').textContent = p.lat.toFixed(6) + ', ' + p.lng.toFixed(6);
    geo.className = 'wk-geo ' + (inRange ? 'ok' : 'bad');
    geo.textContent = inRange
      ? 'Lokasi sesuai: ' + PLACE
      : 'Lokasi belum sesuai titik kerja (' + d + ' m). Ketuk "Perbarui Lokasi".';
    var loc = document.getElementById('wk-loc');
    loc.setAttribute('data-inrange', inRange ? '1' : '0');
    refresh();
  }
  function simulate(forceInside) {
    // Perbarui Lokasi -> pin tepat di titik kerja. Posisi awal bisa "melenceng" (simulasi).
    var outside = !forceInside && Math.random() < OUT_CHANCE;
    setPos(outside ? pointAt(650 + Math.random() * 850) : { lat: BASE.lat, lng: BASE.lng });
  }
  document.getElementById('wk-refresh').addEventListener('click', function (e) {
    e.preventDefault();
    simulate(true);                       // Perbarui Lokasi -> selalu masuk radius
  });
  simulate(false);

  refresh();
})();
</script>