/* OneFIS Admin - skrip kecil (konfirmasi hapus, salin token). Berkas eksternal karena CSP melarang skrip inline.
 * Letak: admin/assets/admin.js */
(function () {
    'use strict';

    // Konfirmasi sebelum form dengan atribut data-confirm dikirim.
    document.addEventListener('submit', function (ev) {
        var msg = ev.target && ev.target.getAttribute && ev.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) { ev.preventDefault(); }
    });

    function copyText(text, btn) {
        var done = function () {
            if (!btn) { return; }
            var old = btn.textContent;
            btn.textContent = 'Tersalin';
            setTimeout(function () { btn.textContent = old; }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, function () {});
            return;
        }
        // Cadangan untuk http://localhost / jaringan lokal (clipboard API butuh HTTPS).
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.className = 'copy-helper';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(ta);
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-copy],[data-copy-all]') : null;
        if (!btn) { return; }
        if (btn.hasAttribute('data-copy')) {
            copyText(btn.getAttribute('data-copy'), btn);
            return;
        }
        var lines = [];
        document.querySelectorAll('.newlist li').forEach(function (li) {
            var lab = li.querySelector('.nl-label'), tok = li.querySelector('.nl-token');
            if (lab && tok) { lines.push(lab.textContent + ' : ' + tok.textContent); }
        });
        copyText(lines.join('\n'), btn);
    });
})();