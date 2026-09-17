<?php
$loId   = isset($_GET['lo']) ? (string) $_GET['lo'] : null;
$method = isset($_GET['metode']) && array_key_exists($_GET['metode'], MEASUREMENT_METHODS)
    ? $_GET['metode']
    : 'ijkbout';
$loQuery = $loId !== null ? '&lo=' . urlencode($loId) : '';
?>
<div class="content-pad">
    <div class="row" style="justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
        <p class="section-heading" style="margin-bottom:0;">Metode Pengukuran</p>
        <a href="#" class="claim-loss-link">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
            Syarat Claim Losses
        </a>
    </div>
    <p class="section-sub" style="margin-bottom:1rem;">Pilih metode pengukuran pembongkaran BBM.</p>

    <?php foreach (MEASUREMENT_METHODS as $key => $m): $isSelected = $key === $method; ?>
    <a href="index.php?screen=claim_loss&metode=<?php echo h($key); ?><?php echo $loQuery; ?>" class="method-card<?php echo $isSelected ? ' selected' : ''; ?>">
        <div>
            <p class="method-title"><?php echo h($m['title']); ?></p>
            <p class="method-desc"><?php echo h($m['desc']); ?></p>
        </div>
        <span class="radio-dot<?php echo $isSelected ? ' checked' : ''; ?>"></span>
    </a>
    <?php endforeach; ?>

    <div style="margin-top:0.5rem;">
        <?php foreach (MEASUREMENT_METHODS[$method]['fields'] as $field): ?>
        <div class="claim-form-field">
            <label><?php echo h($field['label']); ?><span class="req">*</span></label>
            <div class="claim-input-wrap">
                <input type="text" inputmode="decimal" placeholder="<?php echo h($field['placeholder'] ?? '0'); ?>" disabled>
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
    <span class="btn-prev" style="flex:1;text-align:center;display:flex;align-items:center;justify-content:center;">Rumus Hitung</span>
    <button class="btn-primary btn-next" style="display:flex;align-items:center;justify-content:center;gap:0.375rem;" disabled>
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
        Generate
    </button>
</div>