/* ============================================================
   OneFIS - AMT - checkin.js
   Halaman Check-In (roles/amt/views/checkin.php):
   - pindah tab Verifikasi <-> Riwayat Check-In
   - pop up "Lihat Foto" pada riwayat
   File ini HANYA berisi JavaScript.
   ============================================================ */
(function () {
  var tabs  = document.querySelectorAll('[data-ci-tab]');
  var panes = { verifikasi: document.getElementById('ci-pane-verifikasi'),
                riwayat:    document.getElementById('ci-pane-riwayat') };

  function show(name) {
    Object.keys(panes).forEach(function (k) { panes[k].hidden = (k !== name); });
    tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-ci-tab') === name); });
  }
  tabs.forEach(function (t) {
    t.addEventListener('click', function () { show(t.getAttribute('data-ci-tab')); });
  });

  // Lihat Foto
  var modal = document.getElementById('ci-modal');
  var img   = document.getElementById('ci-modal-img');
  document.querySelectorAll('[data-ci-photo]').forEach(function (b) {
    b.addEventListener('click', function () {
      img.src = document.getElementById('ci-photo-' + b.getAttribute('data-ci-photo')).src;
      modal.hidden = false;
    });
  });
  function closeModal() { modal.hidden = true; img.removeAttribute('src'); }
  document.getElementById('ci-modal-close').addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
})();