/* ============================================================
 * OneFIS - AMT - mesin tutorial KHUSUS peran AMT.
 * Salinan dari assets/js/tutorial.js (mesin SPBU) supaya perubahan
 * tutorial AMT tidak pernah memengaruhi tutorial SPBU. Bedanya:
 *   - kunci localStorage/sessionStorage terpisah (onefis_amt_tour_*)
 *   - opsi langkah 'noClickAdvance' (lanjut hanya lewat 'alsoAdvanceOn')
 * Dimuat oleh includes/layout_bottom.php hanya saat peran = AMT.
 * ============================================================ */
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
 *   screenIndex: 2, // posisi $screen di screenOrder (1-based), 0 kalau di luar alur
 *   subKey: null,   // opsional: kunci pelacakan "sudah ditonton" + posisi
 *                    // langkah PENGGANTI `screen` -- dipakai untuk sub-tutorial
 *                    // yang harus dilacak terpisah dari tutorial umum layar yang
 *                    // sama (mis. 'checklist_soal6' pada layar 'checklist').
 * }
 */
(function (window, document) {
    'use strict';

    var AMT_STEP_DELAY_MS = 2000;   // jeda sebelum tiap langkah tutorial AMT muncul
    var LS_SEEN    = 'onefis_amt_tour_seen_screens'; // array layar yang tutorialnya sudah pernah selesai ditonton
    var LS_SKIPPED = 'onefis_amt_tour_skipped';       // '1' kalau user memilih "Lewati Tutorial"

    // Kalimat petunjuk aksi -- dibuat sederhana untuk pengguna awam.
    var HINT_TAP   = '👉 Ketuk bagian yang menyala biru untuk lanjut';
    var HINT_READY = '👉 Sudah siap! Sekarang ketuk tombol yang menyala biru';
    var HINT_ANY   = '👉 Ketuk di mana saja untuk lanjut';

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
        'Semua Selesai': {
            title: 'Selesai! 🎉',
            text: 'Semua aktifitas di SPBU sudah selesai: Tiba di Lokasi, Isi Checklist, Verifikasi Order, dan Rating Petugas AMT. Serah terima order BBM tuntas — tidak ada langkah yang tersisa.',
            // Tutorial terakhir: tertutup sendiri setelah 7 detik.
            autoDismissMs: 7000,
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
                if (k.indexOf('onefis_amt_tour_step:') === 0) { sessionStorage.removeItem(k); }
            });
        } catch (e) {}
    }

    // Sebagian langkah (mis. "pilih LO") memuat ulang HALAMAN YANG SAMA
    // (link biasa, bukan SPA). Supaya tutorial tidak mengulang dari awal
    // tiap kali halaman itu dimuat ulang, posisi langkah per-layar
    // disimpan di sessionStorage (hilang sendiri saat tab ditutup).
    function readStepPos(screen) {
        try { return parseInt(sessionStorage.getItem('onefis_amt_tour_step:' + screen) || '0', 10) || 0; } catch (e) { return 0; }
    }
    function saveStepPos(screen, idx) {
        try { sessionStorage.setItem('onefis_amt_tour_step:' + screen, String(idx)); } catch (e) {}
    }
    function clearStepPos(screen) {
        try { sessionStorage.removeItem('onefis_amt_tour_step:' + screen); } catch (e) {}
    }

    function isVisible(el) {
        if (!el) { return false; }
        if (el.hidden) { return false; }
        // Tombol yang masih "disabled" (mis. "Simpan" sebelum ketiga
        // pertanyaan dijawab) belum boleh disorot - tunggu sampai
        // pengguna selesai mengisi data dan tombolnya aktif.
        if (el.disabled) { return false; }
        var anc = el;
        while (anc) {
            if (anc.hidden) { return false; }
            anc = anc.parentElement;
        }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    // Sama seperti isVisible(), TAPI TIDAK peduli apakah 'el' sedang
    // "disabled" atau tidak. Dipakai KHUSUS untuk langkah yang punya
    // 'highlight' terpisah dari 'target' (mis. "Isi Form Pengukuran" pada
    // Soal 7: 'target'-nya tombol "Generate" yang MEMANG sengaja disabled
    // sampai semua kolom form terisi, sementara 'highlight'-nya adalah
    // SELURUH kartu form/.claim-form). Untuk langkah semacam ini, kotak
    // sorotan hijaunya harus tetap muncul SEJAK AWAL (menyorot form yang
    // masih kosong, mengarahkan pengguna mengisinya) -- bukan baru muncul
    // di akhir setelah tombolnya aktif. Klik tetap tidak akan pernah benar-
    // benar terjadi selama tombolnya disabled (browser menahannya sendiri),
    // jadi tutorial tetap baru lanjut begitu pengguna benar-benar menekan
    // tombolnya setelah aktif.
    function isVisibleIgnoringDisabled(el) {
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

    // Menentukan apakah suatu langkah harus ditampilkan "diam-diam" (lihat
    // _showQuiet): entah lewat 'quietIf' (selector sederhana - diam kalau
    // DITEMUKAN), atau 'quietIfMin' (diam kalau suatu atribut data- pada
    // elemen tsb sudah mencapai angka minimum tertentu, dipakai untuk
    // hint "Lengkapi Sisa Segel" yang cuma boleh tampil TEPAT SATU KALI:
    // saat baru 1 Segel yang terisi, bukan lagi saat sudah 2 atau lebih).
    function isQuietStep(step) {
        if (step.quietIf && document.querySelector(step.quietIf)) {
            return true;
        }
        if (step.quietIfMin) {
            var cfg = step.quietIfMin;
            var cfgEl = document.querySelector(cfg.selector);
            if (cfgEl) {
                var val = parseInt(cfgEl.getAttribute(cfg.attr) || '0', 10) || 0;
                if (val >= cfg.min) { return true; }
            }
        }
        return false;
    }

    function Tour(config) {
        this.screen       = config.screen;
        // posKey = kunci untuk melacak status "sudah ditonton" (LS_SEEN)
        // dan posisi langkah (sessionStorage) tutorial ini. Biasanya sama
        // dengan nama layar, TAPI kalau config.subKey diisi (mis. tutorial
        // khusus Soal 6 pada layar 'checklist'), pakai itu -- supaya
        // sub-tutorial ini dilacak TERPISAH dari tutorial umum layar yang
        // sama dan tetap tampil pertama kali dicapai walau tutorial umum
        // layar itu sudah pernah ditonton.
        this.posKey       = config.subKey || config.screen;
        // [AMT] Semua langkah muncul setelah jeda AMT_STEP_DELAY_MS (2 detik) supaya
        // pengguna sempat melihat halamannya dulu -- tutorial yang langsung muncul
        // membingungkan. Langkah bisa menimpanya lewat 'showAfter' sendiri.
        this.rawSteps     = (config.steps || []).map(function (st) {
            return (st.showAfter === undefined) ? Object.assign({}, st, { showAfter: AMT_STEP_DELAY_MS }) : st;
        });
        this.screenOrder  = config.screenOrder || [];
        this.screenLabels = config.screenLabels || {};
        this.screenIndex  = config.screenIndex || 0;
        this.stepIndex    = 0;
        // true = tutorial layar ini SELALU mulai dari langkah pertama tiap kali
        // layar dibuka (abaikan posisi tersimpan & status "sudah ditonton").
        // Dipakai untuk layar simulasi bertimer (Notifikasi, Kode QR) yang
        // isinya baru muncul beberapa detik setelah dibuka dan dikunjungi
        // ulang tiap alur dijalankan.
        this.restartEveryVisit = !!config.restartEveryVisit;
        this.els          = {};
        this.waitTimer    = null;
        this.waitDeadline = 0;
        this.delayTimer   = null;
        this._delayedForTarget = null;
        this._onReflow    = this._reflow.bind(this);
    }

    Tour.prototype.start = function (force) {
        if (!this.rawSteps.length) { return; }
        if (!force && isSkipped()) { return; }

        // Layar ini bisa dikunjungi BERKALI-KALI dengan status berbeda tiap
        // kali (mis. halaman Detail Order: pertama kali menunjukkan "Tiba
        // di Lokasi", lalu nanti kembali lagi dengan status "Isi Checklist",
        // "Verifikasi Order", dst - lewat langkah { dynamic: true }). Kalau
        // cuma nama layarnya saja yang dicatat sebagai "sudah ditonton",
        // begitu status PERTAMA selesai ditonton, tutorial tidak akan pernah
        // muncul lagi untuk status-status berikutnya di layar yang sama.
        // Jadi untuk layar yang punya langkah dinamis, kita catat per
        // LABEL aktivitasnya masing-masing (mis. "shipment::Isi Checklist"),
        // bukan cuma "shipment" saja.
        var dynIdx = -1;
        for (var i = 0; i < this.rawSteps.length; i++) {
            if (this.rawSteps[i].dynamic) { dynIdx = i; break; }
        }
        var dynLabel = null;
        if (dynIdx !== -1) {
            var dEl = document.querySelector(this.rawSteps[dynIdx].target);
            dynLabel = dEl ? (dEl.getAttribute('data-tour-label') || null) : null;
        }
        this._seenKey = (dynIdx !== -1 && dynLabel) ? (this.posKey + '::' + dynLabel) : this.posKey;

        var seen = readSeen();
        if (!force && !this.restartEveryVisit && seen.indexOf(this._seenKey) !== -1) { return; }

        // [AMT] Form AMT tidak pernah dimuat ulang di tengah tutorial, jadi TIDAK ada
        // gunanya melanjutkan dari langkah tersimpan (dulu membuat Check-In langsung
        // membuka langkah Aktivitas). Selalu mulai dari langkah pertama.
        var savedPos = 0;
        var startPos = (savedPos >= 0 && savedPos < this.rawSteps.length) ? savedPos : 0;

        // Kalau bagian AWAL/statis layar ini (mis. penjelasan "Tab Aktifitas")
        // sudah pernah ditonton sebelumnya, tapi status dinamis SEKARANG ini
        // yang baru ("Isi Checklist", dst) belum - langsung loncat ke langkah
        // dinamisnya saja, tidak perlu mengulang penjelasan dari awal lagi.
        if (!force && dynIdx !== -1 && startPos < dynIdx && seen.indexOf(this.posKey) !== -1) {
            startPos = dynIdx;
        }

        this.stepIndex = this._normalizeStart(startPos);
        this._buildOverlay();
        this._runStep();
    };

    // Merapikan posisi langkah SAAT HALAMAN DIMUAT (bukan saat lanjut biasa):
    //  1. 'screen' (opsional per langkah): langkah hanya boleh tampil di layar
    //     tsb (mis. 'checklist' / 'claim_loss'). Kalau posisi tersimpan milik
    //     layar lain, geser ke langkah terdekat (maju dulu, baru mundur)
    //     yang cocok dengan layar sekarang.
    //  2. 'gotoOnLoad' (opsional): [{ ifPresent | ifAbsent: selector, to: n }]
    //     -> kalau kondisinya terpenuhi, loncat ke langkah n. Dipakai supaya
    //     tutorial selalu sinkron dengan keadaan halaman yang sebenarnya
    //     (mis. LO kedua belum diisi -> tetap diarahkan ke LO tsb).
    Tour.prototype._normalizeStart = function (pos) {
        var steps = this.rawSteps;
        for (var guard = 0; guard < 8; guard++) {
            var moved = false;
            var st = steps[pos];
            if (!st) { break; }

            if (st.screen && st.screen !== this.screen) {
                var found = -1, i;
                for (i = pos + 1; i < steps.length; i++) {
                    if (!steps[i].screen || steps[i].screen === this.screen) { found = i; break; }
                }
                if (found === -1) {
                    for (i = pos - 1; i >= 0; i--) {
                        if (!steps[i].screen || steps[i].screen === this.screen) { found = i; break; }
                    }
                }
                if (found !== -1 && found !== pos) { pos = found; moved = true; }
            }

            st = steps[pos];
            var rules = (st && st.gotoOnLoad) || [];
            for (var r = 0; r < rules.length; r++) {
                var rule = rules[r];
                var hit = rule.ifPresent ? !!document.querySelector(rule.ifPresent)
                                         : !document.querySelector(rule.ifAbsent);
                if (hit && rule.to !== pos) { pos = rule.to; moved = true; break; }
            }
            if (!moved) { break; }
        }
        return pos;
    };

    Tour.prototype.rescan = function () {
        // Dipanggil dari halaman saat DOM berubah (mis. "Tiba di Lokasi" terbuka
        // otomatis setelah timer). Kalau tutorial sedang menunggu target ini,
        // langsung coba tampilkan.
        // Langkah yang SEDANG tampil bisa jadi sudah terpenuhi oleh perubahan
        // halaman (mis. pop up "Berhasil Melakukan Verifikasi" muncul,
        // sehingga langkah "Kode QR" tidak relevan lagi): kalau 'skipIf'-nya
        // kini cocok, langsung lanjut ke langkah berikutnya.
        var cur = this.rawSteps[this.stepIndex];
        if (cur && cur.skipIf && document.querySelector(cur.skipIf)) {
            this._next();
            return;
        }
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
        tooltip.setAttribute('role', 'dialog');
        tooltip.setAttribute('aria-live', 'polite');
        tooltip.innerHTML =
            '<div class="tour-arrow bottom" data-tour-arrow></div>' +
            '<div class="tour-eyebrow">' +
            '  <span data-tour-progress></span>' +
            '  <button type="button" class="tour-collapse" data-tour-collapse aria-label="Ciutkan petunjuk">Ciutkan ▴</button>' +
            '</div>' +
            '<div class="tour-bar" aria-hidden="true"><span data-tour-bar></span></div>' +
            '<h3 class="tour-title" data-tour-title></h3>' +
            '<p class="tour-text" data-tour-text></p>' +
            '<p class="tour-hint" data-tour-hint hidden></p>' +
            '<div class="tour-footer">' +
            '  <button type="button" class="tour-skip" data-tour-skip>Lewati tutorial</button>' +
            '</div>';
        document.body.appendChild(tooltip);

        this.els = { backdrop: backdrop, panels: panels, ring: ring, tooltip: tooltip };

        var self = this;
        tooltip.querySelector('[data-tour-skip]').addEventListener('click', function () {
            setSkipped(true);
            self._destroy();
        });
        // Tombol "Ciutkan": kalau petunjuk terasa menghalangi, pengguna bisa
        // mengecilkannya jadi satu baris judul, lalu membukanya lagi.
        var colBtn = tooltip.querySelector('[data-tour-collapse]');
        colBtn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            self._collapsed = !self._collapsed;
            self._applyCollapsed();
            // Ukuran tooltip berubah (ciut/buka) -> hitung ulang reserve +
            // posisi supaya dorongan konten & sorotan tetap sinkron. Dibungkus
            // requestAnimationFrame supaya browser sempat menerapkan class
            // is-collapsed dulu (ukuran baru) sebelum kita ukur & pindahkan,
            // jadi tidak ada "lompatan" ganda / flicker saat toggle.
            requestAnimationFrame(function () {
                var t = self._currentHighlightEl || self._currentEl;
                if (t) { self._positionOn(t, self._currentPlace); }
            });
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
        // Kalau langkah saat ini sedang "diam-diam" (lihat _showQuiet),
        // JANGAN reposisi ring sama sekali -- _positionOn selalu menyalakan
        // kembali ring.style.display, yang akan membuat sorotan hijau
        // muncul tiba-tiba lagi saat pengguna resize/scroll layar padahal
        // seharusnya sudah disembunyikan permanen untuk langkah ini.
        if (this._quiet) { return; }
        // Sorotan (ring + mask) mengikuti '_currentHighlightEl' -- yang bisa
        // saja berbeda dari '_currentEl' (elemen yang diberi listener klik
        // untuk lanjut ke langkah berikutnya), lihat catatan di _showOn.
        var target = this._currentHighlightEl || this._currentEl;
        if (target) { this._positionOn(target, this._currentPlace); }
    };

    Tour.prototype._applyCollapsed = function () {
        var tt = this.els.tooltip;
        if (!tt) { return; }
        tt.classList.toggle('is-collapsed', !!this._collapsed);
        var b = tt.querySelector('[data-tour-collapse]');
        if (b) {
            b.textContent = this._collapsed ? 'Buka ▾' : 'Ciutkan ▴';
            b.setAttribute('aria-label', this._collapsed ? 'Buka petunjuk' : 'Ciutkan petunjuk');
        }
    };

    Tour.prototype._runStep = function () {
        clearTimeout(this._autoTimer);
        clearTimeout(this.waitTimer);
        clearTimeout(this.delayTimer);
        clearTimeout(this._advTimer);
        this._delayedForTarget = null;
        this._showAfterAt = null;
        this._waitingTarget = null;
        // Selalu lepas listener klik dari elemen langkah SEBELUMNYA di sini,
        // bukan cuma di _showOn/_showCentered. Kalau tidak, listener lama
        // masih menempel di elemen yang sudah tidak lagi menjadi "target"
        // langkah saat ini (mis. saat langkah berikutnya masuk status
        // menunggu karena tombolnya masih disabled) - dan bisa ke-trigger
        // lagi tanpa sengaja.
        this._detachElListener();
        this._clearDock();

        if (this.stepIndex >= this.rawSteps.length) {
            markSeen(this.posKey);
            if (this._seenKey && this._seenKey !== this.posKey) { markSeen(this._seenKey); }
            clearStepPos(this.posKey);
            this._destroy();
            return;
        }
        saveStepPos(this.posKey, this.stepIndex);
        this._tryShowCurrent();
    };

    Tour.prototype._tryShowCurrent = function () {
        var step = this.rawSteps[this.stepIndex];
        if (!step) { return; }

        // Langkah milik LAYAR LAIN (mis. langkah "Lengkapi Sisa LO" yang
        // ada di layar 'checklist', padahal pengguna baru saja mengetuk
        // "Simpan" di layar 'claim_loss'): JANGAN dievaluasi / dilewati di
        // sini. Kalau dievaluasi, 'requireTarget'-nya pasti "tidak ditemukan"
        // (kartunya memang ada di layar lain) sehingga langkah ini keliru
        // dianggap tidak relevan dan tutorial loncat ke langkah berikutnya
        // (tombol "Selanjutnya"), padahal LO lain belum diisi. Cukup sembunyikan
        // sorotan dan tunggu -- posisi langkah sudah tersimpan, jadi begitu
        // layar yang benar dimuat tutorial melanjutkan dari langkah ini.
        if (step.screen && step.screen !== this.screen) {
            this._holdForOtherScreen();
            return;
        }

        // 'skipIf': langkah dianggap SUDAH terpenuhi kalau selector ini ada
        // (mis. pop up Hasil Generate sudah terbuka -> langkah "isi form" lewat).
        if (step.skipIf && document.querySelector(step.skipIf)) {
            this.stepIndex++;
            this._runStep();
            return;
        }

        // Sebagian langkah (mis. "Lanjutkan ke Produk/Segel Berikutnya")
        // hanya relevan SELAMA syarat 'requireTarget'-nya masih ditemukan
        // di halaman (mis. masih ada kartu Produk/Segel berstatus "Belum
        // Terisi"). Begitu selector itu TIDAK ditemukan lagi (berarti
        // semuanya sudah terisi), langkah ini dilewati begitu saja --
        // lanjut ke langkah berikutnya tanpa menampilkan sorotan yang
        // sudah tidak relevan lagi (jadi tidak pernah "nyangkut"/stuck
        // menunggu sesuatu yang tidak akan pernah muncul).
        if (step.requireTarget && !document.querySelector(step.requireTarget)) {
            this.stepIndex++;
            this._runStep();
            return;
        }

        // Langkah pembuka/penutup tanpa target (mis. "Selamat Datang", "Selesai!")
        if (!step.target) {
            this._waitingTarget = null;
            if (step.showAfter) {                       // [AMT] jeda sebelum tampil
                if (!this._showAfterAt) { this._showAfterAt = Date.now() + step.showAfter; }
                var leftC = this._showAfterAt - Date.now();
                if (leftC > 0) {
                    var selfC = this;
                    clearTimeout(this.waitTimer);
                    this.waitTimer = setTimeout(function () {
                        if (selfC.rawSteps[selfC.stepIndex] !== step) { return; }
                        selfC._tryShowCurrent();
                    }, leftC);
                    return;
                }
            }
            this._showCentered(step);
            return;
        }

        var el = document.querySelector(step.target);

        // 'showAfter' (opsional, milidetik): beri pengguna waktu membaca /
        // mengisi halaman DULU tanpa gangguan. Selama jeda ini tidak ada
        // sorotan maupun tooltip. Begitu jeda habis dan pengguna belum
        // mengetuk elemen target, barulah tutorial muncul untuk
        // MENGARAHKAN pengguna mengetuknya -- tutorial TIDAK pernah
        // mengetuk/berpindah halaman sendiri. Kalau pengguna sudah
        // mengetuk targetnya sendiri selama jeda, tutorial ini dianggap
        // selesai (listener diam-diam dari _showQuiet) dan tidak muncul lagi.
        if (step.showAfter) {
            if (!this._showAfterAt) { this._showAfterAt = Date.now() + step.showAfter; }
            var left = this._showAfterAt - Date.now();
            if (left > 0) {
                this._waitingTarget = null;
                this._quiet = true;
                this._currentEl = null;
                if (el && !this._elListenerEl) { this._showQuiet(el, step); }
                var selfDelay = this;
                clearTimeout(this.waitTimer);
                this.waitTimer = setTimeout(function () {
                    if (selfDelay.rawSteps[selfDelay.stepIndex] !== step) { return; }
                    selfDelay._tryShowCurrent();
                }, left);
                return;
            }
        }

        // Langkah yang punya 'highlight' terpisah dari 'target' (lihat
        // catatan di isVisibleIgnoringDisabled di atas) boleh langsung
        // tampil walau elemen target-nya (tombolnya) masih disabled --
        // supaya kotak sorotan pada form yang masih kosong tetap muncul
        // sejak awal, bukan baru muncul di akhir setelah tombolnya aktif.
        var canShow = el && (step.highlight ? isVisibleIgnoringDisabled(el) : isVisible(el));
        if (canShow) {
            this._waitingTarget = null;

            // Beberapa elemen target sengaja diberi atribut
            // "data-tour-delay" (dalam milidetik) oleh halamannya sendiri
            // -- mis. tombol "Mulai Checklist" di Daftar LO diberi jeda
            // 2 detik selama baru SEBAGIAN LO yang dicentang, supaya
            // pengguna sempat menyadari belum semua LO dipilih sebelum
            // sorotan tutorial pindah. Kalau semua LO sudah dicentang,
            // atributnya tidak dipasang sama sekali -> langsung tampil.
            var delayMs = parseInt(el.getAttribute('data-tour-delay') || '0', 10) || 0;
            if (delayMs > 0 && this._delayedForTarget !== step.target) {
                this._delayedForTarget = step.target;
                var self = this;
                clearTimeout(this.delayTimer);
                this.delayTimer = setTimeout(function () {
                    // Cek ulang, siapa tahu keadaannya sudah berubah selama
                    // menunggu (mis. pengguna sempat mencentang LO lainnya
                    // juga, atau tutorial sudah lanjut ke step lain).
                    if (self.rawSteps[self.stepIndex] !== step) { return; }
                    self._tryShowCurrent();
                }, delayMs);
                return;
            }
            this._delayedForTarget = null;
            this._showOn(el, step);
            return;
        }

        // Elemen belum ada / masih tersembunyi (mis. tombol di dalam pop up
        // yang baru muncul otomatis setelah 5 detik) — tunggu, jangan
        // menutupi layar dengan spotlight yang menunjuk ke tempat kosong.
        //
        // Kalau elemennya SUDAH ADA di halaman tapi cuma "disabled" (mis.
        // tombol "Simpan" menunggu ketiga pertanyaan dijawab pengguna),
        // tunggu TANPA batas waktu 20 detik - biarkan pengguna mengisi
        // datanya dulu, baru langkah tutorial ini muncul. Batas 20 detik
        // hanya berlaku untuk elemen yang benar-benar belum ada di DOM.
        //
        // Percobaan PERTAMA untuk step ini JANGAN langsung menyembunyikan
        // sorotan yang masih tampil dari step SEBELUMNYA. Beberapa langkah
        // (mis. "Pilih LO" -> "Mulai Checklist") pindah lewat klik tautan
        // biasa yang memuat ulang HALAMAN YANG SAMA - begitu diklik, target
        // baru memang belum ada sampai halaman baru selesai dimuat. Kalau
        // sorotan lama langsung dihapus di sini, layar terlihat "kedip
        // kosong" sesaat sebelum halaman baru muncul, sehingga terasa ada
        // jeda meski sebenarnya cuma menunggu reload biasa. Membiarkan
        // sorotan lama tetap menyala sampai reload selesai membuat
        // transisinya terasa langsung/instan.
        //
        // Baru pada percobaan ULANG (setelah 400ms, artinya TIDAK terjadi
        // reload dan kita betul-betul masih menunggu di halaman yang sama)
        // sorotan lama itu disembunyikan, supaya tidak terlihat "nyangkut"
        // menunjuk elemen yang sudah tidak relevan.
        var isRetry = (this._waitingTarget === step.target);
        if (isRetry) {
            this.els.backdrop.classList.remove('is-visible');
            this.els.tooltip.classList.remove('is-visible');
            // Sembunyikan juga sisa 4 panel mask + cincin biru dari elemen
            // yang disorot pada langkah SEBELUMNYA.
            this._currentEl = null;
            this._currentHighlightEl = null;
            Object.keys(this.els.panels).forEach(function (k) {
                this.els.panels[k].style.display = 'none';
            }, this);
            this.els.ring.style.display = 'none';
            this._clearDock();
            document.body.classList.remove('tour-active');
        }
        this._waitingTarget = step.target;

        var waitingOnDisabled = !!(el && el.disabled);
        if (!waitingOnDisabled && !step.waitForever && !this.waitDeadline) {
            this.waitDeadline = Date.now() + 20000;
        }

        var self = this;
        this.waitTimer = setTimeout(function () {
            if (!waitingOnDisabled && self.waitDeadline && Date.now() > self.waitDeadline) {
                self.waitDeadline = 0;
                self._advanceSkippingHidden();
                return;
            }
            self._tryShowCurrent();
        }, 400);
    };

    Tour.prototype._holdForOtherScreen = function () {
        this._waitingTarget = null;
        this._quiet = true;
        this._currentEl = null;
        this._currentHighlightEl = null;
        if (this.els.backdrop) {
            this.els.backdrop.classList.remove('is-visible');
            this.els.backdrop.classList.remove('is-blocking');
            this.els.tooltip.classList.remove('is-visible');
            Object.keys(this.els.panels).forEach(function (k) {
                this.els.panels[k].style.display = 'none';
            }, this);
            this.els.ring.style.display = 'none';
        }
        this._clearDock();
        document.body.classList.remove('tour-active');
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
        if (this._extraListeners) {
            this._extraListeners.forEach(function (x) { x.el.removeEventListener(x.evt, x.fn, true); });
        }
        this._extraListeners = [];
        // Lepas juga pengamat "tombol berubah dari disabled -> aktif" (lihat
        // _watchEnableTransition) supaya tidak menempel ke elemen langkah
        // yang sudah tidak relevan lagi.
        if (this._enableObserver) {
            this._enableObserver.disconnect();
            this._enableObserver = null;
        }
    };

    // Mengamati elemen 'el' (tombol yang awalnya disabled, mis. "Generate")
    // sampai benar-benar aktif, lalu memindahkan sorotan (ring + tooltip)
    // supaya menyorot LANGSUNG ke tombol itu sendiri -- lihat catatan
    // panjang di _showOn. Dipanggil sekali per langkah; otomatis berhenti
    // mengamati begitu tutorial pindah ke langkah lain (lihat
    // _detachElListener, yang selalu dipanggil di awal _runStep).
    Tour.prototype._watchEnableTransition = function (el, step) {
        var self = this;
        if (this._enableObserver) { this._enableObserver.disconnect(); }

        function promote() {
            self._enableObserver = null;
            // Kalau tutorial sudah berpindah dari langkah ini (mis. pengguna
            // sempat mengetuk sesuatu yang lain lalu balik lagi), jangan
            // lakukan apa-apa lagi.
            if (self._currentEl !== el) { return; }
            self._currentHighlightEl = el;
            self._clearDock();
            self._ensureVisible(el, self._currentPlace);
            self._positionOn(el, self._currentPlace);
            var tt = self.els.tooltip;
            var hint = tt.querySelector('[data-tour-hint]');
            if (hint) { hint.textContent = HINT_READY; }
        }

        if (!el.disabled) { promote(); return; }

        if (typeof MutationObserver === 'undefined') { return; }
        this._enableObserver = new MutationObserver(function () {
            if (!el.disabled) { promote(); }
        });
        this._enableObserver.observe(el, { attributes: true, attributeFilter: ['disabled'] });
    };

    Tour.prototype._showCentered = function (step) {
        this._detachElListener();
        this._quiet = false;
        this.els.backdrop.classList.add('is-visible');
        Object.keys(this.els.panels).forEach(function (k) {
            this.els.panels[k].style.display = 'none';
        }, this);
        this.els.ring.style.display = 'none';
        this._currentEl = null;
        this._currentHighlightEl = null;

        this.els.backdrop.classList.add('is-blocking');

        var tt = this.els.tooltip;
        this._clearDock();
        tt.className = 'tour-tooltip place-center';
        tt.style.width = '';
        tt.style.left = '';
        tt.style.top = '';
        this._fillTooltip(step);

        var hint = tt.querySelector('[data-tour-hint]');
        hint.textContent = HINT_ANY;
        hint.hidden = false;

        requestAnimationFrame(function () { tt.classList.add('is-visible'); });
        document.body.classList.add('tour-active');
    };

    Tour.prototype._showOn = function (el, step) {
        this._detachElListener();
        this._currentEl = el;
        this._currentPlace = step.place || 'bottom';

        // 'step.quietIf' (opsional): kalau selector ini DITEMUKAN di
        // halaman (mis. sudah ada kartu Produk lain berstatus "Sudah
        // Terisi" -- artinya ini BUKAN produk pertama), langkah ini tidak
        // lagi menampilkan sorotan ring + tooltip-nya sama sekali -- supaya
        // penjelasan pop up Produk/Segel benar-benar hanya tampil SATU
        // KALI, tidak berulang di setiap produk berikutnya. Listener klik
        // pada elemen aksi TETAP dipasang (secara diam-diam) supaya
        // progres tutorial tetap lanjut begitu pengguna benar-benar
        // melakukan aksinya (mis. menyimpan verifikasi produk kedua) --
        // hanya saja pengguna tidak melihat kotak hijau/tooltip lagi.
        if (isQuietStep(step)) {
            this._quiet = true;
            this._showQuiet(el, step);
            return;
        }
        this._quiet = false;

        // 'step.highlight' (opsional) memisahkan elemen yang DISOROT SECARA
        // VISUAL (ring hijau + mask gelap sekitarnya) dari 'el' yang dipakai
        // sebagai PEMICU untuk lanjut ke langkah berikutnya. Ini dipakai
        // supaya pop up Produk/Segel disorot SATU KOTAK PENUH mengelilingi
        // seluruh kartu (.spp-modal) -- bukan cuma kotak kecil di sekitar
        // baris pilihan Sesuai/Tidak Sesuai atau tombol Simpan -- sementara
        // tutorial tetap baru lanjut begitu pengguna benar-benar menjawab /
        // menekan tombol yang dimaksud (elemen 'el' yang sebenarnya).
        // Kalau 'highlight' tidak diisi atau elemennya tidak ditemukan,
        // fallback ke 'el' seperti semula (perilaku lama, tidak berubah).
        var highlightEl = (step.highlight && document.querySelector(step.highlight)) || el;
        this._currentHighlightEl = highlightEl;

        // Kalau langkah ini punya 'highlight' terpisah dan elemen target-nya
        // (tombolnya sendiri, mis. "Generate") MASIH disabled saat ini
        // ditampilkan (lihat isVisibleIgnoringDisabled) -- pasang pengamat:
        // begitu tombol itu benar-benar aktif (pengguna selesai mengisi
        // semua kolom wajib), sorotan PINDAH dari highlight (seluruh
        // form) supaya langsung menyorot tombolnya sendiri, dan teks
        // petunjuknya diperbarui. Jadi begitu pengisian selesai, tutorial
        // ini kelihatan jelas "melangkah maju" mengarah ke tombol yang
        // memang harus diketuk -- bukan diam menunjuk area form yang sama
        // terus sehingga terkesan macet/tidak merespons.
        if (step.highlight && el !== highlightEl && el.disabled) {
            this._watchEnableTransition(el, step);
        }

        // JANGAN nyalakan backdrop gelap layar-penuh di sini: kalau dinyalakan,
        // lapisan gelapnya ikut menutupi elemen yang sedang disorot juga,
        // sehingga warna aslinya (mis. ikon terang) tampak jadi gelap/kusam.
        // Yang menggelapkan AREA SEKITAR elemen cukup 4 panel mask
        // (lihat _positionOn) - itu sudah menyisakan "lubang" transparan
        // persis di atas elemen yang disorot, sehingga warna aslinya tampil.
        this.els.backdrop.classList.remove('is-visible');
        this.els.backdrop.classList.remove('is-blocking'); // elemen lain di layar tetap bisa dipencet
        var self = this;
        var startStepIndex = this.stepIndex;

        this._fillTooltip(step, el);
        // Step dinamis bisa membatalkan diri sendiri & lompat ke step
        // berikutnya (lihat _fillTooltip) - kalau itu terjadi, hentikan di sini.
        if (this.stepIndex !== startStepIndex) { return; }

        var tt = this.els.tooltip;
        tt.className = 'tour-tooltip place-' + (step.place || 'bottom') +
            (this._collapsed ? ' is-collapsed' : '');
        this._customHint = step.hint || null;

        // Susun ulang tata letak SETIAP langkah dari nol: lepas dock lama,
        // gulir halaman seperlunya SAJA (bukan selalu ke tengah), lalu
        // tempatkan tooltip di sisi yang muat (atau dock kalau tidak muat).
        this._clearDock();
        this._ensureVisible(highlightEl, this._currentPlace);
        this._positionOn(highlightEl, this._currentPlace);
        // Pop up punya animasi masuk singkat -- hitung ulang setelah selesai.
        setTimeout(function () {
            if (self._currentHighlightEl === highlightEl && self.els.tooltip) {
                self._positionOn(highlightEl, self._currentPlace);
            }
        }, 260);

        // Tidak ada tombol biru sama sekali di sini: petunjuk hilang begitu
        // pengguna benar-benar mengetuk elemen asli yang disorot.
        var hint = tt.querySelector('[data-tour-hint]');
        hint.textContent = this._customHint || HINT_TAP;
        hint.hidden = false;

        // Detach dulu SEBELUM lanjut: sebuah tap pada <label> radio memicu
        // 2 event "click" berantai (satu di <label>, satu lagi otomatis di
        // <input> pasangannya) yang sama-sama lewat/bubbling di elemen ini.
        // Kalau tidak di-detach lebih dulu, event kedua bisa memanggil
        // _next() sekali lagi dan tutorial melompati satu langkah tanpa
        // sengaja (mis. langkah "Simpan" terlewat begitu saja).
        this._elClickHandler = function () {
            self._detachElListener();
            // 'jumpOnClickTo' (opsional): dipakai oleh langkah "Lanjutkan
            // ke Produk/Segel Berikutnya" supaya sentuhan pengguna pada
            // kartu tsb langsung membawa tutorial ke langkah verifikasi
            // (bukan cuma stepIndex+1 seperti biasa, karena kartu itu
            // membuka pop up yang sama seperti pop up produk/segel yang
            // PERTAMA -- jadi langkah tutorialnya juga harus kembali ke
            // langkah pop up yang sama itu).
            if (typeof step.jumpOnClickTo === 'number') {
                self.stepIndex = step.jumpOnClickTo;
                self._runStep();
            } else {
                self._next();
            }
        };
        this._elListenerEl   = el;
        // [AMT] 'noClickAdvance' (opsional): ketukan pada elemen TIDAK melanjutkan langkah
        // (mis. <select> yang dropdown-nya baru terbuka); lanjut hanya lewat 'alsoAdvanceOn'.
        if (!step.noClickAdvance) {
            el.addEventListener('click', this._elClickHandler, true);
        }
        this._attachExtra(step);

        // [AMT] 'advanceIf' (opsional): selector kondisi (mis. lokasi sudah sesuai titik
        // kerja). Tutorial memantau kondisi ini; begitu terpenuhi, langkah tetap tampil
        // selama 'advanceDelayMs' (default 3 detik) supaya pengguna sempat memahaminya,
        // baru lanjut otomatis. Selama belum terpenuhi, tutorial menunggu.
        clearTimeout(this._advTimer);
        if (step.advanceIf) {
            var advIdx = this.stepIndex;
            var advDelay = step.advanceDelayMs || 3000;
            var advPoll = function () {
                if (self.stepIndex !== advIdx || !self.els.backdrop) { return; }
                if (document.querySelector(step.advanceIf)) {
                    self._advTimer = setTimeout(function () {
                        if (self.stepIndex !== advIdx || !self.els.backdrop) { return; }
                        self._detachElListener();
                        self._next();
                    }, advDelay);
                } else {
                    self._advTimer = setTimeout(advPoll, 400);
                }
            };
            advPoll();
        }

        requestAnimationFrame(function () { tt.classList.add('is-visible'); });

        // Langkah penutup yang tertutup otomatis (mis. "Semua Selesai"):
        // setelah 'autoDismissMs' tutorial dianggap selesai (ditandai sudah
        // ditonton) dan sorotan + tooltip dihapus.
        clearTimeout(this._autoTimer);
        if (this._autoDismissMs > 0) {
            hint.textContent = 'Tutorial ini akan tertutup otomatis dalam ' + Math.round(this._autoDismissMs / 1000) + ' detik';
            var autoIdx = this.stepIndex;
            this._autoTimer = setTimeout(function () {
                if (self.stepIndex !== autoIdx || !self.els.backdrop) { return; }
                self._detachElListener();
                self.stepIndex = self.rawSteps.length; // -> markSeen + _destroy
                self._runStep();
            }, this._autoDismissMs);
        }
    };

    // Versi "diam-diam" dari _showOn: TIDAK menampilkan ring, mask gelap,
    // maupun tooltip sama sekali -- tapi tetap memasang listener klik pada
    // elemen aksi ('el') supaya begitu pengguna benar-benar menjawab /
    // menekan tombolnya, tutorial tetap lanjut ke langkah berikutnya (atau
    // 'jumpOnClickTo' kalau diisi) persis seperti langkah yang tampil
    // biasa. Dipakai supaya penjelasan pop up Produk (langkah 1 & 2) tidak
    // mengulang tampilannya lagi mulai produk kedua dan seterusnya.
    Tour.prototype._showQuiet = function (el, step) {
        this.els.backdrop.classList.remove('is-visible');
        this.els.backdrop.classList.remove('is-blocking');
        this.els.tooltip.classList.remove('is-visible');
        Object.keys(this.els.panels).forEach(function (k) {
            this.els.panels[k].style.display = 'none';
        }, this);
        this.els.ring.style.display = 'none';
        this._currentHighlightEl = null;
        this._clearDock();
        document.body.classList.remove('tour-active');

        var self = this;
        this._elClickHandler = function () {
            self._detachElListener();
            if (typeof step.jumpOnClickTo === 'number') {
                self.stepIndex = step.jumpOnClickTo;
                self._runStep();
            } else {
                self._next();
            }
        };
        this._elListenerEl = el;
        if (!step.noClickAdvance) {
            el.addEventListener('click', this._elClickHandler, true);
        }
        this._attachExtra(step);
    };

    // 'alsoAdvanceOn' (opsional): { selector, event } -- pemicu tambahan
    // selain klik pada elemen target (mis. pengguna langsung mengetik di
    // form tanpa mengetuk kartu metode dulu), supaya tutorial tidak macet.
    Tour.prototype._attachExtra = function (step) {
        this._extraListeners = this._extraListeners || [];
        var self = this;

        var cfg = step.alsoAdvanceOn;
        if (cfg && this._elClickHandler) {
            var node = document.querySelector(cfg.selector);
            if (node) {
                var fn = this._elClickHandler;
                node.addEventListener(cfg.event, fn, true);
                this._extraListeners.push({ el: node, evt: cfg.event, fn: fn });
            }
        }

        // 'cancelOn' (opsional): { selector, event, to } -- kalau pengguna
        // membatalkan (mis. menekan "Batal" pada pop up konfirmasi Kirim),
        // tutorial MUNDUR ke langkah 'to' supaya tidak macet menunggu
        // pop up yang sudah tertutup.
        var cc = step.cancelOn;
        if (cc && typeof cc.to === 'number') {
            var cnode = document.querySelector(cc.selector);
            if (cnode) {
                var cfn = function () {
                    self._detachElListener();
                    self.stepIndex = cc.to;
                    self._runStep();
                };
                cnode.addEventListener(cc.event || 'click', cfn, true);
                this._extraListeners.push({ el: cnode, evt: cc.event || 'click', fn: cfn });
            }
        }
    };

    Tour.prototype._fillTooltip = function (step, el) {
        var label = step;
        this._autoDismissMs = step.autoDismissMs || 0;
        if (step.dynamic && el) {
            var key = el.getAttribute('data-tour-label') || '';
            label = DYNAMIC_LABELS[key] || { title: key, text: '' };
            this._autoDismissMs = label.autoDismissMs || step.autoDismissMs || 0;
            if (!label.text) {
                // Skip langkah dinamis kalau labelnya tidak dikenali (aman untuk masa depan)
                this._advanceSkippingHidden();
                return;
            }
        }
        var tt = this.els.tooltip;
        var screenLabel = this.screenLabels[this.screen] || '';
        var progress = (this.screenIndex ? 'Bagian ' + this.screenIndex + ' dari ' + this.screenOrder.length + ' · ' + screenLabel : screenLabel);
        tt.querySelector('[data-tour-progress]').textContent = progress;
        var bar = tt.querySelector('[data-tour-bar]');
        if (bar) {
            var total = this.screenOrder.length || 1;
            var pct = this.screenIndex ? Math.round(this.screenIndex / total * 100) : 0;
            bar.style.width = pct + '%';
            bar.parentNode.style.display = pct ? '' : 'none';
        }
        this._applyCollapsed();
        tt.querySelector('[data-tour-title]').textContent = label.title || '';
        tt.querySelector('[data-tour-text]').textContent = label.text || '';
    };

    // ==================================================================
    //  MESIN PENEMPATAN TOOLTIP
    //  Aturan utama: tooltip TIDAK BOLEH keluar layar dan TIDAK BOLEH
    //  menutupi elemen yang disorot (mis. kolom form yang harus diisi).
    //  1. Coba sisi yang diminta ('place'), lalu sisi seberangnya. Sisi
    //     yang menutupi paling sedikit tombol/kolom lain dipilih.
    //  2. Kalau di kedua sisi tidak muat (sorotan tinggi, mis. seluruh
    //     form atau pop up), tooltip \"DOCK\": dipasang di pita sendiri di
    //     bagian atas, lalu isi halaman / pop up didorong turun setinggi
    //     pita itu. Jadi tidak ada yang tertimpa dan tidak ada yang
    //     terpotong -- pengguna tetap bebas men-scroll.
    // ==================================================================
    var GAP = 14, EDGE = 8;

    // Batas area yang boleh dipakai tooltip untuk elemen 'el':
    // lebar = kartu aplikasi, tinggi = area .content (di bawah header) untuk
    // elemen halaman biasa, atau seluruh layar untuk pop up (fixed).
    Tour.prototype._region = function (el) {
        var vw = window.innerWidth, vh = window.innerHeight;
        var app = document.querySelector('.app-container');
        var ab = app ? app.getBoundingClientRect() : { left: 0, right: vw };
        var reg = {
            left:   Math.max(0, ab.left),
            right:  Math.min(vw, ab.right),
            top:    0,
            bottom: vh,
            inContent: false,
            content: null
        };
        var content = document.querySelector('.content');
        // Pop up bersifat position:fixed (menutupi seluruh layar) walau secara
        // DOM berada di dalam .content -> perlakukan sebagai layar penuh.
        var isModal = !!(el && el.closest && el.closest('.modal-backdrop, .spp-modal-backdrop'));
        if (el && content && !isModal && content.contains(el)) {
            var cb = content.getBoundingClientRect();
            reg.inContent = true;
            reg.content   = content;
            reg.top       = Math.max(0, cb.top);
            reg.bottom    = Math.min(vh, cb.bottom);
        }
        return reg;
    };

    // Atur lebar tooltip sesuai kartu aplikasi (HP kecil maupun desktop).
    Tour.prototype._sizeTooltip = function (reg) {
        var tt = this.els.tooltip;
        var w = Math.min(340, Math.max(220, reg.right - reg.left - 24));
        if (this._docked) { w = Math.max(220, reg.right - reg.left - 24); }
        tt.style.width = w + 'px';
        return w;
    };

    // Potong rect sorotan ke area yang terlihat (supaya sorotan yang lebih
    // tinggi dari layar tidak menabrak tooltip / header).
    Tour.prototype._clipRect = function (rect, reg) {
        var top = Math.max(rect.top, reg.top + 2);
        var bot = Math.max(top, Math.min(rect.bottom, reg.bottom - 2));
        return { top: top, bottom: bot, left: rect.left, right: rect.right,
                 width: rect.width, height: bot - top };
    };

    // Hitung berapa kolom isian / pilihan jawaban (selain sorotan) yang
    // tertutup kotak 'box'. Kolom form TIDAK BOLEH tertutup tooltip -- kalau
    // di sisi manapun ada yang tertutup, tooltip akan di-dock (lihat _positionOn).
    // Elemen di balik pop up yang sedang terbuka tidak dihitung (memang
    // sudah tidak bisa disentuh).
    var PROTECTED_SEL = 'input:not([type=hidden]), textarea, select, .btn-choice, .spp-opt, ' +
                        '.method-card, .measure-lo-card, .nav-item-card, .lo-card, .konfirmasi-card, ' +
                        '.photo-drop, .rating-stars label, .star';
    Tour.prototype._countCovered = function (box, hl) {
        var n = 0, tt = this.els.tooltip;
        var modal = hl && hl.closest ? hl.closest('.modal-backdrop, .spp-modal-backdrop') : null;
        var list = document.querySelectorAll(PROTECTED_SEL);
        var content = document.querySelector('.content');
        var cb = content ? content.getBoundingClientRect() : null;
        for (var i = 0; i < list.length; i++) {
            var e = list[i];
            if (tt.contains(e) || (hl && hl.contains(e))) { continue; }
            var em = e.closest ? e.closest('.modal-backdrop, .spp-modal-backdrop') : null;
            if (modal ? em !== modal : !!em) { continue; }
            var r = e.getBoundingClientRect();
            if (r.width <= 0 || r.height <= 0) { continue; }
            // Bagian yang sudah tergulir keluar dari area .content tidak terlihat
            var top = r.top, bot = r.bottom;
            if (cb && !em && content.contains(e)) { top = Math.max(top, cb.top); bot = Math.min(bot, cb.bottom); }
            if (bot <= top) { continue; }
            if (r.right > box.left + 1 && r.left < box.right - 1 &&
                bot > box.top + 1 && top < box.bottom - 1) { n++; }
        }
        return n;
    };

    // Pastikan sorotan + tooltip sama-sama muat di layar. Halaman HANYA
    // digulir seperlunya (paling sedikit), dan TIDAK digulir sama sekali
    // kalau sudah muat atau kalau sorotannya terlalu tinggi (-> dock).
    Tour.prototype._ensureVisible = function (el, place) {
        var reg = this._region(el);
        if (!reg.inContent) { return; }
        this._sizeTooltip(reg);
        var tt = this.els.tooltip;
        var H = tt.offsetHeight || 170;
        var rect = el.getBoundingClientRect();
        var top = reg.top + EDGE, bottom = reg.bottom - EDGE;
        var inside = rect.top >= top && rect.bottom <= bottom;
        var fitsTop = rect.top - GAP - H >= top;
        var fitsBot = rect.bottom + GAP + H <= bottom;
        if (inside && (fitsTop || fitsBot)) { return; }

        var Hh = rect.height;
        if (Hh + GAP + H > bottom - top) { return; } // terlalu tinggi -> dock (lihat _positionOn)

        var want;
        if (place === 'top') {
            want = Math.min(Math.max(rect.top, top + H + GAP), bottom - Hh);
        } else {
            want = Math.min(Math.max(rect.top, top), bottom - Hh - GAP - H);
        }
        var prev = reg.content.style.scrollBehavior;
        reg.content.style.scrollBehavior = 'auto';
        reg.content.scrollTop += (rect.top - want);
        reg.content.style.scrollBehavior = prev;
    };

    // DOCK: tooltip di pita atas + isi halaman/pop up didorong turun.
    Tour.prototype._enterDock = function (reg) {
        var tt = this.els.tooltip;
        var root = document.documentElement;
        this._docked = true;
        // TIDAK ADA ciut otomatis -- tutorial selalu tampil PENUH & konsisten
        // di semua langkah (pengguna sendiri yang memutuskan lewat tombol
        // "Ciutkan" kalau mau). Supaya konten asli di baliknya tetap tidak
        // terdorong terlalu jauh, tinggi kartu di mode dock DIBATASI lewat
        // CSS (.is-docked { max-height: ... }) dengan teksnya sendiri yang
        // bisa di-scroll kalau panjang -- bukan kartunya yang menciut.
        this._sizeTooltip(reg);
        tt.classList.add('is-docked');
        tt.style.transform = '';

        var content = reg.content;
        var top;
        if (reg.inContent && content) {
            var prev = parseFloat(content.style.marginTop) || 0;
            top = content.getBoundingClientRect().top - prev;
        } else {
            top = EDGE;
        }
        tt.style.left = (reg.left + 12) + 'px';
        tt.style.top  = (top + EDGE) + 'px';

        var reserve = Math.ceil(tt.offsetHeight + EDGE * 2);
        if (reg.inContent && content) {
            if (content.style.marginTop !== reserve + 'px') {
                content.style.marginTop = reserve + 'px';
            }
        } else {
            root.style.setProperty('--tour-reserve', (tt.offsetHeight + EDGE * 2 + EDGE) + 'px');
            root.classList.add('tour-reserve-modal');
        }
        this._dockContent = reg.inContent ? content : null;
    };

    Tour.prototype._clearDock = function () {
        this._docked = false;
        var root = document.documentElement;
        root.classList.remove('tour-reserve-modal');
        root.style.removeProperty('--tour-reserve');
        var content = document.querySelector('.content');
        if (content && content.style.marginTop) { content.style.marginTop = ''; }
        var tt = this.els && this.els.tooltip;
        if (tt) { tt.classList.remove('is-docked'); }
    };

    Tour.prototype._drawSpotlight = function (rect) {
        var pad = 8, vw = window.innerWidth, vh = window.innerHeight;
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
    };

    Tour.prototype._positionOn = function (el, place) {
        var tt = this.els.tooltip;
        if (!tt || !el) { return; }
        var arrow = tt.querySelector('[data-tour-arrow]');
        var reg = this._region(el);
        this._sizeTooltip(reg);
        var H = tt.offsetHeight || 170;
        var W = tt.offsetWidth || 300;

        // ---- pilih sisi (kecuali sudah/harus dock) ----
        var side = null;
        if (!this._docked) {
            var rect0 = el.getBoundingClientRect();
            var top = reg.top + EDGE, bottom = reg.bottom - EDGE;
            var order = (place === 'top') ? ['top', 'bottom'] : ['bottom', 'top'];
            var best = null;
            for (var i = 0; i < order.length; i++) {
                var sd = order[i];
                var fits = sd === 'top' ? (rect0.top - GAP - H >= top)
                                        : (rect0.bottom + GAP + H <= bottom);
                if (!fits) { continue; }
                var bx = sd === 'top'
                    ? { top: rect0.top - GAP - H, bottom: rect0.top - GAP }
                    : { top: rect0.bottom + GAP,  bottom: rect0.bottom + GAP + H };
                bx.left = reg.left; bx.right = reg.right;
                var cov = this._countCovered(bx, el);
                if (best === null || cov < best.cov) { best = { side: sd, cov: cov }; }
            }
            // Kalau di sisi terbaik pun masih ada kolom form yang tertutup -> dock.
            side = (best && best.cov === 0) ? best.side : null;
            // sorotan sama sekali di luar area yang terlihat -> dock juga
            var offscreen = rect0.bottom < reg.top || rect0.top > reg.bottom;
            if (side === null || offscreen) { this._enterDock(reg); }
        }
        if (this._docked) { this._enterDock(reg); side = 'dock'; }

        // ---- gambar sorotan (setelah dock, karena dock menggeser isi) ----
        var rect = this._clipRect(el.getBoundingClientRect(), reg);
        if (this._docked && reg.inContent) {
            // pastikan tepi atas sorotan terlihat tepat di bawah pita
            var cb = reg.content.getBoundingClientRect();
            var raw = el.getBoundingClientRect();
            if (raw.top < cb.top - 1 && raw.bottom > cb.top) { /* sebagian terlihat: biarkan */ }
        }
        this._drawSpotlight(rect);

        if (side === 'dock') {
            arrow.style.display = 'none';
            this._syncFab();
            return;
        }

        // ---- tempel tooltip di sisi terpilih ----
        var left = rect.left + rect.width / 2 - W / 2;
        left = Math.min(Math.max(left, reg.left + 12), reg.right - 12 - W);
        var tTop = side === 'top' ? rect.top - GAP - H : rect.bottom + GAP;
        tTop = Math.min(Math.max(tTop, EDGE), window.innerHeight - H - EDGE);
        tt.style.transform = '';
        tt.style.left = left + 'px';
        tt.style.top  = tTop + 'px';

        // panah menunjuk ke tengah sorotan
        var ax = rect.left + rect.width / 2 - left - 7;
        ax = Math.min(Math.max(ax, 18), W - 32);
        arrow.style.display = '';
        arrow.style.left = ax + 'px';
        arrow.className = 'tour-arrow ' + (side === 'top' ? 'bottom' : 'top');
        this._syncFab();
    };

    // Tombol "?" mengambang disembunyikan selama tutorial tampil supaya
    // tidak menutupi kolom form.
    Tour.prototype._syncFab = function () {
        var on = !!(this.els.tooltip && this.els.tooltip.classList.contains('is-visible')) || !!this._active;
        document.body.classList.toggle('tour-active', on);
    };

    Tour.prototype._destroy = function () {
        clearTimeout(this._autoTimer);
        clearTimeout(this.waitTimer);
        this._detachElListener();
        this._clearDock();
        document.body.classList.remove('tour-active');
        window.removeEventListener('resize', this._onReflow);
        window.removeEventListener('scroll', this._onReflow, true);
        var els = this.els;
        if (!els.backdrop) { return; }
        [els.backdrop, els.panels.top, els.panels.bottom, els.panels.left, els.panels.right, els.ring, els.tooltip]
            .forEach(function (n) { if (n && n.parentNode) { n.parentNode.removeChild(n); } });
        this.els = {};
    };

    var activeTour = null;

    // Pesan singkat di bawah layar (mis. halaman tanpa tutorial).
    function showToast(msg) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;left:50%;bottom:6rem;transform:translateX(-50%);' +
            'z-index:9500;background:#0f172a;color:#fff;font-size:12px;padding:0.6rem 1rem;' +
            'border-radius:999px;max-width:85vw;text-align:center;box-shadow:0 8px 20px -6px rgba(15,23,42,0.4);';
        document.body.appendChild(t);
        setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 2500);
    }

    window.OneFISTour = {
        init: function (config) {
            activeTour = new Tour(config);

            var params = new URLSearchParams(window.location.search);
            var forceRestart = params.get('tour') === 'restart';
            if (forceRestart) { resetAll(); }

            activeTour.start(forceRestart);

            // Tombol bantuan "?" mengambang: mulai ulang seluruh tutorial dari Beranda
            if (config.screen !== 'role_select') {
                var fab = document.createElement('button');
                fab.type = 'button';
                fab.className = 'tour-help-fab';
                fab.setAttribute('aria-label', 'Tampilkan tutorial halaman ini');
                fab.textContent = '?';
                // Tombol "?": TETAP di halaman yang sedang dibuka (tidak kembali
                // ke Beranda dan tidak mengulang dari awal). Tutorial layar ini
                // dimulai lagi dari langkah pertamanya, walau sebelumnya sudah
                // selesai, dilewati, atau sedang berjalan.
                fab.addEventListener('click', function () {
                    setSkipped(false);
                    if (activeTour) { activeTour._destroy(); }
                    activeTour = new Tour(config);
                    if (!activeTour.rawSteps.length) {
                        showToast('Belum ada tutorial untuk halaman ini.');
                        return;
                    }
                    activeTour.start(true);
                });
                document.body.appendChild(fab);
            }
        },
        // Hapus progres tutorial (dipakai saat pengguna memilih peran SPBU
        // di layar awal supaya tutorial otomatis aktif dari Beranda).
        reset: function () { resetAll(); },
        rescan: function () {
            if (activeTour) { activeTour.rescan(); }
        },
    };
})(window, document);