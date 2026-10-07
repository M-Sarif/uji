/* ============================================================
   OneFIS - AMT - camera.js
   Layar "Foto Verifikasi": kamera layar penuh dengan tombol
   Foto -> (Ambil Ulang | Simpan Foto).

   Pemakaian:
     var cam = AmtCamera.create({
         facing: 'user',                 // 'user' = depan, 'environment' = belakang
         title:  'Foto Verifikasi',
         maxSize: 1024,                  // opsional: sisi terpanjang hasil foto (px)
         switchable: true,               // opsional: tombol "Kamera Depan" / "Kamera Belakang" untuk ganti kamera
                                         // (dipakai layar "Foto Bukti" Laporkan Kendala; Foto tampil biru)
         onSave: function (dataUrl) { ... }   // dipanggil saat "Simpan Foto"
     });
     cam.open();   cam.close();

   Catatan: getUserMedia hanya jalan di HTTPS atau localhost. Bila
   kamera tidak tersedia / izin ditolak, tombol "Foto" otomatis
   membuka kamera bawaan HP lewat <input type="file" capture>.
   ============================================================ */
(function (w, d) {
    'use strict';

    var ICON_BACK = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>';
    var ICON_CAM  = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
    var ICON_REDO = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>';
    var ICON_FLIP = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13a3 3 0 0 1 5.2-2M15 13a3 3 0 0 1-5.2 2M14.2 9.6v1.6h-1.6M9.8 16.4v-1.6h1.6"/></svg>';
    var ICON_SAVE = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8M7 3v5h8"/></svg>';

    var MAX_SIZE = 1024;   // sisi terpanjang hasil foto (px)
    var QUALITY  = 0.85;   // kualitas JPEG

    function create(opts) {
        opts = opts || {};
        var facing = opts.facing || 'user';
        var mirror = facing === 'user';
        var switchable = !!opts.switchable;
        var onSave = typeof opts.onSave === 'function' ? opts.onSave : function () {};
        var maxSize = opts.maxSize || MAX_SIZE;   // sisi terpanjang hasil foto (px); dokumen perlu lebih besar

        var stream = null;
        var captured = null;      // dataURL foto yang sedang ditinjau
        var fallback = false;     // true = pakai input file (kamera bawaan HP)
        var token = 0;            // membatalkan permintaan kamera yang sudah usang

        /* ---------- Markup ---------- */
        var root = d.createElement('div');
        root.className = 'amtcam';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-label', opts.title || 'Foto Verifikasi');
        root.innerHTML =
            '<div class="amtcam-header">' +
                '<button type="button" class="amtcam-back" aria-label="Kembali">' + ICON_BACK + '</button>' +
                '<div class="amtcam-title"></div>' +
            '</div>' +
            '<div class="amtcam-stage">' +
                '<video class="amtcam-video' + (mirror ? ' is-mirror' : '') + '" autoplay playsinline muted></video>' +
                '<img class="amtcam-shot" alt="Hasil foto" hidden>' +
                '<div class="amtcam-msg"></div>' +
                '<div class="amtcam-flash"></div>' +
                '<div class="amtcam-actions">' +
                    (switchable ? '<button type="button" class="amtcam-btn" data-act="flip">' + ICON_FLIP + '<span></span></button>' : '') +
                    '<button type="button" class="amtcam-btn' + (switchable ? ' is-primary' : '') + '" data-act="shoot">' + ICON_CAM + '<span>Foto</span></button>' +
                    '<button type="button" class="amtcam-btn" data-act="retake" hidden>' + ICON_REDO + '<span>Ambil Ulang</span></button>' +
                    '<button type="button" class="amtcam-btn is-primary" data-act="save" hidden>' + ICON_SAVE + '<span>Simpan Foto</span></button>' +
                '</div>' +
            '</div>' +
            '<input type="file" accept="image/*" capture="' + (facing === 'user' ? 'user' : 'environment') + '" hidden>';

        root.querySelector('.amtcam-title').textContent = opts.title || 'Foto Verifikasi';

        var video   = root.querySelector('video');
        var shot    = root.querySelector('.amtcam-shot');
        var msg     = root.querySelector('.amtcam-msg');
        var flash   = root.querySelector('.amtcam-flash');
        var input   = root.querySelector('input[type=file]');
        var btnShoot  = root.querySelector('[data-act=shoot]');
        var btnRetake = root.querySelector('[data-act=retake]');
        var btnSave   = root.querySelector('[data-act=save]');
        var btnFlip   = root.querySelector('[data-act=flip]');   // null bila tidak switchable

        // Label tombol ganti kamera = kamera yang AKAN dipakai bila diketuk
        function flipLabel() {
            if (btnFlip) { btnFlip.querySelector('span').textContent = facing === 'user' ? 'Kamera Belakang' : 'Kamera Depan'; }
        }
        flipLabel();

        var host = d.querySelector('.app-container');
        if (host) { host.appendChild(root); } else { root.classList.add('is-fixed'); d.body.appendChild(root); }

        /* ---------- Bantuan tampilan ---------- */
        function showMsg(html) { msg.innerHTML = html || ''; msg.classList.toggle('is-on', !!html); }

        function setMode(mode) {   // 'live' | 'review'
            var review = mode === 'review';
            video.hidden = review || fallback;
            shot.hidden = !review;
            btnShoot.hidden = review;
            if (btnFlip) { btnFlip.hidden = review; }
            btnRetake.hidden = !review;
            btnSave.hidden = !review;
        }

        function stopStream() {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
            video.srcObject = null;
        }

        function startStream() {
            var my = ++token;
            captured = null;
            setMode('live');
            btnShoot.disabled = true;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                useFallback('Kamera langsung tidak tersedia di browser ini.<br>Tekan <b>Foto</b> untuk membuka kamera HP.');
                return;
            }
            showMsg('<div class="amtcam-spin"></div>Membuka kamera...');

            navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facing }, width: { ideal: 1280 }, height: { ideal: 1280 } },
                audio: false
            }).then(function (s) {
                if (my !== token) { s.getTracks().forEach(function (t) { t.stop(); }); return; }
                fallback = false;
                stream = s;
                video.srcObject = s;
                var p = video.play();
                if (p && p.catch) { p.catch(function () {}); }
                showMsg('');
                btnShoot.disabled = false;
            }).catch(function (err) {
                if (my !== token) { return; }
                var denied = err && (err.name === 'NotAllowedError' || err.name === 'SecurityError');
                useFallback((denied ? 'Izin kamera ditolak.' : 'Kamera tidak dapat dibuka.') +
                    '<br>Tekan <b>Foto</b> untuk memakai kamera HP.');
            });
        }

        function useFallback(text) {
            fallback = true;
            video.hidden = true;
            showMsg(text);
            btnShoot.disabled = false;
        }

        /* ---------- Ambil foto ---------- */
        function fireFlash() {
            flash.classList.remove('fade'); flash.classList.add('fire');
            setTimeout(function () { flash.classList.remove('fire'); flash.classList.add('fade'); }, 90);
        }

        function review(dataUrl) {
            captured = dataUrl;
            shot.src = dataUrl;
            stopStream();
            showMsg('');
            setMode('review');
        }

        function shoot() {
            if (fallback) { input.value = ''; input.click(); return; }
            if (!stream || !video.videoWidth) { return; }

            var vw = video.videoWidth, vh = video.videoHeight;
            var scale = Math.min(1, maxSize / Math.max(vw, vh));
            var c = d.createElement('canvas');
            c.width = Math.round(vw * scale);
            c.height = Math.round(vh * scale);
            var ctx = c.getContext('2d');
            if (mirror) { ctx.translate(c.width, 0); ctx.scale(-1, 1); }  // samakan dengan pratinjau
            ctx.drawImage(video, 0, 0, c.width, c.height);

            fireFlash();
            review(c.toDataURL('image/jpeg', QUALITY));
        }

        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) { return; }
            var r = new FileReader();
            r.onload = function () { review(String(r.result)); };
            r.readAsDataURL(f);
        });

        /* ---------- Tombol ---------- */
        btnShoot.addEventListener('click', shoot);
        if (btnFlip) {
            btnFlip.addEventListener('click', function () {
                facing = facing === 'user' ? 'environment' : 'user';
                mirror = facing === 'user';
                video.classList.toggle('is-mirror', mirror);
                input.setAttribute('capture', facing === 'user' ? 'user' : 'environment');
                flipLabel();
                stopStream();
                startStream();
            });
        }
        btnRetake.addEventListener('click', startStream);
        btnSave.addEventListener('click', function () {
            if (!captured) { return; }
            var data = captured;
            close();
            onSave(data);
        });
        root.querySelector('.amtcam-back').addEventListener('click', close);

        /* ---------- Buka / tutup ---------- */
        function open() {
            root.classList.add('is-open');
            startStream();
        }
        function close() {
            token++;
            stopStream();
            captured = null;
            root.classList.remove('is-open');
        }

        // Lepas kamera bila halaman disembunyikan / ditinggalkan
        d.addEventListener('visibilitychange', function () {
            if (d.hidden && root.classList.contains('is-open') && !captured) { stopStream(); }
            else if (!d.hidden && root.classList.contains('is-open') && !captured && !stream && !fallback) { startStream(); }
        });
        w.addEventListener('pagehide', stopStream);

        return { open: open, close: close };
    }

    w.AmtCamera = { create: create };
})(window, document);