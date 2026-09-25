</div><!-- /.content -->
</div><!-- /.app-container -->

<script>
/* Toast notifikasi sukses (flash message): tampil sebentar lalu hilang sendiri. */
(function () {
    var toast = document.getElementById('toastSuccess');
    if (!toast) { return; }

    requestAnimationFrame(function () { toast.classList.add('is-visible'); });

    setTimeout(function () {
        toast.classList.remove('is-visible');
        toast.classList.add('is-leaving');
        toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
    }, 2500);
})();
</script>

<script src="assets/js/tutorial.js?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/tutorial.js'); ?>"></script>
<script>
/*
 * Tutorial terpandu (guided tour) — menyorot elemen asli halaman satu per
 * satu, persis urutan dokumen panduan "Aktifitas di SPBU". Tampil otomatis
 * saat pertama kali membuka tiap layar, tersimpan di localStorage supaya
 * tidak berulang, dan bisa dimulai ulang lewat tombol "?" mengambang.
 */
(function () {
    var screenOrder  = <?php echo json_encode(TOUR_SCREEN_ORDER); ?>;
    var screenLabels = <?php echo json_encode(TOUR_SCREEN_LABELS); ?>;
    var steps        = <?php echo json_encode(TOUR_STEPS[$screen] ?? []); ?>;
    var idx          = screenOrder.indexOf(<?php echo json_encode($screen); ?>);

    window.OneFISTour.init({
        screen:       <?php echo json_encode($screen); ?>,
        steps:        steps,
        screenOrder:  screenOrder,
        screenLabels: screenLabels,
        screenIndex:  idx === -1 ? 0 : (idx + 1),
    });
})();
</script>

</body>
</html>