(function (w, d) {
    'use strict';

    var loForm = d.getElementById('pblForm');
    if (loForm) {
        var allBox   = d.getElementById('pblAll');
        var cards    = [].slice.call(loForm.querySelectorAll('[data-pbl-card]'));
        var startBtn = d.getElementById('pblStart');
        var sendBtn  = d.getElementById('pblSend');
        var sendMod  = d.getElementById('pblModal');
        var yesBtn   = d.getElementById('pblYes');
        var noBtn    = d.getElementById('pblNo');
        var lastFoc  = null;

        function boxOf(card) { return card.querySelector('input[type="checkbox"]'); }
        function selectable() {
            return cards.filter(function (c) { return !boxOf(c).disabled; });
        }

        function syncLo() {
            var pick = selectable();
            var checked = pick.filter(function (c) { return boxOf(c).checked; });
            var hasDraft = checked.some(function (c) { return c.getAttribute('data-status') === 'draft'; });

            cards.forEach(function (c) { c.classList.toggle('is-selected', boxOf(c).checked); });
            if (allBox) allBox.checked = pick.length > 0 && checked.length === pick.length;
            if (startBtn) startBtn.disabled = checked.length === 0;
            if (sendBtn)  sendBtn.disabled  = !hasDraft;
        }

        loForm.addEventListener('change', function (e) {
            if (e.target === allBox) {
                selectable().forEach(function (c) { boxOf(c).checked = allBox.checked; });
            }
            syncLo();
        });

        if (sendMod) {
            d.body.appendChild(sendMod);

            var onModKey = function (e) { if (e.key === 'Escape') closeSend(); };
            var openSend = function () {
                if (!sendBtn || sendBtn.disabled) return;
                lastFoc = d.activeElement;
                sendMod.hidden = false;
                d.addEventListener('keydown', onModKey);
                if (yesBtn) yesBtn.focus();
            };
            var closeSend = function () {
                sendMod.hidden = true;
                d.removeEventListener('keydown', onModKey);
                if (lastFoc && lastFoc.focus) lastFoc.focus();
            };

            if (sendBtn) sendBtn.addEventListener('click', openSend);
            if (noBtn) noBtn.addEventListener('click', closeSend);
            sendMod.addEventListener('click', function (e) { if (e.target === sendMod) closeSend(); });
            if (yesBtn) {
                yesBtn.addEventListener('click', function () {

                    setTimeout(function () { yesBtn.disabled = true; }, 0);
                });
            }
        }

        var toast = d.getElementById('pblToast');
        if (toast) {
            d.body.appendChild(toast);
            w.requestAnimationFrame(function () { toast.classList.add('is-visible'); });
            setTimeout(function () {
                toast.classList.remove('is-visible');
                toast.classList.add('is-leaving');
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }, 4000);
        }

        syncLo();
        return;
    }

    var form = d.getElementById('pbkForm');
    if (!form) return;

    var type    = form.getAttribute('data-type');
    var next    = d.getElementById('pbkNext');
    var errorEl = d.getElementById('pbkError');
    var sending = false;

    function answered(name) {
        return !!form.querySelector('input[name="' + name + '"]:checked');
    }
    function isComplete() {
        if (type === 'qr')   return form.getAttribute('data-qr') === '1';
        if (type === 'dual') return answered('a') && answered('b');
        return answered('a');
    }
    function refresh() {
        var ok = isComplete();
        next.classList.toggle('is-locked', !ok);
        next.setAttribute('aria-disabled', ok ? 'false' : 'true');
        if (ok && errorEl) errorEl.hidden = true;
    }
    form.addEventListener('change', refresh);

    form.addEventListener('submit', function (e) {
        var nav = e.submitter ? e.submitter.value : 'next';
        if (sending) { e.preventDefault(); return; }

        if (nav === 'next' && !isComplete()) {
            e.preventDefault();
            if (errorEl) errorEl.hidden = false;
            return;
        }
        sending = true;
    });

    var photoBox = d.querySelector('[data-pbk-photo]');
    if (photoBox && w.AmtCamera) {
        var photoInput = d.getElementById('pbkPhotoData');
        var thumb = photoBox.querySelector('[data-pbk-thumb]');
        var cam = w.AmtCamera.create({
            facing: 'environment',
            title: 'Foto Bukti',
            onSave: function (dataUrl) {
                photoInput.value = dataUrl;
                thumb.src = dataUrl;
                photoBox.classList.add('is-taken');
            }
        });
        photoBox.querySelectorAll('[data-pbk-shoot]').forEach(function (b) {
            b.addEventListener('click', function () { cam.open(); });
        });
    }

    function renderQr(el) {
        if (!w.qrcode) return;
        var q = w.qrcode(0, 'M');
        q.addData(el.getAttribute('data-pbk-qr'));
        q.make();
        el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    }
    d.querySelectorAll('[data-pbk-qr]').forEach(renderQr);

    var frames = [].slice.call(d.querySelectorAll('[data-pbk-frame]'));
    var qrEl   = d.querySelector('[data-pbk-exp]');
    var expAt  = qrEl ? parseInt(qrEl.getAttribute('data-pbk-exp'), 10) * 1000 : null;
    var skew   = qrEl ? parseInt(qrEl.getAttribute('data-pbk-now'), 10) * 1000 - Date.now() : 0;

    function expired() { return expAt !== null && Date.now() + skew >= expAt; }
    function tick() {
        if (!expired()) return false;
        frames.forEach(function (f) { f.classList.add('is-expired'); });
        return true;
    }
    if (expAt !== null && !tick()) {
        var timer = setInterval(function () { if (tick()) clearInterval(timer); }, 1000);
    }

    var modal = d.getElementById('pbkQrModal');
    if (modal) {
        d.body.appendChild(modal);

        var closeBtn  = d.getElementById('pbkQrClose');
        var lastFocus = null;

        function onKey(e) { if (e.key === 'Escape') closeModal(); }
        function openModal() {
            lastFocus = d.activeElement;
            tick();
            modal.hidden = false;
            d.addEventListener('keydown', onKey);
            if (closeBtn) closeBtn.focus();
        }
        function closeModal() {
            modal.hidden = true;
            d.removeEventListener('keydown', onKey);
            if (w.history && w.history.replaceState && /[?&]qr=1/.test(w.location.search)) {
                w.history.replaceState(null, '', w.location.search.replace(/([?&])qr=1&?/, '$1').replace(/[?&]$/, '') + w.location.hash);
            }
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        var fab = d.getElementById('pbkQrFab');
        if (fab) fab.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        if (modal.getAttribute('data-open') === '1') openModal();
    }

    refresh();
})(window, document);