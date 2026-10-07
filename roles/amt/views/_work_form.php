<?php
/* Dipakai oleh roles/amt/views/start_work.php, end_work.php, checkin.php, dan checkout.php.
 * Butuh variabel $mode = 'start' | 'end' | 'checkin' | 'checkout'.
 *
 * Mode 'end' (End Work) dan 'checkout' (Check-Out) memakai TAMPILAN BARU ($isV2, kelas .wk-v2):
 *   - foto yang sudah diambil tampil sebagai pratinjau + tombol merah "Hapus Foto"
 *   - tombol kirim menempel di bawah layar (Check-Out: "Kirim Check-Out", End Work: "Akhiri Pekerjaan")
 *   - End Work: dialog konfirmasi "Ya / Batal" sebelum benar-benar dikirim
 *   - Check-Out: pop up sukses "Check-Out Berhasil" + tombol "OK"; Riwayat Check-In di belakangnya langsung kosong
 * Mode 'start' dan 'checkin' tidak berubah. */
work_styles();

$isEnd     = ($mode === 'end');
$isOut     = ($mode === 'checkout');
$isCheckin = ($mode === 'checkin');
$needAct   = !$isEnd;                       // Start Work, Check-In & Check-Out: aktivitas wajib dipilih
$label     = $isOut ? 'Check-Out' : ($isCheckin ? 'Check-In' : ($isEnd ? 'End Work' : 'Start Work'));
$screenKey = $isOut ? 'checkout' : ($isCheckin ? 'checkin' : ($isEnd ? 'end_work' : 'start_work'));
$postAct   = $isOut ? 'submit_checkout' : ($isCheckin ? 'submit_checkin' : ($isEnd ? 'submit_end_work' : 'submit_start_work'));
$actList   = ($isCheckin || $isOut) ? CHECKIN_ACTIVITIES : WORK_ACTIVITIES;
$isV2      = ($isEnd || $isOut);              // tampilan baru: End Work & Check-Out
$photoName = $isOut ? 'Foto Check-Out' : ($isEnd ? 'Foto End Work' : 'Foto Verifikasi');
$hasCheckin = !$isOut || (bool) work_checkins();   // Check-Out butuh minimal satu Check-In
$btnLabel  = $isOut ? ($hasCheckin ? 'Kirim Check-Out' : 'Lakukan Check-In Terlebih Dahulu')
           : ($isEnd ? 'Akhiri Pekerjaan' : 'Kirim');
$w         = work_data();
$activity  = $w['activity'] ?? '';        // End Work: mengikuti pilihan saat Start Work
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/camera.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/camera.css') ?>">
<form method="post" action="?screen=<?= $screenKey ?>" id="wk-form" class="wk-wrap<?= $isV2 ? ' wk-v2 wk-v2-' . $mode : '' ?>"
      data-loc="0" data-pin="0" data-act="<?= $needAct ? '0' : '1' ?>" data-photo="0">
  <input type="hidden" name="action" value="<?= $postAct ?>">
  <input type="hidden" name="photo" id="wk-photo" value="0">
  <input type="hidden" name="photo_data" id="wk-photo-data" value="">
  <input type="hidden" name="lat" id="wk-lat" value="">
  <input type="hidden" name="lng" id="wk-lng" value="">

  <?php if ($isEnd): ?><div class="wk-v2-card"><?php endif; /* End Work: judul, lokasi, aktivitas, foto = satu kartu */ ?>
  <p class="wk-title"><?= $isOut ? 'Form Check-Out' : 'Form Verifikasi ' . ($isCheckin ? 'Check-In' : ($isEnd ? 'End Work' : 'Work Start')) ?></p>

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

  <?php if ($isOut): ?><div class="wk-v2-card"><?php endif; /* Check-Out: Aktivitas + Foto = kartu putih */ ?>
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
      <option><?= htmlspecialchars($activity !== '' ? ($isV2 ? mb_strtoupper($activity, 'UTF-8') : $activity) : '-') ?></option>
    </select>
    <?php if (!$isV2): ?><div class="wk-hint">Mengikuti aktivitas yang dipilih saat Start Work.</div><?php endif; ?>
  <?php endif; ?>
  </div>

  <!-- Foto verifikasi -->
  <div id="wk-photo-box">
  <span class="wk-label"><?= $photoName ?><span style="color:#dc2626"><?= $isV2 ? '*' : ' *' ?></span>
    <?php if ($isOut): ?>
      <svg class="wk-info-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    <?php elseif (!$isV2): ?>
      <span style="color:#2563eb">&#9432;</span>
    <?php endif; ?>
  </span>
  <div class="wk-photo" id="wk-box">
    <!-- Sebelum diambil: ikon AMT + ajakan Ambil Foto. SELURUH kartu ini (#wk-box) bisa diketuk untuk membuka kamera. -->
    <div class="wk-idle" role="button" tabindex="0" aria-label="Ambil Foto selfie untuk Verifikasi <?= $label ?>">
      <?= work_avatar_html(120) ?>
      <b>Selfie untuk Verifikasi <?= $label ?></b>
      <p>Arahkan wajah anda ke kamera depan handphone</p>
      <span class="wk-cta">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Ambil Foto
      </span>
    </div>
    <?php if ($isV2): ?>
    <!-- Setelah diambil (tampilan baru): pratinjau foto + tombol merah "Hapus Foto" -->
    <div class="wk-prev">
      <img id="wk-preview" alt="<?= htmlspecialchars($photoName, ENT_QUOTES) ?>">
      <button type="button" class="wk-del" id="wk-del">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/></svg>
        Hapus Foto
      </button>
    </div>
    <?php else: ?>
    <!-- Setelah diambil: ikon hilang, tampil status terverifikasi -->
    <div class="wk-done">
      <div class="wk-ok">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      </div>
      <b>Foto Terverifikasi</b>
      <p id="wk-done-time"></p>
      <button type="button" class="wk-redo" id="wk-redo">Ambil Ulang</button>
    </div>
    <?php endif; ?>
  </div>
  </div>
  <?php if ($isV2): ?></div><!-- /.wk-v2-card --><?php endif; ?>

  <?php if ($isV2): ?><div class="wk-bar"><?php endif; ?>
  <button type="submit" class="wk-submit<?= $isEnd ? ' wk-submit-end' : '' ?>" id="wk-submit" disabled>
    <?php if (!$isEnd): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg><?php endif; ?>
    <span id="wk-submit-label"><?= htmlspecialchars($btnLabel) ?></span>
  </button>
  <?php if ($isV2): ?></div><?php endif; ?>

</form>

<!-- Pop up sukses (muncul setelah Kirim berhasil; dipindah ke dalam .app-container oleh skrip di bawah) -->
<div class="wk-modal<?= $isOut ? ' is-plain' : '' ?>" id="wk-success" role="dialog" aria-modal="true" aria-labelledby="wk-success-title" hidden>
  <div class="wk-modal-card">
    <div class="wk-modal-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
    </div>
    <h3 class="wk-modal-title" id="wk-success-title"></h3>
    <p class="wk-modal-body" id="wk-success-body"></p>
    <a class="wk-modal-btn" id="wk-success-go" href="index.php?screen=<?= htmlspecialchars(amt_home_screen(), ENT_QUOTES) ?>"><?= $isOut ? 'OK' : 'Lanjutkan ke Homepage' ?></a>
  </div>
</div>

<?php if ($isEnd): ?>
<!-- Dialog konfirmasi End Work (muncul setelah "Akhiri Pekerjaan" diketuk; dipindah ke dalam .app-container oleh skrip di bawah) -->
<div class="wk-modal wk-confirm" id="ew-confirm" role="dialog" aria-modal="true" aria-labelledby="ew-confirm-title" hidden>
  <div class="wk-confirm-card">
    <h3 id="ew-confirm-title">End Work</h3>
    <p>Apakah Anda yakin ingin mengakhiri pekerjaan hari ini?</p>
    <button type="button" class="wk-confirm-yes" id="ew-yes">Ya</button>
    <button type="button" class="wk-confirm-no" id="ew-no">Batal</button>
  </div>
</div>
<?php endif; ?>

<script src="<?= AMT_URL ?>/js/camera.js?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/js/camera.js') ?>"></script>
<script>
(function () {
  var needActivity = <?= $needAct ? 'true' : 'false' ?>;
  var isV2     = <?= $isV2 ? 'true' : 'false' ?>;       // tampilan baru (End Work & Check-Out)
  var isEnd    = <?= $isEnd ? 'true' : 'false' ?>;
  var isOut    = <?= $isOut ? 'true' : 'false' ?>;
  var hasCheckin = <?= $hasCheckin ? 'true' : 'false' ?>;   // Check-Out hanya bila masih ada Check-In
  var sel    = document.getElementById('wk-akt');
  var photo  = document.getElementById('wk-photo');
  var photoData = document.getElementById('wk-photo-data');
  var box    = document.getElementById('wk-box');
  var submit = document.getElementById('wk-submit');

  var form = document.getElementById('wk-form');
  var inRange = false;
  // Tombol Kirim aktif bila lokasi, aktivitas, dan foto sudah lengkap.
  // Status tiap bagian juga ditulis ke atribut data-* pada form; tutorial AMT
  // (roles/amt/assets/js/tutorial-amt.js) membaca atribut ini untuk tahu langkah mana
  // yang sudah selesai.
  function refresh() {
    var actOk   = !needActivity || sel.value !== '';
    var photoOk = photo.value === '1';
    submit.disabled = !(inRange && photoOk && actOk && hasCheckin);
    form.setAttribute('data-loc',   inRange ? '1' : '0');
    form.setAttribute('data-act',   actOk   ? '1' : '0');
    form.setAttribute('data-photo', photoOk ? '1' : '0');
    document.dispatchEvent(new CustomEvent('amt:state'));
  }
  if (needActivity) sel.addEventListener('change', refresh);

  // Kamera layar penuh "Foto Verifikasi" (roles/amt/assets/js/camera.js)
  var cam = AmtCamera.create({
    facing: 'user',
    title: 'Foto Verifikasi',
    onSave: function (dataUrl) {
      photo.value = '1';
      photoData.value = dataUrl;
      box.classList.add('taken');
      var pv = document.getElementById('wk-preview');            // tampilan baru: pratinjau foto
      if (pv) { pv.src = dataUrl; }
      var dt = document.getElementById('wk-done-time');          // tampilan lama: waktu terverifikasi
      if (dt) {
        dt.textContent = new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
      }
      refresh();
    }
  });

  // SELURUH kartu selfie (#wk-box) adalah tombol: ketuk di mana saja pada kartu = buka kamera.
  // Setelah foto diambil kartu berubah jadi "Foto Terverifikasi"; hanya "Ambil Ulang" yang membuka kamera lagi.
  box.addEventListener('click', function () {
    if (!box.classList.contains('taken')) { cam.open(); }
  });
  box.querySelector('.wk-idle').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); cam.open(); }
  });
  var redo = document.getElementById('wk-redo');
  if (redo) { redo.addEventListener('click', function () { cam.open(); }); }

  // Tampilan baru: "Hapus Foto" membuang foto -> kartu kembali ke ajakan "Ambil Foto".
  // stopPropagation supaya ketukan ini tidak ikut membuka kamera (kartu = tombol ambil foto).
  var del = document.getElementById('wk-del');
  if (del) {
    del.addEventListener('click', function (e) {
      e.stopPropagation();
      photo.value = '0';
      photoData.value = '';
      box.classList.remove('taken');
      var pv = document.getElementById('wk-preview');
      if (pv) { pv.removeAttribute('src'); }
      refresh();
    });
  }

  // ---- Kirim: tanpa pindah halaman; hasil sukses tampil sebagai pop up, lalu "Lanjutkan ke Homepage" ----
  var okModal = document.getElementById('wk-success');
  var appHost = document.querySelector('.app-container');
  if (okModal && appHost) { appHost.appendChild(okModal); } else if (okModal) { okModal.classList.add('is-fixed'); }
  var confirmEl = document.getElementById('ew-confirm');       // hanya ada di End Work
  if (confirmEl && appHost) { appHost.appendChild(confirmEl); } else if (confirmEl) { confirmEl.classList.add('is-fixed'); }
  var submitLabel = document.getElementById('wk-submit-label');
  var baseLabel = submitLabel.textContent;
  var sending = false;
  var confirmed = false;     // End Work: sudah menjawab "Ya" pada dialog konfirmasi

  function showSuccess(d) {
    document.getElementById('wk-success-title').textContent = d.title || 'Berhasil';
    document.getElementById('wk-success-body').textContent  = d.body || '';
    var go = document.getElementById('wk-success-go');
    if (d.next) { go.setAttribute('href', d.next); }
    if (d.button) { go.textContent = d.button; }
    // Check-Out: halaman di belakang pop up langsung ikut berubah (Riwayat Check-In kosong, tombol terkunci)
    if (isOut) {
      var hb = document.getElementById('co-hist-body');
      if (hb) { hb.innerHTML = '<div class="co-hist-empty">Belum ada riwayat check-in hari ini</div>'; }
      hasCheckin = false;
      submitLabel.textContent = 'Lakukan Check-In Terlebih Dahulu';
      refresh();
    } else {
      submitLabel.textContent = baseLabel;
    }
    okModal.hidden = false;
    go.focus();
    document.dispatchEvent(new CustomEvent('amt:state'));
  }

  // End Work: ketuk "Akhiri Pekerjaan" -> dialog konfirmasi; "Ya" baru benar-benar mengirim, "Batal" menutup dialog.
  function openConfirm()  { if (confirmEl) { confirmEl.hidden = false; document.getElementById('ew-yes').focus(); document.dispatchEvent(new CustomEvent('amt:state')); } }
  function closeConfirm() { if (confirmEl) { confirmEl.hidden = true; document.dispatchEvent(new CustomEvent('amt:state')); } }
  if (confirmEl) {
    document.getElementById('ew-yes').addEventListener('click', function () {
      confirmed = true;
      closeConfirm();
      form.dispatchEvent(new Event('submit', { cancelable: true }));
    });
    document.getElementById('ew-no').addEventListener('click', closeConfirm);
    confirmEl.addEventListener('click', function (e) { if (e.target === confirmEl) { closeConfirm(); } });   // ketuk area gelap = Batal
  }

  form.addEventListener('submit', function (e) {
    if (!window.fetch || !window.FormData) { return; }   // peramban lama: kirim biasa (server mengalihkan ke beranda)
    e.preventDefault();
    if (sending || submit.disabled) { return; }
    if (isEnd && !confirmed) { openConfirm(); return; }  // End Work: tanya dulu
    sending = true;
    submit.disabled = true;
    submitLabel.textContent = 'Mengirim…';
    // PENTING: pakai getAttribute('action'), BUKAN form.action. Form ini punya <input name="action">
    // yang menimpa properti form.action (hasilnya elemen input, bukan alamat), sehingga POST nyasar ke
    // "/[object HTMLInputElement]" (404 di hosting) lalu halaman dimuat ulang = kembali ke form.
    fetch(form.getAttribute('action'), {
      method: 'POST', body: new FormData(form), credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).then(function (r) {
      // Server menjawab JSON bila sukses. Bila ditolak (mis. lokasi di luar jangkauan) server mengalihkan
      // kembali ke form -> jawabannya HTML -> muat ulang form seperti sebelumnya.
      if ((r.headers.get('Content-Type') || '').indexOf('application/json') === -1) { throw new Error('ditolak'); }
      return r.json();
    }).then(function (d) {
      if (!d || !d.ok) { throw new Error('ditolak'); }
      showSuccess(d);
    }).catch(function () {
      window.location.reload();
    });
  });

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
  // data-pin = "1" hanya bila lokasi SUDAH sesuai DAN peta (pin) selesai dimuat.
  // Tutorial AMT membaca atribut ini supaya menunggu pin benar-benar tampil
  // sebelum pindah ke langkah berikutnya. Bila peta gagal memuat, setelah
  // PIN_FALLBACK_MS tetap dianggap siap agar tutorial tidak macet.
  var PIN_FALLBACK_MS = 6000;
  var mapToken = 0, pinTimer = null;
  function setPos(p) {
    var d = Math.round(distance(p, BASE));
    inRange = d <= RADIUS;
    latEl.value = p.lat.toFixed(6);
    lngEl.value = p.lng.toFixed(6);

    // Mulai dari "belum siap"; baru jadi siap saat peta selesai dimuat.
    var token = ++mapToken;
    clearTimeout(pinTimer);
    form.setAttribute('data-pin', '0');
    function pinReady() {
      if (token !== mapToken || !inRange) return;   // hasil lama / lokasi belum sesuai
      clearTimeout(pinTimer);
      mapEl.onload = null;
      form.setAttribute('data-pin', '1');
      document.dispatchEvent(new CustomEvent('amt:state'));
    }
    mapEl.onload = inRange ? pinReady : null;
    if (inRange) pinTimer = setTimeout(pinReady, PIN_FALLBACK_MS);

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