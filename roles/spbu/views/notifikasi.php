<?php

$mt   = ARRIVAL_SUBJECTS['mt_ok'];
$amt  = ARRIVAL_SUBJECTS['amt_ok'];

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

    <?php

    ?>
    <p class="notif-empty" id="notifKosong" hidden style="font-size:12px;color:#94a3b8;text-align:center;margin:1.5rem 0;">Belum ada notifikasi Verifikasi Order.</p>

    <div class="notif-list" data-type="verifikasi" id="notifVerifikasi" hidden>
        <a href="index.php?screen=qr_code" class="card notif-card" id="notifVerifikasiCard" style="display:block;text-decoration:none;">
            <div class="notif-row">
                <span class="notif-title">Verifikasi Order</span>
                <span class="notif-time">Hari ini, <?php echo h(date('H.i')); ?></span>
            </div>
            <p style="font-size:12px;color:#64748b;margin:2px 0 8px;">Permintaan verifikasi dari AMT <?php echo h($amt['name']); ?> · MT <?php echo h($mt['name']); ?></p>
            <span class="chip chip-pending">Aktif</span>
        </a>
    </div>

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

<div class="modal-backdrop modal-center" id="verifOrderModal" hidden>
    <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="verifOrderJudul">
        <h2 id="verifOrderJudul">Permintaan Verifikasi Order</h2>
        <p>Anda mendapat permintaan verifikasi dari AMT. Buka halaman notifikasi untuk melihat kode konfirmasi.</p>

        <div class="verif-modal-box">
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
        </div>

        <div class="verif-modal-actions">
            <button type="button" class="verif-modal-btn verif-modal-btn--ghost" id="btnTutupVerifOrder">Tutup</button>
            <a href="index.php?screen=qr_code" class="verif-modal-btn verif-modal-btn--solid" id="btnLihatNotifikasi">Lihat Notifikasi</a>
        </div>
    </div>
</div>

<script>
(function () {

    var tabs = document.querySelectorAll('.pill-tab');
    var lists = document.querySelectorAll('.notif-list');
    var notifList  = document.getElementById('notifVerifikasi');
    var notifCard  = document.getElementById('notifVerifikasiCard');
    var notifKosong = document.getElementById('notifKosong');
    var currentFilter = 'semua';
    var notifArrived = false;

    function applyFilter() {
        lists.forEach(function (list) {
            var show = currentFilter === 'semua' || list.getAttribute('data-type') === (currentFilter === 'verifikasi' ? 'verifikasi' : 'claim');
            list.style.display = show ? '' : 'none';
        });

        notifKosong.hidden = !(currentFilter === 'verifikasi' && !notifArrived);
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            currentFilter = tab.getAttribute('data-filter');
            applyFilter();
        });
    });

    var modal = document.getElementById('verifOrderModal');

    function tutupModal() {
        modal.hidden = true;
        modal.setAttribute('data-dismissed', '1');
        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }

    function munculkanPermintaan() {
        notifArrived = true;
        notifCard.setAttribute('data-tour', 'notif-verifikasi');
        notifList.hidden = false;
        applyFilter();
        modal.hidden = false;
        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }

    function kartuTutorialTampil() {
        return !!document.querySelector('.sptt-panel.is-on');
    }
    function tungguTutorial() {
        if (kartuTutorialTampil()) { setTimeout(tungguTutorial, 300); return; }
        munculkanPermintaan();
    }
    setTimeout(function () {

        setTimeout(tungguTutorial, 1200);
    }, 3800);

    document.getElementById('btnTutupVerifOrder').addEventListener('click', tutupModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { tutupModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) { tutupModal(); }
    });
})();
</script>