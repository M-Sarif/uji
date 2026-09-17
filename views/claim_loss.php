<?php
$loId   = isset($_GET['lo']) && array_key_exists((string) $_GET['lo'], LO_LIST)
    ? (string) $_GET['lo']
    : null;
$method = isset($_GET['metode']) && array_key_exists($_GET['metode'], MEASUREMENT_METHODS)
    ? $_GET['metode']
    : 'ijkbout';
$loQuery = $loId !== null ? '&lo=' . urlencode($loId) : '';

// Nilai yang sudah pernah disimpan untuk LO ini (kalau ada)
$saved = ($loId !== null && ($_SESSION['lo_form'][$loId]['metode'] ?? null) === $method)
    ? ($_SESSION['lo_form'][$loId]['nilai'] ?? [])
    : [];
?>
<form method="post" action="index.php" id="claimForm">
    <input type="hidden" name="action" value="save_claim_loss">
    <input type="hidden" name="lo" value="<?php echo h((string) $loId); ?>">
    <input type="hidden" name="metode" value="<?php echo h($method); ?>">

    <div class="claim-pad">
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

        <div class="claim-form">
            <?php foreach (MEASUREMENT_METHODS[$method]['fields'] as $field): ?>
            <div class="claim-form-field">
                <label for="f_<?php echo h($field['key']); ?>"><?php echo h($field['label']); ?> <span class="req">*</span></label>
                <div class="claim-input-wrap">
                    <input type="text" inputmode="decimal"
                           id="f_<?php echo h($field['key']); ?>"
                           name="<?php echo h($field['key']); ?>"
                           value="<?php echo isset($saved[$field['key']]) ? h(rtrim(rtrim(number_format($saved[$field['key']], 3, '.', ''), '0'), '.')) : ''; ?>"
                           placeholder="<?php echo h($field['placeholder'] ?? '0'); ?>" required>
                    <?php if (!empty($field['unit'])): ?><span class="unit"><?php echo h($field['unit']); ?></span><?php endif; ?>
                </div>
                <?php if (!empty($field['hint'])): ?>
                <p class="claim-field-hint">*<?php echo h($field['hint']); ?></p>
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

<script>
/* Tombol "Generate" baru aktif setelah semua isian wajib diisi. */
(function () {
    var form = document.getElementById('claimForm');
    if (!form) { return; }

    var btn    = document.getElementById('btnGenerate');
    var inputs = form.querySelectorAll('input[type="text"][required]');

    function refresh() {
        var lengkap = true;
        inputs.forEach(function (el) {
            if (el.value.trim() === '') { lengkap = false; }
        });
        btn.disabled = !lengkap;
    }

    inputs.forEach(function (el) { el.addEventListener('input', refresh); });
    refresh();
})();
</script>