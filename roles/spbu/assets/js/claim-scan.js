(function () {
    'use strict';

    var video   = document.getElementById('scanVideo');
    var canvas  = document.getElementById('scanCanvas');
    var msg     = document.getElementById('scanMsg');
    var form    = document.getElementById('qrForm');
    var qrInput = document.getElementById('qrValue');
    var galBtn  = document.getElementById('scanGallery');
    var galFile = document.getElementById('galleryInput');
    if (!video || !canvas || !form || !qrInput || typeof jsQR !== 'function') { return; }

    var ctx      = canvas.getContext('2d', { willReadFrequently: true });
    var stream   = null;
    var rafId    = 0;
    var scanning = false;
    var sent     = false;
    var simulating = false;
    var frame    = document.getElementById('scanFrame');
    var simBox   = document.getElementById('scanSim');
    var simQr    = frame ? (frame.getAttribute('data-sim-qr') || '') : '';

    function setMsg(text, isError) {
        if (!msg) { return; }
        msg.textContent = text;
        msg.classList.toggle('is-error', !!isError);
    }

    function stopCamera() {
        scanning = false;
        if (rafId) { cancelAnimationFrame(rafId); rafId = 0; }
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        video.srcObject = null;
    }

        function submitQr(value) {
        if (sent) { return; }
        sent = true;
        stopCamera();
        setMsg('QR Code terbaca. Memeriksa…', false);
        qrInput.value = value;
        form.submit();
    }

    function tick() {
        if (!scanning) { return; }
        if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth) {
            var vw = video.videoWidth, vh = video.videoHeight;
            var k  = Math.min(1, 640 / Math.max(vw, vh));
            var w  = Math.round(vw * k), h = Math.round(vh * k);
            if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; }
            ctx.drawImage(video, 0, 0, w, h);
            var img  = ctx.getImageData(0, 0, w, h);
            var code = jsQR(img.data, w, h, { inversionAttempts: 'dontInvert' });
            if (code && code.data) { submitQr(code.data); return; }
        }
        rafId = requestAnimationFrame(tick);
    }

    function cameraError(err) {
        var name = err && err.name;
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            setMsg('Izin kamera ditolak. Izinkan akses kamera di pengaturan browser, atau pilih gambar QR dari galeri (ikon kanan atas).', true);
        } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            setMsg('Kamera tidak ditemukan. Pilih gambar QR dari galeri (ikon kanan atas).', true);
        } else {
            setMsg('Kamera tidak bisa dibuka. Pilih gambar QR dari galeri (ikon kanan atas).', true);
        }
    }

    function startCamera() {
        if (sent || simulating) { return; }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setMsg('Kamera hanya bisa dipakai lewat HTTPS atau localhost. Pilih gambar QR dari galeri (ikon kanan atas).', true);
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .then(function (s) {
                stream = s;
                video.srcObject = s;
                return video.play();
            })
            .then(function () {
                scanning = true;
                tick();
            })
            .catch(cameraError);
    }

        function decodeImageFile(file) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
            var k = Math.min(1, 1280 / Math.max(img.naturalWidth, img.naturalHeight));
            var w = Math.max(1, Math.round(img.naturalWidth * k));
            var h = Math.max(1, Math.round(img.naturalHeight * k));
            canvas.width = w; canvas.height = h;
            ctx.drawImage(img, 0, 0, w, h);
            URL.revokeObjectURL(url);
            var data = ctx.getImageData(0, 0, w, h);
            var code = jsQR(data.data, w, h, { inversionAttempts: 'attemptBoth' });
            if (code && code.data) {
                submitQr(code.data);
            } else {
                setMsg('QR Code tidak terbaca pada gambar. Coba gambar lain yang lebih jelas.', true);
            }
        };
        img.onerror = function () {
            URL.revokeObjectURL(url);
            setMsg('Gambar tidak bisa dibuka. Pilih file gambar lain.', true);
        };
        img.src = url;
    }

    if (galBtn && galFile) {
        galBtn.addEventListener('click', function () { galFile.click(); });
        galFile.addEventListener('change', function () {
            var f = galFile.files && galFile.files[0];
            galFile.value = '';
            if (f) { decodeImageFile(f); }
        });
    }

    window.addEventListener('pagehide', stopCamera);
    window.addEventListener('pageshow', function (e) { if (e.persisted && !stream) { startCamera(); } });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stopCamera(); }
        else if (!stream && !sent) { startCamera(); }
    });

    function startSimulation() {
        simulating = true;
        video.hidden = true;
        var stage   = document.querySelector('.scan-stage');
        var card    = document.getElementById('scanSimCard');
        var status  = document.getElementById('scanSimStatus');
        var info    = document.getElementById('scanSimInfo');
        var timer   = document.getElementById('scanSimTimer');
        var expired = document.getElementById('scanSimExpired');
        if (stage) { stage.classList.add('is-sim'); }
        if (galBtn) { galBtn.hidden = true; }
        if (msg) { msg.hidden = true; }
        if (card && window.qrcode) {
            var q = window.qrcode(0, 'M');
            q.addData(simQr);
            q.make();
            card.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        }
        if (simBox) { simBox.hidden = false; }
        if (info) { info.hidden = false; }

        var expAt = parseInt(simQr.split('|')[4], 10) * 1000;
        var finished = false;
        var armed = false;

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function show(s) { if (timer) { timer.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); } }
        function expire() {
            finished = true;
            if (stage) { stage.classList.add('is-expired'); }
            if (status) { status.textContent = 'Waktu habis'; }
            if (expired) { expired.hidden = false; }
        }
        function scan() {
            if (finished) { return; }
            finished = true;
            if (stage) { stage.classList.add('is-found'); }
            if (status) { status.textContent = 'Kode QR terbaca ✓'; }
            setTimeout(function () { submitQr(simQr); }, 700);
        }
        function tick() {
            if (finished) { return; }
            var left = Math.max(0, Math.ceil((expAt - Date.now()) / 1000));
            show(left);
            if (left <= 0) { expire(); }
        }
        function arm(ms) {
            if (armed || finished) { return; }
            armed = true;
            setTimeout(scan, ms);
        }

        show(Math.max(0, Math.ceil((expAt - Date.now()) / 1000)));
        setInterval(tick, 250);

        document.addEventListener('spbu:tour-done', function (e) {
            if (!e.detail || String(e.detail.key).indexOf('claim_scan') !== 0) { return; }
            arm(e.detail.skipped ? 3000 : 4000);
        });
    }

    function decide() {
        var tour = window.OneFISTour;
        if (simQr && tour && tour.isActive && tour.isActive()) { startSimulation(); return; }
        startCamera();
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', decide); } else { decide(); }
})();