/* OneFIS - AMT - amt-performance.js
 * Layar Performance: (1) ganti Periode langsung memuat ulang, (2) tombol "?" / "i" membuka pop up
 * informasi. Tidak ada tutorial terpandu di layar ini. */
(function () {
    'use strict';

    // Periode: kirim form begitu bulan diganti
    var sel = document.querySelector('[data-amtp-period]');
    if (sel && sel.form) {
        sel.addEventListener('change', function () { sel.form.submit(); });
    }

    // Pop up dipindah ke .app-container (wadah aplikasi) supaya menutup seluruh layar dan tidak ikut ter-scroll
    var host = document.querySelector('.app-container') || document.body;
    var modals = {};
    Array.prototype.forEach.call(document.querySelectorAll('.amtp-modal'), function (m) {
        modals[m.id.replace('amtpModal-', '')] = m;
        host.appendChild(m);
    });

    var lastFocus = null;
    var openId = null;

    function open(id) {
        var m = modals[id];
        if (!m) { return; }
        lastFocus = document.activeElement;
        m.hidden = false;
        openId = id;
        var ok = m.querySelector('.amtp-modal-ok');
        if (ok) { ok.focus(); }
    }
    function close() {
        if (!openId) { return; }
        modals[openId].hidden = true;
        openId = null;
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
    }

    document.addEventListener('click', function (e) {
        var o = e.target.closest ? e.target.closest('[data-amtp-open]') : null;
        if (o) { open(o.getAttribute('data-amtp-open')); return; }
        if (e.target.closest && e.target.closest('[data-amtp-close]')) { close(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { close(); }
    });
})();