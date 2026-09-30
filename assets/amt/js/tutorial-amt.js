/* ============================================================
 * OneFIS - AMT - mesin tutorial terpandu (khusus peran AMT)
 *
 * Ditulis ulang supaya mudah dipahami orang awam:
 *  - BERBASIS KONDISI: mesin selalu menampilkan langkah PERTAMA yang belum
 *    selesai (dibaca dari keadaan halaman), jadi tutorial tidak pernah
 *    "hilang" walau kamera dibatalkan atau form diisi tidak berurutan.
 *  - JEDA SINGKAT: hanya ~0,8 detik sekali saat halaman dibuka. Antar
 *    langkah tidak ada jeda; hanya pesan "sudah benar" 1 detik.
 *  - KARTU DI BAWAH: posisi selalu sama, halaman otomatis digulir supaya
 *    bagian yang disorot tidak tertutup kartu.
 *  - Tersembunyi otomatis saat kamera / pop up foto terbuka.
 *  - "Lewati" hanya menyembunyikan tutorial halaman ini (sesi ini); tombol
 *    "?" selalu bisa memulai lagi.
 *
 * Data langkah: includes/amt/tutorial.php. Gaya: assets/amt/css/tutorial-amt.css.
 * Dimuat oleh includes/layout_bottom.php hanya saat peran = AMT.
 * ============================================================ */
(function (window, document) {
    'use strict';

    var FIRST_DELAY_MS = 800;    // jeda SEKALI saat halaman baru dibuka
    var OK_HOLD_MS     = 1000;   // pesan "✅ sudah benar" sebelum pindah langkah
    var TICK_MS        = 350;    // cek keadaan halaman
    var PAD            = 6;      // ruang di sekeliling sorotan (px)

    var LS_DONE  = 'onefis_amt_tour_v2_done';    // daftar tutorial yang sudah selesai
    var SS_SKIP  = 'onefis_amt_tour_v2_skip:';   // + kunci -> dilewati (sesi ini)
    var OLD_KEYS = ['onefis_amt_tour_seen_screens', 'onefis_amt_tour_skipped'];

    /* ---------- penyimpanan (semua dibungkus try/catch) ---------- */
    function readDone() {
        try { return JSON.parse(localStorage.getItem(LS_DONE) || '[]'); } catch (e) { return []; }
    }
    function isDone(key) { return readDone().indexOf(key) !== -1; }
    function markDone(key) {
        var d = readDone();
        if (d.indexOf(key) === -1) {
            d.push(key);
            try { localStorage.setItem(LS_DONE, JSON.stringify(d)); } catch (e) {}
        }
    }
    function isSkipped(key) {
        try { return sessionStorage.getItem(SS_SKIP + key) === '1'; } catch (e) { return false; }
    }
    function setSkipped(key, val) {
        try {
            if (val) { sessionStorage.setItem(SS_SKIP + key, '1'); }
            else { sessionStorage.removeItem(SS_SKIP + key); }
        } catch (e) {}
    }
    function resetAll() {
        try {
            localStorage.removeItem(LS_DONE);
            OLD_KEYS.forEach(function (k) { localStorage.removeItem(k); });
            Object.keys(sessionStorage).forEach(function (k) {
                if (k.indexOf(SS_SKIP) === 0 || k.indexOf('onefis_amt_tour_step:') === 0) { sessionStorage.removeItem(k); }
            });
        } catch (e) {}
    }

    /* ---------- bantuan DOM ---------- */
    function qs(sel) {
        try { return sel ? document.querySelector(sel) : null; } catch (e) { return null; }
    }
    function isShown(el) {
        if (!el || (el.closest && el.closest('[hidden]'))) { return false; }
        var r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
    }
    // Kamera layar penuh / pop up foto: tutorial disembunyikan supaya tidak menimpa.
    function overlayOpen() {
        return !!(qs('.amtcam.is-open') || qs('#ci-modal:not([hidden])'));
    }
    function el(tag, cls, html) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (html) { n.innerHTML = html; }
        return n;
    }

    /* ============================================================
     * Tour
     * ============================================================ */
    function Tour(config) {
        this.screen  = config.screen;
        this.key     = config.subKey || config.screen;
        this.steps   = config.steps || [];
        this.journey = config.journey || null;
        this.labels  = config.screenLabels || {};

        this.acked   = {};      // langkah aksi (done = null) yang sudah dilakukan
        this.okDone  = {};      // langkah yang pesan "sudah benar"-nya sudah tampil
        this.shown   = -1;      // indeks langkah yang terakhir ditampilkan
        this.uiOn    = false;
        this.ready   = false;
        this.dead    = false;
        this.okUntil = 0;
        this.hlEl    = null;
        this.dom     = null;
        this.timers  = [];
        this.handlers = [];
    }

    Tour.prototype.start = function (force) {
        if (!this.steps.length) { return; }
        if (!force && (isDone(this.key) || isSkipped(this.key))) { return; }
        if (force) { setSkipped(this.key, false); }

        this._build();
        var self = this;
        this._later(function () { self.ready = true; self.tick(); }, force ? 150 : FIRST_DELAY_MS);
        this.tickTimer = setInterval(function () { self.tick(); }, TICK_MS);
    };

    Tour.prototype._later = function (fn, ms) {
        var t = setTimeout(fn, ms);
        this.timers.push(t);
        return t;
    };

    Tour.prototype._on = function (target, evt, fn, capture) {
        target.addEventListener(evt, fn, !!capture);
        this.handlers.push({ t: target, e: evt, f: fn, c: !!capture });
    };

    /* ---------- membangun elemen ---------- */
    Tour.prototype._build = function () {
        var self = this;
        var dim   = el('div', 'amtt-dim');
        var ring  = el('div', 'amtt-ring');
        var panel = el('div', 'amtt-panel');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-live', 'polite');
        panel.innerHTML =
            '<div class="amtt-eyebrow" data-progress></div>' +
            '<div class="amtt-bar" aria-hidden="true"><span></span></div>' +
            '<h3 class="amtt-title" data-title></h3>' +
            '<p class="amtt-text" data-text></p>' +
            '<p class="amtt-hint" data-hint></p>' +
            '<div class="amtt-actions">' +
            '  <button type="button" class="amtt-btn" data-primary hidden></button>' +
            '  <button type="button" class="amtt-skip" data-skip>Lewati</button>' +
            '</div>';
        document.body.appendChild(dim);
        document.body.appendChild(ring);
        document.body.appendChild(panel);
        this.dom = { dim: dim, ring: ring, panel: panel };

        panel.querySelector('[data-skip]').addEventListener('click', function () {
            setSkipped(self.key, true);
            self.destroy();
            toast('Tutorial disembunyikan. Ketuk tombol “?” untuk membukanya lagi.');
        });
        panel.querySelector('[data-primary]').addEventListener('click', function () {
            if (self.shown >= 0) { self.ack(self.shown); }
        });

        // Ketukan pada elemen target langkah aksi = langkah itu dilakukan.
        this._on(document, 'click', function (e) {
            var t = e.target;
            if (!t || !t.closest || panel.contains(t)) { return; }
            for (var i = self.steps.length - 1; i >= 0; i--) {
                var st = self.steps[i];
                if (st.done || !st.target) { continue; }
                if (t.closest(st.target)) { self.ack(i); break; }
            }
        }, true);

        // Perubahan form / ketukan apa pun -> periksa keadaan halaman segera.
        var soon = function () { self._later(function () { self.tick(); }, 60); };
        this._on(document, 'input', soon, true);
        this._on(document, 'change', soon, true);
        this._on(document, 'click', soon, true);
        this._on(document, 'amt:state', soon, false);

        var relayout = function () { self.reposition(); };
        this._on(window, 'resize', function () { self.layoutPanel(); self.reposition(); });
        this._on(window, 'scroll', relayout, true);
        this._on(window, 'pagehide', function () { self.destroy(); });
    };

    /* ---------- keadaan langkah ---------- */
    Tour.prototype.isComplete = function (i) {
        var st = this.steps[i];
        if (st.skip && qs(st.skip)) { return true; }
        if (st.done) { return !!qs(st.done); }
        return !!this.acked[i];
    };

    Tour.prototype.firstIncomplete = function () {
        for (var i = 0; i < this.steps.length; i++) {
            if (!this.isComplete(i)) { return i; }
        }
        return this.steps.length;
    };

    // Pengguna melakukan langkah aksi ke-i (ketuk target / tekan tombol kartu).
    Tour.prototype.ack = function (i) {
        for (var j = 0; j <= i; j++) {
            if (!this.steps[j].done) { this.acked[j] = true; }
        }
        // Langkah terakhir dilakukan -> alur ini selesai. Dicatat SEKARANG
        // (sebelum halaman berpindah) supaya tidak muncul lagi.
        if (i >= this.steps.length - 1) { markDone(this.key); }
        var self = this;
        this._later(function () { self.tick(); }, 30);
    };

    /* ---------- siklus utama ---------- */
    Tour.prototype.tick = function () {
        if (this.dead || !this.ready) { return; }

        if (overlayOpen()) { this.hideUI(); return; }

        // Sedang menahan pesan "✅ sudah benar" -> biarkan tampil sebentar.
        if (this.okUntil && Date.now() < this.okUntil) { return; }

        var idx  = this.firstIncomplete();
        var prev = this.shown;

        // Langkah yang tadi tampil baru saja selesai -> beri umpan balik singkat.
        if (prev >= 0 && prev < this.steps.length && idx > prev &&
            this.steps[prev].ok && !this.okDone[prev]) {
            this.okDone[prev] = true;
            this.showOk(prev);
            this.okUntil = Date.now() + OK_HOLD_MS;
            var selfOk = this;
            this._later(function () { selfOk.tick(); }, OK_HOLD_MS + 20);   // pindah tepat waktu
            return;
        }
        this.okUntil = 0;

        if (idx >= this.steps.length) {
            markDone(this.key);
            this.destroy();
            return;
        }
        if (idx !== this.shown || !this.uiOn) { this.show(idx); }
        else { this.reposition(); }
    };

    /* ---------- tampilan ---------- */
    Tour.prototype.fill = function (st, forOk) {
        var p = this.dom.panel;
        var j = this.journey;
        var label = (j && j.label) || this.labels[this.screen] || '';
        var prog = label;
        if (st.no && j) { prog = label + ' · Langkah ' + st.no + ' dari ' + j.total; }
        p.querySelector('[data-progress]').textContent = prog;

        var barBox = p.querySelector('.amtt-bar');
        var showBar = !!(st.no && j);
        barBox.hidden = !showBar;
        if (showBar) { barBox.firstChild.style.width = Math.round(st.no / j.total * 100) + '%'; }

        p.querySelector('[data-title]').textContent = forOk ? st.ok : (st.title || '');
        p.querySelector('[data-text]').textContent  = forOk ? 'Lanjut ke langkah berikutnya…' : (st.text || '');

        var hint = p.querySelector('[data-hint]');
        hint.textContent = forOk ? '' : (st.hint || (st.target ? '👆 Ketuk bagian yang menyala biru' : ''));
        hint.hidden = !hint.textContent;

        var btn = p.querySelector('[data-primary]');
        btn.textContent = st.button || '';
        btn.hidden = forOk || !st.button;
    };

    Tour.prototype.show = function (idx) {
        var st = this.steps[idx];
        var d = this.dom;
        this.shown = idx;
        this.uiOn = true;
        this.fill(st, false);

        var center = !st.target;
        var tgt = qs(st.target);
        var hl = qs(st.highlight) || tgt;
        this.hlEl = (!center && isShown(hl)) ? hl : null;

        d.panel.classList.remove('is-ok');
        d.ring.classList.remove('is-ok');
        d.panel.classList.toggle('is-center', center);
        d.dim.classList.toggle('is-on', center);
        d.panel.classList.add('is-on');
        document.body.classList.add('amtt-on');

        this.layoutPanel();
        if (this.hlEl) { this.ensureVisible(this.hlEl); }
        this.reposition();
        // Gulir halus + animasi kartu selesai sebentar lagi -> rapikan sekali lagi.
        var self = this;
        this._later(function () { self.layoutPanel(); self.reposition(); }, 350);
    };

    Tour.prototype.showOk = function (idx) {
        var d = this.dom;
        this.uiOn = true;
        this.fill(this.steps[idx], true);
        d.panel.classList.remove('is-center');
        d.panel.classList.add('is-on', 'is-ok');
        d.ring.classList.add('is-ok');
        document.body.classList.add('amtt-on');
        this.layoutPanel();
        this.reposition();
    };

    Tour.prototype.hideUI = function () {
        if (!this.dom) { return; }
        this.dom.panel.classList.remove('is-on');
        this.dom.ring.classList.remove('is-on');
        this.dom.dim.classList.remove('is-on');
        this.uiOn = false;
        this.hlEl = null;
        this.reserve(0);
        document.body.classList.remove('amtt-on');
    };

    // Ruang di bawah isi halaman supaya bagian paling bawah bisa digulir ke
    // atas kartu (kartu tidak menutupi form).
    Tour.prototype.reserve = function (px) {
        var c = qs('.content');
        if (!c) { return; }
        c.style.paddingBottom = px ? 'calc(env(safe-area-inset-bottom, 0px) + ' + px + 'px)' : '';
    };

    // Kartu selalu menempel di bawah bingkai aplikasi (atau di tengah).
    Tour.prototype.layoutPanel = function () {
        if (!this.uiOn) { return; }
        var p = this.dom.panel;
        if (p.classList.contains('is-center')) {
            p.style.left = p.style.width = p.style.bottom = '';
            this.reserve(0);
            return;
        }
        var vw = window.innerWidth, vh = window.innerHeight;
        var app = qs('.app-container');
        var ab = app ? app.getBoundingClientRect() : { left: 0, right: vw, bottom: vh };
        var left = Math.max(0, ab.left), right = Math.min(vw, ab.right);
        var width = Math.min(380, right - left - 20);
        p.style.width = width + 'px';
        p.style.left = (left + (right - left - width) / 2) + 'px';
        p.style.bottom = 'calc(' + Math.max(0, vh - Math.min(vh, ab.bottom)) + 'px + 10px + env(safe-area-inset-bottom, 0px))';
        this.reserve(p.offsetHeight + 24);
    };

    // Gulir halaman seperlunya supaya bagian yang disorot terlihat DI ATAS kartu.
    Tour.prototype.ensureVisible = function (elm) {
        var c = qs('.content');
        if (!c || !c.contains(elm)) { return; }
        var cr = c.getBoundingClientRect();
        var panelTop = this.dom.panel.getBoundingClientRect().top;
        var top = Math.max(cr.top, 0) + 8;
        var bot = Math.min(cr.bottom, panelTop) - 12;
        var r = elm.getBoundingClientRect();
        if (r.top >= top && r.bottom <= bot) { return; }
        var avail = bot - top, delta;
        if (r.height >= avail) { delta = r.top - top; }
        else { delta = (r.top + r.height / 2) - (top + avail / 2); }
        try { c.scrollBy({ top: delta, behavior: 'smooth' }); } catch (e) { c.scrollTop += delta; }
    };

    // Kotak sorotan (ring) mengikuti elemen; bagian di luar area isi dipotong.
    Tour.prototype.reposition = function () {
        if (!this.uiOn || !this.dom) { return; }
        var ring = this.dom.ring;
        var h = this.hlEl;
        if (!h || !isShown(h)) { ring.classList.remove('is-on'); return; }
        var r = h.getBoundingClientRect();
        var c = qs('.content');
        var top = r.top, bot = r.bottom;
        if (c && c.contains(h)) {
            var cr = c.getBoundingClientRect();
            top = Math.max(top, cr.top);
            bot = Math.min(bot, cr.bottom);
        }
        if (bot - top < 4) { ring.classList.remove('is-on'); return; }
        ring.style.left   = (r.left - PAD) + 'px';
        ring.style.top    = (top - PAD) + 'px';
        ring.style.width  = (r.width + PAD * 2) + 'px';
        ring.style.height = (bot - top + PAD * 2) + 'px';
        ring.classList.add('is-on');
    };

    Tour.prototype.destroy = function () {
        this.dead = true;
        clearInterval(this.tickTimer);
        this.timers.forEach(clearTimeout);
        this.handlers.forEach(function (h) { h.t.removeEventListener(h.e, h.f, h.c); });
        this.handlers = [];
        this.reserve(0);
        document.body.classList.remove('amtt-on');
        if (this.dom) {
            [this.dom.dim, this.dom.ring, this.dom.panel].forEach(function (n) {
                if (n && n.parentNode) { n.parentNode.removeChild(n); }
            });
            this.dom = null;
        }
    };

    /* ---------- pesan singkat di bawah layar ---------- */
    function toast(msg) {
        var t = el('div', 'amtt-toast');
        t.textContent = msg;
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('is-on'); });
        setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 3200);
    }

    /* ---------- API publik (nama sama dengan mesin SPBU) ---------- */
    var activeTour = null;

    window.OneFISTour = {
        init: function (config) {
            var forceRestart = new URLSearchParams(window.location.search).get('tour') === 'restart';
            if (forceRestart) { resetAll(); }

            activeTour = new Tour(config);
            activeTour.start(forceRestart);

            // Tombol "?": mulai lagi tutorial HALAMAN INI (tidak pindah halaman).
            if (config.screen !== 'role_select') {
                var fab = el('button', 'amtt-fab');
                fab.type = 'button';
                fab.setAttribute('aria-label', 'Tampilkan tutorial halaman ini');
                fab.textContent = '?';
                fab.addEventListener('click', function () {
                    if (activeTour) { activeTour.destroy(); }
                    activeTour = new Tour(config);
                    if (!activeTour.steps.length) {
                        toast('Belum ada tutorial untuk halaman ini.');
                        return;
                    }
                    activeTour.start(true);
                });
                document.body.appendChild(fab);
            }
        },
        // Hapus catatan "sudah selesai" (dipakai saat pengguna memilih peran).
        reset: function () { resetAll(); },
        // Halaman lain boleh meminta pemeriksaan ulang keadaan.
        rescan: function () { if (activeTour) { activeTour.tick(); } }
    };

})(window, document);