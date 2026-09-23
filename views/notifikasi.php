<?php
/**
 * Layar "Notifikasi".
 * Dibuka saat pengguna menekan langkah "Verifikasi Order" pada halaman
 * Detail Order (views/shipment.php).
 *
 * 5 detik setelah halaman ini terbuka, muncul pop up "Permintaan
 * Verifikasi Order" yang datanya diambil dari data verifikasi kedatangan
 * (ARRIVAL_SUBJECTS - nomor polisi mobil tangki & nama AMT) dan Daftar LO
 * (LO_LIST) supaya sama persis dengan yang sudah diisi pengguna di
 * tampilan Checklist. Menekan "Lihat Notifikasi" akan membawa pengguna
 * ke halaman "Permintaan Verifikasi" (views/qr_code.php).
 */

$mt   = ARRIVAL_SUBJECTS['mt_ok'];
$amt  = ARRIVAL_SUBJECTS['amt_ok'];

// LO yang dicentang pengguna di Daftar LO. Kalau belum ada yang
// dicentang (mis. langsung dibuka tanpa lewat checklist), tampilkan
// semua LO supaya pop up tetap terisi data yang valid.
$checked   = array_filter($_SESSION['lo_checked'] ?? []);
$loIds     = !empty($checked) ? array_keys($checked) : array_keys(LO_LIST);
?>
<div class="content-pad notif-body">

    <div class="pill-tabs" style="margin:0 0 1rem 0;">
        <button type="button" class="pill-tab active" data-filter="semua">Semua</button>
        <button type="button" class="pill-tab" data-filter="verifikasi">Verifikasi Order</button>
        <button type="button" class="pill-tab" data-filter="claim">Claim Loss</button>
    </div>

    <p class="notif-date"><?php echo h(date('l, d/m/Y')); ?></p>

    <div class="notif-list" data-type="claim">
        <div class="card notif-card">
            <div class="notif-row">
                <span class="notif-title">Claim Loss</span>
                <span class="notif-time">Hari ini, <?php echo h(date('H.i')); ?></span>
            </div>
            <span class="chip chip-done">Selesai</span>
        </div>
        <div class="card notif-card">
            <div class="notif-row">
                <span class="notif-title">Claim Loss</span>
                <span class="notif-time">Hari ini, <?php echo h(date('H.i')); ?></span>
            </div>
            <span class="chip chip-done">Selesai</span>
        </div>
        <div class="card notif-card">
            <div class="notif-row">
                <span class="notif-title">Claim Loss</span>
                <span class="notif-time">Hari ini, <?php echo h(date('H.i')); ?></span>
            </div>
            <span class="chip chip-done">Selesai</span>
        </div>
    </div>

</div>

<!-- Pop up: Permintaan Verifikasi Order (muncul otomatis setelah 5 detik) -->
<div class="modal-backdrop" id="verifOrderModal" hidden>
    <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="verifOrderJudul">
        <h2 id="verifOrderJudul">Permintaan Verifikasi Order</h2>
        <p>Anda mendapat permintaan verifikasi dari AMT. Buka halaman notifikasi untuk melihat kode konfirmasi.</p>

        <div class="verif-modal-grid">
            <div class="verif-modal-col">
                <span class="label">AMT</span>
                <span class="value"><?php echo h($amt['name']); ?></span>
            </div>
            <div class="verif-modal-col">
                <span class="label">Nomor Polisi MT</span>
                <span class="value"><?php echo h($mt['name']); ?></span>
            </div>
        </div>

        <div class="verif-modal-grid">
            <div class="verif-modal-col">
                <span class="label">LO</span>
                <?php foreach ($loIds as $id): ?>
                    <span class="value"><?php echo h($id); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="verif-modal-col">
                <span class="label">Order</span>
                <?php foreach ($loIds as $id): ?>
                    <span class="value"><?php echo h(LO_LIST[$id]['produk'] ?? LO_LIST[$id]['order']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="button" class="btn-outline modal-close" id="btnTutupVerifOrder">Tutup</button>
        <a href="index.php?screen=qr_code" class="btn-primary" id="btnLihatNotifikasi">Lihat Notifikasi</a>
    </div>
</div>

<script>
(function () {
    /* Filter sederhana untuk tab Semua / Verifikasi Order / Claim Loss */
    var tabs = document.querySelectorAll('.pill-tab');
    var lists = document.querySelectorAll('.notif-list');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var filter = tab.getAttribute('data-filter');
            lists.forEach(function (list) {
                var show = filter === 'semua' || list.getAttribute('data-type') === (filter === 'verifikasi' ? 'verifikasi' : 'claim');
                list.style.display = show ? '' : 'none';
            });
        });
    });

    /* Pop up "Permintaan Verifikasi Order" muncul otomatis 5 detik setelah halaman dibuka */
    var modal = document.getElementById('verifOrderModal');
    var timer = setTimeout(function () {
        modal.hidden = false;
    }, 5000);

    document.getElementById('btnTutupVerifOrder').addEventListener('click', function () {
        modal.hidden = true;
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { modal.hidden = true; }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) { modal.hidden = true; }
    });
})();
</script>