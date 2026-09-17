<?php
$checked      = $_SESSION['lo_checked'];
$done         = $_SESSION['lo_done'];
$loIds        = array_keys(LO_LIST);
$checkedCount = count(array_filter($checked));
$allChecked   = $checkedCount > 0 && $checkedCount === count($loIds);
$anyChecked   = $checkedCount > 0;

$checkIcon = '<svg viewBox="0 0 24 24" width="14" height="14" style="display:block;fill:none;stroke:#fff;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;"><path d="M5 13l4 4L19 7"/></svg>';
?>
<div class="content-pad" style="display:flex;flex-direction:column;min-height:100%;">
    <p style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:2px;">Daftar LO</p>
    <p style="font-size:12px;color:#64748b;margin-bottom:20px;">Pilih LO untuk mengisi checklist</p>

    <a href="index.php?screen=lo_list&toggle_all=1" class="lo-select-all" style="display:flex;align-items:center;gap:0.75rem;background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;box-shadow:0 2px 12px -4px rgba(0,0,0,0.08);padding:1rem;margin-bottom:1rem;font-size:0.875rem;font-weight:600;color:#334155;">
        <span class="lo-checkbox<?php echo $allChecked ? ' checked' : ''; ?>" style="width:1.25rem;height:1.25rem;border-radius:0.3125rem;border:1px solid #cbd5e1;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:<?php echo $allChecked ? '#2563eb' : '#fff'; ?>;<?php echo $allChecked ? 'border-color:#2563eb;' : ''; ?>">
            <?php if ($allChecked) echo $checkIcon; ?>
        </span>
        <span>Pilih Semua</span>
    </a>

    <?php foreach (LO_LIST as $id => $lo):
        $isChecked = !empty($checked[$id]);
        $isDone    = !empty($done[$id]);
    ?>
    <a href="index.php?screen=lo_list&toggle=<?php echo urlencode($id); ?>" class="lo-card<?php echo $isChecked ? ' selected' : ''; ?>" style="margin-bottom:1rem;">
        <div class="lo-checkbox<?php echo $isChecked ? ' checked' : ''; ?>">
            <?php if ($isChecked) echo $checkIcon; ?>
        </div>
        <div class="lo-detail">
            <div class="lrow"><span class="label">Nomor LO</span><span class="val">: <?php echo h($id); ?></span></div>
            <div class="lrow"><span class="label">Order</span><span class="val"><span class="colon">:</span><span class="order-input"><?php echo h($lo['order']); ?></span></span></div>
            <div class="lrow"><span class="label">Status Checklist</span><span class="val <?php echo $isDone ? 'done' : 'wait'; ?>">: <?php echo $isDone ? 'Sudah Diisi' : 'Belum Diisi'; ?></span></div>
        </div>
    </a>
    <?php endforeach; ?>

    <div class="row" style="margin-top:auto;padding-top:24px;">
        <span class="btn-ghost" style="opacity:.6;cursor:default;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
            Lihat Checklist
        </span>
        <?php if ($anyChecked): ?>
            <a href="index.php?screen=checklist&step=1" class="btn-primary" style="flex:1;">Mulai Checklist</a>
        <?php else: ?>
            <button class="btn-primary" style="flex:1;" disabled>Mulai Checklist</button>
        <?php endif; ?>
    </div>
</div>