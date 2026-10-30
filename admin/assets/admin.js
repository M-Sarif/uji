(function () {
    'use strict';

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
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.className = 'copy-helper';
        document.body.appendChild(ta);
        ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        if (ok) { done(); } else { window.prompt('Salin token ini (Ctrl+C):', text); }
    }

    function flash(btn, text) {
        var old = btn.getAttribute('data-label') || btn.textContent;
        btn.setAttribute('data-label', old);
        btn.textContent = text;
        setTimeout(function () { btn.textContent = old; }, 1800);
    }

    function revealAndCopy(btn) {
        var table = btn.closest('table');
        var csrf = document.querySelector('input[name="_csrf"]');
        if (!table || !csrf || btn.disabled) { return; }
        var fd = new FormData();
        fd.append('action', 'reveal');
        fd.append('id', btn.getAttribute('data-reveal'));
        fd.append('_csrf', csrf.value);
        btn.disabled = true;
        fetch(table.getAttribute('data-reveal-url'), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            return r.json().then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
            btn.disabled = false;
            if (res.ok && res.d && res.d.token) {
                copyText(res.d.token, btn);
            } else {
                flash(btn, 'Gagal');
                window.alert((res.d && res.d.error) || 'Token tidak bisa disalin.');
            }
        }).catch(function () {
            btn.disabled = false;
            flash(btn, 'Gagal');
        });
    }

    document.addEventListener('click', function (ev) {
        var rev = ev.target.closest ? ev.target.closest('[data-reveal]') : null;
        if (rev) { revealAndCopy(rev); return; }
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