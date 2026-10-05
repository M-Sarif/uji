/* ============================================================
 * OneFIS - AMT - mesin tutorial terpandu (khusus peran AMT)
 *
 * Ditulis ulang supaya mudah dipahami orang awam:
 *  - BERBASIS KONDISI: mesin selalu menampilkan langkah PERTAMA yang belum
 *    selesai (dibaca dari keadaan halaman), jadi tutorial tidak pernah
 *    "hilang" walau kamera dibatalkan atau form diisi tidak berurutan.
 *  - JEDA SINGKAT: hanya ~0,8 detik sekali saat halaman dibuka (atau sesuai
 *    config.startDelay, mis. 5 detik untuk pemberitahuan DCU). Antar
 *    langkah tidak ada jeda; hanya pesan "sudah benar" 1 detik.
 *  - HALAMAN DIAM: tutorial TIDAK menggulir halaman dan TIDAK menambah
 *    ruang kosong. Yang menyesuaikan diri adalah kartu tutorialnya: ia
 *    menempel di bawah, atau pindah ke atas bila akan menutupi bagian
 *    yang disorot. Bila bagian yang disorot ada di luar layar, kartu
 *    meminta pengguna menggulir sendiri.
 *  - LANGKAH "KETUK + TUNGGU" (needTap): mis. Perbarui Lokasi. Langkah baru
 *    selesai setelah diketuk DAN keadaannya terpenuhi (pin peta tampil),
 *    lalu pesan "sudah benar" ditahan sesuai 'hold' (2 detik) sebelum lanjut.
 *  - AUTO-HIDE (autoHide): kartu bisa tertutup sendiri setelah sekian ms
 *    bila pengguna tidak mengetuk tombolnya; sama seperti mengetuk "Mengerti".
 *  - DAFTAR BERNOMOR (list): kartu boleh memuat tahapan berurutan.
 *  - INGAT LANGKAH (remember): langkah yang sudah dibaca tidak diulang bila
 *    pengguna pindah halaman lalu kembali sebelum alur selesai.
 *  - Tersembunyi otomatis saat kamera / pop up foto terbuka. Popup konfirmasi PTI
 *    justru DISOROT oleh langkah tutorial (lihat tutorial.php, 'when').
 *  - "Lewati" hanya menyembunyikan tutorial halaman ini (sesi ini); tombol
 *    "?" selalu bisa memulai lagi.
 *
 * Data langkah: roles/amt/includes/tutorial.php. Gaya: roles/amt/assets/css/tutorial-amt.css.
 * Dimuat oleh core/layout_bottom.php hanya saat peran = AMT.
 * ============================================================ */
(function (window, document) {
    'use strict';

    var FIRST_DELAY_MS = 800;    // jeda SEKALI saat halaman baru dibuka
    var OK_HOLD_MS     = 1000;   // pesan "✅ sudah benar" sebelum pindah langkah
    var TICK_MS        = 350;    // cek keadaan halaman
    var PAD            = 6;      // ruang di sekeliling sorotan (px)
    var GAP            = 10;     // jarak kartu ke tepi bingkai / sorotan (px)

    var LS_DONE  = 'onefis_amt_tour_v2_done';    // daftar tutorial yang sudah selesai
    var SS_SKIP  = 'onefis_amt_tour_v2_skip:';   // + kunci -> dilewati (sesi ini)
    var LS_EPOCH = 'onefis_amt_tour_v2_epoch';   // penanda siklus dari server
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
        announce(key);
    }
    // Beri tahu halaman (mis. beranda AMT) bahwa sebuah tutorial sudah selesai/ditutup.
    function announce(key) {
        try { document.dispatchEvent(new CustomEvent('amt:tour-done', { detail: { key: key } })); } catch (e) {}
    }
    // Hapus catatan langkah-langkah 'remember' milik sebuah tutorial (mulai ulang dari awal).
    function forgetRemembered(key) {
        var d = readDone().filter(function (k) { return k.indexOf(key + '#') !== 0; });
        try { localStorage.setItem(LS_DONE, JSON.stringify(d)); } catch (e) {}
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

    // Siklus baru dari server (kembali ke index / Start Work / End Work):
    // hapus semua catatan "selesai / dilewati" supaya tutorial muncul lagi dari awal.
    function syncEpoch(epoch) {
        if (!epoch) { return; }
        try {
            if (localStorage.getItem(LS_EPOCH) !== epoch) {
                resetAll();
                localStorage.setItem(LS_EPOCH, epoch);
            }
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
        return !!(qs('.amtcam.is-open') || qs('#ci-modal:not([hidden])') || qs('#sc-modal:not([hidden])'));
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
        this.startDelay = config.startDelay || 0;   // ms; 0 = FIRST_DELAY_MS
        this.persist = config.persistDone !== false; // false = layar form: jangan catat "selesai"
        this.labels  = config.screenLabels || {};

        this.acked   = {};      // langkah aksi (done = null) yang sudah dilakukan
        this.okDone  = {};      // langkah yang pesan "sudah benar"-nya sudah tampil
        this.shown   = -1;      // indeks langkah yang terakhir ditampilkan
        this.uiOn    = false;
        this.ready   = false;
        this.dead    = false;
        this.okUntil = 0;
        this.hlEl    = null;    // elemen yang disorot (ring)
        this.tgtEl   = null;    // elemen yang harus diketuk
        this.side    = 'bottom';// posisi kartu terakhir: 'bottom' | 'top'
        this.okMode  = false;   // sedang menampilkan pesan "sudah benar"
        this.baseHint = '';
        this.whenAt  = {};      // kapan syarat 'when' sebuah langkah mulai terpenuhi
        this.autoTimers = {};   // pewaktu 'autoHide' per langkah (dipasang sekali)
        this.lastInputAt = 0;   // kapan pengguna terakhir mengetik (untuk 'settle')
        this.dom     = null;
        this.timers  = [];
        this.handlers = [];
    }

    Tour.prototype.start = function (force) {
        if (!this.steps.length) { return; }
        if (!force && (isDone(this.key) || isSkipped(this.key))) { return; }
        if (force) { setSkipped(this.key, false); forgetRemembered(this.key); }

        this._build();
        var self = this;
        this._later(function () { self.ready = true; self.tick(); }, force ? 150 : (this.startDelay || FIRST_DELAY_MS));
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
            '<ol class="amtt-list" data-list hidden></ol>' +
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
            announce(self.key);
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
            // Tombol yang masih terkunci (mis. Kirim sebelum catatan terisi) tidak menghitung.
            if (t.closest('.is-locked, [aria-disabled="true"]')) { return; }
            for (var i = self.steps.length - 1; i >= 0; i--) {
                var st = self.steps[i];
                if (!st.target || (st.done && !st.needTap)) { continue; }
                if (st.when && !qs(st.when)) { continue; }   // mis. menu PTI masih abu-abu
                if (t.closest(st.target)) { self.ack(i); break; }
            }
        }, true);

        // Perubahan form / ketukan apa pun -> periksa keadaan halaman segera.
        var soon = function () { self._later(function () { self.tick(); }, 60); };
        this._on(document, 'input', function () { self.lastInputAt = Date.now(); }, true);
        this._on(document, 'input', soon, true);
        this._on(document, 'change', soon, true);
        this._on(document, 'click', soon, true);
        this._on(document, 'amt:state', soon, false);

        var raf = null;
        var relayout = function () {
            if (raf) { return; }
            raf = requestAnimationFrame(function () { raf = null; self.sync(); });
        };
        this._on(window, 'resize', relayout);
        this._on(window, 'scroll', relayout, true);
        this._on(window, 'pagehide', function () { self.destroy(); });
    };

    /* ---------- keadaan langkah ---------- */
    Tour.prototype.isComplete = function (i) {
        var st = this.steps[i];
        if (st.skip && qs(st.skip)) { return true; }
        if (st.remember && isDone(this.key + '#' + i)) { return true; }   // sudah dibaca sebelumnya
        if (st.done) {
            var cond = !!qs(st.done);
            // Kolom teks: tunggu pengguna berhenti mengetik sebentar (jangan lompat di tengah ketikan).
            if (cond && st.settle && (Date.now() - this.lastInputAt) < st.settle) { cond = false; }
            return st.needTap ? (cond && !!this.acked[i]) : cond;
        }
        return !!this.acked[i];
    };

    Tour.prototype.firstIncomplete = function () {
        for (var i = 0; i < this.steps.length; i++) {
            if (!this.isComplete(i)) { return i; }
        }
        return this.steps.length;
    };

    // Alur selesai. Layar form (persist = false) tidak mencatat apa pun, karena
    // Kirim bisa ditolak server lalu halaman dimuat ulang: tutorial harus muncul lagi.
    Tour.prototype.finish = function () {
        if (this.persist) { markDone(this.key); }
    };

    // Pengguna melakukan langkah aksi ke-i (ketuk target / tekan tombol kartu).
    Tour.prototype.ack = function (i) {
        for (var j = 0; j <= i; j++) {
            if (!this.steps[j].done || this.steps[j].needTap) { this.acked[j] = true; }
            if (this.steps[j].remember && this.persist) { markDone(this.key + '#' + j); }
        }
        // Langkah bertanda 'announce' (mis. "Mengerti" pada pemberitahuan DCU) memberi
        // tahu halaman SEGERA, tanpa menunggu seluruh alur selesai.
        if (this.steps[i].announce) { announce(this.key); }
        // Langkah terakhir dilakukan -> alur ini selesai. Dicatat SEKARANG
        // (sebelum halaman berpindah) supaya tidak muncul lagi.
        if (i >= this.steps.length - 1) { this.finish(); }
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
            var hold = this.steps[prev].hold || OK_HOLD_MS;   // mis. 2000 ms agar pin sempat terlihat
            this.okUntil = Date.now() + hold;
            var selfOk = this;
            this._later(function () { selfOk.tick(); }, hold + 20);   // pindah tepat waktu
            return;
        }
        this.okUntil = 0;

        if (idx >= this.steps.length) {
            this.finish();
            this.destroy();
            return;
        }

        // Langkah yang menunggu keadaan halaman (mis. menu PTI baru menyala setelah DCU):
        // kartu disembunyikan dulu, lalu muncul 'delay' ms setelah syaratnya terpenuhi.
        var nx = this.steps[idx];
        if (nx.when) {
            if (!qs(nx.when)) { this.whenAt[idx] = 0; this.hideUI(); return; }
            if (!this.whenAt[idx]) { this.whenAt[idx] = Date.now(); }
            if (Date.now() - this.whenAt[idx] < (nx.delay || 0)) { this.hideUI(); return; }
        }

        if (idx !== this.shown || !this.uiOn) { this.show(idx); }
        else { this.sync(); }
    };

    /* ---------- tampilan ---------- */
    Tour.prototype.fill = function (st, forOk) {
        var p = this.dom.panel;
        var j = this.journey;
        var label = st.label || (j && j.label) || this.labels[this.screen] || '';
        var prog = label;
        if (st.no && j) { prog = label + ' · Langkah ' + st.no + ' dari ' + j.total; }
        p.querySelector('[data-progress]').textContent = prog;

        var barBox = p.querySelector('.amtt-bar');
        var showBar = !!(st.no && j);
        barBox.hidden = !showBar;
        if (showBar) { barBox.firstChild.style.width = Math.round(st.no / j.total * 100) + '%'; }

        p.querySelector('[data-title]').textContent = forOk ? st.ok : (st.title || '');
        p.querySelector('[data-text]').textContent  = forOk ? (st.okText || 'Lanjut ke langkah berikutnya…') : (st.text || '');

        // Daftar tahapan bernomor (opsional)
        var ol = p.querySelector('[data-list]');
        ol.innerHTML = '';
        var items = (!forOk && st.list) ? st.list : [];
        items.forEach(function (t) { ol.appendChild(el('li', '', '')).textContent = t; });
        ol.hidden = !items.length;

        this.baseHint = forOk ? '' : (st.hint || (st.target ? '👆 Ketuk bagian yang menyala biru' : ''));
        this.okMode = !!forOk;
        this.applyHint();

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

        this.okDone[idx] = false;          // langkah tampil lagi -> pesan "sudah benar" boleh muncul lagi

        var center = !st.target;
        var tgt = qs(st.target);
        var hl = qs(st.highlight) || tgt;
        this.tgtEl = (!center && isShown(tgt)) ? tgt : null;
        this.hlEl  = (!center && isShown(hl)) ? hl : null;

        d.panel.classList.remove('is-ok');
        d.ring.classList.remove('is-ok');
        d.panel.classList.toggle('is-center', center);
        d.dim.classList.toggle('is-on', center);
        d.panel.classList.add('is-on');
        document.body.classList.add('amtt-on');

        this.applyHint();
        this.sync();
        // Animasi kartu selesai sebentar lagi -> rapikan sekali lagi.
        var self = this;
        this._later(function () { self.sync(); }, 350);

        // Tertutup sendiri bila tidak diketuk: hitungan dimulai SEKALI saat pertama tampil,
        // dan hasilnya sama seperti mengetuk tombol kartu ("Mengerti").
        if (st.autoHide && !this.autoTimers[idx]) {
            this.autoTimers[idx] = this._later(function () {
                if (self.dead || self.shown !== idx || self.isComplete(idx)) { return; }
                self.ack(idx);
            }, st.autoHide);
        }
    };

    Tour.prototype.showOk = function (idx) {
        var d = this.dom;
        this.uiOn = true;
        this.fill(this.steps[idx], true);
        d.panel.classList.remove('is-center');
        d.panel.classList.add('is-on', 'is-ok');
        d.ring.classList.add('is-ok');
        document.body.classList.add('amtt-on');
        this.sync();
    };

    Tour.prototype.hideUI = function () {
        if (!this.dom) { return; }
        this.dom.panel.classList.remove('is-on');
        this.dom.ring.classList.remove('is-on');
        this.dom.dim.classList.remove('is-on');
        this.uiOn = false;
        this.hlEl = null;
        this.tgtEl = null;
        document.body.classList.remove('amtt-on');
    };

    // Kotak sorotan (sudah + PAD) yang dipotong ke area isi halaman; null bila tak terlihat.
    Tour.prototype.hlBox = function () {
        var h = this.hlEl;
        if (!h || !isShown(h)) { return null; }
        var r = h.getBoundingClientRect();
        var c = qs('.content');
        var top = r.top, bot = r.bottom;
        if (c && c.contains(h)) {
            var cr = c.getBoundingClientRect();
            top = Math.max(top, cr.top);
            bot = Math.min(bot, cr.bottom);
        }
        if (bot - top < 4) { return null; }
        return { left: r.left - PAD, top: top - PAD, width: r.width + PAD * 2, height: bot - top + PAD * 2, bottom: bot + PAD };
    };

    // Arah gulir bila bagian yang harus diketuk berada di luar layar ('down' | 'up' | null).
    Tour.prototype.offscreenDir = function () {
        var t = this.tgtEl;
        var c = qs('.content');
        if (!t || !c || !c.contains(t) || !isShown(t)) { return null; }
        var cr = c.getBoundingClientRect(), r = t.getBoundingClientRect();
        var need = Math.min(r.height, 28);
        var visible = Math.min(r.bottom, cr.bottom) - Math.max(r.top, cr.top);
        if (visible >= need) { return null; }
        return r.top >= cr.top ? 'down' : 'up';
    };

    // Isi kotak biru di kartu: menunggu > ajakan menggulir > petunjuk biasa.
    Tour.prototype.applyHint = function () {
        if (!this.dom) { return; }
        var hint = this.dom.panel.querySelector('[data-hint]');
        var txt = this.baseHint, waiting = false;
        if (!this.okMode) {
            var st = this.steps[this.shown];
            if (st && st.needTap && this.acked[this.shown] && !this.isComplete(this.shown)) {
                txt = st.wait || '⏳ Mohon tunggu…';
                waiting = true;
            } else {
                var dir = this.offscreenDir();
                if (dir === 'down') { txt = '👇 Gulir ke bawah sampai bagian yang menyala terlihat'; }
                else if (dir === 'up') { txt = '👆 Gulir ke atas sampai bagian yang menyala terlihat'; }
            }
        }
        if (hint.textContent !== txt) { hint.textContent = txt; }
        hint.hidden = !txt;
        hint.classList.toggle('is-wait', waiting);
    };

    // Satu pintu untuk merapikan tampilan: teks, posisi kartu, dan sorotan.
    Tour.prototype.sync = function () {
        if (!this.uiOn || !this.dom) { return; }
        this.applyHint();
        this.layoutPanel();
        this.reposition();
    };

    // Halaman TIDAK digeser. Kartu menempel di bawah bingkai aplikasi, dan
    // pindah ke atas isi halaman bila di bawah akan menutupi bagian yang disorot.
    Tour.prototype.layoutPanel = function () {
        if (!this.uiOn) { return; }
        var p = this.dom.panel;
        if (p.classList.contains('is-center')) {
            p.style.left = p.style.width = p.style.top = p.style.bottom = p.style.maxHeight = '';
            return;
        }
        var vw = window.innerWidth, vh = window.innerHeight;
        var app = qs('.app-container');
        var ab = app ? app.getBoundingClientRect() : { left: 0, right: vw, top: 0, bottom: vh };
        var c = qs('.content');
        var cr = c ? c.getBoundingClientRect() : ab;

        var left = Math.max(0, ab.left), right = Math.min(vw, ab.right);
        var width = Math.min(380, right - left - 20);
        p.style.width = width + 'px';
        p.style.left = (left + (right - left - width) / 2) + 'px';
        p.style.maxHeight = '';                       // kembali ke batas bawaan CSS

        var botEdge = Math.min(vh, ab.bottom) - GAP;  // y batas bawah kartu
        var topEdge = Math.max(0, cr.top) + GAP;      // y batas atas kartu
        var ph = p.offsetHeight;
        var box = this.hlBox();
        var side = 'bottom';

        if (box) {
            var fitsBottom = botEdge - ph >= box.bottom + 4;
            var fitsTop    = topEdge + ph <= box.top - 4;
            if (this.side === 'top' && fitsTop)          { side = 'top'; }      // bertahan agar tidak berkedip
            else if (this.side === 'bottom' && fitsBottom) { side = 'bottom'; }
            else if (fitsBottom)                          { side = 'bottom'; }
            else if (fitsTop)                             { side = 'top'; }
            else {                                        // tak ada yang muat: pilih sisi lebih lega, kartu diperkecil
                var roomBottom = botEdge - box.bottom - 4;
                var roomTop    = box.top - 4 - topEdge;
                side = roomBottom >= roomTop ? 'bottom' : 'top';
                p.style.maxHeight = Math.max(110, Math.floor(side === 'bottom' ? roomBottom : roomTop)) + 'px';
            }
        }
        this.side = side;

        if (side === 'bottom') {
            p.style.top = 'auto';
            p.style.bottom = 'calc(' + Math.max(0, vh - botEdge) + 'px + env(safe-area-inset-bottom, 0px))';
        } else {
            p.style.bottom = 'auto';
            p.style.top = topEdge + 'px';
        }
    };

    // Kotak sorotan (ring) mengikuti elemen; bagian di luar area isi dipotong.
    Tour.prototype.reposition = function () {
        if (!this.uiOn || !this.dom) { return; }
        var ring = this.dom.ring;
        var b = this.hlBox();
        if (!b) { ring.classList.remove('is-on'); return; }
        ring.style.left   = b.left + 'px';
        ring.style.top    = b.top + 'px';
        ring.style.width  = b.width + 'px';
        ring.style.height = b.height + 'px';
        ring.classList.add('is-on');
    };

    Tour.prototype.destroy = function () {
        this.dead = true;
        clearInterval(this.tickTimer);
        this.timers.forEach(clearTimeout);
        this.handlers.forEach(function (h) { h.t.removeEventListener(h.e, h.f, h.c); });
        this.handlers = [];
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
            syncEpoch(config.epoch);
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