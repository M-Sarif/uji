/* ============================================================
   OneFIS - AMT - surat-jalan.js
   Layar "Surat Jalan" (roles/amt/views/surat_jalan.php):
   - tiap Nomor LO: ketuk SELURUH kartu (Ambil Foto) -> kamera BELAKANG layar penuh (camera.js)
   - hasil foto tampil di kartu + "Lihat Foto" (pop up, bisa Ambil Ulang)
   - tombol "Simpan Foto" aktif bila SEMUA LO sudah punya foto
   File ini HANYA berisi JavaScript.
   ============================================================ */
(function (d) {
  'use strict';

  var form  = d.getElementById('sjForm');
  if (!form || !window.AmtCamera) { return; }

  var save  = d.getElementById('sjSave');
  var items = [].slice.call(form.querySelectorAll('.sj-item'));
  var current = null;                      // kartu LO yang sedang difoto

  /* ---------- Kamera belakang ---------- */
  var cam = AmtCamera.create({
    facing: 'environment',                 // kamera belakang di HP
    title: 'Foto Surat Jalan',
    maxSize: 1600,                         // dokumen: butuh resolusi lebih tinggi agar tulisan terbaca
    onSave: function (dataUrl) {
      if (current) { setPhoto(current, dataUrl); }
      current = null;
    }
  });

  function open(item) { current = item; cam.open(); }

  /* ---------- Tampilan kartu ---------- */
  function setPhoto(item, dataUrl) {
    item.querySelector('input[type=hidden]').value = dataUrl;
    item.querySelector('.sj-thumb').src = dataUrl;
    item.querySelector('.sj-empty-state').hidden = true;
    item.querySelector('.sj-photo-state').hidden = false;
    refresh();
  }

  function refresh() {
    var filled = 0;
    items.forEach(function (it) {
      var has = it.querySelector('input[type=hidden]').value !== '';
      it.classList.toggle('has-photo', has);   // dipakai tutorial untuk menyorot kartu yang belum difoto
      if (has) { filled++; }
    });
    var all = filled === items.length;
    save.disabled = !all;
    form.setAttribute('data-filled', String(filled));
    form.setAttribute('data-total', String(items.length));
    form.setAttribute('data-complete', all ? '1' : '0');
  }

  /* ---------- Pop up Lihat Foto ---------- */
  var modal = d.getElementById('sjModal');
  var mImg  = d.getElementById('sjModalImg');
  var viewing = null;

  function openModal(item) {
    viewing = item;
    mImg.src = item.querySelector('input[type=hidden]').value;
    modal.hidden = false;
  }
  function closeModal() { modal.hidden = true; mImg.removeAttribute('src'); viewing = null; }

  d.getElementById('sjModalClose').addEventListener('click', closeModal);
  d.getElementById('sjModalRetake').addEventListener('click', function () {
    var it = viewing; closeModal(); if (it) { open(it); }
  });
  modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });

  /* ---------- Tombol di kartu ---------- */
  items.forEach(function (item) {
    // SELURUH kartu foto (.sj-card) adalah tombol "Ambil Foto" selama kartu ini belum berisi foto: ketuk di mana saja
    // pada kartu = buka kamera. Tombol "Ambil Foto" di dalamnya ikut bekerja lewat klik yang naik ke kartu
    // (jadi akses keyboard tetap jalan). Setelah ada foto, kartu tidak lagi membuka kamera: pakai "Lihat Foto" -> "Ambil Ulang".
    var emptyState = item.querySelector('.sj-empty-state');
    item.querySelector('.sj-card').addEventListener('click', function () {
      if (!emptyState.hidden) { open(item); }
    });
    item.querySelector('[data-act=view]').addEventListener('click', function () { openModal(item); });

    // ikon (i): tampilkan / sembunyikan nama order LO
    var info = item.querySelector('.sj-info'), tip = item.querySelector('.sj-tip');
    info.addEventListener('click', function () {
      tip.hidden = !tip.hidden;
      info.setAttribute('aria-expanded', tip.hidden ? 'false' : 'true');
    });
  });

  /* ---------- Kirim ---------- */
  form.addEventListener('submit', function (e) {
    if (save.disabled) { e.preventDefault(); return; }
    save.disabled = true;                  // cegah kirim ganda
    save.querySelector('span').textContent = 'Menyimpan…';
  });

  refresh();
})(document);