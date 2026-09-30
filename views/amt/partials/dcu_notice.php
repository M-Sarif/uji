<?php
/**
 * Alur setelah Check-In di dashboard AMT.
 * Include di bagian paling bawah view dashboard AMT:
 *
 *     <?php include __DIR__ . '/partials/dcu_notice.php'; ?>
 *
 * Tile PTI di dashboard (elemen terluar, ikon + label):
 *
 *     <a href="?screen=amt_pti" data-amt-pti <?= amt_pti_icon_attrs() ?>> ...ikon PTI... </a>
 *
 * Urutan waktu (setelah Check-In berhasil):
 *   1 detik  -> tutorial "Lakukan DCU dulu" muncul
 *   7 detik  -> kalau tidak diklik, tutorial hilang sendiri
 *   2 detik setelah tutorial hilang -> PTI aktif (tidak pudar, bisa ditekan)
 *   1 detik setelah PTI aktif -> petunjuk "Buka PTI" menyorot ikon PTI
 *   1 detik setelah halaman PTI dibuka -> tutorial di halaman PTI (views/amt/pti.php)
 *
 * Handler Check-In harus memanggil amt_flow_checkin_success().
 * Kalau dashboard sudah punya tutorial DCU sendiri, hapus supaya tidak tampil dua kali.
 */
require_once __DIR__ . '/../../../includes/amt/amt_pti_functions.php';

$flowCheckin  = amt_flow_get('checkin');
$flowDcuSeen  = amt_flow_get('dcu_seen');
$flowUnlocked = amt_pti_unlocked();
$flowHintSeen = amt_flow_get('pti_hint_seen');
$flowTut      = amt_flow_tutorials();

if ($flowCheckin && !$flowDcuSeen) {
    amt_tutorial_modal('dcuTutorial', $flowTut['dcu']);
}
if ($flowCheckin && !$flowHintSeen) {
    amt_tutorial_spot('ptiHint', $flowTut['pti_hint']);
}
?>
<link rel="stylesheet" href="assets/amt-pti.css">
<script src="assets/amt-flow.js"></script>
<script>
(function () {
    var checkin  = <?= $flowCheckin ? 'true' : 'false' ?>;
    var dcuSeen  = <?= $flowDcuSeen ? 'true' : 'false' ?>;
    var unlocked = <?= $flowUnlocked ? 'true' : 'false' ?>;
    var hintSeen = <?= $flowHintSeen ? 'true' : 'false' ?>;

    var pti = document.querySelector('[data-amt-pti]');

    function block(e) { e.preventDefault(); e.stopPropagation(); }
    function lock() {
        if (!pti) return;
        pti.setAttribute('data-amt-unlocked', 'false');
        pti.setAttribute('aria-disabled', 'true');
        pti.removeEventListener('click', block, true);
        pti.addEventListener('click', block, true);
    }
    function unlock() {
        if (!pti) return;
        pti.setAttribute('data-amt-unlocked', 'true');
        pti.removeAttribute('aria-disabled');
        pti.removeEventListener('click', block, true);
    }

    // Petunjuk yang menyorot ikon PTI
    function showHint() {
        var el = document.getElementById('ptiHint');
        if (!el || !pti || hintSeen) return;
        var hole  = el.querySelector('.amt-spot__hole');
        var card  = el.querySelector('.amt-spot__card');
        var arrow = el.querySelector('.amt-spot__arrow');
        var ctl;

        function place() {
            var pad = 6, gap = 14;
            var r = pti.getBoundingClientRect();
            hole.style.left = (r.left - pad) + 'px';
            hole.style.top = (r.top - pad) + 'px';
            hole.style.width = (r.width + pad * 2) + 'px';
            hole.style.height = (r.height + pad * 2) + 'px';

            var cw = card.offsetWidth, ch = card.offsetHeight;
            var left = Math.min(Math.max(12, r.left + r.width / 2 - cw / 2), window.innerWidth - cw - 12);
            var below = r.bottom + pad + gap + ch < window.innerHeight;
            card.style.left = left + 'px';
            card.style.top = (below ? r.bottom + pad + gap : r.top - pad - gap - ch) + 'px';
            arrow.style.left = Math.max(12, Math.min(cw - 24, r.left + r.width / 2 - left - 6)) + 'px';
            el.classList.toggle('is-below', below);
            el.classList.toggle('is-above', !below);
        }

        window.addEventListener('resize', place);
        ctl = AmtFlow.show(el, {
            delay: <?= (int) AMT_PTI_HINT_DELAY ?>,
            beforeShow: place,
            onClose: function () {
                window.removeEventListener('resize', place);
                AmtFlow.ack('pti_hint_seen');
            }
        });
        pti.addEventListener('click', function () { ctl.close(); }, { once: true });
    }

    function activatePti() {
        unlock();
        AmtFlow.ack('pti_unlocked');
        showHint();
    }
    function activateLater() { setTimeout(activatePti, <?= (int) AMT_PTI_UNLOCK_DELAY ?>); }

    if (!checkin) { lock(); return; }                 // belum Check-In: PTI tetap nonaktif
    if (unlocked) { unlock(); showHint(); return; }   // PTI sudah aktif sebelumnya

    lock();
    if (dcuSeen) { activateLater(); return; }         // dimuat ulang setelah tutorial DCU

    AmtFlow.show(document.getElementById('dcuTutorial'), {
        delay: <?= (int) AMT_DCU_SHOW_DELAY ?>,
        autoHide: <?= (int) AMT_DCU_VISIBLE_FOR ?>,
        onClose: function () {
            AmtFlow.ack('dcu_seen');
            activateLater();
        }
    });
})();
</script>