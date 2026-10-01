<?php
/**
 * Layar: ?screen=amt_spbu&id=...  (role AMT)  -- "SPBU <kode>"
 * Aktifitas di SPBU (5 langkah) + Order List per Nomor LO. Header judul = "SPBU <kode>".
 *
 * Pengiriman berjalan : "Tiba di Lokasi" aktif (kartu biru), langkah lain redup, "Selesai" nonaktif.
 *   Ketuk "Tiba di Lokasi" -> popup (bottom sheet): peta lokasi + "Ya, pengiriman telah tiba".
 *   Setelah dikonfirmasi, "Tiba di Lokasi" hijau dan "Isi Checklist" menjadi langkah aktif.
 * Pengiriman selesai  : semua langkah hijau, status order terverifikasi, tanpa tombol "Selesai".
 * Layar tujuan langkah lain (Isi Checklist, dst) belum dibuat: kartunya belum membuka layar lain.
 *
 * Atribut data-tour dipakai tutorial AMT (includes/amt/tutorial.php).
 * Konfirmasi tiba diproses amt_handle_post('submit_spbu_arrive') di includes/amt/amt.php.
 */
require_once __DIR__ . '/../../includes/amt/amt_ship_data.php';

$s = amt_ship_current();
if ($s === null) {
    echo '<section class="amts-screen"><p class="amts-empty">Shipment tidak ditemukan.</p></section>';
    return;
}
$active   = $s['status'] === 'sedang';
$arrived  = $active && amt_spbu_arrived($s);
$canOpen  = $active && !$arrived;              // popup "Tiba di Lokasi" hanya sebelum tiba
$steps    = amt_spbu_steps();
$total    = count($steps);
$doneN    = $active ? ($arrived ? 1 : 0) : $total;      // jumlah langkah selesai
$progress = $total > 1 ? min($doneN, $total - 1) / ($total - 1) * 100 : 0;
?>

<section class="amts-screen amts-spbu-screen">
    <div class="amts-act-card" data-tour="spbu-activity">
        <h2 class="amts-h2">Aktifitas di SPBU</h2>
        <div class="amts-act">
            <span class="amts-act__line" aria-hidden="true"></span>
            <span class="amts-act__line amts-act__line--done" style="height:<?= (float) $progress ?>%;" aria-hidden="true"></span>

            <?php foreach ($steps as $i => $st):
                $state  = $i < $doneN ? 'done' : ($i === $doneN ? 'active' : 'pending');
                $isOpen = $canOpen && $state === 'active' && $st['key'] === 'tiba'; ?>
                <div class="amts-act__item is-<?= $state ?>"
                     <?= $state === 'active' ? 'data-tour="spbu-step-active"' : '' ?>>
                    <span class="amts-act__node" aria-hidden="true">
                        <?php if ($state === 'done'): ?>
                            <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        <?php endif; ?>
                    </span>
                    <div class="amts-act__row"
                         <?= $isOpen ? 'role="button" tabindex="0" data-spbu-open="arrSheet" aria-haspopup="dialog"' : '' ?>>
                        <img class="amts-act__icon" src="<?= amt_e($st['icon']) ?>" alt="">
                        <span class="amts-act__label"><?= amt_e($st['label']) ?></span>
                        <?php if ($state === 'active'): ?>
                            <svg class="amts-act__chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <h2 class="amts-h2 amts-h2--list">Order List</h2>
    <?php foreach ($s['products'] as $p): ?>
        <article class="amts-card amts-order">
            <div class="amts-order__row">
                <span class="amts-order__k">Nomor LO</span>
                <span class="amts-order__v"><b><?= amt_e($p['lo']) ?></b></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Order</span>
                <span class="amts-order__v"><span class="amts-box"><?= amt_e($p['name']) ?></span></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Status Order</span>
                <span class="amts-order__v"><span class="amts-pill <?= $active ? 'is-warn' : 'is-ok' ?>"><?= $active ? 'Belum Diverifikasi' : 'Sudah Diverifikasi' ?></span></span>
            </div>
            <div class="amts-order__row">
                <span class="amts-order__k">Status Surat Jalan</span>
                <span class="amts-order__v"><span class="amts-pill <?= $active ? 'is-warn' : 'is-ok' ?>"><?= $active ? 'Belum Ditambahkan' : 'Sudah Ditambahkan' ?></span></span>
            </div>
            <button type="button" class="amts-btn amts-btn--outline">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                Lihat Test Report
            </button>
        </article>
    <?php endforeach; ?>
</section>

<?php if ($active): ?>
<div class="amts-footer">
    <button type="button" class="amts-btn amts-btn--solid" disabled>
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4H6a2 2 0 00-2 2v13a2 2 0 002 2h9a2 2 0 002-2v-2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 12h4M9 16h4"/></svg>
        Selesai
    </button>
</div>
<?php endif; ?>

<?php if ($canOpen): ?>
<!-- Popup "Tiba di Lokasi" (bottom sheet). Dipindahkan ke <body> oleh skrip di bawah supaya
     berada di atas semua layar. data-loc / data-pin dibaca tutorial AMT:
       data-loc = 1  posisi sudah dalam radius SPBU
       data-pin = 1  posisi sesuai DAN peta (pin) sudah selesai dimuat -->
<div class="amts-sheet" id="arrSheet" hidden data-loc="0" data-pin="0"
     data-lat="<?= (float) $s['lat'] ?>" data-lng="<?= (float) $s['lng'] ?>"
     data-radius="<?= (int) AMT_SPBU_RADIUS_M ?>" data-out-chance="<?= (float) WORK_SIM_OUTSIDE_CHANCE ?>">
    <div class="amts-sheet__card" role="dialog" aria-modal="true" aria-labelledby="arrTitle">
        <h2 class="amts-sheet__title" id="arrTitle">Tiba di Lokasi</h2>
        <p class="amts-sheet__ask">Apakah kamu sudah tiba di lokasi pengiriman?</p>
        <p class="amts-sheet__code">SPBU <?= amt_e($s['spbu']) ?></p>
        <p class="amts-sheet__plus"><?= amt_e($s['plus']) ?></p>

        <div id="arr-loc">
            <div class="amts-sheet__head">
                <span class="amts-sheet__lbl">Lokasi Anda</span>
                <a href="#" id="arr-refresh" class="amts-sheet__refresh">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
                    Perbarui Lokasi
                </a>
            </div>
            <iframe id="arr-map" class="amts-sheet__map" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="Lokasi Anda"></iframe>
            <p class="amts-sheet__geo" id="arr-geo" role="status">Mencari lokasi...</p>
        </div>

        <form method="post" action="<?= amt_e(amt_ship_url('amt_spbu', $s)) ?>" id="arrForm">
            <input type="hidden" name="action" value="submit_spbu_arrive">
            <input type="hidden" name="id" value="<?= amt_e($s['id']) ?>">
            <input type="hidden" name="lat" id="arr-lat" value="">
            <input type="hidden" name="lng" id="arr-lng" value="">
            <button type="submit" class="amts-btn amts-btn--solid is-locked" id="arr-yes" aria-disabled="true">Ya, pengiriman telah tiba</button>
        </form>
        <button type="button" class="amts-btn amts-btn--outline" id="arr-close">Tutup</button>
    </div>
</div>

<script>
(function () {
    var sheet = document.getElementById('arrSheet');
    if (!sheet) return;
    document.body.appendChild(sheet);                 // di luar .content: tidak ikut tergulir / terpotong

    var form  = document.getElementById('arrForm');
    var yes   = document.getElementById('arr-yes');
    var map   = document.getElementById('arr-map');
    var geo   = document.getElementById('arr-geo');
    var latEl = document.getElementById('arr-lat');
    var lngEl = document.getElementById('arr-lng');
    var BASE      = { lat: parseFloat(sheet.dataset.lat), lng: parseFloat(sheet.dataset.lng) };
    var RADIUS    = parseFloat(sheet.dataset.radius);
    var OUT_CHANCE = parseFloat(sheet.dataset.outChance);
    var CODE      = <?= json_encode('SPBU ' . $s['spbu']) ?>;
    var PIN_FALLBACK_MS = 6000;      // bila peta gagal memuat, tetap dianggap siap agar tidak macet
    var inRange = false, mapToken = 0, pinTimer = null, lastFocus = null;

    function distance(a, b) {        // haversine (meter)
        var R = 6371000, t = Math.PI / 180;
        var dLat = (b.lat - a.lat) * t, dLng = (b.lng - a.lng) * t;
        var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(a.lat * t) * Math.cos(b.lat * t) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
    }
    function pointAt(meters) {       // titik acak pada jarak tertentu dari SPBU
        var ang = Math.random() * 2 * Math.PI;
        return {
            lat: BASE.lat + (meters * Math.cos(ang)) / 111320,
            lng: BASE.lng + (meters * Math.sin(ang)) / (111320 * Math.cos(BASE.lat * Math.PI / 180))
        };
    }
    function announce() { document.dispatchEvent(new CustomEvent('amt:state')); }

    // Tombol "Ya, pengiriman telah tiba" hanya aktif bila posisi sudah di SPBU tujuan.
    function refresh() {
        yes.classList.toggle('is-locked', !inRange);
        yes.setAttribute('aria-disabled', inRange ? 'false' : 'true');
        sheet.setAttribute('data-loc', inRange ? '1' : '0');
        announce();
    }

    function setPos(p) {
        var d = Math.round(distance(p, BASE));
        inRange = d <= RADIUS;
        latEl.value = p.lat.toFixed(6);
        lngEl.value = p.lng.toFixed(6);

        // data-pin = 1 hanya bila lokasi sesuai DAN peta selesai dimuat.
        var token = ++mapToken;
        clearTimeout(pinTimer);
        sheet.setAttribute('data-pin', '0');
        function pinReady() {
            if (token !== mapToken || !inRange) return;
            clearTimeout(pinTimer);
            map.onload = null;
            sheet.setAttribute('data-pin', '1');
            announce();
        }
        map.onload = inRange ? pinReady : null;
        if (inRange) pinTimer = setTimeout(pinReady, PIN_FALLBACK_MS);

        // Pin Google Maps tepat di koordinat posisi pengguna saat ini.
        map.src = 'https://maps.google.com/maps?q=' + p.lat.toFixed(6) + ',' + p.lng.toFixed(6) +
                  '&hl=id&z=17&output=embed&t=' + Date.now();
        geo.className = 'amts-sheet__geo ' + (inRange ? 'ok' : 'bad');
        geo.textContent = inRange
            ? 'Lokasi sesuai: ' + CODE
            : 'Lokasi belum sesuai ' + CODE + ' (' + d + ' m). Ketuk "Perbarui Lokasi".';
        refresh();
    }
    function simulate(forceInside) {
        // Posisi awal bisa "melenceng" (simulasi); Perbarui Lokasi selalu memindahkan ke dalam radius.
        var outside = !forceInside && Math.random() < OUT_CHANCE;
        setPos(outside ? pointAt(RADIUS * 3 + Math.random() * RADIUS * 4) : { lat: BASE.lat, lng: BASE.lng });
    }

    function openSheet() {
        lastFocus = document.activeElement;
        sheet.hidden = false;
        sheet.classList.add('is-open');
        document.addEventListener('keydown', onKey);
        simulate(false);
        document.getElementById('arr-close').focus();
        announce();
    }
    function closeSheet() {
        sheet.classList.remove('is-open');
        sheet.hidden = true;
        clearTimeout(pinTimer);
        mapToken++;
        map.onload = null;
        map.removeAttribute('src');
        sheet.setAttribute('data-loc', '0');
        sheet.setAttribute('data-pin', '0');
        document.removeEventListener('keydown', onKey);
        if (lastFocus && lastFocus.focus) lastFocus.focus();
        announce();
        if (window.OneFISTour && window.OneFISTour.rescan) window.OneFISTour.rescan();
    }
    function onKey(e) {
        if (e.key === 'Escape') { closeSheet(); }
    }

    document.querySelectorAll('[data-spbu-open="arrSheet"]').forEach(function (opener) {
        opener.addEventListener('click', openSheet);
        opener.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openSheet(); }
        });
    });
    document.getElementById('arr-close').addEventListener('click', closeSheet);
    sheet.addEventListener('click', function (e) { if (e.target === sheet) closeSheet(); });
    document.getElementById('arr-refresh').addEventListener('click', function (e) {
        e.preventDefault();
        simulate(true);                                // Perbarui Lokasi -> selalu masuk radius
    });
    form.addEventListener('submit', function (e) {
        if (!inRange) { e.preventDefault(); return; }  // lokasi belum sesuai
        yes.classList.add('is-locked');                // cegah kirim ganda
        yes.setAttribute('aria-disabled', 'true');
        yes.textContent = 'Mengirim…';
    });
})();
</script>
<?php endif; ?>