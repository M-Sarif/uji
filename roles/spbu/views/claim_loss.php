<?php
$loId   = isset($_GET['lo']) && array_key_exists((string) $_GET['lo'], LO_LIST)
    ? (string) $_GET['lo']
    : null;
$method = isset($_GET['metode']) && array_key_exists($_GET['metode'], MEASUREMENT_METHODS)
    ? $_GET['metode']
    : DEFAULT_MEASUREMENT_METHOD;
$loQuery = $loId !== null ? '&lo=' . urlencode($loId) : '';

$draft     = $loId !== null ? ($_SESSION['lo_form_draft'][$loId] ?? null) : null;
$finalForm = $loId !== null ? ($_SESSION['lo_form'][$loId] ?? null) : null;

$source = ($draft !== null && $draft['metode'] === $method) ? $draft
    : (($finalForm !== null && $finalForm['metode'] === $method) ? $finalForm : null);
$saved  = $source !== null ? $source['nilai'] : [];

$showResult = ($_GET['hasil'] ?? '') === '1' && $draft !== null && $draft['metode'] === $method;

$claimLossResult = $draft !== null ? (float) $draft['claim_loss'] : 0.0;
$bisaKlaim        = $claimLossResult > 0;
$batalHref        = 'index.php?screen=claim_loss&metode=' . urlencode($method) . $loQuery;

$ukurActiveLoIds = array_keys(array_filter($_SESSION['lo_checked']));
if (empty($ukurActiveLoIds)) {
    $ukurActiveLoIds = array_keys(LO_LIST);
}
$ukurAnyDone = false;
foreach ($ukurActiveLoIds as $ukurCheckId) {
    if (!empty($_SESSION['lo_form'][$ukurCheckId])) { $ukurAnyDone = true; break; }
}
?>
<form method="post" action="index.php" id="claimForm" data-ready="0">
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
                $minLen = ($field['key'] === 'density_obs') ? 5 : 1;
                $type   = $field['type'] ?? 'text';
                $fid    = 'f_' . $field['key'];
                $val    = isset($saved[$field['key']]) ? rtrim(rtrim(number_format($saved[$field['key']], 3, '.', ''), '0'), '.') : '';
            ?>
            <?php if ($type === 'select'): ?>
            <div class="claim-form-field">
                <label for="<?php echo h($fid); ?>"><?php echo h($field['label']); ?> <span class="req">*</span></label>
                <div class="claim-input-wrap claim-select-wrap">
                    <select id="<?php echo h($fid); ?>" name="<?php echo h($field['key']); ?>"<?php echo $val !== '' ? ' class="is-chosen"' : ''; ?> required>
                        <option value="" disabled<?php echo $val === '' ? ' selected' : ''; ?> hidden><?php echo h($field['placeholder'] ?? 'Pilih'); ?></option>
                        <?php foreach (COMPARTMENT_TERA_T2 as $no => $t2): ?>
                        <option value="<?php echo (int) $no; ?>"<?php echo (string) $val === (string) $no ? ' selected' : ''; ?>>Kompartemen <?php echo (int) $no; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="claim-field-hint" id="kompHint">*Pilih kompartemen tempat BBM LO ini berada di MT (di dispatching tercatat kompartemen <?php echo (int) COMPARTMENT_DISPATCH; ?>). T2 Tera MT dan kapasitas mengikuti kompartemen yang dipilih.</p>
            </div>
            <?php elseif ($type === 'tera'): ?>
            <div class="claim-form-field" id="teraField">
                <label for="<?php echo h($fid); ?>"><?php echo h($field['label']); ?> <span class="req">*</span></label>
                <div class="claim-input-wrap" id="teraWrap">
                    <input type="text" inputmode="decimal"
                           id="<?php echo h($fid); ?>"
                           name="<?php echo h($field['key']); ?>"
                           value="<?php echo h($val); ?>"
                           placeholder="0"
                           data-min-length="1" required>
                    <?php if (!empty($field['unit'])): ?><span class="unit"><?php echo h($field['unit']); ?></span><?php endif; ?>
                </div>
                <p class="claim-field-hint" id="teraHint" data-found="Nilai T2 terisi otomatis dari data Tera MT." data-missing="Data T2 Tera MT tidak ditemukan pada master kendaraan, silakan input manual jika diketahui.">*Data T2 Tera MT tidak ditemukan pada master kendaraan, silakan input manual jika diketahui.</p>
            </div>
            <?php else: ?>
            <div class="claim-form-field">
                <label for="<?php echo h($fid); ?>"><?php echo h($field['label']); ?> <span class="req">*</span></label>
                <div class="claim-input-wrap">
                    <input type="text" inputmode="decimal"
                           id="<?php echo h($fid); ?>"
                           name="<?php echo h($field['key']); ?>"
                           value="<?php echo h($val); ?>"
                           placeholder="<?php echo h($field['placeholder'] ?? '0'); ?>"
                           data-min-length="<?php echo (int) $minLen; ?>" required>
                    <?php if (!empty($field['unit'])): ?><span class="unit"><?php echo h($field['unit']); ?></span><?php endif; ?>
                </div>
                <?php if (!empty($field['hint'])): ?>
                <p class="claim-field-hint<?php echo !empty($field['hint_class']) ? ' ' . h($field['hint_class']) : ''; ?>">*<?php echo h($field['hint']); ?></p>
                <?php endif; ?>
                <?php if ($field['key'] === 'density_obs'): ?>
                <p class="claim-field-hint">*Isi paling sedikit beberapa digit di belakang koma (contoh: 0.989 atau 0.9898) untuk hasil yang akurat.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="wizard-nav claim-nav">
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
                <?php else: ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#ef4444"/><path d="M8.5 8.5l7 7M15.5 8.5l-7 7" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/></svg>
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
            <button type="button" class="btn-primary hasil-btn-main" disabled>Ajukan Klaim</button>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="save_claim_loss">
                <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
                <input type="hidden" name="metode" value="<?php echo h($method); ?>">
                <input type="hidden" name="klaim" value="tanpa">
                <button type="submit" class="btn-outline hasil-btn-full hasil-btn-second" data-tour="hasil-primary-action">Simpan Tanpa Klaim</button>
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
(function () {
    var form = document.getElementById('claimForm');
    if (!form) { return; }

    var btn    = document.getElementById('btnGenerate');
    var inputs = form.querySelectorAll('input[type="text"][required], select[required]');
    var komp   = document.getElementById('f_kompartemen');
    var tera   = document.getElementById('f_level_spp');
    var teraHint = document.getElementById('teraHint');
    var teraWrap = document.getElementById('teraWrap');
    var t2 = <?php echo json_encode(COMPARTMENT_TERA_T2); ?>;

    function fieldFilled(el) {
        var val    = el.value.trim();
        var minLen = parseInt(el.getAttribute('data-min-length') || '1', 10) || 1;
        return val !== '' && val.length >= minLen;
    }

    function syncTera(autofill) {
        if (!komp || !tera) { return; }
        var chosen = komp.value !== '' && t2[komp.value] !== undefined;
        if (chosen) { tera.value = String(t2[komp.value]); }
        tera.readOnly = chosen;
        tera.tabIndex = chosen ? -1 : 0;
        if (teraWrap) { teraWrap.classList.toggle('is-locked', chosen); }
        if (teraHint) {
            teraHint.textContent = '*' + teraHint.getAttribute(chosen ? 'data-found' : 'data-missing');
        }
        komp.classList.toggle('is-chosen', komp.value !== '');
    }

    function refresh() {
        var lengkap = true;
        inputs.forEach(function (el) {
            if (!fieldFilled(el)) { lengkap = false; }
        });
        btn.disabled = !lengkap;
        form.setAttribute('data-ready', lengkap ? '1' : '0');
    }

    inputs.forEach(function (el) {
        el.addEventListener('input', refresh);
        el.addEventListener('change', refresh);
    });
    if (komp) { komp.addEventListener('change', function () { syncTera(true); refresh(); }); }
    syncTera(false);
    refresh();

    form.addEventListener('submit', function (e) {
        var lengkap = true;
        inputs.forEach(function (el) { if (!fieldFilled(el)) { lengkap = false; } });
        if (!lengkap) { e.preventDefault(); }
    });
})();
</script>