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
// Mesin tutorial milik peran aktif (SPBU: tutorial.js, AMT: tutorial-amt.js + css-nya).
// Sebelum memilih peran dipakai DEFAULT_TOUR_ROLE. Data langkah dari hook <peran>_tour_config().
$tourRole   = current_role() ?? DEFAULT_TOUR_ROLE;
$tourAssets = (array) role_call($tourRole, 'tour_assets');
$tourCfg    = (array) role_call($tourRole, 'tour_config', $screen);
?>
<?php foreach ($tourAssets['css'] ?? [] as $tourCss): ?>
<link rel="stylesheet" href="<?php echo h($tourCss); ?>?v=<?php echo (int) @filemtime(APP_ROOT . '/' . $tourCss); ?>">
<?php endforeach; ?>
<script src="<?php echo $tourAssets['engine']; ?>?v=<?php echo (int) @filemtime(APP_ROOT . '/' . $tourAssets['engine']); ?>"></script>
<script>
/*
 * Tutorial terpandu (guided tour) — menyorot elemen asli halaman satu per
 * satu. Tampil otomatis saat pertama kali membuka tiap layar, tersimpan di
 * localStorage supaya tidak berulang, dan bisa dimulai ulang lewat tombol "?"
 * mengambang. Isi tutorial tiap peran: roles/<peran>/includes/ (tour*.php).
 */
(function () {
    <?php
    $tourSteps   = $tourCfg['steps'];
    $tourSubKey  = $tourCfg['subKey'];
    $tourJourney = $tourCfg['journey'] ?? null;     // alur tutorial AMT: ['label' => ..., 'total' => ...]
    $tourDelay   = (int) ($tourCfg['startDelay'] ?? 0); // jeda (ms) sebelum tutorial AMT pertama kali muncul
    $tourEpoch   = $tourCfg['epoch'] ?? null;       // penanda siklus AMT (lihat amt_tour_epoch())
    $tourPersist = (bool) ($tourCfg['persist'] ?? true); // false = jangan simpan status "selesai" (layar form)
    ?>
    var screenOrder  = <?php echo json_encode($tourCfg['screenOrder']); ?>;
    var screenLabels = <?php echo json_encode($tourCfg['screenLabels']); ?>;
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
        restartEveryVisit: <?php echo json_encode((bool) ($tourCfg['restartEveryVisit'] ?? false)); ?>,
    });
})();
</script>

</body>
</html>