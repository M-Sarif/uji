/* ============================================================
   OneFIS - AMT - amt-checklist.js
   Checklist Pra Bongkar BBM (views/amt/pra_bongkar.php):
   - tombol "Selanjutnya" / "Selesai" aktif setelah jawaban lengkap
   - kamera foto bukti (assets/amt/js/camera.js)
   - QR Code (assets/amt/js/qrcode.js) + hitung mundur masa berlaku
   - pop up QR Code (tombol "QR Code" melayang)
   File ini HANYA berisi JavaScript.
   ============================================================ */
(function (w, d) {
    'use strict';

    var form = d.getElementById('pbkForm');
    if (!form) return;

    var type    = form.getAttribute('data-type');
    var next    = d.getElementById('pbkNext');
    var errorEl = d.getElementById('pbkError');
    var qrCard  = d.getElementById('pbkQrCard');
    var sending = false;

    /* ---------- Kelengkapan langkah ---------- */
    function answered(name) {
        return !!form.querySelector('input[name="' + name + '"]:checked');
    }
    function isComplete() {
        if (type === 'qr')   return !!qrCard;                       // QR sudah dibuat (server merender kartunya)
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
        // "Selanjutnya"/"Selesai" ditahan di sini bila belum lengkap (server tetap memeriksa ulang)
        if (nav === 'next' && !isComplete()) {
            e.preventDefault();
            if (errorEl) errorEl.hidden = false;
            return;
        }
        sending = true;                                             // cegah kirim ganda
    });

    /* ---------- Foto bukti ---------- */
    var photoBox = d.querySelector('[data-pbk-photo]');
    if (photoBox && w.AmtCamera) {
        var photoInput = d.getElementById('pbkPhotoData');
        var thumb = photoBox.querySelector('[data-pbk-thumb]');
        var cam = w.AmtCamera.create({
            facing: 'environment',                                  // kamera belakang: foto kondisi di lapangan
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

    /* ---------- QR Code ---------- */
    function renderQr(el) {
        if (!w.qrcode) return;
        var q = w.qrcode(0, 'M');
        q.addData(el.getAttribute('data-pbk-qr'));
        q.make();
        el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    }
    d.querySelectorAll('[data-pbk-qr]').forEach(renderQr);

    // Masa berlaku: selisih jam server-HP dikoreksi memakai data-now dari server
    var expAt = null, skew = 0;
    if (qrCard) {
        expAt = parseInt(qrCard.getAttribute('data-exp'), 10) * 1000;
        skew  = parseInt(qrCard.getAttribute('data-now'), 10) * 1000 - Date.now();
    }
    var modal = d.getElementById('pbkQrModal');
    if (!expAt && modal) {
        var mq = modal.querySelector('[data-pbk-exp]');
        if (mq) { expAt = parseInt(mq.getAttribute('data-pbk-exp'), 10) * 1000; skew = 0; }
    }

    function expired() { return expAt !== null && Date.now() + skew >= expAt; }
    function tick() {
        if (!expired()) return false;
        if (qrCard) qrCard.classList.add('is-expired');
        if (modal) {
            var fr = modal.querySelector('.pbk-qrframe');
            if (fr) fr.classList.add('is-expired');
            var note = d.getElementById('pbkQrRegen');
            if (note) note.hidden = false;
        }
        return true;
    }
    if (expAt !== null && !tick()) {
        var timer = setInterval(function () { if (tick()) clearInterval(timer); }, 1000);
    }

    /* ---------- Pop up QR Code ---------- */
    var fab = d.getElementById('pbkQrFab');
    if (fab && modal) {
        d.body.appendChild(modal);                                  // di luar .content: tidak ikut tergulir / terpotong
        var lastFocus = null;
        var closeBtn = d.getElementById('pbkQrClose');

        function onKey(e) { if (e.key === 'Escape') closeModal(); }
        function openModal() {
            lastFocus = d.activeElement;
            tick();
            modal.hidden = false;
            d.addEventListener('keydown', onKey);
            closeBtn.focus();
        }
        function closeModal() {
            modal.hidden = true;
            d.removeEventListener('keydown', onKey);
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }
        fab.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    }

    refresh();
})(window, document);