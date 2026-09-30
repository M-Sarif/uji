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

<?php
// AMT memakai mesin tutorial + gaya sendiri (terpisah dari SPBU); peran lain tetap tutorial.js.
$tourEngine = (current_role() === 'amt') ? 'assets/amt/js/tutorial-amt.js' : 'assets/js/tutorial.js';
?>
<?php if (current_role() === 'amt'): ?>
<link rel="stylesheet" href="assets/amt/css/tutorial-amt.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/amt/css/tutorial-amt.css'); ?>">
<?php endif; ?>
<script src="<?php echo $tourEngine; ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../' . $tourEngine); ?>"></script>
<script>
/*
 * Tutorial terpandu (guided tour) — menyorot elemen asli halaman satu per
 * satu, persis urutan dokumen panduan "Aktifitas di SPBU". Tampil otomatis
 * saat pertama kali membuka tiap layar, tersimpan di localStorage supaya
 * tidak berulang, dan bisa dimulai ulang lewat tombol "?" mengambang.
 */
(function () {
    <?php
    // Peran AMT memakai data tutorial (includes/amt/tutorial.php) DAN mesin
    // sendiri (assets/amt/js/tutorial-amt.js), terpisah dari tutorial SPBU.
    $amtTour = (current_role() === 'amt') ? amt_tour_config($screen) : null;
    ?>
    var screenOrder  = <?php echo json_encode($amtTour['screenOrder']  ?? TOUR_SCREEN_ORDER); ?>;
    var screenLabels = <?php echo json_encode($amtTour['screenLabels'] ?? TOUR_SCREEN_LABELS); ?>;
    <?php
    // Soal 6 ("Periksa kesesuaian SPP...") pada wizard checklist punya
    // tutorial tersendiri (CHECKLIST_SOAL6_TOUR_STEPS) yang menyorot
    // sub-langkah verifikasi Produk lalu Segel lewat pop up -- lihat
    // catatan panjang di includes/data.php. $tourSubKey memisahkan
    // status "sudah ditonton"-nya dari tutorial umum layar 'checklist'
    // (langkah 1-15 lainnya), supaya tetap tampil pertama kali soal ini
    // dicapai walau tutorial umum sudah pernah ditonton.
    $isChecklistSoal6 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 6;
    // Soal 7 ("Isi form Metode Pengukuran...") mencakup DUA layar: daftar
    // LO di 'checklist' (step 7) dan form pengukurannya sendiri di layar
    // terpisah 'claim_loss' -- makanya subKey-nya sama ('checklist_soal7')
    // untuk KEDUANYA, supaya progres tutorialnya tetap nyambung dan
    // dilacak sebagai SATU rangkaian tutorial yang sama walau berpindah
    // layar (lihat catatan panjang di includes/data.php).
    $isChecklistSoal7 = ($screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 7)
        || $screen === 'claim_loss';
    // Soal 8 (Test Report): hanya satu langkah -- setelah 5 detik tanpa aksi,
    // tutorial muncul menyorot "Selanjutnya" (tidak berpindah halaman sendiri).
    $isChecklistSoal8 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 8;
    // Soal 15 (Konfirmasi Status LO) dan Daftar LO berstatus "Draft"
    // (siap dikirim) masing-masing punya tutorial sendiri.
    $isChecklistSoal15 = $screen === 'checklist' && ($_SESSION['checklist_step'] ?? null) === 15;
    // Rating AMT: langkah 2 (AMT 2, tombol "Kirim") punya tutorial sendiri.
    $isRatingAmt2 = $screen === 'rating' && (int) ($_SESSION['rating_step'] ?? 1) >= 2;
    $isLoKirim = $screen === 'lo_list' && !empty(array_filter($_SESSION['lo_draft'] ?? []));
    $tourSteps  = $isChecklistSoal6 ? CHECKLIST_SOAL6_TOUR_STEPS
        : ($isChecklistSoal7 ? CHECKLIST_SOAL7_TOUR_STEPS
        : ($isChecklistSoal8 ? CHECKLIST_SOAL8_TOUR_STEPS
        : ($isChecklistSoal15 ? CHECKLIST_SOAL15_TOUR_STEPS
        : ($isLoKirim ? LO_KIRIM_TOUR_STEPS
        : ($isRatingAmt2 ? RATING_AMT2_TOUR_STEPS
        : (TOUR_STEPS[$screen] ?? []))))));
    $tourSubKey = $isChecklistSoal6 ? 'checklist_soal6'
        : ($isChecklistSoal7 ? 'checklist_soal7'
        : ($isChecklistSoal8 ? 'checklist_soal8'
        : ($isChecklistSoal15 ? 'checklist_soal15'
        : ($isLoKirim ? 'lo_list_kirim'
        : ($isRatingAmt2 ? 'rating_amt2' : null)))));
    $tourJourney = null;   // alur tutorial AMT: ['label' => ..., 'total' => ...]
    $tourDelay   = 0;      // jeda (ms) sebelum tutorial AMT pertama kali muncul
    $tourEpoch   = null;   // penanda siklus AMT (lihat amt_tour_epoch())
    $tourPersist = true;   // false = jangan simpan status "selesai" (layar form)
    if ($amtTour !== null) {
        $tourSteps   = $amtTour['steps'];
        $tourSubKey  = $amtTour['subKey'];
        $tourJourney = $amtTour['journey'] ?? null;
        $tourDelay   = (int) ($amtTour['startDelay'] ?? 0);
        $tourEpoch   = $amtTour['epoch'] ?? null;
        $tourPersist = (bool) ($amtTour['persist'] ?? true);
    }
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
        // Alur tutorial AMT (label + jumlah langkah) untuk teks "Langkah X dari Y".
        // Diabaikan oleh mesin SPBU.
        journey:      <?php echo json_encode($tourJourney); ?>,
        // Jeda (ms) sebelum tutorial AMT muncul; 0 = jeda bawaan. Diabaikan mesin SPBU.
        startDelay:   <?php echo json_encode($tourDelay); ?>,
        // Penanda siklus + apakah status "selesai" boleh disimpan (khusus mesin AMT).
        epoch:        <?php echo json_encode($tourEpoch); ?>,
        persistDone:  <?php echo json_encode($tourPersist); ?>,
        // true persis pada request yang baru saja mereset progres alur
        // (balik ke dashboard/beranda AMT) -- lihat index.php.
        flowWasReset: <?php echo json_encode($flowWasReset); ?>,
        // Layar simulasi bertimer: tutorial selalu mulai dari langkah pertama
        // tiap layar dibuka (lihat tutorial.js -> restartEveryVisit).
        restartEveryVisit: <?php echo json_encode(in_array($screen, ['notifikasi', 'qr_code', 'rating'], true)); ?>,
    });
})();
</script>

</body>
</html>