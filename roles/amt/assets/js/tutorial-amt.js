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
 *  - Tersembunyi otomatis saat kamera / pop up foto (check-in, Lihat Foto surat jalan) terbuka. Popup konfirmasi PTI
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
    // Tutorial DIMATIKAN untuk semua layar: pengguna menekan "Lewati", atau sudah memakai tutorial sampai
    // alur selesai (langkah bertanda 'final'). Tidak ikut terhapus oleh siklus baru (resetAll). Tutorial hanya
    // tampil lagi bila pengguna menekan tombol "?" (per halaman).
    var LS_OFF   = 'onefis_amt_tour_v2_off';
    var LS_FRESH = 'onefis_amt_tour_v2_fresh';   // penanda kunjungan baru ke AMT (dari index)
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
    // extra.skipped = true bila tutorial ditutup lewat "Lewati" (bukan diselesaikan).
    function announce(key, extra) {
        try { document.dispatchEvent(new CustomEvent('amt:tour-done', { detail: { key: key, skipped: !!(extra && extra.skipped) } })); } catch (e) {}
    }
    // Hapus catatan langkah-langkah 'remember' milik sebuah tutorial (mulai ulang dari awal).
    function forgetRemembered(key) {
        var d = readDone().filter(function (k) { return k.indexOf(key + '#') !== 0; });
        try { localStorage.setItem(LS_DONE, JSON.stringify(d)); } catch (e) {}
    }
    function isOff() {
        try { return localStorage.getItem(LS_OFF) === '1'; } catch (e) { return false; }
    }
    function setOff() {
        try { localStorage.setItem(LS_OFF, '1'); } catch (e) {}
    }
    function clearOff() {
        try { localStorage.removeItem(LS_OFF); } catch (e) {}
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
    // Hapus semua catatan "dilewati" (sesi ini) tanpa menyentuh catatan "selesai".
    function clearSkips() {
        try {
            Object.keys(sessionStorage).forEach(function (k) {
                if (k.indexOf(SS_SKIP) === 0) { sessionStorage.removeItem(k); }
            });
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

    // Kunjungan baru ke peran AMT (pengguna kembali ke index lalu memilih AMT lagi):
    // status "dimatikan" (hasil Lewati / selesai) dihapus supaya tutorial muncul otomatis lagi.
    function syncFresh(fresh) {
        if (!fresh) { return; }
        try {
            if (localStorage.getItem(LS_FRESH) !== fresh) {
                clearOff();
                localStorage.setItem(LS_FRESH, fresh);
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
    // allowCam = true bila langkah yang sedang tampil memang dipandu DI DALAM kamera ('camera': true).
    function overlayOpen(allowCam) {
        return !!((!allowCam && qs('.amtcam.is-open')) || qs('#ci-modal:not([hidden])') || qs('#sc-modal:not([hidden])') || qs('#sjModal:not([hidden])') || (!allowCam && qs('#wk-success:not([hidden])')));
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
        this.hasExplain = false; // langkah ini punya penjelasan untuk lembar baca
        this.isInfo = false;     // langkah baca (ada tombol Mengerti): teksnya tampil 3 baris
        this.needMore = false;   // teks langkah baca terpotong
        this.okMode  = false;   // sedang menampilkan pesan "sudah benar"
        this.baseHint = '';
        this.whenAt  = {};      // kapan syarat 'when' sebuah langkah mulai terpenuhi
        this.condAt  = {};      // kapan syarat 'done' langkah ber-'minShow' mulai terpenuhi
        this.autoTimers = {};   // pewaktu 'autoHide' per langkah (dipasang sekali)
        this.lastInputAt = 0;   // kapan pengguna terakhir mengetik (untuk 'settle')
        this.lastInputEl = null; // elemen yang terakhir diketik (untuk 'settle')
        this.dom     = null;
        this.timers  = [];
        this.handlers = [];
    }

    Tour.prototype.start = function (force) {
        if (!this.steps.length) { return; }
        this.forced = !!force;   // dibuka lewat tombol "?" / ?tour=restart: tetap tampil walau tutorial dimatikan
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
            '<div class="amtt-head">' +
            '  <div class="amtt-eyebrow" data-progress></div>' +
            '  <div class="amtt-tools">' +
            '    <button type="button" class="amtt-min amtt-aa" data-size aria-label="Ubah ukuran huruf">Aa</button>' +
            '    <button type="button" class="amtt-min" data-min aria-label="Kecilkan kartu tutorial">Kecilkan ▾</button>' +
            '  </div>' +
            '</div>' +
            '<div class="amtt-bar" aria-hidden="true"><span></span></div>' +
            '<div class="amtt-bodywrap">' +
            '  <div class="amtt-body" data-body>' +
            '    <h3 class="amtt-title" data-title></h3>' +
            '    <p class="amtt-text" data-text></p>' +
            '    <ol class="amtt-list" data-list hidden></ol>' +
            '    <p class="amtt-hint" data-hint></p>' +
            '  </div>' +
            '  <div class="amtt-more" data-more aria-hidden="true">⌄ Geser ke bawah</div>' +
            '</div>' +
            '<div class="amtt-actions">' +
            '  <button type="button" class="amtt-btn" data-primary hidden></button>' +
            '  <div class="amtt-row2">' +
            '    <button type="button" class="amtt-ghost" data-explain hidden>📖 Penjelasan</button>' +
            '    <button type="button" class="amtt-skip" data-skip>Lewati</button>' +
            '  </div>' +
            '</div>';
        // Lembar baca: penjelasan lengkap, huruf besar, tidak ikut kartu (tidak perlu menggeser kartu)
        var sheet = el('div', 'amtt-sheet');
        sheet.hidden = true;
        sheet.innerHTML =
            '<div class="amtt-sheet__card" role="dialog" aria-modal="true" aria-label="Penjelasan lengkap">' +
            '  <div class="amtt-sheet__eyebrow" data-s-eyebrow></div>' +
            '  <div class="amtt-sheet__body">' +
            '    <h3 class="amtt-sheet__title" data-s-title></h3>' +
            '    <p class="amtt-sheet__text" data-s-text></p>' +
            '    <ol class="amtt-sheet__list" data-s-list hidden></ol>' +
            '  </div>' +
            '  <div class="amtt-sheet__tools">' +
            '    <button type="button" class="amtt-ghost" data-s-speak>🔊 Dengarkan</button>' +
            '    <button type="button" class="amtt-ghost" data-s-size>Aa Ukuran huruf</button>' +
            '  </div>' +
            '  <button type="button" class="amtt-btn" data-s-close>Tutup</button>' +
            '</div>';
        document.body.appendChild(sheet);
        document.body.appendChild(dim);
        document.body.appendChild(ring);
        document.body.appendChild(panel);
        this.dom = { dim: dim, ring: ring, panel: panel, sheet: sheet };
        this.applyScale();

        var minBtn = panel.querySelector('[data-min]');
        minBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.setMin(!panel.classList.contains('is-min'));
        });
        panel.querySelector('.amtt-head').addEventListener('click', function () {
            if (panel.classList.contains('is-min')) { self.setMin(false); }   // ketuk pil kecil = buka lagi
        });
        panel.querySelector('[data-size]').addEventListener('click', function (e) { e.stopPropagation(); self.cycleScale(); });
        panel.querySelector('[data-explain]').addEventListener('click', function (e) { e.stopPropagation(); self.openExplain(); });
        sheet.querySelector('[data-s-close]').addEventListener('click', function () { self.closeExplain(); });
        sheet.querySelector('[data-s-size]').addEventListener('click', function () { self.cycleScale(); });
        sheet.querySelector('[data-s-speak]').addEventListener('click', function () { self.speak(); });
        sheet.addEventListener('click', function (e) { if (e.target === sheet) { self.closeExplain(); } });   // ketuk area gelap = tutup
        panel.querySelector('[data-body]').addEventListener('scroll', function () { self.updateMore(); }, { passive: true });

        panel.querySelector('[data-skip]').addEventListener('click', function () {
            setSkipped(self.key, true);
            setOff();                                   // "Lewati" = matikan tutorial di SEMUA layar
            announce(self.key, { skipped: true });
            self.destroy();
            toast('Tutorial dimatikan. Ketuk tombol “?” bila ingin membukanya lagi.');
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
        // Catat WAKTU dan ELEMEN input terakhir. Penting: ketukan bintang (radio) / centang juga memicu
        // event 'input'; kalau hanya waktunya yang dicatat, langkah "nama terisi" ikut dianggap sedang
        // diketik lagi dan tutorial bolak-balik ke langkah nama setiap kali bintang diketuk.
        this._on(document, 'input', function (e) {
            self.lastInputAt = Date.now();
            self.lastInputEl = (e && e.target) || null;
        }, true);
        this._on(document, 'input', soon, true);
        this._on(document, 'change', soon, true);
        this._on(document, 'click', soon, true);
        this._on(document, 'amt:state', soon, false);

        var raf = null;
        var relayout = function (ev) {
            // Gulir DI DALAM kartu tutorial tidak boleh memicu penataan ulang (dulu membuat gulir terpental ke atas).
            if (ev && ev.target && ev.target.nodeType === 1 && panel.contains(ev.target)) { return; }
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
            // 'settle' HANYA berlaku bila yang baru diketik adalah kolom milik langkah ini (target-nya),
            // bukan kolom/bintang lain di halaman yang sama.
            if (cond && st.settle && (Date.now() - this.lastInputAt) < st.settle) {
                var le = this.lastInputEl;
                var own = !!(le && st.target && le.closest && le.closest(st.target));
                if (own) { cond = false; }
            }
            // 'minShow' (ms): walau syarat sudah terpenuhi, langkah tetap disorot minimal selama itu
            // (mis. lokasi sudah sesuai dari awal: pengguna diberi waktu memeriksa pin, tidak langsung dilewati).
            if (st.minShow) {
                if (cond) {
                    if (!this.condAt[i]) {
                        this.condAt[i] = Date.now();
                        var selfM = this;
                        this._later(function () { selfM.tick(); }, st.minShow + 40);
                    }
                    cond = (Date.now() - this.condAt[i]) >= st.minShow;
                } else {
                    this.condAt[i] = 0;
                }
            }
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
        // Langkah terakhir bertanda 'final' (mis. pop up sukses Check-Out / End Work): pengguna sudah memakai
        // tutorial sampai selesai -> tidak perlu muncul otomatis lagi di layar mana pun.
        var last = this.steps[this.steps.length - 1];
        if (last && last.final) { setOff(); }
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
        // Langkah bertanda 'restart' (mis. "Mulai" pada kartu ucapan selamat): nyalakan lagi tutorial otomatis.
        if (this.steps[i].restart) { clearOff(); }
        // Langkah terakhir dilakukan -> alur ini selesai. Dicatat SEKARANG
        // (sebelum halaman berpindah) supaya tidak muncul lagi.
        if (i >= this.steps.length - 1) { this.finish(); }
        var self = this;
        this._later(function () { self.tick(); }, 30);
    };

    /* ---------- siklus utama ---------- */
    Tour.prototype.tick = function () {
        if (this.dead || !this.ready) { return; }

        // Tutorial sudah dimatikan ("Lewati" / sudah dipakai sampai selesai), mis. dari halaman atau tab lain, atau
        // halaman ini dipulihkan dari cache tombol Kembali: hanya PEMBERITAHUAN ('notice') yang boleh tetap tampil.
        if (!this.forced && isOff()) {
            var cs0 = this.steps[this.firstIncomplete()];
            if (!cs0 || !cs0.notice) { this.destroy(); return; }
        }

        // Kamera layar penuh / pop up foto menyembunyikan tutorial, KECUALI langkah yang memang dipandu di dalamnya
        // (langkah 'camera' di kamera, langkah 'over' di pop up sukses).
        if (overlayOpen(false)) {
            var cs = this.steps[this.firstIncomplete()];
            // overlayOpen(true) = ada pop up LAIN (foto, dll) yang terbuka -> selalu sembunyikan.
            if (overlayOpen(true) || !(cs && (cs.camera || cs.over))) { this.hideUI(); return; }
        }

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
        else { this.refreshTargets(); this.sync(); }
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

        this.baseHint = forOk ? '' : (st.hint || ((st.target && !st.button) ? '👆 Ketuk bagian yang menyala biru' : ''));
        this.okMode = !!forOk;
        this.applyHint();

        this.hasExplain = !forOk && !!((st.text && st.text.length) || items.length);
        this.isInfo = !forOk && !!st.button;
        var btn = p.querySelector('[data-primary]');
        btn.textContent = st.button || '';
        btn.hidden = forOk || !st.button;
        // Teks tautan "Lewati" bisa diganti per langkah (mis. "Sudah cukup sampai di sini").
        var skipBtn = p.querySelector('[data-skip]');
        if (skipBtn) { skipBtn.textContent = (!forOk && st.skipLabel) ? st.skipLabel : 'Lewati'; }
    };

    Tour.prototype.show = function (idx) {
        var st = this.steps[idx];
        var d = this.dom;
        this.shown = idx;
        this.uiOn = true;
        this.fill(st, false);

        this.closeExplain();
        this.setMin(false);
        var bodyEl = d.panel.querySelector('[data-body]');
        if (bodyEl) { bodyEl.scrollTop = 0; }

        this.okDone[idx] = false;          // langkah tampil lagi -> pesan "sudah benar" boleh muncul lagi

        var center = !st.target;
        var tgt = qs(st.target);
        var hl = qs(st.highlight) || tgt;
        this.tgtEl = (!center && isShown(tgt)) ? tgt : null;
        this.hlEl  = (!center && isShown(hl)) ? hl : null;

        d.panel.classList.remove('is-ok');
        d.ring.classList.remove('is-ok');
        d.ring.classList.toggle('is-nodim', !!st.nodim);   // langkah 'nodim': area lain tidak digelapkan
        this.setOver(st);
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

    // Langkah yang sama bisa menyorot elemen berbeda dari waktu ke waktu (mis. kartu LO / kartu bintang
    // berikutnya yang belum terisi). Cari ulang target dan sorotan setiap siklus agar tidak menempel pada elemen lama.
    Tour.prototype.refreshTargets = function () {
        var st = this.steps[this.shown];
        if (!st || !st.target || this.okMode) { return; }
        var tgt = qs(st.target);
        var hl = qs(st.highlight) || tgt;
        this.tgtEl = isShown(tgt) ? tgt : null;
        this.hlEl  = isShown(hl) ? hl : null;
    };

    // Langkah di dalam kamera / pop up: kartu + sorotan harus berada DI ATAS lapisan itu.
    Tour.prototype.setOver = function (st) {
        var on = !!(st && (st.over || st.camera));
        this.dom.panel.classList.toggle('is-over', on);
        this.dom.ring.classList.toggle('is-over', on);
    };

    Tour.prototype.showOk = function (idx) {
        var d = this.dom;
        this.uiOn = true;
        this.setOver(this.steps[idx]);
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

    // Kotak elemen yang harus diketuk (dipakai agar kartu tutorial TIDAK menutupinya); null bila tak terlihat.
    Tour.prototype.tgtBox = function () {
        var t = this.tgtEl;
        if (!t || !isShown(t)) { return null; }
        var r = t.getBoundingClientRect();
        var top = r.top, bot = r.bottom;
        var c = qs('.content');
        if (c && c.contains(t)) {
            var cr = c.getBoundingClientRect();
            top = Math.max(top, cr.top);
            bot = Math.min(bot, cr.bottom);
        }
        if (bot - top < 4) { return null; }
        return { top: top - PAD, bottom: bot + PAD };
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
            if (st && st.minShow && st.condHint && st.done && qs(st.done) && !this.isComplete(this.shown)) {
                txt = st.condHint;                       // syarat terpenuhi, sedang disorot (minShow)
            } else if (st && st.loadHint && st.loadSel && st.done && qs(st.loadSel) && !qs(st.done)) {
                txt = st.loadHint;                       // lokasi sesuai, peta/pin masih dimuat
                waiting = true;
            } else if (st && st.needTap && this.acked[this.shown] && !this.isComplete(this.shown)) {
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
        this.applyCompact();
        this.layoutPanel();
        this.reposition();
    };

    // Kartu RINGKAS (bukan kartu tengah): hanya judul + instruksi + tombol, jadi tidak perlu digeser.
    // Langkah baca (ada tombol Mengerti) menampilkan teksnya 3 baris. Selebihnya ada di tombol "Penjelasan".
    Tour.prototype.applyCompact = function () {
        var p = this.dom.panel;
        var center = p.classList.contains('is-center');
        var ok = p.classList.contains('is-ok');
        var compact = !center && !ok;
        p.classList.toggle('is-compact', compact);
        p.classList.toggle('is-info', compact && this.isInfo);
        var btn = p.querySelector('[data-explain]');
        if (!compact) { btn.hidden = true; return; }
        var show = this.hasExplain;
        if (this.isInfo && !p.classList.contains('is-big')) {
            var t = p.querySelector('[data-text]');
            var hasList = !p.querySelector('[data-list]').hidden;
            this.needMore = hasList || (t.scrollHeight - t.clientHeight > 2);
            show = this.needMore;
        }
        btn.hidden = !show;
    };

    /* ---------- ukuran huruf (untuk pengguna lanjut usia) ---------- */
    var SCALES = [1, 1.25, 1.5];
    function readScale() {
        try { var v = parseFloat(localStorage.getItem('onefis_amt_tour_v2_scale')); return SCALES.indexOf(v) !== -1 ? v : 1; }
        catch (e) { return 1; }
    }
    Tour.prototype.applyScale = function () {
        if (!this.dom) { return; }
        var v = String(readScale());
        this.dom.panel.style.setProperty('--amtt-scale', v);
        this.dom.sheet.style.setProperty('--amtt-scale', v);
        this.dom.panel.classList.toggle('is-big', parseFloat(v) > 1);
    };
    Tour.prototype.cycleScale = function () {
        var i = SCALES.indexOf(readScale());
        var v = SCALES[(i + 1) % SCALES.length];
        try { localStorage.setItem('onefis_amt_tour_v2_scale', String(v)); } catch (e) {}
        this.applyScale();
        toast(v === 1 ? 'Ukuran huruf: normal' : (v === 1.25 ? 'Ukuran huruf: besar' : 'Ukuran huruf: sangat besar'));
        this.sync();
    };

    /* ---------- lembar baca (penjelasan lengkap) ---------- */
    Tour.prototype.openExplain = function () {
        var st = this.steps[this.shown];
        if (!st || !this.dom) { return; }
        var sh = this.dom.sheet;
        var j = this.journey;
        var label = st.label || (j && j.label) || this.labels[this.screen] || '';
        sh.querySelector('[data-s-eyebrow]').textContent = (st.no && j) ? (label + ' · Langkah ' + st.no + ' dari ' + j.total) : label;
        sh.querySelector('[data-s-title]').textContent = st.title || '';
        sh.querySelector('[data-s-text]').textContent = st.text || '';
        var ol = sh.querySelector('[data-s-list]');
        ol.innerHTML = '';
        (st.list || []).forEach(function (t) { ol.appendChild(el('li', '', '')).textContent = t; });
        ol.hidden = !(st.list && st.list.length);
        sh.querySelector('[data-s-speak]').hidden = !window.speechSynthesis;
        sh.querySelector('[data-s-speak]').textContent = '🔊 Dengarkan';
        sh.hidden = false;
        sh.querySelector('.amtt-sheet__body').scrollTop = 0;
    };
    Tour.prototype.closeExplain = function () {
        if (!this.dom) { return; }
        try { if (window.speechSynthesis) { window.speechSynthesis.cancel(); } } catch (e) {}
        this.dom.sheet.hidden = true;
    };
    // Bacakan penjelasan dengan suara (bahasa Indonesia). Ketuk lagi untuk berhenti.
    Tour.prototype.speak = function () {
        var ss = window.speechSynthesis;
        if (!ss || !this.dom) { return; }
        var btn = this.dom.sheet.querySelector('[data-s-speak]');
        if (ss.speaking) { ss.cancel(); btn.textContent = '🔊 Dengarkan'; return; }
        var sh = this.dom.sheet;
        var parts = [sh.querySelector('[data-s-title]').textContent, sh.querySelector('[data-s-text]').textContent];
        [].forEach.call(sh.querySelectorAll('[data-s-list] li'), function (li, i) { parts.push('Nomor ' + (i + 1) + '. ' + li.textContent); });
        var u = new SpeechSynthesisUtterance(parts.join('. ').replace(/[“”👆👇⏳✅]/g, ''));
        u.lang = 'id-ID';
        u.rate = 0.9;
        u.onend = u.onerror = function () { btn.textContent = '🔊 Dengarkan'; };
        btn.textContent = '⏹ Berhenti';
        ss.speak(u);
    };

    // Kecilkan / buka kartu. Saat kecil, kartu jadi pil mungil supaya bagian halaman di bawahnya bisa diketuk.
    Tour.prototype.setMin = function (on) {
        if (!this.dom) { return; }
        var p = this.dom.panel;
        p.classList.toggle('is-min', !!on);
        var b = p.querySelector('[data-min]');
        b.textContent = on ? 'Buka tutorial ▴' : 'Kecilkan ▾';
        b.setAttribute('aria-label', on ? 'Buka kartu tutorial' : 'Kecilkan kartu tutorial');
        this.sync();
    };

    // Tampilkan penanda "geser" bila isi kartu masih ada di bawah.
    Tour.prototype.updateMore = function () {
        if (!this.dom) { return; }
        var p = this.dom.panel;
        var b = p.querySelector('[data-body]');
        var more = !p.classList.contains('is-min') && (b.scrollHeight - b.scrollTop - b.clientHeight > 6);
        p.classList.toggle('has-more', more);
    };

    // Halaman TIDAK digeser. Kartu menempel di bawah bingkai aplikasi, dan
    // pindah ke atas isi halaman bila di bawah akan menutupi bagian yang disorot.
    // Isi kartu (judul + teks) bisa DIGULIR; tombol "Mengerti"/"Lewati" selalu terlihat di bawah kartu.
    // Penting: tinggi alami dihitung dari scrollHeight, TANPA mengosongkan max-height, supaya posisi gulir tidak ter-reset.
    Tour.prototype.layoutPanel = function () {
        if (!this.uiOn) { return; }
        var p = this.dom.panel;
        var body = p.querySelector('[data-body]');
        p.classList.remove('is-tight');   // dihitung ulang tiap penataan (lihat "mode ringkas" di bawah)
        if (p.classList.contains('is-center')) {
            p.style.left = p.style.width = p.style.top = p.style.bottom = p.style.right = p.style.maxHeight = '';
            this.updateMore();
            return;
        }
        var vw = window.innerWidth, vh = window.innerHeight;
        var app = qs('.app-container');
        var ab = app ? app.getBoundingClientRect() : { left: 0, right: vw, top: 0, bottom: vh };
        var c = qs('.content');
        var cr = c ? c.getBoundingClientRect() : ab;

        var left = Math.max(0, ab.left), right = Math.min(vw, ab.right);
        var botEdge = Math.min(vh, ab.bottom) - GAP;  // y batas bawah kartu
        var topEdge = Math.max(0, cr.top) + GAP;      // y batas atas kartu
        var box = this.hlBox();

        // ---- Mode kecil: pil di pojok kanan, di sisi yang jauh dari bagian yang disorot ----
        if (p.classList.contains('is-min')) {
            p.style.width = 'auto';
            p.style.left = 'auto';
            p.style.maxHeight = 'none';
            p.style.right = Math.max(GAP, vw - right + GAP) + 'px';
            var lowerHalf = box && (box.top + box.height / 2) > (topEdge + botEdge) / 2;
            if (lowerHalf) { p.style.bottom = 'auto'; p.style.top = topEdge + 'px'; this.side = 'top'; }
            else { p.style.top = 'auto'; p.style.bottom = 'calc(' + Math.max(0, vh - botEdge) + 'px + env(safe-area-inset-bottom, 0px))'; this.side = 'bottom'; }
            return;
        }

        var width = Math.min(380, right - left - 20);
        p.style.width = width + 'px';
        p.style.right = 'auto';
        p.style.left = (left + (right - left - width) / 2) + 'px';

        var cap = Math.floor(vh * 0.58);                                   // batas tinggi bawaan
        var chrome = p.offsetHeight - body.clientHeight;                   // tinggi di luar isi (kepala, bilah, tombol)
        var natural = chrome + body.scrollHeight;                          // tinggi sesuai isi saat ini
        // Isi kartu (judul + teks) SELALU diberi tinggi minimal, supaya teks tutorial tidak tertekan
        // sampai tak terbaca walau ruang di sekitar bagian yang disorot sempit.
        var minLimit = Math.min(cap, chrome + Math.min(body.scrollHeight, 120));
        var limit = cap;
        var ph = Math.min(natural, limit);
        var side = 'bottom';
        var tb = this.tgtBox();                                            // bagian yang harus diketuk

        if (box || tb) {
            var fitB = function () { return (!box || botEdge - ph >= box.bottom + 4) && (!tb || botEdge - ph >= tb.bottom + 4); };
            var fitT = function () { return (!box || topEdge + ph <= box.top - 4) && (!tb || topEdge + ph <= tb.top - 4); };
            var okB = fitB(), okT = fitT();

            // MODE RINGKAS: bila kartu penuh tidak muat di atas maupun di bawah bagian yang disorot (mis. pop up
            // besar), kartu dikecilkan (tanpa bilah & teks panjang, tombol lebih rendah) supaya TIDAK menutupi
            // judul / isi pop up. Penjelasan lengkap tetap ada di tombol "Penjelasan".
            if (!okB && !okT) {
                p.classList.add('is-tight');
                chrome  = p.offsetHeight - body.clientHeight;
                natural = chrome + body.scrollHeight;
                ph = Math.min(natural, cap);
                okB = fitB(); okT = fitT();
            }

            if (this.side === 'top' && okT)             { side = 'top'; }      // bertahan agar tidak berkedip
            else if (this.side === 'bottom' && okB)     { side = 'bottom'; }
            else if (okB)                               { side = 'bottom'; }
            else if (okT)                               { side = 'top'; }
            else {
                // Tidak ada sisi yang muat tanpa menimpa sorotan. Utamakan JANGAN menutupi bagian yang
                // harus diketuk (mis. tombol "Tutup" di pop up QR); menimpa sorotan yang luas masih wajar.
                var ref = tb || box;
                var roomBottom = botEdge - ref.bottom - 4;
                var roomTop    = ref.top - 4 - topEdge;
                side = roomBottom >= roomTop ? 'bottom' : 'top';
                minLimit = Math.min(cap, chrome + Math.min(body.scrollHeight, 60));
                limit = Math.max(minLimit, Math.min(cap, Math.floor(side === 'bottom' ? roomBottom : roomTop)));
            }
        }
        this.side = side;
        p.style.maxHeight = limit + 'px';

        if (side === 'bottom') {
            p.style.top = 'auto';
            p.style.bottom = 'calc(' + Math.max(0, vh - botEdge) + 'px + env(safe-area-inset-bottom, 0px))';
        } else {
            p.style.bottom = 'auto';
            p.style.top = topEdge + 'px';
        }
        this.updateMore();
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
        try { if (window.speechSynthesis) { window.speechSynthesis.cancel(); } } catch (e) {}
        clearInterval(this.tickTimer);
        this.timers.forEach(clearTimeout);
        this.handlers.forEach(function (h) { h.t.removeEventListener(h.e, h.f, h.c); });
        this.handlers = [];
        document.body.classList.remove('amtt-on');
        if (this.dom) {
            [this.dom.dim, this.dom.ring, this.dom.panel, this.dom.sheet].forEach(function (n) {
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
            syncFresh(config.fresh);
            if (forceRestart) { resetAll(); }

            // Tutorial dimatikan ("Lewati" / sudah dipakai sampai selesai): tampilkan HANYA langkah bertanda 'notice'
            // (pemberitahuan DCU, setelah PTI, pengembalian segel). Selebihnya tidak muncul otomatis.
            var cfg = config;
            if (!forceRestart && isOff()) {
                cfg = {};
                Object.keys(config).forEach(function (k) { cfg[k] = config[k]; });
                // Bila ada langkah 'restart' (kartu ucapan selamat), simpan SEMUA langkah: setelah "Mulai"
                // tutorial otomatis menyala lagi dan lanjut ke langkah berikutnya.
                var keepAll = (config.steps || []).some(function (st) { return st && st.restart; });
                cfg.steps = keepAll ? config.steps : (config.steps || []).filter(function (st) { return st && st.notice; });
            }
            activeTour = new Tour(cfg);
            if (cfg.steps && cfg.steps.length) {
                activeTour.start(forceRestart);
            } else if (!forceRestart && (config.steps || []).some(function (st) { return st && st.announce; })) {
                // Halaman menunggu kabar "pemberitahuan sudah ditutup" untuk menyalakan menu berikutnya -> kabari.
                setTimeout(function () { announce(config.subKey || config.screen, { skipped: true }); }, 400);
            }
            // Halaman dipulihkan dari cache (tombol Kembali) / status dimatikan dari tab lain: periksa ulang.
            window.addEventListener('pageshow', function (e) { if (e.persisted && activeTour) { activeTour.tick(); } });
            window.addEventListener('storage', function (e) { if (e.key === LS_OFF && activeTour) { activeTour.tick(); } });
            document.addEventListener('visibilitychange', function () { if (!document.hidden && activeTour) { activeTour.tick(); } });

            // Tombol "?": mulai lagi tutorial HALAMAN INI (tidak pindah halaman).
            if (config.screen !== 'role_select') {
                var fab = el('button', 'amtt-fab');
                fab.type = 'button';
                fab.setAttribute('aria-label', 'Tampilkan tutorial halaman ini');
                fab.textContent = '?';
                fab.addEventListener('click', function () {
                    // "?" = nyalakan lagi tutorial: status "dimatikan" (Lewati / selesai) dan semua catatan
                    // "dilewati" dihapus, jadi tutorial juga muncul otomatis di layar berikutnya
                    // dan jalan terus sampai alurnya selesai.
                    clearOff();
                    clearSkips();
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
        reset: function () { resetAll(); clearOff(); },
        // true bila tutorial layar ini sedang berjalan (kartu tampil / akan tampil). Dipakai layar yang
        // waktunya bergantung pada mode tutorial (mis. Pindai Kode QR).
        isActive: function () { return !!(activeTour && activeTour.dom && !activeTour.dead); },
        // Halaman lain boleh meminta pemeriksaan ulang keadaan.
        rescan: function () { if (activeTour) { activeTour.tick(); } }
    };

})(window, document);