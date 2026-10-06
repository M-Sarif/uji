/* ============================================================
   OneFIS - AMT - amt-rating.js
   Layar "Beri Penilaian" (roles/amt/views/amt_rating.php):
   - teks apresiasi muncul begitu bintang diketuk ("Luar Biasa!", dst)
   - tombol "Kirim" aktif bila nama petugas terisi + semua bintang dipilih
   File ini HANYA berisi JavaScript.
   ============================================================ */
(function (d) {
  'use strict';

  var form = d.getElementById('rtForm');
  if (!form) { return; }

  var send   = d.getElementById('rtSend');
  var name   = d.getElementById('rtOfficer');
  var cards  = [].slice.call(form.querySelectorAll('.rt-card'));
  var titles = window.AMT_RATING_TITLES || {};

  function complete() {
    if (name.value.replace(/\s+/g, '') === '') { return false; }
    return cards.every(function (c) { return c.querySelector('.rt-star-in:checked') !== null; });
  }

  function refresh() {
    var ok = complete();
    var rated = cards.filter(function (c) { return c.querySelector('.rt-star-in:checked') !== null; }).length;
    send.disabled = !ok;
    // Penanda untuk tutorial (roles/amt/includes/tutorial.php): nama terisi, jumlah kartu berbintang
    form.setAttribute('data-named', name.value.replace(/\s+/g, '') === '' ? '0' : '1');
    form.setAttribute('data-rated', String(rated));
    form.setAttribute('data-allrated', rated === cards.length ? '1' : '0');
    form.setAttribute('data-complete', ok ? '1' : '0');
  }

  cards.forEach(function (card) {
    card.addEventListener('change', function (e) {
      if (!e.target.classList.contains('rt-star-in')) { return; }
      var feed = card.querySelector('.rt-feed');
      card.querySelector('.rt-feed-title').textContent = titles[e.target.value] || '';
      card.querySelector('.rt-stars + .rt-hint').hidden = true;   // sembunyikan "Ketuk bintang ..."
      feed.hidden = false;
      card.classList.add('is-rated');
      refresh();
    });
  });

  name.addEventListener('input', refresh);

  form.addEventListener('submit', function (e) {
    if (!complete()) { e.preventDefault(); return; }
    send.disabled = true;                      // cegah kirim ganda
    send.querySelector('span').textContent = 'Mengirim…';
  });

  refresh();
})(document);