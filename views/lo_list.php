<?php $selected = !empty($_SESSION['lo_selected']); ?>
<div class="content-pad" style="display:flex;flex-direction:column;min-height:100%;">
    <p style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:2px;">Daftar LO</p>
    <p style="font-size:12px;color:#64748b;margin-bottom:20px;">Pilih LO untuk mengisi checklist</p>

    <a href="index.php?screen=lo_list&select=1" class="lo-card<?php echo $selected ? ' selected' : ''; ?>">
        <div class="lo-checkbox<?php echo $selected ? ' checked' : ''; ?>">
            <?php if ($selected): ?>
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php endif; ?>
        </div>
        <div class="lo-detail">
            <div class="lrow"><span class="label">Nomor LO</span><span class="val">: 8143805561</span></div>
            <div class="lrow"><span class="label">Order</span><span class="val">: PERTALITE 5.000 L</span></div>
            <div class="lrow"><span class="label">Status</span><span class="val <?php echo $selected ? 'done' : 'wait'; ?>">: <?php echo $selected ? 'Telah Diisi' : 'Belum Diisi'; ?></span></div>
        </div>
    </a>

    <div class="row" style="margin-top:auto;padding-top:24px;">
        <span class="btn-ghost" style="opacity:.6;cursor:default;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
            Lihat Checklist
        </span>
        <?php if ($selected): ?>
            <a href="index.php?screen=checklist&step=1" class="btn-primary" style="flex:1;">Mulai Checklist</a>
        <?php else: ?>
            <button class="btn-primary" style="flex:1;" disabled>Mulai Checklist</button>
        <?php endif; ?>
    </div>
</div>
