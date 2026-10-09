/* ============================================================
   OneFIS - SPBU - claim-scan.js
   Layar "Scan QR AMT" (halaman claim_scan_qr).

   - Membuka kamera belakang (getUserMedia) dan membaca QR Code terus-menerus
     dengan pustaka jsQR (roles/spbu/assets/js/jsQR.js, lisensi Apache-2.0).
   - Begitu QR terbaca, isinya diisikan ke form tersembunyi #qrForm lalu dikirim
     (aksi "save_qr_amt"). Validasi isi QR dilakukan di server.
   - Ikon galeri (#scanGallery) = pilih gambar QR dari galeri bila kamera
     tidak bisa dipakai.

   Catatan: getUserMedia hanya jalan di HTTPS atau http://localhost. Di alamat
   lain (mis. http://192.168.x.x) kamera tidak aktif -> gunakan ikon galeri.
   ============================================================ */
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

    /* QR terbaca -> kirim ke server (hanya sekali) */
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
            var k  = Math.min(1, 640 / Math.max(vw, vh));   // kecilkan supaya cepat
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
        if (sent) { return; }
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

    /* ---------- Pilih gambar QR dari galeri ---------- */
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

    /* Matikan kamera saat halaman ditinggalkan / tab disembunyikan, nyalakan lagi saat kembali */
    window.addEventListener('pagehide', stopCamera);
    window.addEventListener('pageshow', function (e) { if (e.persisted && !stream) { startCamera(); } });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stopCamera(); }
        else if (!stream && !sent) { startCamera(); }
    });

    startCamera();
})();