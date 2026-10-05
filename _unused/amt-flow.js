/* Modal tutorial AMT: tampil setelah jeda, bisa hilang sendiri, dan lapor ke server. */
(function (w) {
    if (w.AmtFlow) return;

    function ack(key) {
        try {
            var body = new URLSearchParams();
            body.set('pti_action', 'flow_ack');
            body.set('key', key);
            fetch(w.location.href, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true });
        } catch (e) { /* abaikan; alur tetap jalan di sisi tampilan */ }
    }

    /**
     * show(el, { delay, autoHide, onClose })
     *  delay    : ms sebelum modal muncul
     *  autoHide : ms tampil sebelum hilang sendiri (0 = tidak hilang sendiri)
     *  beforeShow: dipanggil tepat sebelum tampil (dipakai untuk menempatkan petunjuk)
     *  onClose  : dipanggil sekali saat ditutup (Mengerti, Lewati, Esc, atau waktu habis)
     * Mengembalikan { close } supaya bisa ditutup dari luar.
     */
    function show(el, opts) {
        opts = opts || {};
        var closed = false, timer = null;

        function close() {
            if (closed) return;
            closed = true;
            clearTimeout(timer);
            document.removeEventListener('keydown', onKey);
            el.classList.remove('is-in');
            if (opts.onClose) opts.onClose();
        }
        function onKey(e) { if (e.key === 'Escape') close(); }

        var ok = el.querySelector('[data-amt-ok]');
        var skip = el.querySelector('[data-amt-skip]');
        if (ok) ok.addEventListener('click', close);
        if (skip) skip.addEventListener('click', close);

        setTimeout(function () {
            if (closed) return;
            if (opts.beforeShow) opts.beforeShow();
            el.classList.add('is-in');
            document.addEventListener('keydown', onKey);
            if (ok) ok.focus();
            if (opts.autoHide > 0) timer = setTimeout(close, opts.autoHide);
        }, opts.delay || 0);

        return { close: close };
    }

    w.AmtFlow = { show: show, ack: ack };
})(window);