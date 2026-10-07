/*
 * OneFIS - AMT - layar Laporkan Kendala (?screen=amt_kendala)
 * Letak: roles/amt/assets/js/amt-kendala.js
 *
 * - Jenis Kendala (select + tombol hapus pilihan)
 * - Estimasi Kendala: "Ya, Bisa Diprediksi" menampilkan kolom Estimasi Waktu; "Tidak Bisa Diprediksi" menyembunyikannya
 * - Estimasi Waktu: pop up pemilih jam : menit (geser / ketuk angka); "Terapkan" aktif bila durasi > 0
 * - Foto Bukti: kamera layar penuh (camera.js, bisa ganti kamera depan/belakang), "Hapus Foto"
 * - Justifikasi AMT (textarea + tombol hapus)
 * - Tombol "Kirim Laporan Kendala" aktif bila semua isian wajib lengkap
 *
 * Status form tampil sebagai atribut data-* pada #kdForm (dibaca tutorial; lihat amt_tour_kendala_steps()):
 *   data-jenis="1"  jenis kendala dipilih          data-bisa="ya|tidak"  pilihan Estimasi Kendala
 *   data-est="1"    estimasi waktu sudah diterapkan data-photo="1"       foto bukti tersimpan
 *   data-note="1"   justifikasi terisi             data-ready="1"        semua isian wajib lengkap
 * Pop up estimasi memakai class .is-open pada #kdModal saat tampil.
 */
(function () {
    'use strict';

    var form = document.getElementById('kdForm');
    if (!form) { return; }

    function $(id) { return document.getElementById(id); }
    function pad(n) { return (n < 10 ? '0' : '') + n; }

    var jenis = $('kdJenis'), jenisClear = $('kdJenisClear');
    var timeBlock = $('kdTimeBlock'), timeBtn = $('kdTime'), timeText = $('kdTimeText');
    var estH = $('kdEstH'), estM = $('kdEstM');
    var photoBtn = $('kdPhotoBtn'), photoDone = $('kdPhotoDone'), photoImg = $('kdPhotoImg'), photoDel = $('kdPhotoDel');
    var photoData = $('kdPhotoData');
    var note = $('kdNote'), noteClear = $('kdNoteClear');
    var send = $('kdSend');
    var modal = $('kdModal'), apply = $('kdApply'), cancel = $('kdCancel');

    var est = { set: false, h: 0, m: 0 };

    function bisa() {
        var r = form.querySelector('input[name="bisa"]:checked');
        return r ? r.value : 'ya';
    }

    /* ---------- status form ---------- */
    function refresh() {
        var hasJenis = jenis.value !== '';
        var hasNote  = note.value.trim() !== '';
        var hasPhoto = photoData.value !== '';
        var prediksi = bisa() === 'ya';
        var estOk    = prediksi && est.set;
        var ready    = hasJenis && hasPhoto && hasNote && (!prediksi || estOk);

        jenis.setAttribute('data-empty', hasJenis ? '0' : '1');
        jenisClear.hidden = !hasJenis;
        noteClear.hidden = note.value === '';
        timeBlock.hidden = !prediksi;

        form.setAttribute('data-jenis', hasJenis ? '1' : '0');
        form.setAttribute('data-bisa', prediksi ? 'ya' : 'tidak');
        form.setAttribute('data-est', estOk ? '1' : '0');
        form.setAttribute('data-photo', hasPhoto ? '1' : '0');
        form.setAttribute('data-note', hasNote ? '1' : '0');
        form.setAttribute('data-ready', ready ? '1' : '0');
        send.disabled = !ready;
        document.dispatchEvent(new CustomEvent('amt:state'));
    }

    /* ---------- jenis kendala ---------- */
    jenis.addEventListener('change', refresh);
    jenisClear.addEventListener('click', function () { jenis.value = ''; refresh(); jenis.focus(); });

    /* ---------- estimasi kendala ---------- */
    form.querySelectorAll('input[name="bisa"]').forEach(function (r) { r.addEventListener('change', refresh); });

    /* ---------- pemilih durasi (jam : menit) ---------- */
    var STEP_PX = 30;   // jarak geser (px) untuk berpindah satu angka

    function Wheel(el, onChange) {
        var max = parseInt(el.getAttribute('data-max'), 10) || 59;
        var val = 0, lastY = 0, acc = 0, dragging = false, dragged = false;

        function wrap(n) { var size = max + 1; return ((n % size) + size) % size; }

        function render() {
            var html = '';
            [-1, 0, 1].forEach(function (off) {
                html += '<button type="button" tabindex="-1" class="kd-wheel__row' + (off === 0 ? ' is-sel' : '') + '"' +
                        (off !== 0 ? ' data-off="' + off + '"' : '') + '>' + pad(wrap(val + off)) + '</button>';
            });
            el.innerHTML = html;
            el.setAttribute('aria-valuemin', '0');
            el.setAttribute('aria-valuemax', String(max));
            el.setAttribute('aria-valuenow', String(val));
        }
        function set(n, silent) {
            val = wrap(n);
            render();
            if (!silent) { onChange(val); }
        }

        el.addEventListener('pointerdown', function (e) {
            dragging = true; dragged = false; lastY = e.clientY; acc = 0;
            try { el.setPointerCapture(e.pointerId); } catch (x) {}
        });
        el.addEventListener('pointermove', function (e) {
            if (!dragging) { return; }
            acc += e.clientY - lastY;
            lastY = e.clientY;
            if (Math.abs(acc) > 6) { dragged = true; }
            while (acc >= STEP_PX)  { set(val - 1); acc -= STEP_PX; }   // geser ke bawah = angka sebelumnya
            while (acc <= -STEP_PX) { set(val + 1); acc += STEP_PX; }   // geser ke atas  = angka berikutnya
        });
        function end() { dragging = false; }
        el.addEventListener('pointerup', end);
        el.addEventListener('pointercancel', end);

        // ketuk angka di atas / bawah angka terpilih
        el.addEventListener('click', function (e) {
            if (dragged) { dragged = false; return; }
            var b = e.target.closest ? e.target.closest('[data-off]') : null;
            if (b) { set(val + parseInt(b.getAttribute('data-off'), 10)); }
        });
        el.addEventListener('wheel', function (e) {
            e.preventDefault();
            set(val + (e.deltaY > 0 ? 1 : -1));
        }, { passive: false });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp')   { e.preventDefault(); set(val - 1); }
            if (e.key === 'ArrowDown') { e.preventDefault(); set(val + 1); }
        });

        render();
        return { get: function () { return val; }, set: set };
    }

    var wheelH = Wheel($('kdWheelH'), updateApply);
    var wheelM = Wheel($('kdWheelM'), updateApply);

    function updateApply() { apply.disabled = (wheelH.get() === 0 && wheelM.get() === 0); }

    // Pop up memenuhi bingkai HP (di dalam .app-container), seperti kamera & pop up sukses
    var host = document.querySelector('.app-container');
    if (host) { host.appendChild(modal); } else { modal.classList.add('is-fixed'); }

    function openModal() {
        wheelH.set(est.set ? est.h : 0, true);
        wheelM.set(est.set ? est.m : 0, true);
        updateApply();
        modal.hidden = false;
        modal.classList.add('is-open');
        document.dispatchEvent(new CustomEvent('amt:state'));
    }
    function closeModal() {
        modal.hidden = true;
        modal.classList.remove('is-open');
        timeBtn.focus();
        document.dispatchEvent(new CustomEvent('amt:state'));
    }

    timeBtn.addEventListener('click', openModal);
    cancel.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });
    apply.addEventListener('click', function () {
        if (apply.disabled) { return; }
        est = { set: true, h: wheelH.get(), m: wheelM.get() };
        estH.value = pad(est.h);
        estM.value = pad(est.m);
        timeText.textContent = pad(est.h) + ' Jam ' + pad(est.m) + ' Menit';
        timeText.classList.remove('is-ph');
        modal.hidden = true;
        modal.classList.remove('is-open');
        refresh();
    });

    /* ---------- foto bukti ---------- */
    var cam = null;
    if (window.AmtCamera) {
        cam = window.AmtCamera.create({
            facing: 'environment',     // kamera belakang dulu (bukti kejadian); tombol "Kamera Depan" untuk ganti
            switchable: true,
            title: 'Foto Bukti',
            onSave: function (dataUrl) { setPhoto(dataUrl); }
        });
    }
    function setPhoto(dataUrl) {
        photoData.value = dataUrl || '';
        photoImg.src = dataUrl || '';
        photoDone.hidden = !dataUrl;
        photoBtn.hidden = !!dataUrl;
        refresh();
    }
    photoBtn.addEventListener('click', function () { if (cam) { cam.open(); } });
    photoDel.addEventListener('click', function () { setPhoto(''); });

    /* ---------- justifikasi ---------- */
    note.addEventListener('input', refresh);
    noteClear.addEventListener('click', function () { note.value = ''; refresh(); note.focus(); });

    /* ---------- kirim ---------- */
    form.addEventListener('submit', function (e) {
        if (form.getAttribute('data-ready') !== '1') { e.preventDefault(); return; }
        setTimeout(function () { send.disabled = true; }, 0);   // cegah kirim ganda
    });

    // Halaman dipulihkan dari cache (tombol Kembali): sinkronkan ulang tampilan dengan isi kolom
    window.addEventListener('pageshow', function () {
        if (photoData.value && photoDone.hidden) { setPhoto(photoData.value); } else { refresh(); }
    });

    refresh();
})();