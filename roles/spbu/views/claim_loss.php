<?php
$loId   = isset($_GET['lo']) && array_key_exists((string) $_GET['lo'], LO_LIST)
    ? (string) $_GET['lo']
    : null;
$method = isset($_GET['metode']) && array_key_exists($_GET['metode'], MEASUREMENT_METHODS)
    ? $_GET['metode']
    : 'ijkbout';
$loQuery = $loId !== null ? '&lo=' . urlencode($loId) : '';

// Draft hasil "Generate" (belum final) & data yang sudah final tersimpan,
// kalau ada, untuk Nomor LO ini.
$draft     = $loId !== null ? ($_SESSION['lo_form_draft'][$loId] ?? null) : null;
$finalForm = $loId !== null ? ($_SESSION['lo_form'][$loId] ?? null) : null;

// Nilai yang sudah pernah diisi untuk LO ini (draft diprioritaskan supaya
// isian form tidak hilang saat pop up Hasil Generate ditutup / Batal).
$source = ($draft !== null && $draft['metode'] === $method) ? $draft
    : (($finalForm !== null && $finalForm['metode'] === $method) ? $finalForm : null);
$saved  = $source !== null ? $source['nilai'] : [];

// Tampilkan pop up "Hasil Generate Claim Losses" hanya kalau baru saja
// klik "Generate" (flag ?hasil=1) DAN draft-nya cocok dengan metode aktif.
$showResult = ($_GET['hasil'] ?? '') === '1' && $draft !== null && $draft['metode'] === $method;

$claimLossResult = $draft !== null ? (float) $draft['claim_loss'] : 0.0;
$bisaKlaim        = $claimLossResult > 0;
$batalHref        = 'index.php?screen=claim_loss&metode=' . urlencode($method) . $loQuery;

// Sama seperti $activeLoIds di views/checklist.php: LO yang sedang
// dikerjakan pada wizard ini. Dipakai HANYA untuk tutorial Soal 7 (lihat
// CHECKLIST_SOAL7_TOUR_STEPS di roles/spbu/includes/tour_data.php) supaya langkah-langkah
// di layar claim_loss ini tahu apakah ini LO PERTAMA yang diisi (tampilkan
// penjelasan lengkap) atau LO kedua dan seterusnya (diam-diam saja,
// penjelasannya sudah pernah ditampilkan sebelumnya di LO pertama).
$ukurActiveLoIds = array_keys(array_filter($_SESSION['lo_checked']));
if (empty($ukurActiveLoIds)) {
    $ukurActiveLoIds = array_keys(LO_LIST);
}
$ukurAnyDone = false;
foreach ($ukurActiveLoIds as $ukurCheckId) {
    if (!empty($_SESSION['lo_form'][$ukurCheckId])) { $ukurAnyDone = true; break; }
}
?>
<form method="post" action="index.php" id="claimForm">
    <input type="hidden" name="action" value="generate_claim_loss">
    <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
    <input type="hidden" name="metode" value="<?php echo h($method); ?>">

    <div class="claim-pad" data-tour-any-done="<?php echo $ukurAnyDone ? '1' : '0'; ?>">
        <div data-tour="claim-metode-group">
        <div class="claim-head">
            <h2 class="claim-title">Metode Pengukuran</h2>
            <a href="#" class="claim-loss-link">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
                Syarat Claim Losses
            </a>
        </div>
        <p class="claim-sub">Pilih metode pengukuran pembongkaran BBM.</p>

        <?php foreach (MEASUREMENT_METHODS as $key => $m): $isSelected = $key === $method; ?>
        <a href="index.php?screen=claim_loss&metode=<?php echo h($key); ?><?php echo $loQuery; ?>" class="method-card<?php echo $isSelected ? ' selected' : ''; ?>">
            <div class="method-main">
                <p class="method-title"><?php echo h($m['title']); ?></p>
                <p class="method-desc"><?php echo h($m['desc']); ?></p>
            </div>
            <span class="radio-dot<?php echo $isSelected ? ' checked' : ''; ?>"></span>
        </a>
        <?php endforeach; ?>
        </div>

        <div class="claim-form">
            <?php foreach (MEASUREMENT_METHODS[$method]['fields'] as $field):
                // Kolom desimal seperti "Density Obs" (contoh: 0.989 /
                // 0.9898) butuh beberapa digit di belakang koma supaya
                // benar-benar akurat -- kalau tombol "Generate" sudah aktif
                // begitu baru ketik "0", pengguna keburu ternavigasi keluar
                // (mis. tersentuh/ter-Enter) SEBELUM selesai mengetik semua
                // angkanya. 'data-min-length' (dibaca oleh skrip validasi &
                // tutorial di bawah) menahan tombol tetap nonaktif sampai
                // panjang isian kolom ini mencapai minimal segini dulu.
                // Kolom lain (angka bulat seperti Kompartemen/Temperatur)
                // tetap cukup diisi apa saja (tidak dibatasi panjang).
                $minLen = ($field['key'] === 'density_obs') ? 5 : 1;
            ?>
            <div class="claim-form-field">
                <label for="f_<?php echo h($field['key']); ?>"><?php echo h($field['label']); ?> <span class="req">*</span></label>
                <div class="claim-input-wrap">
                    <input type="text" inputmode="decimal"
                           id="f_<?php echo h($field['key']); ?>"
                           name="<?php echo h($field['key']); ?>"
                           value="<?php echo isset($saved[$field['key']]) ? h(rtrim(rtrim(number_format($saved[$field['key']], 3, '.', ''), '0'), '.')) : ''; ?>"
                           placeholder="<?php echo h($field['placeholder'] ?? '0'); ?>"
                           data-min-length="<?php echo (int) $minLen; ?>" required>
                    <?php if (!empty($field['unit'])): ?><span class="unit"><?php echo h($field['unit']); ?></span><?php endif; ?>
                </div>
                <?php if (!empty($field['hint'])): ?>
                <p class="claim-field-hint">*<?php echo h($field['hint']); ?></p>
                <?php endif; ?>
                <?php if ($field['key'] === 'density_obs'): ?>
                <p class="claim-field-hint">*Isi paling sedikit beberapa digit di belakang koma (contoh: 0.989 atau 0.9898) untuk hasil yang akurat.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="wizard-nav">
        <a href="#" class="btn-wiz">Rumus Hitung</a>
        <button type="submit" class="btn-primary btn-generate" id="btnGenerate" disabled>
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
            Generate
        </button>
    </div>
</form>

<?php if ($showResult): ?>
<div class="modal-backdrop" id="hasilModal">
    <div class="modal-sheet hasil-generate-sheet" role="dialog" aria-modal="true" aria-labelledby="hasilJudul" data-tour-any-done="<?php echo $ukurAnyDone ? '1' : '0'; ?>">
        <span class="sheet-grabber" aria-hidden="true"></span>
        <h2 id="hasilJudul">Hasil Generate Claim Loss</h2>

        <div class="hasil-row">
            <span class="hasil-label">Selisih Kurang yang Dapat di Klaim</span>
            <span class="hasil-value">
                <span class="hasil-angka"><?php echo h(format_liter_asli($claimLossResult)); ?></span>
                <span class="hasil-satuan">Liter</span>
            </span>
        </div>
        <div class="hasil-row">
            <span class="hasil-label">Status Generate</span>
            <span class="hasil-badge <?php echo $bisaKlaim ? 'bisa' : 'tidak-bisa'; ?>">
                <?php echo $bisaKlaim ? 'Dapat di Klaim' : 'Tidak Bisa di Klaim'; ?>
                <?php if ($bisaKlaim): ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M7.5 12.5l3 3 6-6.5" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php endif; ?>
            </span>
        </div>

        <div class="hasil-barrel">
            <img src="<?php echo SPBU_URL; ?>/img/ilustrasi/fuel-barrel.png" alt="Claim Loss">
        </div>

        <?php if ($bisaKlaim): ?>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="save_claim_loss">
                <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
                <input type="hidden" name="metode" value="<?php echo h($method); ?>">
                <input type="hidden" name="klaim" value="ajukan">
                <button type="submit" class="btn-primary hasil-btn-main" data-tour="hasil-primary-action">Ajukan Klaim</button>
            </form>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="save_claim_loss">
                <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
                <input type="hidden" name="metode" value="<?php echo h($method); ?>">
                <input type="hidden" name="klaim" value="tanpa">
                <button type="submit" class="btn-outline hasil-btn-full hasil-btn-second">Simpan Tanpa Klaim</button>
            </form>
        <?php else: ?>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="save_claim_loss">
                <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
                <input type="hidden" name="metode" value="<?php echo h($method); ?>">
                <input type="hidden" name="klaim" value="">
                <button type="submit" class="btn-primary hasil-btn-main" data-tour="hasil-primary-action">Simpan</button>
            </form>
        <?php endif; ?>

        <a href="<?php echo h($batalHref); ?>" class="hasil-batal modal-close" id="btnHasilBatal">Batal</a>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('hasilModal');
    var batal = document.getElementById('btnHasilBatal');
    if (!modal || !batal) { return; }

    /* klik area gelap di luar kartu, atau tombol Escape, = Batal */
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { batal.click(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { batal.click(); }
    });
})();
</script>
<?php endif; ?>

<script>
/* Tombol "Generate" baru aktif setelah semua isian wajib diisi -- dan
   khusus kolom yang punya 'data-min-length' (mis. "Density Obs"), baru
   dianggap terisi kalau panjang isiannya sudah mencapai minimal segitu,
   supaya tidak dianggap "selesai" begitu baru ketik satu digit saja. */
(function () {
    var form = document.getElementById('claimForm');
    if (!form) { return; }

    var btn    = document.getElementById('btnGenerate');
    var inputs = form.querySelectorAll('input[type="text"][required]');

    function fieldFilled(el) {
        var val    = el.value.trim();
        var minLen = parseInt(el.getAttribute('data-min-length') || '1', 10) || 1;
        return val !== '' && val.length >= minLen;
    }

    function refresh() {
        var lengkap = true;
        inputs.forEach(function (el) {
            if (!fieldFilled(el)) { lengkap = false; }
        });
        btn.disabled = !lengkap;
    }

    inputs.forEach(function (el) { el.addEventListener('input', refresh); });
    refresh();

    /* Jaga-jaga: tombol "Enter"/"Done" di keyboard (terutama keyboard
       angka di HP) bisa langsung men-submit form walau tombol "Generate"
       kelihatannya belum sempat berubah nonaktif -> aktif dengan benar.
       Kalau ini terjadi SELAGI ada kolom yang belum memenuhi syarat
       (termasuk syarat panjang minimal), batalkan submit-nya. */
    form.addEventListener('submit', function (e) {
        var lengkap = true;
        inputs.forEach(function (el) { if (!fieldFilled(el)) { lengkap = false; } });
        if (!lengkap) { e.preventDefault(); }
    });
})();
</script>