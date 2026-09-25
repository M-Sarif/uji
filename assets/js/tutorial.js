/**
 * OneFIS Guided Tour — tutorial "cara pakai" ala first-time-play game
 * (mis. Clash of Clans): menyorot elemen ASLI di halaman satu per satu,
 * mengikuti urutan pada dokumen panduan "Aktifitas di SPBU".
 *
 * Dipasang lewat window.OneFISTour.init(config) di includes/layout_bottom.php.
 * config = {
 *   screen: 'shipment',
 *   steps: [ { target, title, text, place, dynamic } ... ],   // dari TOUR_STEPS[screen]
 *   screenOrder: [ 'dashboard', 'shipments_list', ... ],
 *   screenLabels: { dashboard: 'Beranda', ... },
 *   screenIndex: 2 // posisi $screen di screenOrder (1-based), 0 kalau di luar alur
 * }
 */
(function (window, document) {
    'use strict';

    var LS_SEEN    = 'onefis_tour_seen_screens'; // array layar yang tutorialnya sudah pernah selesai ditonton
    var LS_SKIPPED = 'onefis_tour_skipped';       // '1' kalau user memilih "Lewati Tutorial"

    // Label dinamis untuk step { dynamic: true } pada halaman "shipment"
    // (menyorot langkah aktivitas yang sedang aktif di timeline Aktifitas).
    var DYNAMIC_LABELS = {
        'Tiba di Lokasi': {
            title: 'Langkah 3 · Tiba di Lokasi',
            text: 'Mobil tangki sudah tiba dan dikonfirmasi oleh AMT. Ketuk "Tiba di Lokasi" untuk memulai proses verifikasi Mobil Tangki dan AMT.',
        },
        'Isi Checklist': {
            title: 'Langkah 6 · Isi Checklist',
            text: 'Verifikasi MT dan AMT sudah selesai. Ketuk "Isi Checklist" untuk memulai Checklist Pra-Pembongkaran.',
        },
        'Verifikasi Order': {
            title: 'Langkah 8 · Verifikasi Order',
            text: 'Checklist Pra-Pembongkaran sudah selesai. Ketuk "Verifikasi Order" untuk membuka notifikasi permintaan verifikasi dari AMT.',
        },
        'Rating Petugas AMT': {
            title: 'Langkah 10 · Rating Petugas AMT',
            text: 'Verifikasi order sudah selesai. Ketuk "Rating Petugas AMT" untuk menilai pelayanan AMT yang bertugas.',
        },
    };

    function readSeen() {
        try { return JSON.parse(localStorage.getItem(LS_SEEN) || '[]'); } catch (e) { return []; }
    }
    function markSeen(screen) {
        var seen = readSeen();
        if (seen.indexOf(screen) === -1) {
            seen.push(screen);
            try { localStorage.setItem(LS_SEEN, JSON.stringify(seen)); } catch (e) {}
        }
    }
    // Dipanggil HANYA saat request ini baru saja mereset progres alur
    // (pengguna balik ke dashboard/beranda AMT — lihat index.php &
    // reset_flow_keep_work()). Layar-layar alur setelah dashboard jadi
    // dianggap "belum pernah dilihat" lagi, supaya tutorialnya muncul
    // ulang begitu pengguna masuk lagi ke alur itu dari awal.
    // Dashboard/beranda sendiri TIDAK ikut dihapus (tidak perlu tampil
    // ulang tiap kali sekadar balik ke halaman utama). "Lewati Tutorial"
    // yang eksplisit tetap dihormati (lihat isSkipped() di start()).
    function clearSeenForFlow(currentScreen, screenOrder) {
        var seen = readSeen();
        var kept = seen.filter(function (s) {
            return s === currentScreen || screenOrder.indexOf(s) === -1;
        });
        try { localStorage.setItem(LS_SEEN, JSON.stringify(kept)); } catch (e) {}
        screenOrder.forEach(function (s) {
            if (s !== currentScreen) { clearStepPos(s); }
        });
    }

    function isSkipped() {
        try { return localStorage.getItem(LS_SKIPPED) === '1'; } catch (e) { return false; }
    }
    function setSkipped(val) {
        try { localStorage.setItem(LS_SKIPPED, val ? '1' : '0'); } catch (e) {}
    }
    function resetAll() {
        try {
            localStorage.removeItem(LS_SEEN);
            localStorage.setItem(LS_SKIPPED, '0');
            Object.keys(sessionStorage).forEach(function (k) {
                if (k.indexOf('onefis_tour_step:') === 0) { sessionStorage.removeItem(k); }
            });
        } catch (e) {}
    }

    // Sebagian langkah (mis. "pilih LO") memuat ulang HALAMAN YANG SAMA
    // (link biasa, bukan SPA). Supaya tutorial tidak mengulang dari awal
    // tiap kali halaman itu dimuat ulang, posisi langkah per-layar
    // disimpan di sessionStorage (hilang sendiri saat tab ditutup).
    function readStepPos(screen) {
        try { return parseInt(sessionStorage.getItem('onefis_tour_step:' + screen) || '0', 10) || 0; } catch (e) { return 0; }
    }
    function saveStepPos(screen, idx) {
        try { sessionStorage.setItem('onefis_tour_step:' + screen, String(idx)); } catch (e) {}
    }
    function clearStepPos(screen) {
        try { sessionStorage.removeItem('onefis_tour_step:' + screen); } catch (e) {}
    }

    function isVisible(el) {
        if (!el) { return false; }
        if (el.hidden) { return false; }
        var anc = el;
        while (anc) {
            if (anc.hidden) { return false; }
            anc = anc.parentElement;
        }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function Tour(config) {
        this.screen       = config.screen;
        this.rawSteps     = (config.steps || []);
        this.screenOrder  = config.screenOrder || [];
        this.screenLabels = config.screenLabels || {};
        this.screenIndex  = config.screenIndex || 0;
        this.stepIndex    = 0;
        this.els          = {};
        this.waitTimer    = null;
        this.waitDeadline = 0;
        this._onReflow    = this._reflow.bind(this);
    }

    Tour.prototype.start = function (force) {
        if (!this.rawSteps.length) { return; }
        if (!force && (isSkipped() || readSeen().indexOf(this.screen) !== -1)) { return; }
        var savedPos = force ? 0 : readStepPos(this.screen);
        this.stepIndex = (savedPos >= 0 && savedPos < this.rawSteps.length) ? savedPos : 0;
        this._buildOverlay();
        this._runStep();
    };

    Tour.prototype.rescan = function () {
        // Dipanggil dari halaman saat DOM berubah (mis. "Tiba di Lokasi" terbuka
        // otomatis setelah timer). Kalau tutorial sedang menunggu target ini,
        // langsung coba tampilkan.
        if (this._waitingTarget) { this._tryShowCurrent(); }
    };

    Tour.prototype._buildOverlay = function () {
        var backdrop = document.createElement('div');
        backdrop.className = 'tour-backdrop';
        document.body.appendChild(backdrop);

        var panels = {};
        ['top', 'bottom', 'left', 'right'].forEach(function (side) {
            var p = document.createElement('div');
            p.className = 'tour-mask-panel';
            document.body.appendChild(p);
            panels[side] = p;
        });

        var ring = document.createElement('div');
        ring.className = 'tour-highlight-ring';
        ring.style.display = 'none';
        document.body.appendChild(ring);

        var tooltip = document.createElement('div');
        tooltip.className = 'tour-tooltip';
        tooltip.innerHTML =
            '<div class="tour-arrow bottom" data-tour-arrow></div>' +
            '<div class="tour-eyebrow"><span data-tour-progress></span></div>' +
            '<h3 class="tour-title" data-tour-title></h3>' +
            '<p class="tour-text" data-tour-text></p>' +
            '<p class="tour-hint" data-tour-hint hidden></p>' +
            '<div class="tour-footer">' +
            '  <button type="button" class="tour-skip" data-tour-skip>Lewati Tutorial</button>' +
            '</div>';
        document.body.appendChild(tooltip);

        this.els = { backdrop: backdrop, panels: panels, ring: ring, tooltip: tooltip };

        var self = this;
        tooltip.querySelector('[data-tour-skip]').addEventListener('click', function () {
            setSkipped(true);
            self._destroy();
        });
        // Langkah pembuka/penutup (tanpa target elemen) tidak punya tombol
        // biru sama sekali - ketuk di mana saja pada layar gelap untuk lanjut.
        backdrop.addEventListener('click', function () {
            if (!self._currentEl) { self._next(); }
        });

        window.addEventListener('resize', this._onReflow);
        window.addEventListener('scroll', this._onReflow, true);
    };

    Tour.prototype._reflow = function () {
        if (this._currentEl) { this._positionOn(this._currentEl, this._currentPlace); }
    };

    Tour.prototype._runStep = function () {
        clearTimeout(this.waitTimer);
        this._waitingTarget = null;

        if (this.stepIndex >= this.rawSteps.length) {
            markSeen(this.screen);
            clearStepPos(this.screen);
            this._destroy();
            return;
        }
        saveStepPos(this.screen, this.stepIndex);
        this._tryShowCurrent();
    };

    Tour.prototype._tryShowCurrent = function () {
        var step = this.rawSteps[this.stepIndex];
        if (!step) { return; }

        // Jeda sebelum langkah ini tampil (mis. supaya pengguna sempat
        // melihat halaman barunya dulu, baru kotak tutorial muncul).
        // Ditandai per-index supaya jedanya cuma dipakai sekali, tidak
        // mengulang tiap kali _tryShowCurrent dipanggil ulang (mis. saat
        // menunggu elemen dinamis muncul).
        if (step.delayMs && this._delayedForIndex !== this.stepIndex) {
            this._delayedForIndex = this.stepIndex;
            this.els.backdrop.classList.remove('is-visible');
            this.els.tooltip.classList.remove('is-visible');
            var self2 = this;
            this.waitTimer = setTimeout(function () { self2._tryShowCurrent(); }, step.delayMs);
            return;
        }

        // Langkah pembuka/penutup tanpa target (mis. "Selamat Datang", "Selesai!")
        if (!step.target) {
            this._waitingTarget = null;
            this._showCentered(step);
            return;
        }

        var el = document.querySelector(step.target);
        if (el && isVisible(el)) {
            this._waitingTarget = null;
            this._showOn(el, step);
            return;
        }

        // Elemen belum ada / masih tersembunyi (mis. tombol di dalam pop up
        // yang baru muncul otomatis setelah 5 detik) — tunggu, jangan
        // menutupi layar dengan spotlight yang menunjuk ke tempat kosong.
        this.els.backdrop.classList.remove('is-visible');
        this.els.tooltip.classList.remove('is-visible');
        this._waitingTarget = step.target;
        if (!this.waitDeadline) { this.waitDeadline = Date.now() + 20000; }

        var self = this;
        this.waitTimer = setTimeout(function () {
            if (Date.now() > self.waitDeadline) {
                self.waitDeadline = 0;
                self._advanceSkippingHidden();
                return;
            }
            self._tryShowCurrent();
        }, 400);
    };

    Tour.prototype._advanceSkippingHidden = function () {
        this.stepIndex++;
        this._runStep();
    };

    Tour.prototype._next = function () {
        this.waitDeadline = 0;
        this.stepIndex++;
        this._runStep();
    };

    Tour.prototype._detachElListener = function () {
        if (this._elListenerEl && this._elClickHandler) {
            this._elListenerEl.removeEventListener('click', this._elClickHandler, true);
        }
        this._elListenerEl   = null;
        this._elClickHandler = null;
    };

    Tour.prototype._showCentered = function (step) {
        this._detachElListener();
        this.els.backdrop.classList.add('is-visible');
        Object.keys(this.els.panels).forEach(function (k) {
            this.els.panels[k].style.display = 'none';
        }, this);
        this.els.ring.style.display = 'none';
        this._currentEl = null;

        this.els.backdrop.classList.add('is-blocking');

        var tt = this.els.tooltip;
        tt.className = 'tour-tooltip place-center';
        this._fillTooltip(step);

        var hint = tt.querySelector('[data-tour-hint]');
        hint.textContent = 'Ketuk di mana saja untuk melanjutkan';
        hint.hidden = false;

        requestAnimationFrame(function () { tt.classList.add('is-visible'); });
    };

    Tour.prototype._showOn = function (el, step) {
        this._detachElListener();
        this._currentEl = el;
        this._currentPlace = step.place || 'bottom';
        // Bukan backdrop penuh layar di sini - "lubang terang" dibentuk oleh
        // 4 panel di _positionOn yang mengelilingi elemen. Kalau backdrop
        // penuh layar ikut dinyalakan, ia menutupi elemen yang disorot juga
        // (warnanya jadi gelap padahal seharusnya tampil asli).
        this.els.backdrop.classList.remove('is-visible');
        this.els.backdrop.classList.remove('is-blocking'); // elemen lain di layar tetap bisa dipencet
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });

        var self = this;
        var startStepIndex = this.stepIndex;
        // beri sedikit waktu untuk smooth-scroll sebelum menghitung posisi
        setTimeout(function () { self._positionOn(el, self._currentPlace); }, 220);

        this._fillTooltip(step, el);
        // Step dinamis bisa membatalkan diri sendiri & lompat ke step
        // berikutnya (lihat _fillTooltip) - kalau itu terjadi, hentikan di sini.
        if (this.stepIndex !== startStepIndex) { return; }

        var tt = this.els.tooltip;
        tt.className = 'tour-tooltip place-' + (step.place || 'bottom');

        // Tidak ada tombol biru sama sekali di sini: petunjuk hilang begitu
        // pengguna benar-benar mengetuk elemen asli yang disorot.
        var hint = tt.querySelector('[data-tour-hint]');
        hint.textContent = '👉 Ketuk elemen yang bersinar untuk melanjutkan';
        hint.hidden = false;

        this._elClickHandler = function () { self._next(); };
        this._elListenerEl   = el;
        el.addEventListener('click', this._elClickHandler, true);

        requestAnimationFrame(function () { tt.classList.add('is-visible'); });
    };

    Tour.prototype._fillTooltip = function (step, el) {
        var label = step;
        if (step.dynamic && el) {
            var key = el.getAttribute('data-tour-label') || '';
            label = DYNAMIC_LABELS[key] || { title: key, text: '' };
            if (!label.text) {
                // Skip langkah dinamis kalau labelnya tidak dikenali (aman untuk masa depan)
                this._advanceSkippingHidden();
                return;
            }
        }
        var tt = this.els.tooltip;
        var screenLabel = this.screenLabels[this.screen] || '';
        var progress = (this.screenIndex ? 'Bagian ' + this.screenIndex + '/' + this.screenOrder.length + ' · ' + screenLabel : screenLabel);
        tt.querySelector('[data-tour-progress]').textContent = progress;
        tt.querySelector('[data-tour-title]').textContent = label.title || '';
        tt.querySelector('[data-tour-text]').textContent = label.text || '';
    };

    Tour.prototype._positionOn = function (el, place) {
        var rect = el.getBoundingClientRect();
        var pad  = 8;
        var vw   = window.innerWidth;
        var vh   = window.innerHeight;
        var panels = this.els.panels;

        panels.top.style.cssText    = 'display:block;left:0;top:0;width:' + vw + 'px;height:' + Math.max(0, rect.top - pad) + 'px;';
        panels.bottom.style.cssText = 'display:block;left:0;top:' + (rect.bottom + pad) + 'px;width:' + vw + 'px;height:' + Math.max(0, vh - rect.bottom - pad) + 'px;';
        panels.left.style.cssText   = 'display:block;left:0;top:' + (rect.top - pad) + 'px;width:' + Math.max(0, rect.left - pad) + 'px;height:' + (rect.height + pad * 2) + 'px;';
        panels.right.style.cssText  = 'display:block;left:' + (rect.right + pad) + 'px;top:' + (rect.top - pad) + 'px;width:' + Math.max(0, vw - rect.right - pad) + 'px;height:' + (rect.height + pad * 2) + 'px;';

        var ring = this.els.ring;
        ring.style.display = 'block';
        ring.style.left   = (rect.left - pad) + 'px';
        ring.style.top    = (rect.top - pad) + 'px';
        ring.style.width  = (rect.width + pad * 2) + 'px';
        ring.style.height = (rect.height + pad * 2) + 'px';

        var tt = this.els.tooltip;
        var arrow = tt.querySelector('[data-tour-arrow]');
        var ttW = 320;
        var ttH = 190; // perkiraan tinggi tooltip, dipakai untuk cek muat/tidaknya di atas/bawah elemen
        var gap = 14;
        var top, left, place2 = place;

        // Elemen dekat tepi atas/bawah layar -> tooltip tidak akan muat di
        // sisi yang diminta ('top'/'bottom'), pindahkan ke sisi sebaliknya
        // supaya tidak terpotong keluar layar (lihat juga clamp horizontal
        // di bawah untuk sisi kiri/kanan).
        if (place2 === 'top' && (rect.top - gap - ttH) < 8) {
            place2 = 'bottom';
        } else if (place2 === 'bottom' && (rect.bottom + gap + ttH) > (vh - 8)) {
            place2 = 'top';
        }

        if (place2 === 'top') {
            top  = rect.top - gap;
            left = rect.left + rect.width / 2;
            tt.style.transform = 'translate(-50%, calc(-100% - ' + gap + 'px))';
        } else if (place2 === 'left') {
            top  = rect.top + rect.height / 2;
            left = rect.left - gap;
            tt.style.transform = 'translate(calc(-100% - ' + gap + 'px), -50%)';
        } else if (place2 === 'right') {
            top  = rect.top + rect.height / 2;
            left = rect.right + gap;
            tt.style.transform = 'translate(0, -50%)';
        } else { // bottom (default)
            top  = rect.bottom + gap;
            left = rect.left + rect.width / 2;
            tt.style.transform = 'translate(-50%, 0)';
        }

        // jaga agar tetap di dalam layar secara horizontal
        var estLeft = left;
        if (place2 === 'top' || place2 === 'bottom') {
            var half = Math.min(ttW, vw - 32) / 2;
            estLeft = Math.min(Math.max(left, half + 16), vw - half - 16);
        }

        tt.style.top  = top + 'px';
        tt.style.left = estLeft + 'px';
        arrow.className = 'tour-arrow ' + (place2 === 'top' ? 'bottom' : place2 === 'bottom' ? 'top' : place2 === 'left' ? 'right' : 'left');
    };

    Tour.prototype._destroy = function () {
        clearTimeout(this.waitTimer);
        this._detachElListener();
        window.removeEventListener('resize', this._onReflow);
        window.removeEventListener('scroll', this._onReflow, true);
        var els = this.els;
        if (!els.backdrop) { return; }
        [els.backdrop, els.panels.top, els.panels.bottom, els.panels.left, els.panels.right, els.ring, els.tooltip]
            .forEach(function (n) { if (n && n.parentNode) { n.parentNode.removeChild(n); } });
        this.els = {};
    };

    var activeTour = null;

    window.OneFISTour = {
        init: function (config) {
            var params = new URLSearchParams(window.location.search);
            var forceRestart = params.get('tour') === 'restart';
            if (forceRestart) { resetAll(); }

            // Alur baru saja direset di server (balik ke dashboard/beranda
            // AMT) -> lupakan tutorial layar-layar alur berikutnya supaya
            // tampil lagi saat dilalui ulang, TANPA mengganggu status
            // dashboard sendiri maupun layar di luar alur ini.
            if (!forceRestart && config.flowWasReset) {
                clearSeenForFlow(config.screen, config.screenOrder || []);
            }

            activeTour = new Tour(config);

            activeTour.start(forceRestart);

            // Tombol bantuan "?" mengambang: mulai ulang seluruh tutorial dari Beranda
            if (config.screen !== 'role_select') {
                var fab = document.createElement('button');
                fab.type = 'button';
                fab.className = 'tour-help-fab';
                fab.setAttribute('aria-label', 'Mulai ulang tutorial');
                fab.textContent = '?';
                fab.addEventListener('click', function () {
                    resetAll();
                    window.location.href = 'index.php?screen=dashboard&tour=restart';
                });
                document.body.appendChild(fab);
            }
        },
        rescan: function () {
            if (activeTour) { activeTour.rescan(); }
        },
    };
})(window, document);