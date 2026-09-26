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
    <?php
    // Soal 6 ("Periksa kesesuaian SPP...") pada wizard checklist punya
    // tutorial tersendiri (CHECKLIST_SOAL6_TOUR_STEPS) yang menyorot
    // sub-langkah verifikasi Produk lalu Segel lewat pop up -- lihat
    // catatan panjang di includes/data.php. $tourSubKey memisahkan
    // status "sudah ditonton"-nya dari tutorial umum layar 'checklist'
    // (langkah 1-15 lainnya), supaya tetap tampil pertama kali soal ini
    // dicapai walau tutorial umum sudah pernah ditonton.
    $isChecklistSoal6 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 6;
    $tourSteps  = $isChecklistSoal6 ? CHECKLIST_SOAL6_TOUR_STEPS : (TOUR_STEPS[$screen] ?? []);
    $tourSubKey = $isChecklistSoal6 ? 'checklist_soal6' : null;
    ?>
    var steps        = <?php echo json_encode($tourSteps); ?>;
    var idx          = screenOrder.indexOf(<?php echo json_encode($screen); ?>);

    window.OneFISTour.init({
        screen:       <?php echo json_encode($screen); ?>,
        steps:        steps,
        screenOrder:  screenOrder,
        screenLabels: screenLabels,
        screenIndex:  idx === -1 ? 0 : (idx + 1),
        // Kunci pelacakan "sudah ditonton" + posisi langkah terpisah dari
        // nama layar biasa (lihat tutorial.js -> this.posKey). null berarti
        // pakai nama layar seperti biasa.
        subKey:       <?php echo json_encode($tourSubKey); ?>,
        // true persis pada request yang baru saja mereset progres alur
        // (balik ke dashboard/beranda AMT) -- lihat index.php.
        flowWasReset: <?php echo json_encode($flowWasReset); ?>,
    });
})();
</script>

</body>
</html>